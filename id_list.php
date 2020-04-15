<?php
/*id_list.php
提供session_id和唯一的6位id之间的转换
以及项目的加入/删除
使用GET方法．
qt:query_type 接受GET传递SESID/6_ID 还是检查cookie中的PHPSESSID    0:check cookie 1:passthrough,可从v取会话id值 传递其他值返回请求不合法
v:value 若qt为1,传递的数值
n:new n=1,若phpsesid不在记录中,则创建一个6_id并返回 默认为0(不创建) 安全起见,qt为1时n=0
_POST:v=session_id 返回数据
标准返回格式:
{f/s,BRIEF_DESCR,content(optional)}
*/

/* define("LIST_FILE_NOT_CREATED", 50);
define("LIST_FILE_OPEN_FAILED", 51);
define("LIST_NOT_FOUND", 52);
define("LIST_REG_FAILED",53);
define("TYPE_SESSID", 0);
define("TYPE_SIXID", 1); *///from sess.php
require("sess.php");
//require_once("stdR.php");
if(isset($_POST['v'])){
$pv=htmlspecialchars($_POST['v']);
$convertSR=getSessionData($pv);
echo convertSRtoString($convertSR);
exit;}
/* print_r($_GET);//debug */
$qt = filter_var(@$_GET['qt'], FILTER_SANITIZE_NUMBER_INT); //可能为空
$n=filter_var(@$_GET['n'], FILTER_SANITIZE_NUMBER_INT);
$type = TYPE_SESSID;
if (!$qt == 1) {
    //qt!=1
    $qt=0;
    session_start();
    $v = session_id();
} else {
    //qt=1
    $n=0;
    $v = htmlspecialchars($_GET['v']);
    if (strlen($v) == 6) {
        $type = TYPE_SIXID; //SIX_ID
    }
}
$qr = query($v,$type); //query return  有哪些是有再处理的必要的吗
if ($qr['stc']==LIST_NOT_FOUND and $n==1){
    $sixid=gen_6_id();
    $rr=reg_6_id($v,$sixid,true);
        echo convertSRtoString($rr);
}elseif($qr['stc']==SUCCESS and $n==1){
    $qr['stc']=LIST_EXIST;
    echo convertSRtoString($qr);
}else{
    echo convertSRtoString($qr);
}
exit();
?>