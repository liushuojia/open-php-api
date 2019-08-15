<?php
/*
	该文件是在 /route/index.php 根目录包含执行, 所以该文件

		方法 POST
		传递参数 json 
		可在url里面传递GET的参数

*/

require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CSystemAreaCreateApp extends ApiApp
{
    public $editArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
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
        "system_area_type",
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

        $system_area_type = new $this -> system_area_typeDB -> tableItemClass;
        $system_area_type -> type_name = $this -> editArray['type_name'];
        $system_area_type -> status = $this -> editArray['status'];
        $system_area_type -> is_delete = $this -> editArray['is_delete'];
        $system_area_type -> weight = $this -> editArray['weight'];

        if( !$this -> system_area_typeDB -> CreateData($system_area_type) ) {
            $this -> showMsg( 422, "创建数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> TCloseMysql();

		$this -> showMsg( 200, '添加成功');
		return;
	}
}

$App = new CSystemAreaCreateApp;
$App -> RunApp();
return;
