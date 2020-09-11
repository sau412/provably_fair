<?php
// Only for command line
if(!isset($argc)) die();

// Gridcoinresearch send rewards
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");
db_connect();

$user_uids_array=db_query_to_array("SELECT * FROM `users`");

foreach($user_uids_array as $row) {
	$user_uid=$row['uid'];
	$balance_old=$row['balance'];
	update_user_balance($user_uid);
	$balance_new=db_query_to_variable("SELECT `balance` FROM `users` WHERE `uid`='$user_uid'");
	if($balance_old!=$balance_new) {
		echo "Balance changed user $user_uid from $balance_old GRC to $balance_new GRC\n";
	} else {
		 echo "Balance ok user $user_uid\n";
	}
}
