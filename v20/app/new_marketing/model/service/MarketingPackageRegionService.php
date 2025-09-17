<?php
/**
 * liuruofei
 * 2021/08/24
 * 套餐管理
 */
namespace app\new_marketing\model\service;

use app\new_marketing\model\db\NewMarketingPackageRegion;
use app\new_marketing\model\db\MerchantCategory;

class MarketingPackageRegionService
{

    //保存数据
    public function saveData($param) {
        $where = [
            'region_id' => $param['region_id'],
            'package_id' => $param['package_id']
        ];
        if ($this->getData($where)) {//编辑
            $res = (new NewMarketingPackageRegion())->editData($where, $param);
        } else {//添加
            $param['create_time'] = time();
            $res = (new NewMarketingPackageRegion())->addData($param);
        }
        return $res;
    }


    //通过区域设置id获得套餐详情
    public function getPackByRegion($packAreaId) {
        $where = [
            'a.id' => $packAreaId,
        ];

        $prefix = config('database.connections.mysql.prefix');
        $package = (new NewMarketingPackageRegion())
                    ->alias('a')
                    ->join($prefix.'new_marketing_package p','p.id = a.package_id')
                    ->where($where)
                    ->find()
                    ;

        if(empty($package)){
            return [];
        }
        $package = $package->toArray();
        
        $package = $this->formatData($package);
        return $package;
    }

    //获取数据
    public function getData($param) {
        $res = (new NewMarketingPackageRegion())->getOneData($param);
        return $res;
    }

    //获取联表数据
    public function getWhereData($param, $field) {
        $res = (new NewMarketingPackageRegion())->getWhereData($param, $field);
        return $res;
    }

    //设置状态
    public function setStatus($where, $status) {
        $res = (new NewMarketingPackageRegion())->setStatus($where, $status);
        return $res;
    }

    //删除
    public function del($id) {
        $res = (new NewMarketingPackageRegion())->del($id);
        return $res;
    }

    // 返回前端所需格式
    public function formatData($package) {
        $package['service_cycle'] = [];//服务周期
        $package['manual_price'] = $package['manual_price'] ? json_decode($package['manual_price'], true) : [];
        $package['store_detail'] = $package['store_detail'] ? json_decode($package['store_detail'], true) : [];

        if ($package['store_detail']) {// 店铺分类信息
            foreach ($package['store_detail'] as $sk => $sv) {
                $type_name0 = (new MerchantCategory())->getOneData(['cat_id' => $sv['type'][0]])['cat_name'] ?? '';// 父分类
                $type_name1 = !empty($sv['type'][1]) ? (new MerchantCategory())->getOneData(['cat_id' => $sv['type'][1]])['cat_name'] : '';// 子分类
                $type_name = $type_name1 ? $type_name0 . '-' . $type_name1 : $type_name0;
                $package['store_detail'][$sk]['type_name'] = $type_name;
            }
        }

        // 价格设置
        switch ($package['discount_type']) {
            case 1:// 周年优惠
                $package['service_cycle'] = [
                    [
                        'price' => $package['year_price'],
                        'label' => '1年',
                        'discount' => ''
                    ],
                    [
                        'price' => number_format($package['year_price'] * (1-$package['discount_rate']/100), 2, '.', ''),
                        'label' => '2年',
                        'discount' => trim(floatval((100-$package['discount_rate'])), '0') . '折'
                    ],
                    [
                        'price' => number_format($package['year_price'] * (1-$package['discount_rate']*2/100), 2, '.', ''),
                        'label' => '3年',
                        'discount' => trim(floatval((100-$package['discount_rate']*2)), '0') . '折'
                    ],
                    [
                        'price' => number_format($package['year_price'] * (1-$package['discount_rate']*3/100), 2, '.', ''),
                        'label' => '4年',
                        'discount' => trim(floatval((100-$package['discount_rate']*3)), '0') . '折'
                    ],
                    [
                        'price' => number_format($package['year_price'] * (1-$package['discount_rate']*4/100), 2, '.', ''),
                        'label' => '5年',
                        'discount' => trim(floatval((100-$package['discount_rate']*4)), '0') . '折'
                    ]
                ];
                break;
            case 2:// 单独设置
                $package['service_cycle'][0] = [
                    'price' => $package['year_price'],
                    'label' => '1年',
                    'discount' => '优惠'
                ];
                foreach ($package['manual_price'] as $pk =>  $pv) {
                    $package['service_cycle'][$pk+1] = [
                        'price' => $pv,
                        'label' => $pk+2 . '年',
                        'discount' => '优惠'
                    ];
                }
                break;
            case 3://不设置优惠
                $package['service_cycle'] = [
                    [
                        'price' => $package['year_price'],
                        'label' => '1年',
                        'discount' => ''
                    ],
                    [
                        'price' => $package['year_price'],
                        'label' => '2年',
                        'discount' => ''
                    ],
                    [
                        'price' => $package['year_price'],
                        'label' => '3年',
                        'discount' => ''
                    ],
                    [
                        'price' => $package['year_price'],
                        'label' => '4年',
                        'discount' => ''
                    ],
                    [
                        'price' => $package['year_price'],
                        'label' => '5年',
                        'discount' => ''
                    ]
                ];
                break;
        }
    
        return $package;
    }

