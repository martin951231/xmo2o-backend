<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/8/12 13:31
 */

namespace app\community\controller\manage_api\v1;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\manage_api\BaseController;
use app\community\model\db\PropertyAdmin;
use app\community\model\service\HouseNewPorpertyService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\RepairCateService;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\consts\HouseWorkConst;
use app\consts\WorkOrderConst;

class RepairOrdersController  extends BaseController
{

    // 状态返回
    public $status_desc = [
        ['status' => 'todo', 'name' => '待处理'],
        ['status' => 'processing', 'name' => '处理中'],
        ['status' => 'processed', 'name' => '已处理'],
        ['status' => 'all', 'name' => '全部'],
    ];


    /**
     * Notes: 获取工单列表
     * @author: wanzy
     * @date_time: 2021/8/12 13:37
     */
    public function workersOrderList() {
        $arr = $this->getLoginInfo();
        $village_id = $this->request->post('village_id',0);
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $status = $this->request->post('status');
        if(!$status) {
            // 默认待处理
            $status = 'todo';
        }
        $page = $this->request->post('page');
        $search = $this->request->post('search');
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        $house_new_repair_works_order_service = new HouseNewRepairService();
        !$village_id && $village_id = isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0;
        try {
            $arr = $house_new_repair_works_order_service->workGetWorksOrderLists($worker_id,$login_role,$status,$search,$page,10,false,$village_id);
            
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            $is_supply_station = 0;
            if ($jz_open) {
                $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
                if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                    $is_supply_station = 1;
                }
            }
            $arr['jz_open'] = $jz_open;
            $arr['is_sign'] = false;
            $is_supply_station == 1 && $arr['is_sign'] = true;
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        $arr['status_desc'] = $this->status_desc;
        $arr['is_add'] = true;
        return api_output(0,$arr);
    }

