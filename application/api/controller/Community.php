<?php
/**
 * 社区
 * author: wuyi
 * Date: 2019-3-13
 */
namespace app\api\controller;
use think\Db;
use think\facade\Log;
class Community extends Base {
    // 3.1 社区内容获取
    public function index(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',8,'int');
        $cate_id = $this->request->post('cate_id',0,'int');
        //分类为空为首次加载
        if( empty($cate_id) ){
            $wh_banner = array('category'=>4,'status'=>1);//category=4为中国女孩专属banner
            $banner = Db::name('banner_info')->field('id,title,img,type,url')->where($wh_banner)->order('sort')->select();
            $pageData['catelist'] = array(
                array("id"=>4,"name"=>"中国女孩",'ishot'=>1,'banner'=>$banner),
                // array("id"=>1,"name"=>"热门",'ishot'=>2),
                array("id"=>2,"name"=>"最新",'ishot'=>2),
                array("id"=>3,"name"=>"最多赞",'ishot'=>2),
                array("id"=>5,"name"=>"关注",'ishot'=>2),
            );
            //默认第一分类
            $cate_id = 4;
        }
        $wh_zg = array();
        $wh_community = array('status'=>1);
        $offset = (int)$pagesize*($page-1);

        switch ($cate_id) {
            case '1'://热门
                $pageData['contentlist'] = Db::name('community_info')
                                           ->alias('a')
                                           ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                            ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,a.audio_video as audio_video,m.name as author_name,m.headimg as author_headimg')
                                            ->where($wh_community)
                                            // ->order('views_count','desc')
                                            ->order(['sort'=>'desc','id'=>'desc'])
                                            ->limit($offset,$pagesize)
                                            ->select(); 
                break;
            case '2'://最新
                $pageData['contentlist'] = Db::name('community_info')
                                           ->alias('a')
                                           ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                            ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,a.audio_video as audio_video,m.name as author_name,m.headimg as author_headimg')
                                            ->where($wh_community)
                                            ->order('a.time_create','desc')
                                            ->limit($offset,$pagesize)
                                            ->select();
                break;
            case '3'://最多赞
                $pageData['contentlist'] = Db::name('community_info')
                                           ->alias('a')
                                           ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                            ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,a.audio_video as audio_video,m.name as author_name,m.headimg as author_headimg')
                                            ->where($wh_community)
                                            ->order(['loves_count'=>'desc','id'=>'desc'])
                                            ->limit($offset,$pagesize)
                                            ->select();
                break;
            case '4'://中国女孩
                $wh_zg[] = array('labels','like',"%中国女孩%");
                $pageData['contentlist'] = Db::name('community_info')
                                           ->alias('a')
                                           ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                            ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,a.audio_video as audio_video,m.name as author_name,m.headimg as author_headimg')
                                            ->where($wh_community)->where($wh_zg)
                                            ->order(['loves_count'=>'desc','id'=>'desc'])
                                            ->limit($offset,$pagesize)
                                            ->select();
                break;
            case '5'://圈子（关注）
                $author_arr = array();
                $member_id = $this->getMemberId();
                if( !empty($member_id) ){
                    $wh_subscribe = array('member_id'=>$member_id,'status'=>1);
                    $author_arr = Db::name('member_subscribe')->where($wh_subscribe)->column('subscribe_id');
                }
                $author_arr[] = 0;//防止结果为空报错
                $wh_zg[] = array('author_id','in',$author_arr);
                $pageData['contentlist'] = Db::name('community_info')
                                           ->alias('a')
                                           ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                            ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,a.audio_video as audio_video,m.name as author_name,m.headimg as author_headimg')
                                            ->where($wh_community)->where($wh_zg)
                                            ->limit($offset,$pagesize)
                                            ->select();
                break;
            default:
                
                break;
        }
        $total = Db::name('community_info')->where($wh_community)->where($wh_zg)->count();


        foreach ($pageData['contentlist'] as $k => $v) {
            $pageData['contentlist'][$k]['img_width'] = 300;
            $pageData['contentlist'][$k]['img_height'] = 600;
            // 根据图片链接得到图片宽高
            $img_one = explode(',', $v['img']);
            if( isset($img_one[0]) && !empty($img_one[0]) ){
                $hw = getImgHw( $img_one[0] );
                if( isset($hw['width']) && isset($hw['height']) ){
                    $pageData['contentlist'][$k]['img_width'] = $hw['width'];
                    $pageData['contentlist'][$k]['img_height'] = $hw['height'];
                }
            }

            if( !empty($v['audio_video']) ){
                $hw = getImgHw( $v['audio_video'] );
                if( isset($hw['width']) && isset($hw['height']) ){
                    $pageData['contentlist'][$k]['img_width'] = $hw['width'];
                    $pageData['contentlist'][$k]['img_height'] = $hw['height'];
                }
            }

            //对图片进行处理
            $images_arr = array();
            foreach ($img_one as $images) {
                if( !empty($images) ){
                    $images_arr[] = getImgUrl($images);
                }
            }
            $pageData['contentlist'][$k]['img'] = implode(',', $images_arr);//【拼接回来
            $pageData['contentlist'][$k]['audio_video'] = !empty($v['audio_video']) ? getImgUrl($v['audio_video']) : '';
            $pageData['contentlist'][$k]['title'] = userTextDecode($v['title']);

        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        $pageData['total_nums']  = $total;
        $pageData['cate_id']  = $cate_id;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 3.2 社区内容详情
    public function detail(){
        $id = $this->request->post('id',0,'int');
        $member_id = $this->getMemberId();
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $pageData['community'] = Db::name('community_info')
                                 ->alias('a')
                                 ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                 ->field('a.id,a.type,a.cate_id,a.title,a.imgs,a.labels,a.content,a.audio_video,a.loves,a.views,a.comment,a.author_id,m.name as author_name,m.headimg as author_headimg')
                                 ->where('a.id',$id)->find();
        $pageData['community']['labels'] = $pageData['community']['labels'] ? explode(',', $pageData['community']['labels']) :array();

        //对图片进行处理
        $img_one = explode(',', $pageData['community']['imgs']);
        $images_arr = array();
        foreach ($img_one as $images) {
            if( !empty($images) ){
                $images_arr[] = getImgUrl($images);
            }
        }
        $pageData['community']['imgs'] = implode(',', $images_arr);//【拼接回来
        $pageData['community']['audio_video'] = getImgUrl($pageData['community']['audio_video']);
        //表情解译
        $pageData['community']['comment'] = userTextDecode($pageData['community']['comment']);
        $pageData['community']['title'] = userTextDecode($pageData['community']['title']);

        if( empty($member_id) ){
            $pageData['community']['loves_status'] = 0;
        }else{
            $wh_loves = array('member_id'=>$member_id,'community_id'=>$id,'comment_id'=>0);
            $loves_status = Db::name('community_loves')->where($wh_loves)->value('status');
            $pageData['community']['loves_status'] = $loves_status==1 ? 1 : 0;
        }
        //增加浏览量
        $rst2 = Db::name('community_info')->where('id',$id)->setInc('views');
        //更新热榜数据
        $this->hotDataUp();
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 2.5 获取资讯评论
    public function commentlist(){
        $id = $this->request->post('id',0,'int');
        $member_id = $this->getMemberId();
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $wh_comment = array('community_id'=>$id,'status'=>2,'comment_id'=>0);
        $pageData['commentlist'] = Db::name('community_comment')->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')->field('c.id as id,c.member_id as member_id,c.comment as comment,c.time_create as datetime,m.name as member_name,m.headimg as member_headimg')->where($wh_comment)->order('c.time_create','desc')->select();

        foreach ($pageData['commentlist'] as $k => $v) {
            $wh_sub = array('community_id'=>$id,'status'=>2,'comment_id'=>$v['id']);
            $pageData['commentlist'][$k]['subcomments'] = Db::name('community_comment')->alias('c')->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')->field('c.id as id,c.comment as comment,c.time_create as datetime,c.member_id,m.name as member_name,m.headimg as member_headimg')->where($wh_sub)->order('c.time_create','desc')->select();

            $pageData['commentlist'][$k]['loves_count'] = Db::name('community_loves')->where( array('community_id'=>$id,'comment_id'=>$v['id'],'status'=>1) )->count('id');
            if( !empty($member_id) ){
                $wh_loves = array('member_id'=>$member_id,'community_id'=>$id,'comment_id'=>$v['id']);
                $loves_status = Db::name('community_loves')->where($wh_loves)->value('status');
                $pageData['commentlist'][$k]['loves_status'] = $loves_status==1 ? 1 : 0;
            }

            //表情解译
            $pageData['commentlist'][$k]['comment'] = userTextDecode($v['comment']);
        }
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 3.3 社区内容点赞
    public function loves(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $community_id = $this->request->post('id',0,'int');
        $comment_id = $this->request->post('comment_id',0,'int');
        $status = $this->request->post('status',1,'int');
        if( empty($community_id) && empty($comment_id) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'内容Id和评论ID不能全为空');
        }
        if( !empty($comment_id) ){
            $community_id = Db::name('community_comment')->where('status','>',0)->where('id',$comment_id)->value('community_id');
            if( empty($community_id) ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'评论ID有误');
            }
        }
        $fans_id = Db::name('community_info')->where('id',$community_id)->value('author_id');
        if( empty($fans_id) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'帖子ID有误');
        }
        $status_array = array(1,2);
        if( !in_array($status, $status_array) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数status有误');
        }
        $wh_chk = array('member_id'=>$member_id,'community_id'=>$community_id,'comment_id'=>$comment_id);
        $chk = Db::name('community_loves')->where($wh_chk)->find();
        if( !empty($chk) ){
            // 已有数据 修改数据即可
            if($chk['status']==$status){
                ajaxReturn(REPEAT_OPERATION,'请勿重复操作',$chk['status']);
            }
            $up = array('status'=>$status,'time_create'=>time());
            $rst = Db::name('community_loves')->where($wh_chk)->update($up);
            if(!$rst){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
            }
            //增加消息通知
            if( $status==1 ){
                if( !empty($comment_id) ){
                    $this->addnotice( 'community_comment_loves',array('id'=>$community_id,'comment_id'=>$comment_id) );
                }else{
                    $this->addnotice( 'community_loves',array('id'=>$community_id) );
                    //增加点赞量
                    $rst2 = Db::name('community_info')->where('id',$community_id)->setInc('loves');
                     //增加用户获赞
                    $wh_fans = array('fans_id'=>$fans_id,'member_id'=>$member_id,'remark'=>'community_'.$community_id);
                    $chk_fans_loves = Db::name('member_loves')->where($wh_fans)->find();
                    if( !empty($chk_fans_loves) ){
                        $rst3 = Db::name('member_loves')->where($wh_fans)->update( array('status'=>1,'time_modify'=>time()) );
                    }else{
                        $data_fans = array(
                            'fans_id'=>$fans_id,
                            'member_id'=>$member_id,
                            'status'=>1,
                            'time_modify'=>time(),
                            'time_create'=>time(),
                            'remark'=>'community_'.$community_id
                        );
                        $rst3 = Db::name('member_loves')->insert( $data_fans );
                    }
                }
            }elseif( empty($comment_id) ){
                //减少点赞量
                $rst2 = Db::name('community_info')->where('id',$community_id)->setDec('loves');
                //减少用户获赞
                $wh_fans = array('fans_id'=>$fans_id,'member_id'=>$member_id,'remark'=>'community_'.$community_id);
                $rst3 = Db::name('member_loves')->where($wh_fans)->update(array('status'=>2));
            }
            ajaxReturn(MSG_SUCCESS_CODE,$status==1?'点赞成功':'点赞已取消'); 
        }else{
            $data = array(
                'member_id'=>$member_id,
                'community_id'=>$community_id,
                'comment_id'=>$comment_id,
                'status'=>1,
                'time_create'=>time()
            );
            $rst = Db::name('community_loves')->insert($data);
            if(!$rst){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'点赞失败');
            }
            //增加消息通知
            if( !empty($comment_id) ){
                $this->addnotice( 'community_comment_loves',array('id'=>$community_id,'comment_id'=>$comment_id) );
            }else{
                $this->addnotice( 'community_loves',array('id'=>$community_id) );
                //增加点赞量
                $rst2 = Db::name('community_info')->where('id',$community_id)->setInc('loves');
                //增加用户获赞
                $wh_fans = array('fans_id'=>$fans_id,'member_id'=>$member_id,'remark'=>'community_'.$community_id);
                $chk_fans_loves = Db::name('member_loves')->where($wh_fans)->find();
                if( !empty($chk_fans_loves) ){
                    $rst3 = Db::name('member_loves')->where($wh_fans)->update( array('status'=>1,'time_modify'=>time()) );
                }else{
                    $data_fans = array(
                        'fans_id'=>$fans_id,
                        'member_id'=>$member_id,
                        'status'=>1,
                        'time_modify'=>time(),
                        'time_create'=>time(),
                        'remark'=>'community_'.$community_id
                    );
                    $rst3 = Db::name('member_loves')->insert( $data_fans );
                }
                
            }
            ajaxReturn(MSG_SUCCESS_CODE,'点赞成功'); 
        }
    }

