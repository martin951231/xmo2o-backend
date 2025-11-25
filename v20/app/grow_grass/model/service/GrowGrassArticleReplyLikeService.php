<?php
/**
 * 种草文章点赞
 * Author: hengtingmei
 * Date Time: 2021/5/15 14:20
 */

namespace app\grow_grass\model\service;
use app\grow_grass\model\db\GrowGrassArticleReplyLike;
class GrowGrassArticleReplyLikeService {
    public $growGrassArticleReplyLikeModel = null;
    public function __construct()
    {
        $this->growGrassArticleReplyLikeModel = new GrowGrassArticleReplyLike();
    }
    
    /**
    * 点赞 取消点赞
    * @param int $uid 用户id
    * @param int $replyId 评论ID
    * @return bool
    */
    public function likeReply($uid, $replyId){
        if (empty($uid) || empty($replyId)) {
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $where = [['uid', '=', $uid], ['reply_id', '=', $replyId]];
        $followed = $this->getOne($where);

        $tip = L_('点赞成功');;
        if ($followed) {
            if ($followed['is_del'] == 0) {
                //取消点赞
                $res = $this->updateThis($where, ['is_del' => 1]);
                $tip = L_('取消点赞成功');
            } else {
                //点赞
                $res = $this->updateThis($where, ['is_del' => 0]);
            }
        } else {
            $savaData = ['uid' => $uid, 'reply_id' => $replyId, 'is_del' => 0];
            $res = $this->add($savaData);
        }
        if ($res === false) {
            throw new \think\Exception(L_('点赞失败'), 1003);
        }

        //同步评论数
        (new GrowGrassArticleReplyService())->syncLikeNum($replyId);
        return ['msg' => $tip];
    }

    /**
    * 查看用户是否点赞评论
    * @param int $uid 用户id
    * @param array $replyIdArr 评论id数组eg:[1,2]
    * @return array
    */
    public function getReplyLikedList($uid, $replyIdArr){
        if(empty($uid) || empty($replyIdArr)){
            return [];
        }

        $where = [
            ['uid', '=', $uid ],
            ['reply_id', 'in', implode(',', $replyIdArr) ],
            ['is_del', '=', '0']
        ];
        $list = array_column($this->getSome($where),'reply_id','reply_id') ;
        $returnArr = [];
        foreach($replyIdArr as $id){
            if(isset($list[$id])){
                // 已点赞
                $returnArr[$id] = true;
            }else{
                
                $returnArr[$id] = false;
            }
        }
        return $returnArr;
        
    }

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->growGrassArticleReplyLikeModel->getOne($where);
        if(!$result) {
            return [];
        }
        return $result;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->growGrassArticleReplyLikeModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->growGrassArticleReplyLikeModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
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
        $result = $this->growGrassArticleReplyLikeModel->add($data);
        if(empty($result)) return false;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->growGrassArticleReplyLikeModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}