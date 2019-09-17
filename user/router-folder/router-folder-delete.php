<?php
	
/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 DELETE
        可在url里面传递GET的参数

*/
if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CRouterFolderDeleteApp extends UserApp
{
    public $folder_name;
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> folder_name = trim($routeMatchData["params"]["folder_name"]);
        if( $this -> folder_name=='')
            return false;

        return true;
    }
    public $DB = array(
        "RouterFolder",
    );

	function RunApp()
	{
        if(!$this -> CheckInput($ErrMsg)){
            $this -> showMsg( 406, $ErrMsg);
            return;
        }

        if(!$this -> TConnectMysql()){
            $this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
            return ;
        }

        if( !$this -> RouterFolderDB -> DeleteData( array(
            'folder_name' => $this -> folder_name,
        )) ){
            $this -> showMsg( 422, "删除失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> showMsg( 200, "删除成功");
		return;
	}
}

$App = new CRouterFolderDeleteApp;
$App -> RunApp();
return;
