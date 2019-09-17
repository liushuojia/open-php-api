<?php

/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 PUT
        可在url里面传递GET的参数

*/
if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaTypeUpdateApp extends SystemAraeApp
{
    public $type_id;
    public $editArray = array();
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> type_id = (int) ($routeMatchData["params"]["type_id"]);
        if( $this -> type_id<=0 )
            return false;

        $postArray = $this -> GetBody();
        foreach ( $postArray as $key => $val ){
            $val = trim($val);
            switch ( $key ){
                case "type_name":
                    if( $val=="" )
                        return false;

                    $this -> editArray[$key] = $val;
                    break;
                case "status":
                case "is_delete":
                case "weight":
                    $this -> editArray[$key] = (int) ( $val );
                    break;
            }
        }
        if( count($this -> editArray)==0 )
            return false;

        return true;
    }

    public $DB = array(
        "SystemAreaType",
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

        $this -> editArray["update_time"] = time();
        if( !$this -> SystemAreaTypeDB -> UpdateDataQuickEditMore($this -> editArray, array(
            'type_id' => $this -> type_id,
        )) ){
            $this -> showMsg( 422, "更新数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> showMsg( 200, "更新成功");
		return;
	}
}

$App = new CSystemAreaTypeUpdateApp;
$App -> RunApp();
return;
