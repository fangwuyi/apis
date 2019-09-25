<?php
/**
 * 公共类
 * author: wuyi
 * Date: 2019-3-12
 */

namespace app\api\controller;

use think\Controller;
use think\Db;
class Base extends Controller{
    public function __construct(){
        parent::__construct();

        // 检查登录
        $controller_name = request()->controller();
        $action_name = request()->action();
        if ( in_array($controller_name.'.'.$action_name,$this->needAuth) || in_array($controller_name.'.*',$this->needAuth) ) {
            $this->checkToken();
        }
        
    }

    //需要授权方法
    private $needAuth = array(
        'My.*',
        'Index.loves','Index.addcomment','Index.delcomment',
        'Community.loves','Community.addcomment','Community.delcomment','Community.release',
        'Community.addlabel','Community.dellabel','Community.getlabel',
        'Common.getaiurl',
    );

    //检查token
    protected function checkToken(){
        $token = $this->request->param('token');
        if( empty($token) ){
            ajaxReturn(MSG_LOGIN_EXPIRED,'token丢失');
        }
        $token_arr = tokenDecode($token);
        if( empty($token_arr['str']) ){
            ajaxReturn($token_arr['errcode'],$token_arr['errmsg']);
        }
        return $token_arr['str'];
    }

    //获取用户ID
    protected function getMemberId(){
        $token = $this->request->param('token');
        if( empty($token) ){
            return '';
        }
        $token_arr = tokenDecode($token);
        if( empty($token_arr['str']) ){
            return null;
        }
       // return 6;//测试
        return !empty($token_arr['str']) ? $token_arr['str']: 0;
    }
    
