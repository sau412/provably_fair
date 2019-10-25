<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/lottery.php");

db_connect();

write_log("Task: Lottery round start");

lottery_close_round();

write_log("Task: Lottery round finish");
?>
