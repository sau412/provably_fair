<?php
require_once("settings.php");
require_once("language.php");
require_once("db.php");
require_once("core.php");
require_once("html.php");
require_once("captcha.php");
require_once("minesweeper.php");

db_connect();

$wallet_balance=db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='wallet_balance'");
$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");
$free_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type`='free'");
echo "wallet_balance:$wallet_balance users_balance:$users_balance free_rolls:$free_rolls\n";
?>
