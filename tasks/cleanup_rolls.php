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
SELECT `user_uid`, SUM(`bet`) as total_bet, SUM(`profit`) as total_profit,
        YEAR(`timestamp`) as y, MONTH(`timestamp`) as m
    FROM `rolls`
    WHERE `user_uid` = 1
        `timestamp` < DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)
    GROUP BY `user_uid`, YEAR(`timestamp`), MONTH(`timestamp`)
");

foreach($user_uids_array as $row) {
    $user_uid = $row['user_uid'];
    $total_bet = $row['total_bet'];
    $total_profit = $row['total_profit'];
    $total_result = $total_profit - $total_bet;
    $year = $row['y'];
    $month = $row['m'];
    
    echo "Cleanup for user uid $user_uid year $year month $month\n";
    db_query("START TRANSACTION");
    db_query("
        INSERT INTO `rolls` (`user_uid`, `roll_type`, `server_seed`,
            `user_seed`, `roll_result`, `bet`, `profit`)
        VALUES ('$user_uid', 'total', '',
            '', '0', '0', '$total_result')
    ");

    db_query("
        DELETE FROM `rolls`
        WHERE `user_uid` = '$user_uid'
            AND YEAR(`timestamp`) = '$year'
            AND MONTH(`timestamp`) = '$month'
    ");
    db_query("COMMIT");
}

