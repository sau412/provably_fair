<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");

db_connect();

$wallet_balance=db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='wallet_balance'");
$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");
$free_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type`='free'");
$bet_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type` IN ('high','low')");
$pay_rolls=db_query_to_variable("SELECT count(*) FROM `rolls` WHERE `roll_type` IN ('pay')");

echo "wallet_balance:$wallet_balance users_balance:$users_balance free_rolls:$free_rolls bet_rolls:$bet_rolls pay_rolls:$pay_roll\n";
?>
