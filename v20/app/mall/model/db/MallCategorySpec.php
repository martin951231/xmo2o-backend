<?php

/**
 * @Author: jjc
 * @Date:   2020-06-16 13:50:34
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-16 13:58:02
 */

namespace app\mall\model\db;

use think\Model;

class MallCategorySpec extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * [getNormalList 获取正常商品所有规格列表]
     * @Author   JJC
     * @DateTime 2020-06-08T13:48:15+0800
     * @return   [type]                   [description]
     */
    public function getNormalList($field = "cat_spec_id,cat_spec_name,cat_id")
    {
        $where = [
            ['is_del', '=', 0],
        ];
        return $this->field($field)->where($where)->select()->toArray();
    }

    /**
     * [getSpecListById 获取二级分类下]
     * @Author   Mrdeng
     * @return   [type]                   [description]
     */
    public function getSpecListById($cat_id)
    {
        $field = "cat_spec_id,cat_spec_name,cat_id";
        $where = [
            ['is_del', '=', 0],
            ['cat_id', '=', $cat_id]
        ];
        $sort = 'cat_spec_id desc';
        return $this->field($field)->where($where)->order($sort)->select()->toArray();
    }

    /**
     * auth 朱梦群
     * 根据条件筛选
     * @param string $order
     * @param bool $field
     * @param $where
     * @return array
     */
    public function getSpecBYCondition($order = 'cat_spec_id DESC', $field = true, $where)
    {
        $arr = $this->field($field)->where($where)->order($order)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * auth 朱梦群
     * 删除属性
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delSpec($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * auth朱梦群
     * 新增属性
     * @param $spec
     * @return int|string
     */
    public function addSpec($spec)
    {
        $spec_id = $this->insertGetId($spec);
        return $spec_id;
    }
}