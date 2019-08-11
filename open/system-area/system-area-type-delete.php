<?php
	
/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 DELETE
        可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CSystemAreaTypeDeleteApp extends ApiApp
{
    public $type_id;
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> type_id = (int) ($routeMatchData["params"]["type_id"]);
        if( $this -> type_id<=0 )
            return false;

        return true;
    }
    public $DB = array(
        "system_area_type",
    );

	function RunApp()
	{
        if(!$this -> CheckInput($ErrMsg)){
            $this -> showMsg( 406, $ErrMsg);
            return;
        }

        if( !$this -> system_area_typeDB -> UpdateDataQuickEditMore(array(
            "is_delete" => 1,
        ), array(
            'type_id' => $this -> type_id,
        )) ){
            $this -> showMsg( 422, "更新数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }


        $this -> showMsg( 200, "删除成功");
		return;
	}
}

$App = new CSystemAreaTypeDeleteApp();
$App -> RunApp();
return;
