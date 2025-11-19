<?php
/**
 * 课程、体育馆、景区service
 * @date 2021-12-17 
 */

namespace app\life_tools\model\service;

use app\common\model\service\address\AutoAddressService;
use app\common\model\service\AreaService;
use app\common\model\service\AuditService;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\UserService;
use app\life_tools\model\db\IndexRecommend;
use app\life_tools\model\db\IndexRecommendGoods;
use app\life_tools\model\db\LifeScenicActivityDetail;
use app\life_tools\model\db\LifeScenicLimitedActNotice;
use app\life_tools\model\db\LifeTools;
use app\common\model\db\Area;
use app\common\model\db\SystemOrder;
use app\life_tools\model\db\LifeScenicLimitedSku;
use app\life_tools\model\db\LifeToolsCard;
use app\life_tools\model\db\LifeToolsCardOrderRecord;
use app\life_tools\model\db\LifeToolsCardTools;
use app\life_tools\model\db\LifeToolsCarParkTools;
use app\life_tools\model\db\LifeToolsComplaintAdvice;
use app\life_tools\model\db\LifeToolsCourse;
use app\life_tools\model\db\LifeToolsDistributionUserShare;
use app\life_tools\model\db\LifeToolsInformation;
use app\life_tools\model\db\LifeToolsMessage;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsReply;
use app\life_tools\model\db\LifeToolsScenicMap;
use app\life_tools\model\db\LifeToolsScenicRecommend;
use app\life_tools\model\db\LifeToolsSportsActivityBindTicket;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketDetail;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\db\LifeToolsTicketSku;
use app\life_tools\model\db\LifeToolsTicketSpec;
use app\life_tools\model\db\LifeToolsDistributionSetting;
use app\life_tools\model\db\LifeToolsDistributionUserBindMerchant;
use app\life_tools\model\db\LifeToolsDistributionUser;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketSku;
use map\Coordinate;
use think\facade\Db;
use think\Model;

class LifeToolsService
{
    public $lifeToolsModel = null;
    public $typeNameArr = null;
    public $areaModel = null;
    public $lifeToolsTicketModel = null;
    public $lifeToolsCourseModel = null;
    public $LifeToolsReply = null;
    public $lifeToolsOrderModel = null;
    public $LifeToolsOrderDetail = null;
    public $lifeToolsReplyModel = null;
    public $lifeToolsMessageModel = null;
    public $LifeToolsScenicRecommend = null;
    public $LifeToolsComplaintAdvice = null;
    public $LifeToolsInformation = null;
    public $LifeToolsCard = null;
    public $LifeToolsCardTools = null;
    public $LifeToolsCardOrderRecord = null;
    public $systemOrderModel = null;
    public $LifeToolsSportsActivityBindTicket = null;
    public $lifeToolsDistributionSettingModel = null;
    public $lifeToolsDistributionUserBindMerchantModel = null;
    public $lifeToolsDistributionUserModel = null;
    public $lifeToolsDistributionUserShareModel = null;
    public function __construct()
    {
        $this->lifeToolsModel = new LifeTools();
        $this->areaModel = new Area();
        $this->lifeToolsTicketModel = new LifeToolsTicket();
        $this->lifeToolsCourseModel = new LifeToolsCourse();
        $this->LifeToolsReply = new LifeToolsReply();
        $this->lifeToolsOrderModel = new LifeToolsOrder();
        $this->LifeToolsOrderDetail = new LifeToolsOrderDetail();
        $this->lifeToolsReplyModel = new LifeToolsReply();
        $this->lifeToolsMessageModel = new LifeToolsMessage();
        $this->LifeToolsScenicRecommend = new LifeToolsScenicRecommend();
        $this->LifeToolsComplaintAdvice = new LifeToolsComplaintAdvice();
        $this->LifeToolsInformation = new LifeToolsInformation();
        $this->LifeToolsCard = new LifeToolsCard();
        $this->LifeToolsCardTools = new LifeToolsCardTools();
        $this->LifeToolsCardOrderRecord = new LifeToolsCardOrderRecord();
        $this->systemOrderModel = new SystemOrder();
        $this->LifeToolsSportsActivityBindTicket = new LifeToolsSportsActivityBindTicket();
        $this->lifeToolsDistributionSettingModel = new LifeToolsDistributionSetting();
        $this->lifeToolsDistributionUserBindMerchantModel = new LifeToolsDistributionUserBindMerchant();
        $this->lifeToolsDistributionUserModel = new LifeToolsDistributionUser();
        $this->lifeToolsDistributionUserShareModel = new LifeToolsDistributionUserShare();

        $this->typeNameArr = [
            'stadium' => '场馆',
            'course' => '课程',
            'scenic' => '景区',
        ];
    }

    /**
     *获取类型名称
     * @param string $type 
     * @return string
     */
    public function getTypeName($type){        
        return $this->typeNameArr[$type] ?? '';
    }

    /**
     * @param $data
     * @return int|string
     * 去提醒
     */
    public function addLimitedNotice($data)
    {
        return (new LifeScenicLimitedActNotice())->add($data);
    }
    /**
     *获取一条条数据
     * @param array $where 
     * @return array
     */
    public function getOne($where){
        $result = $this->lifeToolsModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }
 

    /**
     * 获取列表
     */
    public function getLifeToolsList($params,$order = 't.sort DESC,t.add_time DESC')
    { 
        $condition = [];
        if(isset($params['mer_id']) && $params['mer_id']){
            $condition[] = ['t.mer_id' ,'=', $params['mer_id']];
        }
        $condition[] = ['t.is_del' ,'=', 0]; 
        if(!empty($params['keywords'])){
            $condition[] = ['t.title|t.introduce|t.phone|t.address|t.label' ,'like', '%'.$params['keywords'].'%'];  
        }

        if(isset($params['title']) && !empty($params['title'])){
            $condition[] = ['t.title' ,'like', '%'.$params['title'].'%'];  
        }

        if(isset($params['tools_type'])){
            if($params['tools_type'] == 'sports'){
                $condition[] = ['t.type' ,'in', ['stadium', 'course']];
                $auditWhere = ['type' ,'in', ['stadium', 'course']];
            }else{
                $condition[] = ['t.type' ,'=', $params['tools_type']];
                $auditWhere = ['type' ,'=', $params['tools_type']];
            }
        }else{ 
            if(!empty($params['type'])){
                $condition[] = ['t.type' ,'in', $params['type']];
                $auditWhere = ['type' ,'in', $params['type']];
            }else{
                $condition[] = ['t.type' ,'in', ['stadium', 'course']];
                $auditWhere = ['type' ,'in', ['stadium', 'course']];
            }
        }

        // 排除的id
        if(isset($params['tools_ids_not']) && $params['tools_ids_not']){
            $condition[] = ['t.tools_id' ,'not in', $params['tools_ids_not']];
        }

        // 状态
        if(isset($params['audit_status']) && !is_null($params['audit_status'])){
            $condition[] = ['t.audit_status' ,'=', $params['audit_status']];  
        }

        // 状态
        if(isset($params['status'])){
            $condition[] = ['t.status' ,'=', $params['status']];
        }
        $data = $this->lifeToolsModel->alias('t')
                ->field('t.*,m.name as merchant_name')
                ->where($condition)
                ->join($this->lifeToolsModel->dbPrefix().'merchant m','m.mer_id = t.mer_id')
                ->append(['label_arr', 'audit_status_text', 'type_text'])
                ->order($order)
                ->paginate($params['page_size']);
        foreach ($data as &$v){
            $v['add_audit_time'] = $v['add_audit_time'] ? date('Y.m.d H:i',$v['add_audit_time']) : '';
            $v['audit_time'] = $v['audit_time'] ? date('Y.m.d H:i',$v['audit_time']) : '';
            $v['is_close_name'] = $v['is_close']==1 ? '暂停' : '开启';
        }

        return $data;
    }

