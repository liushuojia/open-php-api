<?php

/*
    该文件是在 /route/index.php 根目录包含执行, 所以该文件

        方法 PUT
        可在url里面传递GET的参数

*/
require_once("../include/config.php");
require_once( DOCUMENT_ROOT . "/include/userApp.class.php");

class CAdminUpdateApp extends UserApp
{
    public $admin_id;
    public $editArray = array();
    public function CheckInput(&$ErrMsg)
    {
        $ErrMsg = "参数传递错误";
        Global $routeMatchData;

        $this -> admin_id = (int) ($routeMatchData["params"]["admin_id"]);
        if( $this -> admin_id<=0 )
            return false;

        $postArray = $this -> GetBody();
        foreach ( $postArray as $key => $val ){
            $val = trim($val);
            switch ( $key ){
                case "admin_verify":
                    // 有带这个则直接重置该值
                    $this -> editArray[$key] = strtoupper( md5( random(32) ) );
                    break;
                case "name_en":
                case "realname":
                    if( $val=="" )
                        return false;

                    $this -> editArray[$key] = $val;
                    break;
                case "phone":
                case "remark":
                case "headimg":
                    $this -> editArray[$key] = $val;
                    break;
                case "admin_email":
                    if( $val=="" )
                        return false;

                    if( !CheckEmail($val) ){
                        $ErrMsg = '请您正确输入邮箱';
                        return false;
                    }
                    $this -> editArray[$key] = $val;
                    break;
                case "admin_mobile":
                    if( $val=="" )
                        return false;

                    if( !CheckMobile($val) ){
                        $ErrMsg = '请您正确输入邮箱';
                        return false;
                    }
                    $this -> editArray[$key] = $val;
                    break;
                case "entry_date":
                case "update_time":
                   if( $val=="" )
                        return false;

                    $this -> editArray[$key] = strtotime($val);
                    break;
                case "stop_date":
                    if( $val=="" ){
                        $this -> editArray[$key] = 0;
                    }else{
                        $this -> editArray[$key] = strtotime($val);
                    }
                    break;
                case "admin_role":
                case "is_delete":
                case "admin_status":
                case "admin_email_flag":
                case "admin_mobile_flag":
                    $this -> editArray[$key] = (int)($val);
                    break;
            }
        }
        if( count($this -> editArray)==0 )
            return false;

        return true;
    }

    public $DB = array(
        "admin",
    );

	function RunApp()
	{
        if(!$this -> CheckInput($ErrMsg)) {
            $this->showMsg(406, $ErrMsg);
            return;
        }

        if(!$this -> TConnectMysql()){
            $this -> showMsg( 500, "连接数据库失败,请与网站部联系" );
            return ;
        }

        if( array_key_exists("name_en",$this -> editArray) ){
            $searchArray = array(
                "name_en" => $this -> editArray[ "name_en" ],
                "not_admin_id" => $this -> admin_id,
            );
            $this -> adminDB -> GetNumData($totalNum, $searchArray);
            if( $totalNum>0 ){
                $this -> showMsg( 400, "您输入的英文名字已存在,请重新输入" );
                $this -> TCloseMysql();
                return;
            }
        }

        if( array_key_exists("admin_mobile",$this -> editArray) ){
            $searchArray = array(
                "admin_mobile" => $this -> editArray[ "admin_mobile" ],
                "not_admin_id" => $this -> admin_id,
            );
            $this -> adminDB -> GetNumData($totalNum, $searchArray);
            if( $totalNum>0 ){
                $this -> showMsg( 400, "您输入的手机号码已存在,请重新输入" );
                $this -> TCloseMysql();
                return;
            }
        }

        $this -> editArray["update_time"] = time();
        if( !$this -> adminDB -> UpdateDataQuickEditMore($this -> editArray, array(
            'admin_id' => $this -> admin_id,
        )) ){
            $this -> showMsg( 422, "更新数据失败,请与网站管理员联系" );
            $this -> TCloseMysql();
            return;
        }
        $this -> clearAdminRedis($this -> admin_id);

        $this -> showMsg( 200, "更新成功");
		return;
	}
}

$App = new CAdminUpdateApp;
$App -> RunApp();
return;
