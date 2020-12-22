<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/lottery.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

db_connect();

log_write("Task: Lottery round start");

lottery_close_round();

log_write("Task: Lottery round finish");