    /**
     * 设置属性
     */
    public function setLifeToolsAttrs($params)
    { 
        $condition = [];
        
        if(isset($params['mer_id']) && $params['mer_id']){
            $condition[] = ['mer_id' ,'=', $params['mer_id']];
        }
        $condition[] = ['is_del' ,'=', 0]; 
        $condition[] = ['tools_id' ,'=', $params['tools_id']];  
        
        $data = $this->lifeToolsModel->where($condition)->find();
        if(!$data){
            throw new \think\Exception('内容不存在！');
        }
        if(!is_null($params['sort'])){
            $data->sort = $params['sort'];
        }
        if(!is_null($params['status']) && in_array($params['status'], [0, 1])){
            //开启景区、体育、商城、外卖审核
            if(customization('open_scenic_sports_mall_shop_audit') == 1){
                if($params['status'] == 1 && $data->audit_status != 1){
                    throw new \think\Exception('未审核成功，请先审核');
                }
            }
            $data->status = $params['status'];
        }
        if(!is_null($params['is_hot']) && in_array($params['is_hot'], [0, 1])){
            $data->is_hot = $params['is_hot'];
        }
        
        return $data->save();
    }

    /**
     * 获取地图配置
     */
    public function getMapConfig($params)
    { 
        $data['ak'] = cfg('baidu_map_ak'); 
        if(!$data['ak']){
            throw new \think\Exception('百度地图配置项AK配置！');
        }
        $city_name = '';
        if(isset($params['city_id']) && $params['city_id']){
            $condition = [];
            $condition[] = ['area_id', '=', $params['city_id']];
            $area = $this->areaModel->where($condition)->find();
            $city_name = $area->area_name ?: '';
        }
        $data['detault_city'] = $city_name;
        return $data;
    }

    /**
     * 获取地区信息
     */
    public function getAddressList($params)
    {
        $condition = [];
        $condition[] = ['area_pid', '=', $params['pid']];
        return $this->areaModel->field(['area_id', 'area_name'])->where($condition)->select();
    }

    /**
     * 获取场馆课程详情
     */
    public function getLifeToolsDetail($params)
    { 
        $condition = [];
        $condition[] = ['tools_id', '=', $params['tools_id']]; 
        $condition[] = ['is_del', '=', 0]; 
        $data = $this->lifeToolsModel 
                ->where($condition)
                ->append(['images_arr', 'longlat', 'label_arr','audit_status_text'])
                ->find();
        if(!$data){
            throw new \think\Exception('内容不存在！');
        }
        $data->cover_image = replace_file_domain($data->cover_image);
        return $data;
    }

    /**
     * 增加修改场馆课程
     */
    public function addEditLifeTools($params)
    { 
        //添加
        if(empty($params['tools_id'])){
            $Tools = $this->lifeToolsModel;
            $Tools->add_time = time();
        }else{
            $condition = [];
            $condition[] = ['tools_id', '=', $params['tools_id']]; 
            $condition[] = ['is_del', '=', 0]; 
            $Tools = $this->lifeToolsModel->where($condition)->find();
            if(!$Tools){
                throw new \think\Exception('内容不存在！');
            }
            $Tools->update_time = time();
        }

        $Tools->mer_id = $params['mer_id']; 

        if($params['is_close']==1&&!$params['is_close_body']){
            throw new \think\Exception('自定义按钮文案不能为空！');
        }elseif($params['is_close']==0){
            $params['is_close_body'] = '';
        }
        $Tools->is_close = $params['is_close'];
        $Tools->is_close_body = $params['is_close_body'];
        if(!in_array($params['type'], ['stadium', 'course', 'scenic'])){
            throw new \think\Exception('类型不存在！');
        } 
        $Tools->type = $params['type'];

        if(empty($params['cat_id'])){
            throw new \think\Exception('分类不能为空！');
        } 
        $Tools->cat_id = $params['cat_id'];

        if(empty($params['title'])){
            throw new \think\Exception('标题不能为空！');
        } 
        $Tools->title = $params['title'];

        // if(empty($params['introduce'])){
        //     throw new \think\Exception('简介不能为空！');
        // } 
        // $Tools->introduce = $params['introduce'];

        if(empty($params['images'])){
            throw new \think\Exception('图片不能为空！');
        }   
        $Tools->images =  rtrim($params['images'], ',');

        if(empty($params['cover_image'])){
            throw new \think\Exception('封面不能为空！');
        }   
        $Tools->cover_image = $params['cover_image'];

        if(empty($params['phone'])){
            throw new \think\Exception('联系电话不能为空！');
        }   
        $Tools->phone = $params['phone'];

        if(empty($params['address'])){
            throw new \think\Exception('地址电话不能为空！');
        }   
        $Tools->address = $params['address'];
        
        if(empty($params['longlat'])){
            throw new \think\Exception('经纬度不能为空！');
        }  
        list($long, $lat) = explode(',', $params['longlat']); 
        if (cfg('tianditu_web_key')) {
            list($long, $lat) = Coordinate::wgs84ToBd09($long, $lat);
        }
        $Tools->long = $long;
        $Tools->lat = $lat;
        
        if(empty($params['province_id']) || empty($params['area_id']) || empty($params['area_id'])){
            throw new \think\Exception('请选择地址！');
        }  
        $Tools->province_id = $params['province_id'];
        $Tools->city_id = $params['city_id'];
        $Tools->area_id = $params['area_id']; 
        
        // if(empty($params['money'])){
        //     throw new \think\Exception('大约金额不能为空！');
        // }   
        $Tools->money = $params['money'];
        
        // if(empty($params['start_time']) || empty($params['end_time'])){
        //     throw new \think\Exception('开园闭园时间不能为空！');
        // }
        // if($params['start_time'] >= $params['end_time']){
        //     throw new \think\Exception('闭园时间不能小于开园时间！');
        // }
        // $Tools->start_time = $params['start_time'];
        // $Tools->end_time = $params['end_time'];

        $Tools->time_txt = $params['time_txt'];
        
        if(empty($params['description'])){
            throw new \think\Exception('详细描述不能为空！');
        }   
        $Tools->description = $params['description']; 
        $Tools->tickets_description = $params['tickets_description']; 
        
        if(count($params['label'])){
            foreach($params['label'] as $key => $val)
            {
                $params['label'][$key] = str_replace(' ', '', $val);
            }
            $Tools->label = implode(' ', $params['label']);
        }else{
            $Tools->label = '';
        }
        $Tools->audit_msg = '';
        $status = $params['status'];//默认状态（未审核）
        //开启景区、体育、商城、外卖审核
        if(customization('open_scenic_sports_mall_shop_audit') == 1){
            //判断景区、体育馆、体育课程是否需要手动审核
            if(($params['type'] == 'stadium' && cfg('life_tools_sports_audit_type') == '0') || //体育场馆
            ($params['type'] == 'course' && cfg('life_tools_sports_course_audit_type') == '0') || //体育课程
            ($params['type'] == 'scenic' && cfg('life_tools_scenic_audit') == '0')//景区
            ){
                $Tools->audit_status = 1;
                $Tools->audit_msg = '自动审核通过';
                $status = $status;
            }else{
                $Tools->audit_status = 0;
                $status = 0;
            }
        }
       
        $Tools->add_audit_time = time();
        $Tools->is_appoint = $params['is_appoint'];
        $Tools->status = $status;
        $Tools->sort = 0;
        $Tools->add_ip = $params['ip'];
        $Tools->is_del = 0;
        $Tools->save(); 
  
    }

    /**
     * 删除体育课程
     */
    public function delLifeTools($params)
    {
        $condition = [];
        $condition[] = ['tools_id', '=', $params['tools_id']];  
        $data = $this->lifeToolsModel->where($condition)->find();
        if(!$data){
            throw new \think\Exception('内容不存在！');
        }
   
        // $condition[] = ['is_del', '=', 0];  
        // $condition[] = ['status', '=', 1];  
        // $this->lifeToolsTicketModel

        $data->is_del = 1;
        return $data->save();
    }

