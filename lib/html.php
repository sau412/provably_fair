<?php

// Standard page begin
function html_page_begin($title,$token) {
        global $wallet_name;
//      $lang_select_form=lang_select_form($token);

        return <<<_END
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="icon" href="favicon.png" type="image/png">
<script src='jquery-3.3.1.min.js'></script>
<link rel="stylesheet" type="text/css" href="style.css">
<script src='script.js'></script>
</head>
<body>
<center>
<h1>$wallet_name</h1>

_END;
}

// Page end, scripts and footer
function html_page_end() {
        global $project_counter_name;
        $result=<<<_END
<hr width=10%>
<p>%footer_about%</p>
<p><img src='https://arikado.xyz/counter/?site=$project_counter_name'></p>
</center>
<script>

var hash = window.location.hash.substr(1);

if(hash != null && hash != '') {
        show_block(hash);
} else {
        show_block('dashboard');
}
</script>
</body>
</html>

_END;
        return lang_parser($result);
}

function html_login_form($token) {
        global $recaptcha_public_key;
        $login_submit=lang_message("login_submit");
        $captcha=html_captcha();
        $result=<<<_END
<h2>Login</h2>
<form name=login method=post>
<input type=hidden name=action value='login'>
<input type=hidden name=token value='$token'>
<p>%login_login% <input type=text name=login></p>
<p>%login_password% <input type=password name=password></p>
$captcha
<p><input type=submit value='%login_submit%'></p>
</form>

_END;
        return lang_parser($result);
}

function html_logout_form($user_uid,$token) {
        global $currency_short;
        $username=get_username_by_uid($user_uid);
        $balance=get_user_balance($user_uid);
        $result=<<<_END
<p>%header_greeting% $username (<a href='?action=logout&token=$token'>logout</a>), your balance: <span id='balance'>$balance</span> $currency_short</p>

_END;
        return lang_parser($result);
}

function html_register_form($token) {
        global $recaptcha_public_key;
        $captcha=html_captcha();
        $result=<<<_END
<h2>Register</h2>
<form name=register method=post>
<input type=hidden name=action value='register'>
<input type=hidden name=token value='$token'>
<p>%register_login% <input type=text name=login></p>
<p>%register_mail% <input type=text name=mail></p>
<p>%register_password1% <input type=password name=password1></p>
<p>%register_password2% <input type=password name=password2></p>
<p>%register_withdraw% <input type=text name=withdraw_address></p>
$captcha
<p><input type=submit value='%register_submit%'></p>
</form>

_END;
        return lang_parser($result);
}

function html_tabs($user_uid) {
        global $currency_short;
        $result="";
        $result.="<div style='display: inline-block;'>\n";
        $result.="<ul class=horizontal_menu>\n";
        if($user_uid) {
                $result.=html_menu_element("info","Info");
                $result.=html_menu_element("request_free_roll","Free $currency_short");
                //$result.=html_menu_element("minesweeper","Minesweeper");
                $result.=html_menu_element("dice_roll","Multiply $currency_short");
                //$result.=html_menu_element("last_rolls","Last rolls");
                $result.=html_menu_element("lottery","Lottery");
                $result.=html_menu_element("earn","Earn $currency_short");
                $result.=html_menu_element("exchange","Exchange");
                $result.=html_menu_element("send_receive","Send and receive");
                $result.=html_menu_element("settings","%tab_settings%");
                if(is_admin($user_uid)) {
                        $result.=html_menu_element("control","%tab_control%");
                }
        } else {
                $result.=html_menu_element("info","%tab_info%");
                $result.=html_menu_element("login","%tab_login%");
                $result.=html_menu_element("register","%tab_register%");
        }
        $result.="</ul>\n";
        $result.="</div>\n";

        return lang_parser($result);
}

function html_menu_element($block,$text) {
        return "<li><a href='#$block' onClick=\"show_block('$block')\">$text</a>\n";
}

