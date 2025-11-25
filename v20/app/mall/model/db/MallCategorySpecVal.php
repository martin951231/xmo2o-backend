<?php

/**
 * @Author: jjc
 * @Date:   2020-06-16 13:51:00
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-16 13:57:50
 */
namespace app\mall\model\db;
use think\Model;

class MallCategorySpecVal extends Model {

	/**
	 * [getNormalList 获取正常商品所有规格列表]
	 * @Author   JJC
	 * @DateTime 2020-06-08T13:48:15+0800
	 * @return   [type]                   [description]
	 */
	public function getNormalList($field="id,cat_spec_id,name"){
		$where = [
			['is_del','=',0],
		];
		return $this->field($field)->where($where)->select()->toArray();
	}

    /**
     * [getSpecVal 获取当前分类id的所有规格列表]
     * @Author   Mrdeng
     * @return   [type]                   [description]
     */
    public function getSpecVal($where){
        /*$con=[];
        foreach ($where as $key => $val){
            $con[$key]=$val['cat_spec_id'];
        }*/
        $where1[] = ['is_del','=',0];
        $where1[] = ['cat_spec_id','=',$where];
        $field='id,cat_spec_id,name';
        return $this->field($field)->where($where1)->select()->toArray();
    }

    /**
     * auth 朱梦群
     * 删除属性值
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delSpecVals($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * auth 朱梦群
     * 添加属性值
     * @param $spec
     * @return bool
     */
    public function addSpecVals($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * @param $where_spec_val
     * @return array
     * auth zhumengqun
     * 根据条件获取属性值
     */
    public function getSpecVals($where_spec_val)
    {
        $arr = $this->field('name,id')->where($where_spec_val)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}
