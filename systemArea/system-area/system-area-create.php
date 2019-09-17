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
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CSystemAreaTypeCreateApp extends ApiApp
{
    public $editArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";

        $postArray = $this -> GetBody();

        foreach ( $postArray as $key => $val ){
            $val = trim($val);
            switch ( $key ){
                case "area_name":
                    if( $val=="" )
                        return false;
                    $this -> editArray[$key] = $val;
                    break;
                case "area_type":
                    $val = (int)($val);
                    if($val<=0)
                        return false;
                    $this -> editArray[$key] = $val;
                    break;
                case "parent_area_code":
                    if(strlen($val)>12){
                        $ErrMsg = "参数传递错误, 层级最多四级";
                        return false;
                    }
                    $this -> editArray[$key] = $val;
                    break;
            }
        }
        if( count($this -> editArray)==0 )
            return false;


        return true;
	}

    public $DB = array(
        "SystemArea",
        "SystemAreaType",
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

        if( !$this -> SystemAreaTypeDB -> SelectOneData($system_area_type, array(
            "type_id" => $this -> editArray["area_type"],
            "status" => 1,
            "is_delete" => 0,
        )) ){
            $this -> showMsg( 406, "分类类型不存在 或已停用");
            $this -> TCloseMysql();
            return;
        }

        if( !$this -> SystemAreaDB -> SelectOneData($system_area, array(
            "type_id" => $this -> editArray["area_type"],
            "area_code" => $this -> editArray["parent_area_code"],
            "status" => 1,
            "is_delete" => 0,
        )) ){
            $this -> showMsg( 406, "分类类型不存在 或已停用");
            $this -> TCloseMysql();
            return;
        }

        $this -> SystemAreaDB -> QueryData($systemAreaArray, 0, 0 ,array(
            "type_id" => $this -> editArray["area_type"],
            "area_code_left_like" => $this -> editArray["parent_area_code"],
            "area_code_len" => strlen($this -> editArray["parent_area_code"])+systemAreaCodeLength,
            "status" => 1,
            "is_delete" => 0,
        ));

        $area_code_array = array();
        foreach( $systemAreaArray as $systemArea ) {
            $area_code_array[ $systemArea -> area_code ] = $systemArea;
        }

        $area_code = "";
        for( $index=1; strlen($index)<=systemAreaCodeLength; $index++ ){
            $area_code_tmp = $this -> editArray["parent_area_code"] . substr( "0000000000000" . $index, -4 );
            if( !array_key_exists($area_code_tmp, $area_code_array) ){
                $area_code = $area_code_tmp;
                break;
            }
        }
        if( $area_code=="" ){
            $this -> showMsg( 406, "分类编码不足, 请联系网站部 每个分类最多9999个子类");
            $this -> TCloseMysql();
            return;
        }
        $this -> editArray["area_code"] = $area_code;

        // 数据准备
        $system_area = new $this -> SystemAreaDB -> tableItemClass;
        $system_area -> area_name = $this -> editArray['area_name'];
        $system_area -> area_type = $system_area_type -> type_id;
        $system_area -> area_type_name = $system_area_type -> type_name;
        $system_area -> area_code = $this -> editArray['area_code'];
        $system_area -> status = 1;
        $system_area -> is_delete = 0;
        $system_area -> create_time = time();
        $system_area -> update_time = time();
        $system_area -> create_admin_id = $this -> tokenUser -> admin_id;
        $system_area -> create_realname = $this -> tokenUser -> realname;
        
        if( !$this -> SystemAreaDB -> CreateData($system_area) ) {
            $this -> showMsg( 422, "创建数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> TCloseMysql();

		$this -> showMsg( 200, '添加成功');
		return;
	}
}

$App = new CSystemAreaTypeCreateApp;
$App -> RunApp();
return;