    /**
     * Notes: 获取对应身份小区列表
     * @author: wanzy
     * @date_time: 2021/8/16 13:58
     */
    public function getVillageList()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        $service_house_village = new HouseVillageService();
        $whereList = [];
        $whereList[] = ['status', '=', 1];
        if ($login_role == HouseWorkConst::MANAGE_APP_STREET_WORK) {
            $login_info = $this->login_info;
            // 区分街道还是社区
            if (isset($login_info['area_type']) && isset($login_info['area_id']) && $login_info['area_id'] && $login_info['area_type']=1) {
                $whereList[] = ['community_id', '=', $login_info['area_id']];
            } elseif (isset($login_info['area_id']) && $login_info['area_id']) {
                $whereList[] = ['street_id', '=', $login_info['area_id']];
            } else {
                return api_output(0,[]);
            }
        } elseif ($login_role == HouseWorkConst::MANAGE_APP_PROPERTY_ADMIN) {
            $whereList[] = ['property_id', '=', $worker_id];
        } elseif ($login_role == HouseWorkConst::MANAGE_APP_PROPERTY_USER) {
            $where_admin[] = ['id', '=', $worker_id];
            $property_admin_info = (new PropertyAdmin())->get_one($where_admin,'menus');
            if($property_admin_info && $property_admin_info['menus']){
                $whereList[] = ['village_id', 'in', (explode(',',$property_admin_info['menus']))];
            }
            $login_info = $this->login_info;
            if (isset($login_info['property_id'])) {
                $whereList[] = ['property_id', '=', $login_info['property_id']];
            } else {
                return api_output(0,[]);
            }
        } elseif ($login_role == HouseWorkConst::MANAGE_APP_VILLAGE_ADMIN) {
            $login_info = $this->login_info;
            if (isset($login_info['village_id'])) {
                $whereList[] = ['village_id', '=', $login_info['village_id']];
            } else {
                return api_output(0,[]);
            }
        } elseif ($login_role == HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            $login_info = $this->login_info;
            if (isset($login_info['village_id'])) {
                $whereList[] = ['village_id', '=', $login_info['village_id']];
            } else {
                return api_output(0,[]);
            }
        }
        $field = 'village_id,village_name,village_address';
        $data = $service_house_village->getList($whereList,$field,'','','village_id asc',0);
        $arr = [];
        $arr['village_list'] = $data;
        return api_output(0,$arr);
    }

    /**
     * Notes: 查询公共区域和楼栋列表
     * @author: wanzy
     * @date_time: 2021/8/16 13:58
     */
    public function getHousePosition()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->post('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        // 查询楼栋
        $village_service = new HouseVillageService();
        try {
            $list = $village_service->getHousePosition($village_id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 获取对应子集信息
     * @author: wanzy
     * @date_time: 2021/8/13 17:01
     */
    public function getHousePositionChidren() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->post('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->post('id',0);
        if(!$id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $type = $this->request->post('type',0);
        if(!$type) {
            return api_output_error(1001,'必传参数缺失');
        }
        // 查询楼栋
        $village_service = new HouseVillageService();
        try {
            $list = $village_service->getHousePositionChildren($village_id,$id,$type);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 工单类目-类别列表
     * @author wanzy
     * @date_time 2021/08/13 17:07
     */
    public function getSubject()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->post('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $parent_id = $this->request->post('subject_id',0);
        if (!$parent_id) {
            $parent_id = 0;
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['status','=',1];
        $where[] = ['parent_id','=',$parent_id];
        $field = 'subject_name,id as category_id,color';
//        $service_house_new_repair = new HouseNewRepairService();
        $field = 'cate_name as subject_name,id as category_id,color';
        try{
//            $data = $service_house_new_repair->getRepairSubject($where,$field,0,0,'id DESC');
            $data = (new RepairCateService())->getNewRepairCate($where,$field,0,0,'id DESC');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes:分类 一级分类-二级分类
     * @author: wanzy
     * @date_time: 2021/8/13 17:15
     */
    public function getRepairCate()
    {
        $cat_fid = $this->request->post('cat_fid',0);
        $village_id = $this->request->post('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $subject_id = $this->request->post('subject_id',0);
        if(!$subject_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $appType = $this->request->post('app_type','');
        if (!$cat_fid) {
            $cat_fid = 0;
        }
        $where = [];
        // H5 一次性将一级二级分类拼好返给前端
        if($appType != 'packapp'){
//            $where[] = ['parent_id','=',$cat_fid];
        }
        $where[] = ['parent_id','=',$subject_id];
        $where[] = ['village_id','=',$village_id];
//      $where[] = ['subject_id','=',$subject_id];

        $where[] = ['status','=',1];
        $field='id as cat_id,cate_name';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $appType='packapp';
            $data = $service_house_new_repair->getFidCateList($where,$field,'sort DESC,id desc',$appType);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes: 标签列表
     * @author: wanzy
     * @date_time: 2021/8/16 13:58
     */
    public function getLabel()
    {
        $cate_id = $this->request->post('cat_id',0);
        if(!$cate_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $where['cate_id'] = $cate_id;
        $where['status'] = 1;
        $field='id,name';
        $order='sort DESC';
        $service_house_new_repair = new HouseNewRepairService();
        try{
            $data = $service_house_new_repair->getLabel($where,$field,$order);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes: 上传图片
     * @author: wanzy
     * @date_time: 2021/8/16 14:05
     */
    public function  postEventImg() {
        $file = $this->request->file('imgFile');
        $service_house_village = new HouseVillageService();
        try{
            $imgurl = $service_house_village->uploads($file,'v20RepairOrders');
            $arr = [];
            $arr['url'] = thumb_img($imgurl,200, 200, 'fill');
            $arr['imageUrl_path'] = $imgurl;
            $arr['imageUrl'] = replace_file_domain($imgurl);
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes: 提交工单
     * @author: wanzy
     * @date_time: 2021/8/17 10:27
     */
    public function repairOrderAdd() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->post('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $category_id = $this->request->post('category_id',0);
        $type_id = $this->request->post('type_id',0);
        $cat_fid = $this->request->post('cat_fid',0,'intval');
        $cat_id = $this->request->post('cat_id',0,'intval');
        
        $wisdomqrcode_id = $this->request->post('wisdomqrcode_id',0,'intval'); //智慧二维码id
        $qrcode_record_id = $this->request->post('qrcode_record_id',0,'intval'); //智慧二维码记录id
        if(!$cat_fid) {
            return api_output_error(1001,'请选择工单类目');
        }
        if(!$cat_id) {
            return api_output_error(1001,'请选择工单分类');
        }
        $address_type = $this->request->post('address_type',0);

        $address_id = $this->request->post('address_id',0);
        if($address_type){
            if(!in_array($address_type,['public', 'room'])) {
                return api_output_error(1001,'请选择对应位置');
            }
            if(!$address_id) {
                return api_output_error(1001,'请选择对应位置');
            }
        }
        $label_txt = $this->request->post('label_txt');
        $order_imgs = $this->request->post('order_imgs');
        $order_content = $this->request->post('order_content');
        $videos = $this->request->post('videos');
        $go_time = $this->request->post('go_time','');
        $order_content = htmlspecialchars(trim($order_content));
        if (empty($order_imgs) && !$order_content && empty($label_txt)) {
            return api_output_error(1001,'缺少必传参数');
        }

        $house_new_repair_works_order_service = new HouseNewRepairService();
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        $now_login = $this->login_info;
        $data = [
            'village_id' => $village_id,
            'category_id' => 0,
            'type_id' => 0,
            'cat_fid' => $cat_fid?$cat_fid:0,
            'cat_id' => $cat_id,
            'address_type' => $address_type,
            'address_id' => $address_id,
            'label_txt' => $label_txt?$label_txt:'',
            'order_imgs' => $order_imgs?$order_imgs:'',
            'order_content' => $order_content?$order_content:'',
            'worker_id' => $worker_id,
            'login_role' => $login_role,
            'phone' => $now_login['login_phone']?$now_login['login_phone']:'',
            'name' => $now_login['login_name']?$now_login['login_name']:'',
            'event_status' => 10,
            'go_time' => $go_time,
            'videos' => $videos,
        ];

        $data['wisdomqrcode_id']=$wisdomqrcode_id;
        $data['qrcode_record_id']=$qrcode_record_id;
        try{
            $info = $house_new_repair_works_order_service->addWorksOrder($data);
            $arr = [];
            $arr['info'] = $info;
            return api_output(0, $arr);
        }catch (\Exception $e){
            fdump_api(['file' => $e->getFile(), 'code' => $e->getCode(), 'mes' => $e->getMessage(), 'line' => $e->getLine()], '$tryCatchErrLog', 1);
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes:获取工单详情信息
     * @author: wanzy
     * @date_time: 2021/3/3 10:45
     */
    public function repairOrderDetail() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $house_new_repair_works_order_service = new HouseNewRepairService();
        $worker_id = $this->_uid;
        $login_role = $this->login_role;

        try{
            $params = [
                'village_id' => isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0
            ];
            $arr = [];
            $order_detail = $house_new_repair_works_order_service->workGetOrder($order_id,$worker_id,$login_role,true,false, $params);
            $arr['order_detail'] = $order_detail;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $arr);
    }
    
    public function getBindVillageRepairCate()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $login_village_id = isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0;
        
        $parent_id = $this->request->param('parent_id', 0, 'intval');
        $result = [];
        try {
            $params = ['order_id' => $order_id];
            $worksOrderInfo = (new HouseNewRepairService())->getNewRepairWorksOrderInfo($params);
            $order_village_id = isset($worksOrderInfo['village_id']) ? intval($worksOrderInfo['village_id']) : 0;
            $sHouseCommunityAboutService = new HouseCommunityAboutService();
            $aboutConfig = $sHouseCommunityAboutService->getVillageFacilitiesInfo($login_village_id);
            if ($aboutConfig && !is_array($aboutConfig)) {
                $aboutConfig = $aboutConfig->toArray();
            }
            $is_supply_station = empty($aboutConfig) ? 0 : 1;
            
            $where = [];
            $where[] = ['village_id', '=', $order_village_id];
            $where[] = ['status', 'in', [1, 2]];
            $serviceRepairCate = new RepairCateService();
            if ($is_supply_station == 1) {
                if ($parent_id > 0) {
                    $where[] = ['other_village_id', '=', $login_village_id];
                } else {
                    $whereHas = [];
                    $whereHas[] = ['village_id', '=', $order_village_id];
                    $whereHas[] = ['status', 'in', [1, 2]];
                    $whereHas[] = ['other_village_id', '=', $login_village_id];
                    $repairCateList = $serviceRepairCate->getNewRepairCate($whereHas);
                    if ($repairCateList && !is_array($repairCateList)) {
                        $repairCateList = $repairCateList->toArray();
                    }
                    if (!empty($repairCateList)) {
                        $parentIds = array_unique(array_column($repairCateList, 'parent_id'));
                    } else {
                        $parentIds = [];
                    }
                    $where[] = ['parent_id', '=', 0];
                    $where[] = ['id', 'in', $parentIds];
                }
            } else {
                $where[] = ['parent_id', '=', $parent_id];
            }
            $data = [];
            $data['page'] = 0;
            $data['limit'] = 0;
            fdump_api(['where' => $where, 'data' => $data], '$data');
            $result = $serviceRepairCate->getCateList($where, $data);
            $selectList = [];
            foreach ($result['list'] as $item) {
                $cate_name = isset($item['cate_name']) ? trim($item['cate_name']) : '';
                $id = isset($item['id']) ? intval($item['id']) : 0;
                $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0;
                $selectList[] = [
                    'type' => $cate_name, 'key' => $id, 'village_id' => $village_id, 'cate_name' => $cate_name, 'id' => $id,
                ];
            }
            $result['list'] = $selectList;
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    public function infoBindVillageRepairCate()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $login_village_id = isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0;
        $repair_cate_id = $this->request->post('repair_cate_id',0, 'intval');
        $serviceRepairCate = new RepairCateService();
        $result = [];
        try {
            $sHouseCommunityAboutService = new HouseCommunityAboutService();
            $aboutConfig = $sHouseCommunityAboutService->getVillageFacilitiesInfo($login_village_id);
            if ($aboutConfig && !is_array($aboutConfig)) {
                $aboutConfig = $aboutConfig->toArray();
            }
            $is_supply_station = empty($aboutConfig) ? 0 : 1;
            if ($is_supply_station == 1) {
                $whereQuery = [];
                $whereQuery[] = ['id', '=', $repair_cate_id];
                $queryInfo = $serviceRepairCate->queryRepairCate($whereQuery);
                if ($queryInfo && !is_array($queryInfo)) {
                    $queryInfo = $queryInfo->toArray();
                }
                $other_village_cate_id = isset($queryInfo['other_village_cate_id']) ? intval($queryInfo['other_village_cate_id']) : 0;
                $other_village_cate_fid = isset($queryInfo['other_village_cate_fid']) ? intval($queryInfo['other_village_cate_fid']) : 0;

                $whereOther = [];
                $whereOther[] = ['id', '=', $other_village_cate_fid];
                $otherCateFidInfo = $serviceRepairCate->queryRepairCate($whereOther);
                $fid_cate_name = isset($otherCateFidInfo['cate_name']) ? trim($otherCateFidInfo['cate_name']) : '';
                $whereOther = [];
                $whereOther[] = ['id', '=', $other_village_cate_id];
                $otherCateIdInfo = $serviceRepairCate->queryRepairCate($whereOther);
                $id_cate_name = isset($otherCateIdInfo['cate_name']) ? trim($otherCateIdInfo['cate_name']) : '';

                $result['repair_cate_id'] = $repair_cate_id;
                $result['other_parent_cate_id'] = $other_village_cate_fid;
                $result['other_parent_cate_name'] = $fid_cate_name;
                $result['other_cate_id'] = $other_village_cate_id;
                $result['other_cate_name'] = $id_cate_name;
            } else {
                $result['repair_cate_id'] = $repair_cate_id;
                $result['other_parent_cate_id'] = 0;
                $result['other_parent_cate_name'] = '';
                $result['other_cate_id'] = 0;
                $result['other_cate_name'] = '';
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * Notes: 企业通讯录
     * @author: wanzy
     * @date_time: 2021/8/18 15:04
     */
    public function repairWorkList() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        $now_login = $this->login_info;
        $property_id = isset($now_login['property_id'])&&$now_login['property_id']?$now_login['property_id']:0;
        if(!$property_id) {
            return api_output_error(1001,'当前身份无法查看工作人员');
        }
        $village_id = (isset($now_login['village_id'])&&$now_login['village_id']) ? $now_login['village_id'] : 0;
        $group_id = $this->request->post('group_id',0);
        $log = $this->request->post('log','', 'trim');
        if (!$group_id) {
            $group_id = 0;
        }
        $search = $this->request->post('search');
        if (!$search) {
            $search = '';
        }
        $order_id = $this->request->post('order_id');
        $type_name = $this->request->post('type_name');
        $house_new_repair_works_order_service = new HouseNewRepairService();
        $arr = [];
        try{
            $params = ['log' => $log];
            $list = $house_new_repair_works_order_service->repairWorkList($group_id, $login_role, $worker_id, $property_id, $search, $order_id, $type_name, $village_id, $params);
            $arr['list'] = $list;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $arr);
    }


    /**
     * Notes: 更改工单状态
     * @author: wanzy
     * @date_time: 2021/3/3 16:56
     */
    public function repairOrderChange() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $log_name = $this->request->post('log_name');
        $order_id = $this->request->post('order_id',0);
        $choose_worker_id = $this->request->post('worker_id',0);
        $project_id = $this->request->post('project_id',0);
        $rule_id = $this->request->post('rule_id',0);
        $charge_price = $this->request->post('charge_price',0);
        $pay_type = $this->request->post('pay_type',0);
        $offline_pay_type = $this->request->post('offline_pay_type',0);
        $change_reason= $this->request->post('charge_reason','');
        $evaluate = $this->request->post('evaluate');
        if(!$order_id || !$log_name) {
            return api_output_error(1001,'缺少必传参数');
        }
        $changeCateFid = $this->request->post('changeCateFid',0);
        $changeCateId = $this->request->post('changeCateId',0);
        $scheme_charge_type = $this->request->post('scheme_charge_type',0);
        if ($scheme_charge_type == 1 && $charge_price <= 0) {
            // 自定义收费
            return api_output_error(-1, '自定义收费金额必须大于0');
        }
        if ($scheme_charge_type == 2 && (!$project_id || !$rule_id)) {
            // 收费标准收费
            if (!$project_id){
                return api_output_error(-1, '请先选择工单收费项目');
            }
            if (!$rule_id){
                return api_output_error(-1, '请先选择工单收费标准');
            }
            
        }
        
        $login_role = $this->login_role;
        $worker_id = $this->_uid;
        $order_imgs = $this->request->post('order_imgs');
        $order_content = $this->request->post('order_content');
        $order_content = htmlspecialchars(trim($order_content));
        $house_new_repair_works_order_service = new HouseNewRepairService();
        $now_login = $this->login_info;
        $data = [
            'worker_id' => $worker_id,
            'order_id' => $order_id,
            'project_id' => $project_id,
            'rule_id' => $rule_id,
            'charge_price' => $charge_price,
            'pay_type' => $pay_type,
            'offline_pay_type' => $offline_pay_type,
            'change_reason' => $change_reason,
            'log_name' => $log_name,
            'order_imgs' => $order_imgs?$order_imgs:'',
            'order_content' => $order_content?$order_content:'',
            'choose_worker_id' => $choose_worker_id,
            'phone' => $now_login['login_phone']?$now_login['login_phone']:'',
            'name' => $now_login['login_name']?$now_login['login_name']:'',
            'evaluate' => $evaluate,
            'village_id' => isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0,
            'login_role' => isset($arr['login_role']) ? intval($arr['login_role']) : 0,
            'changeCateFid' => $changeCateFid,
            'changeCateId' => $changeCateId,
            'scheme_charge_type' => $scheme_charge_type,
        ];
        try{
            $log_arr = $house_new_repair_works_order_service->filterAddWorkOrderLog($login_role,$data);
            $arr = [];
            $arr['log'] = $log_arr;
            return api_output(0, $arr);
        }catch (\Exception $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'repairOrderChange',true);
            return api_output_error(-1, $e->getMessage());
        }catch (\Throwable $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'repairOrderChange',true);
            return api_output_error(-1, $e->getMessage());
        }catch (\RuntimeException $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'repairOrderChange',true);
            return api_output_error(-1, $e->getMessage());
        }catch (\InvalidArgumentException $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'repairOrderChange',true);
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes: 获取工单操作记录
     * @author: wanzy
     * @date_time: 2021/8/19 13:24
     */
    public function repairGetOrderLog() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $worker_id = $this->_uid;
        $login_role = $this->login_role;
        try{
            $arr = [];
            $house_new_repair_works_order_service = new HouseNewRepairService();
            $log_list = $house_new_repair_works_order_service->workGetOrderLog($order_id, $login_role,$worker_id);
            $arr['log'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * 查询当前工单的收费设置
     * @author:zhubaodi
     * @date_time: 2022/3/29 14:12
     */
    public function getChargeSetOrder(){
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $data['order_id'] = $this->request->post('order_id',0);
        if(!$data['order_id']) {
            return api_output_error(1001,'缺少必传参数');
        }

        $data['change_status'] = $this->request->post('change_status','');
        if(!$data['change_status']) {
            return api_output_error(1001,'缺少必传参数');
        }
        $data['worker_id'] = $this->_uid;
        $data['login_role'] = $this->login_role;
        try{
            $arr = [];
            $house_new_repair_works_order_service = new HouseNewRepairService();
            $log_list = $house_new_repair_works_order_service->getChargeSetOrder($data);
         //   $arr['res'] = $log_list;
            return api_output(0, $log_list);
        }catch (\Exception $e){
            $log_list = (object)[];
            return api_output(-1, $log_list, $e->getMessage(), 200);
        }
    }

    /**
     * 查询收费项目
     * @author:zhubaodi
     * @date_time: 2022/3/29 14:23
     */
    public function getWorkChargeProject(){
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
//        if ($jz_open) {
//            $login_village_id = isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0;
//        }
        if (isset($login_village_id) && $login_village_id > 0) {
            $data['village_id'] = $login_village_id;
        } else {
            $data['village_id'] = $this->request->param('village_id',0);
        }
        if(!$data['village_id']) {
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $arr = [];
            $house_new_repair_works_order_service = new HouseNewRepairService();
            $log_list = $house_new_repair_works_order_service->getWorkChargeProject($data);
            $arr['list'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getWorkChargeRule(){
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
//        if ($jz_open) {
//            $login_village_id = isset($arr['user']['village_id']) ? intval($arr['user']['village_id']) : 0;
//        }
        if (isset($login_village_id) && $login_village_id > 0) {
            $data['village_id'] = $login_village_id;
        } else {
            $data['village_id'] = $this->request->param('village_id',0);
        }
        if(!$data['village_id']) {
            return api_output_error(1001,'缺少必传参数');
        }
        $data['project_id'] = $this->request->param('project_id',0);
        if(!$data['project_id']) {
            return api_output_error(1001,'请先选择收费项目');
        }
        try{
            $house_new_repair_works_order_service = new HouseNewRepairService();
            $log_list = $house_new_repair_works_order_service->getWorkChargeRule($data);
            return api_output(0, $log_list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 线下支付列表
     * @author lijie
     * @date_time 2021/06/28
     */
    public function getOfflineList()
    {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id',0);
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_new_offline = new HouseNewPorpertyService();
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(['village_id' => $village_id], 'property_id');
        $where['property_id'] = $village_info['property_id'];
        $where['status'] = 1;
        $data = $service_house_new_offline->getOfflineList($where, 'id,name');
        return api_output(0, $data);
    }


    /**
     * 抢单
     * @author: liukezhu
     * @date : 2022/4/9
     */
    public function grabRepairOrder() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $village_id = $this->request->param('village_id',0);
        $order_id = $this->request->post('order_id',0);
        if(!$village_id || !$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            (new HouseNewRepairService())->grabRepairOrder($village_id,$this->login_role,$this->_uid,$order_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['msg'=>'抢单成功'],'抢单成功');
    }


    /**
     * 抢单
     * @author: liukezhu
     * @date : 2022/4/9
     */

    public function getPhone() {
        //网站电话
        $site_phone=cfg('site_phone');
        $returnArr=array();
        $returnArr['phone'] =  !empty($site_phone) ? strval($site_phone):'';

        return api_output(0, $returnArr);
    }

    public function checkGoTime()
    {
        $village_id = $this->request->param('village_id', '', 'intval');
        if (!$village_id) {
            return api_output_error(1001, '缺少必传参数');
        }
        $is_timely = (new HouseVillageService())->checkVillageField($village_id, 'is_timely');
        $data = ['is_go_time' => $is_timely];
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        $data['jz_open'] = $jz_open ? 1 : 0;
        $data['uploadVideo'] = 0;
        if ($jz_open) {
            $data['uploadVideo'] = 1;
            $data['uploadVideoMax'] = WorkOrderConst::WORK_ORDER_UPLOAD_VIDEO_MAX;
            $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
            $data['is_pic_video'] = isset($configInfo['is_pic_video']) ? intval($configInfo['is_pic_video']) : 0;
        }
        $data['uploadImageMax'] = WorkOrderConst::WORK_ORDER_UPLOAD_IMAGE_MAX;
        return api_output(0, $data, 'ok');
    }
}
