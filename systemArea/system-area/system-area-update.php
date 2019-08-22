<?php

/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 PUT
        可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaUpdateApp extends SystemAraeApp
{
    public $area_id;
    public $editArray = array();
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> area_id = (int) ($routeMatchData["params"]["area_id"]);

        if( $this -> area_id<=0 )
            return false;

        $postArray = $this -> GetBody();
        foreach ( $postArray as $key => $val ){
            $val = trim($val);
            switch ( $key ) {
                case "area_name":
                    if( $val=="" )
                        return false;

                    $this -> editArray[$key] = $val;
                    break;
                case "status":
                case "is_delete":
                    $this -> editArray[$key] = (int) ( $val );
                    break;
                case "area_desc":
                case "data_json":
                case "sql_json":
                    $this -> editArray[$key] = $val;
                    break;
            }
        }
        if( count($this -> editArray)==0 )
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

        $this -> editArray["update_time"] = time();
        if( !$this -> system_areaDB -> UpdateDataQuickEditMore($this -> editArray, array(
            'area_id' => $this -> area_id,
        )) ){
            $this -> showMsg( 422, "更新数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> showMsg( 200, "更新成功");
		return;
	}
}

$App = new CSystemAreaUpdateApp;
$App -> RunApp();
return;
