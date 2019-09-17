<?php

//
namespace TUserDB\Admin;
//空间命名必须是数据库名称
function init_data($obj){

	if( strlen($obj -> admin_id)<6 )
		$obj -> admin_id_show = substr(  "0000000" . $obj -> admin_id, -6);
	else
		$obj -> admin_id_show = $obj -> admin_id;


	if( $obj -> is_delete == "1" ){
		$obj -> admin_status_show = "已删";
	}else{
		if( $obj -> admin_status == "1" ){
			$obj -> admin_status_show = "正常";
		}else{
			$obj -> admin_status_show = "停用";
		}
	}

	return;
}


function query_string(&$sqlString,$searchKey,$dataObj){

	if( isset($searchKey["search_key"]) && $searchKey["search_key"]!="" ){
		$sqlString .= " and ( 1=2";
		//$sqlString .= " or name_en like '%" . $dataObj -> realEscapeString($searchKey["search_key"]) . "'";
		$sqlString .= " or realname like '%" . $dataObj -> realEscapeString($searchKey["search_key"]) . "%'";
		$sqlString .= " or admin_email like '%" . $dataObj -> realEscapeString($searchKey["search_key"]) . "%'";
		$sqlString .= " or admin_mobile like '%" . $dataObj -> realEscapeString($searchKey["search_key"]) . "%'";
		$sqlString .= ")";
	}

	if( isset($searchKey["admin_id_array"]) && is_array($searchKey["admin_id_array"]) && count($searchKey["admin_id_array"])>0 ){
		$sqlString .= " and admin_id in (" . $dataObj -> realEscapeString( implode(",", $searchKey["admin_id_array"]) ) . ")";
	}

    if( isset($searchKey["not_admin_id"]) && is_numeric($searchKey["not_admin_id"]) && $searchKey["not_admin_id"]>0 ){
        $sqlString .= " and admin_id != '" . $searchKey["not_admin_id"] . "'";
    }

	return;
}

