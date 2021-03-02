<?php

/**
 * Exchange library
 */

function ex_get_user_uid_by_currency_uid_and_address($currency_uid, $address) {
    $currency_uid_escaped = db_escape($currency_uid);
    $address_escaped = db_escape($address);

    $user_uid = db_query_to_variable("SELECT `uid` FROM `ex_wallets`
                                        WHERE `currency_uid` = '$currency_uid_escaped' AND
                                            `deposit_address` = '$address_escaped'");
    return $user_uid;
}

function ex_get_tx_uid_by_currency_uid_and_tx_id($currency_uid, $tx_id) {
    $currency_uid_escaped = db_escape($currency_uid);
    $tx_id_escaped = db_escape($tx_id);

    $tx_uid = db_query_to_variable("SELECT `uid` FROM `ex_transactions`
                                        WHERE `currency_uid` = '$currency_uid_escaped' AND
                                            `tx_id` = '$tx_id_escaped'");
    return $tx_uid;
}

function ex_add_incoming_transaction($user_uid, $wallet_uid, $status, $address, $amount, $tx_id) {
    $user_uid_escaped = db_escape($user_uid);
    $wallet_uid_escaped = db_escape($wallet_uid);
    $status_escaped = db_escape($status);
    $address_escaped = db_escape($address);
    $amount_escaped = db_escape($amount);
    $tx_id_escaped = db_escape($tx_id);

    db_query("INSERT INTO `ex_transactions` (
                    `user_uid`,
                    `wallet_uid`,
                    `status`,
                    `address`,
                    `amount`,
                    `tx_id`)
                VALUES (
                    '$user_uid_escaped',
                    '$wallet_uid_escaped',
                    '$status_escaped',
                    '$address_escaped',
                    '$amount_escaped',
                    '$tx_id_escaped'
                )");
}

function ex_update_transaction_tx_id($tx_uid, $tx_id) {
    $tx_uid_escaped = db_escape($tx_uid);
    $tx_id_escaped = db_escape($tx_id);
    db_query("UPDATE `ex_transactions`
                SET `tx_id` = '$tx_id_escaped'
                WHERE `uid` = '$tx_uid_escaped'");
}

function ex_update_transaction_wallet_uid($tx_uid, $wallet_uid) {
    $tx_uid_escaped = db_escape($tx_uid);
    $wallet_uid_escaped = db_escape($wallet_uid);
    db_query("UPDATE `ex_transactions`
                SET `wallet_uid` = '$wallet_uid_escaped'
                WHERE `uid` = '$tx_uid_escaped'");
}

function ex_update_transaction_status($tx_uid, $status) {
    $tx_uid_escaped = db_escape($tx_uid);
    $status_escaped = db_escape($status);
    db_query("UPDATE `ex_transactions`
                SET `status` = '$status_escaped'
                WHERE `uid` = '$tx_uid_escaped'");
}

function ex_recalculate_balance($user_uid, $currency_uid) {
    $user_uid_escaped = db_escape($user_uid);
    $currency_uid_escaped = db_escape($currency_uid);

    $received_sum = db_query_to_variable("SELECT SUM(`amount`)
                                            FROM `ex_transactions`
                                            WHERE `user_uid` = '$user_uid_escaped' AND
                                            `currency_uid` = '$currency_uid_escaped' AND
                                            `status` IN ('received')");

    $sent_sum = db_query_to_variable("SELECT SUM(`amount`)
                                            FROM `ex_transactions`
                                            WHERE `user_uid` = '$user_uid_escaped' AND
                                            `currency_uid` = '$currency_uid_escaped' AND
                                            `status` IN ('pending', 'processing', 'sent')");
    
    $exchange_from = db_query_to_variable("SELECT SUM(`from_amount`)
                                            FROM `ex_exchanges`
                                            WHERE `user_uid` = '$user_uid_escaped' AND
                                            `from_currency_uid` = '$currency_uid_escaped'");

    $exchange_to = db_query_to_variable("SELECT SUM(`to_amount`)
                                            FROM `ex_exchanges`
                                            WHERE `user_uid` = '$user_uid_escaped' AND
                                            `to_currency_uid` = '$currency_uid_escaped'");

    $balance = db_query_to_variable("SELECT '$received_sum' + '$sent_sum' + '$exchange_from' + '$exchange_to'");

    db_query("UPDATE `ex_wallets`
                SET `balance` = '$received_sum' + '$sent_sum' + '$exchange_from' + '$exchange_to'
                WHERE `user_uid` = '$user_uid_escaped' AND `currency_uid` = '$currency_uid_escaped'");
}

function ex_withdraw_transaction($user_uid, $currency_uid, $amount, $address) {

}