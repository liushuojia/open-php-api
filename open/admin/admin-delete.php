<?php
	
/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 DELETE
        可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CAdminDeleteApp extends ApiApp
{
    public $admin_id;
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> admin_id = (int) ($routeMatchData["params"]["admin_id"]);
        if( $this -> admin_id<=0 )
            return false;

        return true;
    }
    public $DB = array(
        "admin",
    );

	function RunApp()
	{
        if(!$this -> CheckInput($ErrMsg)){
            $this -> showMsg( 406, $ErrMsg);
            return;
        }

        if( !$this -> adminDB -> UpdateDataQuickEditMore(array(
            "is_delete" => 1,
        ), array(
            'admin_id' => $this -> admin_id,
        )) ){
            $this -> showMsg( 422, "更新数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        // 删除用户缓存数据, 用户在获取数据时候会自动重新生成数据
        $this -> clearAdminRedis($this -> admin_id);

        $this -> showMsg( 200, "删除成功");
		return;
	}
}

$App = new CAdminDeleteApp;
$App -> RunApp();
return;
