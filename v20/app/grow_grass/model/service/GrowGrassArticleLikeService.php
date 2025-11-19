<?php
/**
 * 种草文章点赞
 * Author: hengtingmei
 * Date Time: 2021/5/15 14:20
 */

namespace app\grow_grass\model\service;
use app\grow_grass\model\db\GrowGrassArticle;
use app\grow_grass\model\db\GrowGrassArticleLike;
class GrowGrassArticleLikeService {
    public $growGrassArticleLikeModel = null;
    public function __construct()
    {
        $this->growGrassArticleLikeModel = new GrowGrassArticleLike();
    }
    
    /**
    * 点赞 取消点赞
    * @param int $uid 用户id
    * @param int $articleId 文章ID
    * @return bool
    */
    public function likeArticle($uid, $articleId){
        if(empty($uid) || empty($articleId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        
        $where = [
            ['uid', '=', $uid ],
            ['article_id', '=', $articleId ],
            /*['is_del', '=', '0']*/
        ];
        $followed = $this->getOne($where);
        $where1= [
            ['article_id', '=', $articleId ],
        ];
        $sucessMsg = '';
        $errorMsg = '';
        if($followed){ // 已点赞取消点赞
            $followed=$followed->toArray();
            if($followed['is_del']==0){//取消点赞
                $sucessMsg = L_('取消点赞成功');
                $errorMsg = L_('取消点赞失败');
                $res = $this->updateThis($where,['is_del' => 1]);
            }else{//点赞
                $sucessMsg = L_('点赞成功');
                $errorMsg = L_('点赞失败');
                $res = $this->updateThis($where,['is_del' => 0]);
            }

        }else{
            $sucessMsg = L_('点赞成功');
            $errorMsg = L_('点赞失败');
            $savaData = [
                'uid' => $uid,
                'article_id' => $articleId,
            ];
            $res = $this->add($savaData);
        }
        if($res === false){
            throw new \think\Exception($errorMsg, 1003);
        }

        //同步文章点赞数
        (new GrowGrassArticleService())->syncLikeNum($articleId);
       
        return ['msg' =>$sucessMsg ];
    }

    /**
    * 查看用户是否点赞文章
    * @param int $uid 用户id
    * @param array $articleArr 文章id数组eg:[1,2]
    * @return array
    */
    public function getLikedList($uid, $articleArr){
        if(empty($uid) || empty($articleArr)){
            return [];
        }

        $where = [
            ['uid', '=', $uid ],
            ['article_id', 'in', implode(',', $articleArr) ],
            ['is_del', '=', '0']
        ];
        $list = array_column($this->getSome($where),'article_id','article_id') ;
        $returnArr = [];
        foreach($articleArr as $id){
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
        $result = $this->growGrassArticleLikeModel->getOne($where);
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
        $result = $this->growGrassArticleLikeModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->growGrassArticleLikeModel->getCount($where);
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
        $result = $this->growGrassArticleLikeModel->add($data);
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

        $result = $this->growGrassArticleLikeModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}