<?php
date_default_timezone_set('PRC');//时区设置 设置为 中国第八区时间
date_default_timezone_set ( "Asia/Shanghai" );

echo $modified_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
if (strtotime($modified_time) + 3600 > time()) {
    header("http/1.1 304");
    exit(0);
}
header("Last-Modified:".gmdate("D, d M Y H:i:s")." GMT");
header("Expires:".gmdate("D, d M Y H:i:s",time()+3600)." GMT");
header("Cache-Control: max-age=3600");
echo 'test';
