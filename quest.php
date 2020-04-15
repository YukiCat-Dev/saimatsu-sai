<?php
/*quest.php   description
_GET{
    six_id 返回session中存储的six_id
    有get的时候不处理post
}
_POST{
    m,method 取值范围{0,1,2,3,4}
    q,quest 取值范围{1,2,3,4,5}
    t,tag
    a,answer
}
    m为非数字时，自动设为0.
    m=0.保存进度 q:标记给定的quest为完成（1） a:提供答案字符串以验证是否完成题目 已回答会返回时间戳
    m=1.初始化进度保存   强制初始化：t=1
    m=2.返回进度状况
    m=3.清除进度
    m=4.检查是否完成
    m=5.可以获得指定题目的答案的哈希值  q表示题目 q仅能等于5 哈希值可用算法为hash_algos()的顺序 如md5=2 sha256=5 
    m=6.根据6_id,恢复到已有的会话 t传递6_id
    m=7.注册开始某quest时的时间 已注册会返回时间戳
    else.m为其他值会直接报错

    标准返回格式:(就是个无头json)
    {status_code,content(optional)，contentType(default=CONTENT_PLAIN)}
    status_code{数组详情如下}
    contentType={CONTENT_PLAIN,CONTENT_JSON}
    //version//
    1.2019年11月9日12点09分
    基本功能完成
    2.2019年11月10日00点10分
    加入cookie
    3.2019年11月11日
    a.现在m=3会自动清除客户端存储的cookie b.beautified
    4.2019年11月14日
    新方法m=5
    5.2019年11月15日
    a.现在会初始化6_id b.新版本的标准返回格式 引入stdR.php c.新方法m=6 d.GET six_id? e.引入quest开始时间与完成时间 配套：m=7、新的m=4、删除$_SESSION['progress']
    6.2019年11月18日
    删除了耗时的int_option；更改了第四题的答案；更改了第三题的验证方式，现在仅接受"acgn""ACGN"两个答案
    
    hash_algos();  大于51和小于0的值会被认为是0
    Array
(
    [0] => md2
    [1] => md4
    [2] => md5
    [3] => sha1
    [4] => sha224
    [5] => sha256
    [6] => sha384
    [7] => sha512/224
    [8] => sha512/256
    [9] => sha512
    [10] => sha3-224
    [11] => sha3-256
    [12] => sha3-384
    [13] => sha3-512
    [14] => ripemd128
    [15] => ripemd160
    [16] => ripemd256
    [17] => ripemd320
    [18] => whirlpool
    [19] => tiger128,3
    [20] => tiger160,3
    [21] => tiger192,3
    [22] => tiger128,4
    [23] => tiger160,4
    [24] => tiger192,4
    [25] => snefru
    [26] => snefru256
    [27] => gost
    [28] => gost-crypto
    [29] => adler32
    [30] => crc32
    [31] => crc32b
    [32] => fnv132
    [33] => fnv1a32
    [34] => fnv164
    [35] => fnv1a64
    [36] => joaat
    [37] => haval128,3
    [38] => haval160,3
    [39] => haval192,3
    [40] => haval224,3
    [41] => haval256,3
    [42] => haval128,4
    [43] => haval160,4
    [44] => haval192,4
    [45] => haval224,4
    [46] => haval256,4
    [47] => haval128,5
    [48] => haval160,5
    [49] => haval192,5
    [50] => haval224,5
    [51] => haval256,5
)
*/
session_set_cookie_params(12 * 3600, "/"); //debug
//enum
define("Empty", 0);

define("ERR_METHOD_NOT_EXIST", 10);
define("ERR_QUEST",11);
define("ERR_T",12);
/* define("CONTENT_PLAIN", 20);
define("CONTENT_JSON", 21); *///stdR.php contentType
define("PROGRESS_NOT_INIT", 31);
define("PROGRESS_INITED", 32);
define("QUEST_RIGHT_ANSWER",41);
define("QUEST_WRONG_ANSWER",42);
define("QUEST_HAS_BEEN_ACCOMPLISHED", 43);
define("QUEST_INVALID", 44);
define("QUEST_START_TIME_REGISTERED", 45);
/* define("LIST_FILE_NOT_CREATED", 50);
define("LIST_FILE_OPEN_FAILED", 51);
define("LIST_NOT_FOUND", 52);
define("LIST_REG_FAILED",53);
define("LIST_EXIST",54); sess.php */
//define("SUCCESS",1);stdR.php
//end enum
/* print("<pre>"); 
 print_r($_POST);
 print("</pre>");//debug*/
