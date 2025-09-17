<?php

namespace app\group\model\service;

use app\group\model\db\GroupRenovationCombineGoods as GroupRenovationCombineGoodsModel;
use app\group\model\service\GroupConfigRenovationService;

class GroupRenovationCombineGoodsService
{
    public $groupRenovationCombineGoodsModel = null;

    public function __construct()
    {
        $this->groupRenovationCombineGoodsModel = new GroupRenovationCombineGoodsModel();
    }

    /**获得超值组合商品总数
     * @param array $param
     * @return array|int
     */
    public function getCombineCount($param = [])
    {
        $config_id = $param['config_id'] ?? 0;
        $cat_id = $param['sort_id'] ?? 0;

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

        // 分类ID
        if ($cat_id > 0) {
            $condition[] = [
                ['g.cat_id', '=', $cat_id],
            ];
        }


        $count = $this->groupRenovationCombineGoodsModel->getCombineCount($condition);

        return $count;
    }

    /**获得超值组合商品列表
     * @param array $param
     * @return array|int
     */
    public function getCombineList($param = [])
    {
        $config_id = $param['config_id'] ?? 0;
        $cat_id = $param['sort_id'] ?? 0;
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

        // 分类ID
        if ($cat_id > 0) {
            $condition[] = [
                ['g.cat_id', '=', $cat_id],
            ];
        }

        $field = 'b.config_id,g.*';
        // 商品列表
        $list = $this->groupRenovationCombineGoodsModel->getBindList($condition, $field, $order, $page, $limit);
        if (empty($list)) {
            return [];
        }
        $list = $list->toArray();
        return $list;
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getBindList($param = [])
    {
        $config_id = $param['config_id'] ?? 0;
        $page = $param['page'] ?? 0;
        $limit = $param['limit'] ?? 0;

        $condition = [];

        $order['b.sort'] = 'DESC';

        $condition[] = [
            ['b.config_id', '=', $config_id],
        ];

        $field = 'b.id,b.sort as cfg_sort,g.*';

        // 商品列表
        $list = $this->groupRenovationCombineGoodsModel->getBindList($condition, $field, $order, $page, $limit);
        if (empty($list)) {
            return [];
        }
        $list = $list->toArray();

        // 查看分类相关信息
        $catIdArr = $list ? array_column($list, 'cat_id') : [];
        $where = [
            ['cat_id', 'in', implode(',', $catIdArr)]
        ];
        $catArr = (new GroupCategoryService())->getSome($where);
        $catArr = array_merge($catArr, [['cat_id' => '0', 'cat_name' => L_('其他')]]);
        $catArr = array_column($catArr, 'cat_name', 'cat_id');

        foreach ($list as &$_group) {
            $_group['cat_name'] = $catArr[$_group['cat_id']] ?? '';
            $_group['start_time'] = date('Y-m-d H:i', $_group['start_time']);
            $_group['end_time'] = date('Y-m-d H:i', $_group['end_time']);
            $_group['status'] = $_group['status'] == 1 ? L_('开启') : L_('关闭');
            $_group['detail_url'] = cfg('site_url') . '/packapp/plat/pages/group/groupCombineDetail?combine_id=' . $_group['combine_id'];
            $_group['price'] = get_format_number($_group['price']);
            $_group['old_price'] = get_format_number($_group['old_price']);
            $_group['cfg_sort'] = $_group['cfg_sort'];
        }

        if (isset($param['renovation']) && $param['renovation'] == 1) {//装修过滤过期数据
            foreach ($list as $_key => $group) {
                if ($group['status'] == '关闭' || strtotime($group['end_time']) < time()) {
                    unset($list[$_key]);
                }
            }
        }
        return array_values($list);
    }

    /**获得团购超值组合已装修的商品列表
     * @param $param
     */
    public function getRenovationCombineGoodsList($param)
    {
        $cat_id = $param['cat_id'] ?? 0;
        $type = $param['type'] ?? 0;
        $where = [
            ['cat_id', '=', $cat_id],
            ['type', '=', $type],
        ];
        $info = (new GroupConfigRenovationService())->getOne($where);
        if (!empty($info)) {
            // 获得绑定商品列表
            $param['config_id'] = $info['config_id'];
            $groupGoods = $this->getBindList($param);
            $returnArr['group_list'] = $groupGoods;
        } else {
            $groupGoods = [];
        }
        $returnArr = [];
        $returnArr['group_list'] = $groupGoods;
        return $returnArr;
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
            $result = $this->groupRenovationCombineGoodsModel->where($where)->update($data);
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
            $result = $this->groupRenovationCombineGoodsModel->where($where)->delete();
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
            $result = $this->groupRenovationCombineGoodsModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }
}