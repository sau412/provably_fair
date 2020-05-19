<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");

db_connect();

$wallet_balance=db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='wallet_balance'");
$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");

$rolls_stats=db_query_to_array("SELECT
SUM(IF(`roll_type` IN ('free'),1,0)) AS 'free',
SUM(IF(`roll_type` IN ('high','low'),1,0)) AS 'bet',
SUM(IF(`roll_type` IN ('pay'),1,0)) AS 'pay'
FROM `rolls`");

$free_rolls = $rolls_stats[0]['free'];
$bet_rolls = $rolls_stats[0]['bet'];
$pay_rolls = $rolls_stats[0]['pay'];

/*
$free_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type`='free'");
$bet_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type` IN ('high','low')");
$pay_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type` IN ('pay')");
*/

$lottery_tickets=db_query_to_variable("SELECT SUM(`tickets`)
	FROM `lottery_tickets`
	WHERE `round_uid` IN (
		SELECT `uid` FROM `lottery_rounds`
		WHERE `stop` IS NULL)");
$lottery_funds=db_query_to_variable("SELECT SUM(`spent`)
	FROM `lottery_tickets`
	WHERE `round_uid` IN (
		SELECT `uid` FROM `lottery_rounds`
		WHERE `stop` IS NULL)");
$total_users=db_query_to_variable("SELECT count(*) FROM `users`");
$active_users=db_query_to_variable("SELECT count(DISTINCT `user_uid`)
	FROM `rolls` WHERE DATE_SUB(NOW(),INTERVAL 1 DAY)<`timestamp`");

echo "total_users:$total_users";
echo " active_users:$active_users";
echo " wallet_balance:$wallet_balance";
echo " users_balance:$users_balance";
echo " free_rolls:$free_rolls";
echo " lottery_tickets:$lottery_tickets";
echo " lottery_funds:$lottery_funds";
echo " bet_rolls:$bet_rolls";
echo " pay_rolls:$pay_rolls";
?>
