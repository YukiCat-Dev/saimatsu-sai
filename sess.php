<?php
/*sess.php 
1.session的重新登陆
2.查询某一session的数据

*/
require_once "stdR.php";
define("LIST_FILE_NOT_CREATED", 50);
define("LIST_FILE_OPEN_FAILED", 51);
define("LIST_NOT_FOUND", 52);
define("LIST_REG_FAILED", 53);
define("LIST_EXIST", 54);
define("TYPE_SESSID", 0);
define("TYPE_SIXID", 1);
define("SESSION_NOT_EXIST", 61);
define("SESSION_OPEN_FAILED", 62);
define("SESSION_LOCKED", 63);
define("LIST_PATH", "id_list");

function getSessionData($sessid) {
    //sess_$sessid
    $filepath = session_save_path();
    $filepath.= "/sess_$sessid";
    if (is_file($filepath)) {
        $file = fopen($filepath, "r");
        if (!$file) { //打不开
            doEcho(SESSION_OPEN_FAILED);
            exit();
        } else {
            $row = fgets($file);
            if ($row == "") {
                doEcho(SESSION_LOCKED);
                exit();
            }
            fclose($file);
            //pos offset
            define("OFFSET_SIX_ID", 7);
            define("OFFSET_START_TIME", 11);
            define("OFFSET_ACCOMPLISH_TIME", 16);
            define("OFFSET_TIME_ZONE", 10);
            //get pos
            $pos_six_id = stripos($row, "six_id");
            $pos_start_time = stripos($row, "start_time");
            $pos_accomplish_time = stripos($row, "accomplish_time");
            $pos_time_zone = stripos($row, "time_zone");
            $eos = strlen($row);

            //get contents
            $six_id = substr($row, ($pos_six_id + OFFSET_SIX_ID), ($pos_start_time - $pos_six_id - OFFSET_SIX_ID));
            $start_time = substr($row, ($pos_start_time + OFFSET_START_TIME), ($pos_accomplish_time - $pos_start_time - OFFSET_START_TIME));
            $accomplish_time = substr($row, ($pos_accomplish_time + OFFSET_ACCOMPLISH_TIME), ($pos_time_zone - $pos_accomplish_time - OFFSET_ACCOMPLISH_TIME));
            $time_zone = substr($row, ($pos_time_zone + OFFSET_TIME_ZONE), ($eos - $pos_time_zone - OFFSET_TIME_ZONE));
            /*    $tmp=array(
                   "six_id"=>$six_id,
                   "start_time"=>$start_time,
                   "accomplish_time"=>$accomplish_time,
                   "time_zone"=>$time_zone
               );
               print_r($tmp);
               addBreak(); */ //debug
            $tmp = array(
                "six_id" => unserialize($six_id),
                "start_time" => unserialize($start_time),
                "accomplish_time" => unserialize($accomplish_time),
                "time_zone" => unserialize($time_zone)
            );
            $json = json_encode($tmp);
            $tmp=generateStandardResponse(SUCCESS, $json, CONTENT_JSON);
            return $tmp;
        }

    } else {
        $tmp = generateStandardResponse(SESSION_NOT_EXIST);
        return $tmp;
    }
}

function relogin($sessid) {
    $re = getSessionData($sessid);
    if ($re['stc'] != SUCCESS) {
        return generateStandardResponse(SESSION_NOT_EXIST);
    } else {
        session_id($sessid);
        session_set_cookie_params(12 * 3600, "/"); //debug
        session_start();
        session_write_close();
        return generateStandardResponse(SUCCESS);
    }
}
//id_list.php
function gen_6_id() {
    $chars = "1234567890"; //length=10
    $randStr = '';
    for ($i = 0; $i < 6; $i++) {
        $randStr.= $chars[mt_rand(0, 9)]; //.=串接
    }
    return $randStr;
}

function reg_6_id($sessid, $six_id, $list_not_found = false) {
    if ($list_not_found == false) {
        $q = query($sessid, TYPE_SESSID);
    } else {
        return do_reg_6_id($sessid, $six_id);
    }
    if ($q['stc'] == SUCCESS) { //已存在
        return generateStandardResponse(LIST_EXIST, $q['c']);
    }
    elseif($q['stc'] == LIST_NOT_FOUND) { //未存在,注册
        return do_reg_6_id($sessid, $six_id);
    } else {
        return $q;
    } //其他情况,透传query的返回 
}

function do_reg_6_id($sessid, $six_id) {
    $list = fopen(LIST_PATH, "a", 1); //指针在文件尾部的写模式
    //fwrite($list,chr(10));       
    if (fputcsv($list, array($sessid, $six_id)) == false) {
        return generateStandardResponse(LIST_REG_FAILED);
    } else {
        return generateStandardResponse(SUCCESS, $six_id);
    }
    fclose($list);
}
/* function unreg_6_id($value,$type){
    if (!file_exists(LIST_PATH)) {
        //文件未创建
        return generateStandardResponse(0, LIST_FILE_NOT_CREATED);
    } else {
        $list = fopen(LIST_PATH, "r+");
    }   
    $key=$type;
    if ($key==TYPE_SESSID) {
    $ans=TYPE_SIXID;
    }else{
        $ans=TYPE_SESSID;
    }
        if (!$list) {
            return generateStandardResponse(0, LIST_FILE_OPEN_FAILED);
        } //防止指针无效
        while (!feof($list)) { //feof死循环可能性微存,可能需要写watchdog
            $row = fgetcsv($list);
            echo "<pre>";
            print_r($row);
            echo "</pre>";//debug
            if ($value == $row[$key]) {
                fclose($list);
                
                return generateStandardResponse(1,0);
            }
            return generateStandardResponse(0, LIST_NOT_FOUND);  
    }
} */

function query($value, $type) {
    if (!file_exists(LIST_PATH)) {
        //文件未创建
        return generateStandardResponse(LIST_FILE_NOT_CREATED);
    } else {
        $list = fopen(LIST_PATH, "r", 1);
    }
    $key = $type;
    if ($key == TYPE_SESSID) {
        $ans = TYPE_SIXID;
    } else {
        $ans = TYPE_SESSID;
    }
    if (!$list) {
        return generateStandardResponse(LIST_FILE_OPEN_FAILED);
    } //防止指针无效
    $queryResult = array();
    while (!feof($list)) { //feof死循环可能性微存,可能需要写watchdog
        $row = fgetcsv($list);
        /* echo "<pre>";
        print_r($row);
        echo "</pre>";//debug */
        if ($value == $row[$key]) {
            //echo ftell($list);
            array_push($queryResult, $row[$ans]);
        }

    }
    fclose($list);
    if ($queryResult[0]!=null) {
        $json=json_encode($queryResult);
        return generateStandardResponse(SUCCESS,$json,CONTENT_JSON);
    } else {
        return generateStandardResponse(LIST_NOT_FOUND);
    }
} ?>