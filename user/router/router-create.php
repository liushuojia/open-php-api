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
require_once( LIB_PATH . "/include/curl.class.php");

class CRouterCreateApp extends UserApp
{
    public $postArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";

		$postArray = $this -> GetBody();
		foreach ( $postArray as $key => $value){

            $value = trim($value);
            switch ($key) {
                case 'route_name':
                case 'folder':
               // case 'visit_path':
                case 'area_code':
                case 'area_name':
                    if( $value=='' ){
                        return false;
                    }
                    break;
            }

        }

        $this -> postArray = $postArray;
		return true;
	}

    public $DB = array(
        "Router",
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
            "folder" => $this -> postArray[ "folder" ],
            "visit_path" => $this -> postArray[ "visit_path" ],
            "area_code" => $this -> postArray[ "area_code" ],
        );
        if( $searchArray["visit_path"] == "" ) {
            $searchArray["visit_path_is_empty"] = 1;
        }


        $this -> RouterDB -> GetNumData($totalNum, $searchArray);
        if( $totalNum>0 ){
            $this -> showMsg( 400, "您输入的路径已存在,请重新输入" );
            $this -> TCloseMysql();
            return;
        }

        # 创建没有的文件夹 接口模式创建
        $this -> curlSendData( UserDomain . "/routerFolder", $returnData, array(
            "postDataString" => json_encode( array(
                "folder_name" => $this -> postArray[ "folder" ],
            ) ),
        ) );

        $Router = new $this -> RouterDB -> tableItemClass;
        $Router -> create_time = time();
        $Router -> update_time = time();
        $Router -> create_admin_id = $this -> tokenUser -> admin_id;
        $Router -> create_realname = $this -> tokenUser -> realname;
        $Router -> status = 1;
        foreach ($this -> postArray as $key => $val) {
            $Router -> {$key} = $val;
        }

        if( !$this -> RouterDB -> CreateData($Router) ) {
            $this -> showMsg( 422, "创建失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> TCloseMysql();

		$this -> showMsg( 200, '添加成功', array(
			"router_id" => $Router -> router_id,
		));

		return;
	}
}

$App = new CRouterCreateApp;
$App -> RunApp();
return;
