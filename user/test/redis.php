<?php
		//连接本地的 Redis 服务
	 $redis = new Redis();
	 $redis->connect('127.0.0.1', 6379);
	 echo "Connection to server sucessfully";

	 $redis->connect('127.0.0.1', 6379);
	 $redis -> auth("liushuojia");

	 echo "\n<BR>\n";
				 //查看服务是否运行
	 echo "Server is running: " . $redis->ping();


	 echo "\n<BR>\n";

	$arList = $redis->keys("*");
	 echo "Stored keys in redis:: ";
	 foreach ($arList as $value) {
		 # code...
			echo "\n";
			if( stristr( $value , "Queue" )!==false){
				print_r( $value ." => 队列数量 - " );
				print_r( $redis -> lSize($value) );
			}else{
				switch ($value) {
					default:
						print_r( $value ." => " . $redis->get($value) );
						break;
				}
			}
	 }