    //获取商家后台套餐列表
    public function getMerSearchList($where, $field) {
        $list = (new NewMarketingPackageRegion())->getMerSearchList($where, $field);
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['service_cycle'] = [];//服务周期
                $list[$k]['manual_price'] = $v['manual_price'] ? json_decode($v['manual_price'], true) : [];
                $list[$k]['store_detail'] = $v['store_detail'] ? json_decode($v['store_detail'], true) : [];
                $list[$k]['package_des'] = '';//套餐内容
                if ($list[$k]['store_detail']) {
                    foreach ($list[$k]['store_detail'] as $sk => $sv) {
                        $type_name0 = (new MerchantCategory())->getOneData(['cat_id' => $sv['type'][0]])['cat_name'] ?? '';
                        $type_name1 = !empty($sv['type'][1]) ? (new MerchantCategory())->getOneData(['cat_id' => $sv['type'][1]])['cat_name'] : '';
                        $type_name = $type_name1 ? $type_name0 . '-' . $type_name1 : $type_name0;
                        if (!$list[$k]['package_des']) {
                            $list[$k]['package_des'] = $sv['num'] . '个' . $type_name;
                        } else {
                            $list[$k]['package_des'] = '+' . $sv['num'] . '个' . $type_name;
                        }
                        $list[$k]['store_detail'][$sk]['type_name'] = $type_name;
                    }
                }
                switch ($v['discount_type']) {
                    case 1:
                        $list[$k]['service_cycle'] = [
                            [
                                'price' => $v['year_price'],
                                'label' => '1年',
                                'discount' => '',
                                'years' => 1
                            ],
                            [
                                'price' => number_format($v['year_price'] * (1-$v['discount_rate']/100), 2, '.', ''),
                                'label' => '2年',
                                'discount' => trim(floatval((100-$v['discount_rate'])), '0') . '折',
                                'years' => 2
                            ],
                            [
                                'price' => number_format($v['year_price'] * (1-$v['discount_rate']*2/100), 2, '.', ''),
                                'label' => '3年',
                                'discount' => trim(floatval((100-$v['discount_rate']*2)), '0') . '折',
                                'years' => 3
                            ],
                            [
                                'price' => number_format($v['year_price'] * (1-$v['discount_rate']*3/100), 2, '.', ''),
                                'label' => '4年',
                                'discount' => trim(floatval((100-$v['discount_rate']*3)), '0') . '折',
                                'years' => 4
                            ],
                            [
                                'price' => number_format($v['year_price'] * (1-$v['discount_rate']*4/100), 2, '.', ''),
                                'label' => '5年',
                                'discount' => trim(floatval((100-$v['discount_rate']*4)), '0') . '折',
                                'years' => 5
                            ]
                        ];
                        break;
                    case 2:
                        $list[$k]['service_cycle'][0] = [
                            'price' => $v['year_price'],
                            'label' => '1年',
                            'discount' => '优惠',
                            'years' => 1
                        ];
                        foreach ($list[$k]['manual_price'] as $pk =>  $pv) {
                            $list[$k]['service_cycle'][$pk+1] = [
                                'price' => $pv,
                                'label' => $pk+2 . '年',
                                'discount' => '优惠',
                                'years' => $pk+2
                            ];
                        }
                        break;
                    default:
                        $list[$k]['service_cycle'] = [
                            [
                                'price' => $v['year_price'],
                                'label' => '1年',
                                'discount' => '',
                                'years' => 1
                            ],
                            [
                                'price' => $v['year_price'],
                                'label' => '2年',
                                'discount' => '',
                                'years' => 2
                            ],
                            [
                                'price' => $v['year_price'],
                                'label' => '3年',
                                'discount' => '',
                                'years' => 3
                            ],
                            [
                                'price' => $v['year_price'],
                                'label' => '4年',
                                'discount' => '',
                                'years' => 4
                            ],
                            [
                                'price' => $v['year_price'],
                                'label' => '5年',
                                'discount' => '',
                                'years' => 5
                            ]
                        ];
                        break;
                }
            }
        }
        return $list;
    }

    //获取套餐详情
    public function getDetailData($param, $field) {
        $list = (new NewMarketingPackageRegion())->getWhereData($param, $field)->toArray();
        if ($list) {
            $list['service_cycle'] = [];//服务周期
            $list['manual_price'] = $list['manual_price'] ? json_decode($list['manual_price'], true) : [];
            $list['store_detail'] = $list['store_detail'] ? json_decode($list['store_detail'], true) : [];
            $list['package_des'] = '';//套餐内容
            if ($list['store_detail']) {
                foreach ($list['store_detail'] as $sk => $sv) {
                    $type_name0 = (new MerchantCategory())->getOneData(['cat_id' => $sv['type'][0]])['cat_name'] ?? '';
                    $type_name1 = !empty($sv['type'][1]) ? (new MerchantCategory())->getOneData(['cat_id' => $sv['type'][1]])['cat_name'] : '';
                    $type_name = $type_name1 ? $type_name0 . '-' . $type_name1 : $type_name0;
                    if (!$list['package_des']) {
                        $list['package_des'] = $sv['num'] . '个' . $type_name;
                    } else {
                        $list['package_des'] = '+' . $sv['num'] . '个' . $type_name;
                    }
                    $list['store_detail'][$sk]['type_name'] = $type_name;
                }
            }
            switch ($list['discount_type']) {
                case 1:
                    $list['service_cycle'] = [
                        [
                            'price' => $list['year_price'],
                            'label' => '1年',
                            'discount' => '',
                            'years' => 1
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
                            'price' => 1-$list['discount_rate']*4/100 <= 0 ? 0 :number_format($list['year_price'] * (1-$list['discount_rate']*4/100), 2, '.', ''),
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
                        'discount' => '优惠',
                        'years' => 1
                    ];
                    foreach ($list['manual_price'] as $pk =>  $pv) {
                        $list['service_cycle'][$pk+1] = [
                            'price' => $pv,
                            'label' => $pk+2 . '年',
                            'discount' => '优惠',
                            'years' => $pk+2
                        ];
                    }
                    break;
                default:
                    $list['service_cycle'] = [
                        [
                            'price' => $list['year_price'],
                            'label' => '1年',
                            'discount' => '',
                            'years' => 1
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '2年',
                            'discount' => '',
                            'years' => 2
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '3年',
                            'discount' => '',
                            'years' => 3
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '4年',
                            'discount' => '',
                            'years' => 4
                        ],
                        [
                            'price' => $list['year_price'],
                            'label' => '5年',
                            'discount' => '',
                            'years' => 5
                        ]
                    ];
                    break;
            }
        }
        return $list;
    }

    //计算套餐优惠和付款金额
    public function getDiscountPayPrice($param, $year, $num) {
        $data = (new NewMarketingPackageRegion())->getOneData($param);
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