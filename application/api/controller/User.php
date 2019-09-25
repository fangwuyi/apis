<?php
/**
 * 用户登录、注册
 * author: wuyi
 * Date: 2019-3-13
 */
namespace app\api\controller;
use think\Db;
use think\Cache;
class User extends Base {
    // 用户登录
    public function login(){
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
        $token = tokenEncode($login['member_id']);
        //更新登录信息

        ajaxReturn(MSG_SUCCESS_CODE,'登录成功',$token);
    }

    // 用户注册
    public function register(){
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        $vcode = $this->request->post('vcode');
        if(empty($account)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'账号不能为空');
        }
        if(empty($password)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'密码不能为空');
        }
        if(empty($vcode)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'验证码不能为空');
        }
        //校验验证码
        $chk_vcode = Cache('vcode');
        if( $vcode.$account!=$chk_vcode ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'验证码有误',$chk_vcode);
        }
        $chk_phone = Db::name('member_info')->where('phone',$account)->find();
        if( !empty($chk_phone) ){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'该手机号已注册');
        }
        $time = time();
        $user = array(
            'headimg'=> config('app.img_domain').'/uploads/nomal_heading.png',//设置默认头像
            'name'=>substr_replace($account,'****',3,4),
            'phone'=>$account,
            'time_modify'=>$time,
            'time_create'=>$time
        );
        $member_id = Db::name('member_info')->insertGetId($user);
        if( $member_id<1 ){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'系统方面请稍后再试');
        }
        $token = tokenEncode($member_id);
        $login = array(
            'member_id'=>$member_id,
            'account'=>$account,
            'password'=>createPassword($password),
            'last_ip'=>$member_id,
            'last_time'=>$time,
            'token'=>$token,
            'token_expired'=> $time + 60*60*2
        );
        Cache('vcode',NULL);//删除验证码缓存数据
        $rst = Db::name('member_login')->insert($login);
        if( !$rst ){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'账号初始化失败！');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'注册成功',$token);
    }

    // 手机找回密码
    public function findpwd(){
        $account = $this->request->post('account');
        $password = $this->request->post('password');
        $vcode = $this->request->post('vcode');
        if(empty($account)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'账号不能为空');
        }
        if(empty($password)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'密码不能为空');
        }
        if(empty($vcode)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'验证码不能为空');
        }
        //校验验证码 
        $chk_vcode = Cache('vcode');
        if( $vcode.$account !=$chk_vcode ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'验证码有误');
        }
        $chk_phone = Db::name('member_info')->where('phone',$account)->find();
        if( empty($chk_phone) ){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'该手机号未注册');
        }
        $member_id = $chk_phone['id'];
        $time = time();
        $token = tokenEncode($member_id);
        $login = array(
            'password'=>createPassword($password),
            'token'=>$token,
            'token_expired'=> $time + 60*60*2
        );
        $rst = Db::name('member_login')->where('account', $account)->update($login);
        if( !$rst ){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'操作失败！');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功',$token);
    }

    // 获取手机验证码
    public function getvcode(){
        $account = $this->request->post('account');
        $type = $this->request->post('type','registered');
        $type_arr = array('registered','changepassword');
        if( !in_array($type, $type_arr) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数type有误');
        }
        if(empty($account)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'手机号不能为空');
        }
        if($type=='registered'){
            $chk_phone = Db::name('member_info')->where('phone',$account)->find();
            if( !empty($chk_phone) ){
                ajaxReturn(MSG_OPERATE_FAIL_CODE,'该手机号已注册');
            }
        }

        if($type=='changepassword'){
            $chk_phone = Db::name('member_info')->where('phone',$account)->find();
            if( empty($chk_phone) ){
                ajaxReturn(MSG_OPERATE_FAIL_CODE,'该手机号未注册');
            }
        }

        $vcode = rand(100000,999999);
        //发送验证码 如果成则返回发送成功
        $rst = controller('api/common')->sendsms($account,$vcode,$type);
        if( $rst['Code']!='OK' ){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'发送失败！');
        }
        $vcode = $vcode.$account;
        Cache('vcode',$vcode,3600);
        ajaxReturn(MSG_SUCCESS_CODE,'发送成功');
    }

    // 退出登录
    public function logout(){
        //清除登录态
        ajaxReturn(MSG_SUCCESS_CODE,'ok');
    }

    // 授权登录
    public function authorizelogin(){
        $type = $this->request->post('type',2,'int');//2QQ授权，3微信授权，4微博授权
        $account = $this->request->post('openid');
        $password = $this->request->post('uid');
        $name = $this->request->post('name','');
        $headimg = $this->request->post('headimg','');
        $sex = $this->request->post('gender',0,'int');

        if(empty($account)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'手机号不能为空');
        }
        if( empty($headimg) ){
            $headimg = config('app.img_domain').'/uploads/nomal_heading.png';
        }
        if( empty($name) ){
            $name = $account;
        }

        $type_arr = array(2,3,4);
        if( !in_array($type, $type_arr) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数type不正确');
        }
        $wh_chk = array('type'=>$type,'account'=>$account);
        $login   = Db::name('member_login')->where($wh_chk)->find();
        $time = time();
        if( empty($login) || empty($login['member_id']) ){
            if( empty($login) ){
                // ajaxReturn(MSG_QUERY_FAIL_CODE,'该账号未注册');
                $user = array(
                    'headimg'=> $headimg,
                    'name'=>$name,
                    'phone'=>'',
                    'time_modify'=>$time,
                    'time_create'=>$time
                );
                $member_id = Db::name('member_info')->insertGetId($user);
                if( $member_id<1 ){
                    ajaxReturn(MSG_OPERATE_FAIL_CODE,'系统方面请稍后再试');
                }
                $token = tokenEncode($member_id);
                $login = array(
                    'member_id'=>$member_id,
                    'account'=>$account,
                    'password'=>createPassword($password),
                    'last_ip'=>$member_id,
                    'last_time'=>$time,
                    'token'=>$token,
                    'token_expired'=> $time + 60*60*2,
                    'type'=>$type
                );
                $rst = Db::name('member_login')->insert($login);
            }
            else{
                //是否新建空账号？？
                /* 新建账号，===>账号不互通（增加绑定==合并账号，导致数据处理困难  */
                // ajaxReturn(NON_MEMBERS,'未完善信息',tokenEncode($login['member_id']) );

                $user = array(
                    'headimg'=> $headimg,
                    'name'=>$name,
                    'phone'=>'',
                    'time_modify'=>$time,
                    'time_create'=>$time
                );
                $member_id = Db::name('member_info')->insertGetId($user);
                if( $member_id<1 ){
                    ajaxReturn(MSG_OPERATE_FAIL_CODE,'系统方面请稍后再试');
                }
                $token = tokenEncode($member_id);
                $up = array(
                    'member_id'=>$member_id,
                    'last_time'=>$time,
                    'last_ip'=>$member_id,
                    'last_time'=>$time,
                    'token'=>$token,
                    'token_expired'=> $time + 60*60*2,
                );
                $rst = Db::name('member_login')->where($wh_chk)->update($up);
            }
            if(!$rst){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
            }
            //更新登录信息
            ajaxReturn(MSG_SUCCESS_CODE,'登录成功',$token);
        }

        if( $login['password']!=createPassword($password)  ){
            //更新错误次数
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'授权信息有误');
        }
        if( $login['status']!=1 ){
            ajaxReturn(MSG_DENY_CODE,'该账号异常，请联系管理员处理');
        }
        $token = tokenEncode($login['member_id']);
        //更新登录信息

        ajaxReturn(MSG_SUCCESS_CODE,'登录成功',$token);
    }

    // 授权邦定
    public function authorizebind(){
        $type = $this->request->post('type',2,'int');//2QQ授权，3微信授权，4微博授权
        $account = $this->request->post('openid');
        $password = $this->request->post('uid','');
        $phone = $this->request->post('phone');
        $vcode = $this->request->post('vcode','');
        $name = $this->request->post('screen_name','');
        $headimg = $this->request->post('iconurl','');
        $sex = $this->request->post('gender');
        if( empty($phone) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'手机号不能为空');
        }
        $type_arr = array(2,3,4);
        if( !in_array($type, $type_arr) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数type不正确');
        }
        if(empty($vcode)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'验证码不能为空');
        }
        //校验验证码
        $chk_vcode = Cache('vcode');
        if( $vcode.$phone!=$chk_vcode ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'验证码有误',$chk_vcode);
        }
        if(empty($account)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'手机号不能为空');
        }
        if( empty($headimg) ){
            $headimg = config('app.img_domain').'/uploads/nomal_heading.png';
        }
        //检查手机号是否有账号，如果有取出用户ID
        $wh_phone = array('phone'=>$phone);
        $member   = Db::name('xw_member_info')->where($wh_phone)->find();
        $time = time();
        if( empty($member) ){
            //新增账号信息，取出
            $user = array(
                'headimg'=> $headimg,
                'name'=>!empty($name) ? $name : $account,
                'phone'=>$phone,
                'time_modify'=>$time,
                'time_create'=>$time
            );
            $member_id = Db::name('member_info')->insertGetId($user);
            if( empty($member_id) ){
                ajaxReturn(MSG_OPERATE_FAIL_CODE,'系统方面请稍后再试');
            }
        }else{
            $member_id = $member['id'];
        }

        $wh_chk = array('type'=>$type,'account'=>$account);
        $login   = Db::name('member_login')->where($wh_chk)->find();
        $token = tokenEncode($member_id);
        if( !empty($login) ){
            //已存在，换绑？
            $up = array('member_id'=>$member_id,'last_time'=>$time );
            $rst = Db::name('member_login')->where($wh_chk)->update($up);
        }else{
            //新增授权登录信息
            $login = array(
                'member_id'=>$member_id,
                'account'=>$account,
                'password'=>createPassword($password),
                'last_ip'=>$member_id,
                'last_time'=>$time,
                'token'=>$token,
                'token_expired'=> $time + 60*60*2,
                'type'=>$type
            );
            $rst = Db::name('member_login')->insert($login);
        }

        Cache('vcode',NULL);//删除验证码缓存数据
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        //更新登录信息
        ajaxReturn(MSG_SUCCESS_CODE,'登录成功',$token);
    }
}