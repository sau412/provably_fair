<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

$wallet_balance = get_variable("wallet_balance");
$users_balance = get_variable("users_balance");

$free_rolls = get_variable("free_rolls");
$bet_rolls = get_variable("bet_rolls");
$pay_rolls = get_variable("pay_rolls");

$lottery_tickets = get_variable("lottery_tickets");
$lottery_funds = get_variable("lottery_funds");

$total_users = get_variable("total_users");
$active_users = get_variable("active_users");

echo "total_users:$total_users";
echo " active_users:$active_users";
echo " wallet_balance:$wallet_balance";
echo " users_balance:$users_balance";
echo " free_rolls:$free_rolls";
echo " lottery_tickets:$lottery_tickets";
echo " lottery_funds:$lottery_funds";
echo " bet_rolls:$bet_rolls";
echo " pay_rolls:$pay_rolls";
