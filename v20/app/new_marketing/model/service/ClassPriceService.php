<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/25
 * Time: 14:57
 */

namespace app\new_marketing\model\service;


use app\common\model\db\Area;
use app\foodshop\model\db\MealStoreCategory;
use app\new_marketing\model\db\NewMarketingClassRegion;
use app\new_marketing\model\db\NewMarketingRegion;
use app\new_marketing\model\db\MerchantCategory;
use think\facade\Db;

class ClassPriceService
{
	public function getClassPriceList($region_id, $pageSize)
	{
		$regionService            = new RegionService();
		$newMarketingRegion       = new NewMarketingRegion();
		$newMarketingClassesPrice = new  NewMarketingClassRegion();

		$region_arr = $newMarketingRegion->getOne(['id' => $region_id], 'region_id')['region_id'];

		if ($region_id) {
			if (empty($region_arr)) {
				throw_exception('区域不存在，请选择正确区域');
			}
		}

		$region_arr = json_decode($region_arr);

		$region_list = $regionService->getFatherArea($region_arr);

		foreach ($region_list as &$value) {
			if ($region_id) {
				if (in_array($value['area_id'], $region_arr)) {
					$value['is_checked'] = 1;
				} else {
					$value['is_checked'] = 0;
				}
			} else {
				$value['is_checked'] = 0;
			}
		}

		$data['region_list'] = $region_list;

		//获取分类列表
//		$category_id = $mealStoreCategory->getCategoryByCondition(['cat_fid' => 0, 'cat_status' => 1],'cat_id asc')->toArray();
		$where = ['msc.cat_fid' => 0];
		$join  = 'msc.cat_id = nmcp.class_id and nmcp.is_del = 0';
//		if ($region_id) {
		$join .= ' and nmcp.region_id = ' . $region_id;
//		}
		$field = 'nmcp.*,msc.cat_name,msc.cat_id,msc.cat_status status';

		$classes_price_list = $newMarketingClassesPrice->getClassesPriceList($where, $field, 'nmcp.sort desc,msc.cat_sort desc', $join, 0, $pageSize);

		foreach ($classes_price_list as &$v) {
			$v['year_price'] = $v['year_price'] ?? '未设置';
//			$v['discount_type'] = $this->getDiscountType($v['discount_type']);
			$v['sort']         = $v['sort'] ?? 0;
			$v['status_type']  = $v['status'] == 0 ? '关闭' : '启用';
			$v['manual_price'] = json_decode($v['manual_price']);
			$children          = $this->getAreaByFather($v['cat_id'], $region_id);
			if (!empty($children)) {
				foreach ($children as &$va){
					if($va['manual_price']){
						$va['manual_price'] = json_decode($va['manual_price'],true);
					}
				}
				$v['children'] = $children;
			}
			$v['is_children'] = 0;
		}

		$data['classes_price_list'] = $classes_price_list;

		return $data;
	}

	public function getAreaByFather($fid, $region_id)
	{
		$newMarketingClassesPrice = new  NewMarketingClassRegion();

		$where = ['msc.cat_fid' => $fid];
		$join  = 'msc.cat_id = nmcp.class_id and nmcp.is_del = 0';
//		if ($region_id) {
		$join .= ' and nmcp.region_id = ' . $region_id;
//		}
		$field = 'nmcp.*,msc.cat_name,msc.cat_id,msc.cat_status status';

		$classes_price_list = $newMarketingClassesPrice->getClassesPriceList($where, $field, 'msc.cat_sort desc', $join, 1);
		foreach ($classes_price_list as &$v) {
			if (empty($v['year_price']) && empty($v['discount_type']) && empty($v['sort']) && empty($v['status'])) {
				$newMarketing = $newMarketingClassesPrice->getOne(['class_id' => $fid, 'is_del' => 0], 'region_id,year_price,discount_type,sort,status,manual_price,discount_rate');
				if (!empty($newMarketing)) {
					$v['year_price'] = $newMarketing['year_price'] ?? '未设置';
//					$v['discount_type'] = $this->getDiscountType($newMarketing['discount_type']);
					$v['sort']          = $newMarketing['sort'] ?? 0;
					$v['status_type']   = $newMarketing['status'] == 0 ? '关闭' : '启用';
					$v['manual_price']  = $newMarketing['manual_price'];
					$v['discount_rate'] = $newMarketing['discount_rate'];
					$v['status']        = $newMarketing['status'];
					$v['region_id']     = $newMarketing['region_id'];
				} else {
					$v['year_price'] = $v['year_price'] ?? '未设置';
//					$v['discount_type'] = $this->getDiscountType($v['discount_type']);
					$v['sort']        = $v['sort'] ?? 0;
					$v['status_type'] = $v['status'] == 0 ? '关闭' : '启用';
				}
			} else {
				$v['year_price'] = $v['year_price'] ?? '未设置';
//				$v['discount_type'] = $this->getDiscountType($v['discount_type']);
				$v['sort']        = $v['sort'] ?? 0;
				$v['status_type'] = $v['status'] == 0 ? '关闭' : '启用';
			}
			$v['is_children'] = 1;
		}

		return $classes_price_list;
	}

