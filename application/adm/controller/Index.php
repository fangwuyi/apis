<?php
/**
 * 后台首页、资讯管理
 * author: wuyi
 * Date: 2019-3-14
 */
namespace app\adm\controller;
use think\Db;
class Index extends Base {
    //
    public function index(){
        echo 'nothing todo';exit();
    }

    public function mirroradmin(){
        $mirror_url = 'http://platform.prev.xxlimageim.com/login/xinweiLogin';
        $mirror_key = 'QsPjXstVRymcnlC';
        $mirror_source = 'xinwei';
        $timestamp = time();
        $sign_str = md5($mirror_key.$mirror_source.$timestamp);
        $pageData = $mirror_url+"?sign={$sign_str}&source={$mirror_source}&timestamp={$timestamp}";
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 3.10-1 图片上传Alioss接口
    public function upLoadImg(){
        //切换到oss
        action("api/Alioss/uploadFile");
        // action("Community/upLoadImgOld");
    }

    // 3.10 图片上传接口
    public function upLoadImgOld(){
        $Img = $this->request->file('img');
        if(empty($Img)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'图片不能为空');
        }
        //保存奖项名称图片
        $imgInfo = $Img->validate(array('size'=>config('app.image_size'),'ext'=>config('app.image_type')))->move(config('app.image_path'));
        if(!$imgInfo){
            ajaxReturn(MSG_OPERATE_FAIL_CODE,$Img->getError());
        }
        $imgUrl = config('app.img_domain').'/uploads/'.$imgInfo->getSaveName();
        ajaxReturn(MSG_SUCCESS_CODE,'保存成功',$imgUrl);
    }

    // 3.10 base64图片上传接口
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
        action("api/Alioss/uploadFile");
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

    //热搜榜
    public function hotsearchDo(){
        $dbName = Db::name('hot_search');
        $id = $this->request->post('id',1,'int');
        $act = $this->request->post('act');//add,delete,update,get,
        switch ($act) {
            case 'add':
                $data = array(
                    'type' => $this->request->post('type',1),
                    'content' => $this->request->post('content'),
                    'status' => $this->request->post('status',1),
                    'hot' => $this->request->post('hot',4),
                    'sort' => $this->request->post('sort',255),
                    'time_create' => time()
                );
                $rst = $dbName->insert($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'新增失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'新增成功');
                break;
            case 'upstatus':
                $chk = $dbName->where('id',$id)->find();
                if( empty($chk) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'参数有误');
                }
                if( $chk['status']==$status ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'请勿重复操作');
                }
                $data = array('status'=>$status);
                $rst = $dbName->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
                break;
            case 'update':
                $data = array(
                    'type' => $this->request->post('type',1),
                    'content' => $this->request->post('content'),
                    'status' => $this->request->post('status',1),
                    'hot' => $this->request->post('hot',4),
                    'sort' => $this->request->post('sort',255)
                );
                $rst = $dbName->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'更新失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'更新成功');
                break;
            case 'get':
                $pageData = $dbName->where('id',$id)->find();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
            default:
                $pageData['datalist'] = $dbName->where('status',1)->select();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }

    //举报管理
    public function eportDo(){
        $id = $this->request->post('id',1,'int');
        $act = $this->request->post('act');//add,delete,update,get,
        switch ($act) {
            case 'update':
                $status = $this->request->post('status',1);//维持当前状态，2隐藏评论内容
                if( !in_array($status, array(1,2)) ){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'参数status有误');
                }
                $chk = Db::name('member_eport')->where('id',$id)->find();
                if(empty($chk)){
                     ajaxReturn(MSG_QUERY_FAIL_CODE,'参数id有误');
                }

                if($status==1){
                    $data = array(
                        'status' => 3,
                        'time_modify' => time()
                    );
                    $rst = Db::name('member_eport')->where('id',$id)->update($data);
                }else{
                    //禁显评论
                    if( !empty($chk['news_id']) ){
                        $where2 = array('news_id'=>$chk['news_id'],'comment_id'=>$chk['comment_id']);
                        $chk2 = Db::name('news_comment')->where($where2)->find();
                        if( !empty($chk2) ){
                            $up = array('status'=>3);
                            //更新评论数
                            $rst2 =  Db::name('news_comment')->where($where2)->update($up);
                            //减少评论量
                            Db::name('news_info')->where('id',$chk['news_id'])->setDec('comment');
                        }
                        
                    }else{
                        if( !empty($chk['comment_id']) ){
                            $where2 = array('community_id'=>$chk['community_id'],'comment_id'=>$chk['comment_id']);
                            $chk2 = Db::name('community_comment')->where($where2)->find();
                            if( !empty($chk2) ){
                                $up = array('status'=>3);
                                //更新评论数
                                $rst2 =  Db::name('community_comment')->where($where2)->update($up);
                                //减少评论量
                                if( empty($chk2['comment_id']) ){
                                    $rst3 = Db::name('community_info')->where('id',$chk2['community_id'])->setDec('comment');
                                }
                            }
                        }else{
                            $up = array('status'=>2);
                            $where2 = array('id'=>$chk['community_id']);
                            $rst2 =  Db::name('community_info')->where($where2)->update($up);
                        }
                    }
                    //是否需要合并处理？[同时多人举报的情况]

                    //非合并处理
                    $data = array(
                        'status' => 2,
                        'time_modify' => time()
                    );
                    $rst = Db::name('member_eport')->where('id',$id)->update($data);
                }
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
                break;
            case 'get':
                $pageData = Db::name('member_eport')->where('id',$id)->find();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
            default:

                $pageData['datalist'] = Db::name('member_eport')->alias('r')
                                        ->join(array(config('database.prefix').'member_info'=>'m'),'r.member_id=m.id')
                                        ->where('status',1)
                                        ->field('r.*,m.name as member_name,m.headimg as member_headimg')
                                        ->select();
                foreach ($pageData['datalist'] as $k => $v) {
                    if( !empty($v['news_id']) ){
                        $where = array('id'=>$v['comment_id']);
                        $pageData['datalist'][$k]['content'] = Db::name('news_comment')->where($where)->value('comment');
                        $pageData['datalist'][$k]['type'] = '文章评论';
                    }else{
                        if( !empty($v['comment_id']) ){
                            $where = array('id'=>$v['comment_id']);
                            $pageData['datalist'][$k]['content'] = Db::name('community_comment')->where($where)->value('comment');
                            $pageData['datalist'][$k]['type'] = '帖子评论';
                        }else{
                            $where = array('id'=>$v['community_id']);
                            $pageData['datalist'][$k]['content'] = Db::name('community_info')->where($where)->value('title');
                            $pageData['datalist'][$k]['type'] = '帖子';
                        }
                    }
                    $pageData['datalist'][$k]['content'] = userTextDecode($pageData['datalist'][$k]['content']);
                }
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }

