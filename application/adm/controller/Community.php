<?php
/**
 * 社区管理
 * author: wuyi
 * Date: 2019-3-14
 */
namespace app\adm\controller;
use think\Db;
class Community extends Base {
    // 6.1 社区内容列表
    public function index(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',30,'int');
        $cate_id = $this->request->post('cate_id',0,'int');
        $keywords = $this->request->post('keywords',"");
        
        $wh_community = array('status'=>1);
        if( !empty($cate_id) ){
            $wh_community['cate_id'] = $cate_id;
        }
        $wh_search = array();
        if( !empty($keywords) ){
            $wh_search[] = array('title','like',"%{$keywords}%");
        }

        //分类为空为首次加载
        if( empty($cate_id) ){
            $pageData['catelist'] = array(
                array("id"=>1,"name"=>"热门"),
                array("id"=>2,"name"=>"最新"),
                array("id"=>3,"name"=>"最多赞")
            );
        }
        $offset = (int)$pagesize*($page-1);
        $total = Db::name('community_info')->where($wh_search)->where($wh_community)->count();
        $pageData['contentlist'] = Db::name('community_info')->alias('c')
                                    ->join(array(config('database.prefix').'member_info'=>'m'),'c.author_id=m.id')
                                    ->where($wh_search)->where($wh_community)->order('time_create','desc')
                                    ->field('c.*,m.name as member_name,m.headimg as member_headimg')
                                    ->limit($offset,$pagesize)->select();
        foreach ($pageData['contentlist'] as $k => $v) {
            if( !empty( $v['imgs'] )){
                $img_one = explode(',',  $v['imgs']);
                $images_arr = array();
                foreach ($img_one as $images) {
                    if( !empty($images) ){
                        $images_arr[] = getImgUrl($images);
                    }
                }
                $pageData['contentlist'][$k]['imgs'] = implode(',', $images_arr);
                $pageData['contentlist'][$k]['img'] = isset($images_arr[0]) ? $images_arr[0] : '';
            } 
            $pageData['contentlist'][$k]['audio_video'] = !empty($pageData['contentlist'][$k]['audio_video']) ? getImgUrl($pageData['contentlist'][$k]['audio_video']) : '';
            $pageData['contentlist'][$k]['title'] = userTextDecode($v['title']);
        }

        $pageData['current_page']  = $page;
        $pageData['total_count']  = $total;
        $pageData['cate_id']  = $cate_id;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 6.2 社区内容详情
    public function detail(){
        $id = $this->request->post('id',0,'int');
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $pageData['community'] = Db::name('community_info')
                    ->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.author_id=m.id')
                    ->field('c.*,m.name as author_name,m.headimg as author_headimg')
                    ->where('c.id',$id)->find();

        $wh_comment = array('c.community_id'=>$id);
        $pageData['commentlist'] = Db::name('community_comment')
                                    ->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')
                                    ->field('c.*,m.name as member_name,m.headimg as member_headimg')
                                    ->where('c.status','>',0)->where($wh_comment)
                                    ->order('time_create','desc')->select();

        //对图片进行处理
        if( !empty($pageData['community']['imgs'])){
            $img_one = explode(',', $pageData['community']['imgs']);
            $images_arr = array();
            foreach ($img_one as $images) {
                if( !empty($images) ){
                    $images_arr[] = getImgUrl($images);
                }
            }
            $pageData['community']['img'] = $images_arr;
        } 
        $pageData['community']['audio_video'] = !empty($pageData['community']['audio_video']) ? getImgUrl($pageData['community']['audio_video']) : '';
        $pageData['community']['title'] = userTextDecode($pageData['community']['title']);

        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 6.3 社区内容编辑
    public function editdo(){
        $id = $this->request->post('id',0,'int');
        $status = $this->request->post('status',0,'int');// 状态：-1删除，1显示，2隐藏
        $cate_id = $this->request->post('cate_id',0,'int');

        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }

        $data = array();
        if( !empty($status) ){
            $data['status'] = $status;
        }
        if( !empty($cate_id) ){
            $data['cate_id'] = $cate_id;
        }
        $rst = Db::name('community_info')->where('id',$id)->update($data);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'更新失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'更新成功');
    }

    //  6.4 社区内容评论列表
    public function commentList(){
        $community_id = $this->request->post('community_id',0,'int');
        if(empty($community_id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'社区内容ID不能为空');
        }
        $where = array('c.community_id'=>$community_id,'c.comment_id'=>0);
        $pageData['commentlist'] = Db::name('community_comment')
                                    ->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')
                                    ->field('c.*,m.name as member_name,m.headimg as member_headimg')
                                    ->where('c.status','>',0)->where($where)
                                    ->order('c.time_create')->select();
        foreach ($pageData['commentlist'] as $k => $v) {
            $wh_sub = array('c.community_id'=>$community_id,'c.comment_id'=>$v['id']);
            $pageData['commentlist'][$k]['subcomments'] = Db::name('community_comment')->alias('c')
                                                        ->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')
                                                        ->field('c.*,m.name as member_name,m.headimg as member_headimg')
                                                        ->where('c.status','>',0)->where($wh_sub)
                                                        ->order('c.time_create')->select();
        }
        
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功',$pageData);
    }

    //  6.5 社区内容评论删除
    public function commentDel(){
        $id = $this->request->post('id',0,'int');
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $wh_chk = array('id'=>$id);
        $chk = Db::name('community_comment')->where($wh_chk)->find();
        if( empty($chk) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误');
        }
        //减少评论量
        if( empty($chk['comment_id']) ){
            $rst2 = Db::name('community_info')->where('id',$chk['community_id'])->setDec('comment');
        }

        $up = array('status'=>'-1','time_create'=>time());
        $rst = Db::name('community_comment')->where($wh_chk)->update($up);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 6.6 社区标签管理
    // 分类列表
    public function labelList(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',10,'int');
        $offset = (int)$pagesize*($page-1);
        $where = array('member_id'=>0);
        $pageData['labellist'] = Db::name('community_label')
                                    ->where('status','>',0)->where($where)
                                    ->limit($offset,$pagesize)
                                    ->order(['sort','id'=>'desc'])->select();
        $pageData['current_page']  = $page;
        $pageData['size_page']  = $pagesize;

        $total = Db::name('community_label')
                ->where('status','>',0)->where($where)
                ->count();
        $pageData['total_count']  = $total;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }
    // 6.7 社区标签
    public function labelDo(){
        $id = $this->request->post('id',1,'int');
        $act = $this->request->post('act','get');//add,delete,update,get,
        switch ($act) {
            case 'add':
                $name = $this->request->post('name','');
                if(empty($name)){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'分类名不能为空');
                }
                $chk_name = Db::name('community_label')->where('name',$name)->find();
                if( !empty($chk_name) ){
                    if( $chk_name['status']=='-1' ){
                        $data = array('status'=>1);
                        Db::name('community_label')->where('id',$chk_name['id'])->update($data);
                        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
                    }
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'该分类已存在',$chk_name);
                }
                $data = array(
                    'member_id'=>0,
                    'name' => $name,
                    'status' => $this->request->post('status',1),
                    'time_create'=>time(),
                    'sort' => $this->request->post('sort',255),
                );
                $rst = Db::name('community_label')->insert($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'新增失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'新增成功');
                break;
            case 'delete':
                $data = array('status'=>'-1');
                $rst = Db::name('community_label')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'删除失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'删除成功');
                break;
            case 'update':
                $name = $this->request->post('name','');
                if(empty($name)){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'标签不能为空');
                }
                $data = array();
                if( !empty($name) ){
                    $data['name'] = $name;
                }

                if( !empty($this->request->post('status')) ){
                    $data['status'] = $this->request->post('status');
                }

                if( !empty($this->request->post('sort')) ){
                    $data['sort'] = $this->request->post('sort');
                }

                $rst = Db::name('community_label')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'更新失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'更新成功');
                break;
            default:
                $pageData = Db::name('community_label')->where('id',$id)->find();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }
}