<?php
// Email sending functions

// Send email via broker
function email_add($to, $subject, $body) {
	global $email_sender;
	global $email_reply_to;
	
	mail($to, $subject, $body);

	$message = [
		"to" => $to,
		"subject" => $subject,
		"body" => $body,
	];
	
	log_write($message, 6);
}
