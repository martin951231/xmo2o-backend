<?php


namespace app\employee\model\service;

use app\common\model\db\ConfigData;
use app\common\model\service\AreaService;
use app\employee\model\db\EmployeeActivity;
use app\employee\model\db\EmployeeActivityAdver;
use app\employee\model\db\EmployeeActivityGoods;
use app\employee\model\db\EmployeeCardLable;
use app\employee\model\db\EmployeeCardUser;
use app\mall\model\service\AppOtherService;
use app\mall\model\service\WxappOtherService;
use app\shop\model\db\ShopGoods;
use think\facade\Db;
use think\Model;

class EmployeeActivityService
{

    public $EmployeeActivity = null;
    public $EmployeeActivityGoods = null;
    public $EmployeeCardLable = null;
    public $EmployeeActivityAdver = null;
    public $ShopGoods = null;
    public $EmployeeCardUser = null;
    public $statusArr = [];
    public $configDataModel = null;
    public function __construct()
    {
        $this->EmployeeActivity = new EmployeeActivity();
        $this->EmployeeActivityGoods = new EmployeeActivityGoods();
        $this->EmployeeCardLable = new EmployeeCardLable();
        $this->EmployeeActivityAdver = new EmployeeActivityAdver();
        $this->ShopGoods = new ShopGoods();
        $this->EmployeeCardUser = new EmployeeCardUser();
        $this->configDataModel = new ConfigData();
        $this->statusArr = [
            0 => '未开始',
            1 => '进行中',
            2 => '已结束',
        ];
    }

    /**
     * 活动首页
     */
    public function activityIndex($param) {
        $return = [
            'name'   => '',
            'banner' => [],
            'store'  => [
                [
                    'value' => 0,
                    'title' => '全部',
                    'children' => [
                        [
                            'value' => 0,
                            'title' => '全部'
                        ]
                    ],
                ]
            ],
            'open_free_jump' => '',
            'free_jump_url'  => '',
        ];
        $activityInfo  = $this->EmployeeActivity->where(['pigcms_id' => $param['activity_id'],'is_del'=>0,'status'=>1,'is_temp'=>0])->find();
        if(empty($activityInfo)){
            throw new \think\Exception('活动不存在', 1003);
        }

        $return['name'] = $activityInfo['name'] ?? '';
        $return['open_free_jump'] = $activityInfo['open_free_jump'] ?? 0;
        $return['free_jump_url']  = $activityInfo['free_jump_url'] ?? '';
        $return['status'] = $this->getStatus($activityInfo);// 状态

        $uid = $param['uid'] ?? 0;
        $return['banner'] = $this->getAdverByActivityId($param['activity_id'], 10, false, $uid);
        
        $where = [
            ['a.is_del', '=', 0],
            ['a.activity_id', '=', $param['activity_id']],
            ['g.status', '=', 1],
            ['g.goods_type','=',0],
            ['m.status','=',1],
            ['s.status','=',1],
            ['s.cat_id', '>', 0]
        ];
        $sql = 's.mer_id NOT IN (' . cfg('set_employee_card_mer_id') . ') OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
//        $sql = 's.mer_id <> ' . cfg('set_employee_card_mer_id') . ' OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
        if (!empty($param['uid'])) {
            $sql1 = "(select ecu.lable_ids from pigcms_employee_card_user as ecu where ecu.uid = " . $param['uid'] . " AND ecu.mer_id = g.employee_mer_id AND ecu.status = 1)";
            $sql .= " OR (concat(',',g.employee_lables,',') regexp concat(',(',replace(" . $sql1 . ",',','|'),'),'))";
        }
        $where[] = ['', 'exp', Db::raw($sql)];
        $store = $this->EmployeeActivityGoods->getStoreList($where);
        if (!empty($store)) {
            $arr = [];
            foreach ($store as $v) {
                if ($v['cat_fid'] == 0) {
                    $v['cat_fid'] = $v['cat_id'];
                }
                if (isset($arr[$v['cat_fid']])) {
                    $arr[$v['cat_fid']]['children'][] = [
                        'value' => $v['value'],
                        'title' => $v['title']
                    ];
                } else {
                    $arr[$v['cat_fid']] = [
                        'value' => $v['cat_fid'],
                        'title' => $v['cat_fname'],
                        'children' => [
                            [
                                'value' => $v['value'],
                                'title' => $v['title']
                            ]
                        ],
                    ];
                }
            }
            $arr = array_values($arr);
            $return['store'] = array_merge($return['store'], $arr);
        }
        // 浏览量
        $this->EmployeeActivity->where(['pigcms_id'=>$param['activity_id']])->inc('view_num',1)->update();

        return $return;
    }

