<?php

/**
 * Exchange library
 */

function ex_get_user_uid_by_currency_uid_and_address($currency_uid, $address) {
    $currency_uid_escaped = db_escape($currency_uid);
    $address_escaped = db_escape($address);

    $user_uid = db_query_to_variable("SELECT `user_uid` FROM `ex_wallets`
                                        WHERE `currency_uid` = '$currency_uid_escaped' AND
                                            `deposit_address` = '$address_escaped'");
    return $user_uid;
}

function ex_get_incoming_tx_uid_by_currency_uid_and_tx_id($currency_uid, $tx_id) {
    $currency_uid_escaped = db_escape($currency_uid);
    $tx_id_escaped = db_escape($tx_id);

    $tx_uid = db_query_to_variable("SELECT `uid` FROM `ex_transactions`
                                        WHERE `currency_uid` = '$currency_uid_escaped' AND
                                            `tx_id` = '$tx_id_escaped' AND `status` IN ('received')");
    return $tx_uid;
}

function ex_add_incoming_transaction($user_uid, $currency_uid, $wallet_uid, $status, $address, $amount, $tx_id) {
    $user_uid_escaped = db_escape($user_uid);
    $currency_uid_escaped = db_escape($currency_uid);
    $wallet_uid_escaped = db_escape($wallet_uid);
    $status_escaped = db_escape($status);
    $address_escaped = db_escape($address);
    $amount_escaped = db_escape($amount);
    $tx_id_escaped = db_escape($tx_id);

    db_query("INSERT INTO `ex_transactions` (
                    `user_uid`,
                    `currency_uid`,
                    `wallet_uid`,
                    `status`,
                    `address`,
                    `amount`,
                    `tx_id`)
                VALUES (
                    '$user_uid_escaped',
                    '$currency_uid_escaped',
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

    // For Gridcoin
    if($currency_uid == 4) {
        update_user_balance($user_uid);
    }
    // For others
    else {
        // Request address if not exists
        // Because if address not exists, balance is lost
        ex_user_request_address($user_uid, $currency_uid);

        // Calculate sums
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
        
        $sent_fee_sum = db_query_to_variable("SELECT SUM(`fee`)
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

        $balance = db_query_to_variable("SELECT '$received_sum' + '$exchange_to' - '$exchange_from' - '$sent_fee_sum' - '$sent_sum'");

        db_query("UPDATE `ex_wallets`
                    SET `balance` = '$received_sum' + '$exchange_to' - '$exchange_from' - '$sent_fee_sum' - '$sent_sum'
                    WHERE `user_uid` = '$user_uid_escaped' AND `currency_uid` = '$currency_uid_escaped'");
    }
}

function ex_get_currencies_data() {
    return db_query_to_array("SELECT `uid`, `name`, `symbol`, `rate`, `exchange_limit`, `withdraw_fee`, `min_send_amount` FROM `ex_currencies`");
}

function ex_get_currency_withdraw_fee($currency_uid) {
    $currency_uid_escaped = db_escape($currency_uid);
    return db_query_to_variable("SELECT `withdraw_fee`
                                FROM `ex_currencies` WHERE `uid` = '$currency_uid_escaped'");
}

function ex_get_currency_min_send_amount($currency_uid) {
    $currency_uid_escaped = db_escape($currency_uid);
    return db_query_to_variable("SELECT `min_send_amount`
                                FROM `ex_currencies` WHERE `uid` = '$currency_uid_escaped'");
}

function ex_get_currency_exchange_limit($currency_uid) {
    $currency_uid_escaped = db_escape($currency_uid);
    return db_query_to_variable("SELECT `exchange_limit`
                                FROM `ex_currencies` WHERE `uid` = '$currency_uid_escaped'");
}

function ex_get_exchange_params($currency_uid_from, $currency_uid_to, $amount_from) {
    global $exchange_fee;

    $limit_from = ex_get_currency_exchange_limit($currency_uid_from);
    $limit_to = ex_get_currency_exchange_limit($currency_uid_to);
    $rate = $limit_to / ($limit_from + $amount_from);
    $amount_to = $amount_from * $rate;
    $rate = sprintf("%0.12d", $rate);
    return [
        "rate" => $rate,
        "amount_from" => $amount_from,
        "amount_to" => $amount_to,
        "limit_from" => $limit_from,
        "limit_to" => $limit_to,
        "exchange_fee" => $exchange_fee,
        "exchange_fee_amount" => $exchange_fee,
    ];
}

function ex_get_wallet_data_by_user_uid_currency_uid($user_uid, $currency_uid) {
    $user_uid_escaped = db_escape($user_uid);
    $currency_uid_escaped = db_escape($currency_uid);

    $wallets_data = db_query_to_array("SELECT `uid`, `currency_uid`, `deposit_address`, `balance`
                                FROM `ex_wallets`
                                WHERE `user_uid` = '$user_uid_escaped' AND
                                    `currency_uid` = '$currency_uid_escaped'");
    return array_pop($wallets_data);
}

function ex_user_request_address($user_uid, $currency_uid) {
    $user_uid_escaped = db_escape($user_uid);
    $currency_uid_escaped = db_escape($currency_uid);

    $currency_exists = db_query_to_variable("SELECT 1 FROM `ex_currencies`
                                                WHERE `uid` = '$currency_uid_escaped'");
    if(!$currency_exists) {
        return false;
    }

    $address_exists = db_query_to_variable("SELECT 1 FROM `ex_wallets`
                                                WHERE `user_uid` = '$user_uid_escaped' AND
                                                    `currency_uid` = '$currency_uid_escaped'");

    if($address_exists) {
        return false;
    }

    db_query("INSERT INTO `ex_wallets` (`user_uid`, `currency_uid`)
                VALUES ('$user_uid_escaped', '$currency_uid_escaped')");
    
    return true;
}

function ex_user_withdraw($user_uid, $currency_uid, $amount, $address) {
    $user_uid_escaped = db_escape($user_uid);
    $currency_uid_escaped = db_escape($currency_uid);
    $amount_escaped = db_escape($amount);
    $address_escaped = db_escape($address);

    $withdraw_fee = ex_get_currency_withdraw_fee($currency_uid);
    $min_send_amount = ex_get_currency_min_send_amount($currency_uid);
    
    $amount_to_send = $amount - $withdraw_fee;

    if($amount_to_send <= 0 || $amount_to_send < $min_send_amount) {
        return false;
    }

    // Transaction
    db_query("START TRANSACTION");
    $withdraw_fee_escaped = db_escape($withdraw_fee);

    if($currency_uid == 4) {
        $user_balance = get_user_balance($user_uid);
    }
    else {
        $user_data = ex_get_wallet_data_by_user_uid_currency_uid($user_uid, $currency_uid);
        $user_balance = $user_data['balance'];
    }

    if($user_balance >= $amount_to_send) {
        $amount_to_send_escaped = db_escape($amount_to_send);
        db_query("INSERT INTO `ex_transactions` (`user_uid`, `currency_uid`, `amount`, `fee`, `address`, `status`)
                    VALUES ('$user_uid_escaped', '$currency_uid_escaped', '$amount_to_send_escaped',
                        '$withdraw_fee_escaped', '$address_escaped', 'pending')");
        ex_recalculate_balance($user_uid, $currency_uid);
        db_query("COMMIT");
        return true;
    }
    db_query("COMMIT");
    return false;
}

function ex_exchange($user_uid, $from_currency_uid, $from_amount, $to_currency_uid, $to_amount, $exchange_fee_amount) {
    global $exchange_fee;
    
    if($from_currency_uid == $to_currency_uid) {
        throw new Exception("Cannot convert currency to itself");
    }
    if($from_amount <= 0) {
        throw new Exception("Negative amount");
    }

    $user_uid_escaped = db_escape($user_uid);
    $from_currency_uid_escaped = db_escape($from_currency_uid);
    $to_currency_uid_escaped = db_escape($to_currency_uid);
    $from_amount_escaped = db_escape($from_amount);

    // Transaction
    db_query("START TRANSACTION");
    
    if($from_currency_uid == 4) {
        $user_balance = get_user_balance($user_uid);
    }
    else {
        $user_data = ex_get_wallet_data_by_user_uid_currency_uid($user_uid, $from_currency_uid);
        $user_balance = $user_data['balance'];
    }
    
    if($user_balance >= $from_amount) {
        $from_limit = ex_get_currency_exchange_limit($from_currency_uid);
        $to_limit = ex_get_currency_exchange_limit($to_currency_uid);
        $rate = $to_limit / ($from_limit + $from_amount);
        $to_amount_calculated = $from_amount * $rate * (1 - $exchange_fee);
        $exchange_fee_calculated = $from_amount * $rate * $exchange_fee;

        if($to_amount_calculated < $to_amount || $exchange_fee_amount < $exchange_fee_calculated) {
            db_query("ROLLBACK");
            log_write("Exchange error: to_amount_calculated = $to_amount_calculated < to_amount = $to_amount and exchange_fee_amount = $exchange_fee_amount < exchange_fee_calculated = $exchange_fee_calculated");
            throw new Exception("Unable to exchange: exchange rates changed, try again");
        }

        log_write("Exchange ok: to_amount_calculated = $to_amount_calculated < to_amount = $to_amount and exchange_fee_amount = $exchange_fee_amount < exchange_fee_calculated = $exchange_fee_calculated");

        $rate_escaped = db_escape($rate);
        $to_amount_escaped = db_escape($to_amount);

        // Add exchange record
        db_query("INSERT INTO `ex_exchanges` (`user_uid`, `from_currency_uid`, `from_amount`, `rate`, `to_currency_uid`, `to_amount`)
                    VALUES ('$user_uid_escaped', '$from_currency_uid_escaped', '$from_amount_escaped', '$rate_escaped',
                        '$to_currency_uid_escaped', '$to_amount_escaped')");
        
        // Modify currency exchange limits
        // Increase from
        $from_currency_uid_escaped = db_escape($from_currency_uid);
        db_query("UPDATE `ex_currencies`
            SET `exchange_limit` = `exchange_limit` + '$from_amount_escaped'
            WHERE `uid` = '$from_currency_uid_escaped'");
        // Decrease to
        $to_currency_uid_escaped = db_escape($to_currency_uid);
        db_query("UPDATE `ex_currencies`
            SET `exchange_limit` = `exchange_limit` - '$to_amount_escaped'
            WHERE `uid` = '$to_currency_uid_escaped'");

        ex_recalculate_balance($user_uid, $from_currency_uid);
        ex_recalculate_balance($user_uid, $to_currency_uid);
        db_query("COMMIT");
        return true;
    }

    db_query("COMMIT");
    return false;
}

function ex_get_user_transactions($user_uid) {
    $user_uid_escaped = db_escape($user_uid);

    return db_query_to_array("SELECT t.`uid`, t.`currency_uid`, t.`amount`, t.`address`, t.`status`, t.`tx_id`, t.`timestamp`, c.`name`
                                FROM `ex_transactions` AS t
                                JOIN `ex_currencies` AS c ON c.`uid` = t.`currency_uid`
                                WHERE t.`user_uid` = '$user_uid_escaped'
                                ORDER BY t.`timestamp` DESC LIMIT 100");
}

function ex_get_user_exchanges($user_uid) {
    $user_uid_escaped = db_escape($user_uid);

    return db_query_to_array("SELECT e.`uid`,
                                    e.`from_currency_uid`, fc.`name` AS 'from_name', e.`from_amount`, e.`rate`,
                                    e.`to_currency_uid`, tc.`name` AS 'to_name', e.`to_amount`,
                                    e.`timestamp`
                                FROM `ex_exchanges` AS e
                                JOIN `ex_currencies` AS fc ON fc.`uid` = e.`from_currency_uid`
                                JOIN `ex_currencies` AS tc ON tc.`uid` = e.`to_currency_uid`
                                WHERE e.`user_uid` = '$user_uid_escaped'
                                ORDER BY e.`timestamp` DESC LIMIT 100");
}
