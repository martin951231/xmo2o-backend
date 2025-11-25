<?php
/**
 * 自动更新景区首页推荐内容
 */

namespace app\common\model\service\plan\file;

use app\common\model\db\GroupCategory;
use app\common\model\db\MerchantStore;
use app\group\model\db\Group;
use app\group\model\db\GroupLabel;
use app\life_tools\model\db\LifeToolsRecommendCate;
use app\life_tools\model\db\LifeToolsScenicRecommend;

class LifeToolsScenicIndexRecService
{

    public function runTask()
    {	
    	$LifeToolsRecommendCate   = new LifeToolsRecommendCate();
        $LifeToolsScenicRecommend = new LifeToolsScenicRecommend();
        $MerchantStore = new MerchantStore();
        $LifeToolsScenicRecommend->where(['type' => 'store'])->delete(); //删除旧数据 店铺
        $data = $LifeToolsRecommendCate->getSome(['status' => 1], 'cat_id', 'sort asc');
    	if (!empty($data)) {
            $data = $data->toArray();
    		foreach ($data as $value) {
	    		try {
                    $storeList = $MerchantStore->getSome(['cat_id' => $value['cat_id'], 'status' => 1], true, true, 1, 60);
                    if (!empty($storeList)) {
                        $storeList = $storeList->toArray();
                        $arr = [];
                        foreach ($storeList as $v) {
                            $arr[] = [
                                'type'        => 'store',
                                'type_id'     => $v['store_id'],
                                'name'        => $v['name'],
                                'pic'         => $v['logo'],
                                'score'       => $v['score'],
                                'price'       => $v['money'],
                                'phone'       => $v['phone'],
                                'sort'        => $v['sort'],
                                'province_id' => $v['province_id'],
                                'city_id'     => $v['city_id'],
                                'area_id'     => $v['area_id'],
                                'street_id'   => $v['street_id'],
                                'circle_id'   => $v['circle_id'],
                                'market_id'   => $v['market_id'],
                                'adress'      => $v['adress'],
                                'long'        => $v['long'],
                                'lat'         => $v['lat'],
                                'create_time' => time(),
                            ];
                        }
                        $LifeToolsScenicRecommend->addAll($arr);
                    }
                } catch (\Exception $e) {
                    fdump($value['cat_id'] . $e->getMessage(), "LifeToolsScenicIndexRecService", 1);
                }
	    	}
    	}
        $LifeToolsScenicRecommend->where(['type' => 'hotel'])->delete(); //删除旧数据 酒店
        $catIds = (new GroupCategory())->where(['is_hotel' => 1, 'cat_status' => 1])->column('cat_id');
        if ($catIds) {
            $prefix = config('database.connections.mysql.prefix');
            $data1 = (new Group())->alias('g')
                ->join($prefix . 'merchant m', 'm.mer_id = g.mer_id', 'left')
                ->join($prefix . 'group_store gs', 'gs.group_id = g.group_id', 'left')
                ->join($prefix . 'merchant_store ms', 'ms.store_id = gs.store_id', 'left')
                ->where([
                    ['g.status', '=', 1],
                    ['g.type', '=', 1],
                    ['m.status', '=', 1],
                    ['g.end_time', '>', time()],
                    ['ms.have_group', '=', 1],
                    ['g.cat_id|g.cat_fid', 'in', $catIds]
                ])
                ->order('g.sort desc, g.group_id desc')
                ->group('g.group_id')
                ->limit(60)
                ->field('g.*,ms.long AS s_long,ms.lat AS s_lat')
                ->select();
            if (!empty($data1)) {
                $data1 = $data1->toArray();
                try {
                    $arr1 = [];
                    foreach ($data1 as $value1) {
                        $arr1[] = [
                            'type'        => 'hotel',
                            'type_id'     => $value1['group_id'],
                            'name'        => $value1['name'],
                            'pic'         => $value1['pic'] ? explode(';', $value1['pic'])[0] : '',
                            'sale_count'  => $value1['sale_count'],
                            'label'       => $value1['label_ids'] ? implode(',', (new GroupLabel())->where([['label_id', 'in', $value1['label_ids']]])->column('name')) : '',
                            'score'       => $value1['score'],
                            'old_price'   => $value1['old_price'],
                            'price'       => $value1['price'],
                            'sort'        => $value1['sort'],
                            'province_id' => $value1['province_id'],
                            'city_id'     => $value1['city_id'],
                            'area_id'     => $value1['area_id'],
                            'circle_id'   => $value1['circle_id'],
                            'long'        => $value1['s_long'],
                            'lat'         => $value1['s_lat'],
                            'create_time' => time(),
                        ];
                    }
                    $LifeToolsScenicRecommend->addAll($arr1);
                } catch (\Exception $e) {
                    fdump($value1['group_id'] . $e->getMessage(), "LifeToolsScenicIndexRecService", 1);
                }
            }
        }
        return true;
    }

}