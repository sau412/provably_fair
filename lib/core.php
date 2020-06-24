<?php
// Core functions

// Escape text to show in html page as text
function html_escape($data) {
        $data=htmlspecialchars($data);
        $data=str_replace("'","&apos;",$data);
        return $data;
}

// Add message to log
function write_log($message, $user_uid = 0, $severity=7) {
        global $project_log_name;
        
        broker_add("logger", ["source" => $project_log_name, "severity" => $severity, "message" => $message]);
}

// Checks is string contains only ASCII symbols
function validate_ascii($string) {
        if(strlen($string)>100) return FALSE;
        if(is_string($string)==FALSE) return FALSE;
        for($i=0;$i!=strlen($string);$i++) {
                if(ord($string[$i])<32 || ord($string[$i])>127) return FALSE;
        }
        return TRUE;
}

// Checks is string contains number
function validate_number($string) {
        if(strlen($string)>20) return FALSE;
        if(is_string($string)==FALSE) return FALSE;
        return is_numeric($string);
}

// Get variable
function get_variable($name) {
        $name_escaped=db_escape($name);
        return db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='$name_escaped'");
}

// Set variable
function set_variable($name,$value) {
        $name_escaped=db_escape($name);
        $value_escaped=db_escape($value);
        db_query("INSERT INTO `variables` (`name`,`value`) VALUES ('$name_escaped','$value_escaped') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
}

// Create or get session
function get_session() {
        if(isset($_COOKIE['session_id']) && validate_ascii($_COOKIE['session_id'])) {
                $session=$_COOKIE['session_id'];
                $session_escaped=db_escape($session);
                $session_exists=db_query_to_variable("SELECT 1 FROM `sessions` WHERE `session`='$session_escaped'");
                if(!$session_exists) {
                        unset($session);
                }
        }

        if(!isset($session)) {
                $session=bin2hex(random_bytes(32));
                $token=bin2hex(random_bytes(32));
                setcookie('session_id',$session,time()+86400*30);
                $session_escaped=db_escape($session);
                $token_escaped=db_escape($token);
                db_query("INSERT INTO `sessions` (`session`,`token`) VALUES ('$session_escaped','$token_escaped')");
        }
        return $session;
}

// Get user uid
function get_user_uid_by_session($session) {
        $session_escaped=db_escape($session);
        $user_uid=db_query_to_variable("SELECT `user_uid` FROM `sessions` WHERE `session`='$session_escaped'");
        return $user_uid;
}

// Get user token
function get_user_token_by_session($session) {
        $session_escaped=db_escape($session);
        $token=db_query_to_variable("SELECT `token` FROM `sessions` WHERE `session`='$session_escaped'");
        return $token;
}

// Create new user
function user_register($session,$mail,$login,$password1,$password2,$withdraw_address) {
        global $global_salt;

        if(get_variable("login_enabled")==0) return "register_failed_disabled";

        if($password1!=$password2) return "register_failed_password_mismatch";

        $session_escaped=db_escape($session);
        $salt=bin2hex(random_bytes(16));
        $salt_escaped=db_escape($salt);

        $password_hash=hash("sha256",$password1.strtolower($login).$salt.$global_salt);

        $message="";

        if(validate_ascii($login)) {
                $login_escaped=db_escape($login);
                $mail_escaped=db_escape($mail);
                $withdraw_address_escaped=db_escape($withdraw_address);
                $exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `login`='$login_escaped'");
                $server_seed=bin2hex(random_bytes(32));
                $user_seed=bin2hex(random_bytes(5));
                $server_seed_escaped=db_escape($server_seed);
                $user_seed_escaped=db_escape($user_seed);
                if($exists_hash=="") {
                        write_log("New user '$login' mail '$mail'");
                        db_query("INSERT INTO `users` (`mail`,`login`,`password_hash`,`salt`,`register_time`,`login_time`,`withdraw_address`,`server_seed`,`user_seed`)
VALUES ('$mail_escaped','$login_escaped','$password_hash','$salt_escaped',NOW(),NOW(),'$withdraw_address_escaped','$server_seed_escaped','$user_seed_escaped')");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
                        return "register_successfull";
                        return TRUE;
                } else if($password_hash==$exists_hash) {
                        write_log("Logged in '$login'");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
                        return "login_successfull";
                } else {
                        write_log("Invalid password for '$login'");
                        return "register_failed_invalid_password";
                }
        } else {
                write_log("Invalid login for '$login'");
                return "register_failed_invalid_login";
        }
}

// Check user login and password
function user_login($session,$login,$password) {
        global $global_salt;

        $session_escaped=db_escape($session);

        $message="";

        if(validate_ascii($login)) {
                $login_escaped=db_escape($login);
                $exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `login`='$login_escaped'");
                $salt=db_query_to_variable("SELECT `salt` FROM `users` WHERE `login`='$login_escaped'");
                $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                $user_uid_escaped=db_escape($user_uid);

                if(get_variable("login_enabled")==0 && !is_admin($user_uid)) return "login_failed_disabled";

                $password_hash=hash("sha256",$password.strtolower($login).$salt.$global_salt);

                if($password_hash==$exists_hash) {
                        write_log("Logged in user '$login'",$user_uid);
                        //notify_user($user_uid,"Logged in $login","IP: ".$_SERVER['REMOTE_ADDR']);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid' WHERE `session`='$session_escaped'");
                        db_query("UPDATE `users` SET `login_time`=NOW() WHERE `uid`='$user_uid_escaped'");
                        return "login_successfull";
                } else {
                        write_log("Invalid password for '$login'",$user_uid);
                        //notify_user($user_uid,"Log in failed","IP: ".$_SERVER['REMOTE_ADDR']);
                        return "login_failed_invalid_password";
                }
        } else {
                write_log("Invalid login for '$login'");
                return "login_failed_invalid_login";
        }
}

// Change settings
function user_change_settings($user_uid,$mail,$withdraw_address,$password,$new_password1,$new_password2) {
        global $global_salt;

        if($new_password1!=$new_password2) {
                //notify_user($user_uid,"Change settings fail","Change settings failed, new password mismatch");
                return "user_change_settings_failed_new_password_mismatch";
        }

        $user_uid_escaped=db_escape($user_uid);
        $user_data_array=db_query_to_array("SELECT `mail`,`login`,`salt`,`password_hash`,`withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $user_data=array_pop($user_data_array);
        $login=$user_data['login'];
        $salt=$user_data['salt'];
        $password_hash=$user_data['password_hash'];
        $entered_password_hash=hash("sha256",$password.strtolower($login).$salt.$global_salt);

        if($password_hash==$entered_password_hash) {
                if($mail!=$user_data['mail']) {
                        //notify_user($user_uid,"Settings changed","E-mail changed to: $mail");
                        $mail_escaped=db_escape($mail);
                        db_query("UPDATE `users` SET `mail`='$mail_escaped' WHERE `uid`='$user_uid_escaped'");
                        $change_log="New e-mail: $mail\n";
                }

                if($new_password1!='') {
                        $new_password_hash=hash("sha256",$new_password1.strtolower($login).$salt.$global_salt);
                        $new_password_hash_escaped=db_escape($new_password_hash);
                        db_query("UPDATE `users` SET `password_hash`='$new_password_hash_escaped' WHERE `uid`='$user_uid_escaped'");
                        $change_log="New password applied\n";
                }

                if($withdraw_address!=$user_data['withdraw_address']) {
                        $withdraw_address_escaped=db_escape($withdraw_address);
                        db_query("UPDATE `users` SET `withdraw_address`='$withdraw_address_escaped' WHERE `uid`='$user_uid_escaped'");
                        $change_log="New withdraw address: $withdraw_address\n";
                }

                //notify_user($user_uid,"Settings changed",$change_log);
		write_log("Settings changed\n$change_log",$user_uid);
                return "user_change_settings_successfull";
        } else {
                //notify_user($user_uid,"Change settings fail","Change settings failed, password incorrect");
		write_log("Settings not changed: password incorrect",$user_uid);
                return "user_change_settings_failed_password_incorrect";
        }
}

// Admin change settings
function admin_change_settings($login_enabled,$payouts_enabled,$info,$global_message) {
        // Login enabled
        $login_enabled_value=$login_enabled=="enabled"?"1":"0";
        set_variable("login_enabled",$login_enabled_value);

        // Payouts enabled
        $payouts_enabled_value=$payouts_enabled=="enabled"?"1":"0";
        set_variable("payouts_enabled",$payouts_enabled_value);

        // News
        set_variable("info",$info);

        // Global message
        set_variable("global_message",$global_message);

	// Log
	write_log("Admin settings changed");

        return "admin_change_settings_successfull";
}

// Get username by uid
function get_username_by_uid($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $login=db_query_to_variable("SELECT `login` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $login;
}

// Logout user
function user_logout($session) {
        $user_uid=get_user_uid_by_session($session);
        $username=get_username_by_uid($user_uid);
        write_log("Logged out user '$username'",$user_uid);
        //notify_user($user_uid,"Log out $username","IP: ".$_SERVER['REMOTE_ADDR']);

        $session_escaped=db_escape($session);
        db_query("UPDATE `sessions` SET `user_uid`=NULL WHERE `session`='$session_escaped'");
        return "logout_successfull";
}

// Get user balance
function get_user_balance($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $balance=db_query_to_variable("SELECT `balance` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $balance=sprintf("%0.8F",$balance);
        return $balance;
}

// Update balance
function update_user_balance($user_uid) {
        $user_uid_escaped=db_escape($user_uid);

	$balance=0;

	// Send and receive coins data
        $amount_received=db_query_to_variable("SELECT SUM(`amount`) FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `status` IN ('received')");
        $amount_sent=db_query_to_variable("SELECT SUM(`amount`) FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `status` IN ('processing','sent')");

	$balance+=$amount_received;
	$balance-=$amount_sent;

	// Bets and free rolls data
        $amount_bets=db_query_to_variable("SELECT SUM(`bet`) FROM `rolls` WHERE `user_uid`='$user_uid_escaped'");
        $amount_profits=db_query_to_variable("SELECT SUM(`profit`) FROM `rolls` WHERE `user_uid`='$user_uid_escaped'");

	$balance-=$amount_bets;
	$balance+=$amount_profits;

	// Minesweeper data
        $amount_profits_m=db_query_to_variable("SELECT SUM(`profit`) FROM `minesweeper` WHERE `user_uid`='$user_uid_escaped'");

	$balance+=$amount_profits_m;

	// Lottery data
	$amount_spent_l=db_query_to_variable("SELECT SUM(`spent`) FROM `lottery_tickets` WHERE `user_uid`='$user_uid_escaped'");
	$amount_profits_l=db_query_to_variable("SELECT SUM(`reward`) FROM `lottery_tickets` WHERE `user_uid`='$user_uid_escaped' AND `reward` IS NOT NULL");

	$balance-=$amount_spent_l;
	$balance+=$amount_profits_l;

        db_query("UPDATE `users` SET `balance`='$balance' WHERE `uid`='$user_uid_escaped'");
}

// Send
function user_withdraw($user_uid,$amount) {
        global $currency_short;

        // Check payouts enabled
        if(get_variable("payouts_enabled")==0) return FALSE;

        $address=get_user_withdraw_address($user_uid);

        // Validate data
        if(!validate_number($amount)) return FALSE;
        if(!validate_ascii($address)) return FALSE;

        $min_amount=get_variable("withdraw_min");
        if($amount<$min_amount) return FALSE;

        if($address=="") return FALSE;

	// Lock tables
	db_query("LOCK TABLES `users` WRITE, `transactions` WRITE, `rolls` WRITE, `minesweeper` WRITE, `lottery_tickets` WRITE");

        // Check user balance
        $balance=get_user_balance($user_uid);
        if($balance<$amount) return FALSE;

        // Add transaction to schedule
        $user_uid_escaped=db_escape($user_uid);
        $amount_escaped=db_escape($amount);
        $address_escaped=db_escape($address);
        db_query("INSERT INTO `transactions` (`user_uid`,`amount`,`address`,`status`) VALUES ('$user_uid_escaped','$amount_escaped','$address_escaped','processing')");
        $transaction_uid=mysql_insert_id();

        // Adjust user balance
        update_user_balance($user_uid);

	// Unlock tables
	db_query("UNLOCK TABLES");

        // Send notifications
        $username=get_username_by_uid($user_uid);
        write_log("Withdraw '$amount' $currency_short to address '$address'",$user_uid);
        //notify_user($user_uid,"$username sent $amount $currency_short","Amount: $amount $currency_short\nAddress: $address\nIP: ".$_SERVER['REMOTE_ADDR']);

        return $transaction_uid;
}

function recaptcha_check($response) {
        global $recaptcha_private_key;
        $recaptcha_url="https://www.google.com/recaptcha/api/siteverify";
        $query="secret=$recaptcha_private_key&response=$response&remoteip=".$_SERVER['REMOTE_ADDR'];
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
        curl_setopt($ch,CURLOPT_URL,$recaptcha_url);
        $result = curl_exec ($ch);
        $data = json_decode($result);
        if($data->success) return TRUE;
        else return FALSE;
}

// Checks is user admin
function is_admin($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $result=db_query_to_variable("SELECT `is_admin` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($result==1) return TRUE;
        else return FALSE;
}

// Get server seed
function get_server_seed($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $result=db_query_to_variable("SELECT `server_seed` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $result;
}

// Get server seed hash
function get_server_seed_hash($user_uid) {
        return hash("sha256",get_server_seed($user_uid));
}

// Regenerate server seed
function regen_server_seed($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $new_seed=bin2hex(random_bytes(32));
        $new_seed_escaped=db_escape($new_seed);
        db_query("UPDATE `users` SET `server_seed`='$new_seed_escaped' WHERE `uid`='$user_uid_escaped'");
}

// Get user seed
function get_user_seed($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $result=db_query_to_variable("SELECT `user_seed` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $result;
}

// Update user seed
function update_user_seed($user_uid,$seed) {
        $user_uid_escaped=db_escape($user_uid);
        if(!validate_ascii($seed)) return FALSE;
        $seed_escaped=db_escape($seed);
        db_query("UPDATE `users` SET `user_seed`='$seed_escaped' WHERE `uid`='$user_uid_escaped'");
}

// Change user balance
function change_user_balance($user_uid,$balance_delta) {
        $user_uid_escaped=db_escape($user_uid);
        $balance_delta_escaped=db_escape($balance_delta);
        db_query("UPDATE `users` SET `balance`=`balance`+'$balance_delta_escaped' WHERE `uid`='$user_uid_escaped'");
}

// Get user deposit address
function get_user_deposit_address($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $result=db_query_to_variable("SELECT `deposit_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $result;
}

// Get user withdrawal address
function get_user_withdraw_address($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $result=db_query_to_variable("SELECT `withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $result;
}

// Roll
function get_roll_result($server_seed,$user_seed) {
        $hash=hash("sha256","$server_seed.$user_seed");
        $bytes=substr($hash,0,8);
        $number=hexdec($bytes);
//return $number;
        $roll=round($number/429496.7295);
        $roll=sprintf("%05d",$roll);
        return $roll;
}

// Check free roll cooldown
function free_roll_cooldown_active($user_uid) {
        global $free_roll_cooldown_interval;
        $user_uid_escaped=db_escape($user_uid);
        $user_ip=$_SERVER['REMOTE_ADDR'];
        $user_ip_escaped=db_escape($user_ip);

	$user_uid_escaped=db_escape($user_uid);
        $interval_user=db_query_to_variable("SELECT UNIX_TIMESTAMP(NOW())-COALESCE(UNIX_TIMESTAMP(`last_roll_time`),0) FROM `users` WHERE `uid`='$user_uid_escaped'");
        $interval_ip=db_query_to_variable("SELECT UNIX_TIMESTAMP(NOW())-COALESCE(UNIX_TIMESTAMP(`timestamp`),0) FROM `ip_rolls` WHERE `ip`='$user_ip_escaped'");
        if($interval_user==="") $interval_user=$free_roll_cooldown_interval;
        if($interval_ip==="") $interval_ip=$free_roll_cooldown_interval;
//var_dump($interval_user,$interval_ip);
        $interval=min($interval_user,$interval_ip);
        $wait_interval=$free_roll_cooldown_interval-$interval;
        if($wait_interval>0) return $wait_interval;

        return 0;
}

// Free roll
function do_free_roll($user_uid) {
        global $free_roll_cooldown_interval;

        db_query("LOCK TABLES `ip_rolls` WRITE, `users` WRITE");

        $wait_interval=free_roll_cooldown_active($user_uid);
        if($wait_interval>0) {
                return array("result"=>"fail","reason"=>"Only one roll in $free_roll_cooldown_interval seconds allowed. Wait $wait_interval seconds more.");
        }

	$user_uid_escaped=db_escape($user_uid);
        db_query("UPDATE `users` SET `last_roll_time`=NOW() WHERE `uid`='$user_uid_escaped'");
        $user_ip=$_SERVER['REMOTE_ADDR'];
        $user_ip_escaped=db_escape($user_ip);
        db_query("INSERT INTO `ip_rolls` (`ip`) VALUES ('$user_ip_escaped') ON DUPLICATE KEY UPDATE `timestamp`=NOW()");

        db_query("UNLOCK TABLES");

        $user_seed=get_user_seed($user_uid);
        $server_seed=get_server_seed($user_uid);
        $roll_result=get_roll_result($server_seed,$user_seed);

        $user_uid_escaped=db_escape($user_uid);
        $user_seed_escaped=db_escape($user_seed);
        $server_seed_escaped=db_escape($server_seed);
        $bet_escaped=db_escape("0");
        $roll_result_escaped=db_escape($roll_result);
        $roll_type_escaped=db_escape('free');

        $reward=db_query_to_variable("SELECT `reward` FROM `rewards` WHERE '$roll_result_escaped' BETWEEN `roll_min` AND `roll_max`");

        $reward_escaped=db_escape($reward);

        db_query("INSERT INTO `rolls` (`user_uid`,`roll_type`,`server_seed`,`user_seed`,`roll_result`,`bet`,`profit`)
VALUES ('$user_uid_escaped','$roll_type_escaped','$server_seed_escaped','$user_seed_escaped','$roll_result_escaped','$bet_escaped','$reward_escaped')");

        change_user_balance($user_uid,$reward);
        regen_server_seed($user_uid);
        $balance=get_user_balance($user_uid);
        $server_seed_hash=get_server_seed_hash($user_uid);
        $result=array(
                "result"=>"ok",
                "roll"=>$roll_result,
                "reward"=>$reward,
                "balance"=>$balance,
                "server_seed_hash"=>$server_seed_hash
        );

	// Log
	write_log("Free roll, reward: $reward",$user_uid);

        return $result;
}

// Dice roll
function do_dice_roll($user_uid,$bet,$type) {
        $user_seed=get_user_seed($user_uid);
        $server_seed=get_server_seed($user_uid);
        $roll_result=get_roll_result($server_seed,$user_seed);
        $balance=get_user_balance($user_uid);

        if($bet>$balance) {
                return array("result"=>"fail","reason"=>"Bet is bigger than balance");
        }

        $bet_min=get_variable("bet_min");
        if($bet<$bet_min) {
                return array("result"=>"fail","reason"=>"Bet is lower than minimum");
        }

        $bet_max=get_variable("bet_max");
        if($bet>$bet_max) {
                return array("result"=>"fail","reason"=>"Bet is higher than maximum");
        }

        $user_uid_escaped=db_escape($user_uid);
        $user_seed_escaped=db_escape($user_seed);
        $server_seed_escaped=db_escape($server_seed);
        $bet_escaped=db_escape($bet);
        $roll_result_escaped=db_escape($roll_result);
        $roll_type_escaped=db_escape($type);
        if($type=='low') {
                $bet_lo_limit=get_variable("bet_lo_limit");
                if($roll_result<=$bet_lo_limit) $reward=$bet*2;
                else $reward=0;
        } else {
                $bet_hi_limit=get_variable("bet_hi_limit");
                if($roll_result>=$bet_hi_limit) $reward=$bet*2;
                else $reward=0;
        }

        $reward_escaped=db_escape($reward);

        db_query("INSERT INTO `rolls` (`user_uid`,`roll_type`,`server_seed`,`user_seed`,`roll_result`,`bet`,`profit`)
VALUES ('$user_uid_escaped','$roll_type_escaped','$server_seed_escaped','$user_seed_escaped','$roll_result_escaped','$bet_escaped','$reward_escaped')");

        change_user_balance($user_uid,$reward-$bet);
        regen_server_seed($user_uid);
        $balance=get_user_balance($user_uid);
        $server_seed_hash=get_server_seed_hash($user_uid);
        $result=array(
                "result"=>"ok",
                "roll"=>$roll_result,
                "type"=>$type,
                "bet"=>$bet,
                "reward"=>$reward,
                "balance"=>$balance,
                "server_seed_hash"=>$server_seed_hash
        );

	// Log
	write_log("Dice roll, bet: $bet, reward: $reward",$user_uid);

        return $result;
}

// Payroll
function do_payroll($user_uid,$reward) {
        $user_uid_escaped=db_escape($user_uid);
        $user_seed_escaped="";
        $server_seed_escaped="";
        $bet_escaped=db_escape("0");
        $roll_result_escaped="";
        $roll_type_escaped=db_escape('pay');

        $reward_escaped=db_escape($reward);

        db_query("INSERT INTO `rolls` (`user_uid`,`roll_type`,`server_seed`,`user_seed`,`bet`,`profit`)
VALUES ('$user_uid_escaped','$roll_type_escaped','$server_seed_escaped','$user_seed_escaped','$bet_escaped','$reward_escaped')");

        change_user_balance($user_uid,$reward);

        $balance=get_user_balance($user_uid);
}

// For php 5 only variant for random_bytes is openssl_random_pseudo_bytes from openssl lib
if(!function_exists("random_bytes")) {
        function random_bytes($n) {
                return openssl_random_pseudo_bytes($n);
        }
}
?>
