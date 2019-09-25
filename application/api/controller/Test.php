<?php
/**
 * 测试接口
 * author: wuyi
 * Date: 2019-3-13
 */
namespace app\api\controller;
use think\Db;
use think\facade\Log;
use think\facade\Env;

class Test extends Base {
    //测试-
    public function index(){
        $uid = $this->request->post('uid');//唯一标识
        $expo_token = $this->request->post('expo_token');//expo token
        // $expo_token = 'ExponentPushToken[Nk_Yo3Mg_hnkPBSfU4MEt5]';
        $content = $this->request->post('content','Hello World!');
        $member_id = $this->getMemberId();
        if( empty($uid) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'uid不能为空');
        }
         if( empty($expo_token) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'expo_token不能为空');
        }

        require_once  Env::get('extend_path'). '/expo/autoload.php';
        try {
            $interestDetails = [$uid, $expo_token];
            // You can quickly bootup an expo instance
            $expo = \ExponentPhpSDK\Expo::normalSetup();
            // Subscribe the recipient to the server
            $expo->subscribe($interestDetails[0], $interestDetails[1]);
            // Build the notification data
            $notification = ['body' =>$content ];
            // $notification = ['body' => 'Hello World!', 'data'=> json_encode(array('someData' => 'goes here'))];
            // Notify an interest with a notification
            $rst = $expo->notify($interestDetails[0], $notification);
            ajaxReturn(MSG_SUCCESS_CODE,'ok',$rst);
            // echo 'Succeeded! We have created an Expo instance successfully';
        } catch (Exception $e) {
            ajaxReturn(MSG_QUERY_FAIL_CODE,'Test Failed');
        }


        exit;
        // $act = 'community_comment';
        // $data = array('id'=>46,'comment'=>'社区评论信息');
        // return $this->addnotice( $act,$data );
        $img = 'http://xinwei-oss-aliyun.oss-cn-beijing.aliyuncs.com/uploads/201904241027139313_600_626.jpg';
        $rst = 'mp4';
        $ext_str = config('app.image_type').','.config('app.video_type');
        $ext_arr = explode(',', $ext_str);
        if( !in_array($rst,$ext_arr) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'上传文件格式不正确');
        }
        var_dump($rst);
        //清数据
        //1、删除评论数据
        //2、删除点赞数据
        //3、删除点赞数据
        //4、更新文章点赞，评论，浏览量数据
        //5、更新社区点赞，评论，浏览量数据
        //UPDATE xw_community_info SET loves=0,views=0,comment=0 WHERE id>0;
        /*
        UPDATE xw_news_info SET loves=0,views=0,comment=0 WHERE id>0;
        truncate xw_community_info;
        truncate xw_notice_info;
        truncate xw_community_comment;
        truncate xw_community_loves;
        truncate xw_news_comment;
        truncate xw_news_loves;
        */
        ajaxReturn(MSG_SUCCESS_CODE,'新增成功');
    }
    //消息推送
    public function msgpush(){
        $uid = $this->request->post('uid');//唯一标识
        $expo_token = $this->request->post('expo_token');//expo token
        $expo_token = 'Nk_Yo3Mg_hnkPBSfU4MEt5';
        $content = $this->request->post('content','Hello World!');
        $member_id = $this->getMemberId();
        if( empty($uid) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'uid不能为空');
        }
         if( empty($expo_token) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'expo_token不能为空');
        }

        require_once  Env::get('extend_path'). '/expo/autoload.php';
        try {
            $interestDetails = [$uid, $expo_token];
            // You can quickly bootup an expo instance
            $expo = \ExponentPhpSDK\Expo::normalSetup();
            // Subscribe the recipient to the server
            $expo->subscribe($interestDetails[0], $interestDetails[1]);
            // Build the notification data
            $notification = ['body' =>$content ];
            // $notification = ['body' => 'Hello World!', 'data'=> json_encode(array('someData' => 'goes here'))];
            // Notify an interest with a notification
            $rst = $expo->notify($interestDetails[0], $notification);
            ajaxReturn(MSG_SUCCESS_CODE,'ok',$rst);
            // echo 'Succeeded! We have created an Expo instance successfully';
        } catch (Exception $e) {
            ajaxReturn(MSG_QUERY_FAIL_CODE,'Test Failed');
        }
    }

}