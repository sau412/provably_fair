<?php
require_once("../lib/settings.php");
require_once("../lib/language.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/html.php");
require_once("../lib/captcha.php");
require_once("../lib/minesweeper.php");

db_connect();

if(isset($_COOKIE['lang'])) $lang=$_COOKIE['lang'];
else $lang=$default_language;
$current_language=lang_load($lang);

$session=get_session();
$user_uid=get_user_uid_by_session($session);
$token=get_user_token_by_session($session);

// Captcha
if(isset($_GET['captcha'])) {
        captcha_show($session);
        die();
}

if(isset($_POST['action'])) $action=stripslashes($_POST['action']);
else if(isset($_GET['action'])) $action=stripslashes($_GET['action']);

if(isset($action)) {
        if(isset($_POST['token'])) $received_token=stripslashes($_POST['token']);
        else if(isset($_GET['token'])) $received_token=stripslashes($_GET['token']);
        if($received_token!=$token) die("Wrong token");

        if($action=='login') {
                $captcha_code=stripslashes($_POST['captcha_code']);
                if(captcha_check($session,$captcha_code)) {
                        $login=stripslashes($_POST['login']);
                        $password=stripslashes($_POST['password']);
                        $message=user_login($session,$login,$password);
                } else {
                        $message="login_failed_invalid_captcha";
                }
                captcha_regenerate($session);
        } else if($action=='register') {
                $captcha_code=stripslashes($_POST['captcha_code']);
                if(captcha_check($session,$captcha_code)) {
                        $login=stripslashes($_POST['login']);
                        $mail=stripslashes($_POST['mail']);
                        $password1=stripslashes($_POST['password1']);
                        $password2=stripslashes($_POST['password2']);
                        $withdraw_address=stripslashes($_POST['withdraw_address']);
                        $message=user_register($session,$mail,$login,$password1,$password2,$withdraw_address);
                } else {
                        $message="register_failed_invalid_captcha";
                }
                captcha_regenerate($session);
        } else if($action=='logout') {
                user_logout($session);
                $message="logout_successfull";
        } else if($action=='free_roll') {
                $user_seed=stripslashes($_POST['user_seed']);
                update_user_seed($user_uid,$user_seed);
                $result=do_free_roll($user_uid);
                echo json_encode($result);
                die();
        } else if($action=='dice_roll') {
                $user_seed=stripslashes($_POST['user_seed']);
                $bet=stripslashes($_POST['bet']);
                $type=stripslashes($_POST['type']);
                update_user_seed($user_uid,$user_seed);
                $result=do_dice_roll($user_uid,$bet,$type);
                echo json_encode($result);
                die();
        } else if($action=='minesweeper') {
                $x=stripslashes($_POST['x']);
                $y=stripslashes($_POST['y']);
                $game_uid=new_game($user_uid);
                $field=load_field($game_uid);
                $action=array("x"=>$x,"y"=>$y,"action"=>"open");
                $result_data=apply_action($field,$action);
                save_field($game_uid,$result_data['field'],$action);
                if($result_data['result']!='continue') {
                        echo json_encode($result_data);
                        finish($game_uid);
                } else {
                        $result_data['field']=to_user_field($result_data['field']);
                        echo json_encode($result_data);
                }
                die();
        } else if($action=='withdraw') {
                $amount=stripslashes($_POST['amount']);
                $result=user_withdraw($user_uid,$amount);
                if($result) $message="send_successfull";
                else $message="send_failed";
        } else if($action=='user_change_settings') {
                $mail=stripslashes($_POST['mail']);
                $withdraw_address=stripslashes($_POST['withdraw_address']);
                $password=stripslashes($_POST['password']);
                $new_password1=stripslashes($_POST['new_password1']);
                $new_password2=stripslashes($_POST['new_password2']);

                $message=user_change_settings($user_uid,$mail,$withdraw_address,$password,$new_password1,$new_password2);
        } else if($action=='admin_change_settings' && is_admin($user_uid)) {
                $login_enabled=stripslashes($_POST['login_enabled']);
                $payouts_enabled=stripslashes($_POST['payouts_enabled']);
                $info=stripslashes($_POST['info']);
                $global_message=stripslashes($_POST['global_message']);
                $message=admin_change_settings($login_enabled,$payouts_enabled,$info,$global_message);
        }
        if(isset($message) && $message!='') setcookie("message",$message);
        header("Location: ./");
        die();
}

if(isset($_GET['ajax']) && isset($_GET['block'])) {
        if($user_uid) {
                switch($_GET['block']) {
                        case 'address_book':
                                $limit=10000;
                                $form=TRUE;
                                echo html_address_book($user_uid,$token,$form,$limit);
                                break;
                        case 'control':
                                if(is_admin($user_uid)) {
                                        echo html_admin_settings($user_uid,$token);
                                }
                                break;
                        default:
                        case 'free_roll':
                                echo html_free_roll($user_uid,$token);
                                break;
                        case 'minesweeper':
                                echo html_minesweeper($user_uid,$token);
                                break;
                        case 'dice_roll':
                                echo html_dice_game($user_uid,$token);
                                break;
                        case 'last_rolls':
                                echo html_last_rolls($user_uid,$token);
                                break;
                        case 'send_receive':
                                echo html_send_receive($user_uid,$token);
                                break;
                        case 'info':
                                echo html_info();
                                break;
                        case 'log':
                                if(is_admin($user_uid)) {
                                        echo html_log_section_admin();
                                }
                                break;
                        case 'settings':
                                echo html_user_settings($user_uid,$token);
                                break;
                }
        } else {
                switch($_GET['block']) {
                        default:
                        case 'info':
                                echo html_info();
                                break;
                        case 'login':
                                echo html_login_form($token);
                                break;
                        case 'register':
                                echo html_register_form($token);
                                break;
                }
        }
        die();
}

if(isset($_COOKIE['message'])) {
        $message=$_COOKIE['message'];
        setcookie("message","");
} else {
        $message="";
}
echo html_page_begin($wallet_name,$token);
echo html_message_global();
if($user_uid) {
        echo html_logout_form($user_uid,$token);
}
if($message) {
        $lang_message=lang_message($message);
        if($lang_message!='') {
                echo "<p class='message'>$lang_message</p>";
        }
}
echo html_tabs($user_uid);
echo html_loadable_block();

//echo "Session $session\n";
//echo "User uid '$user_uid'\n";
//echo "Token $token\n";

echo html_page_end();

?>