<?php
/**
 * 公共类
 * author: wuyi
 * Date: 2019-3-14
 */

namespace app\adm\controller;

use think\Controller;

class Base extends Controller{
    public function __construct(){
        parent::__construct();
        
        // 检查登录
        $controller_name = request()->controller();
        $action_name = request()->action();
        if ( in_array($controller_name.'.'.$action_name,$this->exceptAuth) || in_array($controller_name.'.*',$this->exceptAuth) ) {
            return ;
        }
        $this->checkLogin();
    }
    //免验证方法
    private $exceptAuth = array(
        'Login.index',
        'Index.test',
        'Pushmsg.index',
    );

    protected function checkLogin(){
        if( !$this->getAdminId() ){
            ajaxReturn(MSG_LOGIN_EXPIRED,'登录过期');
        }
    }

    protected function getAdminId(){
        $admin_id = session('admin_id');
        return $admin_id;
    }

    public function test(){
        echo 11111111;
    }

}