    /**
     * 课程/场馆列表
     */
    public function getToolsList($param = [], $type = '') {
        $where = [
            ['is_del', '=', 0],
            ['status', '=', 1],
            ['audit_status', '=', 1]
        ];
        if ($type && $type == 'sports') {
            $where[] = ['type', 'in', ['stadium', 'course']];
        }else
        if ($type) {
            $where[] = ['type', '=', $type];
        }
        if (!empty($param['cat_id'])) {
            $where[] = ['cat_id', '=', $param['cat_id']];
        }
        $limit = 20;
        if (isset($param['page']) && isset($param['pageSize'])) {
            $limit = [
                'page' => $param['page'] ?? 1,
                'list_rows' => $param['pageSize'] ?? 10
            ];
        }
        $distance = '0 as distance';
        if (!empty($param['long']) && !empty($param['lat'])) {
            $distance = '(st_distance(point(`long`, `lat`), point('.$param['long'].', '.$param['lat'].') ) * 111195 / 1000) AS distance';
        }
        if (empty($param['order'])) {
            $param['order'] = 'sort desc';
        }
        if (isset($param['is_sports_activity']) && $param['is_sports_activity'] == 1) { //只显示约战列表
            $toolsIds = $this->LifeToolsSportsActivityBindTicket->where('1=1')->column('tools_id');
            $where[]  = ['tools_id', 'in', $toolsIds];
        }
        
        if(!empty($param['keywords'])){
            $where[]  = ['title', 'like', "%{$param['keywords']}%"];
        }

        $data = $this->lifeToolsModel->getList($where, '*,' . $distance, $param['order'], $limit);
        if (!empty($data['data'])) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['url']      = get_base_url() . 'pages/lifeTools/tools/detail?id=' . $v['tools_id'];
                $data['data'][$k]['image']    = thumb_img(replace_file_domain($v['cover_image']),200,200) ;
                $data['data'][$k]['label']    = !empty($v['label']) ? explode(' ', $v['label']) : [];
                $data['data'][$k]['distance'] = !empty($v['distance']) ? get_format_number($v['distance']) : 0;
                if (!empty($param['long']) && !empty($param['lat']) && empty($v['distance'])) { //计算距离
                    $data['data'][$k]['distance'] = $this->getDistance($param['long'], $param['lat'], $v['long'], $v['lat']);
                }
                $data['data'][$k]['act_type'] ='normal';
                $ret=[];
                if($v['type']=='scenic'){
                    $where_act=[['lt.tools_id','=',$v['tools_id']],['lt.status','=',1],['lt.is_del','=',0],
                        ['la.end_time','>',time()],['la.type','=','limited'],['la.is_del','=',0]];
                    $ret=(new LifeScenicActivityDetail())->getActDetail($where_act,'la.start_time,la.end_time,act.*,l.tools_id');
                }

                if($v['type']=='stadium' || $v['type']=='course'){
                    $where_act=[['lt.tools_id','=',$v['tools_id']],['lt.status','=',1],['lt.is_del','=',0],
                        ['la.end_time','>',time()],['la.type','=','limited'],['la.is_del','=',0]];
                    $ret=(new LifeToolsSportsSecondsKillTicketDetail())->getActDetail($where_act,'la.start_time,la.end_time,act.*,l.tools_id');
                }

                if(!empty($ret)){
                    $data['data'][$k]['act_stock_num'] =$ret['act_stock_num'];
                    $data['data'][$k]['limited_status'] =$ret['limited_status'];
                    $data['data'][$k]['act_type'] =$ret['act_type'];
                }

                $data['data'][$k]['is_sports_activity'] = 0;
                if (!empty($this->LifeToolsSportsActivityBindTicket->getOne(['tools_id' => $v['tools_id']]))) {
                    $data['data'][$k]['is_sports_activity'] = 1;
                }
                $data['data'][$k]['is_close_name'] = $v['is_close']==1?'暂停营业':'正常营业';
                $data['data'][$k]['is_close_body'] = $v['is_close_body']?:'';

                $data['data'][$k]['sale_count'] = $this->LifeToolsOrderDetail->getSaleCount($v['tools_id']);
            }
        }
        if ($limit == 20) return $data['data'] ?? [];
        return $data;
    }

    /**
     * 课程/场馆/景区-详情
     */
    public function getToolsDetail($tools_id, $uid = 0, $invite_id = 0) {
        $data = [];
        $data['book_btn_show'] = 0;//默认不展示预订按钮，只有出现非约战门票或者是不仅仅支持约战购买的约战门票时才展示
        $data['base_info'] = $this->lifeToolsModel->getDetail(['tools_id' => $tools_id, 'status' => 1, 'is_del' => 0]);
        if (empty($data['base_info'])) {
            throw new \think\Exception('参数有误！');
        }
        $data['base_info']['is_close_name'] = $data['base_info']['is_close']==1?'暂停营业':'正常营业';
        $data['base_info']['is_close_body'] = $data['base_info']['is_close_body']?:'';
        $data['base_info']['teacher'] = '';
        $data['base_info']['is_sports_activity'] = 0;
        if ($data['base_info']['type'] == 'course') {
            $data['base_info']['teacher'] = $this->lifeToolsCourseModel->getAllCoach($tools_id);
        }
        $data['base_info']['information_id'] = $this->LifeToolsInformation->where(['tools_id' => $data['base_info']['tools_id'], 'is_del' => 0])->value('pigcms_id') ?? 0;
        $data['base_info']['images'] = !empty($data['base_info']['images']) ? array_column($this->lifeToolsModel->getImagesArrAttr('', $data['base_info']), 'url') : [];
        
        foreach($data['base_info']['images'] as &$image){
            $image = thumb_img($image,'750', '490');
        }

        $data['base_info']['label']  = !empty($data['base_info']['label']) ? $this->lifeToolsModel->getLabelArrAttr('', $data['base_info']) : [];
        $data['base_info']['description'] = replace_file_domain_content_img($data['base_info']['description']);
        $data['mer_coupon'] = (new MerchantCouponService())->getMerchantCouponList($data['base_info']['mer_id'], 0, $data['base_info']['type'], '', $uid, true);
        if (!empty($data['mer_coupon'])) {
            foreach ($data['mer_coupon'] as $mk => $mv) {
                $data['mer_coupon'][$mk]['title'] = $mv['name'];
                $data['mer_coupon'][$mk]['desc']  = $mv['discount_des'];
                $data['mer_coupon'][$mk]['price'] = $mv['discount'];
                $data['mer_coupon'][$mk]['start_time'] = !empty($mv['start_time']) ? date('Y-m-d H:i:s', $mv['start_time']) : '无';
                $data['mer_coupon'][$mk]['end_time']   = !empty($mv['end_time']) ? date('Y-m-d H:i:s', $mv['end_time']) : '无';
            }
        }
        $data['reply'] = $this->LifeToolsReply->getList(['tools_id' => $tools_id, 'status' => 1], 1, 5);
        if (!empty($data['reply'])) {
            foreach ($data['reply'] as $rk => $rv) {
                $data['reply'][$rk]['images'] = $this->LifeToolsReply->getImagesArr($rv['images']);
                $user = (new UserService())->getUser($rv['uid']);
                $data['reply'][$rk]['username'] = $user['nickname'] ?? '匿名用户';
                $data['reply'][$rk]['avatar']   = $user['avatar'] ?? cfg('site_url') . '/static/images/user_avatar.jpg';
            }
        }
        $data['ticket'] = $this->lifeToolsTicketModel->getList(['tools_id' => $tools_id, 'status' => 1, 'is_del' => 0]);
        if ($data['ticket']) {
            foreach ($data['ticket'] as $tk => $tv) {
                $data['ticket'][$tk]['activity_id']  = 0;
                $data['ticket'][$tk]['is_only_sports_activity']  = 0;
                $data['ticket'][$tk]['limit_num']    = $tv['stock_num'];
                $data['ticket'][$tk]['label']        = $this->lifeToolsModel->getLabelArrAttr('', ['label' => $tv['label']]);
                $month_sale = $this->lifeToolsOrderModel->getSum([
                    ['ticket_id', '=', $tv['ticket_id']], 
                    ['paid', '=', 2], 
                    ['order_status', 'not in', [10,50]],
                    ['add_time', '>', time() - 86400 * 30]]) ?? '0';
                $data['ticket'][$tk]['month_sale']   = $month_sale >= 10000 ? get_format_number($month_sale / 10000) . '万+' : strval($month_sale); //月销量
                if ($data['base_info']['type'] == 'course') {
                    $data['ticket'][$tk]['limit_num'] = $tv['stock_num'] - $tv['sale_count'];
                    $data['ticket'][$tk]['limit_num'] <= 0 && $data['ticket'][$tk]['limit_num'] = 0;
                }
                $ret=[];
                if ($data['base_info']['type'] == 'scenic') {
                    $where_act=[['lt.tools_id','=',$tv['tools_id']],['lt.status','=',1],['lt.is_del','=',0],['sku.ticket_id','=',$tv['ticket_id']],
                        ['la.end_time','>',time()],['la.type','=','limited'],['la.is_del','=',0]];
                    $ret=(new LifeScenicActivityDetail())->getActDetail($where_act,'la.start_time,la.end_time,act.*,sku.act_stock_num,sku.act_price,l.tools_id,sku.ticket_id');
                }elseif ($data['base_info']['type']=='stadium' || $data['base_info']['type']=='course'){
                    $where_act=[['lt.tools_id','=',$tv['tools_id']],['lt.status','=',1],['lt.is_del','=',0],['sku.ticket_id','=',$tv['ticket_id']],
                        ['la.end_time','>',time()],['la.type','=','limited'],['la.is_del','=',0]];
                    $ret=(new LifeToolsSportsSecondsKillTicketDetail())->getActDetail($where_act,'la.start_time,la.end_time,act.*,sku.act_stock_num,sku.act_price,l.tools_id,sku.ticket_id');
                    if($ret){//判断总库存是否为0，总库存为0时，秒杀也显示已售空
                        if($data['base_info']['type']=='stadium' || $data['ticket'][$tk]['stock_type'] == 2){//体育馆-每日库存
                            $startDate = $ret['start_time'] >= time() ? $ret['start_time'] : time();
                            //查询有效可售卖天数（包括开始当天）
                            $canSaleDate = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $tv['ticket_id']], true, 'day asc');
                            $canSaleDateAry = [];
                            foreach ($canSaleDate as $kk=>$vv){
                                if (strtotime($vv['day']) + 86399 <= $startDate) {
                                    unset($canSaleDate[$kk]);
                                } else {
                                    $today = date('Y-m-d',$startDate);
                                    if ($vv['day'] == $startDate && ($tv['can_book_today'] == 0 || $startDate >= strtotime($today . ' ' . $tv['book_today_time']))) {
                                        unset($canSaleDate[$kk]);
                                    }else
                                        if ($canSaleDate[$kk]['is_sale'] == 0) {
                                            unset($canSaleDate[$kk]);
                                        }else{
                                            $canSaleDateAry[] = $vv['day'];
                                        }
                                }
                            }
                            $canSaleDate = $canSaleDate ? array_values($canSaleDate) : [];
                            if(count($canSaleDate) == 0){//无可售天数，总库存为0
                                $ret['act_stock_num'] = 0;
                            }
                            $sumWhere = [
                                ['ticket_id', '=', $tv['ticket_id']],
                                ['ticket_time', 'IN', $canSaleDateAry],
                                ['is_give', '=', 0]
                            ];
                            $orderIds = $this->lifeToolsOrderModel->where($sumWhere)->column('order_id');
                            $condition = [];
                            $condition[] = ['order_id', 'in', $orderIds];
                            $condition[] = ['status', '<>', 3];
                            $saleNum = $this->LifeToolsOrderDetail->where($condition)->count();//已售出总库存
                            $allStock = $tv['stock_num'] * count($canSaleDate);//设置的总库存
                            $ret['act_stock_num'] = $allStock - $saleNum > 0 ? $ret['act_stock_num'] : 0;
                        }elseif($data['base_info']['type']=='course' || $data['ticket'][$tk]['stock_type'] == 1){//课程-总库存
                            $ret['act_stock_num'] = $tv['stock_num'] -$tv['sale_count'] > 0 ? $ret['act_stock_num'] : 0;
                        }
                    }
                }
                if(!empty($ret)){
                    $data['ticket'][$tk]['act_id']=$ret['id'];
                    $data['ticket'][$tk]['act_type'] =$ret['act_type'];
                    $data['ticket'][$tk]['limited_status'] =$ret['limited_status'];
                    $data['ticket'][$tk]['left_time'] =$ret['left_time'];
                    $data['ticket'][$tk]['act_start_time'] =$ret['start_time'];
                    $data['ticket'][$tk]['notice_status'] =0;
                    if($ret['limited_status']==1){
                        $data['ticket'][$tk]['differ_price'] =get_format_number($tv['old_price'] - $ret['act_price']); //立减
                        $data['ticket'][$tk]['limited_logo'] =cfg('site_url').'/v20/public/static/scenic/limited_logo.png';
                        $data['ticket'][$tk]['act_stock_num'] =$ret['act_stock_num'];
                        $data['ticket'][$tk]['act_price'] =$ret['act_price'];
                    }elseif($ret['limited_status']==0){
                        $data['ticket'][$tk]['differ_price'] =get_format_number($tv['old_price'] - $ret['act_price']); //立减
                    }else{
                        $data['ticket'][$tk]['limited_logo'] ="";
                        $data['ticket'][$tk]['act_stock_num'] =0;
                        $data['ticket'][$tk]['act_price'] =0;
                    }

                    if($ret['limited_status']==0){
                        if($ret['act_type']=='limited'){
                            $data['ticket'][$tk]['act_stock_num'] =$ret['act_stock_num'];
                            $data['ticket'][$tk]['limited_logo'] =cfg('site_url').'/v20/public/static/scenic/limited_logo.png';
                        }
                        $data['ticket'][$tk]['act_price'] =$ret['act_price'];
                        $notice=(new LifeScenicLimitedActNotice())->getOne(['uid'=>$uid,'act_id'=>$ret['id'],'ticket_id'=>$tv['ticket_id']]);
                        if(!empty($notice)){
                            $data['ticket'][$tk]['notice_status'] =1;
                        }
                    }
                }

                $activityInfo = $this->LifeToolsSportsActivityBindTicket->getActivityInfo(['ticket_id' => $tv['ticket_id']],'a.activity_id,b.is_only_sports_activity');
//                $activity_id = $this->LifeToolsSportsActivityBindTicket->where(['ticket_id' => $tv['ticket_id']])->value('activity_id');
                if (!empty($activityInfo)) {
                    $data['base_info']['is_sports_activity'] = 1;
                    $data['ticket'][$tk]['is_only_sports_activity'] = $activityInfo['is_only_sports_activity'];
                    $data['ticket'][$tk]['activity_id'] = $activityInfo['activity_id'];
                }
                if($data['ticket'][$tk]['is_only_sports_activity'] == 0){//有不用仅仅只展示约战的门票，可以展示预订按钮
                    $data['book_btn_show'] = 1;
                }

                if (($data['base_info']['type'] == 'course' || $data['base_info']['type'] == 'stadium') && $data['ticket'][$tk]['is_sku']){
                    //多规格
                    $data['ticket'][$tk]['limit_num'] =$data['ticket'][$tk]['stock_num'] =(new LifeToolsTicketSku())->getSum(['ticket_id'=>$tv['ticket_id'],'is_del'=>0],'stock_num');
                    $spec = $sku = $skuList = [];
                    $specList = (object)[];
                    $specList->tree = [];
                    $specList->list = [];
                    $spec = (new LifeToolsTicketSpec())
                        ->field(['name','spec_id'])
                        ->with(['values'=>function($query){
                            $query->field(['id', 'name', 'spec_id']);
                        }])
                        ->where('ticket_id', $tv['ticket_id'])
                        ->where('is_del', 0)
                        ->select();

                    $sku = (new LifeToolsTicketSku())
                        ->field(['sku_id','stock_num','price', 'sale_price','sku_info','sku_str'])
                        ->where('ticket_id', $tv['ticket_id'])
                        ->where('is_del', 0)
                        ->select();

                    foreach ($spec as $k => $val) {
                        $val->k_id = 's' . ($k+1);
                        unset($val->spec_id);
                        $specList->tree[] = $val;
                    }

                    $select_date=date("Y-m-d",time());
                    if(!($data['ticket'][$tk]['stock_type'] == 1 || $data['base_info']['type'] == 'course')){
                        $data['saleday'] = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $tv['ticket_id']], true, 'day asc');
                        if ($data['saleday']) {
                            foreach ($data['saleday'] as $k => $v) {
                                if (strtotime($v['day']) + 86399 <= time()) {
                                    $data['saleday'][$k]['is_sale'] = 0;
                                } else {
                                    $today = date('Y-m-d');
                                    if ($v['day'] == $today && ($tv['can_book_today'] == 0 || time() >= strtotime($today . ' ' . $tv['book_today_time']))) {
                                        $data['saleday'][$k]['is_sale'] = 0;
                                    }
                                    if ($data['saleday'][$k]['is_sale'] == 1) {
                                        $select_date = $v['day'];
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    foreach ($sku as $val)
                    {
                        $val->id = $val->sku_id;
                        $val->sku_info = trim($val->sku_info, '|');
                        $val->sku_str = trim($val->sku_str, ',');
                        $skuList[$val->sku_info] = $val;
                        $skuInfos = explode('|', $val->sku_info);
                        foreach ($skuInfos as $k => $v) {
                            list($sid, $vid) = explode(':', $v);
                            $sname = 's' . ($k + 1);
                            $val->$sname = $vid;
                        }
                        $val->limit_num = $val->stock_num;
                        unset($val->sku_info);
                        $specList->list[] = $val;
                    }
                    $data['ticket'][$tk]['specList'] = $specList;
                    $data['ticket'][$tk]['skuList'] = $skuList;
                }else{
                    $data['ticket'][$tk]['specList'] = [];
                    $data['ticket'][$tk]['skuList'] = [];
                }
                $showPrice =  $data['ticket'][$tk]['act_price'] ?: $tv['price'];
                $data['ticket'][$tk]['differ_price'] = get_format_number($tv['old_price'] - $showPrice); //立减
            }
        }
        $this->lifeToolsModel->setInc(['tools_id' => $tools_id], 'view_count', 1); //增加点击量

        //次卡
        $data['card'] = $this->LifeToolsCard->where([['is_del', '=', 0],['pigcms_id', 'in', $this->LifeToolsCardTools->where(['tools_id' => $tools_id])->column('card_id') ?? []]])->column('title');


        //景区三级分销
        if($data['base_info']['type'] == 'scenic'){
            //景区分销配置
            $distributionSetting = $this->lifeToolsDistributionSettingModel->where('mer_id', $data['base_info']['mer_id'])->find();

            //三级分销邀请
            if($invite_id){

                $time = time();
                //判断链接有效期
                $condition = [];
                $condition[] = ['uid', '=', $invite_id];
                $condition[] = ['tools_id', '=', $tools_id];
                $userShare = $this->lifeToolsDistributionUserShareModel->where($condition)->find();
                if(!$userShare){
                    throw new \think\Exception('二维码有误！');
                }
                if($userShare->expiration_time > 0 && $userShare->expiration_time < $time){
                    throw new \think\Exception('二维码已过期！');
                }
                $userShare->pv ++;
                $userShare->save();

                $condition = [];
                $condition[] = ['uid', '=', $uid];
                $condition[] = ['is_del', '=', 0];
                $distributionUser = $this->lifeToolsDistributionUserModel->where($condition)->find();

                $condition = [];
                $condition[] = ['uid', '=', $invite_id];
                $condition[] = ['is_del', '=', 0];
                $inviteDistributionUser = $this->lifeToolsDistributionUserModel->where($condition)->find();

                //邀请人是认证分销员
                if($inviteDistributionUser->is_cert == 1){

                    //未认证的分销员定义为游客身份，点不同链接进来会更换上级
                    if($distributionUser && $distributionUser->is_cert == 0){
                        $distributionUser->pid = $inviteDistributionUser->user_id;
                        $distributionUser->update_time = $time;
                        $distributionUser->save();
                    }else if(!$distributionUser){
                        $distributionUser = $this->lifeToolsDistributionUserModel;
                        $distributionUser->uid = $uid;
                        $distributionUser->pid = $inviteDistributionUser->user_id;
                        $distributionUser->status = 1;
                        $distributionUser->is_cert = 0;
                        $distributionUser->add_time = $time;
                        $distributionUser->save();
    
                    }
                    
                }
            }

            //景区详情入口
            $distribution = null;
            if ($distributionSetting && $distributionSetting->status_distribution == 1) {
                $distributionSetting->share_logo = replace_file_domain($distributionSetting->share_logo);
                $distribution = $distributionSetting;
                $distribution->is_distribution_user = 0;
                $condition = [];
                $condition[] = ['uid', '=', $uid];
                $condition[] = ['mer_id', '=', $data['base_info']['mer_id']];
                $condition[] = ['is_del', '=', 0];
                $distributionBindMer = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
                if($distributionBindMer){
                    switch($distributionBindMer->audit_status){
                        case 0:
                            $distribution->is_distribution_user = 3;
                            break;
                        case 1:
                            $distribution->is_distribution_user = 1;
                            break;
                        case 2:
                            $distribution->is_distribution_user = 2;
                            break;
                    }
                    $distribution->pigcms_id = $distributionBindMer->pigcms_id;
                }
            }

            $data['distribution'] = $distribution;
        }


        //获取当前景区是否有停车场
        $car_park = (new LifeToolsCarParkTools())->get_car_park_num(['tools_id'=>$tools_id]);
        $data['base_info']['car_park'] = $car_park;
        // 是否绑定了景区地图
        $map = LifeToolsScenicMap::where('scenic_id',$tools_id)
            ->where('status',1)
            ->field('id')
            ->find();
        $data['has_map'] = !empty($map) ? true : false;

        return $data;
    }

    /**
     * 景区首页-推荐美食酒店
     */
    public function getScenicRecList($param = []) {
        $where = [];
        if ($param['type'] != 'all') {
            $where[] = ['type', '=', $param['type']];
        }
        $limit = 20;
        if (isset($param['page']) && isset($param['pageSize'])) {
            $limit = [
                'page' => $param['page'] ?? 1,
                'list_rows' => $param['pageSize'] ?? 10
            ];
        }
        $distance = '0 as distance';
        if (isset($param['order']) && $param['order'] == 'distance ASC' && !empty($param['long']) && !empty($param['lat'])) {
            $distance = '(st_distance(point(`long`, `lat`), point('.$param['long'].', '.$param['lat'].') ) * 111195 / 1000) AS distance';
        } else {
            $param['order'] = 'sort desc';
        }
        $data = $this->LifeToolsScenicRecommend->getList($where, '*,' . $distance, $param['order'], $limit);
        if (!empty($data['data'])) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['url']      = $v['type'] == 'store' ? get_base_url() . 'pages/store/homePage?store_id=' . $v['type_id'] : get_base_url() . 'pages/group/v1/groupDetail/index?group_id=' . $v['type_id'];
                $data['data'][$k]['pic']      = replace_file_domain($v['pic']);
                $data['data'][$k]['label']    = !empty($v['label']) ? explode(',', $v['label']) : [];
                $data['data'][$k]['distance'] = !empty($v['distance']) ? get_format_number($v['distance']) : 0;
                if (!empty($param['long']) && !empty($param['lat']) && empty($v['distance'])) { //计算距离
                    $data['data'][$k]['distance'] = $this->getDistance($param['long'], $param['lat'], $v['long'], $v['lat']);
                }
                $data['data'][$k]['arrival_time'] = $data['data'][$k]['distance'] == 0 ? 0 : round(get_format_number($data['data'][$k]['distance'] / 20 * 60), 0); //到达时间/分钟,平均20公里每小时
            }
        }
        if ($limit == 20) return $data['data'] ?? [];
        return $data;
    }

    /**
     * 景区详情页-推荐美食酒店周边
     */
    public function scenicRecommendList($param = []) {
        $toolsData = $this->lifeToolsModel->getOne(['tools_id' => $param['tools_id']])->toArray();
        if (!empty($toolsData['long']) && !empty($toolsData['lat'])) {
            $distance = '(st_distance(point(`long`, `lat`), point('.$toolsData['long'].', '.$toolsData['lat'].') ) * 111195 / 1000) AS distance';
        } else {
            $distance = '0 AS distance';
        }
        if ($param['type'] == 'scenic') {
            $where = [
                ['is_del', '=', 0],
                ['status', '=', 1],
                ['tools_id', '<>', $param['tools_id']],
                ['type', '=', 'scenic']
            ];
            $data = $this->lifeToolsModel->getList($where, '*,' . $distance, 'distance ASC');
        } else {
            $data = $this->LifeToolsScenicRecommend->getList([['type', '=', $param['type']]], '*,pic as cover_image,price as money,score as score_mean,' . $distance, 'distance ASC');
        }
        if (!empty($data['data'])) {
            foreach ($data['data'] as $k => $v) { //前端需提供美食酒店详情页跳转地址
                $data['data'][$k]['url'] = '';
                switch ($v['type']) {
                    case 'scenic':
                        $data['data'][$k]['url'] = get_base_url() . 'pages/lifeTools/tools/detail?id=' . $v['tools_id'];
                        break;
                    case 'store':
                        $data['data'][$k]['url'] = cfg('site_url') . '/wap.php?g=Wap&c=Merchant&a=shop&store_id=' . $v['type_id'];
                        break;
                    case 'hotel':
                        $data['data'][$k]['url'] = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=detail&pin_num=0&group_id=' . $v['type_id'];
                        break;
                }
                $data['data'][$k]['pic']      = replace_file_domain($v['cover_image']);
                $data['data'][$k]['label']    = !empty($v['label']) ? ($param['type'] == 'scenic' ? explode(' ', $v['label']) : explode(',', $v['label'])) : [];
                $data['data'][$k]['distance'] = !empty($v['distance']) ? get_format_number($v['distance']) : 0;
                if (!empty($param['long']) && !empty($param['lat']) && empty($v['distance'])) { //计算距离
                    $data['data'][$k]['distance'] = $this->getDistance($param['long'], $param['lat'], $v['long'], $v['lat']);
                }
                $data['data'][$k]['arrival_time'] = $data['data'][$k]['distance'] == 0 ? 0 : round(get_format_number($data['data'][$k]['distance'] / 20 * 60), 0); //到达时间/分钟,平均20公里每小时
            }
        }
        return $data['data'];
    }

    /**
     * 景区投诉建议列表
     */
    public function complaintAdviceList($param = []) {
        $limit = 20;
        if (isset($param['page']) && isset($param['pageSize'])) {
            $limit = [
                'page' => $param['page'] ?? 1,
                'list_rows' => $param['pageSize'] ?? 10
            ];
        }
        $where = ['is_del' => 0];
        if (!empty($param['tools_id'])) {
            $where['tools_id'] = $param['tools_id'];
        }
        if(!empty($param['from']) && $param['from'] == 'api'){
            if(empty($param['uid'])){
                throw new \think\Exception('请先登录！');
            }
            $where['r.uid'] = $param['uid'];
        }
        $data = $this->LifeToolsComplaintAdvice->getList($where, $limit);
        if (!empty($data['data'])) {
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['images']     = !empty($v['images']) ? $this->LifeToolsComplaintAdvice->getImagesArrAttr($v['images']) : [];
                $data['data'][$k]['add_time']   = date('Y-m-d H:i:s', $v['add_time']);
                $data['data'][$k]['avatar']     = $v['avatar'] ?? cfg('site_url') . '/static/images/user_avatar.jpg';
                $data['data'][$k]['address']    = '';
                if (!empty($v['province_id']) && !empty($v['city_id']) && !empty($v['area_id'])) {
                    $areaName = (new AreaService())->getNameByIds([$v['province_id'], $v['city_id'], $v['area_id']]);
                    $data['data'][$k]['address']    = $areaName[$v['province_id']] . '-' . $areaName[$v['city_id']] . '-' .$areaName[$v['area_id']];
                }
            }
        }
        if ($limit == 20) return $data['data'] ?? [];
        return $data;
    }

    /**
     * 景区投诉建议详情
     */
    public function complaintAdviceDetail($pigcms_id) {
        $data = $this->LifeToolsComplaintAdvice->getDetail(['r.pigcms_id' => $pigcms_id, 'r.is_del' => 0]);
        if (empty($data)) {
            throw new \think\Exception('参数有误！');
        }
        $data['images']     = !empty($data['images']) ? $this->LifeToolsComplaintAdvice->getImagesArrAttr($data['images']) : [];
        $data['add_time']   = date('Y-m-d H:i:s', $data['add_time']);
        $data['avatar']     = $data['avatar'] ?? cfg('site_url') . '/static/images/user_avatar.jpg';
        $data['address']    = '';
        if (!empty($data['province_id']) && !empty($data['city_id']) && !empty($data['area_id'])) {
            $areaName = (new AreaService())->getNameByIds([$data['province_id'], $data['city_id'], $data['area_id']]);
            $data['address']  = $areaName[$data['province_id']] . '-' . $areaName[$data['city_id']] . '-' .$areaName[$data['area_id']];
        }
        return $data;
    }

    /**
     * 景区提交投诉建议
     */
    public function complaintAdviceSave($param = []) {
        if (empty($param['content'])) {
            throw new \think\Exception('内容不能为空！');
        }
        if (!empty($param['images'])) {
            $param['images'] = implode(',', $param['images']);
        }
        if (!empty($param['long']) && !empty($param['lat'])) {
            try{
                $cityMatching = (new AutoAddressService())->cityMatching($param['lat'], $param['long']);
                $param['province_id'] = $cityMatching['area_info']['province_id'] ?? 0;
                $param['city_id']     = $cityMatching['area_info']['city_id'] ?? 0;
                $param['area_id']     = $cityMatching['area_info']['area_id'] ?? 0;
            }catch (\think\Exception $e){
                //pass
            }
        }
        $param['add_time'] = time();
        $this->LifeToolsComplaintAdvice->insert($param);
        return true;
    }

    /**
     * 计算两点之间的距离
     * @param $lng1 经度1
     * @param $lat1 纬度1
     * @param $lng2 经度2
     * @param $lat2 纬度2
     * @param int $unit m，km
     * @param int $decimal 位数
     * @return float
     */
    function getDistance($lng1, $lat1, $lng2, $lat2, $unit = 2, $decimal = 2)
    {
        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI           = 3.1415926535898;
        $radLat1 = $lat1 * $PI / 180.0;
        $radLat2 = $lat2 * $PI / 180.0;
        $radLng1 = $lng1 * $PI / 180.0;
        $radLng2 = $lng2 * $PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;
        if ($unit === 2) {
            $distance /= 1000;
        }
        return round($distance, $decimal);
    }

    /**
     * 课程/场馆-提交评价
     */
    public function reply($params)
    {
        if(empty($params['order_id'])){
            throw new \think\Exception('order_id不能为空！');
        }
        if(empty($params['ticket_id'])){
            throw new \think\Exception('ticket_id不能为空！');
        }
        if(empty($params['content'])){
            throw new \think\Exception('content不能为空！');
        }
        if(empty($params['score'])){
            throw new \think\Exception('score不能为空！');
        }

        $condition = [];
        $condition[] = ['order_id', '=', $params['order_id']];
        $condition[] = ['ticket_id', '=', $params['ticket_id']];
        $condition[] = ['uid', '=', $params['uid']];
        $order = $this->lifeToolsOrderModel->where($condition)->find();
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }

        $condition = [];
        $condition[] = ['type', '=', 'lifetools'];
        $condition[] = ['order_id', '=', $order->order_id];
        $condition[] = ['is_del', '=', 0];
        $systemOrder = $this->systemOrderModel->where($condition)->find();
        if(!$systemOrder){
            throw new \think\Exception('订单不存在！');
        }

        $time = time();

        Db::startTrans();
        try {
            $reply = $this->lifeToolsReplyModel;
            $reply->tools_id = $order->tools_id;
            $reply->ticket_id = $order->ticket_id;
            $reply->order_id = $order->order_id;
            $reply->uid = $order->uid;
            $reply->content = $params['content'];
            $reply->images = $params['images'];
            $reply->video_url = $params['video_url'];
            $reply->video_image = $params['video_image'];
            $reply->score = $params['score'];
            $reply->type = $order->tools->type;
            $reply->add_time = $time;
            $reply->status = 0;
            $reply->save();


            $this->lifeToolsOrderModel->where('order_id', $order->order_id)->update(['order_status'=>40,'last_time'=>$time]);


            $systemOrder->system_status = 3;
            $systemOrder->last_time = $time;
            $systemOrder->save();
          
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 评价列表
     */
    public function replyList($params)
    {
        $condition = []; 
        if(!empty($params['tools_id'])){
            $condition[] = ['tools_id', '=', $params['tools_id']];
        }
        $condition[] = ['status', '=', 1];
        $numCondition = $condition;
        $order = '';
        if(!is_null($params['type'])){
        
        // 类型:0=全部，1=晒图，2=低分，3=最新
            switch ($params['type']) {
                case 0: 
                    $order .= 'score desc,images desc';
                    break;
                case 1:
                    $condition[] = ['images', '<>', ''];
                    break;
                case 2:
                    $condition[] = ['score', '<', 3];
                    break;
                case 3: 
                    $order .= 'add_time desc';
                    break;
                
            }
        } 
        $LifeToolsReply = $this->lifeToolsReplyModel;
        $reply = $LifeToolsReply
                ->with(['user'=>function($query){
                    $query->field(['uid', 'avatar', 'nickname'])->bind(['avatar', 'username'=>'nickname']);
                }])
                ->where($condition)
                ->order($order)
                ->paginate($params['page_size'])
                ->each(function($item, $key) use($LifeToolsReply){
                    
                    $item->images = $LifeToolsReply->getImagesArr($item->images); 
                    $item->video_url = replace_file_domain($item->video_url); 
                    $item->video_image = replace_file_domain($item->video_image);  
                    $item->replys_time = date('Y-m-d H:i:s', $item->replys_time);
                    $item->add_time = date('Y-m-d H:i:s', $item->add_time);
                    $item->avatar = $item->avatar ?: cfg('site_url') . '/static/images/user_avatar.jpg';
  
                })->toArray();

 
        $reply['num']['all_num'] = $this->lifeToolsReplyModel->field('reply_id')->where($numCondition)->count();
        $reply['num']['img_num'] = $this->lifeToolsReplyModel->field('reply_id')->where($numCondition)->where([['images', '<>', '']])->count();
        $reply['num']['good_num'] = $this->lifeToolsReplyModel->field('reply_id')->where($numCondition)->where([['score', 'in',[4,5]]])->count();
        $reply['num']['mid_num'] = $this->lifeToolsReplyModel->field('reply_id')->where($numCondition)->where([['score', 'in', [2,3]]])->count();
        $reply['num']['bad_num'] = $this->lifeToolsReplyModel->field('reply_id')->where($numCondition)->where([['score', '<', 2]])->count();
        return $reply;
    }


    
    /**
     * 我的消息
     */
    public function myMsg($params)
    {
        if(empty($params['type'])){
            throw new \think\Exception('type不能为空！');
        }
        if(!in_array($params['type'], ['scenic', 'sports'])){
            throw new \think\Exception('未知的type值!');
        }
        $condition = [];
        $condition[] = ['uid', '=', $params['uid']];
        if($params['type'] == 'scenic'){
            $condition[] = ['type', '=', $params['type']];
        }else{
            $condition[] = ['type', 'in', ['stadium', 'course']];
        }
        $condition[] = ['is_del', '=', 0];
        return $this->lifeToolsMessageModel
            ->with(['tools'=> function($query){
                $query->field(['tools_id', 'cover_image'])
                ->withAttr('cover_image', function($value, $data){
                    return replace_file_domain($value);
                })    
                ->bind(['avatar'=>'cover_image']);
            }])
            ->withAttr('add_time', function($value, $data){
                return date('H:i', $value);
            })
            ->where($condition)
            ->order('add_time DESC')
            ->paginate($params['pageSize']);
        
    }

    /**
     * 热门推荐列表
     */
    public function getRecommendList()
    {
        $out=[];
        $cat_list=(new IndexRecommend())->getSome(['is_show'=>1],true,'sort desc')->toArray();
        if(!empty($cat_list)){
            foreach ($cat_list as $k=>$v){
                $ret['title']=$v['title'];
                $goods_list=(new IndexRecommendGoods())->getGoodsList(['r.recommend_id'=>$v['id']],'rg.*','rg.sort desc',$v['goods_type']);
                $ret['goods_list']=$goods_list;
                $out[]=$ret;
            }
        }
        return $out;
    }

    /**
     *获取次卡列表
     * @param $param array
     * @return array
     */
    public function getToolsCardList($param = []) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [
            ['is_del', '=', 0]
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['keyword'])) {
            switch ($param['search_type']) {
                case 1:
                    $where[] = ['title', 'like', '%' . $param['keyword'] . '%'];
                    break;
                case 2:
                    $tools_ids = $this->lifeToolsModel->where([['title', 'like', '%' . $param['keyword'] . '%'], ['is_del', '=', 0], ['status', '=', 1]])->column('tools_id') ?? [];
                    $card_ids  = $this->LifeToolsCardTools->where([['tools_id', 'in', $tools_ids]])->column('card_id') ?? [];
                    $where[]   = ['pigcms_id', 'in', $card_ids];
                    break;
            }
        }
        if (!empty($param['type']) && $param['type'] != 'all') {
            if($param['type'] == 'sports'){
                $where[] = ['type', 'exp', Db::raw(' = "'.$param['type'].'" OR type = "stadium" OR type = "course"')];
            }else if(in_array($param['type'], ['stadium', 'course'])){
                $where[] = ['type', 'exp', Db::raw(' = "'.$param['type'].'" OR type = "sports"')];
            }else{
                $where[] = ['type', '=', $param['type']]; 
            }
            
        }
        $result = $this->LifeToolsCard->getList($where, $limit);
        if (!empty($result['data'])) {
            $typeMap = [
                'stadium' => '体育',
                'course' => '体育',
                'scenic' => '景区',
                'sports' => '体育'
            ];
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['type_txt']         = $typeMap[$v['type']] ?? '';
                $result['data'][$k]['add_time']     = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $result['data'][$k]['term_num_val'] = $v['term_num'] . $this->LifeToolsCard->getTermType($v['term_type']);
                $result['data'][$k]['tools_ids']    = $this->LifeToolsCardTools->where(['card_id' => $v['pigcms_id']])->column('tools_id');
                $result['data'][$k]['tools_title']  = implode(',', $this->lifeToolsModel->where([['tools_id', 'in', $result['data'][$k]['tools_ids']]])->column('title'));
                $result['data'][$k]['tools_sub_title'] = mb_strlen($result['data'][$k]['tools_title'], 'utf-8') > 10 ? mb_substr($result['data'][$k]['tools_title'], 0, 10, 'utf-8') . '...' : '';
            }
        }
        return $result;
    }

    /**
     * 获取次卡编辑信息
     */
    public function getToolsCardEdit($pigcms_id) {
        $result = $this->LifeToolsCard->getOne(['pigcms_id' => $pigcms_id])->toArray();
        return $result;
    }

    /**
     *获取次卡列表
     * @param $param array
     */
    public function AddOrEditToolsCard($param = []) {
        if(!empty($param['type']) && $param['type'] == 'sports'){
            if(!empty($param['course_ids']) && count($param['course_ids']) && !empty($param['stadium_ids']) && count($param['stadium_ids'])){
                $param['type'] = 'sports';
            }else if(!empty($param['course_ids']) && count($param['course_ids'])){
                $param['type'] = 'course';
            }else if(!empty($param['stadium_ids']) && count($param['stadium_ids'])){
                $param['type'] = 'stadium';
            }else{
                throw new \think\EXception('请绑定体育！');
            }
        }
        $arr = [
            'mer_id'      => $param['mer_id'],
            'type'        => $param['type'],
            'title'       => $param['title'],
            'old_price'   => $param['old_price'],
            'price'       => $param['price'],
            'image'       => $param['image'],
            'term_type'   => $param['term_type'],
            'term_num'    => $param['term_num'],
            'num'         => $param['num'],
            'day_num'     => $param['day_num'] ?? 0,
            'user_num'    => $param['user_num'] ?? 0,
            'description' => $param['description'] ?? ''
        ];
        $time = time();
        if (!empty($param['pigcms_id'])) {
            $this->LifeToolsCard->updateThis(['pigcms_id' => $param['pigcms_id']], $arr);
        } else {
            $arr['add_time'] = $time;
            $param['pigcms_id'] = $this->LifeToolsCard->add($arr);
        }
        $this->LifeToolsCardTools->where(['card_id' => $param['pigcms_id']])->delete();

        if($param['type'] == 'scenic'){
            //景区
            foreach ($param['scenic_ids'] as $v) {
                $this->LifeToolsCardTools->add([
                    'card_id'  => $param['pigcms_id'],
                    'tools_id' => $v,
                    'add_time' => $time,
                    'type'     => 'scenic'
                ]);
            }
        }else{
            //体育馆
            foreach ($param['stadium_ids'] as $v) {
                $this->LifeToolsCardTools->add([
                    'card_id'  => $param['pigcms_id'],
                    'tools_id' => $v,
                    'add_time' => $time,
                    'type'     => 'stadium'
                ]);
            }
            //课程
            foreach ($param['course_ids'] as $v) {
                $this->LifeToolsCardTools->add([
                    'card_id'  => $param['pigcms_id'],
                    'tools_id' => $v,
                    'add_time' => $time,
                    'type'     => 'course'
                ]);
            }
        }
        
        return true;
    }

    /**
     * 获取所有景区体育健身
     */
    public function getAllToolsList($param = []) {
        $where = [
            // ['status', '=', 1],
            ['is_del', '=', 0]
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['type']) && $param['type'] != 'all') {
            $where[] = ['type', '=', $param['type']];
        }
        $result = $this->lifeToolsModel->getList($where, 'tools_id,title', 'sort desc', 0);
        return $result;
    }

    /**
     * 获取次卡核销列表
     */
    public function getToolsCardRecord($param = []) {
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];
        $where = [];
        if (!empty($param['mer_id'])) {
            $where[] = ['r.mer_id', '=', $param['mer_id']];
        }
        if (!empty($param['type']) && $param['type'] != 'all') {
            if($param['type'] == 'sports'){
                $where[] = ['o.type', 'exp', Db::raw('= "stadium" OR o.type = "course" OR o.type = "sports"')];
            }else{
                $where[] = ['o.type', '=', $param['type']];
            }
        }
        if (!empty($param['keyword'])) {
            switch ($param['search_type']) {
                case 1:
                    $search_type = 'c.title';
                    break;
                case 2:
                    $search_type = 'a.title';
                    break;
                case 3:
                    $search_type = 'o.nickname';
                    break;
                case 4:
                    $search_type = 'r.staff_name';
                    break;
                case 5:
                    $search_type = 'o.phone';
                    break;
            }
            $where[] = [$search_type, 'like', '%' . $param['keyword'] . '%'];
        }
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $where[] = ['r.add_time', '>=', strtotime($param['begin_time'])];
            $where[] = ['r.add_time', '<', strtotime($param['end_time']) + 86400];
        }
        if (!empty($param['staffId'])) {
            $where[] = ['r.staff_id', '=', $param['staffId']];
        }
        if(!empty($param['order_id'])){
            $where[] = ['o.order_id', '=', $param['order_id']];
        }
        $result = $this->LifeToolsCardOrderRecord->getList($where, $limit);
        if (!empty($result['data'])) {
            $typeMap = [
                'stadium' => '体育次卡',
                'course' => '体育次卡',
                'scenic' => '景区次卡',
                'sports' => '体育次卡'
            ];
            foreach ($result['data'] as $k => $v) {
                $result['data'][$k]['add_time'] = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
                $result['data'][$k]['type_txt'] = $typeMap[$v['type']] ?? '';
            }
        }
        return $result;
    }

    /**
     *获取次卡列表（用户端）
     * @param $param array
     * @return array
     */
    public function getCardList($param = []) {
        $where = [
            ['is_del', '=', 0]
        ];
        if (!empty($param['tools_id'])) {
            $card_ids = $this->LifeToolsCardTools->where([['tools_id', '=', $param['tools_id']]])->column('card_id') ?? [];
            $where[]  = ['pigcms_id', 'in', $card_ids];
        }
        if (!empty($param['type']) && $param['type'] != 'all') {
            if(in_array($param['type'], ['stadium', 'course'])){
                $where[] = ['type', 'exp', Db::raw(' = "'.$param['type'].'" OR type = "sports"')];
            }else{
                $where[] = ['type', '=', $param['type']];
            }
        }
        $result = $this->LifeToolsCard->getList($where, 0);
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $result[$k]['discount_price'] = get_format_number($v['old_price'] - $v['price']);
                $result[$k]['limit_num']      = $v['num'] - $v['sale_count']; //限购
                $result[$k]['term_type']      = $this->LifeToolsCard->getTermType($v['term_type']);
                $result[$k]['tools_id']       = $this->LifeToolsCardTools->where(['card_id' => $v['pigcms_id']])->column('tools_id');
                $result[$k]['tools_title']    = $this->lifeToolsModel->where([['tools_id', 'in', $result[$k]['tools_id']]])->column('title');
                $result[$k]['add_time']       = !empty($v['add_time']) ? date('Y-m-d H:i:s', $v['add_time']) : '无';
            }
        }
        return $result;
    }

    /**
     * 审核
     */
    public function lifeToolsAudit($params)
    {
        if(!in_array($params['audit_status'], [1, 2])){
            throw new \think\Exception('审核状态不正确！');
        }
        if($params['audit_status'] == 2 && !$params['audit_msg']){
            throw new \think\Exception('请填写审核理由！');
        }
        if(!is_array($params['tools_ids']) || !count($params['tools_ids'])){
            throw new \think\Exception('审核内容不能为空！');
        }

        Db::startTrans();
        try {
            //修改审核状态
            $this->lifeToolsModel->where('tools_id', 'in', $params['tools_ids'])->select()->each(function($item) use($params){
                $item->audit_status = $params['audit_status'];
                $item->audit_msg = $params['audit_msg'];
                $item->status = $params['audit_status'] == 1 ? 1 : 0;
                $item->update_time = time();
                $item->audit_time = time();
                $item->save();
            });
            //写入审核日志记录
            $params['admin_id'] = $params['admin_id'] ?? 0;
            $params['audit_object_ids'] = $params['tools_ids'];
            $params['type'] = 'tools';
            $auditService = new AuditService();
            $auditService->addLog($params);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        //写入审核记录日志
        return true;
    }

    /*
     * 查询景区列表
     * @author nidan
     * @date 2022/4/1
     */
    public function getScenic($param)
    {
        if(!isset($param['mer_id'])){
            throw new \think\Exception('参数缺失！');
        }
        $where = [
            'r.mer_id'=>$param['mer_id'],
            'r.type'=>'scenic',
            'r.is_del'=>0,
            'r.status'=>1,
        ];
        $field = ['r.tools_id','r.title'];
        $order = 'r.sort desc';
        $data = $this->lifeToolsModel->getListTool($where,$field,'','',$order);
        return $data;
    }

    /**
     * 景区/体育开启/关闭暂停功能
     * @param $param
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeCloseStatus($param){
        $tools_info = $this->lifeToolsModel->where(['tools_id'=>$param['tools_id'],'mer_id'=>$param['mer_id']])->find();
        if(!$tools_info){
            throw new \think\Exception('信息不存在');
        }
        if($param['is_close'] != 1){
            $param['is_close_body'] = '';
        }
        try {
            $this->lifeToolsModel->where(['tools_id'=>$param['tools_id'],'mer_id'=>$param['mer_id']])->save($param);
        }catch (\Exception $exception){
            throw new \think\Exception($exception->getMessage());
        }
    }

    public function cancelOrder($order_id, $uid)
    {
    	$where   = [
    		['order_id', '=', $order_id],
    		['uid', '=', $uid]
    	];
        $order = $this->lifeToolsOrderModel->where($where)->find();
        if(!$order){
            throw new \think\Exception('订单不存在');
        }
        if($order['order_status'] > 10){
            throw new \think\Exception('订单状态不支持取消操作');
        }
        $LifeToolsOrderService = new LifeToolsOrderService();
        $LifeToolsOrderService->changeOrderStatus($order_id, 60, '用户取消订单');
        // $this->LifeToolsOrderDetail->where('order_id', $order['order_id'])->update([
        //     'status'    =>  3
        // ]);
        return true;
    }

}
