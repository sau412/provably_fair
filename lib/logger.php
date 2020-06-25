<?php

function log_write($message, $severity) {
        global $logger_url;
        global $project_log_name;

		if(is_array($message)) $message = json_encode($message);

        $ch = curl_init($logger_url);
        $body = json_encode([
        	"source" => $project_log_name,
        	"severity" => $severity,
        	"message" => $message]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $result_json = curl_exec($ch);
        curl_close($ch);        
        $result = json_decode($result_json, true);
        return $result;
}
