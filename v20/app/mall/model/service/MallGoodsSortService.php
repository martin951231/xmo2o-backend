<?php
/**
 * @Author: 朱梦群
 * @Date:   2020-09-04 11:17:43
 * @Desc:   商城3.0分类管理service
 */

namespace app\mall\model\service;

use app\mall\model\db\MallGoods;
use app\mall\model\db\MallGoodsSort;

class MallGoodsSortService
{
    /**
     * @param:
     * @return :  array
     * @Desc:   获取分类列表
     */
    public function getSortList($mer_id, $store_id, $type, $param)
    {
        if (!empty($mer_id) && !empty($store_id)) {
            $mallSort = new MallGoodsSort();
            // 总数
            $count = $mallSort->getSortCount(['mer_id' => $mer_id, 'store_id' => $store_id, 'level' => 1]);
            //排序
            $order = [
                'sort' => 'DESC',
                'mer_id' => 'ASC',
                'id' => 'ASC',
            ];
            //获取三级分类数据
            $list = $mallSort->getCategoryByCondition(['mer_id' => $mer_id, 'store_id' => $store_id], $order);
            $farr = array();
            $carr = array();
            $garr = array();
            if (!empty($list)) {
                //分离出子数组和父数组
                foreach ($list as $val) {
                    if ($val['fid'] == 0) {
                        //$farr[] = $val;
                        $farr = $mallSort->getCategoryByCondition2(['fid' => 0, 'mer_id' => $mer_id, 'store_id' => $store_id], $order, $param);
                    } else if ($mallSort->getCategoryByCondition(['id' => $val['fid'], 'mer_id' => $mer_id, 'store_id' => $store_id], $order)[0]['fid'] == 0) {
                        $carr[] = $val;
                    } else {
                        $garr[] = $val;
                    }
                }
                $count_2 = 0;
                foreach ($carr as $key => $val1) {
                    foreach ($garr as $gkey => $val2) {
                        if ($type != 0) {
                            $goods_count = (new MallGoodsService())->getCountByCondition(['sort_id' => $val2['id']]);
                            if ($goods_count == 0) {
                                // unset($garr[$gkey]);
                                // $garr[$gkey]['has_goods'] = 0;
                                $garr[$gkey]['has_goods'] = 1;
                            } else {
                                $garr[$gkey]['has_goods'] = 1;
                            }
                            $count_2 += $goods_count;
                        } else {
                            // $garr[$gkey]['has_goods'] = 0;
                            $garr[$gkey]['has_goods'] = 1;
                        }
                        if ($val1['id'] == $val2['fid']) {
                            $carr[$key]['children'][] = $val2;
                        } else {
                            $count_2 = 0;
                        }
                    }
                    if ($count_2 == 0) {
                        // unset($carr[$key]);
                        $carr[$key]['has_goods'] = 0;
                    } else {
                        // $garr[$gkey]['has_goods'] = 0;
                        $garr[$gkey]['has_goods'] = 1;
                        $carr[$key]['has_goods'] = 1;
                    }
                    $count_2 = 0;
                }
                $count_1 = 0;
                foreach ($farr as $key => $val1) {
                    foreach ($carr as $ckey => $val2) {
                        if ($val1['id'] == $val2['fid']) {
                            if ($type != 0) {
                                    if ($carr[$ckey]['has_goods'] == 0) {
                                        $goods_count = (new MallGoodsService())->getCountByCondition(['sort_id' => $val2['id']]);
                                        if ($goods_count == 0) {
                                            //unset($carr[$ckey]);
                                            // $carr[$ckey]['has_goods'] = 0;
                                            $carr[$ckey]['has_goods'] = 1;
                                        } else {
                                            $carr[$ckey]['has_goods'] = 1;
                                        }
                                        $count_1 += $goods_count;
                                    } else {
                                        $count_1 = 1;
                                    }
                            } else {
                                // $carr[$ckey]['has_goods'] = 0;
                                $carr[$ckey]['has_goods'] = 1;
                            }
                            $farr[$key]['children'][] = $val2;
                        }
                    }
                    if ($count_1 == 0) {
                        // unset($farr[$key]);
                        $farr[$key]['has_goods'] = 0;
                    } else {
                        // $farr[$key]['has_goods'] = 0;
                        $farr[$key]['has_goods'] = 1;
                    }
                    $count_1 = 0;
                }
                if (!empty($param)) {
                    $list1['count'] = $count;
                }
                $list1['list'] = $farr;
                return $list1;
            } else {
                return ['list'=>[]];
            }
        } else {
            return ['list'=>[]];
        }
    }

