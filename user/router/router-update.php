<?php

/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 PUT
        可在url里面传递GET的参数

*/
if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CRouterUpdateApp extends UserApp
{
    public $router_id;
    public $editArray = array();
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> router_id = (int) ($routeMatchData["params"]["router_id"]);
        if( $this -> router_id<=0 )
            return false;

        $postArray = $this -> GetBody();
        foreach ( $postArray as $key => $val ){
            $val = trim($val);
            switch ( $key ){
                case "area_code":
                case "area_name":
                case "route_name":
                case "folder":
                    if( $val=="" )
                        return false;

                    $this -> editArray[$key] = $val;
                    break;
                case "visit_path":
                    $this -> editArray[$key] = $val;
                    break;
                case "weight":
                case "status":
                case "is_delete":
                    $this -> editArray[$key] = (int)($val);
                    break;
            }
        }
        if( count($this -> editArray)==0 )
            return false;

        return true;
    }

    public $DB = array(
        "Router",
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

        # 创建没有的文件夹 接口模式创建
        if( isset($this -> editArray[ "folder" ]) && $this -> editArray[ "folder" ]!="" ){
            $this -> curlSendData( UserDomain . "/routerFolder", $returnData, array(
                "postDataString" => json_encode( array(
                    "folder_name" => $this -> editArray[ "folder" ],
                ) ),
            ) ); 
        }


        $this -> editArray["update_time"] = time();
        if( !$this -> RouterDB -> UpdateDataQuickEditMore($this -> editArray, array(
            'router_id' => $this -> router_id,
        )) ){
            $this -> showMsg( 422, "更新数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> showMsg( 200, "更新成功");
		return;
	}
}

$App = new CRouterUpdateApp;
$App -> RunApp();
return;
