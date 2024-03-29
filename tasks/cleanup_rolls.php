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
    SELECT `user_uid`, YEAR(`timestamp`) as y, MONTH(`timestamp`) as m, count(*) as c
    FROM `rolls`
    WHERE `timestamp` < DATE_SUB(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH), INTERVAL DAYOFMONTH(CURRENT_DATE) - 1 DAY)
    GROUP BY `user_uid`, YEAR(`timestamp`), MONTH(`timestamp`)
");

foreach($user_uids_array as $row) {
    $user_uid = $row['user_uid'];
    $year = $row['y'];
    $month = $row['m'];
    $count = $row['c'];

    // Skip single records
    if($count <= 1) continue;

    $user_data_array = db_query_to_array("
        SELECT SUM(`bet`) as total_bet, SUM(`profit`) as total_profit
        FROM `rolls`
        WHERE `user_uid` = '$user_uid'
            AND YEAR(`timestamp`) = '$year'
            AND MONTH(`timestamp`) = '$month'
    ");
    $user_data_row = array_pop($user_data_array);
    $total_bet = $user_data_row['total_bet'];
    $total_profit = $user_data_row['total_profit'];

    $total_result = $total_profit - $total_bet;
    $timestamp = "$year-$month-01";
    
    echo "Cleanup for user uid $user_uid year $year month $month\n";
    db_query("START TRANSACTION");
    db_query("
        DELETE FROM `rolls`
        WHERE `user_uid` = '$user_uid'
            AND YEAR(`timestamp`) = '$year'
            AND MONTH(`timestamp`) = '$month'
    ");
    db_query("
        INSERT INTO `rolls` (`user_uid`, `roll_type`, `server_seed`,
            `user_seed`, `roll_result`, `bet`, `profit`, `timestamp`)
        VALUES ('$user_uid', 'total', '',
            '', '0', '$total_bet', '$total_profit', '$timestamp')
    ");

    db_query("COMMIT");
}

