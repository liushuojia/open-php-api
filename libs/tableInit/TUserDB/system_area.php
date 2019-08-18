<?php

namespace TUserDB\system_area;
//空间命名必须是数据库名称

function init_data($obj){

	if( $obj -> is_delete == "1" ){
		$obj -> status_show = "已删";
	}else{
		if( $obj -> status == "1" ){
			$obj -> status_show = "正常";
		}else{
			$obj -> status_show = "停用";
		}
	}
    $obj -> level_show = (int)( strlen($obj -> area_code) / 4 ) . "级";

    return;
}


function query_string(&$sqlString,$searchKey,$dataObj){

	if( isset($searchKey["search_key"]) && $searchKey["search_key"]!="" ){
        $sqlString .= " and area_name like '%" . $dataObj -> realEscapeString($searchKey["search_key"]) . "%'";
    }

    if( isset($searchKey["area_code_len_min"])
        && is_numeric($searchKey["area_code_len_min"])
        && $searchKey["area_code_len_min"]>0
    ){
        $sqlString .= " and LENGTH(`area_code`) >= '" . $dataObj -> realEscapeString($searchKey["area_code_len_min"]) . "'";
    }

    if( isset($searchKey["area_code_len_max"])
        && is_numeric($searchKey["area_code_len_max"])
        && $searchKey["area_code_len_max"]>0
    ){
        $sqlString .= " and LENGTH(`area_code`) <= '" . $dataObj -> realEscapeString($searchKey["area_code_len_max"]) . "'";
    }

    if( isset($searchKey["area_code_len"])
        && is_numeric($searchKey["area_code_len"])
        && $searchKey["area_code_len"]>0
    ){
        $sqlString .= " and LENGTH(`area_code`) = '" . $dataObj -> realEscapeString($searchKey["area_code_len"]) . "'";
    }

    if( isset($searchKey["area_code_left_like"]) && $searchKey["area_code_left_like"]!="" ){
        $sqlString .= " and area_code like '" . $dataObj -> realEscapeString($searchKey["area_code_left_like"]) . "%'";
    }

    if( isset($searchKey["parent_area_code"]) && $searchKey["parent_area_code"]!="" ){
        $sqlString .= " and area_code like '" . $dataObj -> realEscapeString($searchKey["parent_area_code"]) . "%'";
        $sqlString .= " and LENGTH(`area_code`) = '" . (strlen($searchKey["parent_area_code"]) + systemAreaCodeLength) . "'";
    }


    return;
}

