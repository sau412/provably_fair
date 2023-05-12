<?php

function log_write($messageRaw, $severity = 7) {
        global $logger_url;
        global $project_log_name;

        openlog('php', LOG_ODELAY, LOG_USER);
        if(gettype($message) !== "string") {
                $message = json_encode($messageRaw);
        }
        else {
                $message = $messageRaw;
        }
	syslog($severity, $project_log_name . " " . $message);
	closelog();
}
