<?php

namespace app\group\model\service;

use app\group\model\db\GroupRenovationCustom;
use app\group\model\db\GroupRenovationCustomStoreSort as GroupRenovationCustomStoreSortModel;
use app\group\model\db\GroupStore;
use app\group\model\service\GroupRenovationCustomService;
use app\group\model\db\Group;
use app\merchant\model\db\MerchantStore;
use app\group\model\service\GroupService;

class GroupRenovationCustomStoreSortService
{
    public $groupRenovationCustomStoreSortModel = null;

    public function __construct()
    {
        $this->groupRenovationCustomStoreSortModel = new GroupRenovationCustomStoreSortModel();
    }

    /**获取首页 发现页自定义分类 团购商品列表
     * @param array $param
     * @return array
     */
    public function getCustomGroupList($param)
    {
        $custom_id = $param['custom_id'] ?? 0;
        $city_id = $param['city_id'] ?? '';
        $user_long = $param['user_long'] ?? 0.00;
        $user_lat = $param['user_lat'] ?? 0.00;
        $page = $param['page'] ?? 0;
        $pageSize = $param['pageSize'] ?? 10;
        $no_booking_appoint = $param['no_booking_appoint']??0;
        $custom_sort = $this->getOne($where = ['custom_id' => $custom_id]);
        if (empty($custom_sort)) {
            //前端请求自定义分类时，如果店铺管理没有数据，系统默认添加数据，方便后续业务
            $this->addCustomStoreSort($custom_id);
        }
        $count = $this->groupRenovationCustomStoreSortModel->getListCount($where = ['b.custom_id' => $custom_id, 'm.city_id' => $city_id]);
        if ($count > 0) {
            //获取当前团购类型
            $group_renovation_custom = (new GroupRenovationCustom())->where(['custom_id'=>$custom_id])->find();
            $group_store_where = [];
            //$group_store_where[] = ['a.store_id','in',$store_ids];
            $group_store_where[] = ['b.status', '=', 1];
            $group_store_where[] = ['b.begin_time', '<', time()];
            $group_store_where[] = ['b.end_time', '>', time()];
            if($group_renovation_custom&&$group_renovation_custom['type']){
                $group_store_where[] = ['b.cat_id', '=', $group_renovation_custom['type']];
            }
            if($no_booking_appoint){
                $group_store_where[] = ['b.group_cate','<>','booking_appoint'];
            }
            $group_list = (new GroupStore())->alias('a')
                ->field('a.store_id,b.group_id,b.name,b.old_price,b.price')
                ->join('group b','a.group_id = b.group_id')
                ->where($group_store_where)
                ->order('score_mean desc')
                ->select()
                ->toArray();
            $group_data = [];
            foreach ($group_list as $group) {
                $group_data[$group['store_id']][] = $group;
            }
            $store_ids = array_column($group_list,'store_id');
            $store_ids = array_flip($store_ids);
            $store_ids = array_flip($store_ids);
            $store_ids = array_values($store_ids);
            $where = [];
            $where[] = ['b.custom_id','=',$custom_id];
            $where[] = ['m.city_id','=',$city_id];
            $where[] = ['s.status','=',1];
            $where[] = ['s.store_id','in',$store_ids];

            if (isset($param['have_group'])) {
                $where[] = ['s.have_group', '=', $param['have_group']];
            }

            $field = 's.*,c.cat_name,l.score_mean';
            $sort = 'b.sort desc';
            $merchant_store_info = $this->groupRenovationCustomStoreSortModel->getList($where, $field, $sort, $page, $pageSize);
            $merchant_store_info = $merchant_store_info->toArray();

            $list = [];
            foreach ($merchant_store_info as $value) {
                $arr['logo'] = empty($value['logo']) ? '' : thumb($value['logo'], 200, 200, 'fill');
				if(!$arr['logo'] && $value['pic_info']){
				    $pic_info = replace_file_domain(explode(';', $value['pic_info'])[0]);
					$arr['logo'] = $pic_info?thumb($pic_info, 200, 200, 'fill'):'';
				}
				
                $arr['name'] = $value['name'];
                $arr['score'] = !empty($value['score_mean']) ? get_format_number($value['score_mean']) : '5.0';
                $arr['permoney'] = !empty($value['permoney']) ? $value['permoney'] : 0.0;
                $arr['cat_name'] = !empty($value['cat_name']) ? $value['cat_name'] : '';
                $arr['address'] = $value['adress'];
                $distance = 0;
                if($user_long>0 && $user_lat>0){
                    $distance = getDistance($user_long, $user_lat, $value['long'], $value['lat']);
                    $distance = sprintf("%.2f", $distance / 1000);
                }
                $arr['distance'] = $distance <= 0 ? '' : $distance . 'km';
                $arr['url'] = cfg('site_url') . '/packapp/plat/pages/store/homePage?store_id=' . $value['store_id'];
                if (isset($group_data[$value['store_id']])) {
                    $group_list = [];
                    foreach ($group_data[$value['store_id']] as $key => $val) {
                        if ($key < 2) {
                            $a['group_id'] = $val['group_id'];
                            $a['name'] = $val['name'];
                            $a['old_price'] = get_format_number($val['old_price']);
                            $a['price'] = get_format_number($val['price']);
                            $group_list[] = $a;
                        }
                    }
                    $arr['group_list'] = $group_list;
                } else {
                    $arr['group_list'] = [];
                }
                $list[] = $arr;
            }
        } else {
            $list = [];
        }

        $returnArr = [];
        $returnArr['count'] = $count;
        $returnArr['page'] = ceil($count / $pageSize);
        $returnArr['list'] = $list;
        return $returnArr;
    }