    /**
     * @param: $arr array
     * @return :  int
     * @Desc:   编辑或新增分类
     */
    public function addOrEditSort($arr)
    {

        if (empty($arr['mer_id']) || empty($arr['name']) || empty($arr['store_id'] || empty($arr['level']))) {
            throw new \think\Exception('参数缺失');
        }
        $goodsSort = new MallGoodsSort();
        if ($arr['fid'] === 0 || $arr['id'] == $arr['fid']) {//一级分类
            $arr['level'] = 1;
        } else {
            $goodsSort = new MallGoodsSort();
            $editArr = $goodsSort->getEdit(['id' => $arr['fid']]);
            $arr['level'] = $editArr['level'] + 1;
        }
        if ($arr['id'] == $arr['fid']) {
            $arr['fid'] = 0;
        }
        if (!empty($arr['id'])) {
            //查询新的父级
            if($arr['fid'] > 0 && isset($editArr) && $editArr['fid'] == $arr['id']){
                throw new \think\Exception('不能设置上级分类为当前分类的子分类');
            }
            //编辑
            $where = [
                'id' => $arr['id'],
                'mer_id' => $arr['mer_id'],
                'store_id' => $arr['store_id']
            ];
            unset($arr['id']);
            unset($arr['mer_id']);
            unset($arr['store_id']);
            $result = $goodsSort->editSort($where, $arr);
            //新建下级分类，如果父分类存在商品，则移入子分类
            $goods = (new MallGoodsService())->getSome(['sort_id' => $arr['fid']]);
            if (!empty($goods)) {
                (new MallGoods())->updateOne(['sort_id' => $arr['fid']], ['sort_id' => $arr['id']]);
            }
        } else {
            //保存
            $result = $goodsSort->addSort($arr);
            //新建下级分类，如果父分类存在商品，则移入子分类
            $goods = (new MallGoodsService())->getSome(['sort_id' => $arr['fid']]);
            if (!empty($goods) && $result) {
                (new MallGoods())->updateOne(['sort_id' => $arr['fid']], ['sort_id' => $result]);
            }
        }
        if ($result !== false) {
            return $result;
        } else {
            throw new \think\Exception('操作失败，请重试');
        }

    }

    /**
     * @param: $arr array
     * @return :  int
     * @Desc:   删除分类
     */
    public function delSort($arr)
    {
        if (empty($arr['mer_id']) || empty($arr['id']) || empty($arr['store_id'])) {
            throw new \think\Exception('参数缺失');
        }
        $goodsSort = new MallGoodsSort();
        //查看被删除分类的子分类是否存在
        $children = $goodsSort->getCategoryByCondition(['fid' => $arr['id'], 'mer_id' => $arr['mer_id'], 'store_id' => $arr['store_id']], []);
        if (!empty($children)) {
            throw new \think\Exception('当前分类存在子分类，无法删除');
        }
        switch ($arr['level']) {
            case 1:
                $goods_where = ['sort_first' => $arr['id'], 'is_del'=>0];
                break;
            case 2:
                $goods_where = ['sort_second' => $arr['id'], 'is_del'=>0];
                break;
            case 3:
                $goods_where = ['sort_third' => $arr['id'], 'is_del'=>0];
                break;
            default:
                $goods_where = ['sort_id' => $arr['id'], 'is_del'=>0];
        }
        $goods = (new MallGoodsService())->getSome($goods_where);
        if (!empty($goods)) {
            return 100;//用100标识该分类下存在商品
        }
        //删除
        $result = $goodsSort->delSort(['id' => $arr['id'], 'mer_id' => $arr['mer_id'], 'store_id' => $arr['store_id']]);
        if ($result !== false) {
            return $result;
        } else {
            throw new \think\Exception('操作失败，请重试');
        }

    }

    /**
     * @param: $arr array()
     * @return :  array
     * @Desc:   获取被编辑的分类
     */
    public function getEditSort($id)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $goodsSort = new MallGoodsSort();
        $editArr = $goodsSort->getEdit(['id' => $id]);
        $list = array();
        if (!empty($editArr)) {
            $list['list'] = $editArr;
        }
        return $list;
    }

    /**
     * 根据条件获取叶子分类
     * @param $field
     * @param $order
     * @param $where
     * @param $param
     * @return string
     */
    public function getSortByCondition($field, $order, $where, $param)
    {
        $goodsSort = new MallGoodsSort();
        $arr = $goodsSort->getSortByCondition($field, $order, $where, $param['page'], $param['pageSize']);
        if (!empty($arr)) {
            $count = 0;
            //获取叶子节点
            foreach ($arr as $key => $val) {
                $son = $goodsSort->getCategoryByCondition(['fid' => $val['id'], 'mer_id' => $param['mer_id'], 'store_id' => $arr['store_id']], $order);
                if (empty($son)) {
                    $list[] = $val;
                    $count++;
                }
            }
            $list['count'] = $count;
            return $list;
        } else {
            return '';
        }
    }

    /**
     * @param $id
     * 设置排序
     */
    public function saveSort($id, $sort)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $goodsSort = new MallGoodsSort();
        $res = $goodsSort->editSort(['id' => $id], ['sort' => $sort]);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * @param $id
     * 设置排序
     */
    public function saveStatus($id, $status)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $goodsSort = new MallGoodsSort();
        $res = $goodsSort->editSort(['id' => $id], ['status' => $status]);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * @param $store_id
     * 获取一级和二级分类
     */
    public function getSort($store_id)
    {
        if (empty($store_id)) {
            throw new \think\Exception('参数缺失');
        }
        $order = [
            'sort' => 'DESC',
            'store_id' => 'ASC',
        ];
        //获取三级分类数据
        $arr = [];
        $mallSort = new MallGoodsSort();
        $list = $mallSort->getCategoryByCondition(['store_id' => $store_id,'status'=>1], $order);
        $arr = array();
        if (!empty($list)) {
            //分离出子数组和父数组
            foreach ($list as $val) {
                if ($val['fid'] == 0) {
                    $arr[] = $val;
                } else if ($mallSort->getCategoryByCondition(['id' => $val['fid'], 'store_id' => $store_id], $order)[0]['fid'] == 0) {
                    $arr[] = $val;
                }
            }
        }
        $list1['list'] = $arr;
        return $list1;
    }
}