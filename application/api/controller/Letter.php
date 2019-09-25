<?php
/**
 * 私信
 * author: wuyi
 * Date: 2019-6-17
 */
namespace app\api\controller;
use think\Db;
class Letter extends Base {
    // 9.1 获取私信信息
    public function index(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $page = $this->request->post('page',1,'int');
        $pagesize = $this->request->post('pagesize',20,'int');
        $fans_id = $this->request->post('fans_id',0,'int');
        if( empty($fans_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'粉丝ID不能为空');
        }
        $map1 = array(
                array('from_id', '=', $member_id),
                array('receive_id', '=', $fans_id),
                array('status','in','1,2'),
            );
            
        $map2 = array(
                array('from_id', '=', $fans_id),
                array('receive_id', '=', $member_id),
                array('status','in','1,2'),
            );    
        $offset = (int)$pagesize*($page-1);
        $total = Db::name('member_letter')->whereOr( array($map1, $map2) )->count();
        $pageData['datalist'] = Db::name('member_letter')->alias('s')
                                ->join(array(config('database.prefix').'member_info'=>'f'),'s.from_id=f.id')
                                ->join(array(config('database.prefix').'member_info'=>'r'),'s.receive_id=r.id')
                                ->field('s.*,f.name as from_name,f.headimg as from_headimg,r.name as receive_name,r.headimg as receive_headimg')
                                ->whereOr( array($map1, $map2) )
                                ->order(['s.time_create'=>'desc','id'=>'asc'])
                                ->limit($offset,$pagesize)
                                ->select();
        foreach ($pageData['datalist'] as $k => $v) {
            $pageData['datalist'][$k]['is_self'] = $v['from_id']==$member_id ? 1 : 2;
            $content = json_decode($v['content'],true);
            if( !empty($content) && !empty($content['type']) ){
                switch ($content['type']) {
                    case 'text':
                        $content['content'] = userTextDecode($content['content']);
                        break;
                    case 'img':
                        $content['content'] = getImgUrl($content['content']);
                        break;
                    default:
                        # code...
                        break;
                } 
            }else{
                ajaxReturn(MSG_QUERY_FAIL_CODE,'数据出错了',$v['content']);
            }
            $pageData['datalist'][$k]['content'] = $content;
        }
        $pageData['current_page']  = $page;
        $pageData['total_page']  = ceil($total/$pagesize);
        $pageData['total_nums']  = $total;
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$pageData);
    }

    // 9.2 发送私信信息
    public function send(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'未登录账号');
        }
        $fans_id = $this->request->post('fans_id',0,'int');
        $content = $this->request->post('content','');
        $type = $this->request->post('type','text');//img,text
        $content = !empty($content) ? userTextEncode($content) : '';//表情处理

        if( empty($fans_id) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'粉丝ID不能为空');
        }
        if( empty($content) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'内容不能为空');
        }
        $type_arr = array('img','text');
        if( !in_array($type, $type_arr) ){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'内容类型不正确',$type);
        }
        $content_arr  = array('type' =>$type,'content'=>$content );

        $data = array(
            'from_id'=>$member_id,
            'receive_id'=>$fans_id,
            'content'=>json_encode($content_arr),
            'status'=>1,
            'time_modify'=>time(),
            'time_create'=>time(),
        );
        
        $rst = Db::name('member_letter')->insert($data);
        if(!$rst){
            ajaxReturn(MSG_QUERY_FAIL_CODE,'操作失败',$status);
        }
        ajaxReturn(MSG_SUCCESS_CODE,'ok');
    }

}