    // 刷新token
    public function refreshToken(){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            ajaxReturn(MSG_TOKEN_VALID,'token校验失败');
        }
        $token = tokenEncode($member_id);
        ajaxReturn(MSG_SUCCESS_CODE,'ok',$token);
    }

    //增加通知 type: article_comment_loves,article_comment_comment,
    // community_loves,community_comment,community_comment_loves,community_comment_comment,
    protected function addnotice( $act,$data ){
        $member_id = $this->getMemberId();
        if( empty($member_id) ){
            return 1000;
        }
        // 类型：11资讯点赞，12资讯评论点赞 ，13资讯评论，14资讯评论回复；
        //       21社区点赞，22社区评论点赞, 23社区评论， 24社区评论回复
        switch ($act) {
            case 'article_comment_loves':
                //文章评论点赞:评论ID，
                if( empty($data['comment_id']) ){
                    return 100;
                }
                $comment = Db::name('news_comment')->where('status','>',0)->where('id',$data['comment_id'])->find();
                if( empty($comment) ){
                    return 101;
                }
                if($comment['member_id']==$member_id ){
                    //相同用户不需要
                    return 102;
                }
                $data = array(
                    'type'=>12,
                    'member_id'=>$comment['member_id'],
                    'status'=>1,
                    'time_modify'=>time(),
                    'datetime'=>time(),
                    'reply'=>'',
                    'comment'=>$comment['comment'],
                    'content'=>'',
                    'img'=>'',
                    'operator'=>$member_id,
                    'relate_id'=>$comment['news_id']//$data['comment_id'],
                );
                //检查是否重复消息
                $where = array(
                    'type'=>$data['type'],
                    'status'=>1,
                    'comment'=>$data['comment'],
                    'member_id'=>$data['member_id'],
                    'relate_id'=>$data['relate_id']
                );
                if( Db::name('notice_info')->where($where)->find() ){
                    return 0;
                }
                return Db::name('notice_info')->insert($data);
            break;

            case 'article_comment_comment':
                //文章评论回复:评论ID，
                if( empty($data['comment_id']) ){
                    return 100;
                }
                if( empty($data['comment']) ){
                    return 100;
                }
                $comment = Db::name('news_comment')->where('status','>',0)->where('id',$data['comment_id'])->find();
                if( empty($comment) ){
                    return 101;
                }
                if($comment['member_id']==$member_id ){
                    //相同用户不需要
                    return 102;
                }
                $data = array(
                    'type'=>14,
                    'member_id'=>$comment['member_id'],
                    'status'=>1,
                    'time_modify'=>time(),
                    'datetime'=>time(),
                    'reply'=>$data['comment'],
                    'comment'=>$comment['comment'],
                    'content'=>'',
                    'img'=>'',
                    'operator'=>$member_id,
                    'relate_id'=>$comment['news_id']//$data['comment_id'],
                );
                //检查是否重复消息
                $where = array(
                    'type'=>$data['type'],
                    'status'=>1,
                    'comment'=>$data['comment'],
                    'member_id'=>$data['member_id'],
                    'relate_id'=>$data['relate_id']
                );
                if( Db::name('notice_info')->where($where)->find() ){
                    return 0;
                }
                return Db::name('notice_info')->insert($data);
            break;

            case 'community_loves':
                //帖子点赞:
                if( empty($data['id']) ){
                    return 100;
                }
                $community = Db::name('community_info')->where('status','>',0)->where('id',$data['id'])->find();
                if( empty($community) ){
                    return 101;
                }
                if($community['author_id']==$member_id ){
                    //相同用户不需要
                    return 102;
                }
                $imgs_arr = explode(',', $community['imgs']);
                $data = array(
                    'type'=>21,
                    'member_id'=>$community['author_id'],
                    'status'=>1,
                    'time_modify'=>time(),
                    'datetime'=>time(),
                    'reply'=>'',
                    'comment'=>'',
                    'content'=>$community['title'],
                    'img'=>isset($imgs_arr[0]) ? $imgs_arr[0] : '',
                    'operator'=>$member_id,
                    'relate_id'=>$data['id'],
                );
               //检查是否重复消息
                $where = array(
                    'type'=>$data['type'],
                    'status'=>1,
                    'comment'=>$data['comment'],
                    'member_id'=>$data['member_id'],
                    'relate_id'=>$data['relate_id']
                );
                if( Db::name('notice_info')->where($where)->find() ){
                    return 0;
                }
                return Db::name('notice_info')->insert($data);
            break;

            case 'community_comment':
                //帖子评论:
                if( empty($data['id']) ){
                    return 100;
                }
                if( empty($data['comment']) ){
                    return 100;
                }
                $community = Db::name('community_info')->where('status','>',0)->where('id',$data['id'])->find();
                if( empty($community) ){
                    return 101;
                }
                if($community['author_id']==$member_id ){
                    //相同用户不需要
                    return 102;
                }
                $imgs_arr = explode(',', $community['imgs']);
                $data = array(
                    'type'=>23,
                    'member_id'=>$community['author_id'],
                    'status'=>1,
                    'time_modify'=>time(),
                    'datetime'=>time(),
                    'reply'=>$data['comment'],
                    'comment'=>'',
                    'content'=>$community['title'],
                    'img'=>isset($imgs_arr[0]) ? $imgs_arr[0] : '',
                    'operator'=>$member_id,
                    'relate_id'=>$data['id'],
                );
                //检查是否重复消息
                $where = array(
                    'type'=>$data['type'],
                    'status'=>1,
                    'comment'=>$data['comment'],
                    'member_id'=>$data['member_id'],
                    'relate_id'=>$data['relate_id']
                );
                if( Db::name('notice_info')->where($where)->find() ){
                    return 0;
                }
                return Db::name('notice_info')->insert($data);
            break;

            case 'community_comment_loves':
                //帖子评论点赞:
                if( empty($data['comment_id']) ){
                    return 100;
                }
                $comment = Db::name('community_comment')->where('status','>',0)->where('id',$data['comment_id'])->find();
                if( empty($comment) ){
                    return 101;
                }
                if($comment['member_id']==$member_id ){
                    //相同用户不需要
                    return 102;
                }
                $data = array(
                    'type'=>22,
                    'member_id'=>$comment['member_id'],
                    'status'=>1,
                    'time_modify'=>time(),
                    'datetime'=>time(),
                    'reply'=>'',
                    'comment'=>$comment['comment'],
                    'content'=>'',
                    'img'=>'',
                    'operator'=>$member_id,
                    'relate_id'=>$comment['community_id']//$data['comment_id'],
                );
                //检查是否重复消息
                $where = array(
                    'type'=>$data['type'],
                    'status'=>1,
                    'comment'=>$data['comment'],
                    'member_id'=>$data['member_id'],
                    'relate_id'=>$data['relate_id']
                );
                if( Db::name('notice_info')->where($where)->find() ){
                    return 0;
                }
                return Db::name('notice_info')->insert($data);
            break;
            
            case 'community_comment_comment':
                //帖子评论回复:
                if( empty($data['comment_id']) ){
                    return 100;
                }
                if( empty($data['comment']) ){
                    return 100;
                }
                $comment = Db::name('community_comment')->where('status','>',0)->where('id',$data['comment_id'])->find();
                if( empty($comment) ){
                    return 101;
                }
                if($comment['member_id']==$member_id ){
                    //相同用户不需要
                    return 102;
                }
                $data = array(
                    'type'=>24,
                    'member_id'=>$comment['member_id'],
                    'status'=>1,
                    'time_modify'=>time(),
                    'datetime'=>time(),
                    'reply'=>$data['comment'],
                    'comment'=>$comment['comment'],
                    'content'=>'',
                    'img'=>'',
                    'operator'=>$member_id,
                    'relate_id'=>$comment['community_id']//$data['comment_id'],
                );
                //检查是否重复消息
                $where = array(
                    'type'=>$data['type'],
                    'status'=>1,
                    'comment'=>$data['comment'],
                    'member_id'=>$data['member_id'],
                    'relate_id'=>$data['relate_id']
                );
                if( Db::name('notice_info')->where($where)->find() ){
                    return 0;
                }
                return Db::name('notice_info')->insert($data);
            break;

            default:
                # code...
                break;
        }
    }


}