    //反馈管理
    public function feedbackDo(){
        $id = $this->request->post('id',0,'int');
        $act = $this->request->post('act');//add,delete,update,get,
        switch ($act) {
            case 'update':
                $chk = Db::name('feedback')->where('id',$id)->find();
                if(empty($chk)){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'参数id有误');
                }

               $data = array(
                    'status' => 2,
                    'time_modify' => time()
                );
                $rst = Db::name('feedback')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
                break;
            case 'get':
                $pageData = Db::name('feedback')->where('id',$id)->find();
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
            default:
                $page = $this->request->post('page',1,'int');
                $pagesize = $this->request->post('pagesize',10,'int');
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('feedback')->where('status',1)->count();
                $pageData['datalist'] =  Db::name('feedback')->alias('c')
                                        ->join(array(config('database.prefix').'member_info'=>'m'),'c.member_id=m.id')
                                        ->where('status',1)
                                        ->field('c.*,m.name as member_name,m.headimg as member_headimg')
                                        ->limit($offset,$pagesize)->select();
                $pageData['current_page']  = $page;
                $pageData['total_count']  = $total;
                $pageData['size_page']  = $pagesize;
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }

    //推送
    public function messagePushDo(){
        $id = $this->request->post('id',1,'int');
        $act = $this->request->post('act');//add,delete,update,get,
        switch ($act) {
            case 'add':
                $data = array(
                    'type' => $this->request->post('type',1),
                    'title' => $this->request->post('title'),
                    'content' => $this->request->post('content'),
                    'push_time' => strtotime( $this->request->post('push_time') ) ,
                    'push_status' => 1,
                    'push_users' => $this->request->post('push_users'),
                    'time_modify' => time(),
                    'time_create' => time()
                );
                $rst = Db::name('message_push')->insert($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'新增失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'新增成功');
                break;
            case 'update':
                $data = array(
                    'type' => $this->request->post('type',1),
                    'title' => $this->request->post('title'),
                    'content' => $this->request->post('content'),
                    'push_time' => strtotime( $this->request->post('push_time') ) ,
                    'push_status' => 1,
                    'push_users' => $this->request->post('push_users'),
                    'time_modify' => time(),
                    'time_create' => time()
                );
                $rst = Db::name('message_push')->where('id',$id)->update($data);
                if(!$rst){
                    ajaxReturn(MSG_QUERY_FAIL_CODE,'更新失败');
                }
                ajaxReturn(MSG_SUCCESS_CODE,'更新成功');
                break;
            default:
                $page = $this->request->post('page',1,'int');
                $pagesize = $this->request->post('pagesize',10,'int');
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('message_push')->where('push_status','>',0)->count();
                $pageData['datalist'] =  Db::name('message_push')
                                        ->where('push_status','>',0)
                                        ->limit($offset,$pagesize)->select();

                foreach ($pageData['datalist'] as $k => $v) {
                    $pageData['datalist'][$k]['push_time'] = date("Y-m-d H:i:s",$v['push_time']);
                    $pageData['datalist'][$k]['action'] = $v['push_status']==1 ? '编辑' : '';
                    $pageData['datalist'][$k]['push_status'] = $v['push_status']==1 ? '未推送' : ($v['push_status']==2 ? '已推送' : $v['push_status']);
                }
                $pageData['current_page']  = $page;
                $pageData['total_count']  = $total;
                $pageData['size_page']  = $pagesize;
                ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
                break;
        }
    }

    public function memberDevice(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',10,'int');
        $device_type = $this->request->post('device_type');
        $keywords = $this->request->post('keywords');
        $where = array();
        if( !empty($device_type) ){ $where['device_type'] = $device_type; } 
        if( !empty($keywords) ){ $where['member_id'] = $keywords; } 

        $offset = (int)$pagesize*($page-1);
        $total = Db::name('message_subscribe')->where($where)->count();
        $pageData['datalist'] =  Db::name('message_subscribe')
                                ->where($where)
                                ->limit($offset,$pagesize)->select();

        $pageData['current_page']  = $page;
        $pageData['total_count']  = $total;
        $pageData['size_page']  = $pagesize;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

}