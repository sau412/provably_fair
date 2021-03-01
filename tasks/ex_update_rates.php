<?php

// Update currency rates
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

db_connect();

function get_coingecko_rate($currency) {
    // Setup cURL
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
    curl_setopt($ch,CURLOPT_POST,FALSE);

    // Get XMR price
    curl_setopt($ch,CURLOPT_URL,"https://api.coingecko.com/api/v3/coins/$currency");
    $result=curl_exec($ch);
    if($result=="") {
            echo "No $currency price data\n";
            log_write("No $currency price data", 4);
            die();
    }
    $parsed_data=json_decode($result);
    $btc_per_coin_price=(string)$parsed_data->market_data->current_price->btc;
    return $btc_per_coin_price;
}

$currency_data = db_query_to_array("SELECT `uid`, `coingecko_name` FROM `ex_currencies`");

foreach($currency_data as $currency_row) {
    $uid = $currency_row['uid'];
    $coingecko_name = $currency_row['coingecko_name'];
    $rate = get_coingecko_rate($coingecko_name);
    if(!$rate) {
        log_write("Incorrect $currency price data", 4);
        continue;
    }
    $uid_escaped = db_escape($uid);
    $rate_escaped = db_escape($rate);
    db_query("UPDATE `ex_currencies` SET `rate` = '$rate_escaped' WHERE `uid` = '$uid_escaped'");
}