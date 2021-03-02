<?php

// Send outgoing transactions
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");
require_once("../lib/gridcoin_web_wallet.php");
require_once("../lib/ex_lib.php");

db_connect();

$currency_data = db_query_to_array("SELECT `uid`, `wallet_api`, `wallet_key`
                                    FROM `ex_currencies`
                                    WHERE `wallet_api` != ''");

foreach($currency_data as $currency_row) {
    $currency_uid = $currency_row['uid'];

    $grc_api_url = $currency_row['wallet_api'];
    $grc_api_key = $currency_row['wallet_key'];

    $currency_uid_escaped = db_escape($currency_uid);

    // Get all unsent transactions
    $transactions_array = db_query_to_array("SELECT `uid`, `user_uid`, `wallet_uid`, `amount`, `address`, `status`
                                                FROM `ex_transactions`
                                                WHERE `tx_id` IS NULL");
    foreach($transactions_array as $tranaction_row) {
        $tx_uid = $tranaction_row['uid'];
        $user_uid = $tranaction_row['user_uid'];
        $wallet_uid = $tranaction_row['wallet_uid'];
        $amount = $tranaction_row['amount'];
        $address = $tranaction_row['address'];
        $status = $tranaction_row['status'];

        if($wallet_uid) {
            $transaction_data = grc_web_get_tx_status($wallet_uid);
            $status = $transaction_data->status;
            $tx_id = $transaction_data->tx_id;
            if($tx_id) {
                ex_update_transaction_tx_id($tx_uid, $tx_id);
                ex_update_transaction_status($tx_uid, $status);
            }
            else {
                ex_update_transaction_status($tx_uid, $status);
            }
            ex_recalculate_balance($user_uid, $currency_uid);
        }
        else {
            $wallet_uid = grc_web_send($address, $amount);
            if($wallet_uid) {
                $status = "processing";
                ex_update_transaction_wallet_uid($tx_uid, $wallet_uid);
                ex_update_transaction_status($tx_uid, $status);
                ex_recalculate_balance($user_uid, $currency_uid);
            }
        }
    }
}
