<?php

// Update currency rates
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");
require_once("../lib/gridcoin_web_wallet.php");

db_connect();

$wallets_data = db_query_to_array("SELECT w.`uid`, w.`currency_uid`, c.`symbol`,
                                        w.`wallet_uid`, c.`wallet_api`, c.`wallet_key`,
                                        w.`deposit_address`
                                    FROM `ex_wallets` AS w
                                    JOIN `ex_currencies` AS c ON w.`currency_uid` = c.`uid`");

foreach($wallets_data as $wallet_row) {
    $uid = $wallet_row['uid'];
    $wallet_uid = $wallet_row['wallet_uid'];
    $deposit_address = $wallet_row['deposit_address'];

    $grc_api_url = $wallet_row['wallet_api'];
    $grc_api_key = $wallet_row['wallet_key'];

    $uid_escaped = db_escape($uid);

    if($wallet_uid) {
        if(!$deposit_address) {
            $deposit_address_data = grc_web_get_receiving_address($wallet_uid);
            $deposit_address = $deposit_address_data->address;
            $deposit_address_escaped = db_escape($deposit_address);
            db_query("UPDATE `ex_wallets`
                        SET `deposit_address` = '$deposit_address_escaped'
                        WHERE `uid` = '$uid_escaped'");
        }
    }
    else {
        $wallet_data = grc_web_get_new_receiving_address();
        $wallet_uid = $wallet_data->uid;
        if($wallet_uid) {
            $wallet_uid_escaped = db_escape($wallet_uid);
            db_query("UPDATE `ex_wallets`
                        SET `wallet_uid` = '$wallet_uid_escaped'
                        WHERE `uid` = '$uid_escaped'");
        }
    }
}