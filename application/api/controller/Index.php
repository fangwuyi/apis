<?php
/**
 * 首页资讯
 * author: wuyi
 * Date: 2019-3-12
 */
namespace app\api\controller;
use think\Db;
class Index extends Base {
    // 指引页
    public function getstart(){
        $where = array(
            'category'=>1,
            'status'=>1,
        );
        $pageData['imglist'] = Db::name('banner_info')->field('id,title,img,url')->where($where)->order('sort')->select();
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 获取首页资讯
    public function index(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $cate_id = $this->request->post('cate_id',0,'int');
        $member_id = $this->getMemberId();
        //分类为空为首次加载
        if( empty($cate_id) ){
            $wh_banner = array('category'=>2,'status'=>1);
            $pageData['banner']   = Db::name('banner_info')->field('id,title,img,type,url')->where($wh_banner)->order('sort')->select();

            $wh_banner = array('category'=>3,'status'=>1,);
            $pageData['navlist']   = Db::name('banner_info')->field('id,title,img,url')->where($wh_banner)->order('sort')->select();
            //形象AI转换链接
            if( !empty($member_id) ){
                foreach ($pageData['navlist'] as $k => $v) {
                    if($v['title']=='形象AI'){
                        $pageData['navlist'][$k]['url'] = getAiUrl($member_id);
                    }
                }
            }
            
            $pageData['catelist'] = Db::name('news_cate')->field('id,name')->where('status',1)->order('sort')->select();
        }
        else{
            // 加载新闻信息
            $wh_news = array('status'=>1,'cate_id'=>$cate_id);
            $offset = (int)$pagesize*($page-1);
            $total = Db::name('news_info')->where($wh_news)->count();
            $pageData['contentlist'] = Db::name('news_info')
                                        ->alias('a')
                                        ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                        ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,m.name as author_name,m.headimg as author_headimg')
                                        ->where($wh_news)
                                        ->order(['sort','id'=>'desc'])
                                        ->limit($offset,$pagesize)
                                        ->select();
            $pageData['current_page']  = $page;
            $pageData['total_page']  = ceil($total/$pagesize);
            $pageData['cate_id']  = $cate_id;


            //查询点赞状态，收藏状态
            foreach ($pageData['contentlist'] as $k => $v) {
                $pageData['contentlist'][$k]['loves_status'] = 0;
                if( !empty($member_id) ){
                    $wh_loves = array('member_id'=>$member_id,'news_id'=>$v['id'],'comment_id'=>0);
                    $loves_status = Db::name('news_loves')->where($wh_loves)->value('status');
                    $pageData['contentlist'][$k]['loves_status'] = $loves_status==1 ? 1 : 0;
                }
            }
        }
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 热搜榜
    public function searchhot(){
        $pageData['datalist'] = Db::name('hot_search')->where('status',1)->order(['hot'=>'desc','sort'=>'asc'])->column('content');
        // array('最想买的一致口红','林俊杰','爱情','陈奕迅','篮球','张杰','前任');
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 搜索资讯内容
    public function searchcontent(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $cate_id = $this->request->post('cate_id',0,'int');
        $keywords = $this->request->post('keywords','');
        if(empty($keywords)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'关键词不能为空');
        }
        $member_id = $this->getMemberId();
        $wh_news = array('status'=>1);
        if( !empty($cate_id) ){
            $wh_news['cate_id'] = $cate_id;
        }
        $offset = (int)$pagesize*($page-1);
        $total = Db::name('news_info')->where($wh_news)
                    ->where('title','like',"%{$keywords}%")->whereOr('content','like',"%{$keywords}%")
                    ->count();
        $pageData['contentlist'] = Db::name('news_info')
                                    ->alias('a')
                                    ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                    ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.content as content,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,m.name as author_name,m.headimg as author_headimg')
                                    ->where($wh_news)
                                    ->where('title','like',"%{$keywords}%")->whereOr('content','like',"%{$keywords}%")
                                    ->order(['sort'=>'desc','id'=>'desc'])
                                    ->limit($offset,$pagesize)
                                    ->select();
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        $pageData['cate_id']  = $cate_id;


        //查询点赞状态，收藏状态
        foreach ($pageData['contentlist'] as $k => $v) {
            $pageData['contentlist'][$k]['loves_status'] = 0;
            if( !empty($member_id) ){
                $wh_loves = array('member_id'=>$member_id,'news_id'=>$v['id'],'comment_id'=>0);
                $loves_status = Db::name('news_loves')->where($wh_loves)->value('status');
                $pageData['contentlist'][$k]['loves_status'] = $loves_status==1 ? 1 : 0;
            }
        }
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }
    
    // 获取资讯详情
    public function detail(){
        $id = $this->request->post('id',0,'int');
        $member_id = $this->getMemberId();
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $pageData['news'] = Db::name('news_info')
                            ->alias('a')
                            ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                            ->field('a.*,m.name as author_name,m.headimg as author_headimg')
                            ->where('a.id',$id)->find();
        $pageData['news']['content_nodes'] = json_decode($pageData['news']['content'],true);
        $pageData['news']['content'] = htmlspecialchars('<p>这是文本内容</p>');
        unset($pageData['news']['content']);
        $pageData['news']['labels'] = $pageData['news']['labels'] ? explode(',', $pageData['news']['labels']) : array();
        if( empty($member_id) ){
            $pageData['news']['loves_status'] = 0;
        }else{
            $wh_loves = array('member_id'=>$member_id,'news_id'=>$id,'comment_id'=>0);
            $loves_status = Db::name('news_loves')->where($wh_loves)->value('status');
            $pageData['news']['loves_status'] = $loves_status==1 ? 1 : 0;
        }
        $pageData['news']['link'] = config('app.img_domain').'/article.html?id='.$pageData['news']['id']; 

        // 推荐
        $pageData['recommend'] = Db::name('news_info')->field('id,title,imgs')->where('status',1)->limit(8)->select();
        //打乱数据
        shuffle($pageData['recommend']);
        $pageData['recommend'] = array_slice($pageData['recommend'], 0,5);

        //增加浏览量
        $rst2 = Db::name('news_info')->where('id',$id)->setInc('views');

        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 2.5 获取资讯评论
    public function commentlist(){
        $id = $this->request->post('id',0,'int');
        $member_id = $this->getMemberId();
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $wh_comment = array('news_id'=>$id,'status'=>2,'comment_id'=>0);
        $pageData['commentlist'] = Db::name('news_comment')->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')->field('c.id as id,c.member_id as member_id,c.comment as comment,c.time_create as datetime,m.name as member_name,m.headimg as member_headimg')->where($wh_comment)->order('c.time_create','desc')->select();
        foreach ($pageData['commentlist'] as $k => $v) {
            $wh_sub = array('news_id'=>$id,'status'=>2,'comment_id'=>$v['id']);
            $pageData['commentlist'][$k]['subcomments'] = Db::name('news_comment')->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')->field('c.id as id,c.comment as comment,c.time_create as datetime,c.member_id,m.name as member_name,m.headimg as member_headimg')->where($wh_sub)->order('c.time_create','desc')->select();
            // 获取总赞数
            $pageData['commentlist'][$k]['loves_count'] = Db::name('news_loves')->where( array('news_id'=>$id,'comment_id'=>$v['id'],'status'=>1) )->count('id');
            // 获取点赞状态
            $pageData['commentlist'][$k]['loves_status'] = 0;
            if( !empty($member_id) ){
                $wh_loves = array('member_id'=>$member_id,'news_id'=>$id,'comment_id'=>$v['id']);
                $loves_status = Db::name('news_loves')->where($wh_loves)->value('status');
                $pageData['commentlist'][$k]['loves_status'] = $loves_status==1 ? 1 : 0;
            }

            $pageData['commentlist'][$k]['comment'] = userTextDecode($v['comment']);
        }
        $pageData['member_id'] = $member_id;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 资讯点赞
    public function loves(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $news_id = $this->request->post('id',0,'int');
        $comment_id = $this->request->post('comment_id',0,'int');
        $status = $this->request->post('status',1,'int');
        if(empty($news_id)&&empty($comment_id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'资讯ID和评论ID不能同时为空');
        }
        if(!empty($comment_id)){
            //查询资讯信息
            $news_id = Db::name('news_comment')->where('status','>',0)->where('id',$comment_id)->value('news_id');
            if( empty($news_id) ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'评论ID有误');
            }
        }

        $status_array = array(1,2);
        if( !in_array($status, $status_array) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数status有误');
        }
        $wh_chk = array('member_id'=>$member_id,'news_id'=>$news_id,'comment_id'=>$comment_id);
        $chk = Db::name('news_loves')->where($wh_chk)->find();
        if( !empty($chk) ){
            // 已有数据 修改数据即可
            if($chk['status']==$status){
                ajaxReturn(REPEAT_OPERATION,'请勿重复操作',$chk['status']);
            }
            $up = array('status'=>$status,'time_create'=>time());
            $rst = Db::name('news_loves')->where($wh_chk)->update($up);
            if(!$rst){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
            }

            if( $status==1 ){
                $rst2 = Db::name('news_info')->where('id',$news_id)->setInc('loves');
                //增加评论消息通知
                if( !empty($comment_id) ){
                    $this->addnotice( 'article_comment_loves',array('id'=>$news_id,'comment_id'=>$comment_id) );
                }
            }else{
                $rst2 = Db::name('news_info')->where('id',$news_id)->setDec('loves');
            }
            
            ajaxReturn(MSG_SUCCESS_CODE,$status==1?'点赞成功':'点赞已取消'); 
        }else{
            $data = array(
                'member_id'=>$member_id,
                'news_id'=>$news_id,
                'comment_id'=>$comment_id,
                'status'=>1,
                'time_create'=>time()
            );
            $rst = Db::name('news_loves')->insert($data);
            $rst2 = Db::name('news_info')->where('id',$news_id)->setInc('loves');
            if(!$rst){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'点赞失败');
            }
            //增加评论消息通知
            if( !empty($comment_id) ){
                $this->addnotice( 'article_comment_loves',array('id'=>$news_id,'comment_id'=>$comment_id) );
            }

            ajaxReturn(MSG_SUCCESS_CODE,'点赞成功'); 
        }
    }

    // 2.6 资讯评论新增
    public function addcomment(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $news_id = $this->request->post('id',0,'int');
        $comment_id = $this->request->post('comment_id',0,'int');
        $comment = $this->request->post('comment');
        if( empty($comment) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'资讯评论内容不能为空');
        }
        if( empty($news_id) && empty($comment_id) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'资讯ID和评论ID不能同时为空');
        }

        if(!empty($comment_id)){
            //回复评论
            $news_id = Db::name('news_comment')->where('status','>',0)->where('id',$comment_id)->value('news_id');
            if( empty($news_id) ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'评论ID有误');
            }
        }
        
