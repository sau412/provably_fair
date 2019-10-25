<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/lottery.php");

db_connect();

lottery_close_round();

?>
