<?php
/**
 * 腾讯AI 
 * author: wuyi
 * Date: 2019-7-10
 */
namespace app\api\controller;
use think\Db;
use think\facade\Log;
use think\facade\Env;

class Qqai extends Base {
    //滤镜功能
    public function imgfilter(){
        $tech = $this->request->post('tech','ptu');//技术选型： 天天P图
        $type = $this->request->post('type',1,'int');//滤镜类型
        $img = $this->request->post('img');//图片
        $img_width = $this->request->post('width',300);
        $img_height = $this->request->post('height',600);

        $base64 = $img;
        $size = strlen($base64);
        if( empty($img) ){
            $img = $this->request->file('img');
            if( empty($img) ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'img必传');
            }
            $file_info = $img->getInfo();
            $ext_arr = explode('.',$file_info['name']);
            $file_ext  = array_pop($ext_arr);
            if( !in_array(strtolower($file_ext), array('jpg','png'))  ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'滤镜功能仅支持JPG、PNG类型图片');
            }
            $rename = $file_info['tmp_name'];
            // $size = getimagesize($rename);
            $size = ceil(filesize($rename) / 1000);//kb
            if($size>500){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'滤镜功能仅支持小于500KB的图片',$size);
            }
            $img_tmp   = file_get_contents($rename);
            $base64 = base64_encode($img_tmp);
        }
       
        
        if( empty($type) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'type不能为空');
        }
        if( empty($img) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'img不能为空');
        }

        require_once Env::get('extend_path'). 'qqai/API.php';
        require_once Env::get('extend_path'). 'qqai/Configer.php';
        require_once Env::get('extend_path'). 'qqai/HttpUtil.php';
        require_once Env::get('extend_path'). 'qqai/Signature.php';
        $app_id = '2118072276';
        $app_key = 'SmO03K1cmKPaPl3c';
        //设置AppID与AppKey
        \Configer::setAppInfo($app_id, $app_key);
        
        //
        

        $params = array(
            'image' => $base64,
            'filter' => $type,
        );

        $response = \API::ptu_imgfilter($params);
        $rst = json_decode($response,true);
        if( isset($rst['ret']) && $rst['ret']==0 ){
            $pageData['type']  = $type;
            $pageData['img']  = isset($rst['data']) && isset($rst['data']['image']) ? 'data:image/jpeg;base64,'.$rst['data']['image'] : '';
            //转成链接
            $base64_image_content = $pageData['img'];
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
                $type = $result[2];$type = 'jpg';
                $new_file = "uploads/temp_".date('Ymd');
                $new_file = $new_file.time()."_{$img_width}_{$img_height}.{$type}";
                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                    $pageData['img'] = config('app.img_domain').'/'.$new_file;
                }else{
                    ajaxReturn(MSG_OPERATE_FAIL_CODE,'缓存文件失败');
                }
            }else{
                ajaxReturn(MSG_PARAM_VALID_CODE,'格式有误');
            }

            ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
        }else{
            $rst['size'] = $size;
            ajaxReturn(MSG_QUERY_FAIL_CODE,'该图片暂不支持滤镜功能',$rst);
        } 
    }

    //美妆功能
    public function facecosmetic(){
        $tech = $this->request->post('tech','ptu');//技术选型： 天天P图
        $type = $this->request->post('type',1,'int');//滤镜类型
        $img = $this->request->post('img');//图片

        $base64 = $img;
        if( empty($img) ){
            $img = $this->request->file('img');
            if( empty($img) ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'img必传');
            }
            $file_info = $img->getInfo();
            $ext_arr = explode('.',$file_info['name']);
            $file_ext  = array_pop($ext_arr);
            if( !in_array(strtolower($file_ext), array('jpg','png'))  ){
                ajaxReturn(MSG_PARAM_MISS_CODE,'仅支持JPG、PNG类型图片');
            }
            $rename = $file_info['tmp_name'];
            $img_tmp   = file_get_contents($rename);
            $base64 = base64_encode($img_tmp);
        }
        
        if( empty($type) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'type不能为空');
        }
        if( empty($img) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'img不能为空');
        }

        require_once Env::get('extend_path'). 'qqai/API.php';
        require_once Env::get('extend_path'). 'qqai/Configer.php';
        require_once Env::get('extend_path'). 'qqai/HttpUtil.php';
        require_once Env::get('extend_path'). 'qqai/Signature.php';
        $app_id = '2118072276';
        $app_key = 'SmO03K1cmKPaPl3c';
        //设置AppID与AppKey
        \Configer::setAppInfo($app_id, $app_key);
        
        $params = array(
            'image' => $base64,
            'cosmetic' => $type,
        );

        $response = \API::ptu_facecosmetic($params);
        $rst = json_decode($response,true);
        if( isset($rst['ret']) && $rst['ret']==0 ){
            $pageData['type']  = $type;
            $pageData['img']  = isset($rst['data']) && isset($rst['data']['image']) ? 'data:image/jpeg;base64,'.$rst['data']['image'] : '';
            //转成链接
            $base64_image_content = $pageData['img'];
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
                $type = $result[2];$type = 'jpg';
                $new_file = "uploads/temp_".date('Ymd');
                $new_file = $new_file.time().".{$type}";
                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
                    $pageData['img'] = config('app.img_domain').'/'.$new_file;
                }else{
                    ajaxReturn(MSG_OPERATE_FAIL_CODE,'缓存文件失败');
                }
            }else{
                ajaxReturn(MSG_PARAM_VALID_CODE,'格式有误');
            }

            ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
        }else{
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败',$rst);
        } 
    }

}