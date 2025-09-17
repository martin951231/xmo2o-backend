<?php


namespace app\common\model\service;


use app\common\model\db\HomeSearchRanking;
use app\common\model\db\MerchantStore;
use app\group\model\db\Group;
use app\mall\model\db\MallGoods;
use app\shop\model\db\ShopGoods;

class HomeSearchRankingService
{
    /**
     * 首页搜索排行榜前三图标
     * @param string $sort_type
     */
    private function getSortPic($sort_type = '')
    {
        $site_url = cfg('site_url');
        $data = [
            'hit' => $site_url . '/static/images/searchRanking/hot.png',
            'hit_active' => $site_url . '/static/images/searchRanking/hot_active.png',
            'sales' => $site_url . '/static/images/searchRanking/ranking.png',
            'sales_active' => $site_url . '/static/images/searchRanking/ranking_active.png',
        ];
        if ($sort_type) {
            return $data[$sort_type] ?? '';
        }
        return $data;
    }

    private function getBusinessType($type = '')
    {
        $business_type = [
            'mall' => '商城',
            'shop' => '外卖',
            'group' => '团购',
            'custom' => '自定义',
        ];
        if ($type) {
            return $business_type[$type] ?? '';
        }
        return $business_type;
    }

    private function getSelectList()
    {
        //主题色
        $mainColor = [
            [
                "title" => "红色主题",
                "bgColor" => "#FDCDC8",
                "fontColor" => "#ED2639"
            ],
            [
                "title" => "黄色主题",
                "bgColor" => "#FFE5BF",
                "fontColor" => "#FF7200"
            ],
            [
                "title" => "蓝色主题",
                "bgColor" => "#BFE5FF",
                "fontColor" => "#21a5ff"
            ],
            [
                "title" => "紫色主题",
                "bgColor" => "#E3CBFD",
                "fontColor" => "#ad63fe"
            ],
            [
                "title" => "绿色主题",
                "bgColor" => "#CAF4B5",
                "fontColor" => "#5db730"
            ],
        ];
        //业务
        $businessType = [];
        $business_type = $this->getBusinessType();
        foreach ($business_type as $key => $item) {
            $businessType[] = [
                'key' => $key,
                'value' => $item,
            ];
        }
        //类型
        $type = [
            [
                'key' => 'goods',
                'value' => '商品',
            ],
            [
                'key' => 'store',
                'value' => '店铺',
            ],
        ];
        //排行类型
        $sortType = [
            [
                'key' => 'hit',
                'value' => '点击量（热度）',
            ],
            [
                'key' => 'sales',
                'value' => '销量',
            ],
            [
                'key' => 'new',
                'value' => '新品（创建时间排序）',
            ],
        ];
        return [
            'mainColor' => $mainColor,
            'businessType' => $businessType,
            'type' => $type,
            'sortType' => $sortType
        ];
    }

    /**
     * 获取排行榜配置列表
     */
    public function getList($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $is_from_api = $params['is_from_api'] ?? 0; //1=从首页进来
        $show_position = $params['show_position'];
        $where = [
            ['show_position', '=', $show_position]
        ];
        if ($is_from_api) {
            $time = time();
            $where[] = ['status', '=', 1];
            $where[] = ['start_time', '<', $time];
            $where[] = ['end_time', '>', $time];
        }

        $sort = 'sort desc,id asc';
        $list = (new HomeSearchRanking())->getList($where, '*', $page, $pageSize, $sort);
        $data = $page > 0 ? $list['data'] : $list;
        foreach ($data as &$item) {
            $item['business_type_txt'] = $this->getBusinessType($item['business_type']);
            $item['pic'] = $item['pic'] ? replace_file_domain($item['pic']) : '';
            $item['custom_value'] = $item['custom_value'] ? json_decode($item['custom_value'], true) : [];
            $item['start_time'] = $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '';
            $item['end_time'] = $item['end_time'] ? date('Y-m-d H:i:s', $item['end_time']) : '';
            $item['preview_link'] = get_base_url().'pages/index/search?viewId='.$item['id'];
        }
        if ($page > 0) {
            $list['data'] = $data;
        } else {
            $list = $data;
        }
        if ($page > 0) {
            $list = array_merge($list, $this->getSelectList());
        }
        return $list;
    }

