<?php


namespace app\community\model\service;

use app\community\model\db\AreaStreetEventCategory;

class AreaStreetEventCategoryService
{
    /**
     * 获取事件分类列表
     * @author lijie
     * @date_time 2020/02/22
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCategoryList($where,$field=true,$page=1,$limit=15,$order='cat_id DESC')
    {
        $db_area_street_event_category = new AreaStreetEventCategory();
        $data = $db_area_street_event_category->getList($where,$field,$page,$limit,$order);
        return $data;
    }

    /**
     * 添加事件分类
     * @author lijie
     * @date_time 2021/02/22
     * @param $data
     * @return int|string
     */
    public function addCategory($data)
    {
        $db_area_street_event_category = new AreaStreetEventCategory();
        $res = $db_area_street_event_category->addOne($data);
        return $res;
    }

    /**
     * 获取事件分类详情
     * @author lijie
     * @date_time 2021/02/24
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCategoryInfo($where,$field=true)
    {
        $db_area_street_event_category = new AreaStreetEventCategory();
        $data = $db_area_street_event_category->getOne($where,$field);
        return $data;
    }

    /**
     * 修改事件分类
     * @author lijie
     * @date_time 2021/02/22
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveCategory($where,$data)
    {
        $db_area_street_event_category = new AreaStreetEventCategory();
        $res = $db_area_street_event_category->saveOne($where,$data);
        return $res;
    }

    /**
     * 获取事件分类数量
     * @author lijie
     * @date_time 2021/02/23
     * @param $where
     * @return int
     */
    public function getCategoryCount($where)
    {
        $db_area_street_event_category = new AreaStreetEventCategory();
        $count = $db_area_street_event_category->getCount($where);
        return $count;
    }

    /**
     * Notes: 用户获取事件分类
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/23 13:33
     */
    public function userGetCategoryList($where,$field=true,$order='sort DESC, cat_id DESC',$type='all'){
        $page = 0;
        $limit = 0;
        $db_area_street_event_category = new AreaStreetEventCategory();
        $data = $db_area_street_event_category->getList($where,$field,$page,$limit,$order);
        $arr = [];
        if (!empty($data)) {
            $data = $data->toArray();
            foreach ($data as $val) {
                $cat_id = intval($val['cat_id']);
                $cat_fid = intval($val['cat_fid']);
                $cat_info = [
                    'cat_id' => $cat_id,
                    'cat_fid' => $cat_fid,
                    'sort' => $val['sort'],
                    'label' => $val['cat_name'],
                    'value' => $cat_id,
                    'cat_name' => $val['cat_name'],
                    'add_time' => $val['add_time'],
                ];
                if ($cat_fid>0 && !isset($arr[$cat_fid])) {
                    $arr[$cat_fid] = [];
                    $children = [];
                    $children[] = $cat_info;
                    $arr[$cat_fid]['children'] = $children;
                } elseif ($cat_fid>0 && isset($arr[$cat_fid])) {
                    if (isset($arr[$cat_fid]['children']) && is_array($arr[$cat_fid]['children'])) {
                        $arr[$cat_fid]['children'][] = $cat_info;
                    } else {
                        $children = [];
                        $children[] = $cat_info;
                        $arr[$cat_fid]['children'] = $children;
                    }
                } elseif ($cat_id && !isset($arr[$cat_id])) {
                    $arr[$cat_id] = $cat_info;
                    $children = [];
                    $arr[$cat_id]['children'] = $children;
                } elseif ($cat_id && isset($arr[$cat_id])) {
                    $arr[$cat_id]['cat_id'] = $cat_id;
                    $arr[$cat_id]['cat_fid'] = $cat_fid;
                    $arr[$cat_id]['sort'] = $val['sort'];
                    $arr[$cat_id]['label'] = $val['cat_name'];
                    $arr[$cat_id]['value'] = $cat_id;
                    $arr[$cat_id]['cat_name'] = $val['cat_name'];
                    $arr[$cat_id]['add_time'] = $val['add_time'];
                }
            }
            foreach ($arr as $k=>$v) {
                if ($type!='all') {
                    if (isset($v['children']) && empty($v['children'])) {
                        unset($arr[$k]['children']);
                    }
                } elseif (!isset($v['children']) || empty($v['children'])) {
                    unset($arr[$k]);
                } elseif (!isset($v['cat_id'])) {
                    unset($arr[$k]);
                }
            }
            // 重新排序数组
            $arr = array_values($arr);
        }
        return $arr;
    }

    public function childrenCategoryList($fid,$field=true,$order='cat_id DESC') {
        $page = 0;
        $limit = 0;
        $db_area_street_event_category = new AreaStreetEventCategory();
        $where = [];
        $where[] = ['cat_fid', '=', $fid];
        $children_list = $db_area_street_event_category->getList($where,$field,$page,$limit,$order);
        $children_list = $children_list->toArray();
        if (empty($children_list)) {
            $children_list = [];
        }
    }

    /**
     * Notes: 单个分类详情
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/23 14:08
     */
    public function getCategoryDetail($where,$field =true,$source=0) {
        $db_area_street_event_category = new AreaStreetEventCategory();
        $info = $db_area_street_event_category->getOne($where,$field);
        if (!empty($info)) {
            $info = $info->toArray();
            if (isset($info['cat_fid']) && intval($info['cat_fid'])) {
                $where_fid = [];
                $where_fid[] = ['cat_id','=',$info['cat_fid']];
                $fid_info = $db_area_street_event_category->getOne($where_fid,$field);
                if (!empty($fid_info)) {
                    $fid_info = $fid_info->toArray();
                    $info['fid_info'] = $fid_info;
                    $info['cat_txt'] = ($source == 0) ? $fid_info['cat_name'].'-'.$info['cat_name'] : $fid_info['cat_name'];
                }
            }
        } else {
            $info = [];
        }
        return $info;
    }
}