    /**
     * 活动列表
     */
    public function activityList($param) {
        $return = [
        ];

        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $uid = $param['uid'] ?? 0;

        $where[] = ['a.is_del', '=', 0];
        $where[] = ['a.is_temp', '=', 0];
        $where[] = ['a.status', '=', 1];

        // 搜索
        if(isset($param['keyword']) && $param['keyword']){
            $where[] = ['a.name', 'like', '%'.$param['keyword'].'%'];
        }

        if($uid){
            // 查询用户绑定的员工标签
            $lableIds = (new EmployeeCardUser())->getSome([['uid', '=', $uid],['status', '=', 1], ['lable_ids','<>', '']], 'lable_ids');
            $lableIdsArr = [];
            foreach($lableIds as $lables){
                $lables = explode(',',$lables['lable_ids']);
                $lableIdsArr = array_merge($lables, $lableIdsArr);
            }
            if($lableIdsArr){
                $where[] = ['', 'exp', Db::raw( '(b.lable_id in ('.implode(',', $lableIdsArr).') OR a.only_show_lable=0)')];
            }else{
                $where[] = ['a.only_show_lable', '=', 0];
            }
        }

        $limit = [
            'page' => $page,
            'list_rows' => $pageSize
        ];
        $data = $this->EmployeeActivity->getListJoin($where, $limit);
    
        if (!empty($data['data'])) {
            foreach ($data['data'] as &$value) {
                $value['cover_image'] = replace_file_domain( $value['cover_image']);
                $value['status'] = $this->getStatus($value);
                $value['status_str'] = $this->statusArr[$value['status']] ?? '';
                $value['start_time'] = $value['start_time'] ? date('Y-m-d', $value['start_time']) : '';
                $value['end_time'] = $value['end_time'] ? date('Y-m-d', $value['end_time']) : '';
                $value['detail_url'] = get_base_url('pages/lifeTools/employee/activityArea?activity_id=').$value['pigcms_id'];
                if ($value['open_free_jump'] == 1) {
                    $value['detail_url'] = $value['free_jump_url'];
                }
            }
        }
        return $data;
    }

    public function getStatus($activity) {
        if(!$activity['start_time'] && !$activity['end_time']){// 兼容以前数据
            return 1;// 进行中
        }elseif($activity['start_time'] > strtotime(date('Y-m-d'))){
            return 0;// 未开始
        }elseif($activity['start_time'] <= strtotime(date('Y-m-d')) && $activity['end_time']+86400 > time()){
            return 1;// 进行中
        }elseif($activity['end_time']+86400 < time()){
            return 2;// 已结束
        }
    }
    
