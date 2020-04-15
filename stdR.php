<?php
  /*stdR.php*/
  define("EMPTY", 0);
  define("SUCCESS",1);
  define("CONTENT_PLAIN", 20);
  define("CONTENT_JSON", 21);
  function generateStandardResponse($status_code,$contents="",$contentType=CONTENT_PLAIN){
    if($contents==""){$contentType="";}
    $tmp=array(
        "stc"=>$status_code,
        "c"=>$contents,
        "ct"=>$contentType
    );
    return $tmp;
    }
function convertSRtoString($SRArray){
        $tmp="{";
            $tmp.=$SRArray['stc'];
            $tmp.=",";
            $tmp.=$SRArray['c'];
            $tmp.=",";
            $tmp.=$SRArray['ct'];
            $tmp.="}";
            return $tmp;
    }
    function addBreak() {
        echo "<br>";
    }
    function doEcho($status_code,$contents = "",$contentType=CONTENT_PLAIN){
        if($contents=="" and !is_numeric($contents)){$contentType="";}
        echo "{";
        echo $status_code;
        echo chr(44);
        echo $contents;
        echo chr(44);
        echo "$contentType}";
    }
?>