<?php
/**
 * 资讯管理
 * author: wuyi
 * Date: 2019-3-14
 */
namespace app\adm\controller;
use think\Db;
class News extends Base {
    // 图片列表  category：1指引页，2首页banner,3nav
    public function imgList(){
        $category = $this->request->post('category',1,'int');
        $where = array('category'=>$category);
        $pageData['imglist'] = Db::name('banner_info')->where($where)->order(['status','sort'=>'asc'])->select();
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }
    // 图片管理
    public function imgDo(){
        $id = $this->request->post('id',1,'int');
        $act = $this->request->post('act','get');//add,delete,update,get,
        switch ($act) {
            case 'add':
                $data = array(
                    'title' => $this->request->post('title',''),
                    'img' => $this->request->post('img'),
                    'status' => $this->request->post('status',1),
                    'type' => $this->request->post('type',4),
                    'url' => $this->request->post('url',''),
                    'sort' => $this->request->post('sort',1),
                    'category' => $this->request->post('category',1)
                );
                $rst = Db::name('banner_info')->insert($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'新增失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'新增成功');
                break;
            case 'upstatus':
                $chk = Db::name('banner_info')->where('id',$id)->find();
                if( empty($chk) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'参数有误');
                }
                $status = $this->request->post('status',2,'int');
                $status_arr = array(1,2);
                if( !in_array($chk['status'],$status_arr) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'参数有误');
                }
                 if( $chk['status']==$status ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'请勿重复操作');
                }
                $data = array('status'=>$status);
                $rst = Db::name('banner_info')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
                break;
            case 'update':
                $data = array(
                    'title' => $this->request->post('title',''),
                    'img' => $this->request->post('img'),
                    'status' => $this->request->post('status',1),
                    'type' => $this->request->post('type',4),
                    'url' => $this->request->post('url','#'),
                    'sort' => $this->request->post('sort',1),
                    'category' => $this->request->post('category',1)
                );
                $rst = Db::name('banner_info')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'更新失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'更新成功');
                break;
            default:
                $pageData = Db::name('banner_info')->where('id',$id)->find();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }
    // 资讯列表
    public function newsList(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $cate_id = $this->request->post('cate_id','all','int');
        $keywords = $this->request->post('keywords','');
        
        // 加载新闻信息
        $wh_news = array();
        if( !empty($keywords) ){
            $wh_news[] = array('title','like',"%{$keywords}%");
        }
        $where = array();
        if( $cate_id!='all' ){
            $where['cate_id'] = $cate_id;
        }
        $offset = (int)$pagesize*($page-1);
        $total = Db::name('news_info')->where($wh_news)->where('status','>',0)->where($where)->count();
        $pageData['contentlist'] = Db::name('news_info')
                                    ->alias('a')
                                    ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                    ->join(array(config('database.prefix').'news_cate'=>'c'),'a.cate_id=c.id')
                                    ->field('a.*,m.name as author_name,m.headimg as author_headimg,c.name as cate_name')
                                    ->where($wh_news)
                                    ->where($where)
                                    ->where('a.status','>',0)
                                    ->order(['time_modify'=>'desc'])
                                    ->limit($offset,$pagesize)
                                    ->select();
        $pageData['current_page']  = $page;
        $pageData['total_count']  = $total;
        $pageData['cate_id']  = $cate_id;
         $pageData['sql'] = Db::name('news_info')->getLastSql();
        $pageData['cate_list']  = Db::name('news_cate')->where('status',1)->order('sort')->select();
        $pageData['cate_list'][] = array('id'=>'all','name'=>'请选择类别');
       
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }
    // 资讯处理
    public function newsDo(){
        $id = $this->request->post('id',1,'int');
        $act = $this->request->post('act','get');//add,delete,update,get,
        switch ($act) {
            case 'add':
                $content = $this->request->post('content','');//存json类型吧
                if( empty($content) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'内容不能为空');
                }
                //检查json结构
                $content_arr = json_decode($content,true);
                if( !is_array($content_arr) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'内容格式有误',$content_arr);
                }
                if( empty($content_arr) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'内容不能为空');
                }
               /* $content_nodes = array(
                    array('type'=>'text','text'=>'这是文本内容这是文本内容'),
                    array('type'=>'text','text'=>'这是第二段文本内容，文本内容'),
                    array('type'=>'img','src'=>'http://www.xinwei.tv/uploadfile/2018/1015/thumb_380_251_20181015050326595.png','width'=>380,'height'=>241),
                );
                echo json_encode($content_nodes);
                exit;*/
                //$content做处理再存数据表

                $data = array(
                    'type' => $this->request->post('type',1),
                    'cate_id' => $this->request->post('cate_id'),
                    'title' => $this->request->post('title',1),
                    'topimgs' => $this->request->post('topimgs',''),
                    'imgs' => $this->request->post('imgs',''),
                    'content' => $content,
                    'author_id' => $this->getAdminId(),
                    'labels' => $this->request->post('labels',''),
                    'status' => $this->request->post('status',1),
                    'time_modify' => time(),
                    'time_create' => time(),
                    'sort' => $this->request->post('sort',1)
                );
                //替换中文逗号
                $data['labels'] = str_replace('，',',',$data['labels']);
                if( empty($data['cate_id']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'分类必选');
                }
                if( empty($data['title']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'标题不能为空');
                }
                if( empty($data['topimgs']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'顶图不能为空');
                }
                if( empty($data['imgs']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'标题图不能为空');
                }

                $rst = Db::name('news_info')->insert($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'新增失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'新增成功');
                break;
            case 'delete':
                $data = array('status'=>'-1','time_modify' => time());
                $rst = Db::name('news_info')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'删除失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'删除成功');
                break;
            case 'status':
                $status = $this->request->post('status',2,'int');
                $statu_arr = array(1,2);
                if( !in_array($status, $statu_arr) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'参数有误',$status);
                }
                $data = array('status'=>$status,'time_modify' => time());
                $rst = Db::name('news_info')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
                break;
            case 'update':
                $content = $this->request->post('content','');//存json类型吧
                if( empty($content) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'内容不能为空');
                }
                //检查json结构
                $content_arr = json_decode($content,true);
                if( !is_array($content_arr) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'内容格式有误',$content_arr);
                }
                if( empty($content_arr) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'内容不能为空');
                }

                $data = array(
                    'type' => $this->request->post('type'),
                    'cate_id' => $this->request->post('cate_id'),
                    'title' => $this->request->post('title'),
                    'topimgs' => $this->request->post('topimgs'),
                    'imgs' => $this->request->post('imgs'),
                    'content' => $content,
                    'status' => $this->request->post('status'),
                    'sort' => $this->request->post('sort')
                );
                //对空数据进行过滤
                /*foreach ($data as $k => $v) {
                    if( empty($v) ){
                        unset($data[$k]);
                    }
                }*/
                if( empty($data['cate_id']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'分类必选');
                }
                if( empty($data['title']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'标题不能为空');
                }
                if( empty($data['topimgs']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'顶图不能为空');
                }
                if( empty($data['imgs']) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'标题图不能为空');
                }

                $data['author_id'] = $this->getAdminId();
                $data['labels'] = $this->request->post('labels','');
                $data['labels'] = str_replace('，',',',$data['labels']);
                $data['time_modify'] = time();

                $rst = Db::name('news_info')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'更新失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'更新成功');
                break;
            default:
                $pageData['news'] = Db::name('news_info')->where('id',$id)->find();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }

    // 资讯评论列表
    public function commentList(){
        $news_id = $this->request->post('news_id',0,'int');
        if(empty($news_id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'资讯ID不能为空');
        }
        $where = array('c.news_id'=>$news_id,'c.comment_id'=>0);
        $pageData['commentlist'] = Db::name('news_comment')
                                    ->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')
                                    ->field('c.*,m.name as member_name,m.headimg as member_headimg')
                                    ->where('c.status','>',0)->where($where)
                                    ->order('c.time_create')->select();
        foreach ($pageData['commentlist'] as $k => $v) {
            $wh_sub = array('c.news_id'=>$news_id,'c.comment_id'=>$v['id']);
            $pageData['commentlist'][$k]['subcomments'] = Db::name('news_comment')->alias('c')
                                                        ->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')
                                                        ->field('c.*,m.name as member_name,m.headimg as member_headimg')
                                                        ->where('c.status','>',0)->where($wh_sub)
                                                        ->order('c.time_create')->select();
        }
        
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功',$pageData);
    }

    // 资讯评论删除
    public function commentDel(){
        $id = $this->request->post('id',0,'int');
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $wh_chk = array('id'=>$id);
        $chk = Db::name('news_comment')->where($wh_chk)->find();
        if( empty($chk) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误');
        }
        $up = array('status'=>'-1','time_create'=>time());
        $rst = Db::name('news_comment')->where($wh_chk)->update($up);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        //减少评论
        $rst2 = Db::name('news_info')->where('id',$chk['news_id'])->setDec('comment');
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 分类列表
    public function cateList(){
        $pageData['catelist'] = Db::name('news_cate')->where('status','>',0)->order(['status','sort'])->select();
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }
    // 分类管理
    public function cateDo(){
        $id = $this->request->post('id',1,'int');
        $act = $this->request->post('act','get');//add,delete,update,get,
        switch ($act) {
            case 'add':
                $name = $this->request->post('name','');
                $sort = $this->request->post('sort');
                $status = $this->request->post('status');
                if(empty($name)){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'分类名不能为空');
                }
                if(empty($sort)){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'排序值不能为空');
                }
                if(empty($status)){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'状态必选');
                }
                $chk_name = Db::name('news_cate')->where('name',$name)->find();
                if( !empty($chk_name) ){
                    if( $chk_name['status']=='-1' ){
                        $data = array('status'=>1);
                        Db::name('news_cate')->where('id',$chk_name['id'])->update($data);
                        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
                    }
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'该分类已存在',$chk_name);
                }
                $data = array(
                    'name' => $name,
                    'status' => $status,
                    'sort' => $sort,
                );
                $rst = Db::name('news_cate')->insert($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'新增失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'新增成功');
                break;
            case 'delete':
                $data = array('status'=>'-1');
                $rst = Db::name('news_cate')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'删除失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'删除成功');
                break;
            case 'status':
                $status = $this->request->post('status',2);
                $status_arr = array(1,2);
                if( !in_array($status, $status_arr)){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'参数有误');
                }
                $data = array('status'=>$status);
                $rst = Db::name('news_cate')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'删除失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'删除成功');
                break;
            case 'update':
                $name = $this->request->post('name','');
                if(empty($name)){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'分类名不能为空');
                }
                $data = array(
                    'name' => $name,
                    'status' => $this->request->post('status',1),
                    'sort' => $this->request->post('sort',1),
                );
                $rst = Db::name('news_cate')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'更新失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'更新成功');
                break;
            default:
                $pageData = Db::name('news_cate')->where('id',$id)->find();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }

}