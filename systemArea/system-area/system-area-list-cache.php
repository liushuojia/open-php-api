<?php

if( !defined("DOCUMENT_ROOT") ){
    return;
}
require_once( DOCUMENT_ROOT . "/include/systemAraeApp.class.php");

class CSystemAreaTypeListCacheApp extends SystemAraeApp
{
	public $DB = array(
		"SystemArea",
	);
    public $type_id;
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;
        $this -> type_id = (int) ($routeMatchData["params"]["type_id"]);

        if( $this -> type_id<=0 )
            return false;

        return true;
    }

	function RunApp()
	{
        if(!$this -> CheckInput($ErrMsg)){
            $this -> showMsg( 406, $ErrMsg);
            return;
        }

        $RedisDB = new TRedisDB();
        if( !$RedisDB -> exists(redisSystemArea) ) {

            if(!$this -> TConnectMysql()){
                $this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
                return ;
            }

            $searchArray = array(
                "status" => 1,
                "is_delete" => 0,
                "order_by" => array(
                    "area_type" => "asc",
                    "weight" => "desc",
                    "area_code" => "asc",
                ),
            );

            $this -> SystemAreaDB -> QueryData($SystemAreaList, 0, 0, $searchArray );
            $this -> TCloseMysql();

            $tempArray = [];
            foreach ($SystemAreaList as $SystemArea)
            {
                $SystemArea -> level_show = (int)( strlen($SystemArea -> area_code) / 4 ) . "级";
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
                    if(!is_array($SystemAreaList[$tempData4 -> area_type]))
                        $SystemAreaList[$tempData4 -> area_type] = array();

                    $SystemAreaList[$tempData4 -> area_type][] = $tempData4;
                }
            }
            $RedisDB -> set(redisSystemArea, $SystemAreaList,redis_ext_time);
        }

        $retrunData = $RedisDB -> get(redisSystemArea);

        if( ! array_key_exists($this -> type_id, $retrunData) ){
            $this -> showMsg( 406, "参数传递错误");
            return;
        }

        $this -> showMsg( 200,  "OK", array(
            "content" => $retrunData[$this -> type_id],
        ));
		return;
	}
}

$App = new CSystemAreaTypeListCacheApp;
$App -> RunApp();
return;