    public function addCustomStoreSort($custom_id)
    {
        $custom_info = (new GroupRenovationCustomService())->getOne($where = ['custom_id' => $custom_id]);
        $param = [];
        $param['status'] = 1;
        $param['is_not_cashing'] = 1;
        if ($custom_info['type'] != 0) {
            $param['sort_id'] = $custom_info['type'];
        }
        $group_goods_list = (new GroupService())->getGroupGoodsList($param);
        $group = isset($group_goods_list['list']) ? $group_goods_list['list'] : [];
        if (!empty($group)) {
            $mer_group = array_unique(array_column($group, 'mer_id'));
            $mer_where = [
                ['mer_id', 'in', $mer_group],
                ['status', '=', 1]
            ];
            $store = (new MerchantStore())->getSome($mer_where, true, 'store_id desc');
            $store_list = $store->toArray();
            $store_list = array_column($store_list, NUll, 'store_id');
            $addData = [];
            if (!empty($store_list)) {
                foreach ($store_list as $store) {
                    $arr['custom_id'] = $custom_id;
                    $arr['store_id'] = $store['store_id'];
                    $arr['sort'] = 0;
                    $arr['create_time'] = time();
                    $addData[] = $arr;
                }
            }
            $this->groupRenovationCustomStoreSortModel->addAll($addData);
        }
    }

    /**获取自定义活动推荐店铺管理列表
     * @param $param
     */
    public function getList($param)
    {
        $custom_id = $param['custom_id'] ?? 0;
        $page = $param['page'] ?? 0;
        $pageSize = $param['pageSize'] ?? 10;
        $keyword = $param['keyword'] ?? '';
        $custom_info = (new GroupRenovationCustomService())->getOne($where = ['custom_id' => $custom_id]);

        if (empty($custom_info)) {
            throw new \think\Exception(L_("店铺活动推荐信息不存在"), 1003);
        }

        $res = $this->getSome($where = ['custom_id' => $custom_id]);

        if (empty($res) || $custom_info['type'] != $custom_info['old_type']) {
            $this->groupRenovationCustomStoreSortModel->del($where = ['custom_id' => $custom_id]);
            $this->addCustomStoreSort($custom_id);
        }


        $condition = [];
        // 排序
        $order = [
            'b.sort' => 'DESC',
            'b.id' => 'ASC'
        ];
        $condition[] = ['b.custom_id', '=', $custom_id];

        if (!empty($keyword)) {
            $condition[] = ['m.name', 'like', '%' . $keyword . '%'];
        }

        $group_store_where = [];
        $group_store_where[] = ['b.status', '=', 1];
        $group_store_where[] = ['b.begin_time', '<', time()];
        $group_store_where[] = ['b.end_time', '>', time()];
        if($custom_info&&$custom_info['type']){
            $group_store_where[] = ['b.cat_id', '=', $custom_info['type']];
        }
        $group_list = (new GroupStore())->alias('a')
            ->field('a.store_id,b.group_id,b.name,b.old_price,b.price')
            ->join('group b','a.group_id = b.group_id')
            ->where($group_store_where)
            ->order('score_mean desc')
            ->select()
            ->toArray();
        $group_data = [];
        foreach ($group_list as $group) {
            $group_data[$group['store_id']][] = $group;
        }
        $store_ids = array_column($group_list,'store_id');
        $store_ids = array_flip($store_ids);
        $store_ids = array_flip($store_ids);
        $store_ids = array_values($store_ids);

        $condition[] = ['s.status','=',1];
        $condition[] = ['s.store_id','in',$store_ids];


        // 商品列表
        $field = 'b.*,s.name as store_name,s.last_time,s.phone,m.mer_id,m.name as merchant_name';
        $list = $this->groupRenovationCustomStoreSortModel->getList($condition, $field, $order, $page, $pageSize);
        $count = $this->groupRenovationCustomStoreSortModel->getListCount($condition);
        $merchant = (new Group())->getGroupCountBy($where = ['status' => 1], 'mer_id,COUNT(*) as nums');
        $merchant = array_column($merchant, NULL, 'mer_id');
        foreach ($list as &$value) {
            $value['last_time'] = date('Y - m - d H:i:s', $value['last_time']);
            $value['nums'] = isset($merchant[$value['mer_id']]) ? $merchant[$value['mer_id']]['nums'] : 0;
        }
        $returnArr = [];
        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
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
            $result = $this->groupRenovationCustomStoreSortModel->insertAll($data);
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
            $result = $this->groupRenovationCustomStoreSortModel->where($where)->update($data);
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

        $result = $this->groupRenovationCustomStoreSortModel->getOne($where, $order);
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
            $result = $this->groupRenovationCustomStoreSortModel->getSome($where, $field, $order, $page, $limit);
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
            $count = $this->groupRenovationCustomStoreSortModel->getCount($where);
        } catch (\Exception $e) {
            return 0;
        }
        return $count;
    }
}