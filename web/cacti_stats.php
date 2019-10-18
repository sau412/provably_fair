<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");

db_connect();

$wallet_balance=db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='wallet_balance'");
$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");
$free_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type`='free'");
$bet_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type` IN ('high','low')");
$pay_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type` IN ('pay')");
$total_users=db_query_to_variable("SELECT count(*) FROM `users`");
$active_users=db_query_to_variable("SELECT count(DISTINCT `user_uid`) FROM `rolls` WHERE DATE_SUB(NOW(),INTERVAL 1 DAY)<`timestamp`");

echo "total_users:$total_users";
echo " active_users:$active_users";
echo " wallet_balance:$wallet_balance";
echo " users_balance:$users_balance";
echo " free_rolls:$free_rolls";
echo " bet_rolls:$bet_rolls";
echo " pay_rolls:$pay_rolls";
?>
