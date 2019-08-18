<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件
	
	这里是单个数据的页面, 如需要更多参数可以使用
	GET 或者重新定义路由,在path_match里面拿数据

*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CSystemAreaOneApp extends ApiApp
{
	public $area_id;
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;
		$this -> area_id = (int) ($routeMatchData["params"]["area_id"]);

		if( $this -> area_id<=0 )
		    return false;

		return true;
	}
    public $DB = array(
        "system_area",
        "system_area_type",
    );

	function RunApp()
	{
		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406, $ErrMsg);
			return;
		}

        if( !$this -> system_areaDB -> SelectOneData($systemArea, array(
            "area_id" => $this -> area_id,
        )) ) {
            $this -> showMsg( 404, "查无数据" );
            $this -> TCloseMysql();
            return;
        }

        $parentSystemAreaArray = array();

        $index = 1;
        $area_code = substr( $systemArea -> area_code, 0, ($index++) * systemAreaCodeLength );
        while( $area_code != $systemArea -> area_code ) {
            $this -> system_areaDB -> SelectOneData($systemAreaParent, array(
                "area_code" => $area_code,
                "area_type" => $systemArea -> area_type,
            ));
            $parentSystemAreaArray[] = $systemAreaParent;
            $area_code = substr( $systemArea -> area_code, 0, ($index++) * systemAreaCodeLength );
        }

        $this -> system_areaDB -> QueryData($childrenSystemAreaArray, 0, 0, array(
            "status" => 1,
            "is_delete" => 0,
            "area_type" => $systemArea -> area_type,
            "area_code_left_like" => $systemArea -> area_code,
            "area_code_len" => strlen($systemArea -> area_code) + systemAreaCodeLength,
            "order_by" => array(
                "weight" => "desc",
                "area_code" => "asc",
            ),
        ) );

        $this -> showMsg( 200, "OK", array(
            "systemArea" => $systemArea,
            "parentSystemAreaArray" => $parentSystemAreaArray,
            "childrenSystemAreaArray" => $childrenSystemAreaArray,
        ) );
		return;
	}
}

$App = new CSystemAreaOneApp();
$App -> RunApp();
return;
