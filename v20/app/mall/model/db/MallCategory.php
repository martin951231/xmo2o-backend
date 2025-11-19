<?php
/**
 * 商城商品分类model
 * Created by vscode.
 * Author: JJC
 * Date Time: 2020/5/19 10:50
 */

namespace app\mall\model\db;

use think\Model;

class MallCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * [getNormalList 获取正常商品分类列表]
     * @Author   JJC
     * @DateTime 2020-06-08T13:48:15+0800
     * @return   [type]                   [description]
     */
    public function getNormalList($field = true)
    {
        $where = [
            'is_del' => 0,
            'status' => 1
        ];
        return $this->field($field)->where($where)->order('sort desc ,id desc')->select();
    }

    public function getList($where, $field = "*")
    {
        return $this->where($where)->field($field)->order('sort desc')->select()->toArray();
    }

    /**
     * @param $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList2($list, $where, $pageto)
    {
        $field = 'cat_id as cate_second';
        $where[] = ['is_del', '=', 0];
        $where[] = ['level', '=', 2];
        if (count($list) != 0) {
            $con = [];
            foreach ($list as $key => $val) {
                $con[$key] = $val['cate_second'];
            }
            $con = implode(',', $con);
            $where[] = ['cat_id', 'not in', $con];
        }

        $result = $this->field($field)->where($where)->limit(0, $pageto)->select()->toArray();
        return $result;
    }

    //查询出参数内包含的二级id的id和名称
    public function getGoodLevelTwoName($condition)
    {
        $con = [];
        foreach ($condition as $key => $val) {
            $con[$key] = $val['cate_second'];
        }
        $where[] = ['is_del', '=', 0];
        $where[] = ['cat_id', 'in', $con];
        $field = 'cat_id,cat_name';
        $result = $this->field($field)->where($where)->select()->toArray();
        return $result;
    }

    /**
     * @param $where
     * @param $order
     * @return array
     */
    public function getCategoryByCondition($where, $order)
    {
        $sort = $this->field(['cat_id', 'cat_fid', 'cat_name', 'sort', 'status', 'image', 'level'])->where($where)->order($order)->select();
        if (!$sort) {
            return [];
        }
        return $sort->toArray();
    }

    /**
     * @param $where
     * @param  $order array
     * @param $page
     * @param $pageSize
     * @return array :  array
     * @Desc:   获取分类列表
     */
    public function getCategoryByCondition2($where, $order, $page, $pageSize)
    {
        if (empty($page) && empty($pageSize)) {
            $sort = $this->field(['cat_id', 'cat_fid', 'cat_name', 'sort', 'status', 'image', 'level'])->where($where)->order($order)->select();
        } else {
            $sort = $this->field(['cat_id', 'cat_fid', 'cat_name', 'sort', 'status', 'image', 'level'])->where($where)->order($order)->page($page, $pageSize)->select();
        }
        if (!$sort) {
            return [];
        }
        return $sort->toArray();
    }

    /**
     * @return int
     * 获取分类总数
     */
    public function getCategoryCount()
    {
        $count = $this->where(['level' => 1, 'is_del' => 0, 'cat_fid' => 0])->select()->count('cat_id');
        return $count;
    }

    /**
     * 新增分类
     * @param $where
     */
    public function editCategory($where, $arr)
    {
        $result = $this->where($where)->update($arr);
        return $result;
    }

    /**
     * 删除分类
     * @param $where
     * @return bool
     */
    public function addCategory($arr)
    {
        $result = $this->insertGetId($arr);
        return $result;
    }

    /**
     * @param $where
     * @return array
     * 获取编辑的分类
     */
    public function getEditCategory($where)
    {
        $arr = $this->field(['cat_id', 'cat_fid', 'cat_name', 'sort', 'status', 'image'])->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @return bool
     * 删除平台分类
     */
    public function delCategory($where)
    {
        $result = $this->where($where)->update(['is_del'=>1,'status'=>0]);
        return $result;
    }

    /**
     * @param $where
     * @return bool
     * 获取平台分类
     */
    public function getCateName($where,$field)
    {
        $result = $this->where($where)->value($field);
        return $result;
    }
}