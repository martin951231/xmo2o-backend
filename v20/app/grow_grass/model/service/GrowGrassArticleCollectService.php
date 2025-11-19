<?php
/**
 * 种草文章收藏
 * Author: hengtingmei
 * Date Time: 2021/5/17 10:56
 */

namespace app\grow_grass\model\service;
use app\grow_grass\model\db\GrowGrassArticle;
use app\grow_grass\model\db\GrowGrassArticleCollect;
class GrowGrassArticleCollectService {
    public $growGrassArticleCollectModel = null;
    public function __construct()
    {
        $this->growGrassArticleCollectModel = new GrowGrassArticleCollect();
    }
    
    /**
    * 收藏 取消收藏
    * @param int $uid 用户id
    * @param int $articleId 文章ID
    * @return bool
    */
    public function collectArticle($uid, $articleId){
        if(empty($uid) || empty($articleId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        
        $where = [
            ['uid', '=', $uid ],
            ['article_id', '=', $articleId ],
//            ['is_del', '=', '0']
        ];
        $followed = $this->getOne($where);

        $sucessMsg = '';
        $errorMsg = '';
        $where1=[['article_id','=',$articleId]];
        if(!empty($followed)){ // 已收藏取消收藏
            $followed=$followed->toArray();
            if($followed['is_del']){
                $sucessMsg = L_('收藏成功');
                $errorMsg = L_('收藏失败');
                $res = $this->updateThis($where,['is_del' => 0]);
                (new GrowGrassArticle())->setInc($where1,'collect_num');
            }else{
                $sucessMsg = L_('取消收藏成功');
                $errorMsg = L_('取消收藏失败');
                $savaData = [
                    'uid' => $uid,
                    'article_id' => $articleId,
                ];
                $res = $this->updateThis($where,['is_del' => 1]);
                (new GrowGrassArticle())->setDec($where1,'collect_num');
            }
        }else{
            $sucessMsg = L_('收藏成功');
            $errorMsg = L_('收藏失败');
            $savaData = [
                'uid' => $uid,
                'article_id' => $articleId,
            ];
            $res = $this->add($savaData);
            (new GrowGrassArticle())->setInc($where1,'collect_num');
        }
        if($res === false){
            throw new \think\Exception($errorMsg, 1003);
        }
       
        return ['msg' =>$sucessMsg ];
        
    }

    /**
    * 查看用户是否收藏文章
    * @param int $uid 用户id
    * @param array $articleArr 文章id数组eg:[1,2]
    * @return array
    */
    public function getCollectdList($uid, $articleArr){
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
                // 已收藏
                $returnArr[$id] = true;
            }else{
                
                $returnArr[$id] = false;
            }
        }
        return $returnArr;
        
    }

    

    /**
     * 获得用户收藏列表
     * @param array $where
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $pageSize
     * @return array
     */
	public function getCollectList($where, $field= true, $order = ['c.id'=>'DESC'], $page=1, $pageSize = 10){

        $articleList = $this->growGrassArticleCollectModel->getCollectList($where, $field, $order, $page, $pageSize);
        $articleList = $articleList ? $articleList->toArray() : [];
        $list = (new GrowGrassArticleService())->formatDataList($articleList);
		
		$return['list'] = $list ?: [];
		$return['page_size'] = $pageSize;

		return $return;
	}

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->growGrassArticleCollectModel->getOne($where);
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
        $result = $this->growGrassArticleCollectModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->growGrassArticleCollectModel->getCount($where);
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
        $result = $this->growGrassArticleCollectModel->add($data);
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

        $result = $this->growGrassArticleCollectModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}