require("stdR.php");
//GET
if(isset($_GET['six_id'])){
    session_start();
    if(isset($_SESSION['six_id'])){
        doEcho(SUCCESS,$_SESSION['six_id']);
        exit();
    }else{
        doEcho(PROGRESS_NOT_INIT);
            exit();
    }
}
//POST
$method = filter_var(@$_POST['m'], FILTER_SANITIZE_NUMBER_INT); //probably empty
if (!is_numeric($method)) { //if empty then set default
    $method = 0; //设置默认值
}
switch ($method) {
    case 0:
        session_start();        
        if (isset($_SESSION['six_id'])) { //检查是否已创建progress
            date_default_timezone_set($_SESSION['time_zone']);
            //审查post
            $questToMark = filter_var(@$_POST['q'], FILTER_SANITIZE_NUMBER_INT);
            $answer = substr(@$_POST['a'], 0, 10); //限定答案在十个字符内
            $answer = htmlspecialchars($answer, ENT_QUOTES);
            //filter end
            if ($questToMark<=5 and $questToMark>=1) { //检查$questToMark是否在1-5的范围内
                //合法，生成$pre
                $pre = "quest_$questToMark";
                //检查申请完成quest，所提供的答案($answer)是否正确
                if (if_quest_accomplish_valid($questToMark, $answer)) { //答案正确
                    $_SESSION['accomplish_time'][$pre]=time();                
                    doEcho(QUEST_RIGHT_ANSWER);                    
                    exit();
                } else { //答案不正确
                    //前面已经处理过了.
                    exit();
                }
            } else { //$questToMark不合法
                doEcho(ERR_QUEST);
                exit();
            }
        } else {
            doEcho(PROGRESS_NOT_INIT);
            exit();
        }
    case 1: //init
        session_start();
            if (isset($_SESSION['six_id'])) { //检查是否已创建progress
                $forced = filter_var(@$_POST['t'], FILTER_SANITIZE_NUMBER_INT); //read 'forced' from _POST['t']
                if ($forced == 1) {
                    $tmp=do_init();
                    if($tmp['stc']==SUCCESS){
                        echo convertSRtoString($tmp);
                    }
                    exit();
                } else {
                    doEcho(PROGRESS_INITED);
                    exit();
                }
            } else {
                $tmp=do_init();
                if($tmp['stc']==SUCCESS){
                    echo convertSRtoString($tmp);
                }
                exit();
            }
    case 2: //return status
        session_start();
        $pre = json_encode($_SESSION);
        doEcho(SUCCESS,$pre,CONTENT_JSON);
        exit();
    case 3: //clear status 
        session_start();
        $name = session_name();
        if (isset($_COOKIE[$name])) {
            $r = session_get_cookie_params();
            setcookie($name, '', time() - 3600, $r['path'], $r['domain'], $r['secure'], $r['httponly']);
                }
        session_destroy(); 
        doEcho(SUCCESS); 
        exit();
    case 4: //check if completed
        session_start();
        if (isset($_SESSION['six_id'])) {
        for($complete=1;$complete<=5;$complete++){
            if($_SESSION['accomplish_time']["quest_$complete"]==0){
            break;
            }
            continue;
        }//若整个循环成功跑完，那么$complete=6
            if ($complete==6) {
                doEcho(SUCCESS,1, CONTENT_PLAIN);
            } else {
                doEcho(SUCCESS,0, CONTENT_PLAIN);
            }
            exit();
        } else {
            doEcho(PROGRESS_NOT_INIT);
            exit();
        }

            /*case 5:
                $q=$_POST['q'];
                $_SESSION['progress']["quest_$q"]=$_POST['t'];
                exit();*/ //debug 为quest设定特定值
    case 5:
            //审查post
            $quest = filter_var(@$_POST['q'], FILTER_SANITIZE_NUMBER_INT);
            //filter
            $hashalgos=hash_algos();
            $ha_options = array(
                "options" => array(
                    "min_range" => 0,
                    "max_range" => (count($hashalgos)-1)
                )
            );
            //filter end
            if ($questToMark<=5 and $questToMark>=1) { //检查$quest是否在1-5的范围内
                switch ($quest) {//按题目返回答案
                    case 5://11037
                        $hashalgo=filter_var(@$_POST['t'], FILTER_SANITIZE_NUMBER_INT);
                        $hashalgo=filter_var($hashalgo, FILTER_VALIDATE_INT, $ha_options);                       
                        $tmp=hash($hashalgos[$hashalgo],"11037");
                        doEcho(1,$tmp);
                        exit();
                    default: //虽然大概不可能出现但还是写一下
                        doEcho(QUEST_INVALID, "quest_$quest:invalid quest for this method");
                        exit();
                }
            }else{ //$quest不合法
                doEcho(ERR_QUEST);exit();
            }
        case 6://根据6_id恢复会话
            $sixid=filter_var(@$_POST['t'], FILTER_SANITIZE_NUMBER_INT);
            if(strlen($sixid)==6){
            require "sess.php";
            $tmp=query($sixid,TYPE_SIXID);
            $jsonde=json_decode($tmp['c']);
                if($tmp['stc']==SUCCESS){
                    $sessid=$jsonde[0];
                    $reloginre=relogin($sessid);
                    if($reloginre['stc']!=SUCCESS){
                        echo convertSRtoString($reloginre);
                        exit;
                    }
                    doEcho(SUCCESS);
                    exit();
                }else{
                    echo convertSRtoString($tmp);
                    exit();
                }
            }else{
                doEcho(ERR_T);
                exit();
            }
        case 7://注册开始某quest时的时间
            session_start();
        if (isset($_SESSION['six_id'])) { //检查是否已创建progress
            date_default_timezone_set($_SESSION['time_zone']);
            $questToMark = filter_var(@$_POST['q'], FILTER_SANITIZE_NUMBER_INT);
            //filter end
            if ($questToMark<=5 and $questToMark>=1) { //检查$questToMark是否在1-5的范围内
                 //合法，生成$pre
                 $pre = "quest_$questToMark";
                 if($_SESSION['start_time'][$pre]==0){
                    $_SESSION['start_time'][$pre]=time();
                    doEcho(SUCCESS);
                    exit();
                 }else{
                     $tmp="$pre:";
                     $tmp.=$_SESSION['start_time'][$pre];
                     doEcho(QUEST_START_TIME_REGISTERED,$tmp);
                     exit();
                 }
            }else{//$questToMark不合法
                doEcho(ERR_QUEST);
                exit();}
        }else{
            doEcho(PROGRESS_NOT_INIT);
            exit();
        }
    default: //直接报错
    doEcho(ERR_METHOD_NOT_EXIST); exit();
    }

                    function do_init() {                       
                        require_once("sess.php");
                        $v=session_id();
                        $sixid=gen_6_id();
                        $tmp=reg_6_id($v,$sixid);
                        if($tmp['stc']==SUCCESS){
                            setcookie("six_id",$sixid,time()+3600); 
                            $_SESSION['six_id']=$sixid;                                      
                            $_SESSION['start_time'] = array(  //包含五个题目的数组，启动时间（默认为UTC+8）
                                "quest_1" => 0,
                                "quest_2" => 0,
                                "quest_3" => 0,
                                "quest_4" => 0,
                                "quest_5" => 0
                            );
                            $_SESSION['accomplish_time'] = array( //包含五个题目的数组，完成时间（默认为UTC+8）
                                "quest_1" => 0,
                                "quest_2" => 0,
                                "quest_3" => 0,
                                "quest_4" => 0,
                                "quest_5" => 0
                            );
                            $_SESSION['time_zone']=date_default_timezone_get();
                        }else if($tmp['stc']==LIST_EXIST){
                            $jsonde=json_decode($tmp['c']);
                            setcookie("six_id",$jsonde[0],time()+3600); 
                            $_SESSION['six_id']=$jsonde[0];                                      
                            $_SESSION['start_time'] = array(  //包含五个题目的数组，启动时间（默认为UTC+8）
                                "quest_1" => 0,
                                "quest_2" => 0,
                                "quest_3" => 0,
                                "quest_4" => 0,
                                "quest_5" => 0
                            );
                            $_SESSION['accomplish_time'] = array( //包含五个题目的数组，完成时间（默认为UTC+8）
                                "quest_1" => 0,
                                "quest_2" => 0,
                                "quest_3" => 0,
                                "quest_4" => 0,
                                "quest_5" => 0
                            );
                            $_SESSION['time_zone']=date_default_timezone_get();
                            $tmp['stc']=SUCCESS;
                        }
                        echo convertSRtoString($tmp);
                    }
                    function if_quest_accomplish_valid($quest_check, $answer_check) {
                        $self = "if_quest_accomplish_valid";
                        $tmp_return = false;
                        //先检查变量状态.
                        /*echo "quest_check:$quest_check";
                        addBreak();
                        echo "answer_check:$answer_check"; */ //debug
                        if($_SESSION['accomplish_time']["quest_$quest_check"]==0) { //未完成
                                //检查前面题目是否已完成

                                //检查各答案正确性
                                switch ($quest_check) {
                                    case 1://quest_1
                                        if ($answer_check == "岁末祭"
                                            or $answer_check == "歳末祭"
                                            or $answer_check == "歲末祭") {
                                            $tmp_return = true;
                                        }
                                        break;
                                    case 2://quest_2
                                        if ($answer_check == "2887") {
                                            $tmp_return = true;
                                        }
                                        break;
                                    case 3://quest_3
                                        //acgn
                                        if ($answer_check=="acgn" or $answer_check=="ACGN") { 
                                            $tmp_return = true;
                                        }
                                        break;
                                    case 4://quest_4
                                        //旋转题
                                        if ($answer_check == "423") { //temp
                                            $tmp_return = true;
                                        }
                                        break;
                                    case 5://quest_5
                                        if ($answer_check == "11037") {
                                            $tmp_return = true;
                                        }
                                        break;
                                    default: //虽然大概不可能出现但还是写一下
                                        doEcho(QUEST_INVALID, "quest_$quest_check:invalid quest");
                                        return false;
                                }
                                if (!$tmp_return) {
                                    doEcho(QUEST_WRONG_ANSWER, "quest_$quest_check:invalid answer:$answer_check");
                                }
                                return $tmp_return;
                            }else{
                                $t = $_SESSION['accomplish_time']["quest_$quest_check"];
                                
                                doEcho(QUEST_HAS_BEEN_ACCOMPLISHED, "quest_$quest_check:$t");
                                return false;
                        }
                    }
                    ?>