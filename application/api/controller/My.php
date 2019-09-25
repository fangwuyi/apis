<?php
/**
 * 我的
 * author: wuyi
 * Date: 2019-3-13
 */
namespace app\api\controller;
use think\Db;
class My extends Base {
    // 4.1 我的主页
    public function index(){
        $member_id = $this->getMemberId();
        //发布
        $pageData['total_release'] = Db::name('community_info')->where('status','>',0)->where('author_id',$member_id)->count('id');
        //点赞
        $total_loves_news = Db::name('news_loves')->where('status',1)->where('member_id',$member_id)->count('id');
        $total_loves_community = Db::name('community_loves')->where('status',1)->where('member_id',$member_id)->count('id');
        $pageData['total_loves'] = $total_loves_news+$total_loves_community;
        //评论
        $where = array('member_id'=>$member_id,'comment_id'=>0);
        $total_comment_news = Db::name('news_comment')->where('status','>',0)->where($where)->count('id');
        $total_comment_community = Db::name('community_comment')->where('status','>',0)->where($where)->count('id');
        $pageData['total_comment'] = $total_comment_news+$total_comment_community;

        $pageData['total_notice'] = Db::name('notice_info')->where('status',1)->where('member_id',$member_id)->count('id');

        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }
    // 4.2我的信息
    public function getInfo(){
        $member_id = $this->getMemberId();
        $pageData = Db::name('member_info')->where('id',$member_id)->field('id,name,headimg,country,province,city,county,address,age,sex,phone')->find();
        $pageData['age'] = $pageData['age'].'';
        
        //关注量
        $wh_subscribe = array('member_id'=>$member_id,'status'=>1);
        $pageData['subscribe_total'] = Db::name('member_subscribe')->where($wh_subscribe)->count();
        //粉丝量
        $wh_fans = array('subscribe_id'=>$member_id,'status'=>1);
        $pageData['fans_total'] = Db::name('member_subscribe')->where($wh_fans)->count();
        //点赞量
        $wh_loves = array('fans_id'=>$member_id,'status'=>1);
        $pageData['loves_total'] = Db::name('member_loves')->where($wh_loves)->count();

        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 4.3我的信息编辑
    public function editInfo(){
        $member_id = $this->getMemberId();
        $name = $this->request->post('name');
        $headimg = $this->request->post('headimg');
        $country = $this->request->post('country');
        $province = $this->request->post('province');
        $city = $this->request->post('city');
        $county = $this->request->post('county');
        $address = $this->request->post('address');
        $age = $this->request->post('age');
        $sex = $this->request->post('sex');
        $data = array();
        if( !empty($name) ){
            $data['name'] = $name;
            $data['name'] = filter_Emoji($data['name']);
        }
        if( !empty($headimg) ){
            $data['headimg'] = $headimg;
        }
        if( !empty($country) ){
            $data['country'] = $country;
        }
        if( !empty($province) ){
            $data['province'] = $province;
        }
        if( !empty($city) ){
            $data['city'] = $city;
        }
        if( !empty($county) ){
            $data['county'] = $county;
        }
        if( !empty($address) ){
            $data['address'] = $address;
        }
        if( !empty($age) ){
            $data['age'] = $age;
        }
        if( !empty($sex) ){
            $data['sex'] = $sex;
        }
        if( empty($data) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'数据丢失');
        }
        $data['time_modify'] = time();
        $rst = Db::name('member_info')->where('id',$member_id)->update($data);
        if( !$rst ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'修改失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'修改成功');
    }

    // 4.4 我的发布
    public function release(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',10,'int');
        $member_id = $this->getMemberId();

        $where = array('author_id'=>$member_id);
        $offset = (int)$pagesize*($page-1);
        $total = Db::name('community_info')->where('status','>',0)->where($where)->count();
        $pageData['list'] = Db::name('community_info')->field('id,type,title,content,imgs,audio_video as video')->where('status','>',0)->where($where)->order('time_create','desc')->limit($offset,$pagesize)->select();
        foreach ($pageData['list'] as $k => $v) {
            $imgs_arr = explode(',', $v['imgs']);
            $pageData['list'][$k]['img'] = isset($imgs_arr[0]) ? $imgs_arr[0] : '';
            unset($pageData['list'][$k]['imgs']);
            $pageData['list'][$k]['img_width'] = 300;
            $pageData['list'][$k]['img_height'] = 500;

            $hw = getImgHw( $pageData['list'][$k]['img'] );
            if( isset($hw['width']) && isset($hw['height']) ){
                $pageData['list'][$k]['img_width'] = $hw['width'];
                $pageData['list'][$k]['img_height'] = $hw['height'];
            }

            $pageData['list'][$k]['video'] = !empty($pageData['list'][$k]['video']) ?  getImgUrl($pageData['list'][$k]['video']) : '';
            $pageData['list'][$k]['img'] = !empty($pageData['list'][$k]['img']) ?  getImgUrl($pageData['list'][$k]['img']) : '';
            $pageData['list'][$k]['title'] = userTextDecode($v['title']);
        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 4.5我的点赞
    public function loves(){
        $attributes = $this->request->post('attributes',1,'int');//1:文章，2帖子，31文章评论 32帖子评论
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',10,'int');
        $member_id = $this->getMemberId();

        if( !in_array($attributes, array(1,2,3,31,32)) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数attributes有误');
        }
        switch ($attributes) {
            case '1':
                // 文章点赞
                $where = array('member_id'=>$member_id,'l.status'=>1,'comment_id'=>0);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('news_loves')->alias('l')->where($where)->count();
                $pageData['list'] = Db::name('news_loves')->alias('l')
                                    ->join(array(config('database.prefix').'news_info'=>'a'),'l.news_id=a.id')
                                    ->field('l.news_id as news_id,a.type as type,a.title as title,a.content as content,a.imgs as imgs,a.time_create as time_create')/*l.id as id,*/
                                    ->where($where)->order('l.time_create','desc')
                                    ->limit($offset,$pagesize)->select();
                break;
            case '2':
                // 帖子点赞
                $where = array('member_id'=>$member_id,'l.status'=>1,'comment_id'=>0);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('community_loves')->alias('l')->where($where)->count();
                $pageData['list'] = Db::name('community_loves')->alias('l')
                                    ->join(array(config('database.prefix').'community_info'=>'a'),'l.community_id=a.id')->field('l.community_id as community_id,a.type as type,a.title as title,a.content as content,a.imgs as imgs,a.audio_video as video,a.time_create as time_create')/*l.id as id,*/
                                    ->where($where)->order('l.time_create','desc')
                                    ->limit($offset,$pagesize)->select();
                break;
            case '3':
                //评论点赞：
                // 文章评论
                $where = array('l.member_id'=>$member_id,'l.status'=>1);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('news_loves')->alias('l')->where($where)->where('l.comment_id','>',0)->count();
                $news = Db::name('news_loves')->alias('l')
                                    ->join(array(config('database.prefix').'news_comment'=>'a'),'l.comment_id=a.id')->field('l.news_id as news_id,l.comment_id as comment_id,a.comment as comment,a.time_create as time_create')/*l.id as id,*/
                                    ->where($where)->where('l.comment_id','>',0)
                                    ->order('l.time_create','desc')
                                    ->limit($offset,$pagesize)->select();
                // 帖子评论
                $where = array('l.member_id'=>$member_id,'l.status'=>1);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('community_loves')->alias('l')->where($where)->where('l.comment_id','>',0)->count();
                $community = Db::name('community_loves')->alias('l')
                                    ->join(array(config('database.prefix').'community_comment'=>'a'),'l.comment_id=a.id')
                                    ->field('l.community_id as community_id,l.comment_id as comment_id,a.comment as comment,a.time_create as time_create')/*l.id as id,*/
                                    ->where($where)->where('l.comment_id','>',0)
                                    ->order('l.time_create','desc')
                                    ->limit($offset,$pagesize)->select();

                $pageData['list'] = array();
                foreach ($news as $k => $v) {
                    $v['comment_type'] = 1;//文章
                    $pageData['list'][] = $v;
                }
                foreach ($community as $k => $v) {
                    $v['comment_type'] = 2;//帖子
                    $pageData['list'][] = $v;
                }
                break;
            case '31':
                // 文章评论点赞
                $where = array('l.member_id'=>$member_id,'l.status'=>1);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('news_loves')->alias('l')->where($where)->where('l.comment_id','>',0)->count();
                $pageData['list'] = Db::name('news_loves')->alias('l')
                                    ->join(array(config('database.prefix').'news_comment'=>'a'),'l.comment_id=a.id and a.status=2')->field('l.news_id as news_id,l.comment_id as comment_id,a.comment as comment,a.time_create as time_create')/*l.id as id,*/
                                    ->where($where)->where('l.comment_id','>',0)
                                    ->order('l.time_create','desc')
                                    ->limit($offset,$pagesize)->select();
                break;
            case '32':
                // 帖子评论点赞
                $where = array('l.member_id'=>$member_id,'l.status'=>1);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('community_loves')->alias('l')->where($where)->where('l.comment_id','>',0)->count();
                $pageData['list'] = Db::name('community_loves')->alias('l')
                                    ->join(array(config('database.prefix').'community_comment'=>'a'),'l.comment_id=a.id and a.status=2')
                                    ->field('l.id as id,l.community_id as community_id,l.comment_id as comment_id,a.comment as comment,a.time_create as time_create')
                                    ->where($where)->where('l.comment_id','>',0)
                                    ->order('l.time_create','desc')
                                    ->limit($offset,$pagesize)->select();
                break;
            default:
                $pageData['list'] = array();
                break;
        }

        foreach ($pageData['list'] as $k => $v) {
            $pageData['list'][$k]['img_width'] = 300;
            $pageData['list'][$k]['img_height'] = 500;
            if(isset($v['imgs'])){
                $hw = getImgHw( $v['imgs'] );
                if( isset($hw['width']) && isset($hw['height']) ){
                    $pageData['list'][$k]['img_width'] = $hw['width'];
                    $pageData['list'][$k]['img_height'] = $hw['height'];
                }

                $imgs_arr = explode(',', $v['imgs']);
                $pageData['list'][$k]['img'] = isset($imgs_arr[0]) ? $imgs_arr[0] : '';
                unset($pageData['list'][$k]['imgs']);

                $pageData['list'][$k]['img'] = !empty($pageData['list'][$k]['img']) ?  getImgUrl($pageData['list'][$k]['img']) : '';
            }

            if( !empty($v['video']) ){
                $pageData['list'][$k]['video'] = getImgUrl($v['video']);
            }

            if( !empty($v['comment']) ){
                $pageData['list'][$k]['comment'] = userTextDecode($v['comment']);
            }
            if( !empty($v['title']) ){
                $pageData['list'][$k]['title'] = userTextDecode($v['title']);
            }


        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 4.6我的评论
    public function comment(){
        $attributes = $this->request->post('attributes',1,'int');//1文章，2帖子
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',10,'int');
        $member_id = $this->getMemberId();
        if( !in_array($attributes, array(1,2)) ){
            ajaxReturn(MSG_PARAM_VALID_CODE,'参数attributes有误');
        }
        switch ($attributes) {
            case '2':
                $where = array('member_id'=>$member_id,'comment_id'=>0);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('community_comment')->alias('c')->where($where)->where('c.status','>',0)->count();
                $pageData['list'] = Db::name('community_comment')->alias('c')
                                    ->join(array(config('database.prefix').'community_info'=>'a'),'c.community_id=a.id')
                                    ->field('c.id as id,c.community_id as community_id,c.comment as comment,a.type as type,a.title as title,a.content as content,a.imgs as imgs,a.audio_video as video')
                                    ->where($where)->where('c.status','>',0)->order('c.time_create','desc')->limit($offset,$pagesize)->select();
                break;
            
            default:
                $where = array('member_id'=>$member_id,'comment_id'=>0);
                $offset = (int)$pagesize*($page-1);
                $total = Db::name('news_comment')->alias('c')->where($where)->where('c.status','>',0)->count();
                $pageData['list'] = Db::name('news_comment')->alias('c')
                                    ->join(array(config('database.prefix').'news_info'=>'a'),'c.news_id=a.id')
                                    ->field('c.id as id,c.news_id as news_id,c.comment as comment,a.type as type,a.title as title,a.content as content,a.imgs as imgs')
                                    ->where($where)->where('c.status','>',0)->order('c.time_create','desc')->limit($offset,$pagesize)->select();
                break;
        }
        foreach ($pageData['list'] as $k => $v) {
            $pageData['list'][$k]['img_width'] = 300;
            $pageData['list'][$k]['img_height'] = 500;
            $imgs_arr = explode(',', $v['imgs']);
            $pageData['list'][$k]['img'] = isset($imgs_arr[0]) ? $imgs_arr[0] : '';
            unset($pageData['list'][$k]['imgs']);

            $pageData['list'][$k]['img'] = !empty($pageData['list'][$k]['img']) ?  getImgUrl($pageData['list'][$k]['img']) : '';

            if($pageData['list'][$k]['img']){
                $hw = getImgHw( $pageData['list'][$k]['img'] );
                if( isset($hw['width']) && isset($hw['height']) ){
                    $pageData['list'][$k]['img_width'] = $hw['width'];
                    $pageData['list'][$k]['img_height'] = $hw['height'];
                }
            }
            if( !empty($v['video']) ){
                $pageData['list'][$k]['video'] = getImgUrl($v['video']);
            }
            
            $pageData['list'][$k]['content'] = $pageData['list'][$k]['title'];

            if( !empty($v['comment']) ){
                $pageData['list'][$k]['comment'] = userTextDecode($v['comment']);
            }
        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 4.7我的通知消息
    public function notice(){
        $member_id = $this->getMemberId();
        // $member_id = 1;
        $where = array('member_id'=>$member_id,'status'=>1);
        $pageData['list'] = Db::name('notice_info')->alias('n')
                            ->join(array(config('database.prefix').'member_info'=>'m'),'n.operator=m.id')
                            ->field('n.*,m.name as author_name,m.headimg as author_headimg')->where($where)
                            ->order('n.datetime','desc')->select();
        $love_type = array(11,12,21,22);
        $comment_type = array(13,14,23,24);
        // 类型：11资讯点赞，12资讯评论点赞 ，13资讯评论，14资讯评论回复；
        //       21社区点赞，22社区评论点赞, 23社区评论， 24社区评论回复
        foreach ($pageData['list'] as $k => $v) {
            $pageData['list'][$k]['type_old'] = $v['type'];
            if( in_array($v['type'], $love_type) ){
                $pageData['list'][$k]['type'] = 1;
            }
            if( in_array($v['type'], $comment_type) ){
                $pageData['list'][$k]['type'] = 2;
            } 
        }
        //更新状态
        Db::name('notice_info')->where($where)->update( array('time_modify'=>time(),'status'=>2) );
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }


    
    // 4.8 新增反馈建议
    public function addfeedback(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $imgs = $this->request->post('imgs','');
        $content = $this->request->post('content','');
        if( empty($content) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'内容不能为空');
        }

        $data = array(
            'member_id'=>$member_id,
            'imgs'=>$imgs,
            'content'=>$content,
            'status'=>1,
            'time_create'=>time(),
        );
        //标签转义
        $data['content'] = userTextEncode($data['content']);
        $rst = Db::name('feedback')->insert($data);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 4.9 我的订阅
    public function messageSubscribe(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $channel = $this->request->post('channel');//消息推送渠道
        $device_token = $this->request->post('device_token');
        $device_type = $this->request->post('device_type');//设备类型：1安卓，2苹果
        $subscribe = $this->request->post('subscribe',0);
        if( empty($device_token) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'device_token不能为空');
        }
        if( !in_array($device_type, array(1,2)) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'device_type不正确');
        }
        //检查
        $where = array(
            'member_id' => $member_id,
            'device_type'=>$device_type,
            'device_token'=>$device_token,
        );
        $chk = Db::name('message_subscribe')->where($where)->find();
        if( empty($chk) ){
            $data = array(
                'member_id'=>$member_id,
                'channel'=>$channel,
                'device_token'=>$device_token,
                'device_type'=>$device_type,
                'subscribe'=>$subscribe,
                'time_modify'=>time(),
                'time_create'=>time(),
            );
            $rst = Db::name('message_subscribe')->insert($data);
        }
        else{
            $up = array(
                'time_modify' =>time(),
            );
            $rst = Db::name('message_subscribe')->where($where)->update($up);
        }
        
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败');
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }
}