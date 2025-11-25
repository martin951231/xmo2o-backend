<?php
/**
 * 种草文章model
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:06
 */

namespace app\grow_grass\model\db;

use think\Model;

class GrowGrassArticle extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;


    public function getArticleListByCondition($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = $this->field($field)
            ->alias('a')
            ->leftJoin($prefix . 'user u', 'u.uid = a.uid')
            ->where($where)
            ->order($order);
        if ($limit) {
            $sql->page($page, $limit);
        }
        $result = $sql->select();
        return $result;
    }

    /**
     * @param $where
     * @return mixed
     * 查询店铺以及分类
     */
    public function getArticleListCategoryCondition($where, $field = true, $order = true, $page = 0, $limit = 0, $have = '')
    {
        // 表前缀
        if ($where == "") {
            $where = [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $sql = $this->field($field)
            ->alias('a')
            ->leftJoin($prefix . 'user u', 'u.uid = a.uid')
            ->whereRaw($where)
            ->order($order);
        if ($limit) {
            $sql->page($page, $limit);
        }

        if ($have) {
            $sql->having($have);
        }
        $result = $sql->select();
        return $result;
    }

    /**
     * @param $where
     * @return mixed
     * 查询店铺以及分类
     */
    public function getArticleListCategoryByUidCondition($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        // 表前缀
        if ($where == "") {
            $where = [];
        }
        $prefix = config('database.connections.mysql.prefix');
        $sql = $this->field($field)
            ->alias('a')
            ->join($prefix . 'user u', 'u.uid = a.uid')
            ->whereRaw($where)
            ->group('a.uid')
            ->order($order);
        if ($limit) {
            $sql->page($page, $limit);
        }
        $result = $sql->select();
        return $result;
    }

    /**
     * 发布列表
     */
    public function getArticleLists($where, $field, $order, $page, $pageSize)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->leftJoin($prefix . 'user b', 'a.uid=b.uid')
            ->leftJoin($prefix . 'grow_grass_category c', 'a.category_id=c.category_id')
            ->leftJoin($prefix . 'grow_grass_bind_store e', 'a.article_id=e.article_id')
            ->leftJoin($prefix . 'merchant_store d', 'e.store_id=d.store_id')
            ->GROUP('a.article_id,b.nickname,c.name,d.name')
            ->field($field)
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->select();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        }
        return $arr;
    }
    /**
     * @param $param
     * 最近使用
     */
    public function getMyArticleList($where, $field, $order, $page, $pageSize,$group)
    {
        $arr = $this
            ->field($field)
            ->where($where)
            ->group($group)
            ->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $arr;
    }

    /**
     * 发布列表数量
     */
    public function getArticleCount($where, $field)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->leftjoin($prefix . 'user b', 'a.uid=b.uid')
            ->leftjoin($prefix . 'grow_grass_category c', 'a.category_id=c.category_id')
            ->leftjoin($prefix . 'grow_grass_bind_store e', 'a.article_id=e.article_id')
            ->leftjoin($prefix . 'merchant_store d', 'e.store_id=d.store_id')
            ->GROUP('a.article_id')
            ->field($field)
            ->where($where)
            ->count('a.article_id');
        return $count;
    }

    /**
     * 设置为发布、不予发布、删除
     */
    public function getEditArticle($id, $type)
    {
        if ($type == 1) {
            $list = $this->where(array('article_id' => $id))->update(array('status' => 20, 'publish_time' => time()));
        } elseif ($type == 2) {
            $list = $this->where(array('article_id' => $id))->update(array('status' => 30));
        } elseif ($type == 3) {
            $list = $this->where(array('article_id' => $id))->update(array('is_system_del' => 1));
        }
        return $list;
    }

    /**
     * 文章详情
     */
    public function getArticleDetails($id)
    {
        $list = $this->where(array('article_id' => $id))->find()->toArray();
        if (!empty($list)) {
            //评论图片
            if ($list['img']) {
                if(strpos($list['img'],',') !== false){
                    $list['img'] = explode(',', $list['img']);
                }else{
                    $list['img'] = explode(';', $list['img']);
                }
                foreach ($list['img'] as $key => $val) {
                    $list['img'][$key] = replace_file_domain($val);
                }
            }
            $details = [
                'name' => $list['name'],
                'content' => $list['content'],
                'img' => $list['img'] ?: [],
                'video_img'=>empty($list['video_img'])?"":replace_file_domain($list['video_img']),
                'video_url'=>empty($list['video_url'])?"":replace_file_domain($list['video_url']),
            ];
            return $details;
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @param $field
     * @return float
     * 求字段和
     */
    public function getSum($where, $field)
    {
        return $this->where($where)->sum($field);
    }
    
    public function getDetail($where,$field='a.*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $info = $this->alias('a')
            ->join($prefix.'user b','a.uid = b.uid')
            ->where($where)
            ->field($field)
            ->find()
            ->toArray();
        return $info;
    }
    
    public function getGrowGrassList($where,$field='a.*',$limit=10)
    {
        $prefix = config('database.connections.mysql.prefix');
        $info = $this->alias('a')
            ->join($prefix.'user b','a.uid = b.uid')
            ->where($where)
            ->field($field)
            ->order('a.article_id desc')
            ->paginate($limit)
            ->toArray();
        return $info;
    }

    public function getWarn($goodsInfo)
    {
        $warn = '';
        if($goodsInfo['status']!=20){
            $warn = '文章未审核通过';
        }
        if($goodsInfo['is_del']){
            $warn = $warn ? $warn.'/文章已被删除' : '文章已被删除';
        }
        if($goodsInfo['is_system_del']){
            $warn = $warn ? $warn.'/文章已被系统删除' : '文章已被系统删除';
        }
        return $warn;
    }
}