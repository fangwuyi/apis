<?php
/**
 * 阿里api
 * author: wuyi
 * Date: 2019-4-13
 */
namespace app\api\controller;
use think\Db;
use think\Cache;
use think\facade\Env;
use OSS\OssClient;


class Alioss extends Base {
     public function __construct(){
        parent::__construct();
    }
    public function ossClient(){
        $accessKeyId = config('app.sms_accessKeyId');
        $accessKeySecret = config('app.sms_accessKeySecret');
        $endpoint = config('app.oss_endpoint');

        require_once  Env::get('extend_path'). '/alioss/autoload.php';
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false);
        return $ossClient;
    }

    public function getBucketName(){
        $bucketName = config('app.oss_bucket');
        return $bucketName;
    }
    public function createBucket(){
        $ossClient = $this->ossClient();
        $bucket = $this->getBucketName();
        if (is_null($ossClient)) exit(1);
        $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ;
        try {
            $rst = $ossClient->createBucket($bucket, $acl);
            ajaxReturn(MSG_SUCCESS_CODE,'ok',$rst);
        } catch (OssException $e) {
            $message = $e->getMessage();
            ajaxReturn(MSG_QUERY_FAIL_CODE,'createBucket failed',$message);
        }
    }
    //上传文件
    //post_max_size=30M
    //upload_max_filesize = 30M
    //client_max_body_size 30M
    public function uploadFile(){
        $ossClient = $this->ossClient();
        $bucketName = $this->getBucketName();
        try {
            $Img = $this->request->file('img');
            if( empty($Img) ){
                $Img = $this->request->file('video');
                if(empty($Img)){
                    $Img = $this->request->file('audio');
                    if(empty($Img)){
                        ajaxReturn(MSG_PARAM_MISS_CODE,'文件不能为空');
                    }
                }
            }

            $file_info = $Img->getInfo();
            $ext_arr = explode('.',$file_info['name']);
            $file_ext  = array_pop($ext_arr);
            $img_width = $this->request->post('width',300);
            $img_height = $this->request->post('height',600);
            $filename  = date("YmdHis").rand(1000,9999)."_{$img_width}_{$img_height}.".$file_ext;
            $file_name = 'community/'.$filename;
            $rename = $file_info['tmp_name'];

            //校验文件
            $ext_str = config('app.image_type').',mp3,'.config('app.video_type');
            $ext_arr = explode(',', $ext_str);
            if( !in_array($file_ext,$ext_arr) ){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'上传文件格式不正确');
            }

            $rst = $ossClient->uploadFile($bucketName, $file_name, $rename);
            $imgUrl = isset($rst['info']) && isset($rst['info']['url']) ? $rst['info']['url'] : '';
            if( empty($imgUrl) ){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'上传失败');
            }
            ajaxReturn(MSG_SUCCESS_CODE,'ok',$imgUrl);
            // $imgInfo = array(
            //     'pathName'=>$file_name,
            //     'imgUrl'=>$imgUrl
            // );
            // ajaxReturn(MSG_SUCCESS_CODE,'ok',$imgInfo);
        } catch (OssException $e) {
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
    }

   /* public function getImgUrl(){

        $ak = config('app.sms_accessKeyId');;
        $sk = config('app.sms_accessKeySecret');
        $domain = "http://xinwei-oss-aliyun.oss-cn-beijing.aliyuncs.com/";//图片域名或bucket域名
        $expire = time()+3600;
        $bucketname= config('app.oss_bucket');
        $file = "201904240930172905_300_600.png";//或者"mulu/1.jpg@!样式名"  或者 mulu/1.jpg”
        $StringToSign="GET\n\n\n".$expire."\n/".$bucketname."/".$file;
        $Sign=base64_encode(hash_hmac("sha1",$StringToSign,$sk,true));
        $url= $domain.urlencode($file)."?OSSAccessKeyId=".$ak."&Expires=".$expire."&Signature=".urlencode($Sign);
        return $url;
    }*/
}