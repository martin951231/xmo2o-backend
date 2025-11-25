<?php

namespace app\group\model\service;

use app\common\model\service\GoodsScoreService;
use app\group\model\db\GroupRenovationCustomGroupSort as GroupRenovationCustomGroupSortModel;
use app\group\model\service\GroupService;

class GroupRenovationCustomGroupSortService
{
    public $groupRenovationCustomGroupSortModel = null;

    public function __construct()
    {
        $this->groupRenovationCustomGroupSortModel = new GroupRenovationCustomGroupSortModel();
    }


    public function addCustomGroupSort($custom_id)
    {
        $custom_info = (new GroupRenovationCustomService())->getOne($where = ['custom_id' => $custom_id]);
        $param = [];
        $param['status'] = 1;
        if ($custom_info['type'] != 0) {
            $param['sort_id'] = $custom_info['type'];
        }
        $group_goods_list = (new GroupService())->getGroupGoodsList($param);
        $group_list = isset($group_goods_list['list']) ? $group_goods_list['list'] : [];

        if (!empty($group_list)) {
            $addData = [];
            foreach ($group_list as $group) {
                $arr['custom_id'] = $custom_id;
                $arr['group_id'] = $group['group_id'];
                $arr['sort'] = 0;
                $arr['create_time'] = time();
                $addData[] = $arr;
            }
            $this->groupRenovationCustomGroupSortModel->addAll($addData);
        }
    }

    /**获得团购发现页团购分类商品管理列表
     * @param $param
     */
    public function getList($param)
    {
        $custom_id = $param['custom_id'] ?? 0;
        $city_id = $param['city_id'] ?? 0;//城市id
        $page = $param['page'] ?? 0;
        $pageSize = $param['pageSize'] ?? 10;
        $keyword = $param['keyword'] ?? '';
        $cat_id = $param['cat_id'] ?? 0;
        $sort = $param['sort'] ?? '';
        $no_booking_appoint = $params['no_booking_appoint'] ?? 0;
        $custom_info = (new GroupRenovationCustomService())->getOne($where = ['custom_id' => $custom_id]);

        if (empty($custom_info)) {
            throw new \think\Exception(L_("团购分类展示信息不存在"), 1003);
        }

        $res = $this->getSome($where = ['custom_id' => $custom_id]);

        if (empty($res) || $custom_info['type'] != $custom_info['old_type']) {
            $this->groupRenovationCustomGroupSortModel->del($where = ['custom_id' => $custom_id]);
            $this->addCustomGroupSort($custom_id);
        }


        $condition = [];
        // 排序
        $order = [
            'b.sort' => 'DESC',
        ];
        $condition[] = ['b.custom_id', '=', $custom_id];

        if (!empty($keyword)) {
            $condition[] = ['g.name', 'like', '%' . $keyword . '%'];
        }

        // 团购分类
        if ($cat_id > 0) {
            $condition[] = ['g.cat_id', '=', $cat_id];
        }

        // 城市ID
        if ($city_id > 0) {
            $condition[] = ['m.city_id', '=', $city_id];
        }
        
        if(!empty($param['is_not_cashing'])){
            $condition[] = ['g.group_cate', '<>', 'cashing'];
        }
        if($no_booking_appoint){
            $condition[] = ['g.group_cate', '<>', 'booking_appoint'];
        }

        $condition[] = ['g.status', '=', 1];
        $condition[] = ['g.begin_time', '<', $_SERVER['REQUEST_TIME']];
        $condition[] = ['g.end_time', '>', $_SERVER['REQUEST_TIME']];

        // 智能排序
        if (!empty($sort)) {
            if ($sort == 'defaults') {
                $order = [
                    'g.group_id' => 'DESC',
                ];
            } elseif ($sort == 'score') {
                $order = [
                    'g.score_mean' => 'DESC',
                ];
            }
        }


        // 商品列表
        $field = 'b.*,m.mer_id,m.name as merchant_name,g.name as group_name,g.add_time,g.old_price,g.price,g.pic,g.sale_count,g.group_cate,g.score_percent';
        $list = $this->groupRenovationCustomGroupSortModel->getList($condition, $field, $order, $page, $pageSize);
        $count = $this->groupRenovationCustomGroupSortModel->getCountBy($condition);
        $groupImage = new GroupImageService();
        foreach ($list as &$value) {
            $tmp_pic_arr = explode(';', $value['pic']);
            $value['pic'] = $groupImage->getImageByPath($tmp_pic_arr[0], 's');
            if(!$value['pic']&&$value['group_cate']=='cashing'){
                //代金券默认图片
                $value['pic'] = cfg('site_url').'/static/images/group/cashing.png';
            }
            $value['add_time'] = empty($value['add_time']) ? '' : date("Y-m-d H:i:s");
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($value['price'],'group',false,$value['score_percent']);
            $value['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $value['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        }
        $returnArr = [];
        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
        $returnArr['page'] = ceil($count / $pageSize);
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

    /**删除商品列表数据
     * @param $param
     * @return bool
     */
    public function del($custom_id)
    {
        if ($custom_id > 0) {
            $where = ['custom_id' => $custom_id];
            $result = $this->groupRenovationCustomGroupSortModel->del($where);
            return $result;
        }
        return true;
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
            $result = $this->groupRenovationCustomGroupSortModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
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
            $result = $this->groupRenovationCustomGroupSortModel->where($where)->update($data);
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

        $result = $this->groupRenovationCustomGroupSortModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        try {
            $result = $this->groupRenovationCustomGroupSortModel->getSome($where, $field, $order, $page, $limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取总数
     * @param $where array
     * @return array
     */
    public function getCount($where)
    {
        try {
            $count = $this->groupRenovationCustomGroupSortModel->getCount($where);
        } catch (\Exception $e) {
            return 0;
        }
        return $count;
    }
}