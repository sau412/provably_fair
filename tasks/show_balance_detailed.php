<?php
// Only for command line
if(!isset($argc)) die();

// Gridcoinresearch send rewards
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/logger.php");
db_connect();

$user_uid = $argv[1];

if(!$user_uid) {
    die("User_uid as argument required\n");
}

$details = get_balance_detailed($user_uid);

$balance = 0;
echo "timestamp;type;balance_before;amount;balance_after\n";
foreach($details as $row) {
    $timestamp = $row['timestamp'];
    $type = $row['type'];
    $amount = $row['amount'];
    $balance_before = $balance;
    $balance += $amount;
    $balance_after = $balance;
    echo "$timestamp;$type;$balance_before;$amount;$balance_after\n";
}