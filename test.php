<?php
echo session_save_path();
echo 1;
/* require("stdR.php");
define("LIST_FILE_NOT_CREATED", 50);
define("LIST_FILE_OPEN_FAILED", 51);
define("LIST_NOT_FOUND", 52);

define("TYPE_SESSID", 0);
define("TYPE_FIVEID", 1);
echo "<pre>";
$type=TYPE_SESSID;
$v = htmlspecialchars($_GET['v']);
if (strlen($v) == 5) {
    $type = TYPE_FIVEID; //FIVE_ID
}
$a="e:";
$a=e($v,$type);
echo convertSRtoString($a);
addBreak();
function e($value,$type){
if (!file_exists("id_list")) {
    //文件未创建
    return generateStandardResponse(0, LIST_FILE_NOT_CREATED);
} else {
    $list = fopen("id_list", "r");
}  
$key=$type;
if ($key==TYPE_SESSID) {
$ans=TYPE_FIVEID;
}else{
    $ans=TYPE_SESSID;
}
if (!$list) {
    return generateStandardResponse(0, LIST_FILE_OPEN_FAILED);
} //防止指针无效
while (!feof($list)) { //feof死循环可能性微存,可能需要写watchdog
    $row = fgetcsv($list);
echo "row:";
    print_r($row);
addBreak();
    if ($value == $row[$key]) {
        echo "ftell after finded:";
        echo ftell($list);
        addBreak();
        //fseek($list,-1,SEEK_CUR);
        do {
        fseek($list,-2,SEEK_CUR);
        echo ftell($list);
        $c=fgetc($list);
        echo " ";
        echo ord($c);
        echo " ";
        echo $c;
        addBreak();
        }while ($c!=chr(10));
        for ($i=0;$i<=8;$i++){
        fwrite($list,chr(0));
        }
        fclose($list);
        
        return generateStandardResponse(1, CONTENT_PLAIN, $row[$ans]);
    }
    
}return generateStandardResponse(0, LIST_NOT_FOUND);  
}
echo "</pre>";  */
?>