// User settings
function html_user_settings($user_uid,$token) {
        $result="";

        $user_uid_escaped=db_escape($user_uid);
        $user_settings_data=db_query_to_array("SELECT `mail`,`withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $user_settings=array_pop($user_settings_data);
        $mail=$user_settings['mail'];
        $withdraw_address=$user_settings['withdraw_address'];

        $result.=lang_parser("<h2>%settings_header%</h2>\n");
        $result.="<form name=user_settings method=post>\n";
        $result.="<input type=hidden name=action value='user_change_settings'>\n";
        $result.="<input type=hidden name=token value='$token'>\n";

        // Notifications
        $mail_html=html_escape($mail);
        $result.=lang_parser("<p>%settings_mail%")." <input type=text size=40 name=mail value='$mail_html'>";
        $result.="</p>";

        // Withdraw addresss
        //$result.="<h3>Withdraw address</h3>\n";
        $withdraw_address_html=html_escape($withdraw_address);
        $result.=lang_parser("<p>%settings_withdraw_address%")." <input type=text size=40 name=withdraw_address value='$withdraw_address_html'></p>";

        // Password options
        //$result.="<h3>Password</h3>\n";
        $result.=lang_parser("<p>%settings_password% <input type=password name=password></p>");
        $result.=lang_parser("<p>%settings_new_password1% <input type=password name=new_password1></p>");
        $result.=lang_parser("<p>%settings_new_password2% <input type=password name=new_password2></p>");

        // Submit button
        $result.=lang_parser("<p><input type=submit value='%settings_submit%'></p>\n");
        $result.="</form>\n";

        return $result;
}

// Admin settings
function html_admin_settings($user_uid,$token) {
        $result="";

        $result.=lang_parser("<h2>%wallet_settings_header%</h2>\n");
        $result.="<form name=admin_settings method=post>\n";
        $result.="<input type=hidden name=action value='admin_change_settings'>\n";
        $result.="<input type=hidden name=token value='$token'>\n";

        $login_enabled=get_variable("login_enabled");
        $login_enabled_selected=$login_enabled?"selected":"";

        $payouts_enabled=get_variable("payouts_enabled");
        $payouts_enabled_selected=$payouts_enabled?"selected":"";

        $result.=lang_parser("<p>%wallet_settings_login_state% <select name=login_enabled><option value='disabled'>%wallet_settings_disabled%</option><option value='enabled' $login_enabled_selected>%wallet_settings_enabled%</option></select>\n");
        $result.=lang_parser(", %wallet_settings_payouts_state% <select name=payouts_enabled><option value='disabled'>%wallet_settings_disabled%</option><option value='enabled' $payouts_enabled_selected>%wallet_settings_enabled%</option></select>\n");

        $info=get_variable("info");
        $info_html=html_escape($info);
        $result.=lang_parser("<p>%wallet_settings_info%</p>")."<p><textarea name=info rows=10 cols=50>$info_html</textarea></p>";

        $global_message=get_variable("global_message");
        $global_message_html=html_escape($global_message);
        $result.=lang_parser("<p>%wallet_settings_global_message% ")."<input type=text size=60 name=global_message value='$global_message_html'></p>\n";

        // Submit button
        $result.=lang_parser("<p><input type=submit value='%wallet_settings_submit%'></p>\n");
        $result.="</form>\n";

        return $result;
}

// Global message
function html_message_global() {
        $result="";

        $global_message=get_variable("global_message");
        if($global_message!='') {
                $result.="<div class='message_global'>$global_message</div>";
        }

        return $result;
}

// Log
function html_log_section_admin() {
        $result="";
        $result.=lang_parser("<h2>%log_header%</h2>\n");
        $data_array=db_query_to_array("SELECT u.`login`,l.`message`,l.`timestamp` FROM `log` AS l
JOIN `users` u ON u.`uid`=l.`user_uid`
ORDER BY `timestamp` DESC LIMIT 100");

        $result.="<table class='table_horizontal'>\n";
        $result.=lang_parser("<tr><th>%log_table_header_timestamp%</th><th>%log_table_header_login%</th><th>%log_table_header_message%</th></tr>\n");
        foreach($data_array as $row) {
                $login=$row['login'];
                $timestamp=$row['timestamp'];
                $message=$row['message'];
                $login_html=html_escape($login);
                $message_html=html_escape($message);
                $result.="<tr><td>$timestamp</td><td>$login_html</td><td>$message_html</td></tr>\n";
        }
        $result.="</table>\n";
        return $result;
}

function html_message($message) {
        return "<div style='background:yellow;'>".html_escape($message)."</div>";
}

function html_address_url($address) {
        global $address_url;
        $address_begin=substr($address,0,10);
        $address_end=substr($address,-10,10);
        $result="<div class='block_with_container'>$address_begin...$address_end <div class='block_with_container_inside'>$address<br><a href='$address_url$address'>Block explorer</a></div></div>";
        return $result;
}

function html_tx_url($tx) {
        global $tx_url;
        if($tx=='') return '';
        $tx_begin=substr($tx,0,10);
        $tx_end=substr($tx,-10,10);
        $result="<div class='block_with_container'>$tx_begin......$tx_end<div class='block_with_container_inside'>$tx<br><a href='$tx_url$tx'>Block explorer</a></div></div>";
        return $result;
}

// Loadable block for ajax
function html_loadable_block() {
        return lang_parser("<div id='main_block'>%loading_block%</div>\n");
}

// Show info
function html_info() {
        $result='';
        $result.=lang_parser("<h2>%info_header%</h2>\n");
        $result.=get_variable("info");
        return $result;
}

// Show captcha
function html_captcha() {
        $result=<<<_END
<p><img src='?captcha'><br>Code from image above: <input type=text name=captcha_code></p>
_END;
        return $result;
}

// Show recaptcha
function html_recaptcha() {
	global $recaptcha_public_key;
	return <<<_END
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<div class='g-recaptcha' data-sitekey='$recaptcha_public_key'></div>
_END;
}

// Request free roll
function html_request_free_roll($user_uid, $token) {
        $result = '';

        $result .= <<<_END
<p>
You can use faucet with your e-mail (specified in your settings). You receive your roll link with letter.
</p>
<p>
If you see no e-mail, check spam folder. If you didn't receive mail anyways, write me.
</p>
<p>
<form method=post>
<input type=hidden name=action value='request_free_roll'>
<input type=hidden name=token value='$token'>
<input type=submit value='Request free roll via email'>
</form>
</p>
<p>
If you interested in your message in mail (like an advertisement), write me.
</p>

_END;

        return $result;
}

// Free coins
function html_free_roll($user_uid, $token, $free_roll_token) {
        // Check token roll
        if(!check_roll_token($user_uid, $free_roll_token)) {
                return "<p>Use link from your email</p>\n";
        }
        global $currency_short;
        global $free_roll_cooldown_interval;
        $server_seed_hash=get_server_seed_hash($user_uid);
        $user_seed=get_user_seed($user_uid);
        $user_seed_html=html_escape($user_seed);
        $result="";
        $roll_rewards_data=db_query_to_array("SELECT `roll_min`,`roll_max`,`reward` FROM `rewards` ORDER BY roll_min ASC");
        $result.="<p>\n";
        $result.="<table class='table_horizontal'>\n";
        $result.="<tr><th>Lucky number</th><th>Payout</th></tr>";
        foreach($roll_rewards_data as $roll_rewards) {
                $roll_min=$roll_rewards['roll_min'];
                $roll_max=$roll_rewards['roll_max'];
                $reward=$roll_rewards['reward'];
                if($roll_min!=$roll_max) $lucky_number_text="$roll_min - $roll_max";
                else $lucky_number_text="$roll_min";
                $result.="<tr><td>$lucky_number_text</td><td>$reward $currency_short</td></tr>\n";
        }
        $result.="</table>\n";
        $result.="</p>\n";
        $result.="<p>\n";
        $cooldown_time=free_roll_cooldown_active($user_uid);
//	$recaptcha=html_recaptcha();
	$recaptcha='';
        $result.=<<<_END
<form id=free_roll_form name=free_roll method=post>
<input type=hidden id=action name=action value='free_roll'>
<input type=hidden id=token name=token value='$token'>
<input type=hidden is=server_seed_hash name=server_seed_hash value='$server_seed_hash'>
<small><a href='#' id='seeds_link' onClick='return show_and_hide("seeds","seeds_link")'>view or edit seeds</a></small>
<div id='seeds' class='seeds'>
<p>Server seed hash: <strong id=server_seed_hash>$server_seed_hash</strong></p>
<p>User seed <input type=text id=user_seed name=user_seed value='$user_seed_html'></p>
</div>
<p id=roll_wait_text></p>
<p id=roll_button style='display:none;'>
<input type=button id=roll_button value='Roll' onClick='do_free_roll()'>
</p>
<h2 id=roll_result></h2>
<p id=roll_comment></p>
</form>
<script>
var cooldown_until = Date.now() + $cooldown_time * 1000;

if(!window.cooldownIntervalTimerValue) {
	window.cooldownIntervalTimerValue = setInterval(() => wait_cooldown(), 1000);
}

function do_free_roll() {
        $.post("./", $("#free_roll_form").serialize(), function(result) {
                var result_json = JSON.parse(result);
                if(result_json.result == "ok") {
			pretty_roll(50, result_json);
			cooldown_until = Date.now() + $free_roll_cooldown_interval * 1000;
                }
		else if(result_json.result == "fail") {
                        document.getElementById("roll_comment").innerHTML = "<span class=lost>" + result_json.reason + "</span>";
                }
		else {
                        document.getElementById("roll_comment").innerHTML = "<span class=lost>Unknown error, try to reload page</span>";
                }
        });
}

function pretty_roll(roll_index, result_json) {
	if(roll_index > 0) {
		roll_index--;
		document.getElementById("roll_result").innerHTML = ("00000" + Math.floor(Math.random()*10000)).slice(-5);
		setTimeout(() => pretty_roll(roll_index, result_json), 20);
	}
	else {
		document.getElementById("roll_result").innerHTML = result_json.roll;
		document.getElementById("roll_comment").innerHTML = "<span class=won>You earned " + result_json.reward + " $currency_short</span>";
		document.getElementById("balance").innerHTML = result_json.balance;
		document.getElementById("server_seed_hash").innerHTML = result_json.server_seed_hash;
	}
}

function wait_cooldown() {
	if(!document.getElementById("roll_button")) return;
	cooldown_interval = Math.floor((cooldown_until - Date.now()) / 1000);
        if(cooldown_interval > 0) {
                document.getElementById("roll_button").style.display = "none";
                var minutes_show = Math.floor(cooldown_interval / 60);
                var seconds_show = cooldown_interval % 60
                if(minutes_show < 10) minutes_show = "0" + minutes_show;
                if(seconds_show < 10) seconds_show = "0" + seconds_show;
                document.getElementById("roll_wait_text").innerHTML = "Wait for " + minutes_show + ":" + seconds_show + " before next roll";
        } else {
                document.getElementById("roll_button").style.display = "block";
                document.getElementById("roll_wait_text").innerHTML="";
        }
}

function load_proof_seeds_free() {
	$("#show_proof_seeds").load("./?ajax=1&block=proof_seeds_free",function() {
		show_and_hide("show_proof_seeds","");
	});
	return false;
}
</script>

</p>

<p id='load_proof_seeds_request'>
<small><a href='#' id='roll_link' onClick='return load_proof_seeds_free()'>view last rolls seeds</a></small>
</p>
<p id='show_proof_seeds'>
</p>
_END;

        //$result = "<p>You also can use <a href='https://nicegrc.arikado.ru/'>NiceHash-to-Gridcoin service</a> to earn some Gridcoin.</p>\n";
        //$result .= "<p>That is direct mining to NiceHash with Gridcoin payouts.</p>\n";
        
        return $result;
}

// Dice game
function html_dice_game($user_uid,$token) {
        global $currency_short;
        $server_seed_hash=get_server_seed_hash($user_uid);
        $user_seed=get_user_seed($user_uid);
        $user_seed_html=html_escape($user_seed);

        $bet_lo_limit=get_variable("bet_lo_limit");
        $bet_hi_limit=get_variable("bet_hi_limit");
        $bet_min=get_variable("bet_min");
        $bet_max=get_variable("bet_max");

	$user_balance=get_user_balance($user_uid);

        $result="";
        $result.=<<<_END
<div class=dice_ext>
<div class=dice_int>
<form id=dice_game name=dice_game method=post>
<input type=hidden name=action value='dice_roll'>
<input type=hidden name=token value='$token'>
<input type=hidden id=type name=type value='low'>
<p>Bet <input type=text id=bet name=bet value='$bet_min'> $currency_short
	<input type=button value='x2' onClick='bet_double();'>
	<input type=button value='/2' onClick='bet_half();'>
</p>
<p>Min bet $bet_min $currency_short, max bet $bet_max $currency_short</p>
<small><a href='#' id='seeds_link' onClick='return show_and_hide("seeds","seeds_link")'>view or edit seeds</a></small>
<div id='seeds' class='seeds'>
<p>Server seed hash: <strong id=server_seed_hash>$server_seed_hash</strong></p>
<p>User seed <input type=text id=user_seed name=user_seed value='$user_seed_html'></p>
</div>
<p><input type=button value='Bet LO' onClick='do_dice_roll("low")'> <input type=button value='Bet HI' onClick='do_dice_roll("high")'></p>
Bet LO - below or equals $bet_lo_limit, bet HI - above or equals $bet_hi_limit
<h2 id=roll_result></h2>
<p id=roll_comment></p>
</form>
</div>
</div>
<script>
function get_current_bet() {
	return parseFloat(document.getElementById('bet').value);
}

function set_current_bet(amount) {
	document.getElementById('bet').value=(Math.floor(amount*1000)/1000).toFixed(3);
}

function bet_double() {
	set_current_bet(Math.min($user_balance,$bet_max,get_current_bet()*2));
}

function bet_half() {
	set_current_bet(Math.max($bet_min,get_current_bet()/2));
}

function do_dice_roll(type) {
        document.getElementById("type").value=type;
        $.post("./",$("#dice_game").serialize(),function(result) {
                var result_json=JSON.parse(result);
                if(result_json.result=="ok") {
			pretty_roll(50, result_json);
                } else if(result_json.result=="fail") {
                        document.getElementById("roll_comment").innerHTML="<span class=lost>" + result_json.reason + "</span>";
                } else {
                        document.getElementById("roll_comment").innerHTML="<span class=lost>Unknown error, try to reload page</span>";
                }
        });
}

function pretty_roll(roll_index, result_json) {
	if(roll_index > 0) {
		roll_index--;
		document.getElementById("roll_result").innerHTML = ("00000" + Math.floor(Math.random()*10000)).slice(-5);
		setTimeout(() => pretty_roll(roll_index, result_json), 20);
	}
	else {
		document.getElementById("roll_result").innerHTML = result_json.roll;
		if(result_json.reward>0) document.getElementById("roll_comment").innerHTML = "<span class=won>You earned " + result_json.bet + " $currency_short</span>";
		else document.getElementById("roll_comment").innerHTML = "<span class=lost>You lost " + result_json.bet + " $currency_short</span>";
		document.getElementById("balance").innerHTML = result_json.balance;
		document.getElementById("server_seed_hash").innerHTML = result_json.server_seed_hash;
	}
}

function load_proof_seeds_dice() {
	$("#show_proof_seeds").load("./?ajax=1&block=proof_seeds_dice",function() {
		show_and_hide("show_proof_seeds","");
	});
	return false;
}

</script>

<p id='load_proof_seeds_request'>
<small><a href='#' id='roll_link' onClick='return load_proof_seeds_dice()'>click here to view last rolls seeds</a></small>
</p>
<p id='show_proof_seeds'>
</p>

_END;
        return $result;
}

// Last rolls
function html_last_rolls($user_uid, $token, $roll_type = '') {
        global $currency_short;
        $result = "";

        $user_uid_escaped = db_escape($user_uid);

	if($roll_type == 'free') {
	        $rolls_data_array = db_query_to_array("SELECT `roll_type`,`server_seed`,`user_seed`,`roll_result`,`bet`,`profit`,`timestamp` FROM `rolls` WHERE `user_uid`='$user_uid_escaped' AND `roll_type` = 'free' ORDER BY `timestamp` DESC LIMIT 20");
	}
	else if($roll_type == 'bet') {
	        $rolls_data_array = db_query_to_array("SELECT `roll_type`,`server_seed`,`user_seed`,`roll_result`,`bet`,`profit`,`timestamp` FROM `rolls` WHERE `user_uid`='$user_uid_escaped' AND `roll_type` IN ('high','low') ORDER BY `timestamp` DESC LIMIT 20");
	}
	else {
	        $rolls_data_array = db_query_to_array("SELECT `roll_type`,`server_seed`,`user_seed`,`roll_result`,`bet`,`profit`,`timestamp` FROM `rolls` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 20");
	}
        $result.="<p>\n";
        $result.="<table class='table_horizontal'>\n";
        $result.="<tr><th>Type</th><th>Seeds</th><th>Roll</th><th>Bet</th><th>Reward</th><th>Timestamp</th></tr>";
        foreach($rolls_data_array as $roll_data) {
                $type=$roll_data['roll_type'];
                $server_seed=$roll_data['server_seed'];
                $user_seed=$roll_data['user_seed'];
                $roll_result=$roll_data['roll_result'];
                $bet=$roll_data['bet'];
                $profit=$roll_data['profit'];
                $timestamp=$roll_data['timestamp'];

                $user_seed_html=html_escape($user_seed);
                $server_seed_html=html_escape($server_seed);

                $seed_text="Server:<br>$server_seed_html<br><br>User seed:<br>$user_seed_html";

                if($type=="low") $type_text="Bet LO";
                else if($type=="high") $type_text="Bet HI";
		else if($type=="pay") $type_text="Pay";
                else $type_text="Free";

		if($type=="pay") {
			$seed_text = "Compound interest on user funds";
			$profit = sprintf("%0.4f",$profit);
		} else {
			$seed_text = "<div class='block_with_container'>view <div class='block_with_container_inside'>$seed_text</div></div>";
		}

                $result.="<tr><td>$type_text</td><td>$seed_text</td><td>$roll_result</td><td>$bet</td><td>$profit</td><td>$timestamp</td></tr>\n";
        }
        $result.="</table>\n";
        $result.="</p>\n";

        return $result;
}

// Send and receive
function html_send_receive($user_uid,$token) {
        global $currency_short;
        $result="";

        $deposit_address=get_user_deposit_address($user_uid);
        $withdraw_address=get_user_withdraw_address($user_uid);
        $balance=get_user_balance($user_uid);
        $withdraw_min=get_variable("withdraw_min");

	if($deposit_address == "") {
		$deposit_address_string="<p>Your deposit address is not generated yet, should be ready in 10 minutes</p>";
	} else {
		$deposit_address_string="<p>Your deposit address is <strong>$deposit_address</strong></p>";
	}

        $result.=<<<_END
<h2>Deposit</h2>
$deposit_address_string
<p>
<form name=withdraw method=post>
<h2>Withdraw</h2>
<input type=hidden name=action value='withdraw'>
<input type=hidden name=token value='$token'>
<p>Your balance: $balance $currency_short</p>
<p>Your withdraw address: <strong>$withdraw_address</strong></p>
<p>Min withdraw amount: $withdraw_min $currency_short</p>
<p><input type=text name=amount value='0'> $currency_short <input type=submit value='withdraw'>
</form>
</p>

_END;

        $result.=html_transactions($user_uid,$token);

        return $result;
}

// Transactions
function html_transactions($user_uid,$token) {
        global $currency_short;
        global $wallet_receive_confirmations;
        $result="";

        $user_uid_escaped=db_escape($user_uid);

        $tx_data_array=db_query_to_array("SELECT `amount`,`address`,`status`,`tx_id`,`confirmations`,`timestamp` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 20");

        $result.="<h2>Transactions</h2>\n";
        $result.="<p>\n";
        $result.="<table class='table_horizontal'>\n";
        $result.="<tr><th>Address</th><th>Amount, $currency_short</th><th>Status</th><th>TX ID</th><th>Timestamp</th></tr>";
        foreach($tx_data_array as $tx_data) {
                $address=$tx_data['address'];
                $amount=$tx_data['amount'];
                $status=$tx_data['status'];
                $tx_id=$tx_data['tx_id'];
                $confirmations=$tx_data['confirmations'];
                $timestamp=$tx_data['timestamp'];

                $address_html=html_address_url($address);
                $tx_id_html=html_tx_url($tx_id);

                if($status=='pending') {
                        $status_text="$status ($confirmations/$wallet_receive_confirmations)";
                } else {
                        $status_text=$status;
                }

                $result.="<tr><td>$address_html</td><td>$amount</td><td>$status_text</td><td>$tx_id_html</td><td>$timestamp</td></tr>\n";
        }
        $result.="</table>\n";
        $result.="</p>\n";

        return $result;
}

// Minesweeper
function html_minesweeper($user_uid,$token) {
        $game_uid=new_game($user_uid);

        $field=load_field($game_uid);
        $user_field=to_user_field($field);
        $server_seed_hash=get_current_game_hash($game_uid);
//get_server_seed_hash($user_uid);

        $result="";
        $result.="<p>Server seed hash: <b>$server_seed_hash</b></p>\n";
        $result.="<h2>Minesweeper</h2>";
        $result.="<p id=result></p>";
        $result.="<table class=horizontal_table>";

        for($y=0;$y!=16;$y++) {
                $result.="<tr>";
                for($x=0;$x!=16;$x++) {
                        $result.="<td><input id='cell_${x}_${y}' type=button value='&nbsp;' onClick='do_move($x,$y);'></td>";
                }
                $result.="</tr>";
        }

        $result.="</table>";
        $result.=<<<_END
<script>

function do_move(x,y) {
//      alert("y " + y + " x " + x);
        var query={
                token:"$token",
                action:"minesweeper",
                x:x,
                y:y
        };
        $.post("./",query,function(result){
                result_json=JSON.parse(result);
                if(result_json.result=="win") {
                        document.getElementById("result").innerHTML="You win!";
                } else if(result_json.result=="lose") {
                        document.getElementById("result").innerHTML="You lose!";
                }
                update_field(result_json.field,result_json.result);
        })
}

function update_field(field,result) {
        var ch;
        for(var y=0;y!=16;y++) {
                for(var x=0;x!=16;x++) {
                        var button=document.getElementById("cell_"+x+"_"+y);
                        button.style.fontWeight=900;
                        ch=field.charAt(x+y*16);
                        if(ch=='c') ch=' ';
                        if(ch=='0') button.style.color='gray';
                        if(ch=='1') button.style.color='blue';
                        if(ch=='2') button.style.color='green';
                        if(ch=='3') button.style.color='red';
                        if(ch=='4') button.style.color='black';
                        if(ch=='5') button.style.color='black';
                        if(ch=='6') button.style.color='black';
                        if(ch=='7') button.style.color='black';
                        if(ch=='8') button.style.color='black';
                        if(ch=='b' && result=="lose") button.style.background='red';
                        if(ch=='b' && result=="win") button.style.background='green';
                        button.value=ch;
                }
        }
}

update_field("$user_field","continue");
</script>

_END;
        return $result;
}

// Earn currency tab
function html_earn($user_uid,$token) {
        global $currency_short;
	global $daily_percentage;

	$user_balance=get_user_balance($user_uid);

	$weekly_percentage=pow(1+$daily_percentage/100,7)-1;
	$monthly_percentage=pow(1+$daily_percentage/100,30)-1;
	$yearly_percentage=pow(1+$daily_percentage/100,365)-1;

	$daily_earnings=$user_balance*$daily_percentage/100;
	$weekly_earnings=$user_balance*$weekly_percentage;
	$monthly_earnings=$user_balance*$monthly_percentage;
	$yearly_earnings=$user_balance*$yearly_percentage;

	$weekly_percentage=sprintf("%0.4f",$weekly_percentage*100);
	$monthly_percentage=sprintf("%0.4f",$monthly_percentage*100);
	$yearly_percentage=sprintf("%0.4f",$yearly_percentage*100);

	$daily_earnings=sprintf("%0.8f",$daily_earnings);
	$weekly_earnings=sprintf("%0.8f",$weekly_earnings);
	$monthly_earnings=sprintf("%0.8f",$monthly_earnings);
	$yearly_earnings=sprintf("%0.8f",$yearly_earnings);

	$result="";
	$result.=<<<_END
<h2>Earn $currency_short</h2>
<p>We add $daily_percentage % to user balance daily as compound interest.</p>

<table class='table_horizontal'>
<tr>
	<th>Your balance</th>
	<td></td>
	<td><input type=text id=user_balance value='$user_balance' onChange='recalc_earnings();' onKeyUp='recalc_earnings();'> $currency_short</td>
</tr>
<tr>
	<th>One day</th>
	<td>$daily_percentage %</td>
	<td><span id=daily_earnings>$daily_earnings</span> $currency_short</td>
</tr>
<tr>
	<th>One week</th>
	<td>$weekly_percentage %</td>
	<td><span id=weekly_earnings>$weekly_earnings</span> $currency_short</td>
</tr>
<tr>
	<th>One month (30 days)</th>
	<td>$monthly_percentage %</td>
	<td><span id=monthly_earnings>$monthly_earnings</span> $currency_short</td>
</tr>
<tr>
	<th>One year (365 days)</th>
	<td>$yearly_percentage %</td>
	<td><span id=yearly_earnings>$yearly_earnings</span> $currency_short</td>
</tr>
</table>

<script>
function recalc_earnings() {
	let user_balance = parseFloat(document.getElementById('user_balance').value);
	document.getElementById('daily_earnings').innerHTML = (user_balance * $daily_percentage/100).toFixed(8);
	document.getElementById('weekly_earnings').innerHTML = (user_balance * $weekly_percentage/100).toFixed(8);
	document.getElementById('monthly_earnings').innerHTML = (user_balance * $monthly_percentage/100).toFixed(8);
	document.getElementById('yearly_earnings').innerHTML = (user_balance * $yearly_percentage/100).toFixed(8);
}
</script>
_END;

	$result.=<<<_END
<h2>Your earnings</h2>
<table class='table_horizontal'>
<tr><th>Date</th><th>Earnings</th></tr>
_END;

	$user_uid_escaped=db_escape($user_uid);
	$earnings_data=db_query_to_array("SELECT `timestamp`,`profit` FROM `rolls`
		WHERE `user_uid`='$user_uid_escaped' AND `roll_type`='pay' ORDER BY `timestamp` DESC LIMIT 20");

	foreach($earnings_data as $earnings_row) {
		$timestamp=$earnings_row['timestamp'];
		$profit=$earnings_row['profit'];
		$profit=sprintf("%0.8f",$profit);
		$result.="<tr><td>$timestamp</td><td>$profit $currency_short</td></tr>\n";
	}

	$result.="<table>\n";

	return $result;
}

// Lottery tab
function html_lottery($user_uid,$token) {
        global $currency_short;
	global $lottery_ticket_price;

	$result="";

	$result.="<h2>Lottery</h2>";

	$round_uid=lottery_get_actual_round();
	$total_tickets=lottery_get_round_tickets($round_uid);
	$user_tickets=lottery_get_round_user_tickets($round_uid,$user_uid);
	$prize_fund=lottery_get_round_prize_fund($round_uid);
	$round_start=lottery_get_round_start($round_uid);
	$round_stop=lottery_get_round_stop($round_uid);
	$round_stop_interval=lottery_get_round_stop_interval($round_uid);
	$server_seed_hash=lottery_get_server_seed_hash($round_uid);

	$round_stop_hours=floor($round_stop_interval/3600);
	$round_stop_minutes=floor(($round_stop_interval%3600)/60);
	$round_stop_seconds=floor($round_stop_interval%60);

	if($user_tickets>0 && $total_tickets>0) {
		$probability=$user_tickets/$total_tickets;
		$probability=sprintf("%0.6f",$probability*100);
	} else {
		$probability=0;
	}

	$prize_fund=sprintf("%0.8f",$prize_fund);

	$result.=<<<_END
<h3>Current round</h3>
<p>Server seed hash: <strong>$server_seed_hash</strong></p>

<table class='table_horizontal'>
<tr><th>Round #</th><td>$round_uid</td></tr>
<tr><th>Round begin</th><td>$round_start</td></tr>
<tr><th>Round end</th><td>in $round_stop_hours hours $round_stop_minutes minutes</td></tr>
<tr><th>Prize fund</th><td>$prize_fund $currency_short</td></tr>
<tr><th>Total tickets</th><td>$total_tickets</td></tr>
<tr><th>Your tickets</th><td>$user_tickets</td></tr>
<tr><th>Chance to be the 1st</th><td>$probability %</td></tr>
</table>

_END;

	$result.=<<<_END
<h3>Buy tickets</h3>
<form name=lottery_buy method=post>
<input type=hidden name=action value='lottery_buy'>
<input type=hidden name=token value='$token'>
<p>Ticket price: $lottery_ticket_price $currency_short</p>
<p>Tickets to buy: <input type=text name=amount value='0'> <input type=submit value='Buy'></p>
</form>

<h3>Prize fund distribution</h3>
<table class='table_horizontal'>
<tr><th>Place</th><th>% of funds</th><th>Reward</th></tr>
_END;

	$places_data=db_query_to_array("SELECT `place`,`percentage` FROM `lottery_rewards` ORDER BY `place`");
	foreach($places_data as $place_row) {
		$place=$place_row['place'];
		$percentage=$place_row['percentage'];
		$reward=$prize_fund*$percentage/100;
		$result.="<tr><td>$place</td><td>$percentage</td><td>$reward $currency_short</td></tr>\n";
	}
	$result.="</table>\n";

	$round_uid=lottery_get_finished_round();
	if($round_uid) {
		$total_tickets=lottery_get_round_tickets($round_uid);
		$prize_fund=lottery_get_round_prize_fund($round_uid);
		$round_start=lottery_get_round_start($round_uid);
		$round_stop=lottery_get_round_stop($round_uid);
		$server_seed=lottery_get_server_seed($round_uid);
		$server_seed_hash=lottery_get_server_seed_hash($round_uid);

		$prize_fund=sprintf("%0.8f",$prize_fund);

		$result.=<<<_END
<h3>Previous round</h3>
<p>Server seed hash: <strong>$server_seed_hash</strong></p>
<p>Server seed: <strong>$server_seed</strong></p>
<table class='table_horizontal'>
<tr><th>Round #</th><td>$round_uid</td></tr>
<tr><th>Round begin</th><td>$round_start</td></tr>
<tr><th>Round end</th><td>$round_stop</td></tr>
<tr><th>Prize fund</th><td>$prize_fund $currency_short</td></tr>
<tr><th>Total tickets</th><td>$total_tickets</td></tr>
</table>

<h3>Previous round winners</h3>

<table class='table_horizontal'>
<tr><th>Place</th><th>User ID</th><th>Tickets</th><th>Prize</th></tr>
_END;

	$round_uid_escaped=db_escape($round_uid);
	$winners_data=db_query_to_array("SELECT `user_uid`,`tickets`,`reward`
		FROM `lottery_tickets`
		WHERE `round_uid`='$round_uid_escaped' AND `reward`>0
		ORDER BY `best_hash` ASC");

	$place=1;
	foreach($winners_data as $winner_row) {
		$winner_user_uid=$winner_row['user_uid'];
		$tickets=$winner_row['tickets'];
		$reward=$winner_row['reward'];

		$result.="<tr><td>$place</td><td>$winner_user_uid</td><td>$tickets</td><td>$reward</td></tr>\n";
		$place++;
	}

	$result.="</table>\n";
	}

	$result.=<<<_END
<h3>Last 10 rounds</h3>
<table class='table_horizontal'>
<tr><th>Round</th><th>Your place</th><th>Your reward</th></tr>

_END;

	$user_uid_escaped=db_escape($user_uid);
	$all_round_data=db_query_to_array("SELECT `round_uid`,`best_hash`,`reward` FROM `lottery_tickets`
		WHERE `user_uid`='$user_uid_escaped' AND `reward` IS NOT NULL ORDER BY `round_uid` DESC LIMIT 10");

	foreach($all_round_data as $round_row) {
		$round_uid=$round_row['round_uid'];
		$reward=$round_row['reward'];
		$best_hash=$round_row['best_hash'];
		$round_uid_escaped=db_escape($round_uid);
		$best_hash_escaped=db_escape($best_hash);
		$place=db_query_to_variable("SELECT count(*) FROM `lottery_tickets`
			WHERE `round_uid`='$round_uid' AND `best_hash`<='$best_hash_escaped'");
		$result.="<tr><td>$round_uid</td><td>$place</td><td>$reward $currency_short</td></tr>\n";
	}

	$result.="</table>\n";

	return $result;
}

function html_exchange($user_uid, $token) {
        global $exchange_fee;
        $result = "";

        $currencies_data = ex_get_currencies_data();
        
        // Balances block
        $result .= <<<_END
<h2>Balances</h2>
<table class='table_horizontal'>
<tr><th>Currency</th><th>Deposit address</th><th>Balance</th></tr>

_END;

        foreach($currencies_data as $currency_row) {
                $currency_uid = $currency_row['uid'];
                $currency_name = $currency_row['name'];
                $currency_symbol = $currency_row['symbol'];

                if($currency_symbol == 'GRC') {
                        $wallet_data = [
                                "deposit_address" => get_user_deposit_address($user_uid),
                                "balance" => get_user_balance($user_uid),
                        ];
                }
                else {
                        $wallet_data = ex_get_wallet_data_by_user_uid_currency_uid($user_uid, $currency_uid);
                }

                if($wallet_data) {
                        $deposit_address = $wallet_data['deposit_address'];
                        $balance = $wallet_data['balance'];

                        if(!$deposit_address) {
                                $deposit_address = "<i>generating...</i>";
                        }
                        $result .= "<tr><td>$currency_name</td>";
                        $result .= "<td>$deposit_address</td>";
                        $result .= "<td>$balance $currency_symbol</td></tr>\n";
                }
                else {
                        $request_address_form = "<form method=post>";
                        $request_address_form .= "<input type=hidden name='action' value='exchange_request_address'>";
                        $request_address_form .= "<input type=hidden name='currency_uid' value='$currency_uid'>";
                        $request_address_form .= "<input type=hidden name='token' value='$token'>";
                        $request_address_form .= "<input type=submit value='Request address'>";
                        $request_address_form .= "</form>";
                        $result .= "<tr><td>$currency_name</td>";
                        $result .= "<td colspan=2>$request_address_form</td></tr>";
                }
        }

        $result .= "</table>\n";

        $result .= <<<_END
<h2>Transactions</h2>
<table class='table_horizontal'>
<tr><th>Currency</th><th>Amount</th><th>Address and TX ID</th><th>Status</th><th>Timestamp</th></tr>

_END;

        $transactions_data = ex_get_user_transactions($user_uid);
        foreach($transactions_data as $tx_row) {
                $amount = $tx_row['amount'];
                $address = $tx_row['address'];
                $name = $tx_row['name'];
                $status = $tx_row['status'];
                $timestamp = $tx_row['timestamp'];
                $tx_id = $tx_row['tx_id'];

                $result .= "<tr>\n";
                $result .= "<td>$name</td>\n";
                $result .= "<td>$amount</td>\n";
                $result .= "<td>$address<br>$tx_id</td>\n";
                $result .= "<td>$status</td>\n";
                $result .= "<td>$timestamp</td>\n";
                $result .= "</tr>\n";
        }
        $result .= "</table>\n";
        
        $currency_select = html_currency_select();
        $result .= <<<_END
<h2>Withdraw</h2>
<p>
<form method=post>
<input type=hidden name='action' value='exchange_withdraw'>
<input type=hidden name='token' value='$token'>
<p>Currency: <select name='currency_uid' id='withdraw_currency_uid' onChange='updateWithdrawFee();'>
$currency_select
</select></p>
<p>Address: <input type=text name='address' size=40></p>
<p>Amount: <input type=text name='amount' id='withdraw_amount' value='0.00000000' onChange='updateWithdrawFee();'></p>
<p>Fee: <input type=text name='withdraw_fee' id='withdraw_fee' value='0' disabled></p>
<p>Total: <input type=text name='withdraw_total' id='withdraw_total' value='0' disabled></p>
<p>Password: <input type=password name='password'></p>
<input type=submit value='Withdraw'>
</form>
</p>

_END;

        $result .= <<<_END
<h2>Exchange</h2>
<p>
<form method=post>
<input type=hidden name='action' value='exchange_exchange'>
<input type=hidden name='token' value='$token'>
<p>From currency: <select name='from_currency_uid' id='from_currency_uid' onChange='updateExchangeAmount();'>
$currency_select
</select></p>
<p>Amount: <input type=text name='from_amount' id='from_amount' value='0.00000000' onChange='updateExchangeAmount();'></p>
<p>To currency: <select name='to_currency_uid' id='to_currency_uid' onChange='updateExchangeAmount();'>
$currency_select
</select></p>
<p>Result (estimation): <input type=text id=to_amount disabled></p>
<p>Exchange fee: <input type=text id=exchange_fee_amount disabled></p>
<input type=submit value='Exchange'>
</form>
</p>
_END;

        // Exchanges history
        $result .= <<<_END
        <h2>Exchanges</h2>
        <table class='table_horizontal'>
        <tr><th>From Currency</th><th>From Amount</th><th>Rate</th><th>To Currency</th><th>To Amount</th><th>Timestamp</th></tr>
        
        _END;
        
                $exchanges_data = ex_get_user_exchanges($user_uid);
                foreach($exchanges_data as $ex_row) {
                        $from_name = $ex_row['from_name'];
                        $from_amount = $ex_row['from_amount'];
                        $rate = $ex_row['rate'];
                        $to_name = $ex_row['to_name'];
                        $to_amount = $ex_row['to_amount'];
                        $timestamp = $ex_row['timestamp'];
        
                        $result .= "<tr>\n";
                        $result .= "<td>$from_name</td>\n";
                        $result .= "<td>$from_amount</td>\n";
                        $result .= "<td>$rate</td>\n";
                        $result .= "<td>$to_name</td>\n";
                        $result .= "<td>$to_amount</td>\n";
                        $result .= "<td>$timestamp</td>\n";
                        $result .= "</tr>\n";
                }
                $result .= "</table>\n";
                
        // JS functions
        $currencies_data_json = json_encode($currencies_data);
        $result .= <<<_END
<script>
let currenciesData = JSON.parse('$currencies_data_json');
let exchangeFee = parseFloat("$exchange_fee");

function getcurrencyDataByUid(currency_uid) {
        let result = null;
        currenciesData.forEach(function(currency) {
                if(currency.uid == currency_uid) {
                        result = currency;
                }
        })
        return result;
}

function updateWithdrawFee() {
        let currency_uid = $("#withdraw_currency_uid").val();
        let currency = getcurrencyDataByUid(currency_uid);

        $("#withdraw_fee").val(currency.withdraw_fee);
        $("#withdraw_total").val(parseFloat($("#withdraw_amount").val()) + parseFloat(currency.withdraw_fee));
}

function updateExchangeAmount() {
        let from_currency_uid = $("#from_currency_uid").val();
        let to_currency_uid = $("#to_currency_uid").val();
        let from_currency = getcurrencyDataByUid(from_currency_uid);
        let to_currency = getcurrencyDataByUid(to_currency_uid);
        
        let from_amount = parseFloat($("#from_amount").val());
        let rate = from_currency.rate / to_currency.rate;
        
        $("#to_amount").val(from_amount * rate * (1 - exchangeFee));
        $("#exchange_fee_amount").val(from_amount * rate * exchangeFee);
}

updateWithdrawFee();
updateExchangeAmount();
</script>

_END;

        return $result;
}

function html_currency_select() {
        $result = '';

        $currencies_data = ex_get_currencies_data();
        foreach($currencies_data as $currency_row) {
                $currency_uid = $currency_row['uid'];
                $currency_name = $currency_row['name'];
                $result .= "<option value='$currency_uid'>$currency_name</option>\n";
        }

        return $result;
}