    /**
     * 活动首页商品
     */
    public function activityIndexGoods($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['a.is_del', '=', 0],
            ['a.activity_id', '=', $param['activity_id']],
            ['g.status', '=', 1],
            ['g.goods_type','=',0],
            ['m.status','=',1],
            ['s.status','=',1]
        ];
        if (!empty($param['store_id'])) {
            $where[] = ['s.store_id', '=', $param['store_id']];
        }
        $sql = 's.mer_id NOT IN (' . cfg('set_employee_card_mer_id') . ') OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
//        $sql = 's.mer_id <> ' . cfg('set_employee_card_mer_id') . ' OR g.open_employee_card = 0'; //只显示未开启员工专卖商品
        if (!empty($param['uid'])) {
            $sql1 = "(select ecu.lable_ids from pigcms_employee_card_user as ecu where ecu.uid = " . $param['uid'] . " AND ecu.mer_id = g.employee_mer_id AND ecu.status = 1)";
            $sql .= " OR (concat(',',g.employee_lables,',') regexp concat(',(',replace(" . $sql1 . ",',','|'),'),'))";
//            $employee_card_user_lable = $this->EmployeeCardUser->where(['uid' => $param['uid'], 'mer_id' => cfg('set_employee_card_mer_id'), 'status' => 1])->value('lable_ids');
//            if (!empty($employee_card_user_lable)) {
//                $lable_ids = explode(',', $employee_card_user_lable);
//                $sql .= ' OR (';
//                foreach ($lable_ids as $lk => $lv) {
//                    if ($lk == 0) {
//                        $sql .= 'FIND_IN_SET("' . $lv . '", g.employee_lables)';
//                    } else {
//                        $sql .= ' OR FIND_IN_SET("' . $lv . '", g.employee_lables)';
//                    }
//                }
//                $sql .= ')';
//            }
        }
        $where[] = ['', 'exp', Db::raw($sql)];
        $result = $this->EmployeeActivityGoods->getList($where, $limit);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['price'] = get_format_number($v['price']);
                $result['data'][$k]['small_price'] = get_format_number($v['small_price']);
                $result['data'][$k]['has_spec'] = !empty($v['spec_value']) ? true : false;
                $imageAry = $v['image'] ? explode(';',$v['image']) : [];
                $result['data'][$k]['image']    = $imageAry ? replace_file_domain($imageAry[0]) : '';
                $result['data'][$k]['url']      = get_base_url('pages/shop_new/goodsDetail/goodsDetail?store_id=' . $v['store_id'] . '&goods_id=' . $v['goods_id'] . '&activity_id=' . $param['activity_id']);
            }
        }
        return $result;
    }

    /**
     * 活动列表
     */
    public function getActivityList($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['is_del', '=', 0],
            ['is_temp', '=', 0],
        ];
        if (!empty($param['keyword'])) {
            $where[] = ['name', 'like', '%' . $param['keyword'] . '%'];
        }
        $result = $this->EmployeeActivity->getList($where, $limit);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['add_time'] = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                if (empty($v['ewm']) || !is_file($v['ewm'])) { //二维码不存在则重新生成
                    $result['data'][$k]['url'] = get_base_url('pages/lifeTools/employee/activityArea?activity_id=' . $v['pigcms_id']);
                    $result['data'][$k]['ewm'] = $this->getQrCode($result['data'][$k]['url']);
                    $this->EmployeeActivity->updateThis(['pigcms_id' => $v['pigcms_id']], [
                            'url' => $result['data'][$k]['url'],
                            'ewm' => $result['data'][$k]['ewm']
                        ]
                    );
                }
            }
        }
        return $result;
    }

    /**
     * 活动商品列表
     */
    public function getActivityGoods($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['a.is_del', '=', 0],
            ['a.activity_id', '=', $param['activity_id']],
            ['g.status', '=', 1],
            ['g.goods_type','=',0],
            ['m.status','=',1],
            ['s.status','=',1]
        ];
        if (!empty($param['keyword'])) {
            switch ($param['search_type']) {
                case 1:
                    $search_type = 'm.name';
                    break;
                case 2:
                    $search_type = 's.name';
                    break;
                case 3:
                    $search_type = 'g.name';
                    break;
            }
            $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        $result = $this->EmployeeActivityGoods->getList($where, $limit);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                if (!empty($v['employee_lables'])) {
                    $employee_lables = $this->EmployeeCardLable->where([['id', 'in', $v['employee_lables']]])->column('name');
                    $result['data'][$k]['employee_lables'] = implode(',', $employee_lables);
                }
            }
        }
        return $result;
    }

    /**
     * 员工卡列表
     */
    public function employActivityAddOrEdit($param) {
        $data = [];
        $data['name'] = $param['name'] ?? '';
        $data['company'] = $param['company'] ?? '';
        $data['status'] = $param['status'] ?? 0;
        $data['start_time'] = $param['start_time'] ? strtotime($param['start_time'] ) : '';
        $data['end_time'] = $param['end_time'] ? strtotime($param['end_time'] ) : '';
        $data['cover_image'] = $param['cover_image'] ? str_replace(file_domain(),'',$param['cover_image']) : '';
        $data['is_temp'] = $param['is_temp'] == 1 ? 1 : 0;
        $data['open_free_jump'] = $param['open_free_jump'] ?? 0;
        $data['free_jump_url'] = $param['free_jump_url'] ?? '';
        $lableArr = $param['lable_arr'] ?? '';
        if($lableArr){
            $data['only_show_lable'] = 1;// 是否设置仅为指定员工标签查看
        }else{            
            $data['only_show_lable'] = 0;// 是否设置仅为指定员工标签查看
        }

        if (empty($param['pigcms_id'])) {
            $data['add_time'] = time();
            $param['pigcms_id'] = $this->EmployeeActivity->add($data);
        } else {
            $this->EmployeeActivity->updateThis(['pigcms_id' => $param['pigcms_id']], $data);
        }

        // 保存员工标签
        $lableData = [];
        if($lableArr){
            foreach($lableArr as $mer){
                foreach($mer['lables'] as $label){
                    $lableData[] = [
                        'activity_id' => $param['pigcms_id'],
                        'mer_id' => $mer['mer_id'],
                        'lable_id' => $label,
                    ];
                }
            }
        }
        (new EmployeeActivityBindLableService())->del(['activity_id'=>$param['pigcms_id']]);
        if($lableData){
            (new EmployeeActivityBindLableService())->addAll($lableData);
        }

        $url = get_base_url('pages/lifeTools/employee/activityArea?activity_id=' . $param['pigcms_id']);
        $arr = [
            'url' => $url,
            'ewm' => $this->getQrCode($url)
        ];
        $this->EmployeeActivity->updateThis(['pigcms_id' => $param['pigcms_id']], $arr);
        return ['pigcms_id' => $param['pigcms_id']];
    }

    /**
     * 获取活动轮播图列表
     * @param $activity_id
     * @param $systemUser
     * @return array
     * @throws \think\Exception
     */
    public function getActivityAdverList($activity_id, $systemUser)
    {
        $many_city = cfg('many_city');
        $where = [['activity_id', '=', $activity_id]];
        if ($systemUser['area_id']) {
            $area_id = $systemUser['area_id'];
            if ($systemUser['level'] == 1) {
                $temp = (new AreaService())->getOne(['area_id' => $systemUser['area_id']]);
                if ($temp['area_type'] == 1) {
                    $city_list = (new AreaService())->getAreaListByCondition(['area_pid' => $temp['area_id']]);
                    $area_id = array();
                    foreach ($city_list as $value) {
                        $area_id[] = $value['area_id'];
                    }
                } else if ($temp['area_type'] == 2) {
                    $area_id = $temp['area_id'];
                } else {
                    $area_id = $temp['area_pid'];
                }
            }
            if (is_array($area_id)) {
                array_push($where, ['city_id', 'in', $area_id]);
            } else {
                array_push($where, ['city_id', '=', $area_id]);
            }
        }

        $order = ['sort' => 'DESC', 'id' => 'DESC'];
        $adver_list = $this->EmployeeActivityAdver->getByCondition(true, $where, $order);
        if (!empty($adver_list)) {
            if ($many_city == 1 && !empty($adver_list)) {
                foreach ($adver_list as $key => $v) {
                    $city = (new AreaService())->getOne(['area_id' => $v['city_id']]);
                    if (empty($city)) {
                        $adver_list[$key]['area_name'] = '通用';
                    } else {
                        $adver_list[$key]['area_name'] = $city['area_name'];
                    }
                }
            }
            //处理图片
            foreach ($adver_list as $key => $v) {
                $adver_list[$key]['pic'] = $v['pic'] ? replace_file_domain($v['pic']) : '';
                $adver_list[$key]['last_time'] = date('Y-m-d H:i:s', $adver_list[$key]['last_time']);
            }
        } else {
            $adver_list = [];
        }
        $arr['adver_list'] = $adver_list;
        $arr['many_city'] = $many_city;
        $arr['now_category'] = [
            'cat_id'    => 0,
            'cat_name'  => '配置轮播图',
            'cat_key'   => '',
            'cat_type'  => 0,
            'is_system' => 0,
            'size_info' => '640*240'
        ];
        return $arr;
    }

    /**
     * 编辑或参看时的一些参数
     * @param $id
     * @return array
     * @throws \think\Exception
     */
    public function getActivityAdver($id)
    {
        if (!empty($id)) {
            $where = ['id' => $id];
            $now_adver = $this->EmployeeActivityAdver->getById(true, $where);
            if (!empty($now_adver)) {
                $now_adver['pic'] = $now_adver['pic'] ? replace_file_domain($now_adver['pic']) : '';
            }
            $arr['now_adver'] = $now_adver;
            if (empty($now_adver)) {
                throw new \think\Exception('该广告不存在');
            }
            $arr['now_category'] = [
                'cat_id'    => 0,
                'cat_name'  => '配置轮播图',
                'cat_key'   => '',
                'cat_type'  => 0,
                'is_system' => 0,
                'size_info' => '640*240'
            ];
        }
        $many_city = cfg('many_city');
        $arr['many_city'] = $many_city;
        //小程序列表
        $wxapp_list = (new WxappOtherService())->getAll();
        $arr['wxapp_list'] = $wxapp_list;
        //ios列表
        $app_list = (new AppOtherService())->getAll();
        $arr['app_list'] = $app_list;

        // 员工标签
        $lableArr = (new EmployeeActivityAdverBindLableService())->getSome(['adver_id'=>$id], true, ['id'=>'asc']);
        $lableFormat = [];
        foreach($lableArr as $value){
            if(isset($lableFormat[$value['mer_id']])){
                $lableFormat[$value['mer_id']]['lables'][] = $value['lable_id'];
            }else{
                $lableFormat[$value['mer_id']]['mer_id'] = $value['mer_id'];
                $lableFormat[$value['mer_id']]['lables'][] = $value['lable_id'];
            }
        }
        $arr['lable_arr'] = array_values($lableFormat);

        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 新增或编辑轮播图
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function addOrEditActivityAdver($param)
    {
        if (empty($param['name'])) {
            throw new \think\Exception('缺少name参数');
        }
        if (empty($param['activity_id'])) {
            throw new \think\Exception('缺少activity_id参数');
        }

        if (!empty($param['areaList'])) {
            $param['province_id'] = $param['areaList'][0];
            $param['city_id'] = $param['areaList'][1];
        } else {
            $param['currency'] = 1;
            $param['province_id'] = 0;
            $param['city_id'] = 0;
        }
        //没图片使用默认图片地址
        if (empty($param['pic'])) {
            $param['pic'] = '/v20/public/static/mall/mall_platform_default_decorate.png';
        }
        unset($param['areaList']);
        unset($param['cat_key']);
        // app打开其他小程序需要获取原始id
        if ($param['app_wxapp_id']) {
            $wxapp = (new WxappOtherService())->getById(true, ['appid' => $param['app_wxapp_id']]);
            $param['app_wxapp_username'] = $wxapp['username'];
        }
        $param['last_time'] = time();
        $param['url'] = htmlspecialchars_decode($param['url']);

        // 员工标签
        $lableArr = $param['lable_arr'] ?? '';
        unset($param['lable_arr']);
        if($lableArr){
            $param['only_show_lable'] = 1;// 是否设置仅为指定员工标签查看
        }else{            
            $param['only_show_lable'] = 0;// 是否设置仅为指定员工标签查看
        }

        if (empty($param['id'])) {
            //添加
            $res = $param['id'] = $this->EmployeeActivityAdver->add($param);
        } else {
            //编辑
            if (stripos($param['pic'], 'http') !== false) {
                $param['pic'] = '/upload/' . explode('/upload/', $param['pic'])[1];
            }
            $res = $this->EmployeeActivityAdver->updateThis(['id' => $param['id']], $param);
        }

         // 保存员工标签
         $lableData = [];
         if($lableArr){
             foreach($lableArr as $mer){
                 foreach($mer['lables'] as $label){
                     $lableData[] = [
                         'adver_id' => $param['id'],
                         'mer_id' => $mer['mer_id'],
                         'activity_id' => $param['activity_id'],
                         'lable_id' => $label,
                     ];
                 }
             }
         }
         (new EmployeeActivityAdverBindLableService())->del(['adver_id'=>$param['id']]);
         if($lableData){
             (new EmployeeActivityAdverBindLableService())->addAll($lableData);
         }

        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 商品列表
     */
    public function getShopGoodsList($param) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['r.status', '=', 1],
            ['r.goods_type','=',0],
            ['m.status','=',1],
            ['ms.status','=',1]
        ];
        $goods_ids = $this->EmployeeActivityGoods->where(['activity_id' => $param['activity_id'], 'is_del' => 0])->column('goods_id') ?? [];
        !empty($goods_ids) && $where[] = ['r.goods_id', 'not in', $goods_ids];
        if (!empty($param['keyword'])) {
            switch ($param['search_type']) {
                case 1:
                    $search_type = 'm.name';
                    break;
                case 2:
                    $search_type = 'ms.name';
                    break;
                case 3:
                    $search_type = 'r.name';
                    break;
            }
            $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        $result = $this->ShopGoods->getActivityList($where, $limit);
        if (!empty($result['data'])) {
            foreach ($result['data'] as $k => $v) {
                if (!empty($v['employee_lables'])) {
                    $employee_lables = $this->EmployeeCardLable->where([['id', 'in', $v['employee_lables']]])->column('name');
                    $result['data'][$k]['employee_lables'] = implode(',', $employee_lables);
                }
            }
        }
        return $result;
    }

    /**
     * 商添加活动商品
     */
    public function addActivityShopGoods($param) {
        $arr = [];
        foreach ($param['goods_ids'] as $v) {
            $goodsDetail = $this->ShopGoods->getDetail(['r.goods_id' => $v], 'r.goods_id,ms.store_id,m.mer_id');
            $arr[] = [
                'activity_id' => $param['activity_id'],
                'goods_id'    => $goodsDetail['goods_id'],
                'store_id'    => $goodsDetail['store_id'],
                'mer_id'      => $goodsDetail['mer_id'],
                'add_time'    => time()
            ];
        }
        $this->EmployeeActivityGoods->addAll($arr);
        return true;
    }

    /**
     * 轮播图
     * @param $activity_id
     * @param int $limit
     * @param bool $needFormart
     * @return array|string|string[]
     */
    public function getAdverByActivityId($activity_id, $limit = 3, $needFormart = false, $uid = 0)
    {
        // 当前城市
        $nowCity = cfg('now_city');
        $adverList = [];
        if (!empty($adverList)) {
            $adverList = replace_domain($adverList);
            return $adverList;
        }
        // 搜索条件
        $where = [['a.activity_id', '=', $activity_id], ['status', '=', 1]];
        // 开启多城市
        if (cfg('many_city')) {
            array_push($where, ['a.city_id', 'exp', Db::raw('=' . $nowCity . ' or a.currency = 1')]);
        }

        if($uid){
            // 查询用户绑定的员工标签
            $lableIds = (new EmployeeCardUser())->getSome([['uid', '=', $uid],['status', '=', 1], ['lable_ids','<>', '']], 'lable_ids');
            $lableIdsArr = [];
            foreach($lableIds as $lables){
                $lables = explode(',',$lables['lable_ids']);
                $lableIdsArr = array_merge($lables, $lableIdsArr);
            }
            if($lableIdsArr){
                $where[] = ['', 'exp', Db::raw( '(b.lable_id in ('.implode(',', $lableIdsArr).') OR a.only_show_lable=0)')];
            }else{
                $where[] = ['a.only_show_lable', '=', 0];
            }
        }else{
            $where[] = ['a.only_show_lable', '=', 0];
        }

        // 排序
        $order = ['a.complete' => 'DESC', 'a.sort' => 'DESC', 'a.id' => 'DESC',];
        // 广告列表
        $adverList = $this->EmployeeActivityAdver->getListJoin($where, $order, $limit);
        $imgCount = count($adverList);
        // 替换图片路径
        foreach ($adverList as $key => $value) {
            if ($value['pic'] == '/v20/public/static/mall/mall_platform_default_decorate.png') {
                $adverList[$key]['pic'] = cfg('site_url') . '/v20/public/static/mall/mall_platform_default_decorate.png';
            } else {
                $adverList[$key]['pic'] = $value['pic'] ? thumb($value['pic'], 640, 240) : cfg('site_url') . '/v20/public/static/mall/mall_platform_default_decorate.png';
            }
            $adverList[$key]['img_count'] = $imgCount;
        }
        if (!empty($adverList) && $needFormart) {
            $adverList = $this->formatAdver($adverList);
        }
        $adverList = replace_domain($adverList);

        return $adverList;
    }

    /**
     * 去掉不要的字段
     * @param $array
     * @return array
     */
    public function formatAdver($array)
    {
        foreach ($array as &$adver_value) {
            unset($adver_value['id']);
            unset($adver_value['bg_color']);
            unset($adver_value['cat_id']);
            unset($adver_value['status']);
            unset($adver_value['last_time']);
            unset($adver_value['sort']);
            unset($adver_value['province_id']);
            unset($adver_value['city_id']);
            unset($adver_value['complete']);
            unset($adver_value['img_count']);
        }
        return $array;
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        require_once '../extend/phpqrcode/phpqrcode.php';
        $date = date('Y-m-d');
        $time = date('Hi');
        $qrcode = new \QRcode();
        $file_name = createRandomStr(12);
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/employee/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/employee/'.$date.'/'.$time . '/' . $file_name . '.png';
        $qrcode->png($code, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/employee/'.$date.'/'.$time . '/' . $file_name . '.png';      //已经生成的原始二维码图片文件
        return cfg('site_url') . '/' . $QR;
    }

     /**
     * 获取自提点配置信息
     */
    public function getPickTimeSetting()
    {
        $condition = [];
        $condition[] = ['gid', '=', 'employee_active'];
        $data = $this->configDataModel->where($condition)->select();
        $config = [];
        if($data){
            foreach ($data as $key => $val) {
                $config[$val['name']] = $val['value'];
                if($val['name'] == 'pick_time'){
                    $config[$val['name']] = json_decode($val['value']);
                }
            }
        }
        return $config;
    }

    /**
     * 编辑自提点配置
     */
    public function pickTimeSetting($params)
    {
        if(!isset($params['open_pick_time']) || !isset($params['pick_time'])){
            throw new \think\Exception('参数有误');
        }
        foreach($params as $key => $val){
            if($key == 'pick_time'){
                $select_date = [];
                foreach ($val as $k => $v) {
                    if(in_array($v['select_date'], $select_date)){
                        throw new \think\Exception('日期：'.$v['select_date'] . ' 重复配置！');
                    }else{
                        $select_date[] = $v['select_date'];
                    }
                }
                $val = json_encode($val);
            }
            $this->configDataModel->where('name', $key)->update(['value'=>$val]);
        }
        return true;
    }

}