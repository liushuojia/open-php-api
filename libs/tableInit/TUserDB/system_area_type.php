<?php

namespace TUserDB\system_area_type;
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

	return;
}


function query_string(&$sqlString,$searchKey,$dataObj){

	if( isset($searchKey["search_key"]) && $searchKey["search_key"]!="" ){
		$sqlString .= " and type_name like '%" . $dataObj -> realEscapeString($searchKey["search_key"]) . "%'";
	}


	return;
}

