<?php
/**
 * 会员管理
 * author: wuyi
 * Date: 2019-3-14
 */
namespace app\adm\controller;
use think\Db;
class Member extends Base {
    // 会员列表
    public function index(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',10,'int');
        $keywords = $this->request->post('keywords','');
        $offset = (int)$pagesize*($page-1);
        $where = array();
        if( !empty($keywords) ){
            $where[] = array('name|phone','like',"%{$keywords}%");
        }
        $pageData['memberlist']  = Db::name('member_info')
                        ->where($where)->where('role',0)
                        ->limit($offset,$pagesize)
                        ->select();
        $pageData['current_page']  = $page;
        $pageData['size_page']  = $pagesize;

        $total = Db::name('member_info')->where($where)->where('role',0)->count();
        $pageData['total_count']  = $total;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    public function admin(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',10,'int');
        $keywords = $this->request->post('keywords','');

        $offset = (int)$pagesize*($page-1);
         $where = array();
        if( !empty($keywords) ){
            $where[] = array('name|phone','like',"%{$keywords}%");
        }
        $pageData['memberlist']   = Db::name('member_info')
                        ->where($where)->where('role',1)
                        ->limit($offset,$pagesize)
                        ->select();
        $pageData['current_page']  = $page;
        $pageData['size_page']  = $pagesize;
        $total = Db::name('member_info')->where($where)->where('role',1)->count();
        $pageData['total_count']  = $total;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    public function deladmin(){
        $id = $this->request->post('id',0,'int');
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数丢失');
        }
        $chk = Db::name('member_info')->where('id',$id)->find();
        if( empty($chk) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数有误');
        }

        if($chk['role']!=1){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'请勿重复操作');
        }
        //超级管理员不能取消
        if($chk['id']==1){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'超级管理员不能取消');
        }

        $data = array('role'=>0,'time_modify' => time());
        $rst = Db::name('member_info')->where('id',$id)->update($data);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    public function setadmin(){
        $id = $this->request->post('id',0,'int');
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数丢失');
        }
        $chk = Db::name('member_info')->where('id',$id)->find();
        if( empty($chk) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数有误');
        }
        if($chk['role']==1){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'请勿重复操作');
        }

        $data = array('role'=>1,'time_modify' => time());
        $rst = Db::name('member_info')->where('id',$id)->update($data);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

}