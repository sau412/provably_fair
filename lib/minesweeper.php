<?php

$mines_count=16;
$field_size_x=16;
$field_size_y=16;

function field_get($field,$x,$y) {
        global $field_size_x;
        global $field_size_y;

        if(is_numeric($x) && $x>=0 && $x<$field_size_x && is_numeric($y) && $y>=0 && $y<$field_size_y) {
                return $field[$x+$y*$field_size_x];
        }
        return NULL;
}

function field_set($field,$x,$y,$value) {
        global $field_size_x;
        global $field_size_y;
        if(is_numeric($x) && $x>=0 && $x<$field_size_x && is_numeric($y) && $y>=0 && $y<$field_size_y) {
                $field[$x+$y*$field_size_x]=$value;
        }
        return $field;
}

function generate_field($server_seed) {
        global $mines_count;
        global $field_size_x;
        global $field_size_y;

        $field=str_repeat(" ",$field_size_x*$field_size_y);
        for($y=0;$y!=$field_size_y;$y++) {
                for($x=0;$x!=$field_size_x;$x++) {
                        // c - closed cell
                        $field=field_set($field,$x,$y,"c");
                }
        }
        for($i=0;$i!=16;$i++) {
                $x=hexdec($server_seed[$i*2]);
                $y=hexdec($server_seed[$i*2+1]);
                // b - bomb cell
                $field=field_set($field,$x,$y,"b");
        }
        return $field;
}

function is_possible_action($action_data,$field) {
        global $field_size_x;
        global $field_size_y;

        $x=$action_data->x;
        $y=$action_data->y;
        $action=$action_data['action'];

        // Valid coordinates
        if(is_numeric($x) && $x>=0 && $x<=$field_size_x && is_numeric($y) && $y>=0 && $y<=$field_size_y) {
                // Valid move only if not opened yet
                if(field_get($field,$x,$y)!="o")
                        return TRUE;
        }
        return FALSE;
}

function check_win($field) {
        global $field_size_x;
        global $field_size_y;

        // Win when no unopened tiles without bombs
        for($y=0;$y!=$field_size_y;$y++) {
                for($x=0;$x!=$field_size_x;$x++) {
                        if(field_get($field,$x,$y) == "c") {
                                return FALSE;
                        }
                }
        }
        return TRUE;
}

function open_cell($field,$x,$y) {
        $num=0;
        if(field_get($field,$x,$y)!="c") return $field;
        if(field_get($field,$x-1,$y-1)=="b") $num++;
        if(field_get($field,$x-1,$y  )=="b") $num++;
        if(field_get($field,$x-1,$y+1)=="b") $num++;
        if(field_get($field,$x  ,$y-1)=="b") $num++;
        if(field_get($field,$x  ,$y+1)=="b") $num++;
        if(field_get($field,$x+1,$y-1)=="b") $num++;
        if(field_get($field,$x+1,$y  )=="b") $num++;
        if(field_get($field,$x+1,$y+1)=="b") $num++;
        $field=field_set($field,$x,$y,"$num");
        if($num==0) {
                $field=open_cell($field,$x-1,$y-1);
                $field=open_cell($field,$x-1,$y  );
                $field=open_cell($field,$x-1,$y+1);
                $field=open_cell($field,$x  ,$y-1);
                $field=open_cell($field,$x  ,$y+1);
                $field=open_cell($field,$x+1,$y-1);
                $field=open_cell($field,$x+1,$y  );
                $field=open_cell($field,$x+1,$y+1);
        }
        return $field;
}

function apply_action($field,$action_data) {
        global $field_size_x;
        global $field_size_y;

        $x=$action_data['x'];
        $y=$action_data['y'];
        $action=$action_data['action'];

        if($action=="open") {
                if(field_get($field,$x,$y) == "b") {
                        return array("result"=>"lose","field"=>$field);
                }
                // o - opened cell
                $field=open_cell($field,$x,$y);
                if(check_win($field)) {
                        return array("result"=>"win","field"=>$field);
                }
        }
        return array("result"=>"continue","field"=>$field);
}

function new_game($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $exists_uid=db_query_to_variable("SELECT `uid` FROM `minesweeper` WHERE `user_uid`='$user_uid_escaped' AND `is_finished`=0");
        if($exists_uid==NULL) {
                $server_seed=get_server_seed($user_uid);
                $user_seed=get_server_seed($user_uid);
                $hash=hash("sha256","$server_seed.$user_seed");
                $field=generate_field($hash);
                $field_escaped=db_escape($field);
                $user_seed_escaped=db_escape($user_seed);
                $server_seed_escaped=db_escape($server_seed);
                db_query("INSERT INTO `minesweeper` (`user_uid`,`server_seed`,`user_seed`,`state`,`actions`) VALUES ('$user_uid_escaped','$server_seed_escaped','$user_seed_escaped','$field_escaped','start')");
                $exists_uid=mysql_insert_id();
                regen_server_seed($user_uid);
        }
        return $exists_uid;
}

function save_field($game_uid,$field,$action_data) {
        $field_escaped=db_escape($field);
        $game_uid_escaped=db_escape($game_uid);
        $x=$action_data['x'];
        $y=$action_data['y'];
        $action=$action_data['action'];
        $action_log="x${x}y${y}$action";
        $action_log_escaped=db_escape($action_log);
        db_query("UPDATE `minesweeper` SET `state`='$field_escaped',`actions`=CONCAT(`actions`,' $action_log') WHERE `uid`='$game_uid_escaped'");
}

function load_field($game_uid) {
        $game_uid_escaped=db_escape($game_uid);
        return db_query_to_variable("SELECT `state` FROM `minesweeper` WHERE `uid`='$game_uid_escaped'");
}

function get_current_game_hash($game_uid) {
        $game_uid_escaped=db_escape($game_uid);
        $server_seed=db_query_to_variable("SELECT `server_seed` FROM `minesweeper` WHERE `uid`='$game_uid_escaped'");
        $server_seed_hash=hash("sha256",$server_seed);
        return $server_seed_hash;
}

function finish($game_uid, $result) {
        $game_uid_escaped=db_escape($game_uid);
        if($result == "win") {
                db_query("UPDATE `minesweeper` SET `is_finished`=1,`profit`=0.01 WHERE `uid`='$game_uid_escaped'");
                $user_uid=db_query_to_variable("SELECT `user_uid` FROM `minesweeper` WHERE `uid`='$game_uid_escaped'");
                change_user_balance($user_uid, 0.01);
        }
        else {
                db_query("UPDATE `minesweeper` SET `is_finished`=1,`profit`=0 WHERE `uid`='$game_uid_escaped'");
        }
}

function to_user_field($field) {
        return str_replace("b","c",$field);
}
