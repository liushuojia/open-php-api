<?php

if( DebugFlag==1 )
{
    # 数据库的链接方式
    $MysqlDefine = array(
        "MysqlHost" => "192.168.1.30",
        "MysqlEditHost" => "192.168.1.30",
        "MysqlDatabase" => "TClassDB",
        "MysqlUser" => "root",
        "MysqlPasswd" => "liushuojia",
    );
} else {
    # 数据库的链接方式
    $MysqlDefine = array(
        "MysqlHost" => "127.0.0.1",
        "MysqlEditHost" => "127.0.0.1",
        "MysqlDatabase" => "TClassDB",
        "MysqlUser" => "root",
        "MysqlPasswd" => "liushuojia",
    );
}
