<?php
// Lottery-related functions

// Get actual round
function lottery_get_actual_round() {
	$actual_round=db_query_to_variable("SELECT `uid` FROM `lottery_rounds` WHERE `stop` IS NULL");
	return $actual_round;
}

// Get latest finished round
function lottery_get_finished_round() {
	$finished_round=db_query_to_variable("SELECT `uid` FROM `lottery_rounds` WHERE `stop` IS NOT NULL ORDER BY `stop` DESC LIMIT 1");
	return $finished_round;
}

// Get server seed for round
function lottery_get_server_seed($round_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$server_seed=db_query_to_variable("SELECT `seed` FROM `lottery_rounds` WHERE `uid`='$round_uid_escaped'");
	return $server_seed;
}

// Get server seed hash
function lottery_get_server_seed_hash($round_uid) {
	$server_seed=lottery_get_server_seed($round_uid);
	$seed_hash=hash("sha256",$server_seed);
	return $seed_hash;
}

// Get round start
function lottery_get_round_start($round_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$start=db_query_to_variable("SELECT `start` FROM `lottery_rounds` WHERE `uid`='$round_uid'");
	return $start;
}

// Get round stop
function lottery_get_round_stop($round_uid) {
	global $lottery_round_length;
	$round_uid_escaped=db_escape($round_uid);
	$stop=db_query_to_variable("SELECT `stop` FROM `lottery_rounds` WHERE `uid`='$round_uid'");
	if(!$stop) {
		$stop=db_query_to_variable("SELECT DATE_ADD(`start`,INTERVAL $lottery_round_length SECOND)
			FROM `lottery_rounds` WHERE `uid`='$round_uid'");
	}
	return $stop;
}

// Get round stop interval
function lottery_get_round_stop_interval($round_uid) {
	global $lottery_round_length;
	$round_length_escaped=db_escape($lottery_round_length);
	$round_uid_escaped=db_escape($round_uid);
	$interval=db_query_to_variable("SELECT '$round_length_escaped'+(UNIX_TIMESTAMP(`start`)-UNIX_TIMESTAMP(NOW()))
		FROM `lottery_rounds` WHERE `uid`='$round_uid'");
	return $interval;
}

// Get round tickets
function lottery_get_round_tickets($round_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$tickets=db_query_to_variable("SELECT SUM(`tickets`) FROM `lottery_tickets` WHERE `round_uid`='$round_uid'");
	if(!$tickets) $tickets=0;
	return $tickets;
}

// Get round user tickets
function lottery_get_round_user_tickets($round_uid,$user_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$user_uid_escaped=db_escape($user_uid);
	$tickets=db_query_to_variable("SELECT `tickets` FROM `lottery_tickets`
		WHERE `round_uid`='$round_uid_escaped' AND `user_uid`='$user_uid_escaped'");
	if(!$tickets) $tickets=0;
	return $tickets;
}

// Get round prize fund
function lottery_get_round_prize_fund($round_uid) {
	global $lottery_ticket_price;
	global $lottery_min_funds;
	$round_uid_escaped=db_escape($round_uid);
	$spent=db_query_to_variable("SELECT SUM(`spent`) FROM `lottery_tickets` WHERE `round_uid`='$round_uid'");
	if(!$spent) $spent=0;
	return max($spent,$lottery_min_funds);
}

// Free tickets
function lottery_free_tickets($round_uid,$user_uid,$amount) {
	$round_uid_escaped=db_escape($round_uid);
	$user_uid_escaped=db_escape($user_uid);
	$amount_escaped=db_escape($amount);
	db_query("INSERT INTO `lottery_tickets` (`round_uid`,`user_uid`,`spent`,`tickets`)
			VALUES ('$round_uid_escaped','$user_uid_escaped','0','$amount_escaped')
			ON DUPLICATE KEY UPDATE `tickets`=`tickets`+VALUES(`tickets`)");

	// Log
	log_write("Lottery: free $amount tickets to user");
}

// Buy tickets
function lottery_buy_tickets($round_uid,$user_uid,$amount) {
	global $lottery_ticket_price;
	$round_uid_escaped=db_escape($round_uid);
	$user_uid_escaped=db_escape($user_uid);
	$amount_escaped=db_escape($amount);
	$spent=$amount*$lottery_ticket_price;
	$spent_escaped=db_escape($spent);

	// Check number
	if(floor($amount) != $amount) return;
	if($amount<1) return;

	// Check user's balance
	$user_balance=get_user_balance($user_uid);
	if ($user_balance < $spent) return;

	// Add user's tickets
	db_query("INSERT INTO `lottery_tickets` (`round_uid`,`user_uid`,`spent`,`tickets`)
			VALUES ('$round_uid_escaped','$user_uid_escaped','$spent_escaped','$amount_escaped')
			ON DUPLICATE KEY UPDATE
				`tickets`=`tickets`+VALUES(`tickets`),
				`spent`=`spent`+VALUES(`spent`)");

	// Change user balance
	change_user_balance($user_uid,-$spent);

	// Log
	log_write("Lottery: bought $amount tickets");
}

// Lotto close round
function lottery_close_round() {
	// Get current round uid
	$round_uid=lottery_get_actual_round();
	// Round could not exists (at zero round)
	if($round_uid) {
		$round_uid_escaped=db_escape($round_uid);

		// Mark round as closed
		db_query("UPDATE `lottery_rounds` SET `stop`=NOW() WHERE `uid`='$round_uid'");

		// Calculate user's best hashes
		lottery_calc_all_users_best_hashes($round_uid);

		// Send rewards to winners
		lottery_set_winners($round_uid);
	}

	// Start new round
	$seed=bin2hex(random_bytes(32));
	$seed_escaped=db_escape($seed);
	db_query("INSERT INTO `lottery_rounds` (`seed`,`start`) VALUES ('$seed_escaped',NOW())");

	// Log
	log_write("Lottery: old round closed, new round started");
}

// Set winners
function lottery_set_winners($round_uid) {
	$round_uid_escaped=db_escape($round_uid);

	// Check if hashes not set
	$hashes_not_exists=db_query_to_variable("SELECT 1 FROM `lottery_tickets` WHERE `best_hash` IS NULL");
//	if($hashes_not_exists) return;

	// Check if round already have winners
	$winners_already_set=db_query_to_variable("SELECT 1 FROM `lottery_tickets` WHERE `reward` IS NOT NULL");
//	if($winners_already_set) return;

	// Get prize fund
	$prize_fund=lottery_get_round_prize_fund($round_uid);

	// Set winners
	$places_data=db_query_to_array("SELECT `place`,`percentage` FROM `lottery_rewards` ORDER BY `place` ASC");
	$winners_data=db_query_to_array("SELECT `uid`,`user_uid` FROM `lottery_tickets`
		WHERE `round_uid`='$round_uid_escaped' ORDER BY `best_hash` ASC");

	$place=0;
	foreach($winners_data as $winner) {
		$ticket_uid=$winner['uid'];
		$user_uid=$winner['user_uid'];
		if(isset($places_data[$place])) {
			$percentage=$places_data[$place]['percentage'];
			$reward=$prize_fund*$percentage/100;
		} else {
			$reward=0;
		}

		// Write rewards
		$ticket_uid_escaped=db_escape($ticket_uid);
		$reward_escaped=db_escape($reward);
		db_query("UPDATE `lottery_tickets` SET `reward`='$reward_escaped' WHERE `uid`='$ticket_uid_escaped'");

		// Change user balance
		change_user_balance($user_uid,$reward);

		$place++;
	}
}

// Lotto calculate all users best hashes
function lottery_calc_all_users_best_hashes($round_uid) {
	$round_uid_escaped=db_escape($round_uid);
	$users_data=db_query_to_array("SELECT `user_uid` FROM `lottery_tickets` WHERE `round_uid`='$round_uid_escaped'");
	foreach($users_data as $users_row) {
		$user_uid=$users_row['user_uid'];
		lottery_calc_user_best_hash($round_uid,$user_uid);
	}
}

// Lotto get user best hash
function lottery_calc_user_best_hash($round_uid,$user_uid) {
	// Get required parameters
	$user_tickets=lottery_get_round_user_tickets($round_uid,$user_uid);
	$server_seed=lottery_get_server_seed($round_uid);
	$user_seed=get_user_seed($user_uid);

	// Calculate best user's hash
	for($i=0;$i!=$user_tickets;$i++) {
		$hash=hash("sha256","$i.$server_seed.$user_seed");
		if(!isset($best_hash) || $hash<$best_hash) {
			$best_hash=$hash;
		}
	}

	// Write this hash to DB
	$user_uid_escaped=db_escape($user_uid);
	$round_uid_escaped=db_escape($round_uid);
	$user_seed_escaped=db_escape($user_seed);
	$best_hash_escaped=db_escape($best_hash);

	db_query("UPDATE `lottery_tickets`
		SET `user_seed`='$user_seed_escaped',`best_hash`='$best_hash_escaped'
		WHERE `user_uid`='$user_uid_escaped' AND `round_uid`='$round_uid_escaped'");
}
