<?php

/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 PUT
        可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaTypeUpdateApp extends SystemAraeApp
{
    public $editArray = array();
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";

        $postArray = $this -> GetBody();
        foreach ( $postArray as $data ){
            $data["area_id"] = (int)($data["area_id"]);
            if( $data["area_id"] > 0 ) {
                $this -> editArray[] = array(
                    "area_id" => $data["area_id"],
                    "weight" => $data["weight"],
                );
            }
        }
        if( count($this -> editArray) == 0 )
            return false;

        return true;
    }

    public $DB = array(
        "system_area",
    );

	function RunApp()
	{
        if(!$this -> CheckInput($ErrMsg)) {
            $this->showMsg(406, $ErrMsg);
            return;
        }

        if(!$this -> TConnectMysql()){
            $this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
            return ;
        }

        foreach( $this -> editArray as $data ) {
            $editArray = array(
                "update_time" => time(),
                "weight" => $data["weight"],
            );
            $searchArray = array(
                "area_id" => $data["area_id"],
            );
            $this -> system_areaDB -> UpdateDataQuickEditMore($editArray, $searchArray);
        }
        $this -> showMsg( 200, "排序成功");
		return;
	}
}

$App = new CSystemAreaTypeUpdateApp;
$App -> RunApp();
return;
