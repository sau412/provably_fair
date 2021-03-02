<?php

// Sync incoming transactions
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

    // Check for new receiving transactions
    $transactions_array = grc_web_get_all_tx();
    foreach($transactions_array as $tranaction_row) {
        $tx_uid = $tranaction_row->uid;
        $tx_id = $tranaction_row->tx_id;
        $amount = $tranaction_row->amount;
        $address = $tranaction_row->address;
        $status = $tranaction_row->status;

        // Check if transaction exists in table
        $tx_uid = ex_get_tx_uid_by_currency_uid_and_tx_id($currency_uid, $tx_id);
        if($tx_uid) {
            // May be update status/amount
        }
        // Else if transaction not exists in table yet
        else {
            if($status == 'received') {
                // Add received transaction
                $user_uid = ex_get_user_uid_by_currency_uid_and_address($currency_uid, $address);
                if($user_uid) {
                    ex_add_incoming_transaction($user_uid, $wallet_uid, $status, $address, $amount, $tx_id);
                    ex_recalculate_balance($user_uid, $currency_uid);
                }
            }
        }
    }
}