	public function addClassPrice($param)
	{
		$newMarketingClassesPrice = new  NewMarketingClassRegion();

		return $newMarketingClassesPrice->add($param);
	}

	public function saveClassPrice($param, $id)
	{
		$newMarketingClassesPrice = new  NewMarketingClassRegion();

		return $newMarketingClassesPrice->where(['id' => $id])->save($param);
	}

	/**
	 * @param $discount_type
	 * 0=未设置,1=周年优惠,2=单独设置,3=不设置优惠
	 */
	public function getDiscountType($discount_type)
	{
		switch ($discount_type) {
			case 0:
				$discount_type = '未设置';
				break;
			case 1:
				$discount_type = '周年优惠';
				break;
			case 2:
				$discount_type = '单独设置';
				break;
			case 3:
				$discount_type = '不设置优惠';
				break;
			default:
				$discount_type = '未设置';
				break;
		}

		return $discount_type;
	}

    //获取分类店铺数据
    public function getClassData($where) {
        $list = (new NewMarketingClassRegion())->getClassData($where);
        return $list;
    }

    //获取分类店铺列表
    public function getClassList($where) {
        $list = (new NewMarketingClassRegion())->getClassList($where);
        return $list;
    }

    //获取分类店铺名称
    public function getClassName($where) {
        $data = (new NewMarketingClassRegion())->getClassData($where);
        $catData = (new MerchantCategory())->getOneData(['cat_id' => $data['class_id']]);
        $cat_fname = $catData['cat_fid'] ? (new MerchantCategory())->getOneData(['cat_id' => $catData['cat_fid']])['cat_name'] : '';
        $name = $cat_fname ? $cat_fname . '-' . $catData['cat_name'] : $catData['cat_name'];
        return $name;
    }

	/**
	 * 获得分类购买详情
	 * @param int $catId 分类id
	 * @param int $catId 分类父id
	 * @param int $proviceId 省份id
	 */
	public function getCategoryPriceDetail($catId, $catFid, $proviceId)
	{
		$newMarketingClassesPrice = new  NewMarketingClassRegion();

		$where = [];
		$where[] = ['p.class_id', 'in', $catId.','.$catFid];// 分类id
		$where[] = ['', 'exp', Db::raw('find_in_set(",'.$proviceId.',", r.region_id) OR find_in_set(",'.$proviceId.']", r.region_id) OR find_in_set("['.$proviceId.']", r.region_id) OR find_in_set("['.$proviceId.',", r.region_id) OR r.region_id=0')];
		$where[] = ['p.is_del', '=', 0];
		$where[] = ['p.status', '=', 1];// 分类状态
		$where[] = ['r.is_del', '=', 0];
		$where[] = ['r.status', '=', 1];// 区域状态
		$field = 'p.*';

		$priceDetail = $newMarketingClassesPrice
						->alias('p')
						->field($field)
						->where($where)
						->order('p.class_id DESC,p.sort DESC')
						->join('new_marketing_region r', 'r.id=p.region_id')
						// ->fetchSql(true)
						->find();

		if(empty($priceDetail)){
            throw new \think\Exception(L_("分类未开启"), 1003);
		}
		return $priceDetail;
	}

