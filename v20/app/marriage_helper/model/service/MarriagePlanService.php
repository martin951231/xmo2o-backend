<?php


namespace app\marriage_helper\model\service;


use app\marriage_helper\model\db\MarriagePlan;
use app\marriage_helper\model\db\MarriagePlanCategory;

class MarriagePlanService
{
    /**
     * 结婚计划分类列表
     */
    public function planCategoryList()
    {
        $where = [['is_del', '=', 0]];
        $list = (new MarriagePlanCategory())->getSome($where, true, 'sort desc')->toArray();
        // 返回前端需要格式
        $fomartList = [];
        foreach ($list as $_sort) {
            $temp = [
                'title' => $_sort['cat_name'],//分类名
                'id' => $_sort['cat_id'],//分类id
                'fid' => 0,//父id
                /* 'goods_count' => $childCount,//分类下商品总数*/
                'children' => [],//子分类（餐饮只有一级分类）
            ];
            $fomartList[] = $temp;
        }
        return $fomartList;
    }


    /**
     * 结婚计划分类对应的计划列表
     */
    public function childPlanList($param)
    {
        $where = [['is_del', '=', 0]];
        if (!empty($param['plan_title'])) {
            array_push($where, ['plan_title', 'like', '%' . $param['plan_title'] . '%']);
        }
        if ($param['cat_id'] > 0 && empty($param['plan_title'])) {
            array_push($where, ['cat_id', '=', $param['cat_id']]);
        }
        $list['list'] = (new MarriagePlan())->getSome($where, true, 'plan_id desc')->toArray();
        if(!empty($list['list'])){
            foreach ($list['list'] as $k=>$v){
                if($v['add_time']){
                    $list['list'][$k]['add_time']=date("Y-m-d H:i:s",$v['add_time']);
                }
            }
        }
        $list['count'] = (new MarriagePlan())->getCount($where);
        return $list;
    }

    /**
     * 拖拽分类排序
     */
    public function changeSort($list)
    {
        $sort = 0;
        $sortList = array_reverse($list);
        foreach ($sortList as $_sort) {
            $sort += 10;
            // 条件
            $where = [
                'cat_id' => $_sort['id']
            ];
            // 更新排序值
            $data = [
                'sort' => $sort
            ];
            $res = (new MarriagePlanCategory())->updateThis($where, $data);
        }

        return true;
    }

    /**
     *新增分类
     */
    public function addCategory($param)
    {
        $ret = (new MarriagePlanCategory())->add($param);
        return $ret;
    }

    /**
     *新增计划
     */
    public function addPlan($param)
    {
        $ret = (new MarriagePlan())->add($param);
        return $ret;
    }

    /**
     *  更新分类
     */
    public function updateCategory($param)
    {
        $where = [['cat_id', '=', $param['cat_id']]];
        $ret = (new MarriagePlanCategory())->updateThis($where, $param);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  更新计划
     */
    public function updatePlan($param)
    {
        $where = [['plan_id', '=', $param['plan_id']]];
        $ret = (new MarriagePlan())->updateThis($where, $param);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *删除分类
     */
    public function delCategory($param)
    {
            $where = [['cat_id', 'in', $param['cat_id']]];
            $data['is_del'] = 1;
            $list = (new MarriagePlan())->getSome($where)->toArray();
            if (!empty($list)) {
                $where1 = [['cat_id', '=', $param['cat_id']]];
                (new MarriagePlan())->updateThis($where1, $data);
            }
            $ret = (new MarriagePlanCategory())->updateThis($where, $data);
            if ($ret !== false) {
                return true;
            } else {
                return false;
            }
    }

    /**
     * 重新绑定给计划分类
     */
    public function byOtherCategory($param)
    {
        $where = [['plan_id', 'in', $param['plan_ids']]];
        $data['cat_id'] = $param['cat_id'];
        $ret = (new MarriagePlan())->updateThis($where, $data);
        fdump((new MarriagePlan())->getLastSql(), "dddddd000000000000000000000");
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *获取分类
     */
    public function getSelCategory($param)
    {
        $where = [['cat_id', 'not in', $param['cat_id']], ['is_del', '=', 0]];
        $list = (new MarriagePlanCategory())->getSome($where, 'cat_id,cat_name')->toArray();
        return $list;
    }

    /**
     *删除计划
     */
    public function delPlan($param)
    {
        $where = [['plan_id', 'in', $param['plan_id']]];
        $data['is_del'] = 1;
        $ret = (new MarriagePlan())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 编辑分类
     */
    public function editCategory($param)
    {
        $where = [['cat_id', '=', $param['cat_id']]];
        $ret = (new MarriagePlanCategory())->getOne($where);
        if ($ret) {
            return $ret->toArray();
        } else {
            return false;
        }
    }

    /**
     * 编辑计划
     */
    public function editPlan($param)
    {
        $where = [['plan_id', '=', $param['plan_id']]];
        $ret = (new MarriagePlan())->getOne($where);
        if ($ret) {
            $ret = $ret->toArray();
            $ret['link'] = empty($ret['link']) ? "" : replace_file_domain($ret['link']);
            return $ret;
        } else {
            return false;
        }
    }
}