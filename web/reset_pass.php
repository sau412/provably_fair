<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/logger.php");

// Only ASCII parameters allowed
foreach($_GET as $key => $value) {
        if(validate_ascii($key)==FALSE) die("Non-ASCII parameters disabled");
        if(validate_ascii($value)==FALSE) die("Non-ASCII parameters disabled");
}
foreach($_POST as $key => $value) {
        if(validate_ascii($key)==FALSE) die("Non-ASCII parameters disabled");
        if(validate_ascii($value)==FALSE) die("Non-ASCII parameters disabled");
}

db_connect();

$hash=$_GET['hash'];
$hash_escaped=db_escape($hash);
$username=db_query_to_variable("SELECT `login` FROM `users` WHERE `password_hash`='$hash_escaped'");

if($username!='') {
        if(isset($_POST['password'])) {
                $login=stripslashes($_POST['login']);
                $login_escaped=db_escape($login);
                if($login!=$username) die("Invalid username");
                $password=stripslashes($_POST['password']);
                $salt=db_query_to_variable("SELECT `salt` FROM `users` WHERE `login`='$login_escaped'");
                $new_password_hash=hash("sha256",$password.strtolower($username).$salt.$global_salt);
                //$new_password_hash=hash("sha256",$password.strtolower($username).$salt);
                $new_password_hash_escaped=db_escape($new_password_hash);
                db_query("UPDATE `users` SET `password_hash`='$new_password_hash_escaped' WHERE `password_hash`='$hash_escaped'");
                die("Password changed. <a href='./'>Try to log in with new data</a>");
        }
} else {
        die("Unknown token");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Password reset</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="icon" href="favicon.png" type="image/png">
<script src='jquery-3.3.1.min.js'></script>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<h1>Password changer</h1>
<form name=change_pass method=post>
<p>Login: <input type=text name=login></p>
<p>New password: <input type=password name=password></p>
<p><input type=submit value='Change password'></p>
</form>
