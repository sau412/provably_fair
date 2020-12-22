<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

db_connect();

$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");

/*
free, bet and pay rolls now updated in real-time
$rolls_stats=db_query_to_array("SELECT
		SUM(IF(`roll_type` IN ('free'),1,0)) AS 'free',
		SUM(IF(`roll_type` IN ('high','low'),1,0)) AS 'bet',
		SUM(IF(`roll_type` IN ('pay'),1,0)) AS 'pay'
	FROM `rolls`");

$free_rolls = $rolls_stats[0]['free'];
$bet_rolls = $rolls_stats[0]['bet'];
$pay_rolls = $rolls_stats[0]['pay'];
*/
$lottery_stats = db_query_to_array("SELECT SUM(`spent`) AS spent, SUM(`tickets`) AS tickets
	FROM `lottery_tickets`
    JOIN `lottery_rounds` ON `lottery_rounds`.`uid` = `lottery_tickets`.`round_uid`
	WHERE `lottery_rounds`.`stop` IS NULL");

$lottery_tickets = $lottery_stats[0]['tickets'];
$lottery_funds = $lottery_stats[0]['spent'];

$total_users=db_query_to_variable("SELECT count(*) FROM `users`");
$active_users=db_query_to_variable("SELECT count(DISTINCT `user_uid`)
	FROM `rolls` WHERE DATE_SUB(NOW(),INTERVAL 1 DAY)<`timestamp`");

set_variable("total_users", $total_users);
set_variable("active_users", $active_users);
set_variable("users_balance", $users_balance);
//set_variable("free_rolls", $free_rolls);
//set_variable("bet_rolls", $bet_rolls);
//set_variable("pay_rolls", $pay_rolls);
set_variable("lottery_tickets", $lottery_tickets);
set_variable("lottery_funds", $lottery_funds);
set_variable("active_users", $active_users);
