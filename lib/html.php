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
        $result=<<<_END
<hr width=10%>
<p>%footer_about%</p>
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
                $result.=html_menu_element("free_roll","Free $currency_short");
                //$result.=html_menu_element("minesweeper","Minesweeper");
                $result.=html_menu_element("dice_roll","Multiply $currency_short");
                $result.=html_menu_element("last_rolls","Last rolls");
                $result.=html_menu_element("lotto","Lottery<sup style='color:red;'>&beta;</sup>");
                $result.=html_menu_element("send_receive","Send and receive");
                $result.=html_menu_element("settings","%tab_settings%");
                if(is_admin($user_uid)) {
                        $result.=html_menu_element("control","%tab_control%");
                        $result.=html_menu_element("log","%tab_log%");
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

// Free coins
function html_free_roll($user_uid,$token) {
        global $currency_short;
        global $free_roll_cooldown_interval;
        $server_seed_hash=get_server_seed_hash($user_uid);
        $user_seed=get_user_seed($user_uid);
        $user_seed_html=html_escape($user_seed);
        $result="";
        $roll_rewards_data=db_query_to_array("SELECT `roll_min`,`roll_max`,`reward` FROM `rewards` ORDER BY roll_min ASC");
        $result.="<p>Server seed hash: <strong id=server_seed_hash>$server_seed_hash</strong></p>\n";
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
	$recaptcha=html_recaptcha();
        $result.=<<<_END
<form id=free_roll_form name=free_roll method=post>
<input type=hidden id=action name=action value='free_roll'>
<input type=hidden id=token name=token value='$token'>
<input type=hidden is=server_seed_hash name=server_seed_hash value='$server_seed_hash'>
<p>User seed <input type=text id=user_seed name=user_seed value='$user_seed_html'></p>
<p id=roll_wait_text></p>
$recaptcha
<p id=roll_button><input type=button id=roll_button value='Roll' onClick='do_free_roll()'></p>
<h2 id=roll_result></h2>
<p id=roll_comment></p>
</form>
<script>
function do_free_roll() {
        $.post("./",$("#free_roll_form").serialize(),function(result) {
                var result_json=JSON.parse(result);
                if(result_json.result=="ok") {
                        document.getElementById("roll_result").innerHTML=result_json.roll;
                        document.getElementById("roll_comment").innerHTML="<span class=won>You earned " + result_json.reward + " $currency_short</span>";
                        document.getElementById("balance").innerHTML=result_json.balance;
                        document.getElementById("server_seed_hash").innerHTML=result_json.server_seed_hash;
                        wait_cooldown($free_roll_cooldown_interval);
                } else if(result_json.result=="fail") {
                        document.getElementById("roll_comment").innerHTML="<span class=lost>" + result_json.reason + "</span>";
                } else {
                        document.getElementById("roll_comment").innerHTML="<span class=lost>Unknown error, try to reload page</span>";
                }
        });
}

function wait_cooldown(seconds) {
        if(seconds > 0) {
                document.getElementById("roll_button").style.display = "none";
                var minutes_show=Math.floor(seconds/60);
                var seconds_show=seconds%60
                if(minutes_show<10) minutes_show="0"+minutes_show;
                if(seconds_show<10) seconds_show="0"+seconds_show;
                document.getElementById("roll_wait_text").innerHTML="Wait for " + minutes_show + ":" + seconds_show + " before next roll";
                var next_seconds=seconds-1;
                setTimeout("wait_cooldown("+next_seconds+")",1000);
        } else {
                document.getElementById("roll_button").style.display = "block";
                document.getElementById("roll_wait_text").innerHTML="";
        }
}
</script>

_END;
        $result.="</p>\n";

        if($cooldown_time>0) {
                $result.=<<<_END
<script>
wait_cooldown($cooldown_time);
</script>
_END;
        }

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

        $result="";
        $result.=<<<_END
<div class=dice_ext>
<div class=dice_int>
<form id=dice_game name=dice_game method=post>
<input type=hidden name=action value='dice_roll'>
<input type=hidden name=token value='$token'>
<input type=hidden id=type name=type value='low'>
<p>Server seed hash: <strong id=server_seed_hash>$server_seed_hash</strong></p>
<p>Bet <input type=text name=bet value='$bet_min'> $currency_short</p>
<p>Min bet $bet_min $currency_short, max bet $bet_max $currency_short</p>
<p>User seed <input type=text id=user_seed name=user_seed value='$user_seed_html'></p>
<p><input type=button value='Bet LO' onClick='do_dice_roll("low")'> <input type=button value='Bet HI' onClick='do_dice_roll("high")'></p>
Bet LO - below or equals $bet_lo_limit, bet HI - above or equals $bet_hi_limit
<h2 id=roll_result></h2>
<p id=roll_comment></p>
</form>
</div>
</div>
<script>
function do_dice_roll(type) {
        document.getElementById("type").value=type;
        $.post("./",$("#dice_game").serialize(),function(result) {
                var result_json=JSON.parse(result);
                if(result_json.result=="ok") {
                        document.getElementById("roll_result").innerHTML=result_json.roll;
                        if(result_json.reward>0) document.getElementById("roll_comment").innerHTML="<span class=won>You earned " + result_json.bet + " $currency_short</span>";
                        else document.getElementById("roll_comment").innerHTML="<span class=lost>You lost " + result_json.bet + " $currency_short</span>";
                        document.getElementById("balance").innerHTML=result_json.balance;
                        document.getElementById("server_seed_hash").innerHTML=result_json.server_seed_hash;
                } else if(result_json.result=="fail") {
                        document.getElementById("roll_comment").innerHTML="<span class=lost>" + result_json.reason + "</span>";
                } else {
                        document.getElementById("roll_comment").innerHTML="<span class=lost>Unknown error, try to reload page</span>";
                }
        });
}
</script>

_END;
        return $result;
}

// Last rolls
function html_last_rolls($user_uid,$token) {
        global $currency_short;
        $result="";

        $user_uid_escaped=db_escape($user_uid);

        $rolls_data_array=db_query_to_array("SELECT `roll_type`,`server_seed`,`user_seed`,`roll_result`,`bet`,`profit`,`timestamp` FROM `rolls` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 20");
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

function html_lotto($user_uid,$token) {
        global $currency_short;
	global $lotto_ticket_price;

	$result="";

	$result.="<h2>Lottery</h2>";

	$round_uid=lotto_get_actual_round();
	$total_tickets=lotto_get_round_tickets($round_uid);
	$user_tickets=lotto_get_round_user_tickets($round_uid,$user_uid);
	$prize_fund=lotto_get_round_prize_fund($round_uid);
	$round_start=lotto_get_round_start($round_uid);
	$round_stop=lotto_get_round_stop($round_uid);
	$server_seed_hash=lotto_get_server_seed_hash($round_uid);

	if($user_tickets>0 && $total_tickets>0) {
		$probability=$user_tickets/$total_tickets;
		$probability=sprintf("%0.6f",$probability*100);
	} else {
		$probability=0;
	}

	$result.=<<<_END
<h3>Current round</h3>
<p>Server seed hash: <strong>$server_seed_hash</strong></p>

<table class='table_horizontal'>
<tr><th>Round #</th><td>$round_uid</td></tr>
<tr><th>Round begin</th><td>$round_start</td></tr>
<tr><th>Round end</th><td>$round_stop</td></tr>
<tr><th>Prize fund</th><td>$prize_fund $currency_short</td></tr>
<tr><th>Total tickets</th><td>$total_tickets</td></tr>
<tr><th>Your tickets</th><td>$user_tickets</td></tr>
<tr><th>Chance to be 1st</th><td>$probability %</td></tr>
</table>

_END;

	$result.=<<<_END
<h3>Buy tickets</h3>
<form name=lotto_buy method=post>
<input type=hidden name=action value='lotto_buy'>
<input type=hidden name=token value='$token'>
<p>Ticket price: $lotto_ticket_price $currency_short</p>
<p>Tickets to buy: <input type=text name=amount value='0'> <input type=submit value='Buy'></p>
</form>

<h3>Prize fund distribution</h3>
<table class='table_horizontal'>
<tr><th>Place</th><th>% of funds</th><th>Reward</th></tr>
_END;

	$places_data=db_query_to_array("SELECT `place`,`percentage` FROM `lotto_rewards` ORDER BY `place`");
	foreach($places_data as $place_row) {
		$place=$place_row['place'];
		$percentage=$place_row['percentage'];
		$reward=$prize_fund*$percentage/100;
		$result.="<tr><td>$place</td><td>$percentage</td><td>$reward $currency_short</td></tr>\n";
	}
	$result.="</table>\n";

	$round_uid=lotto_get_finished_round();
	if($round_uid) {
		$total_tickets=lotto_get_round_tickets($round_uid);
		$prize_fund=lotto_get_round_prize_fund($round_uid);
		$round_start=lotto_get_round_start($round_uid);
		$round_stop=lotto_get_round_stop($round_uid);

		$result.=<<<_END
<h3>Previous round</h3>
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
		FROM `lotto_tickets`
		WHERE `round_uid`='$round_uid_escaped' AND `reward`>0
		ORDER BY `best_hash` ASC");

	$place=1;
	foreach($winners_data as $winner_row) {
		$user_uid=$winner_row['user_uid'];
		$tickets=$winner_row['tickets'];
		$reward=$winner_row['reward'];

		$result.="<tr><td>$place</td><td>$user_uid</td><td>$tickets</td><td>$reward</td></tr>\n";
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
	$all_round_data=db_query_to_array("SELECT `round_uid`,`best_hash`,`reward` FROM `lotto_tickets`
		WHERE `user_uid`='$user_uid_escaped' AND `reward` IS NOT NULL ORDER BY `round_uid` DESC LIMIT 10");
/*
	foreach($all_round_data as $round_row) {
		$round_uid=$round_row['round_uid'];
		$reward=$round_row['reward'];
		$best_hash=$round_row['best_hash'];
		$round_uid_escaped=db_escape($round_uid);
		$best_hash_escaped=db_escape($best_hash);
		$place=db_query_to_variable("SELECT count(*) FROM `lotto_tickets`
			WHERE `round_uid`='$round_uid' AND `best_hash`<='$best_hash_escaped'");
		echo "<tr><td>$round_uid</td><td>$place</td><td>$reward</td></tr>\n";
	}
*/
	$result.="</table>\n";

	return $result;
}
?>
