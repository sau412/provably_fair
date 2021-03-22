<?php

// Update currency rates
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

db_connect();

function get_coingecko_rate($currency) {
    if($currency == 'bitcoin') {
        return 1;
    }

    // Setup cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, FALSE);

    // Get XMR price
    curl_setopt($ch, CURLOPT_URL,"https://api.coingecko.com/api/v3/coins/$currency");
    $result = curl_exec($ch);
    if($result == "") {
            echo "No $currency price data\n";
            log_write("No $currency price data", 4);
            die();
    }
    $parsed_data = json_decode($result, true);

    $symbol = $parsed_data['symbol'];

    foreach($parsed_data['tickers'] as $ticker) {
        $base = $ticker['base'];
        $target = $ticker['target'];
        $exchange_name = $ticker['market']['identifier'];

        if($exchange_name == 'south_xchange' && strtolower($base) == strtolower($symbol) && $target == 'BTC') {
            $btc_per_coin_price = $ticker['last'];
        }
    }
    if(!isset($btc_per_coin_price)) {
        $btc_per_coin_price = $parsed_data['market_data']['current_price']['btc'];
    }
    return $btc_per_coin_price;
}

$currency_data = db_query_to_array("SELECT `uid`, `coingecko_name` FROM `ex_currencies`");

foreach($currency_data as $currency_row) {
    $uid = $currency_row['uid'];
    $coingecko_name = $currency_row['coingecko_name'];
    $rate = get_coingecko_rate($coingecko_name);
    if(!$rate) {
        log_write("Incorrect $coingecko_name price data", 4);
        continue;
    }

    echo "Currency $coingecko_name rate $rate\n";

    $uid_escaped = db_escape($uid);
    $rate_escaped = db_escape($rate);
    db_query("UPDATE `ex_currencies` SET `rate` = '$rate_escaped' WHERE `uid` = '$uid_escaped'");
}