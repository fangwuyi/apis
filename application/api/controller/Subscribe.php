<?php
/**
 * 关注
 * author: wuyi
 * Date: 2019-3-13
 */
namespace app\api\controller;
use think\Db;
class Subscribe extends Base {
    // 6.1 我的关注
    public function index(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $member_id = $this->getMemberId();
        $where = array(
            'status'=>1,
            'member_id'=>$member_id,
        );
        $offset = (int)$pagesize*($page-1);
        $total = Db::name('member_subscribe')->where($where)->count();

        $pageData['datalist'] = Db::name('member_subscribe')->alias('s')
                                ->join(array(config('database.prefix').'member_info'=>'m'),'s.subscribe_id=m.id')
                                ->field('s.id as id,s.time_create as time_create,m.id as member_id,m.name as member_name,m.headimg as member_headimg')
                                ->where($where)
                                ->order(['s.time_create'=>'desc','id'=>'desc'])
                                ->limit($offset,$pagesize)
                                ->select();
        foreach ($pageData['datalist'] as $k => $v) {
            $pageData['datalist'][$k]['is_subscribe'] = 1;
        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        $pageData['total_nums']  = $total;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }
    // 6.2 我的粉丝
    public function fans(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $member_id = $this->getMemberId();
        $where = array(
            'status'=>1,
            'subscribe_id'=>$member_id,
        );
        $offset = (int)$pagesize*($page-1);
        $total = Db::name('member_subscribe')->where($where)->count();
        $pageData['datalist'] = Db::name('member_subscribe')->alias('s')
                                ->join(array(config('database.prefix').'member_info'=>'m'),'s.member_id=m.id')
                                ->field('s.id as id,s.subscribe_id as subscribe_id,s.time_create as time_create,m.id as member_id,m.name as member_name,m.headimg as member_headimg')
                                ->where($where)
                                ->order(['s.time_create'=>'desc','id'=>'desc'])
                                ->limit($offset,$pagesize)
                                ->select();
        foreach ($pageData['datalist'] as $k => $v) {
            $wh = array('status'=>1,'subscribe_id'=>$v['member_id'],'member_id'=>$member_id);//是否关注
            $pageData['datalist'][$k]['is_subscribe'] = Db::name('member_subscribe')->where($wh)->count();
        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        $pageData['total_nums']  = $total;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 6.3 添加或取消关注
    public function addsubscribe(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $status = $this->request->post('status',1);
        if( !in_array($status, array(1,2)) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数status不合法');
        }
        $subscribe_id = $this->request->post('subscribe_id',0,'int');
        if( empty($subscribe_id) || $member_id==$subscribe_id ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数subscribe_id不合法');
        }
        $where = array('member_id'=>$member_id,'subscribe_id'=>$subscribe_id);
        $chk = Db::name('member_subscribe')->where($where)->find();
        if( !empty($chk) ){
            if($chk['status']==$status){
                ajaxReturn(MSG_QUERY_FAIL_CODE,'请勿重复操作',$status);
            }
            $up = array('status'=>$status,'time_modify'=>time());
            $rst = Db::name('member_subscribe')->where($where)->update($up);
        }else{
            $status = 1;
            $data = array(
                'member_id'=>$member_id,
                'subscribe_id'=>$subscribe_id,
                'status'=>$status,
                'time_create'=>time(),
            );
            $rst = Db::name('member_subscribe')->insert($data);
        }
        
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败',$status);
        }
        ajaxReturn(MSG_SUCCESS_CODE,'操作成功');
    }

    // 6.4 粉丝详情
    public function fansinfo(){
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $fans_id = $this->request->post('fans_id',0);
        if( empty($fans_id) ){
            ajaxReturn(MSG_PARAM_MISS_CODE,'参数fans_id不合法');
        }
        $wh_member = array('id'=>$fans_id);
        $pageData['fansinfo'] = Db::name('member_info')->where($wh_member)
                ->field('id,name,headimg,sex,country,province,city,county,address,age,time_create')
                ->find();
        //关注量
        $wh_subscribe = array('member_id'=>$fans_id,'status'=>1);
        $pageData['subscribe_total'] = Db::name('member_subscribe')->where($wh_subscribe)->count();
        //粉丝量
        $wh_fans = array('subscribe_id'=>$fans_id,'status'=>1);
        $pageData['fans_total'] = Db::name('member_subscribe')->where($wh_fans)->count();
        //点赞量
        $wh_loves = array('fans_id'=>$fans_id,'status'=>1);
        $pageData['loves_total'] = Db::name('member_loves')->where($wh_loves)->count();
        //是否关注
        $wh_issubscribe = array('member_id'=>$member_id,'subscribe_id'=>$fans_id,'status'=>1);
        $is_subscribe = Db::name('member_subscribe')->where($wh_issubscribe)->count();
        $pageData['is_subscribe'] = $is_subscribe ? 1: 0;
        //帖子
        $wh_community = array('author_id'=>$fans_id,'status'=>1);
        $pageData['community_total'] = Db::name('community_info')->where($wh_community)->count();

        $offset = (int)$pagesize*($page-1);
        $pageData['community_list'] = Db::name('community_info')
                                      ->field('id,type,title,imgs,audio_video,content')
                                      ->order(['time_create'=>'desc','id'=>'desc'])
                                      ->limit($offset,$pagesize)
                                      ->where($wh_community)->select();
        foreach ($pageData['community_list'] as $k => $v) {
            $pageData['community_list'][$k]['img_width'] = 300;
            $pageData['community_list'][$k]['img_height'] = 600;
            // 根据图片链接得到图片宽高
            $img_one = explode(',', $v['imgs']);
            if( isset($img_one[0]) && !empty($img_one[0]) ){
                $hw = getImgHw( $img_one[0] );
                if( isset($hw['width']) && isset($hw['height']) ){
                    $pageData['community_list'][$k]['img_width'] = $hw['width'];
                    $pageData['community_list'][$k]['img_height'] = $hw['height'];
                }
            }

            if( !empty($v['audio_video']) ){
                $hw = getImgHw( $v['audio_video'] );
                if( isset($hw['width']) && isset($hw['height']) ){
                    $pageData['community_list'][$k]['img_width'] = $hw['width'];
                    $pageData['community_list'][$k]['img_height'] = $hw['height'];
                }
            }

            //对图片进行处理
            $images_arr = array();
            foreach ($img_one as $images) {
                if( !empty($images) ){
                    $images_arr[] = getImgUrl($images);
                }
            }
            $pageData['community_list'][$k]['imgs'] = implode(',', $images_arr);//【拼接回来
            $pageData['community_list'][$k]['audio_video'] = !empty($v['audio_video']) ? getImgUrl($v['audio_video']) : '';
            $pageData['community_list'][$k]['title'] = userTextDecode($v['title']);

        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($pageData['community_total']/$pagesize);

        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

}