<?php

/*
	路由类 这里只是实现路由功能
*/

$routeArray = array();
$routeMatchData = array();

class routeApp{

    // api版本号 必须与ShowApp.class的类一致
    public $prefix_path = "v1";         #访问前缀   api.hotel.com/[prefix_path]/[model]

    public $routeFilePath = "";

	//开启路由功能
	//路由仅仅只是做了文件加载, 不进行任何数据调整
	function notFound(){
		header("HTTP/1.1 404 Not Found");  
		header("Status: 404 Not Found");  
		exit();
	}
    public function errorAjax($msg, $array = array())
    {
        $array = array(
            "flag" => 1,
            "msg" => $msg,
            "data" => $array,
        );
        echo json_encode($array, true);
        return;
    }

	function RouteAction()
	{
        $this -> routeFilePath = DOCUMENT_ROOT . "/include/route.php";

        if( !is_file($this -> routeFilePath)) {
	        echo 'route 路由文件错误,请进行设置';
	        return;
        }
        Global $routeArray;
        Global $routeMatchData;

        require_once($this -> routeFilePath);

        if( count($routeArray)==0 ){
            echo '未设置路由数据,请进行设置';
            return;
        }

        $REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
		$PATH_INFO = $_SERVER["PATH_INFO"];
		$tmp = explode("/", $PATH_INFO);

		if( $tmp<3 ){
			$this -> notFound();
			return;
		}

		$prefix_path = trim($tmp[1]);
		$controller = trim($tmp[2]);

		if( $prefix_path !== $this -> prefix_path ){
			$this -> notFound();
			return;
		}

		if( !array_key_exists( $controller, $routeArray) ){
			$this -> notFound();
			return;
		}

		$routeController = $routeArray[$controller];

		if( count($tmp) == 3)
			$tmp[3] = "";

		if( $tmp[count($tmp)-1]=="index.php" )
			$tmp[count($tmp)-1] = "";

		$filePath = "";
		if( count($tmp)==4 ){
		    /*
		     * 判断 /v1/admin  or /v1/admin/1  or /v1/admin/status
		     * 这种路径是否存在,存在则不需要正则判断
		     */
			if( array_key_exists( $tmp[3], $routeController) ){
				$ActionArray = $routeController[ $tmp[3] ];
				if( array_key_exists( $REQUEST_METHOD, $ActionArray ) ){
					$filePath = DOCUMENT_ROOT . $ActionArray[$REQUEST_METHOD];
				}
			}
		}

        //正则匹配到的字段
        $params = array();
        if($filePath ==""){
		    // 正则判断数据是否存在 或 如无正则则判断是否路径是否存在

            $lastPath = "";
            for( $i=3; $i<count($tmp); $i ++ ) {
                $lastPath .= ($lastPath!=""?"/":"");
                $lastPath .= $tmp[$i];
            }

            foreach( $routeController as $key => $val ){
				if( $key=="" ){
					continue;
				}
                preg_match_all( "/\[[:|@|#]([a-z|A-Z|_]+)\]/", $key,$path_match_key );
				if( count($path_match_key[1])>0 ){

                    $key = preg_replace("/\[:[a-z|A-Z|_]+\]/", "([a-z|A-Z|0-9|_]+)", $key);
                    $key = preg_replace("/\[#[a-z|A-Z|_]+\]/", "([0-9]+)", $key);
                    $key = preg_replace("/\[@[a-z|A-Z|_]+\]/", "([a-z|A-Z|0-9|_|-]+)", $key);

                    $key = str_replace("/", "\/", $key);
                    $key = '/^' . $key . '$/';

                    if( preg_match($key, $lastPath, $path_match) ){
                        if( array_key_exists( $REQUEST_METHOD, $val) ){
                            $params = array();
                            foreach( $path_match_key[1] as $index => $valTmp ){
                                $params[$valTmp] = $path_match[$index+1];
                            }
                            $filePath = DOCUMENT_ROOT . $val[$REQUEST_METHOD];
                            break;
                        }
                    }

                }else{
                    if( $key==$lastPath ){
                        if( array_key_exists( $REQUEST_METHOD, $val) ){
                            $filePath = DOCUMENT_ROOT . $val[$REQUEST_METHOD];
                            break;
                        }
				    }
                }
            }
		}

        $routeMatchData[ "REQUEST_METHOD" ] = $REQUEST_METHOD;
        $routeMatchData[ "PATH_INFO" ] = $PATH_INFO;
        $routeMatchData[ "filePath" ] = $filePath;
        $routeMatchData[ "params" ] = $params;

        if( $filePath == "" || !file_exists($filePath) ){

			if( DebugFlag ){
				$errorMsg = ($REQUEST_METHOD . " : " . $PATH_INFO);
                $errorMsg .= ("\n<BR>");
                if( $filePath == "" ) {
                    $errorMsg .= ("没有匹配的路由, 请先添加路由 ") ;
                }else{
                    $errorMsg .= ("匹配到了路由, 但是路由文件未创建, 路由文件地址: ". $filePath) ;
                }
                $this -> errorAjax($errorMsg,$routeMatchData);
				return;
			}else{
				$this -> notFound();
				return;
			}
		}

        include_once $filePath;
		return;
	}

}
