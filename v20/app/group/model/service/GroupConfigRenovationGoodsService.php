<?php

namespace app\group\model\service;

use app\group\model\db\GroupConfigRenovationGoods as GroupConfigRenovationGoodsModel;

class GroupConfigRenovationGoodsService
{
    public $groupConfigRenovationGoodsModel = null;

    public function __construct()
    {
        $this->groupConfigRenovationGoodsModel = new GroupConfigRenovationGoodsModel();
    }


    /**获得优选商品商品总数
     * @param array $param
     * @return array|int
     */
    public function getSelectCount($param = [])
    {
        $config_id = $param['config_id'] ?? 0;
        $cat_id = $param['sort_id'] ?? 0;
        $city_id = $param['city_id'] ?? 0;//城市id

        $condition = [];

        $condition[] = [
            ['b.config_id', '=', $config_id],
        ];

        // 状态 1启用 0禁用
        $condition[] = [
            ['g.status', '=', 1],
        ];

        // 开始时间
        $condition[] = [
            ['g.begin_time', '<', time()],
        ];

        // 关闭时间
        $condition[] = [
            ['g.end_time', '>', time()],
        ];

        // 分类ID
        if ($cat_id > 0) {
            $condition[] = [
                ['g.cat_id', '=', $cat_id],
            ];
        }

        // 城市ID
        if ($city_id > 0) {
            $condition[] = [
                ['m.city_id', '=', $city_id],
            ];
        }


        $count = $this->groupConfigRenovationGoodsModel->getSelectCount($condition);

        return $count;
    }

    /**获得优选商品商品列表
     * @param array $param
     * @return array|int
     */
    public function getSelectList($param = [])
    {
        $config_id = $param['config_id'] ?? 0;
        $page = $param['page'] ?? 0;
        $limit = $param['limit'] ?? 0;
        $cat_id = $param['sort_id'] ?? 0;
        $city_id = $param['city_id'] ?? 0;//城市id

        $condition = [];

        $order['b.sort'] = 'DESC';

        $condition[] = [
            ['b.config_id', '=', $config_id],
        ];

        // 状态 1启用 0禁用
        $condition[] = [
            ['g.status', '=', 1],
        ];

        // 分类ID
        if ($cat_id > 0) {
            $condition[] = [
                ['g.cat_id', '=', $cat_id],
            ];
        }

        // 开始时间
        $condition[] = [
            ['g.begin_time', '<', time()],
        ];

        // 关闭时间
        $condition[] = [
            ['g.end_time', '>', time()],
        ];

        // 城市ID
        if ($city_id > 0) {
            $condition[] = [
                ['m.city_id', '=', $city_id],
            ];
        }

        $field = 'b.*,b.sort as cfg_sort,m.name as merchant_name,g.*';
        // 商品列表
        $list = $this->groupConfigRenovationGoodsModel->getBindList($condition, $field, $order, $page, $limit);
        if (empty($list)) {
            return [];
        }
        $list = $list->toArray();
        return $list;
    }

    /**获得超值组合商品总数
     * @param array $param
     * @return array|int
     */
    public function getCombineCount($param = [])
    {
        $config_id = $param['config_id'] ?? 0;

        $condition = [];

        $condition[] = [
            ['b.config_id', '=', $config_id],
        ];

        // 状态 1启用 0禁用
        $condition[] = [
            ['g.status', '=', 1],
        ];

        // 开始时间
        $condition[] = [
            ['g.start_time', '<', time()],
        ];

        // 关闭时间
        $condition[] = [
            ['g.end_time', '>', time()],
        ];


        $count = $this->groupConfigRenovationGoodsModel->getCombineCount($condition);

        return $count;
    }

    /**获得超值组合商品列表
     * @param array $param
     * @return array|int
     */
    public function getCombineList($param = [])
    {
        $config_id = $param['config_id'] ?? 0;
        $page = $param['page'] ?? 0;
        $limit = $param['limit'] ?? 0;

        $condition = [];

        $order['b.sort'] = 'DESC';

        $condition[] = [
            ['b.config_id', '=', $config_id],
        ];

        // 状态 1启用 0禁用
        $condition[] = [
            ['g.status', '=', 1],
        ];

        // 开始时间
        $condition[] = [
            ['g.start_time', '<', time()],
        ];

        // 关闭时间
        $condition[] = [
            ['g.end_time', '>', time()],
        ];

        $field = 'b.config_id,g.*';
        // 商品列表
        $list = $this->groupConfigRenovationGoodsModel->getCombineList($condition, $field, $order, $page, $limit);
        if (empty($list)) {
            return [];
        }
        $list = $list->toArray();
        return $list;
    }

    /**
     * 获得优选商品商品列表
     * @param $where
     * @return array
     */
    public function getBindList($param = [], $type = 0)
    {
        $config_id = $param['config_id'] ?? 0;
        $page = $param['page'] ?? 0;
        $limit = $param['limit'] ?? 0;
        // 团购id
        $groupId = $param['group_id'] ?? 0;

        $condition = [];

        $order['b.sort'] = 'DESC';

        $condition[] = [
            ['b.config_id', '=', $config_id],
        ];

        $field = 'b.*,b.sort as cfg_sort,m.name as merchant_name,g.*';

//        var_dump($order);die;
        // 商品列表
        $list = $this->groupConfigRenovationGoodsModel->getBindList($condition, $field, $order, $page, $limit);
        if (empty($list)) {
            return [];
        }
        $list = $list->toArray();
        if ($type == 1) {
            return $list;
        }

        $nowGroup = [];
        $groupImage = new GroupImageService();
        foreach ($list as $key => &$_group) {
            $tmp_pic_arr = explode(';', $_group['pic']);
            $_group['image'] = $groupImage->getImageByPath($tmp_pic_arr[0], 'm');
            if (isset($param['image_size']) && $param['image_size']) {
                $_group['image'] = thumb_img($_group['image'], $param['image_size']['width'], $param['image_size']['height'], 'fill');
            } else {
                $_group['image'] = thumb_img($_group['image'], '200', '200', 'fill');
            }
            //$_group['status_str'] = (new GroupService())->getStatus($_group);
            $_group['status_str'] = $_group['status']==1?'正常':'关闭';
            $_group['url'] = cfg('site_url') . '/wap.php?c=Groupnew&a=detail&source=group_combine&group_id=' . $_group['group_id'];
            $_group['price'] = get_format_number($_group['price']);
            $_group['old_price'] = get_format_number($_group['old_price']);

            if ($groupId > 0 && $_group['group_id'] == $groupId) {
                $nowGroup[] = $_group;
                unset($list[$key]);
            }
        }
        if (isset($param['renovation']) && $param['renovation'] == 1) {//装修过滤过期数据
            foreach ($list as $_key => $group) {
                if ($group['status_str'] == '已过期') {
                    unset($list[$_key]);
                }
            }
        }
        $list = array_merge($nowGroup, $list);
        return array_values($list);
    }

    /**编辑排序
     * @param $data
     */
    public function edit($param)
    {
        $id = $param['id'] ?? 0;
        $sort = $param['sort'] ?? 0;
        $where = ['id' => $id];
        $data = ['sort' => $sort];
        $res = $this->updateThis($where, $data);
        return $res;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }

        try {
            $result = $this->groupConfigRenovationGoodsModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }


    /**
     *删除
     * @param $where array
     * @return array
     */
    public function del($where)
    {
        if (empty($where)) {
            return false;
        }
        try {
            $result = $this->groupConfigRenovationGoodsModel->where($where)->delete();
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupConfigRenovationGoodsModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }
}