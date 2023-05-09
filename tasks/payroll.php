<?php
// Only for command line
if(!isset($argc)) die();

// Gridcoinresearch send rewards
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/logger.php");

// Check if unsent rewards exists
db_connect();

log_write("Task: Payroll started");

$users_data_array = db_query_to_array("SELECT `uid`,`balance` FROM `users`");

foreach($users_data_array as $user_data) {
	$user_uid = $user_data['uid'];
	$balance = $user_data['balance'];
	$amount = $balance * $daily_percentage / 100;
	if($amount > 0) {
		do_payroll($user_uid, $amount);
	}
}

log_write("Task: Payroll finished");
