<?php
// 应用公共文件

/***
 * 错误码
 */
define('MSG_SUCCESS_CODE',0); //成功
define('MSG_PARAM_MISS_CODE',10001); //参数缺失
define('MSG_PARAM_VALID_CODE',10002); //参数不合法
define('MSG_QUERY_FAIL_CODE',10003); //查询失败
define('MSG_OPERATE_FAIL_CODE',10004); //操作失败
define('MSG_DENY_CODE',10005); //黑名单
define('MSG_LOGIN_EXPIRED',10006); //登录已过期
define('MSG_TOKEN_VALID',10007); //token校验失败
define('MSG_OPERATE_DISABLED_CODE',10009); //不可操作
define('REPEAT_OPERATION', 10010);//重复操作
define('NON_MEMBERS', 10011);//非会员

/**
 * @param $password
 * @param string $key
 * @return string
 */
function createPassword($password,$key='xwfashion2019') {
    $pwd = md5( md5($password).$key );
    return $pwd;
}


//获取通过获取AI链接 $data= [openid]
function getAiUrl( $openid=null ) {
    if( empty($openid) ){
        return '#needlogin';
    }
    //校验参数
    $base_url = 'http://aitools.xxlimageim.com/imageim/index?useOldWebView=1&';
    $data['openid'] = $openid;
    $data['appid'] = config('app.ai_appid');
    $data['appSrecet'] = config('app.ai_appSrecet');
    $data['timestamp'] = time();
    $data['nonceString'] = createNoncestr(8);
    $data['sourceKey'] = config('app.ai_sourceKey');
    //签名
    $sign_str = "appId={$data['appid']}&appSecret={$data['appSrecet']}&nonceString={$data['nonceString']}&timestamp={$data['timestamp']}&sourceKey={$data['sourceKey']}";
    $data['sign'] = sha1($sign_str);
    $url = $base_url."appId={$data['appid']}&appSecret={$data['appSrecet']}&nonceString={$data['nonceString']}&timestamp={$data['timestamp']}&sourceKey={$data['sourceKey']}&openid={$data['openid']}&sign={$data['sign']}";
    return $url;
}

//是否是链接
function isUrl($url){
    //判断如果是链接说明非oss图片直接返回链接
    if( substr($url, 0, strlen('https://'))=='https://' || substr($url, 0, strlen('http://'))=='http://'  ){
        return true;
    }
    return false;
}

function filter_Emoji($text, $replaceTo = '')
{
    $clean_text = "";
    // Match Emoticons
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $clean_text = preg_replace($regexEmoticons, $replaceTo, $text);
    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $clean_text = preg_replace($regexSymbols, $replaceTo, $clean_text);
    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $clean_text = preg_replace($regexTransport, $replaceTo, $clean_text);
    // Match Miscellaneous Symbols
    $regexMisc = '/[\x{2600}-\x{26FF}]/u';
    $clean_text = preg_replace($regexMisc, $replaceTo, $clean_text);
    // Match Dingbats
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    $clean_text = preg_replace($regexDingbats, $replaceTo, $clean_text);
    return $clean_text;
}


//根据图片路基名称 获取图片真实链接
function getImgUrl($file,$isSign=0){
    //判断如果是链接说明非oss图片直接返回链接
    if( substr($file, 0, strlen('https://'))=='https://' || substr($file, 0, strlen('http://'))=='http://'  ){
        return $file;
    }

    // $file = "201904240930172905_300_600.png";//或者"mulu/1.jpg@!样式名"  或者 mulu/1.jpg”
    //签名方式
    $domain = config('app.oss_domain');//图片域名或bucket域名
    if($isSign){
        $ak = config('app.sms_accessKeyId');;
        $sk = config('app.sms_accessKeySecret');
       
        $expire = time()+3600;
        $bucketname = config('app.oss_bucket');
        $StringToSign = "GET\n\n\n".$expire."\n/".$bucketname."/".$file;
        $Sign= base64_encode(hash_hmac("sha1",$StringToSign,$sk,true));
        $url = $domain.urlencode($file)."?OSSAccessKeyId=".$ak."&Expires=".$expire."&Signature=".urlencode($Sign);
    }else{
        $url = $domain.$file;
    }

    return $url;
}


//根据图片名称获取图片宽度，高度
function getImgHw( $img ) {
    $hw = array('width'=>300,'height'=>600);

    $file_arr = explode('/', $img);
    $filename = array_pop($file_arr);
    $filename_arr = explode('.', $filename);
    if( count($filename_arr)==2 ){
        $filename_n = $filename_arr[0];
        $hw_arr = explode('_', $filename_n);
        if( count($hw_arr) == 3 && is_numeric($hw_arr[1]) &&  is_numeric($hw_arr[2]) ){
            $hw['width'] = $hw_arr[1];
            $hw['height'] = $hw_arr[2];
        }
    }

    return $hw;
}



