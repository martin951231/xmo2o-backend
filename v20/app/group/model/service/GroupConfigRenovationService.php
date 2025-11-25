<?php

namespace app\group\model\service;

use app\group\model\db\GroupConfigRenovation as GroupConfigRenovationModel;
use app\group\model\service\GroupRenovationCombineGoodsService;

class GroupConfigRenovationService
{
    public $groupConfigRenovationModel = null;

    public function __construct()
    {
        $this->groupConfigRenovationModel = new GroupConfigRenovationModel();
    }

    /**团购自定义配置装修基本信息
     * @param $param
     */
    public function getGroupCfgInfo($param)
    {
        $cat_id = $param['cat_id'] ?? 0;
        $type = $param['type'] ?? 0;
        $where = [
            ['cat_id', '=', $cat_id],
            ['type', '=', $type],
        ];
        $rs = $this->groupConfigRenovationModel->getOne($where);
        if (!empty($rs)) {
            $info = $rs->toArray();
            // 获得绑定商品列表
            $param['config_id'] = $info['config_id'];
            $param['renovation'] = 1;
            if ($type == 1) {
                $groupGoods = (new GroupConfigRenovationGoodsService())->getBindList($param);
            } else {
                $groupGoods = (new GroupRenovationCombineGoodsService())->getBindList($param);
            }

            foreach ($groupGoods as &$good)
            {
                $good['begin_time'] = date('Y-m-d H:i:s',$good['begin_time']);
                $good['end_time'] = date('Y-m-d H:i:s',$good['end_time']);
            }

            $returnArr['group_list'] = $groupGoods;
        } else {
            $info = $groupGoods = [];
        }
        $returnArr = [];
        $returnArr['info'] = $info;
        $returnArr['group_list'] = $groupGoods;
        return $returnArr;
    }

    /**团购自定义配置装修基本信息
     * @param $param
     */
    public function getRenovationGoodsList($param)
    {
        $cat_id = $param['cat_id'] ?? 0;
        $type = $param['type'] ?? 0;
        $where = [
            ['cat_id', '=', $cat_id],
            ['type', '=', $type],
        ];
        $rs = $this->groupConfigRenovationModel->getOne($where);
        if (!empty($rs)) {
            $info = $rs->toArray();
            // 获得绑定商品列表
            $param['config_id'] = $info['config_id'];
            $groupGoods = (new GroupConfigRenovationGoodsService())->getBindList($param);
            foreach ($groupGoods as $key => $group)
            {
                if ($group['status_str'] == '已过期') {
                    unset($groupGoods[$key]);
                }
            }
            $returnArr['group_list'] = $groupGoods;
        } else {
            $groupGoods = [];
        }
        $returnArr = [];
        $returnArr['group_list'] = $groupGoods;
        return $returnArr;
    }

    /**
     * 优选商品编辑活动
     * @param $where
     * @return array
     */
    public function editGroupRenovation($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $type = $param['type'] ?? 0;
        $goodsList = $param['goods_list'] ?? [];
        if(isset($param['system_type'])){
            unset($param['system_type']);
        }
        unset($param['goods_list']);
        if(isset($param['system_type'])){
            unset($param['system_type']);
        }
        if ($goodsList) {
            $merIdArr = [];//验证商家，一个商家只能添加一个商品
            foreach ($goodsList as $key => $value) {
                if (in_array($value['mer_id'], $merIdArr)) {
                    throw new \think\Exception(L_("同一个商家不能添加多个商品，请修改后再提交！"));
                }
                $merIdArr[] = $value['mer_id'];
            }
        }

        $param['create_time'] = time();

        // 查询商品是否已经添加过
        $where = [
            ['cat_id', '=', $cat_id],
            ['type', '=', $type],
        ];

        $goods = $this->getOne($where);

        if ($goods) {
            //编辑
            $res = $this->updateThis($where, $param);
            $config_id = $goods['config_id'];
        } else {
            // 新增
            $config_id = $res = $this->add($param);
        }

        // 商品保存
        if ($goodsList) {
            $data = $group_ids = [];
            foreach ($goodsList as $key => $value) {
                if(!in_array($value['group_id'],$group_ids)) {
                    $data[] = [
                        'config_id' => $config_id,
                        'group_id' => $value['group_id'],
                        'sort' => empty($value['cfg_sort']) ? 0 : $value['cfg_sort'],
                        'mer_id' => $value['mer_id'],
                        'create_time' => time(),
                    ];
                    $group_ids[] = $value['group_id'];
                }
            }

            // 删除原有的
            $where = [
                'config_id' => $config_id
            ];
            (new GroupConfigRenovationGoodsService())->del($where);

            // 保存新加的
            (new GroupConfigRenovationGoodsService())->addAll($data);
        }else{//删除全部商品
            $where = [
                'config_id' => $config_id
            ];
            (new GroupConfigRenovationGoodsService())->del($where);
        }

        if ($res === false) {
            throw new \think\Exception(L_("操作失败请重试"), 1003);

        }
        return true;
    }

    /**
     * 超值组合编辑活动
     * @param $where
     * @return array
     */
    public function editGroupCombineRenovation($param = [])
    {
        $cat_id = $param['cat_id'] ?? 0;
        $type = $param['type'] ?? 0;
        $goodsList = $param['goods_list'] ?? [];
        if(isset($param['system_type'])){
            unset($param['system_type']);
        }
        unset($param['goods_list']);
        
        $param['create_time'] = time();

        // 查询商品是否已经添加过
        $where = [
            ['cat_id', '=', $cat_id],
            ['type', '=', $type],
        ];

        $goods = $this->getOne($where);

        if ($goods) {
            //编辑
            $res = $this->updateThis($where, $param);
            $config_id = $goods['config_id'];
        } else {
            // 新增
            $config_id = $res = $this->add($param);
        }

        // 商品保存
        if ($goodsList) {
            $data = [];
            foreach ($goodsList as $key => $value) {
                $data[] = [
                    'config_id' => $config_id,
                    'combine_id' => $value['combine_id'],
                    'sort' => empty($value['cfg_sort']) ? 0 : $value['cfg_sort'],
                    'create_time' => time(),
                ];
            }

            // 删除原有的
            $where = [
                'config_id' => $config_id
            ];
            (new GroupRenovationCombineGoodsService())->del($where);

            // 保存新加的
            (new GroupRenovationCombineGoodsService())->addAll($data);
        }

        if ($res === false) {
            throw new \think\Exception(L_("操作失败请重试"), 1003);

        }
        return true;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->groupConfigRenovationModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
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
            $result = $this->groupConfigRenovationModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupConfigRenovationModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }
}