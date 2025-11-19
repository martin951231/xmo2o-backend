<?php
/**
 * 种草话题model
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:05
 */

namespace app\grow_grass\model\db;
use app\common\model\db\MerchantCategory;
use think\Model;
class GrowGrassCategory extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    public function indexCategoryList($where,$field,$order){
        $result = $this->alias('g')
            ->where($where)
            ->field($field)
            ->join('merchant_category mc', 'mc.cat_id = g.cat_id');
        $assign = $result->order($order)
            ->select()
            ->toArray();
        return $assign;
    }

    /**
     * @param $where
     * @param $column
     * @return array
     * 取某个字段值
     */
    public function getCategoryName($where,$column){
        return $this->where($where)->column($column);
    }
    /**
     * 话题列表
     * @param $data array 数据
     * @return array
     */
    public function getCategoryList($where, $page, $pageSize){
        $arr = $this->where($where)->field(true)->order('sort DESC')->page($page, $pageSize)->select();
        foreach($arr as $k=>$v){
            $arr[$k]['last_time'] = date('Y-m-d H:i:s',$v['last_time']);
            $return = (new MerchantCategory())->field('cat_name')->where(array('cat_id'=>$v['cat_id']))->find();
            $arr[$k]['cat_name'] = $return['cat_name'];
        }

        $count = $this->where($where)->count('category_id');

        $list['count'] = $count;
        $list['page_size'] = intval($pageSize);
        $list['list'] = [];

        if (!empty($arr)) {
            $list['list'] = $arr->toArray();
            $list['list_count'] = count($list['list']);
            return $list;
        } 
        
        return $list;
        
    }

    /**
     * 编辑记录
     * @param $data array 数据
     * @return array
     */
    public function getCategoryEdit($where, $data){
        
        $result = $this->where($where)->update($data);
        if($result===false) {
            return false;
        }

        return $result;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function getCategoryAdd($data){
        $result = $this->save($data);
        if(!$result) {
            return false;
        }

        return $this->category_id;
    }

    /**
     * 获取一条记录
     * @param $data array 数据
     * @return array
     */
    public function getOneDetail($where){
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 文字话题详情
     */
    public function getArticleCategoryDetails($id)
    {
        $list = $this->where(array('category_id'=>$id))->find()->toArray();
        if (!empty($list)) {
            //评论图片
			if($list['img']){
				$list['img'] = explode(';', $list['img']);
				foreach ($list['img'] as $key => $val) {
					$list['img'][$key] = replace_file_domain($val);
				}
			}
            $details = [
                'name' => $list['name'],
                'description' => $list['description'],
                'img' => $list['img'] ?: [],
            ];
            return $details;
        } else {
            return [];
        }
    }
    
    /**
     * 话题详情
     */
    public function getCategoryDetail($id)
    {
        $list = $this->where(array('category_id'=>$id))->find()->toArray();
        if (!empty($list)) {
            //评论图片
			if($list['img']){
				$list['img'] = explode(';', $list['img']);
				foreach ($list['img'] as $key => $val) {
					$list['img'][$key] = replace_file_domain($val);
				}
			}else{
                $list['img'] = [];
            }
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 话题排序
     */
    public function getCategorySort($id,$sort)
    {
        $list = $this->where(array('category_id'=>$id))->update(array('sort'=>$sort));
        return $list;
    }

    /**
     * 话题删除
     */
    public function getCategoryDel($id)
    {
        $list = $this->where(array('category_id'=>$id))->update(array('is_del'=>1));
        return $list;
    }
}