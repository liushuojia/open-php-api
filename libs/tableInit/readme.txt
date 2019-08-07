readme.txt

这个文件夹是 读取数据库后特殊处理的扩展函数, 每个php类都会在这里寻找包含 数据库.表名.php 的文件,并且在读取数据后, 会执行 


namespace TUserDB\admin;    // 数据库名\表名

function init_data($objData){
    $objData -> admin_balance_show = $objData -> admin_balance / 100;
    if($objData -> admin_role == 0){
        $objData -> admin_role_show = "-";
    }
}

function query_string(&$sqlString,$searchKey,$dataObj ){
    if( isset($searchKey["admin_id_not"]) && trim($searchKey["admin_id_not"])!=""){
        $sqlString .= " and admin_id != '" . $dataObj -> realEscapeString( $searchKey['admin_id_not'] ) . "'";
    }
}