    /**
     * 获得店铺分类购买详情
     * @param int $catId 分类id
     * @param int $catId 分类父id
     * @param int $proviceId 省份id
     */
    public function getStoreCategoryPriceDetail($catId, $catFid, $proviceId)
    {
        $newMarketingClassesPrice = new  NewMarketingClassRegion();

        $where = [];
        $where[] = ['p.class_id', 'in', $catId.','.$catFid];// 分类id
        $where[] = ['', 'exp', Db::raw('find_in_set(",'.$proviceId.',", r.region_id) OR find_in_set(",'.$proviceId.']", r.region_id) OR find_in_set("['.$proviceId.']", r.region_id) OR find_in_set("['.$proviceId.',", r.region_id) OR r.region_id=0')];
        $where[] = ['p.is_del', '=', 0];
        $where[] = ['p.status', '=', 1];// 分类状态
        $where[] = ['r.is_del', '=', 0];
        $where[] = ['r.status', '=', 1];// 区域状态
        $field = 'p.*';

        $priceDetail = $newMarketingClassesPrice
            ->alias('p')
            ->field($field)
            ->where($where)
            ->order('p.class_id DESC,p.sort DESC')
            ->join('new_marketing_region r', 'r.id=p.region_id')
            // ->fetchSql(true)
            ->find();
        return $priceDetail;
    }

    //获取分类店铺详情数据
    public function getClassDetailData($where, $data = []) {
		if($data){
			$list = $data;
		}else{
			$list = (new NewMarketingClassRegion())->getClassData($where)->toArray();
		}

        if ($list) {
            if ($list['discount_type'] == 0) {
                $fId = (new MerchantCategoryService)->getOneData([['cat_id', '=', $list['class_id']], ['cat_status', '=', 1]])['cat_fid'] ?? '';//获取全部一级下二级分类ID
                if (!$fId) {
                    return [];
                }
                $where1 = [
                    ['class_id', '=', $fId],
                    ['is_del', '=', 0],
                    ['region_id', '=', $list['region_id']]
                ];
                $list1 = (new NewMarketingClassRegion())->getClassData($where1)->toArray() ?? [];
                if (!$list1) {
                    return [];
                }
                $list1['id'] = $list['id'];
                $list1['class_id'] = $list['class_id'];
                $list = $list1;
            }
            $list['service_cycle'] = [];//服务周期
            $list['manual_price'] = $list['manual_price'] ? json_decode($list['manual_price'], true) : [];
            $catData = (new MerchantCategory)->where(['cat_id' => $list['class_id']])->find();
            $cat_fname = '';
            if ($catData['cat_fid']) {
                $cat_fname = (new MerchantCategory)->where(['cat_id' => $catData['cat_fid']])->value('cat_name') ?? '';
            }
            if ($cat_fname) {
                $list['name'] = $cat_fname . '-' . $catData['cat_name'] . ' 店铺';
            } else {
                $list['name'] = $catData['cat_name'] . ' 店铺';
            }
            switch ($list['discount_type']) {
                case 1:
                    $list['service_cycle'] = [
                        [
                            'price' => $list['year_price'],
                            'label' => '1年',
                            'years' => 1,
                            'discount' => ''
                        ],
                        [
                            'price' => 1-$list['discount_rate']/100 <= 0 ? 0 : number_format($list['year_price'] * (1-$list['discount_rate']/100), 2, '.', ''),
                            'label' => '2年',
                            'discount' => (trim(floatval((100-$list['discount_rate'])), '0') <= 0 ? '免费' : trim(floatval((100-$list['discount_rate'])), '0') . '折'),
                            'years' => 2
                        ],
                        [
                            'price' => 1-$list['discount_rate']*2/100 <= 0 ? 0 :number_format($list['year_price'] * (1-$list['discount_rate']*2/100), 2, '.', ''),
                            'label' => '3年',
                            'discount' => (trim(floatval((100-$list['discount_rate']*2)), '0') <= 0 ? '免费' : trim(floatval((100-$list['discount_rate']*2)), '0') . '折'),
                            'years' => 3
                        ],
                        [
                            'price' => 1-$list['discount_rate']*3/100 <= 0 ? 0 : number_format($list['year_price'] * (1-$list['discount_rate']*3/100), 2, '.', ''),
                            'label' => '4年',
                            'discount' => (trim(floatval((100-$list['discount_rate']*3)), '0') <= 0 ? '免费' : trim(floatval((100-$list['discount_rate']*3)), '0') . '折'),
                            'years' => 4
                        ],
                        [
                            'price' => 1-$list['discount_rate']*4/100 <= 0 ? 0 : number_format($list['year_price'] * (1-$list['discount_rate']*4/100), 2, '.', ''),
                            'label' => '5年',
                            'discount' => (trim(floatval((100-$list['discount_rate']*4)), '0') <= 0 ? '免费' : trim(floatval((100-$list['discount_rate']*4)), '0') . '折'),
                            'years' => 5
                        ]
                    ];
                    break;
                case 2:
                    $list['service_cycle'][0] = [
                        'price' => $list['year_price'],
                        'label' => '1年',
						'years' => 1,
                        'discount' => '优惠'
                    ];
                    foreach ($list['manual_price'] as $pk =>  $pv) {
                        $list['service_cycle'][$pk+1] = [
                            'price' => $pv,
                            'label' => $pk+2 . '年',
							'years' => $pk+2 ,
                            'discount' => '优惠'
                        ];
                    }
                    break;
                default:
                    $list['service_cycle'] = [
                        [
                            'price' => $list['year_price'],
                            'label' => '1年',
							'years' => 1 ,
                            'discount' => ''
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '2年',
							'years' => 2 ,
                            'discount' => ''
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '3年',
							'years' => 3 ,
                            'discount' => ''
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '4年',
							'years' => 4 ,
                            'discount' => ''
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '5年',
							'years' => 5 ,
                            'discount' => ''
                        ]
                    ];
                    break;
            }
        }
        return $list;
    }

