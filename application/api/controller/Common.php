<?php
/**
 * 共同接口
 * author: wuyi
 * Date: 2019-4-4
 */
namespace app\api\controller;
use think\Db;
use think\facade\Env;
use Aliyun\DySDKLite\SignatureHelper;
class Common extends Base {
    //点赞
    public function loves(){
        $type = $this->request->post('type','article');
        if($type=='article'){
            action("index/loves");
        }
        elseif($type=='community'){
            action("community/loves");
        }
    }
    //获取评论
    public function commentlist(){
        $type = $this->request->post('type','article');
        if($type=='article'){
            action("index/commentlist");
        }
        elseif($type=='community'){
            action("community/commentlist");
        }
    }
    //评论新增
    public function addcomment(){
        $type = $this->request->post('type','article');
        if($type=='article'){
            action("index/addcomment");
        }
        elseif($type=='community'){
            action("community/addcomment");
        }
    }
    //评论删除 
    public function delcomment(){
        $type = $this->request->post('type','article');
        if($type=='article'){
            action("index/delcomment");
        }
        elseif($type=='community'){
            action("community/delcomment");
        }
    }

    //发送验证码
    public function sendsms($phone,$vcode,$type='registered'){
        require_once  Env::get('extend_path'). '/alidayu/SignatureHelper.php';
        $params = array ();
        $security = false;//是否启用https
        $accessKeyId = config('app.sms_accessKeyId');
        $accessKeySecret = config('app.sms_accessKeySecret');
        $params["SignName"] = config('app.sms_signName');
        $TemplateCode = config('app.sms_tplcode.'.$type);
        if( empty($TemplateCode) ){
            return array('Code'=>'type error');
        }
        $params["TemplateCode"] = $TemplateCode;
        $params["PhoneNumbers"] = $phone;
        $params['TemplateParam'] = Array("code" => $vcode,);
        
        $params['OutId'] = "12345";// fixme 可选: 设置发送短信流水号
        $params['SmsUpExtendCode'] = "1234567";// fixme 可选: 上行短信扩展码, 

        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            )),
            $security
        );

        return (array)$content;
    }

    //获取AI链接
    public function getaiurl(){
        $id = $this->request->post('id',0,'int');
        if( empty($id) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $member_id = $this->getMemberId();
        $rst = getAiUrl($member_id);
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$rst);
    }

     //8.1 通过标签查询内容
    public function bylabel(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $type = $this->request->post('type','article');
        $labels = $this->request->post('labels');
        if( empty($labels) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'标签不能为空');
        }
        $where = array();
        $where[] = array('labels','like',"%{$labels}%");

        $offset = (int)$pagesize*($page-1);
        $total = 0;
        switch ($type) {
            case 'article':
                $total = Db::name('news_info')->where($where)->where('status',1)->count();
                $pageData['contentlist'] = Db::name('news_info')
                                            ->alias('a')
                                            ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                            ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,labels,m.name as author_name,m.headimg as author_headimg')
                                            ->where($where)->where('status',1)
                                            ->order(['sort','id'=>'desc'])
                                            ->limit($offset,$pagesize)
                                            ->select();
                //查询点赞状态，收藏状态
                foreach ($pageData['contentlist'] as $k => $v) {
                    $pageData['contentlist'][$k]['loves_status'] = 0;
                    if( !empty($member_id) ){
                        $wh_loves = array('member_id'=>$member_id,'news_id'=>$v['id'],'comment_id'=>0);
                        $loves_status = Db::name('news_loves')->where($wh_loves)->value('status');
                        $pageData['contentlist'][$k]['loves_status'] = $loves_status==1 ? 1 : 0;
                    }
                }

                break;
            case 'community':
                $total = Db::name('community_info')->where($where)->where('status',1)->count();
                $pageData['contentlist'] = Db::name('community_info')
                                           ->alias('a')
                                           ->join(array(config('database.prefix').'member_info'=>'m'),'a.author_id=m.id')
                                            ->field('a.id as id,a.type as type,a.cate_id as cate_id,a.title as title,a.imgs as img,a.loves as loves_count,a.views as views_count,a.comment as comment_count,a.audio_video as audio_video,labels,m.name as author_name,m.headimg as author_headimg')
                                            ->where($where)->where('status',1)
                                            ->order('a.time_create','desc')
                                            ->limit($offset,$pagesize)
                                            ->select();
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
                break;
            default:
                ajaxReturn(MSG_PARAM_MISS_CODE,'参数有误');
                break;
        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        $pageData['labels']  = $labels;

        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    //8.2新增举报信息
    public function addreport(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $type = $this->request->post('type');//article_comment community_comment community
        $id = $this->request->post('id',0,'int');
        if( empty($id) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'ID不能为空');
        }
        $type_arr = array('article_comment','community_comment','community');
        if( !in_array($type, $type_arr)){
            ajaxReturn(MSG_PARAM_MISS_CODE,'数据type有误');
        }
        $news_id = 0;//文章ID
        $community_id = 0;//帖子ID
        $comment_id = 0;//评论ID
        switch ($type) {
            case 'article_comment':
                //举报文章的评论&评论回复
                $comment_id = $id;
                $news_id = Db::name('news_comment')->where('id',$comment_id)->value('news_id');
                if( empty($news_id) ){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'参数id有误');
                }
                break;
            case 'community_comment':
                //举报帖子的评论&评论回复
                $comment_id = $id;
                $community_id = Db::name('community_comment')->where('id',$comment_id)->value('community_id');
                if( empty($community_id) ){
                    ajaxReturn(MSG_PARAM_MISS_CODE,'参数id有误');
                }
                break;
            case 'community':
                //举报帖子
                $community_id = $id;

                break;
            default:
                # code...
                break;
        }


        $data = array(
            'member_id'=>$member_id,
            'comment_id'=>$comment_id,
            'news_id'=>$news_id,
            'community_id'=>$community_id,
            'time_modify'=>time(),
            'time_create'=>time(),
            'status'=>1,
        );
        if( empty($data['news_id'])&&empty($data['community_id']) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数type有误');
        }
        $rst = Db::name('member_eport')->insert($data);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败',$status);
        }
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$rst);
    }

}