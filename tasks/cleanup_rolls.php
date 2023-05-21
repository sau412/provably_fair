<?php
// Only for command line
if(!isset($argc)) die();

// Gridcoinresearch send rewards
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/logger.php");
db_connect();

$user_uids_array = db_query_to_array("
    SELECT `user_uid`, SUM(`profit`) as total_profit
    FROM `minesweeper`
    GROUP BY `user_uid`
");

foreach($user_uids_array as $row) {
    $user_uid = $row['user_uid'];
    $total_profit = $row['total_profit'];

    db_query("BEGIN TRANSACTION");
    db_query("
        INSERT INTO `rolls` (`user_uid`, `roll_type`, `server_seed`,
            `user_seed`, `roll_result`, `bet`, `profit`)
        VALUES ('$user_uid', 'total', '',
            '', '0', '0', '$total_profit')
    ");

    db_query("DELETE FROM `minesweeper` WHERE `user_uid` = '$user_uid'");
    db_query("COMMIT");
}