//表情编译
function userTextEncode($str){
    if(!is_string($str))return $str;
    if(!$str || $str=='undefined')return '';

    $text = json_encode($str); //暴露出unicode
    $text = preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i",function($str){
        return addslashes($str[0]);
    },$text); //将emoji的unicode留下，其他不动，这里的正则比原答案增加了d，因为我发现我很多emoji实际上是\ud开头的，反而暂时没发现有\ue开头。
    return json_decode($text);
}
/**
  表情解译
 */
function userTextDecode($str){
    $text = json_encode($str); //暴露出unicode
    $text = preg_replace_callback('/\\\\\\\\/i',function($str){
        return '\\';
    },$text); //将两条斜杠变成一条，其他不动
    return json_decode($text);
}


//获取随机字符串
function createNoncestr( $length=32,$type='abcd')
{
    $chars = "abcdefghijklmnopqrstuvwxyz_123456789";
    $library = array(
            'd' => '0123456789',
            'abc' => 'abcdefghijklmnopqrstuvwxyz',
            'abcd' => 'abcdefghijklmnopqrstuvwxyz123456789',
            'abcdf' => strtoupper('abcdefghijklmnopqrstuvwxyz_123456789'),
            'ABC' => strtoupper('abcdefghijklmnopqrstuvwxyz'),
            'ABCD' => strtoupper('abcdefghijklmnopqrstuvwxyz123456789'),
            'ABCDF' => strtoupper('abcdefghijklmnopqrstuvwxyz_123456789'),
            'ABD' => 'abcdefghijklmnopqrstuvwxyz123456789'.strtoupper('abcdefghijklmnopqrstuvwxyz'),
        );
    $chars = isset($library["{$type}"]) ? $library["{$type}"] : $library['abc'];
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {
        $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}

/**
 * 发送HTTP请求方法
 * @param string $url  请求URL
 * @param array $params 请求参数
 * @param string $method 请求方法GET/POST
 * @return string $data  响应数据
 */
// 模拟POST提交
function vpost($url,$data){ 
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)'); // 模拟用户使用的浏览器
    // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    // curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包x
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
       echo 'Errno'.curl_error($curl);//捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}

// 模拟GET
function vget($url,$timeout='30'){ 
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)'); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
       echo 'Errno'.curl_error($curl);//捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据
}

/**
 * @param $data
 * @return string
 */
function base64UrlEncode($data) { 
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
}

/**
 * @param $data
 * @return bool|string
 */
function base64UrlDecode($data) { 
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
}

// 生成token
function tokenEncode($str,$key='xinweiapp'){
    $time = time(); //时间戳
    $md5 = strtoupper(md5($str.$time.md5($key))); // 签名字符串
    $shuffleStr = str_shuffle('12345789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
    $randStr = substr($shuffleStr,0,5); //混淆码
    return  $randStr.base64UrlEncode(strrev($time).rand(100,199).$md5.$str);
}

// 解析token
function tokenDecode($token,$key='xinweiapp'){
    $decodeResult = array('errcode'=>MSG_SUCCESS_CODE,'errmsg'=>'');
    $base64Str = substr($token,5); //取出加密字符串
    $decodeStr = base64UrlDecode($base64Str);//解析加密字符串

    if($decodeStr == FALSE){
        $decodeResult['errcode'] = MSG_TOKEN_VALID;
        $decodeResult['errmsg'] = 'decode fail';
        return $decodeResult;
    }

    $signStr = substr($decodeStr,13,32);//获取校验字符串
    $str = substr($decodeStr,45);//获取参数值
    $time = strrev(substr($decodeStr,0,10));//获取参与签名的字符串
    $checkStr = strtoupper(md5($str.$time.md5($key)));

    //校验校验码
    if($signStr != $checkStr){
        $decodeResult['errcode'] = MSG_TOKEN_VALID;
        $decodeResult['errmsg'] = 'auth fail';
        return $decodeResult;
    }

    //是否过期 默认2小时有效
    if($time<time()-7200){
        $decodeResult['errcode'] = MSG_LOGIN_EXPIRED;
        $decodeResult['errmsg'] = 'token expired';
        $decodeResult['str'] = $str;
        return $decodeResult;
    }

    $decodeResult['str'] = $str;
    return $decodeResult;
}


/**
 * @param int $errCode
 * @param string $msg
 * @param null $data
 */
function ajaxReturn($errCode=0,$msg='ok',$data=null){
    $return = array(
        'errcode' => $errCode,
        'errmsg'=> $msg
    );
    if($data){
        $return['data'] = $data;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo  json_encode($return);exit();
}


function ajaxError( $return ){
    $return = array(
        'errcode' => isset($return[1]) ? $return[1] : 0,
        'errmsg'=> isset($return[0]) ? $return[0] : 'ok'
    );
    header('Content-Type: application/json; charset=utf-8');
    echo  json_encode($return);exit();
}