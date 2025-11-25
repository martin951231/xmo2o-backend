<?php
require LIB_PATH . 'ORG/Erp/Type/Miaomaiyun.class.php';

class ErpService
{
    public static function getInstance()
    {
        return (new Miaomaiyun());
    }

    public static function getErpConfigByStoreid($storeId)
    {
        $store = D('Merchant_store')->where(['store_id' => $storeId])->find();
        $merchant = D('Merchant')->where(['mer_id' => $store['mer_id']])->find();
        if(empty($merchant['miaomaiyun_erp_appid'])){
            throw_exception('商户未配置ERP');
        }
        return [
            'miaomaiyun_erp_appid' => C('config.miaomaiyun_erp_appid'),
            'miaomaiyun_erp_appsecret' => C('config.miaomaiyun_erp_appsecret'),
            'miaomaiyun_erp_storeid' => $store['miaomaiyun_erp_storeid'],
            'miaomaiyun_erp_pt_user_id' => $store['miaomaiyun_erp_pt_user_id'] ?: 99,
        ];
    }

    public static function getSortDetail($sortId)
    {
        $sort = D('Shop_goods_sort')->where(['sort_id' => $sortId])->find();
        if (empty($sort)) {
            throw new Exception('分类不存在');
        }
        if ($sort['fid'] > 0) {
            $parentSort = D('Shop_goods_sort')->where(['sort_id' => $sort['fid']])->find();
            if (!$parentSort['miaomai_category_id']) {
                throw new Exception('上级分类未同步ERP系统');
            }
            $sort['miaomai_parent_id'] = $parentSort['miaomai_category_id'];
        } else {
            $sort['miaomai_parent_id'] = 0;
        }
        return $sort;
    }

    public static function getGoodsDetail($goodsId)
    {
        $goods = D('Shop_goods')->where(['goods_id' => $goodsId])->find();
        if (empty($goods)) {
            throw new Exception('商品不存在');
        }
        return $goods;
    }

    public static function getUser($uid)
    {
        $user = D('User')->where(['uid' => $uid])->find();
        if (empty($user)) {
            throw new Exception('用户不存在');
        }
        return $user;
    }