    //计算分类店铺优惠和付款金额
    public function getDiscountPayPrice($where, $year, $num, $data = []) {
		if(empty($data)){
			$data = (new NewMarketingClassRegion())->getClassData($where);
		}
		if ($data['discount_type'] == 0) {
            $fId = (new MerchantCategoryService)->getOneData([['cat_id', '=', $data['class_id']], ['cat_status', '=', 1]])['cat_fid'] ?? '';//获取全部一级下二级分类ID
            if ($fId) {
                $where1 = [
                    ['class_id', '=', $fId],
                    ['is_del', '=', 0],
                    ['region_id', '=', $data['region_id']]
                ];
                $data1 = (new NewMarketingClassRegion())->getClassData($where1)->toArray() ?? [];
                if ($data1) {
                    $data1['id'] = $data['id'];
                    $data1['class_id'] = $data['class_id'];
                    $data = $data1;
                }
            }
        }
        $discount = 0;
        $pay = $data['year_price'];
        switch ($data['discount_type']) {
            case 1:
                $pay = 1 - ($data['discount_rate'] * ($year - 1))/100 <= 0 ? 0 : $data['year_price'] * (1 - ($data['discount_rate'] * ($year - 1))/100) * $year;
                $discount = $pay == 0 ? $data['year_price'] * $year : $data['year_price'] * ($data['discount_rate'] * ($year - 1))/100 * $year;
//                for ($i = 2; $i <= $year; $i++) {
//                    $discount += $data['year_price'] * $data['discount_rate']/100 * ($i-1);
//                    $pay += $data['year_price'] * (1 - $data['discount_rate']/100 * ($i-1));
//                }
                break;
            case 2:
                $data['manual_price'] = json_decode($data['manual_price'], true);
                for ($i = 2; $i <= $year; $i++) {
                    $discount += $data['year_price'] - $data['manual_price'][$i-2];
                    $pay += $data['manual_price'][$i-2];
                }
                break;
            default:
                $pay += $data['year_price'] * ($year-1);
                break;
        }
        $data = [
            'discount' => number_format($discount * $num, 2, '.', ''),
            'pay' => number_format($pay * $num, 2, '.', '')
        ];
        return $data;
    }
}