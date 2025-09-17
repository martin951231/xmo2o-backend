<?php
/**
 * liuruofei
 * 2021/08/24
 * 套餐管理
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingPackage;
use app\new_marketing\model\db\NewMarketingPackageRegion;
use app\new_marketing\model\db\NewMarketingOrder;

class MarketingPackageService
{

    //套餐管理列表
    public function getSearchList($where, $limit){
        $list = (new NewMarketingPackage())->getSearchList($where, $limit)->toArray();
        if ($list['data']) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $list['data'][$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
                $list['data'][$k]['store_detail'] = $v['store_detail'] ? json_decode($v['store_detail'], true) : [];
                $areaIds = (new NewMarketingPackageRegion())->getIdsByWhere(['package_id' => $v['id']]);
                $list['data'][$k]['order_count'] = $areaIds ? (new NewMarketingOrder())->getPackageCount([['pack_region_id', 'in', $areaIds]]) : 0;//下单总数量
            }
        }
        return $list;
    }

    //保存数据
    public function saveData($id, $param) {
        if ($id) {//编辑
            $res = (new NewMarketingPackage())->editData($id, $param);
        } else {//添加
            $res = (new NewMarketingPackage())->addData($param);
        }
        return $res;
    }

    //获取数据
    public function getData($param) {
        $res = (new NewMarketingPackage())->getOneData($param);
        return $res;
    }

    //设置状态
    public function setStatus($id, $status) {
        $res = (new NewMarketingPackage())->setStatus($id, $status);
        return $res;
    }

    //删除
    public function del($id) {
        $res = (new NewMarketingPackage())->del($id);
        return $res;
    }

    //区域套餐管理列表
    public function getAreaSearchList($where, $limit, $area_id){
        $list = (new NewMarketingPackage())->getSearchList($where, $limit)->toArray();
        if ($list['data']) {
            foreach ($list['data'] as $k => $v) {
                $areaData = (new NewMarketingPackageRegion())->getOneData([
                    ['region_id', '=', $area_id],
                    ['package_id', '=', $v['id']]
                ]);
                $list['data'][$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                $list['data'][$k]['store_detail'] = $v['store_detail'] ? json_decode($v['store_detail'], true) : [];
                $list['data'][$k]['order_count'] = 0;//下单总数量
                $list['data'][$k]['year_price'] = 0;//未设置
                $list['data'][$k]['discount_type'] = 0;//未设置
                $list['data'][$k]['status'] = 0;//未设置
                $list['data'][$k]['discount_rate'] = 0;//未设置
                $list['data'][$k]['manual_price'] = [];//未设置
                if ($areaData) {
                    $list['data'][$k]['order_count'] = (new NewMarketingOrder())->getPackageCount(['pack_region_id' => $areaData['id']]);//下单总数量
                    $list['data'][$k]['year_price'] = $areaData['year_price'];
                    $list['data'][$k]['discount_type'] = $areaData['discount_type'];
                    $list['data'][$k]['status'] = $areaData['status'];
                    $list['data'][$k]['discount_rate'] = $areaData['discount_rate'];
                    $list['data'][$k]['manual_price'] = $areaData['manual_price'] ? json_decode($areaData['manual_price'], true) : [];
                }
            }
        }
        return $list;
    }

}