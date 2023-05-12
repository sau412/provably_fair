<?php

function log_write($message, $severity = 7) {
        global $logger_url;
        global $project_log_name;

        openlog('php', LOG_ODELAY, LOG_USER);
	syslog($severity, $message);
	closelog();
}
