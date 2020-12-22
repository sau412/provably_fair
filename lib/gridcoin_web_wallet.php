<?php
// Gridcoin-client related functions
//require_once("settings.php");
//var_dump(grc_rpc_get_balance());

// Send query to gridcoin client
function grc_web_send_query($query) {
        global $grc_api_url;
        global $grc_api_key;

        $ch=curl_init($grc_api_url);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
//curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
//curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
//var_dump("grc_web_send_query",$query);
        curl_setopt($ch, CURLOPT_POSTFIELDS,"api_key=$grc_api_key&".$query);
        $result=curl_exec($ch);
//var_dump($result);
	$error=curl_error($ch);
	if($error) var_dump("curl error",$error);
        curl_close($ch);

        return $result;
}

// Get balance
function grc_web_get_balance() {
        $query="method=get_balance";
        $result=grc_web_send_query($query);
        $data=json_decode($result);
//var_dump($result,$data);
        if(property_exists($data,"error")) return $data->error;
        return $data->balance;
}

// Send coins
function grc_web_send($grc_address,$amount) {
        $query="method=send&address=$grc_address&amount=$amount";
//var_dump($query);
        $result=grc_web_send_query($query);
//var_dump($result);
        $data=json_decode($result);
//var_dump($data);
        if(property_exists($data,"error")) return $data->error;
        return $data->uid;
}

// Get sending status
function grc_web_get_tx_status($tx_uid) {
        $query="method=get_transaction_by_uid&transaction_uid=$tx_uid";
//var_dump($query);
        $result=grc_web_send_query($query);
//var_dump($result);
        $data=json_decode($result);
        if(property_exists($data,"error")) return $data->error;

        return $data;
}

// Get all transactions
function grc_web_get_all_tx() {
        $query="method=get_all_transactions";
        $result=grc_web_send_query($query);
//var_dump($result);
        $data=json_decode($result);
//var_dump($data);
        if(is_array($data)) {
                if(isset($data['error'])) return $data['error'];
        } else {
                if(property_exists($data,"error")) return $data->error;
        }

        return $data;
}

// Get new address
function grc_web_get_new_receiving_address() {
        $query="method=new_receiving_address";
        $result=grc_web_send_query($query);
        $data=json_decode($result);
//var_dump($result,$data);
        if(property_exists($data,"error")) return $data->error;
        return $data;
}

// Get receiving address
function grc_web_get_receiving_address($address_uid) {
        $query="method=get_receiving_address_by_uid&address_uid=$address_uid";
        $result=grc_web_send_query($query);
        $data=json_decode($result);
//var_dump($result,$data);
        if(property_exists($data,"error")) return $data->error;
        return $data;
}
