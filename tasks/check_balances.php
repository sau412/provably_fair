<?php
// Only for command line
if(!isset($argc)) die();

// Gridcoinresearch send rewards
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/logger.php");
db_connect();

$user_uids_array=db_query_to_array("SELECT * FROM `users`");

foreach($user_uids_array as $row) {
	$user_uid=$row['uid'];
	$balance_old=$row['balance'];
	$balance_detailed = get_user_balance_detailed($user_uid);

    if(abs($balance_old - $balance_detailed['balance']) > 0.0001) {
        echo "Balance mismatch user uid $user_uid: old balance $balance_old\n";
        var_dump($balance_detailed);
    }
}

function get_user_balance_detailed($user_uid) {
	$result = [];
	$user_uid_escaped=db_escape($user_uid);

	$balance=0;

	// Send and receive coins data
	$amount_received=db_query_to_variable("SELECT SUM(`amount`) FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `status` IN ('received')");
	$amount_sent=db_query_to_variable("SELECT SUM(`amount`) FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `status` IN ('processing','sent')");
	$result['amount_received'] = $amount_received;
	$result['amount_sent'] = $amount_sent;

	$balance+=$amount_received;
	$balance-=$amount_sent;

	// Bets and free rolls data
	$amount_bets=db_query_to_variable("SELECT SUM(`bet`) FROM `rolls` WHERE `user_uid`='$user_uid_escaped'");
	$amount_profits=db_query_to_variable("SELECT SUM(`profit`) FROM `rolls` WHERE `user_uid`='$user_uid_escaped'");
	$result['amount_bets'] = $amount_bets;
	$result['amount_profits'] = $amount_profits;

	$balance-=$amount_bets;
	$balance+=$amount_profits;

	// Minesweeper data
	$amount_profits_m=db_query_to_variable("SELECT SUM(`profit`) FROM `minesweeper` WHERE `user_uid`='$user_uid_escaped'");
	$result['amount_profits_m'] = $amount_profits_m;

	$balance+=$amount_profits_m;

	// Lottery data
	$amount_spent_l=db_query_to_variable("SELECT SUM(`spent`) FROM `lottery_tickets` WHERE `user_uid`='$user_uid_escaped'");
	$amount_profits_l=db_query_to_variable("SELECT SUM(`reward`) FROM `lottery_tickets` WHERE `user_uid`='$user_uid_escaped' AND `reward` IS NOT NULL");
	$result['amount_spent_l'] = $amount_received;
	$result['amount_profits_l'] = $amount_sent;

	$balance-=$amount_spent_l;
	$balance+=$amount_profits_l;

	//db_query("UPDATE `users` SET `balance`='$balance' WHERE `uid`='$user_uid_escaped'");
	$result['balance'] = (string) $balance;
	return $result;
}