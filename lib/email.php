<?php
// Email sending functions

// Send email via broker
function email_add($to, $subject, $body) {
	global $email_sender;
	global $email_reply_to;
	global $broker_project_name;
	
	$message = [
		"source" => $broker_project_name,
		"to" => $to,
		"from" => $email_sender,
		"reply" => $email_reply_to,
		"subject" => $subject,
		"body" => $body,
	];
	
	log_write($message, 6);
	
	broker_add("smtp2go", $message);
}
