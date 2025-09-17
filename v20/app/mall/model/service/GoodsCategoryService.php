<?php
/**
 * GoodsCategoryService.php
 * 后台分类的service
 * Create on 2020/9/7 16:32
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\GoodsCategory;
use think\Exception;

class GoodsCategoryService
{
    public function __construct()
    {
        $this->goodsDb = new GoodsCategory();
        $this->bannerService = new GoodsBannerService();
    }

    /**
     * 获取分类列表
     * @param $pageSize
     * @param $page
     * @return array
     */
    public function goodsCategoryList($pageSize, $page)
    {
        $order = [
            'sort' => 'DESC',
            'id' => 'ASC'
        ];
        $list = $this->goodsDb->getCategoryByCondition([], $order);
        $count = $this->goodsDb->getCategoryCount();
        $farr = array();
        $carr = array();
        $garr = array();
        if (!empty($list)) {
            //分离出子数组和父数组
            foreach ($list as $val) {
                if ($val['fid'] == 0) {
                    //$farr[] = $val;
                    $farr = $this->goodsDb->getCategoryByCondition2(['fid' => 0], $order, $page, $pageSize);
                } else if ($this->goodsDb->getCategoryByCondition(['id' => $val['fid']], $order)[0]['fid'] == 0) {
                    $carr[] = $val;
                } else {
                    $garr[] = $val;
                }
            }
            foreach ($carr as $key => $val1) {
                foreach ($garr as $val2) {
                    if ($val1['id'] == $val2['fid']) {
                        $carr[$key]['children'][] = $val2;
                    }
                }
            }
            foreach ($farr as $key => $val1) {
                foreach ($carr as $val2) {
                    if ($val1['id'] == $val2['fid']) {
                        $farr[$key]['children'][] = $val2;
                    }
                }
            }
            $farr['count'] = $count;
            return $farr;
        } else {
            return [];
        }
    }

    public function addOrEditCategory($arr)
    {
        if (!empty($arr['id'])) {
            $where = ['id' => $arr['id']];
            unset($arr['id']);
            //编辑
            $result = $this->goodsDb->editCategory($where, $arr);
        } else {
            //新增
            $result = $this->goodsDb->addCategory($arr);
        }
        if ($result !== false) {
            return $result;
        } else {
            throw new \think\Exception('操作失败，请重试');
        }
    }

    public function getEditCategory($id)
    {
        if (empty($id)) {
            throw new \think\Exception('id参数缺失');
        }
        $arr = $this->goodsDb->getEditCategory(['id' => $id]);
        if (!empty($arr)) {
            return $arr;
        } else {
            throw new \think\Exception('未查到分类信息');
        }
    }
    public function getNameById($cat_id)
    {
        $where = ['id' => $cat_id];
        $catename = $this->goodsDb->getNameById($where);
        if (!empty($catename)) {
            return $catename;
        } else {
            return '';
        }
    }
    public function delCategory($id)
    {
        if (empty($id)) {
            throw new \think\Exception('id参数缺失');
        }
        $result = $this->goodsDb->delCategory(['id' => $id]);
        if ($result !== false) {
            return $result;
        } else {
            throw new \think\Exception('操作失败，请重试');
        }
    }

    public function bannerList($cat_id, $type)
    {
        $arr = $this->bannerService->getBannerList($cat_id, $type);
        return $arr;

    }

    public function addOrEditBanner($arr)
    {
        $result = $this->bannerService->addOrEditBanner($arr);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return $result;
        }
    }

    public function delBanner($id)
    {
        $result = $this->bannerService->delBanner($id);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return $result;
        }
    }

    public function propertyList($cat_id)
    {

    }

    public function addOrEditProperty($cat_id, $property)
    {

    }
}