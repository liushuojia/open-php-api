<?php
/*
	该文件是在 /route/index.php 根目录包含执行, 所以该文件

		方法 POST
		传递参数 json 
		可在url里面传递GET的参数

*/

require_once("../include/config.php");
require_once( OPEN_PATH . "/include/apiApp.class.php");

class CAdminCreateApp extends ApiApp
{
    public $postArray = array();
	public function CheckInput(&$ErrMsg)
	{
		$ErrMsg = "参数传递错误";
		Global $path_match;

		$postArray = $this -> GetBody();

		foreach ( $postArray as $key => $value){

            $value = trim($value);
            switch ($key) {
                case 'admin_mobile':
                    if( $value=='' ){
                        $ErrMsg = '请您输入邮件地址';
                        return false;
                    }

                    if( !CheckMobile($value) ) {
                        $ErrMsg = '请您正确输入手机号码';
                        return false;
                    }
                    $this -> postArray[ $key ] = $value;
                    break;
                case 'admin_email':
                    if( $value=='' ) {
                        $ErrMsg = '请您输入手机';
                        return false;
                    }

                    if( !CheckEmail($value) ){
                        $ErrMsg = '请您正确输入邮箱';
                        return false;
                    }

                    $this -> postArray[ $key ] = $value;
                    break;
                case 'entry_date':
                    if( $value=='' ){
                        $ErrMsg = '请您选择入职日期';
                        return false;
                    }

                    $this -> postArray[ $key ] = strtotime($value);
                    break;
                case 'name_en':
                case 'realname':
                    if( $value=='' ){
                        $ErrMsg .= '#' . $key;
                        return false;
                    }

                    $this -> postArray[ $key ] = $value;
                    break;

            }

        }
		return true;
	}

    public $DB = array(
        "admin",
    );

	function RunApp()
	{

		if(!$this -> CheckInput($ErrMsg)){
			$this -> showMsg( 406, $ErrMsg);
			return;
		}

        if(!$this -> TConnectMysql()){
            $this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
            return ;
        }

        $searchArray = array(
            "name_en" => $this -> postArray[ "name_en" ],
        );
        $this -> adminDB -> GetNumData($totalNum, $searchArray);
        if( $totalNum>0 ){
            $this -> showMsg( 400, "您输入的英文名字已存在,请重新输入" );
            $this -> TCloseMysql();
            return;
        }
        $searchArray = array(
            "admin_mobile" => $this -> postArray[ "admin_mobile" ],
        );
        $this -> adminDB -> GetNumData($totalNum, $searchArray);
        if( $totalNum>0 ){
            $this -> showMsg( 400, "您输入的手机号码已存在,请重新输入" );
            $this -> TCloseMysql();
            return;
        }

        $this -> postArray[ "admin_verify" ] = strtoupper( md5( random(32) ) );

        $admin = new $this -> adminDB -> tableItemClass;
        $admin -> create_time = time();
        $admin -> update_time = time();
        $admin -> create_admin_id = $this -> tokenUser -> admin_id;
        $admin -> create_realname = $this -> tokenUser -> realname;
        $admin -> admin_status = 1;

        foreach ($this -> postArray as $key => $val) {
            $admin -> {$key} = $val;
        }

        if( !$this -> adminDB -> CreateData($admin) ) {
            $this -> showMsg( 422, "创建后台账号失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }

        $this -> TCloseMysql();

		$this -> showMsg( 200, '添加成功', array(
			"admin_id" => $admin -> admin_id,
		));

		return;
	}
}

$App = new CAdminCreateApp;
$App -> RunApp();
return;
