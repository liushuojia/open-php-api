<?php

//路由匹配结果, 做一个全局变量
$routeMatchData = array();
$routeArray = array();

/*范例增加admin
	访问地址  /v1/admin/
		get  		/v1/admin		多条记录 		
		post   		/v1/admin		创建一条记录/或多条记录 		
		put  [可选] 	/v1/admin		更改多条记录 		

		get			/v1/admin/1		一条记录
		put			/v1/admin/1		更新一条数据 指定id
		delete		/v1/admin/1		删除一条数据


		对应页面传递参数规范请参照范例
*/

//增加一个admin测试模块
// [:str] 	不定长 字符串 (大小写字母 + 数字 + 下划线"_" )
// [#str] 	不定长 数字
// [@str] 	不定长 字符串 (大小写字母 + 数字 + 下划线"_" + 中划线 "-" )
//
//             str 为匹配到的字符串的key名
//
        
// 分类所属
$routeArray["system-area-type"] = array(
    '' => array(
        "GET" => "/system-area/system-area-type-list.php",
        "POST" => "/system-area/system-area-type-create.php",
    ),
    'cache' => array(
        //数据缓存, 提供获取缓存数据及删除缓存
        "GET" => "/system-area/system-area-type-list-cache.php",
        "DELETE" => "/system-area/system-area-type-list-cache-delete.php",
    ),
    '[#type_id]' => array(
        "PUT" => "/system-area/system-area-type-update.php",
        "DELETE" => "/system-area/system-area-type-delete.php",
    ),
);

// 分类管理
$routeArray["system-area"] = array(
    '' => array(
        "GET" => "/system-area/system-area-list.php",
        "POST" => "/system-area/system-area-create.php",
    ),
    'sort' => array(
        "PUT" => "/system-area/system-area-list-sort-update.php",
    ),

    'cache' => array(
        "DELETE" => "/system-area/system-area-list-cache-delete.php",
    ),
    'cache/[#type_id]' => array(
        "GET" => "/system-area/system-area-list-cache.php",
        "DELETE" => "/system-area/system-area-list-cache-delete.php",
    ),

    '[#area_id]' => array(
        "GET" => "/system-area/system-area-one.php",
        "PUT" => "/system-area/system-area-update.php",
        "DELETE" => "/system-area/system-area-delete.php",
    ),
);