    /**
     * 同步店铺分类，如果是子分类自动判断同步上级分类
     * @author: 张涛
     * @date: 2021/04/02
     */
    public static function syncGoodsCategory($sortId)
    {
        try {
            $sort = self::getSortDetail($sortId);
            $config = self::getErpConfigByStoreid($sort['store_id']);
            $pSort = $gSort = [];
            $sort['fid'] && $pSort = self::getSortDetail($sort['fid']);
            $pSort['fid'] && $gSort = self::getSortDetail($pSort['fid']);

            $syncOrignSort = array_filter([$gSort, $pSort, $sort]);
            $instance = self::getInstance();
            $instance->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret']);
            foreach ($syncOrignSort as $s) {
                $instance->clearParam()
                    ->setParam('name', $s['sort_name'])
                    ->setParam('parent_id', $s['miaomai_parent_id'])
                    ->setParam('category_code', $s['sort_id']);
                if ($s['miaomai_category_id']) {
                    $instance->setParam('category_id', $s['miaomai_category_id'])->setParam('type', 2);
                } else {
                    $instance->setParam('type', 1);
                }
                $result = $instance->run(Miaomaiyun::CATEGORY_SET);
                if ($result['status'] == 'y') {
                    D('Shop_goods_sort')->where(['sort_id' => $s['sort_id']])->save(['miaomai_category_id' => $result['data']['category_id']]);
                } else {
                    throw new Exception($result['info']);
                }
            }
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function delGoodsCategory($sortId)
    {
        try {
            $sort = self::getSortDetail($sortId);
            $config = self::getErpConfigByStoreid($sort['store_id']);
            return (self::getInstance())->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'])
                ->setParam('category_id', $sort['miaomai_category_id'])
                ->run(Miaomaiyun::CATEGORY_DEL);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function syncGoods($goodsId)
    {
        try {
            $goodsMod = D('Shop_goods');
            $specMod = D('Shop_goods_spec');
            $specValMod = D('Shop_goods_spec_value');
            $goodsInfo = self::getGoodsDetail($goodsId);
            $config = self::getErpConfigByStoreid($goodsInfo['store_id']);
            $instance = self::getInstance();
            $instance->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret']);

            //存在规格同步规格
            $specs = $specMod->where(['goods_id' => $goodsId])->select();
            $specId = 0;
            if ($specs) {
                if ($goodsInfo['miaomaiyun_spec_id']) {
                    $specId = $goodsInfo['miaomaiyun_spec_id'];
                } else {
                    //创建存货类型。存货类型名称格式:存货类型_商品ID
                    $instance->clearParam();
                    $instance->setParam('spec_name', '存货类型_' . $goodsId * 0);
                    $instance->setParam('spec_id', 0);
                    $instance->setParam('type', 1);
                    $specRs = $instance->run(Miaomaiyun::SPEC_SET);
                    if ($specRs['status'] == 'y') {
                        $specId = $specRs['data']['spec_id'];
                        $goodsMod->where(['goods_id' => $goodsId])->save(['miaomaiyun_spec_id' => $specId]);
                    } else {
                        throw new Exception($specRs['info']);
                    }
                }

                foreach ($specs as $s) {
                    $instance->clearParam();
                    $instance->setParam('attr_name', $s['name']);
                    $instance->setParam('spec_id', $specId);
                    $instance->setParam('spec_attr_id', $s['miaomaiyun_spec_attr_id']);
                    $instance->setParam('type', $s['miaomaiyun_spec_attr_id'] ? 2 : 1);
                    $attrRs = $instance->run(Miaomaiyun::SPEC_ATTR_SET);
                    if ($attrRs['status'] == 'y') {
                        $attrId = $attrRs['data']['spec_attr_id'];
                        $specMod->where(['id' => $s['id']])->save(['miaomaiyun_spec_attr_id' => $attrId]);
                    } else {
                        throw new Exception($attrRs['info']);
                    }
                }
            }

            //规格值
            $sids = array_column($specs, 'id');
            $specVals = $specValMod->alias('sv')->join('INNER JOIN ' . C('DB_PREFIX') . 'shop_goods_spec AS s ON s.id = sv.sid')
                ->where(['sv.sid' => ['IN', $sids]])
                ->field('sv.*,s.miaomaiyun_spec_attr_id')
                ->select();
            if ($specVals) {
                foreach ($specVals as $v) {
                    $instance->clearParam();
                    $instance->setParam('val_name', $v['name']);
                    $instance->setParam('spec_attr_id', $v['miaomaiyun_spec_attr_id']);
                    $instance->setParam('spec_attr_val_id', $v['miaomaiyun_spec_attr_val_id']);
                    $instance->setParam('type', $v['miaomaiyun_spec_attr_val_id'] ? 2 : 1);
                    $attrValRs = $instance->run(Miaomaiyun::SPEC_ATTR_VAL_SET);
                    if ($attrValRs['status'] == 'y') {
                        $valId = $attrValRs['data']['spec_attr_val_id'];
                        $specValMod->where(['id' => $v['id']])->save(['miaomaiyun_spec_attr_val_id' => $valId]);
                    } else {
                        throw new Exception($attrValRs['info']);
                    }
                }
            }

            //同步主商品信息
            $category = M('Shop_goods_sort')->where(['sort_id' => $goodsInfo['sort_id']])->find();
            if (empty($category)) {
                throw new Exception($goodsInfo['name'] . '没有设置分类');
            }
            //如果分类没有同步，则主动同步一下分类
            if (empty($category['miaomai_category_id'])) {
                self::syncGoodsCategory($goodsInfo['sort_id']);
                $category = M('Shop_goods_sort')->where(['sort_id' => $goodsInfo['sort_id']])->find();
            }

            $instance->clearParam();
            $instance->setParam('goods_title', $goodsInfo['name']);
            $instance->setParam('category_id', $category['miaomai_category_id']);
            $instance->setParam('unit', $goodsInfo['unit']);
            $instance->setParam('spec_id', $specId);
            $instance->setParam('buying_price', $goodsInfo['price']);
            $instance->setParam('only_coding', $goodsInfo['number']);
            $instance->setParam('warn_num', 1);
            if ($goodsInfo['miaomaiyun_g_m_id']) {
                $instance->setParam('type', 2);
                $instance->setParam('g_m_id', $goodsInfo['miaomaiyun_g_m_id']);
            } else {
                $instance->setParam('type', 1);
            }

            $goods = [];
            if ($goodsInfo['spec_value']) {
                //多规格
                $specList = $goodsMod->format_spec_value($goodsInfo['spec_value'], $goodsId);
                foreach ($specList['list'] as $l) {
                    $item = [
                        'barcode' => $l['number'] ?: $l['index'],
                        'retail_price' => $l['price'],
                        'trade_price' => 0,
                        'yh_price' => 0,
                        'cost_price' => 0,
                        'ps_price' => 0,
                        'sys_unit' => $goodsInfo['unit'],
                        'conversion' => 1,
                        'yh_mode' => 0
                    ];
                    foreach ($l['spec'] as $sv) {
                        $item['spec_' . $sv['miaomaiyun_spec_attr_id']] = $sv['miaomaiyun_spec_attr_val_id'];
                    }
                    $goods[] = $item;
                }
            } else {
                //单规格
                $goods[] = [
                    'barcode' => $goodsInfo['number'],
                    'barcode_id' => 0,
                    'retail_price' => $goodsInfo['price'],
                    'trade_price' => 0,
                    'yh_price' => 0,
                    'cost_price' => 0,
                    'ps_price' => 0,
                    'sys_unit' => $goodsInfo['unit'],
                    'conversion' => 1,
                    'yh_mode' => 0,
                ];
            }
            $instance->setParam('goods', json_encode($goods));
            $goodsRs = $instance->run(Miaomaiyun::GOODS_SETS);
            if ($goodsRs['status'] == 'y') {
                $goodsMod->where(['goods_id' => $goodsId])->save(['miaomaiyun_g_m_id' => $goodsRs['data']['g_m_id']]);
            } else {
                throw new Exception($goodsRs['info']);
            }
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public static function delGoods($goodsId)
    {
        $goodsInfo = self::getGoodsDetail($goodsId);
        if ($goodsInfo['g_m_ids']) {
            $config = self::getErpConfigByStoreid($goodsInfo['store_id']);
            $instance = self::getInstance();
            $instance->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'])->setParam('g_m_ids', $goodsInfo['g_m_ids'])->run(Miaomaiyun::GOODS_DEL);
        }
    }

    public function syncUserScoreChange($uid, $score, $desc = '')
    {
        $user = self::getUser($uid);
        $instance = self::getInstance();
        $instance->setConfig(C('config.miaomaiyun_erp_appid'), C('config.miaomaiyun_erp_appsecret'));
        if (empty($user['miaomaiyun_member_id'])) {
            self::syncUser($uid);
            $user = self::getUser($uid);
        }
        if ($user['miaomaiyun_member_id']) {
            $instance->clearParam()->setParam('stores_id', C('config.miaomaiyun_erp_storeid'))
                ->setParam('points', getFormatNumber($score))
                ->setParam('member_id', $user['miaomaiyun_member_id'])
                ->setParam('xnote', 'O2O系统同步-' . $desc)
                ->run(Miaomaiyun::SET_MEMBER_POINTS);
        }
    }

    public static function syncUser($uid)
    {
        $user = self::getUser($uid);
        $instance = self::getInstance();
        $instance->setConfig(C('config.miaomaiyun_erp_appid'), C('config.miaomaiyun_erp_appsecret'));

        $levelName = '';
        if ($user['level'] > 0) {
            $levelName = M('User_level')->where(['level' => $user['level']])->getField('lname');
        }

        //获取等级id
        $gradeId = 0;
        if ($levelName) {
            $gradeRs = $instance->clearParam()
                ->setParam('grade_name', $levelName)
                ->run(Miaomaiyun::GET_MEMBER_GRADE);
            if ($gradeRs['status'] == 'y') {
                $gradeId = $gradeRs['data'][0]['grade_id'] ?? 0;
            } else {
                //throw new Exception($gradeRs['info']);
            }
        }

        //创建用户
        if (empty($user['miaomaiyun_member_id'])) {
            if (empty($gradeId)) {
                //return false;
            }

            $erpUser = $instance->clearParam()
                ->setParam('mobile', $user['phone'])
                ->setParam('name', $user['nickname'])
                ->setParam('grade_id', $gradeId)
                ->setParam('relegated_time', $user['level_expire_time']?:0)
                ->run(Miaomaiyun::MEMBER_MK);
            $memberId = $erpUser['data']['member_id'] ?? 0;
            $memberId && D('User')->where(['uid' => $uid])->save(['miaomaiyun_member_id' => $memberId]);

            //初始化同步积分
            $instance->clearParam()->setParam('stores_id', C('config.miaomaiyun_erp_storeid'))
                ->setParam('points', getFormatNumber($user['score_count']))
                ->setParam('member_id', $memberId)
                ->setParam('xnote', 'O2O系统同步-积分初始化')
                ->run(Miaomaiyun::SET_MEMBER_POINTS);

            //同步余额
            /*$instance->clearParam()->setParam('stores_id', C('config.miaomaiyun_erp_storeid'))
                ->setParam('money', getFormatNumber($user['now_money']))
                ->setParam('member_id', $memberId)
                ->setParam('xnote', '余额初始化')
                ->run(Miaomaiyun::SET_MEMBER_MONEY);*/
        } else {
            $memberId = $user['miaomaiyun_member_id'];
            //同步等级
            $instance->clearParam()->setParam('mobile', $user['phone'])
                ->setParam('name', $user['nickname'])
                ->setParam('grade_id', $gradeId)
                ->setParam('relegated_time', $user['level_expire_time']?:0)
                ->run(Miaomaiyun::MEMBER_UP);
        }
    }

    public function syncUseScore($uid, $score)
    {

    }

    public static function updateGoodsStock($goodsId, $num)
    {
        $goodsInfo = self::getGoodsDetail($goodsId);
        if ($goodsInfo['miaomaiyun_g_m_id']) {
            $config = self::getErpConfigByStoreid($goodsInfo['store_id']);
            $instance = self::getInstance();
            $instance->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret']);

            //获取商品基本信息
            $instance->setParam('pos_num', createRandomStr(8));
            $instance->setParam('stores_id', $config['miaomaiyun_erp_storeid'])->setParam('keyword', $goodsInfo['miaomaiyun_g_m_id'])->setParam('fields', 'g_m_id');
            $erpGoods = $instance->run(Miaomaiyun::GET_GOODS_ALL);
            $erpGoods = $erpGoods['data'];
            if (empty($erpGoods)) {
                return false;
            }
            $baseInfo = [];
            foreach ($erpGoods as $v) {
                if ($v['g_m_id'] == $goodsInfo['miaomaiyun_g_m_id']) {
                    $baseInfo = $v;
                    break;
                }
            }

            //申请更新库存
            $instance->clearParam()->setParam('stores_id', $config['miaomaiyun_erp_storeid'])->setParam('pt_user_id', $config['miaomaiyun_erp_pt_user_id']);
            $instance->setParam('bgd_code', 'BG' . date('Ymd') . createRandomStr(8, true));
            $instance->setParam('js_user_cname', 'O2O系统经办人');
            $instance->setParam('bgd_mode', $num > 0 ? 1 : 2);
            $instance->setParam('remark', 'O2O系统同步');
            $goodsLists = [
                [
                    'xnum' => abs($num),
                    'price' => $goodsInfo['price'],
                    'xnote' => '',
                    'buying_price' => $baseInfo['buying_price'],
                    'bg_name' => 'O2O同步',
                    'goods_id' => $baseInfo['goods_id'],
                    'barcode_id' => $baseInfo['barcode_id'],
                    'purchase_tax_rate' => $baseInfo['tax_rate'],
                    'conversion' => $baseInfo['conversion'],
                    'total' => $goodsInfo['price'] * abs($num),
                    'price_tax' => $goodsInfo['price'],
                    'total_tax' => $goodsInfo['price'] * abs($num)
                ]
            ];
            $instance->setParam('goods_list_json', json_encode($goodsLists));
            $bgdRs = $instance->run(Miaomaiyun::BGD_ADD);
            if ($bgdRs['status'] == 'y') {
                $instance->clearParam()
                    ->setParam('stores_id', $config['miaomaiyun_erp_storeid'])
                    ->setParam('pt_user_id', $config['miaomaiyun_erp_pt_user_id'])
                    ->setParam('bgd_id', $bgdRs['data']['bgd_id'])
                    ->setParam('bgd_mode', $num > 0 ? 1 : 2)
                    ->run(Miaomaiyun::BGD_SH);
            } else {
                throw new Exception($bgdRs['info']);
            }
        }
    }

    public static function syncErpGoodsToSystem($erpGoodsId,$storeId)
    {
        //$erpGoodsId = 9464;
        //$storeId = 429;
        $tm = time();
        $config = self::getErpConfigByStoreid($storeId);
        $instance = self::getInstance();
        //获取商品基本信息
        $instance->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret']);
        $instance->setParam('pos_num', createRandomStr(8));
        $instance->setParam('stores_id', $config['miaomaiyun_erp_storeid'])->setParam('keyword', $erpGoodsId)->setParam('fields', 'g_m_id');
        $erpGoods = $instance->run(Miaomaiyun::GET_GOODS_ALL);
        $erpGoods = $erpGoods['data'];
        if (empty($erpGoods)) {
            return false;
        }
        $goods = [];
        foreach ($erpGoods as $v) {
            if ($v['g_m_id'] == $erpGoodsId) {
                $goods[] = $v;
            }
        }
        if (empty($goods)) {
            return false;
        }
        $goodsDetail = $goods[0];

        //商品分类同步
        $sortMod = D('Shop_goods_sort');
        $instance->clearParam();
        $erpCategorys = $instance->run(Miaomaiyun::CATEGORY_GET);
        $categorys = array_combine(array_column($erpCategorys['data']['category'], 'category_id'), $erpCategorys['data']['category']);
        $thisCate = $categorys[$goodsDetail['category_id']] ?? [];
        if (empty($thisCate)) {
            return [];
        }
        $thisPath = array_filter(explode('@', $thisCate['path']));
        $cates = array_map(function ($id) use ($categorys) {
            return $categorys[$id] ?? [];
        }, $thisPath);

        $sortId = 0;
        foreach ($cates as $c) {
            $data = [
                'store_id' => $storeId,
                'sort_name' => $c['name'],
                'sort' => $c['sort'],
                'week' => '1,2,3,4,5,6,0',
                'status' => 1,
                'fid' => $sortId,
                'level' => $c['depth'] + 1,
                'sort_status' => 1,
                'miaomai_category_id' => $c['category_id']
            ];

            $old = $sortMod->where($data)->find();
            if ($old) {
                $sortId = $old['sort_id'];
            } else {
                $sortId = $sortMod->add($data);
            }
        }

        //保存商品基本信息
        $goodsMod = D('Shop_goods');
        $specRetailPrice = array_column($goods, 'retail_price');
        $specTradePrice = array_column($goods, 'trade_price');
        $goodsData = [
            'sort_id' => $sortId,
            'store_id' => $storeId,
            'name' => $goodsDetail['goods_title'],
            'describe' => '',
            'unit' => $goodsDetail['unit_name'],
            'old_price' => max($specTradePrice),
            'price' => max($specRetailPrice),
            'last_time' => $tm,
            'status' => ($goodsDetail['is_sales'] && $goodsDetail['goods_status'] == 1) ? 1 : 0,
            'sort' => $goodsDetail['sort'] ?? 0,
            'sell_type' => $goodsDetail['metering_id'] == 1 ? 1 : 2,
            'miaomaiyun_g_m_id' => $erpGoodsId
        ];

        //获取库存
        $stockRs = $instance->clearParam()->setParam('stores_id', $config['miaomaiyun_erp_storeid'])
            ->setParam('only_coding', $goodsDetail['only_coding'])
            ->run('/api_v2/goods_set/stock_query');

        $stock = $stockRs['data'][0]['stock_num'] ?? 0;
        $goodsData['stock_num'] = $stock;
        $stock < -1 && $goodsData['status'] = 0;

        $old = $goodsMod->where(['miaomaiyun_g_m_id' => $erpGoodsId])->find();
        if ($old) {
            $goodsId = $old['goods_id'];
            $goodsMod->where(['goods_id' => $goodsId])->save($goodsData);
        } else {
            $goodsId = $goodsMod->add($goodsData);
        }


        //获取多规格信息
        $specMod = D('Shop_goods_spec');
        $specValMod = D('Shop_goods_spec_value');
        $specfi = array_unique(array_filter(array_column($goods, 'specifi')));
        $goodsSpec = [];
        foreach ($specfi as $item) {
            $split = array_map(function ($i) {
                $i = trim($i, "[,]");
                $i = explode(':', $i);
                return count($i) == 2 ? $i : [];
            }, array_filter(explode(' ', $item)));
            foreach ($split as $s) {
                $goodsSpec[$s[0]][] = $s[1];
                $goodsSpec[$s[0]] = array_unique($goodsSpec[$s[0]]);
            }
        }
        if ($goodsSpec) {
            $newspecIds = [];
            foreach ($goodsSpec as $specName => $specVals) {
                $specData = [
                    'store_id' => $storeId,
                    'goods_id' => $goodsId,
                    'name' => $specName
                ];
                $oldSpec = $specMod->where($specData)->find();
                if ($oldSpec) {
                    $specId = $oldSpec['id'];
                } else {
                    $specId = $specMod->add($specData);
                }
                $newspecIds[] = $specId;

                $newSpecValIds = [];
                foreach ($specVals as $val) {
                    $specValData = [
                        'sid' => $specId,
                        'name' => $val,
                    ];
                    $oldSpecVal = $specValMod->where($specValData)->find();
                    if ($oldSpecVal) {
                        $newSpecValIds[] = $oldSpecVal['id'];
                    } else {
                        $newSpecValIds[] = $specValMod->add($specValData);
                    }
                }
                $specValMod->where(['sid' => $specId, 'id' => ['NOT IN', $newSpecValIds]])->delete();
            }
            $specMod->where(['goods_id' => $goodsId, 'id' => ['NOT IN', $newspecIds]])->delete();

            //更新spec_value字段
            $specString = '';
            foreach ($goods as $g) {
                $specifi = $g['specifi'];
                if ($specifi) {
                    $thisSpecInfo = array_map(function ($i) {
                        $i = trim($i, "[,]");
                        $i = explode(':', $i);
                        return count($i) == 2 ? $i : [];
                    }, array_filter(explode(' ', $specifi)));
                    $specValId = [];
                    foreach ($thisSpecInfo as $v) {
                        $specValId[] = $specMod->alias('s')->join(C('DB_PREFIX') . 'shop_goods_spec_value as v ON s.id=v.sid')
                            ->where(['s.goods_id' => $goodsId, 's.store_id' => $storeId, 's.name' => $v[0], 'v.name' => $v[1]])
                            ->getField('v.id');
                    }
                    if ($specString) {
                        $specString .= '#' . implode($specValId, ':') . '|' . $g['retail_price'] . ':' . $g['retail_price'] . '::' . $stock . ':0::' . '||' . $g['barcode'] . '||';
                    } else {
                        $specString .= implode($specValId, ':') . '|' . $g['retail_price'] . ':' . $g['retail_price'] . '::' . $stock . ':0::' . '||' . $g['barcode'] . '||';
                    }
                }
            }
            if ($specString) {
                $goodsMod->where(['goods_id' => $goodsId])->save(['spec_value' => $specString]);
            }
        }
    }

    public static function test(){
        $config = self::getErpConfigByStoreid(429);
        $instance = self::getInstance();
        $instance->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret']);
        //$instance->setParam('g_m_id',17196);
        $instance->setParam('nonce_str',createRandomStr(11));
        $instance->setParam('pos_num',md5(123456));
        $instance->setParam('stores_id',10)->setParam('keyword','17464')->setParam('xtype','barcode_id');
        //$rs = $instance->run('/api_v2/goods/goods_search');
        $rs = $instance->run('/api_v2/stock_query/xlist');

        //获取分类
        //$cate = $instance->clearParam()->run('/api_v2/goods_set/get_category');
        dump($rs);exit;
    }

}