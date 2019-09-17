<?php
/*
	该文件是在 /route/index.php 根目录包含执行, 所以该文件

		方法 POST
		传递参数 json 
		可在url里面传递GET的参数

*/

if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CRouterFolderCreateApp extends UserApp
{
    public $postArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";

		$postArray = $this -> GetBody();
		foreach ( $postArray as $key => $value){

            $value = trim($value);
            switch ($key) {
                case 'folder_name':
                    if( $value=='' ){
                        return false;
                    }
                    break;
            }
        }
        $this -> postArray = $postArray;

        if( count( $this -> postArray ) == 0 )
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

        $searchArray = array(
            "folder_name" => $this -> postArray[ "folder_name" ],
        );
        $this -> RouterFolderDB -> GetNumData($totalNum, $searchArray);
        if( $totalNum>0 ){
            $this -> showMsg( 406, '创建失败, 文件夹已经存在');
            $this -> TCloseMysql();
            return;
        }

        $RouterFolder = new $this -> RouterFolderDB -> tableItemClass;
        $RouterFolder -> folder_name = $this -> postArray[ "folder_name" ];

        if( !$this -> RouterFolderDB -> CreateData($RouterFolder) ) {
            $this -> showMsg( 422, "创建失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> TCloseMysql();

		$this -> showMsg( 200, '创建成功');
		return;
	}
}

$App = new CRouterFolderCreateApp;
$App -> RunApp();
return;
