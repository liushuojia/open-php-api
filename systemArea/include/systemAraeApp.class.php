<?php

/*
	api 路由类 这里只是实现自动化

*/
class SystemAraeApp  extends ShowApp {
    /* ------------------------------------------------------------------------------
     * 检查权限一般整个站点所有页面都需要检查, 除了登录页面外; 登录页面请根据实际情况设置开关
     */
    public $checkRoleFlag = true;   //检查用户是否有权限访问页面
    public $checkTokenFlag = true;  //检查页面访问的token是否正确

    /*
     * ------------------------------------------------------------------------------
   	 */
    function WebInitReconstruction() {
        #在不同域名中重构该函数，初始化对象的时候需要初始化数据

        //检查页面访问的token是否正确
        if(
            $this -> checkTokenFlag
            && !($this-> CheckToken())
        ) {
            $this -> showMsg( 401, "登录超时 #token is wrong" );
            exit();
        }

        //检查用户是否有权限访问页面
        if( $this -> checkRoleFlag ){
            if( !$this -> CheckStatus() ){
                // 登录超时
                $this -> showMsg( 401, "登录超时 #token time out" );
                exit();
            }
            if( !$this -> checkRole() ){
                // 检查是否有权限
                $this -> showMsg( 403, "无权限 #403 Forbidden" );
                exit();
            }
        }
        return;
    }

}
