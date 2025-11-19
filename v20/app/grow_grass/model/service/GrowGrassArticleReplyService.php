<?php


namespace app\grow_grass\model\service;


use app\grow_grass\model\db\GrowGrassArticleReply;
use app\grow_grass\model\db\GrowGrassArticleReplyLike;

class GrowGrassArticleReplyService
{
    public $growGrassArticleReplyModel = null;
    public function __construct()
    {
        $this->growGrassArticleReplyModel = new GrowGrassArticleReply();
    }
    /**
     * @param $param
     * @param $page
     * @param $order 排序
     * @param $pageSize
     * @return mixed
     * 评论列表
     */
    public function getCommentList($param=[],$order=[],$page=0,$pageSize=10){
        $where=[];

        if (isset($param['status']) && $param['status'] != "") {
            array_push($where, ['g.status', '=', $param['status']*1]);
        }

        if (isset($param['comment']) && !empty($param['comment'])) {
            array_push($where, ['g.content|u.nickname|u.phone', 'like', '%'.$param['comment'].'%']);
        }

        if (isset($param['article_id']) && !empty($param['article_id'])) {
            array_push($where, ['g.article_id', '=', $param['article_id']]);
        }

        $field="g.*,u.nickname,u.avatar,at.name,u.phone";
        $order['g.reply_id'] = 'desc';
        $list=(new GrowGrassArticleReply())->getCommentList($page,$pageSize,$where,$field,$order);
        if(!empty($list['list'])){
            foreach ($list['list'] as $k=>$v){
                if(empty($v['add_time'])){
                    $list['list'][$k]['add_time']="未知";
                }else{
                    $list['list'][$k]['add_time']=date("Y-m-d H:i:s",$v['add_time']);
                }
            }
        }
        return $list;
    }

    /**
     * 根据文章id获得评论列表
     * @param array $param
     */
    public function getCommentListByArticleId($param = []){
        $articleId = $param['article_id'] ?? 0;
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;

        // 当前登录的用户信息
        $user = request()->user;
        $uid = $user['uid'] ?? 0;

        $where = [
            'article_id' => $articleId,
            'status' => 1,
        ];
        $order['add_time'] = 'desc';
       
        $list= $this->getCommentList($where,$order,$page,$pageSize);
        
        if($list['list']){
            $likedList = [];
//            if($uid){// 查看是是否点赞了评论
//                $replyIdArr = array_column($list,'reply_id');
//                $likedList = (new GrowGrassArticleReplyLikeService())->getReplyLikedList($uid,$replyIdArr);
//            }
            foreach($list['list'] as &$reply){
                if($uid) {
                    $where=[['reply_id','=',$reply['reply_id']],['uid','=',$uid],['is_del','=',0]];
                    $is_like=(new GrowGrassArticleReplyLike())->getOne($where);
                    $reply['is_like'] =empty($is_like)?false:true;
                }else{
                    $reply['is_like'] =false;
                }
                $reply['avatar'] = $reply['avatar'] ?: cfg('site_url') . '/static/images/user-avatar.jpg';// 发布者头像
               /* $reply['is_like'] = isset($likedList[$reply['reply_id']]) && $likedList[$reply['reply_id']] ? true :false;
                $reply['add_time'] = date('Y-m-d',$reply['add_time'] );*/
            } 
        }
        return $list['list'];
    }

    /**
     * 添加评论
     * @param $articleId
     * @param $page
     * @param $order 排序
     * @param $pageSize
     * @return mixed
     * 评论列表
     */
    public function addReply($param = []){
        $data['article_id'] = $param['article_id'] ?? 0;
        $data['content'] = $param['content'] ?? 0;
        $data['add_time'] = time();
        $data['add_ip'] =request()->ip();
        $data['add_address'] = '';

        $address = (new \net\IpLocation())->getlocation($data['add_ip']);
        $address['country'] = iconv('GBK','UTF-8',$address['country']);
        $address['area'] = iconv('GBK','UTF-8',$address['area']);
        $data['add_address'] = $address['country'];

        // 当前登录的用户信息
        $user = request()->user;
        $uid = $user['uid'] ?? 0;
        if(empty($uid)){
            throw new \think\Exception(L_('未登录'),1002);
        }

        $data['uid'] = $uid;
        $data['status'] = cfg('grow_grass_audit_auto') ? 1 : 0;
        $res = $this->add($data);
        if($res === false){
            throw new \think\Exception(L_('评论失败'),1003);
        }
  
        return ['mag'=>L_('评论成功')];
    }

    

    /**
     *添加一条数据
     * @param $where array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }
        $result = $this->growGrassArticleReplyModel->add($data);
        if(empty($result)) return false;
        return $result;
    }

    public function getOneById($replyId)
    {
        return $this->growGrassArticleReplyModel->where('reply_id', '=', $replyId)->findOrEmpty()->toArray();
    }

    /**
     * 同步种草文章评论点赞数
     * @param $replyId
     * @date: 2021/06/11
     */
    public function syncLikeNum($replyId)
    {
        $count = (new GrowGrassArticleReplyLike())->where([['reply_id', '=', $replyId], ['is_del', '=', 0]])->count();
        return $this->growGrassArticleReplyModel->updateThis(['reply_id' => $replyId], ['like_num' => $count]);
    }

}