<?php
/**
 * 图文管理收藏Service
 * Author: wangchen
 * Date Time: 2021/5/24
 */

namespace app\atlas\model\service;

use app\atlas\model\db\AtlasArticleCollect;

class AtlasArticleCollectService {
    public $atlasArticleCollectModel = null;
    public function __construct()
    {
        $this->atlasArticleCollectModel = new AtlasArticleCollect();
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
            ['status', '=', '0']
        ];
        $followed = $this->atlasArticleCollectModel->getOptionOne($where);
        $sucessMsg = '';
        $errorMsg = '';
        if($followed){ // 已收藏取消收藏
            $sucessMsg = L_('取消收藏成功');
            $errorMsg = L_('取消收藏失败');
            $res = $this->atlasArticleCollectModel->collectArticleEdit($where,['status' => 1]);
        }else{
            $sucessMsg = L_('收藏成功');
            $errorMsg = L_('收藏失败');
            $savaData = [
                'uid' => $uid,
                'article_id' => $articleId,
            ];
            $wheres = [
                ['uid', '=', $uid ],
                ['article_id', '=', $articleId ],
                ['status', '=', '1']
            ];
            $followeds = $this->atlasArticleCollectModel->getOptionOne($wheres);
            if($followeds){
                $res = $this->atlasArticleCollectModel->collectArticleEdit($wheres,['status' => 0]);
            }else{
                $res = $this->atlasArticleCollectModel->collectArticleAdd($savaData);
            }
        }
        if($res === false){
            throw new \think\Exception($errorMsg, 1003);
        }
        return ['msg' =>$sucessMsg ];
    }

    /**
     * 获得用户收藏图文数量
     * @param array $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->atlasArticleCollectModel->alias('c')
            ->join('atlas_article a', 'a.id = c.article_id')
            ->where($where)
            ->count();
        return $count;
    }

    //获取图文收藏列表
    public function getCollectList($where, $field = true, $order = ['c.id' => 'DESC'], $page = 1, $pageSize = 10)
    {
        $lists = $this->atlasArticleCollectModel->alias('c')
            ->join('atlas_article a', 'a.id = c.article_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->page($page, $pageSize)
            ->select()
            ->each(function ($item) {
                if (isset($item['pic'])) {
                    $item['pic'] =  replace_file_domain($item['pic']);
                }
                return $item;
            })
            ->toArray();
        return $lists;
    }
}