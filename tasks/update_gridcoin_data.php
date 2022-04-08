<?php
// Only for command line
if(!isset($argc)) die();

$f=fopen("/tmp/freegridcoin_lockfile","w");
if($f) {
        echo "Checking locks\n";
        if(!flock($f,LOCK_EX|LOCK_NB)) {
                die("Lockfile locked\n");
        }
        echo "Lock obtained\n";
}

// Gridcoinresearch send rewards
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/gridcoin_web_wallet.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

// Check if unsent rewards exists
db_connect();

// Get addresses for new users
echo "Generating addresses for new users...\n";
$new_array=db_query_to_array("SELECT `uid` FROM `users` WHERE `wallet_uid` IS NULL OR `wallet_uid`=0");
foreach($new_array as $user_info) {
        $uid=$user_info['uid'];
	//echo "1 grc_web_get_new_receiving_address()\n";
        $result=grc_web_get_new_receiving_address();
        $wallet_uid=$result->uid;
        $uid_escaped=db_escape($uid);
        $wallet_uid_escaped=db_escape($wallet_uid);
        echo "Requested new address\n";
        db_query("UPDATE `users` SET `wallet_uid`='$wallet_uid_escaped' WHERE `uid`='$uid_escaped'");
}

// Update addresses data for all users
echo "Updating addresses for all users...\n";
$pending_array=db_query_to_array("SELECT `uid`,`wallet_uid`,`deposit_address`
									FROM `users`
									WHERE `wallet_uid` IS NOT NULL AND `deposit_address` IS NULL");
foreach($pending_array as $user_info) {
        $uid = $user_info['uid'];
        $address_uid = $user_info['wallet_uid'];
        $deposit_address = $user_info['deposit_address'];
        $result = grc_web_get_receiving_address($address_uid);
        $address = $result->address;
        $received = $result->received;

        if($address != '' && $deposit_address == '') {
        	echo "User uid $uid address $address\n";
                $uid_escaped = db_escape($uid);
                $address_escaped = db_escape($address);
                $received_escaped = db_escape($received);
                db_query("UPDATE `users`
                                SET `deposit_address`='$address_escaped'
                                WHERE `uid`='$uid_escaped'");
        }
        //update_user_balance($uid);
}

// Get balance
$current_balance=grc_web_get_balance();
set_variable("wallet_balance",$current_balance);
echo "Current balance: $current_balance\n";

// Get payout information for GRC
$payout_data_array=db_query_to_array("SELECT `uid`,`user_uid`,`address`,`amount`,`wallet_uid` FROM `transactions`
                                        WHERE `status` IN ('requested','processing') AND `address` IS NOT NULL");

// Sending unsent transactions
echo "Sending transactions...\n";
foreach($payout_data_array as $payout_data) {
        $uid=$payout_data['uid'];
        $user_uid=$payout_data['user_uid'];
        $address=$payout_data['address'];
        $amount=$payout_data['amount'];
        $wallet_uid=$payout_data['wallet_uid'];

        $uid_escaped=db_escape($uid);

        // If we have funds for this
        if($wallet_uid) {
                $tx_data=grc_web_get_tx_status($wallet_uid);
                if($tx_data) {
                        switch($tx_data->status) {
                                case 'address error':
                                        echo "Address error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC\n";
                                        //log_write("Address error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC");
                                        db_query("UPDATE `transactions` SET `tx_id`='address error',`status`='error' WHERE `uid`='$uid_escaped'");
                                        break;
                                case 'sending error':
                                        echo "Sending error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC\n";
                                        //log_write("Sending error wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC");
                                        db_query("UPDATE `transactions` SET `tx_id`='sending error',`status`='error' WHERE `uid`='$uid_escaped'");
                                        break;
                                case 'received':
                                case 'pending':
                                case 'sent':
                                        $tx_id=$tx_data->tx_id;
                                        $tx_id_escaped=db_escape($tx_id);
                                        //log_write("Sent wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC");
                                        echo "Sent wallet uid '$wallet_uid' for address '$address' amount '$amount' GRC\n";
                                        db_query("UPDATE `transactions` SET `tx_id`='$tx_id_escaped',`status`='sent' WHERE `uid`='$uid_escaped'");
                                        break;
                        }
                }
        } else if($amount<$current_balance) {
                echo "Sending $amount to $address\n";

                // Send coins, get txid
                $wallet_uid=grc_web_send($address,$amount);
                $wallet_uid_escaped=db_escape($wallet_uid);
                if($wallet_uid && is_numeric($wallet_uid)) {
                        db_query("UPDATE `transactions` SET `status`='processing',`wallet_uid`='$wallet_uid_escaped' WHERE `uid`='$uid_escaped'");
                } else {
                        log_write("Sending error, no wallet uid for address '$address' amount '$amount' GRC");
                }
                echo "----\n";
        } else {
                // No funds
                echo "Insufficient funds for sending rewards\n";
                log_write("Insufficient funds for sending rewards");
                break;
        }
}

// Sync transactions
echo "Syncing incoming transactions...\n";

$max_wallet_uid = db_query_to_variable("SELECT MAX(`wallet_uid`) FROM `transactions` WHERE `status` IN ('received')");

$transactions_data=grc_web_get_all_tx($max_wallet_uid);

foreach($transactions_data as $tx_row) {
        $uid = $tx_row->uid;
        $amount = $tx_row->amount;
        $address = $tx_row->address;
        $status = $tx_row->status;
        $tx_id = $tx_row->tx_id;
        $confirmations = $tx_row->confirmations;
        $timestamp = $tx_row->timestamp;

        $tx_id_escaped = db_escape($tx_id);
        $uid_escaped = db_escape($uid);
        
        // Need to check only incoming transaction
        if($status!='pending' && $status!='received') {
                echo "Skipping non-incoming transaction $tx_id\n";
                continue;
        }

        $address_escaped = db_escape($address);
        $user_uid = db_query_to_variable("SELECT `uid` FROM `users` WHERE `deposit_address`='$address_escaped'");

        $exists_tx_uid=db_query_to_variable("SELECT `uid` FROM `transactions`
        					WHERE `wallet_uid`='$uid_escaped' AND `status` IN ('pending','received')");
        if($exists_tx_uid) {
                // Exists transaction - update data
                // Update if status changed?
                echo "Update data for existing transaction $exists_tx_uid\n";
                $status_escaped=db_escape($status);
                if($status == 'received') {
                        $exists_status = db_query_to_variable("SELECT `status` FROM `transactions` WHERE `uid` = '$exists_tx_uid'");
                        if($exists_status == 'pending') {
                                change_user_balance($user_uid, $amount);
                        }
                }
                $confirmations_escaped=db_escape($confirmations);
                db_query("UPDATE `transactions` SET `status`='$status_escaped',
                                        `confirmations`='$confirmations_escaped'
                                WHERE `uid`='$exists_tx_uid'");

                $user_uid=db_query_to_variable("SELECT `user_uid` FROM `transactions`
                                                WHERE `wallet_uid`='$uid_escaped' AND `status` IN ('pending','received')");
                //update_user_balance($user_uid);
        } else {
                // Not exists - new transaction
                // Finding user with that address
                echo "New transaction found\n";
                $amount_escaped=db_escape($amount);
                $address_escaped=db_escape($address);
                $status_escaped=db_escape($status);
                $confirmations_escaped=db_escape($confirmations);
                $timestamp_escaped=db_escape($timestamp);
                $user_uid_escaped=db_escape($user_uid);

                if($user_uid=='') {
                	echo "User not found for transaction $tx_id\n";
                	continue;
                }

                if($status == 'received') {
                        change_user_balance($user_uid, $amount);
                }
                //update_user_balance($user_uid);
                // Check if sending transaction already exists
                if($status == 'sent' || $status === 'processing') {
                        $tx_exists = db_query_to_variable("SELECT 1 FROM `transactions`
                                WHERE `wallet_uid`='$uid_escaped' AND `status` IN ('requested','processing','sent')");
                        if($tx_exists) continue;
                }
		echo "Adding new transaction\n";
                db_query("INSERT INTO `transactions` (`user_uid`,`amount`,`address`,`status`,`wallet_uid`,`tx_id`,`confirmations`,`timestamp`)
VALUES ('$user_uid_escaped','$amount_escaped','$address_escaped','$status_escaped','$uid_escaped','$tx_id_escaped','$confirmations_escaped','$timestamp_escaped')");
        }
}

$current_balance=grc_web_get_balance();
set_variable("wallet_balance",$current_balance);