        $data = array(
            'member_id'=>$member_id,
            'news_id'=>$news_id,
            'comment'=>$comment,
            'comment_id'=>$comment_id,
            'time_create'=>time(),
            'status'=>2
        );

        $data['comment'] = userTextEncode($data['comment']);
        
        $rst = Db::name('news_comment')->insert($data);
        $rst2 = Db::name('news_info')->where('id',$news_id)->setInc('comment');
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        
        //增加消息通知
        if( !empty($comment_id) ){
            $this->addnotice( 'article_comment_comment',array('id'=>$news_id,'comment_id'=>$comment_id,'comment'=>$comment) );
        }

        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 2.7 资讯评论删除
    public function delcomment(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        
        $id = $this->request->post('id',0,'int');
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $comment_id = $this->request->post('comment_id',0,'int');
        if(empty($comment_id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $wh_chk = array('member_id'=>$member_id,'news_id'=>$id,'id'=>$comment_id);
        $chk = Db::name('news_comment')->where($wh_chk)->find();
        if( empty($chk) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误');
        }
        if($chk['status']=='-1'){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误请勿重复操作');
        }
        $up = array('status'=>'-1','time_create'=>time());
        $rst = Db::name('news_comment')->where($wh_chk)->update($up);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        $rst2 = Db::name('news_info')->where('id',$id)->setDec('comment');
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

}