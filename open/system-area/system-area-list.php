<?php

/*
	方法 GET

	该文件是在 /route/index.php 根目录包含执行, 所以该文件

	获取list数据, 使用的是get方式获取数据


*/
require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CSystemAreaListApp extends ApiApp
{
	public $searchArray = array();
	public $returnType = '';
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $routeMatchData;


		if( isset($_GET["parent_area_code"]) && $_GET["parent_area_code"]!="")
            $this -> searchArray["parent_area_code"] = trim($_GET["parent_area_code"]);

		if( isset($_GET["search_key"]) && $_GET["search_key"]!="")
            $this -> searchArray["search_key"] = trim($_GET["search_key"]);

		if( isset($_GET["status"]) && is_numeric($_GET["status"]))
			$this -> searchArray["status"] = (int)($_GET["status"]);

		if( isset($_GET["is_delete"]) && is_numeric($_GET["is_delete"]))
			$this -> searchArray["is_delete"] = (int)($_GET["is_delete"]);

        if( isset($_GET["area_type"]) && is_numeric($_GET["area_type"]))
            $this -> searchArray["area_type"] = (int)($_GET["area_type"]);

        if( isset($_GET["area_code_len_max"]) && is_numeric($_GET["area_code_len_max"]))
            $this -> searchArray["area_code_len_max"] = (int)($_GET["area_code_len_max"]);

        if( isset($_GET["area_code_len"]) && is_numeric($_GET["area_code_len"]))
            $this -> searchArray["area_code_len"] = (int)($_GET["area_code_len"]);

        if( isset($_GET["area_code_len_min"]) && is_numeric($_GET["area_code_len_min"]))
            $this -> searchArray["area_code_len_min"] = (int)($_GET["area_code_len_min"]);

        if( is_array($_GET["order_by"]) && count($_GET["order_by"])>0){
            $this -> searchArray["order_by"] = ($_GET["order_by"]);
        }
        $this -> returnType = trim($_GET["returnType"]);

        $this -> page_id = (int)($_GET["page_id"]);
		$this -> one_page_num = (int)($_GET["one_page_num"]);

		if( $this -> page_id<1 )
			$this -> page_id = 1;

		if( $this -> one_page_num<1 )
			$this -> one_page_num = 30;

		return true;
	}

	public $DB = array(
		"system_area",
    );
	function RunApp()
	{
		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406, $ErrMsg );
			return;
		}

		if(!$this -> TConnectMysql()){
			$this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
			return ;
		}

		$searchArray = $this -> searchArray;

        switch($this -> returnType){
            case 'level':
            case 'excel':
                $StartPos = 0;
                $Num = 0;
                break;
            default:
                $StartPos = ($this -> page_id - 1) * $this -> one_page_num;
                $Num = $this -> one_page_num;
                break;
        }

        $searchArray["order_by"] = array(
            "weight" => "desc",
            "area_code" => "asc",
        );

		$this -> system_areaDB -> QueryData($SystemAreaList, $StartPos, $Num, $searchArray );
		$this -> system_areaDB -> GetNumData($totalNum, $searchArray);

		$this -> TCloseMysql();


        switch($this -> returnType){
            case 'level':
                //层级
                $tempArray = [];
                foreach ($SystemAreaList as $SystemArea)
                {
                    if( !is_array($tempArray[$SystemArea -> area_type]) ){
                        $tempArray[$SystemArea -> area_type] = array();
                    }
                    if( !is_array($tempArray[$SystemArea -> area_type][ strlen($SystemArea -> area_code) ]) ){
                        $tempArray[$SystemArea -> area_type][ strlen($SystemArea -> area_code) ] = array();
                    }

                    $key = substr($SystemArea -> area_code,0,strlen($SystemArea -> area_code)-4);
                    if( $key=="" ) {
                        $tempArray[$SystemArea -> area_type][ strlen($SystemArea -> area_code) ][] = $SystemArea;
                    }else{
                        if( !is_array($tempArray[$SystemArea -> area_type][strlen($SystemArea -> area_code)][$key]) ){
                            $tempArray[$SystemArea -> area_type][strlen($SystemArea -> area_code)][$key] = array();
                        }
                        $tempArray[$SystemArea -> area_type][strlen($SystemArea -> area_code)][$key][$SystemArea -> area_code] = $SystemArea;
                    }

                }

                $SystemAreaList = array();
                foreach( $tempArray as $tenmpData ){
                    foreach( $tenmpData[4] as $tempData4 ){
                        if( is_array( $tenmpData[8][$tempData4 -> area_code] ) && count($tenmpData[8][$tempData4 -> area_code])>0  ){
                            $tempData4 -> children = array();
                            foreach( $tenmpData[8][$tempData4 -> area_code] as $tempData8 ){
                                if( is_array( $tenmpData[12][$tempData8 -> area_code] ) && count($tenmpData[12][$tempData8 -> area_code])>0  ) {
                                    $tempData8 -> children = array();
                                    foreach( $tenmpData[12][$tempData8 -> area_code] as $tempData12 ) {
                                        if( is_array( $tenmpData[16][$tempData12 -> area_code] ) && count($tenmpData[16][$tempData12 -> area_code])>0  ) {
                                            $tempData12 -> children = array();
                                            foreach( $tenmpData[16][$tempData12 -> area_code] as $tempData16 ) {
                                                $tempData12 -> children[] = $tempData16;
                                            }
                                        }
                                        $tempData8 -> children[] = $tempData12;
                                    }
                                }
                                $tempData4 -> children[] = $tempData8;
                            }
                        }
                        $SystemAreaList[] = $tempData4;
                    }
                }
                $this -> showMsg( 200,  "OK", array(
                    "content" => $SystemAreaList,
                    "page" => array(
                        "page_id" => $this -> page_id,
                        "one_page_num" => $totalNum,
                        "total_num" => $totalNum,
                        "total_page" => 1,
                    ),
                ));
                break;
            case 'excel':
                //文件下载

                break;
            default:
                $this -> showMsg( 200,  "OK", array(
                    "content" => $SystemAreaList,
                    "page" => array(
                        "page_id" => $this -> page_id,
                        "one_page_num" => $this -> one_page_num,
                        "total_num" => $totalNum,
                        "total_page" => ceil($totalNum/$this -> one_page_num),
                    ),
                ));
                break;
        }
		return;
	}
}

$App = new CSystemAreaListApp;
$App -> RunApp();
return;
