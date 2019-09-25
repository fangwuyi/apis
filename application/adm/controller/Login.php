<?php
/**
 * 管理员登录
 * author: wuyi
 * Date: 2019-3-14
 */
namespace app\adm\controller;
use think\Db;
class Login extends Base {
    // 管理员登录
    public function index(){
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        if(empty($account)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'账号不能为空');
        }
        if(empty($password)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'密码不能为空');
        }
        $login   = Db::name('member_login')->where('account',$account)->find();
        if( empty($login) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'该账号未注册');
        }
        if( $login['password']!=createPassword($password)  ){
            //更新错误次数
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'账号或密码错误');
        }
        if( $login['status']!=1 ){
            ajaxReturn(MSG_DENY_CODE,'该账号异常，请联系管理员处理');
        }
        $user = Db::name('member_info')->where('id',$login['member_id'])->find();
        if( empty($user) ){
            ajaxReturn(MSG_DENY_CODE,'系统出错');
        }
        if( $user['role']<1 ){
            ajaxReturn(MSG_DENY_CODE,'无管理权限');
        }
        $time = time();
        $token = $login['member_id'].md5('token'.$time.$login['member_id']);
        session('admin_id',$login['member_id']);
        //更新登录信息

        ajaxReturn(MSG_SUCCESS_CODE,'登录成功',$token);
    }
    // 管理员信息
    public function getUserInfo(){
        $admin_id = session('admin_id');
        if( empty($admin_id) ){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'登录态失效');
        }
        $pageData['user']   = Db::name('member_info')->where('id',$admin_id)->find();
         ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);

    }

}