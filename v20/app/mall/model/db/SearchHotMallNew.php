<?php
/**
 * SearchHotMallNew.php
 * 商城3.0 热搜词 model
 * Create on 2020/10/16 13:05
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\model;

class SearchHotMallNew extends model
{
    /**
     * @param $page
     * @param $pageSize
     * @return array
     *
     */
    public function getSearchHotList($page, $pageSize)
    {
        $arr = $this->field(true)->order('sort DESC')->page($page, $pageSize)->select();
        $count = $this->count('id');
        if (!empty($arr)) {
            $list['list'] = $arr->toArray();
            $list['count'] = $count;
            return $list;
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @param $data
     * @return SearchHotMallNew
     * 编辑热搜词
     */
    public function EditSearchHot($where, $data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * 添加热搜词
     * @param $data
     * @return int|string
     */
    public function addSearchHot($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 根据条件获取
     * @param $field
     * @param $where
     * @return array
     */
    public function getByCondition($field, $where)
    {
        $arr = $this->field($field)->where($where)->order('sort DESC')->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

}