    /**
     * 添加/编辑排行榜配置
     */
    public function saveData($params)
    {
        $id = $params['id'];
        $save_data = [
            'name' => $params['name'],
            'pic' => $params['pic'],
            'bg_color' => $params['bg_color'],
            'font_color' => $params['font_color'],
            'business_type' => $params['business_type'],
            'type' => $params['type'],
            'sort_type' => $params['sort_type'],
            'virtual_sales' => $params['virtual_sales'],
            'start_time' => $params['start_time'] ? strtotime($params['start_time']) : 0,
            'end_time' => $params['end_time'] ? strtotime($params['end_time']) : 0,
            'sort' => $params['sort'],
            'status' => $params['status'],
            'custom_value' => $params['custom_value'],
            'show_position' => $params['show_position'],
        ];
        $this->checkSaveData($save_data);
        $save_data['custom_value'] = $save_data['custom_value'] ? json_encode($save_data['custom_value']) : '';
        try {
            if ($id) {
                $info = (new HomeSearchRanking())->where(['id' => $id])->findOrEmpty()->toArray();
                if (!$info) {
                    throw new \think\Exception(L_('编辑信息不存在/已删除！'));
                }
                $save_data['update_time'] = time();
                (new HomeSearchRanking())->where(['id' => $id])->save($save_data);
            } else {
                $save_data['create_time'] = time();
                (new HomeSearchRanking())->save($save_data);
            }
        } catch (\Exception $e) {
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 检验参数
     */
    public function checkSaveData($save_data)
    {
        $select_type = $this->getSelectList();
        if (!$save_data['name']) {
            throw new \think\Exception(L_('标题必填！'));
        }
        if ($save_data['show_position'] == 'home' && !$save_data['pic']) {
            throw new \think\Exception(L_('请上传图标！'));
        }
        if (!$save_data['bg_color'] || !$save_data['font_color']) {
            throw new \think\Exception(L_('请选择主题色！'));
        }
        $business_type = !empty($select_type['businessType']) ? array_column($select_type['businessType'], 'key') : [];
        $type = !empty($select_type['type']) ? array_column($select_type['type'], 'key') : [];
        $sort_type = !empty($select_type['sortType']) ? array_column($select_type['sortType'], 'key') : [];
        if ($save_data['business_type'] != 'custom' && !in_array($save_data['business_type'], $business_type)) {
            throw new \think\Exception(L_('业务类型错误！'));
        }
        if (!in_array($save_data['type'], $type)) {
            throw new \think\Exception(L_('类型错误！'));
        }
        if ($save_data['business_type'] != 'custom' && !in_array($save_data['sort_type'], $sort_type)) {
            throw new \think\Exception(L_('排行类型错误！'));
        }
        if (!is_numeric($save_data['virtual_sales']) || $save_data['virtual_sales'] < 0) {
            throw new \think\Exception(L_('虚拟量不能小于0！'));
        }
        if (!is_numeric($save_data['virtual_sales']) || $save_data['virtual_sales'] < 0) {
            throw new \think\Exception(L_('虚拟量不能小于0！'));
        }
        if ($save_data['start_time'] >= $save_data['end_time']) {
            throw new \think\Exception(L_('结束时间必须大于开始时间！'));
        }
        if (!is_numeric($save_data['sort']) || $save_data['sort'] < 0) {
            throw new \think\Exception(L_('排序值不能小于0！'));
        }
        if (!in_array($save_data['status'], [0, 1])) {
            throw new \think\Exception(L_('状态错误！'));
        }
        if ($save_data['show_position'] == 'home' && $save_data['business_type'] == 'custom') {
            if (!$save_data['custom_value'] || !is_array($save_data['custom_value'])) {
                throw new \think\Exception(L_('自定义信息有误！'));
            }
            foreach ($save_data['custom_value'] as $key => $item) {
                if (!$item['name']) {
                    throw new \think\Exception(L_('名称X1必填！', ['X1' => $key]));
                }
            }
        }
        return true;
    }

    /**
     * 修改排行榜配置排序值
     * @param $params
     */
    public function changeInfo($params)
    {
        $id = $params['id'];
        $value = $params['value'];
        $type = $params['type'];
        if (!in_array($type, ['status', 'sort'])) {
            throw new \think\Exception(L_('修改类型错误！'));
        }
        if (!$id) {
            throw new \think\Exception(L_('请选择要编辑的信息！'));
        }
        if ($type == 'sort' && (!is_numeric($value) || $value < 0)) {
            throw new \think\Exception(L_('排序值不能小于0！'));
        }
        if ($type == 'status' && !in_array($value, [1, 0])) {
            throw new \think\Exception(L_('状态值错误！'));
        }
        $info = (new HomeSearchRanking())->where(['id' => $id])->findOrEmpty()->toArray();
        if (!$info) {
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        try {
            (new HomeSearchRanking())->where(['id' => $id])->save([$type => $value]);
        } catch (\Exception $e) {
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 修改排行榜配置排序值
     * @param $params
     */
    public function delete($params)
    {
        $id = $params['id'];
        if (!$id) {
            throw new \think\Exception(L_('请选择要删除的信息！'));
        }
        try {
            (new HomeSearchRanking())->where(['id' => $id])->delete();
        } catch (\Exception $e) {
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     *
     * @param $params
     */
    public function getApiList($params)
    {
        $params['page'] = 0;
        $params['pageSize'] = 10;
        $data = $this->getList($params);
        $list = [];
        foreach ($data as $key => $item) {
            $list[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'pic' => $item['pic'] ? replace_file_domain($item['pic']) : '',
                'bg_color' => $item['bg_color'],
                'font_color' => $item['font_color'],
                'sort_type' => $item['sort_type'],
                'business_type' => $item['business_type'],
                'show_position' => $item['show_position'],
                'data_list' => $this->dealData($item),
            ];
        }
        return ['list' => $list];
    }

    /**
     * 处理信息
     * @param $data
     */
    public function dealData($data)
    {
        $list = [];
        switch ($data['show_position']) {
            case 'home':
                if ($data['business_type'] == 'custom') {
                    $list = $data['custom_value'];
                } elseif ($data['type'] == 'goods') {
                    $list = $this->dealBusinessGoods($data['business_type'], $data['sort_type'], $data['virtual_sales']);
                } elseif ($data['type'] == 'store') {
                    $list = $this->dealBusinessStore($data['business_type'], $data['sort_type'], $data['virtual_sales']);
                }
                break;
            case 'mall':

                break;
            case 'shop':

                break;
            case 'group':

                break;
        }
        foreach ($list as $key => $val) {
            $list[$key]['sort_type_pic'] = $this->getSortPic(($key < 3 ? $data['sort_type'] . '_active' : $data['sort_type']));
            $list[$key]['virtual_sales_txt'] = $this->getNumberTxt($data['business_type'], $data['sort_type'], $val['virtual_sales']);
        }
        return $list;
    }

    /**
     * 商品排行
     * @param $business_type
     * @param $sort_type
     * @param int $virtual_sales
     * @return array
     */
    public function dealBusinessGoods($business_type, $sort_type, $virtual_sales = 0)
    {
        if ($business_type == 'mall') {
            if ($sort_type == 'sales') {
                $field = 'g.goods_id as id,g.name,(g.sale_num + g.virtual_sales + ' . $virtual_sales . ') as total';
                $order = 'total desc,g.goods_id asc';
            } elseif ($sort_type == 'hit') {
                $field = 'g.goods_id as id,g.name,(g.browse_num + ' . $virtual_sales . ') as total';
                $order = 'total desc,g.goods_id asc';
            } else {
                $field = 'g.goods_id as id,g.name, -1 as total';
                $order = 'g.goods_id desc';
            }
            $data = (new MallGoods())->alias('g')
                ->field($field)
                ->join('merchant_store s', 'g.store_id = s.store_id')
                ->join('merchant m', 's.mer_id = m.mer_id')
                ->where([
                    ['g.status', '=', 1],
                    ['s.status', '=', 1],
                    ['s.have_mall', '=', 1],
                    ['m.status', '=', 1],
                    ['g.is_del', '=', 0],
                ])->order($order)->limit(10)->select()->toArray();
        } elseif ($business_type == 'shop') {
            if ($sort_type == 'sales') {
                $field = 'g.goods_id as id,g.store_id,g.name,(g.sell_count + g.shop_virtual_sales + ' . $virtual_sales . ') as total';
                $order = 'total desc,g.goods_id asc';
            } elseif ($sort_type == 'hit') {
                $field = 'g.goods_id as id,g.store_id,g.name,(g.hits + ' . $virtual_sales . ') as total';
                $order = 'total desc,g.goods_id asc';
            } else {
                $field = 'g.goods_id as id,g.store_id,g.name, -1 as total';
                $order = 'g.goods_id desc';
            }
            $data = (new ShopGoods())->alias('g')
                ->field($field)
                ->join('merchant_store s', 'g.store_id = s.store_id')
                ->join('merchant m', 's.mer_id = m.mer_id')
                ->where([
                    ['m.status', '=', 1],
                    ['s.status', '=', 1],
                    ['g.status', '=', 1],
                    ['s.have_shop', '=', 1],
                ])->order($order)->limit(10)->select()->toArray();
        } elseif ($business_type == 'group') {
            if ($sort_type == 'sales') {
                $field = 'g.group_id as id,g.name,(g.sale_count_total + g.virtual_num + ' . $virtual_sales . ') as total';
                $order = 'total desc,g.group_id asc';
            } elseif ($sort_type == 'hit') {
                $field = 'g.group_id as id,g.name,(g.hits + ' . $virtual_sales . ') as total';
                $order = 'total desc,g.group_id asc';
            } else {
                $field = 'g.group_id as id,g.name, -1 as total';
                $order = 'g.group_id desc';
            }
            $data = (new Group())->alias('g')
                ->field($field)
                ->join('group_store gs', 'gs.group_id = g.group_id')
                ->join('merchant_store s', 'gs.store_id = s.store_id')
                ->join('merchant m', 's.mer_id = m.mer_id')
                ->where([
                    ['m.status', '=', 1],
                    ['s.status', '=', 1],
                    ['g.status', '=', 1],
                    ['s.have_group', '=', 1],
                    ['g.group_cate', '=', 'normal'],
                    ['g.end_time', '>', time()],
                ])->order($order)->group('g.group_id')->limit(10)->select()->toArray();
        }
        $list = [];
        foreach ($data as $item) {
            $list[] = [
                'name' => $item['name'],
                'virtual_sales' => $item['total'],
                'link_url' => $this->getLikUrl($business_type, 'goods', $item['id'], ($item['store_id'] ?? 0)),
            ];
        }
        return $list;
    }

    /**
     * 返回链接
     * @param $businesstype
     * @param $type
     * @param $id
     * @param int $store_id $businesstype为shop，$type为goods时使用
     */
    public function getLikUrl($businesstype, $type, $id, $store_id = 0)
    {
        $link_url = '';
        switch ($businesstype) {
            case 'mall':
                $link_url = $type == 'goods' ? 'pages/shopmall_third/commodity_details?goods_id=' . $id : 'pages/shopmall_third/store_home/index?store_id=' . $id;
                break;
            case 'shop':
                $link_url = $type == 'goods' ? 'pages/shop_new/goodsDetail/goodsDetail?store_id=' . $store_id . '&product_id=' . $id . '&backgroundColor=06C1AE&frontColor=ffffff' . $id : 'pages/shop_new/shopDetail/shopDetail?store_id=' . $id;
                break;
            case 'group':
                $link_url = $type == 'goods' ? 'pages/group/v1/groupDetail/index?group_id=' . $id : 'pages/store/v1/home/index?store_id=' . $id;
                break;
        }
        return $link_url = $link_url ? get_base_url($link_url,($businesstype=='group'?1:0)): '';
    }

    /**
     * 店铺排行
     * @param $business_type
     * @param $sort_type
     * @param int $virtual_sales
     * @return array
     */
    public function dealBusinessStore($business_type, $sort_type, $virtual_sales = 0)
    {
        $data = [];
        $where_new_store = [
            ['s.status', '=', 1],
            ['m.status', '=', 1],
        ];
        if ($business_type == 'mall') {
            if ($sort_type == 'sales') {
                $data = (new MerchantStore())->alias('s')
                    ->field('s.store_id as id,s.name,sum(g.sale_num + g.virtual_sales + ' . $virtual_sales . ') as total')
                    ->join('merchant m', 'ms.mer_id = m.mer_id')
                    ->join('mall_goods g', 'g.store_id = s.store_id')
                    ->where([
                        ['s.status', '=', 1],
                        ['m.status', '=', 1],
                        ['g.status', '=', 1],
                        ['s.have_mall', '=', 1],
                        ['g.is_del', '=', 0],
                    ])->order('total desc,s.store_id asc')->group('s.store_id')->limit(10)->select()->toArray();
            } elseif ($sort_type == 'hit') {
                $data = (new MerchantStore())->alias('s')
                    ->field('s.store_id as id,s.name,(ms.hits + ' . $virtual_sales . ') as total')
                    ->join('merchant_store_mall ms', 'ms.store_id = s.store_id')
                    ->join('merchant m', 's.mer_id = m.mer_id')
                    ->where([
                        ['m.status', '=', 1],
                        ['s.status', '=', 1],
                    ])->order('total desc,s.store_id asc')->limit(10)->select()->toArray();
            } else {
                $where_new_store[] = ['s.have_mall', '=', 1];
            }
        } elseif ($business_type == 'shop') {
            if ($sort_type == 'sales') {
                $data = (new MerchantStore())->alias('s')
                    ->field('s.store_id as id,s.name,(ms.sale_count + ms.virtual_sale_count + ' . $virtual_sales . ') as total')
                    ->join('merchant_store_shop ms', 'ms.store_id = s.store_id')
                    ->join('merchant m', 's.mer_id = m.mer_id')
                    ->where([
                        ['m.status', '=', 1],
                        ['s.status', '=', 1],
                        ['s.have_shop', '=', 1],
                    ])->order('total desc,s.store_id asc')->group('s.store_id')->limit(10)->select()->toArray();
            } elseif ($sort_type == 'hit') {
                $data = (new MerchantStore())->alias('s')
                    ->field('s.store_id as id,s.name,(ms.hits + ' . $virtual_sales . ') as total')
                    ->join('merchant_store_shop ms', 'ms.store_id = s.store_id')
                    ->join('merchant m', 's.mer_id = m.mer_id')
                    ->where([
                        ['m.status', '=', 1],
                        ['s.status', '=', 1],
                        ['s.have_shop', '=', 1],
                    ])->order('total desc,s.store_id asc')->limit(10)->select()->toArray();

            } else {
                $where_new_store[] = ['s.have_shop', '=', 1];
            }
        } elseif ($business_type == 'group') {
            if ($sort_type == 'sales') {
                $data = (new MerchantStore())->alias('s')
                    ->field('s.store_id as id,s.name,sum(g.sale_count_total + g.virtual_num + ' . $virtual_sales . ') as total')
                    ->join('merchant m', 's.mer_id = m.mer_id')
                    ->join('group_store gs', 'gs.store_id = s.store_id')
                    ->join('group g', 'g.group_id = gs.group_id')
                    ->where([
                        ['m.status', '=', 1],
                        ['s.status', '=', 1],
                        ['g.status', '=', 1],
                        ['s.have_group', '=', 1],
                        ['g.group_cate', '=', 'normal'],
                        ['g.end_time', '>', time()],
                    ])->order('total desc,s.store_id asc')->group('s.store_id')->limit(10)->select()->toArray();
            } elseif ($sort_type == 'hit') {
                $data = (new MerchantStore())->alias('s')
                    ->field('s.store_id as id,s.name,(s.hits + ' . $virtual_sales . ') as total')
                    ->join('merchant m', 's.mer_id = m.mer_id')
                    ->where([
                        ['m.status', '=', 1],
                        ['s.status', '=', 1],
                        ['s.have_group', '=', 1],
                    ])->order('total desc,s.store_id asc')->limit(10)->select()->toArray();
            } else {
                $where_new_store[] = ['s.have_group', '=', 1];
            }
        }
        if ($sort_type == 'new') {
            $data = (new MerchantStore())->alias('s')
                ->field('s.store_id as id,s.name,-1 as total')
                ->join('merchant m', 's.mer_id = m.mer_id')
                ->where($where_new_store)->order('s.store_id desc')->limit(10)->select()->toArray();
        }
        $list = [];
        foreach ($data as $item) {
            $list[] = [
                'name' => $item['name'],
                'virtual_sales' => $item['total'],
                'link_url' => $this->getLikUrl($business_type, 'store', $item['id']),
            ];
        }
        return $list;
    }

    private function getNumberTxt($business_type, $sort_type, $virtual_sales)
    {
        $txt = '';
        if ($business_type != 'custom' && $sort_type != 'new') {
            $txt = $sort_type == 'sales' ? '销量' : '热度';
            if ($virtual_sales >= 10000) {
                $txt .= number_format(($virtual_sales / 10000),1) . 'w'; //保留一位小数
            } else {
                $txt .= $virtual_sales;
            }
        }elseif ($business_type == 'custom'){
            $txt = $virtual_sales;
        }
        return $txt;
    }
}