    // 3.4 社区新增评论
    public function addcomment(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $community_id = $this->request->post('id',0,'int');
        $comment_id = $this->request->post('comment_id',0,'int');
        $comment = $this->request->post('comment');
        if( empty($community_id) && empty($comment_id) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'内容ID和评论ID不能全为空');
        }
        if( empty($comment) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'内容不能为空');
        }
        if( !empty($comment_id) ){
            $community_id = Db::name('community_comment')->where('status','>',0)->where('id',$comment_id)->value('community_id');
            if( empty($community_id) ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'评论ID有误');
            }
        }

        // Log::write('community-addcomment:'.$comment,'notice');

        $data = array(
            'member_id'=>$member_id,
            'community_id'=>$community_id,
            'comment'=>$comment,
            'comment_id'=>$comment_id,
            'time_create'=>time(),
            'status'=>2
        );
        //标签转义
        $data['comment'] = userTextEncode($data['comment']);

        $rst = Db::name('community_comment')->insert($data);
        // Log::write('community-addcomment::'.json_encode($rst),'notice');
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        //增加消息通知
        if( !empty($comment_id) ){
            $this->addnotice( 'community_comment_comment',array('id'=>$community_id,'comment_id'=>$comment_id,'comment'=>$comment) );
        }else{
            $this->addnotice( 'community_comment',array('id'=>$community_id,'comment'=>$comment) );
            //增加评论量
            $rst2 = Db::name('community_info')->where('id',$community_id)->setInc('comment');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 3.5 社区删除评论
    public function delcomment(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $id = $this->request->post('id',0,'int');
        
        $comment_id = $this->request->post('comment_id',0,'int');
        if(empty($comment_id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $wh_chk = array('member_id'=>$member_id,'id'=>$comment_id);
        $chk = Db::name('community_comment')->where($wh_chk)->find();
        if( empty($chk) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误');
        }
        if($chk['status']=='-1'){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误请勿重复操作');
        }
        $up = array('status'=>'-1','time_create'=>time());
        $rst = Db::name('community_comment')->where($wh_chk)->update($up);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        //减少评论量
        if( empty($chk['comment_id']) ){
            $rst2 = Db::name('community_info')->where('id',$id)->setDec('comment');
        }
       
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 3.6 社区信息发布
    public function release(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $label_id = $this->request->post('label_id','');
        $imgs = $this->request->post('imgs','');
        $audio = $this->request->post('audio','');
        $video = $this->request->post('video','');
        $title = $this->request->post('title','');
        $sync_weibo = $this->request->post('sync_weibo',1,'int');
        
        $type = !empty($video) ? 3 : (!empty($audio)?2:1);
        $cate_id = 2;//规则是什么
        $content = $title;//是否需要帮忙截取
        $audio_video = !empty($video) ? $video : $audio;

        //标签处理
        $labels = '';//查询后,拼接
        if( !empty($label_id) ){
            $labels_arr = Db::name('community_label')->where('id','in',$label_id)->column('name');
            $labels = implode(',', $labels_arr);
        }
        //图片处理
        if( !empty($imgs) ){
            $imgs_arr = explode(',', $imgs);
            $imgs_temp = array();
            foreach ($imgs_arr as $img) {
                $imgs_temp[] = str_replace(config('app.oss_domain'),'',$img);
            }
            $imgs = implode(',', $imgs_temp);
        }
        //视频处理
        if( !empty($audio_video) ){
            $audio_video = str_replace(config('app.oss_domain'),'',$audio_video);
        }

        $data = array(
            'type'=>$type,
            'cate_id'=>$cate_id,
            'title'=>$title,
            'imgs'=>$imgs,
            'audio_video'=>$audio_video,
            'content'=>trim($content),
            'author_id'=>$member_id,
            'labels'=>$labels,
            'status'=>1,
            'time_modify'=>time(),
            'time_create'=>time()
        );
        $data['title'] = userTextEncode($data['title']);
        $data['content'] = userTextEncode($data['content']);


        //校验
        if( empty($data['audio_video']) && empty($data['imgs']) && empty($data['content']) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'不允许发空帖子');
        }
        if( empty($data['audio_video']) && empty($data['imgs'])){
            ajaxReturn(MSG_PARAM_MISS_CODE,'文件过大，请重新选择！');
            ajaxReturn(MSG_PARAM_MISS_CODE,'视频和图片不能同时为空');
        }

        $id = Db::name('community_info')->insertGetId($data);
        if(!$id){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        $pageData['id'] = $id;
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功',$pageData);
    }

    // 3.7 社区标签新增
    public function addlabel(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $name = $this->request->post('name');
        if(empty($name)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'标签名不能为空');
        }
        $wh_chk = array('name'=>$name,'member_id'=>$member_id);
        $chk = Db::name('community_label')->where($wh_chk)->find();
        if( !empty($chk) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'请勿重复添加');
        }

        $data = array(
            'name'=>$name,
            'status'=>1,
            'member_id'=>$member_id,
            'time_create'=>time()
        );
        $id = Db::name('community_label')->insertGetId($data);
        if(!$id){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        $pageData = array('id'=>$id,'name'=>$name);
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功',$pageData);
    }

    // 3.8 社区标签删除
    public function dellabel(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $id = $this->request->post('id',0,'int');
        if(empty($id)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $wh_chk = array('member_id'=>$member_id,'id'=>$id);
        $chk = Db::name('community_label')->where($wh_chk)->find();
        if( empty($chk) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误');
        }
        if($chk['status']=='-1'){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数有误请勿重复操作');
        }
        $up = array('status'=>'-1','time_create'=>time());
        $rst = Db::name('community_label')->where($wh_chk)->update($up);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 3.9 社区标签获取
    public function getlabel(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        
        $where = array('status'=>1);
        $pageData['labelList'] = Db::name('community_label')->field('id,name,sort,member_id')->where('member_id','in','0,'.$member_id)->where($where)->order(['sort','member_id','id'=>'desc'])->select();
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 3.10-1 图片上传Alioss接口
    public function upLoadImg(){
        //切换到oss
        action("Alioss/uploadFile");
        // action("Community/upLoadImgOld");
    }

    // 3.10-2 图片上传接口
    public function upLoadImgOld(){
        $Img = $this->request->file('img');
        if(empty($Img)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'图片不能为空');
        }

        $file_info = $Img->getInfo();
        $ext_arr = explode('.',$file_info['name']);
        $file_ext  = array_pop($ext_arr);
        $img_width = $this->request->post('width',300);
        $img_height = $this->request->post('height',600);
        $filename  = date("YmdHis").rand(1000,9999)."_{$img_width}_{$img_height}.".$file_ext;

        $imgInfo = $Img->validate(array('size'=>config('app.image_size'),'ext'=>config('app.image_type')))
                    ->move(config('app.image_path'),$filename,false);
        if(!$imgInfo){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,$Img->getError());
        }
        $imgUrl = config('app.img_domain').'/uploads/'.$imgInfo->getSaveName();
        ajaxReturn(MSG_SUCCESS_CODE,'保存成功',$imgUrl);
    }

    // 3.10-3 base64图片上传接口
    public function upLoadBaseImg(){
        $base64_image_content = $this->request->post('img');
        if(empty($base64_image_content)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'图片不能为空');
        }
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $type = $result[2];
            $new_file = "uploads/".date('Ymd',time())."/";
            $new_file = $new_file.time().".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                    $imgUrl = config('app.img_domain').'/'.$new_file;
                    ajaxReturn(MSG_SUCCESS_CODE,'保存成功',$imgUrl);
                }else{
                    ajaxReturn(MSG_OPERATE_FAIL_CODE,'保存失败');
                }
        }else{
            ajaxReturn(MSG_PARAM_VALID_CODE,'格式有误');
        }
    }

    // 3.11-1 上传视频
    public function upLoadVideo(){
        //切换到oss
        action("Alioss/uploadFile");
    }

    // 3.11-2 上传视频
    public function upLoadVideoOld(){
        //日志
        // Log::write('upLoadVideo:'.date("YmdHis"),'notice');

        $video_content = $this->request->file('video');
        if(empty($video_content)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'视频不能为空');
        }
        $videoInfo = $video_content->validate(array('size'=>config('app.video_size'),'ext'=>config('app.video_type')))->move(config('app.image_path'));
        if(!$videoInfo){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,'保存失败');
        }
        $videoUrl = config('app.img_domain').'/uploads/'.$videoInfo->getSaveName();
        ajaxReturn(MSG_SUCCESS_CODE,'保存成功',$videoUrl);
    }

    //更新热门数据 可以定时20min或30min更新[只对有效文章更新]
    public function hotDataUp(){
        // 帖子的点击次数X1:
        // 实点击次数N，若
        // N＞1000次，X1=7
        // 100＜N≤1000次，X1=5
        // 10＜N≤100次，X1=3
        // 0≤N≤10次，X1=1

        // 帖子的评论数量X2:
        // N＞100次，X2=8
        // 50＜N≤100次，X2=5
        // 10＜N≤50次，X2=4
        // 0≤N≤10次，X2=2

        // 帖子的分享次数X3:
        // N＞100次，X1=8
        // 50＜N≤100次，X1=5
        // 10＜N≤50次，X1=4
        // 0≤N≤10次，X1=2

        // 帖子的发布时间X4:
        // N≤1小时，X4=10
        // 1＜N≤12小时，X4=8
        // 12＜N≤24小时，X4=6
        // 24＜N≤120小时，X4=3
        // N＞120小时，X4=1

        // RankValue=α*X1+β*X2+γ*X3+δ*X4
        // （α=0.2，β=0.3，γ=0.1，δ=0.4）
        $time = time()-60*10;
        $community_list = Db::name('community_info')
                     ->where('status',1)->where('time_modify','<',$time)
                     ->limit(30)
                     ->order('time_modify')
                     ->select();
        if( empty($community_list) ){
            return array(MSG_QUERY_FAIL_CODE,'无需要更新的数据');
        }
        $update_list = array();
        foreach ($community_list as $k => $v) {
            $id = $v['id'];
            $views = $v['views'];
            $comment = $v['comment'];
            $loves = $v['loves'];//$shares = $community['shares'];
            $createtime = (time()-$v['time_create'])/3600;//距离现在小时数

            $x1 = 1; $x2 = 2; $x3 = 2; $x4 = 1;
            if($views>1000){
                $x1 = 7;
            }elseif($views>100 && $views<=1000 ){
                $x1 = 5;
            }elseif($views>10 && $views<=100 ){
                $x1 = 3;
            }else{
                $x1 = 1;
            }

            if($comment>100){
                $x2 = 8;
            }elseif($comment>50 && $comment<=100 ){
                $x2 = 5;
            }elseif($comment>10 && $comment<=50 ){
                $x2 = 4;
            }else{
                $x2 = 2;
            }

            if($loves){
                $x3 = 8;
            }elseif($loves>50 && $loves<=100 ){
                $x3 = 5;
            }elseif($loves>10 && $loves<=50 ){
                $x3 = 4;
            }else{
                $x3 = 2;
            }

            if($createtime<1){
                $x4 = 10;
            }elseif($createtime>1 && $createtime<=12 ){
                $x4 = 8;
            }elseif($createtime>12 && $createtime<=24 ){
                $x4 = 6;
            }elseif($createtime>24 && $createtime<=120 ){
                $x4 = 3;
            }else{
                $x4 = 1;
            }
            $hot = intval($x1*2 + $x2*3 + $x3*1 + $x4*4);//使用十倍避免小数计算

            $update = array('sort'=>$hot,'time_modify'=>time());
            $rst = Db::name('community_info')->where('id',$id)->update($update);
            $update_list[] = array('id'=>$id,'sort'=>$hot,'time_modify'=>time(),'rst'=>$rst);
        }
        return array(MSG_SUCCESS_CODE,'操作成功',$update_list);
    }
}