<?php
/**
 * 网格化事件管理-住户端
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/2/22 11:50
 */
namespace app\community\controller\street_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\StreetWorksOrderService;
use app\community\model\service\AreaStreetEventCategoryService;
use app\community\model\service\AreaStreetService;

class GridUserController extends CommunityBaseController
{
    // 状态返回
    public $status_desc = [
        ['status' => 'todo', 'name' => '待处理'],
        ['status' => 'processing', 'name' => '处理中'],
        ['status' => 'processed', 'name' => '已处理'],
        ['status' => 'all', 'name' => '全部'],
    ];
    // 状态转换为条件
    public $status_data = [
        'todo' => '1',
        'processing' => '2,3',
        'processed' => '4,5',
        'all' => '',
    ];

    /**
     * Notes: 住户获取工单列表
     * @return \json
     * @author: wanzy
     * @date_time: 2021/2/23 9:24
     */
    public function workersOrderList() {
        $status = $this->request->post('status');
        if(!$status) {
            // 默认待处理
            $status = 'todo';
        }
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $page = $this->request->post('page');
        $street_works_order_service = new StreetWorksOrderService();

        $order_status = $this->status_data[$status];
        $where = [];
        if ($order_status) {
            $where[] = ['order_status', 'in', $order_status];
        }
        $where[] = ['order_type', '=', 0];
        $where[] = ['uid', '=', $uid];
        $where[] = ['del_time','=',0];
        $arr = [];
        $field = 'order_id,order_time,cat_id,cat_fid,order_content,order_type,uid,bind_id,last_time,order_status';
        $arr['list'] = $street_works_order_service->userGetWorksOrderLists($where,$field,'last_time DESC, order_time DESC',$page,10);
        $arr['status_desc'] = $this->status_desc;
        return api_output(0,$arr);
    }

    /**
     * Notes:住户获取工单分类
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/26 17:55
     */
    public function streetEventCategory() {
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $arr = [];
        $arr['title'] = [
            'top' => '工单分类',
            'content' => '上报工单时需要选择下方对应的工单分类，方便上报后相关处理人员快速定位问题，提高解决效率。'
        ];
        $service_area_street_event_category = new AreaStreetEventCategoryService();

        $where[] = ['status', '=', 1];
        $where[] = ['area_id', '=', $street_id];
        $field = true;
        $list = $service_area_street_event_category->userGetCategoryList($where,$field,'sort DESC, cat_id DESC');
//        if (empty($list)) {
//            return api_output_error(1003,'暂无分类');
//        }
        $arr['list'] = $list;
        return api_output(0,$arr);
    }

    /**
     * Notes: 工单内容内容提交页信息
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/23 17:50
     */
    public function streetEventAddInfo() {
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $cat_id = $this->request->post('cat_id',0);
        $source = $this->request->post('source',0);
        if(!$cat_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_area_street_event_category = new AreaStreetEventCategoryService();
        $where = [];
        $where[] = ['cat_id','=',$cat_id];
        $field = 'cat_id,cat_name,cat_fid,add_time';
        $list = $service_area_street_event_category->getCategoryDetail($where,$field,$source);
        $arr['category'] = $list;
        // 获取下当前该用户在对应街道下所有拥有的小区身份
        $service_area_street = new AreaStreetService();
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $service_area_street->getAreaStreet($where_street,'area_id,area_type,area_name');
        $where_bind = [];
        if ($street_info && $street_info['area_type']==1) {
            $where_bind[] = ['v.community_id','=',$street_id];
        } elseif($street_info) {
            $where_bind[] = ['v.street_id','=',$street_id];
        }
        $where_bind[] = ['hvb.uid','=',$uid];
        $where_bind[] = ['hvb.status','=',1];
        $where_bind[] = ['hvb.type','in','0,1,2,3'];
        $street_works_order_service = new StreetWorksOrderService();
        $field_bind = 'hvb.pigcms_id,hvb.type,hvb.uid,hvb.single_id,hvb.floor_id,hvb.layer_id,hvb.vacancy_id,hvb.village_id,v.village_name';
        $bind_list = $street_works_order_service->userGetStreetHouseBindLists($where_bind,$field_bind,$street_id);
        if (isset($bind_list['bind_msg'])) {
            $arr['bind_msg'] = $bind_list['bind_msg'];
        }
        if (isset($bind_list['data'])) {
            $arr['bind_list'] = $bind_list['data'];
        } else {
            $arr['bind_list'] = $bind_list;
        }

        return api_output(0,$arr);
    }

    /**
     * 获取街道社区网格事件二级分类
     * @author: liukezhu
     * @date : 2022/6/9
     * @return \json
     */
    public function  getFidCategory() {
        $cat_id = $this->request->post('cat_id',0);
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id || !$cat_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $where[] = ['area_id','=',$street_id];
            $where[] = ['cat_fid','=',$cat_id];
            $where[] = ['status','=',1];
            $field='cat_id,cat_name';
            $list = (new AreaStreetEventCategoryService())->getCategoryList($where,$field,0,15,'sort desc,cat_id desc');
            return api_output(0, $list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }


    /**
     * Notes: 图片上传
     * @return \json
     * @author: wanzy
     * @date_time: 2021/2/23 19:30
     */
    public function  streetEventImg() {
        $file = $this->request->file('imgFile');
        $service_area_street = new AreaStreetService();
        try{
            $imgurl = $service_area_street->uploads($file,'gridUser');
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
     * Notes: 住户添加工单
     * @return \json
     * @author: wanzy
     * @date_time: 2021/2/24 15:35
     */
    public function streetOrderAdd() {
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $cat_id = $this->request->post('cat_id',0);
        if(!$cat_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $bind_id = $this->request->post('pigcms_id',0);
        if(!$bind_id) {
            return api_output_error(1001,'请选择所处位置如果没有位置请前往相应小区绑定');
        }
        $order_imgs = $this->request->post('order_imgs');
        $order_content = $this->request->post('order_content');
        $order_content = htmlspecialchars(trim($order_content));
        if (empty($order_imgs) && !$order_content) {
            return api_output_error(1001,'缺少必传参数');
        }
        $street_works_order_service = new StreetWorksOrderService();
        $data = [
            'uid' => $uid,
            'cat_id' => $cat_id,
            'street_id' => $street_id,
            'bind_id' => $bind_id,
            'order_imgs' => $order_imgs?$order_imgs:'',
            'order_content' => $order_content?$order_content:'',
            'now_role' => 1,
            'order_type' => 0,
            'order_status' => 1,
            'event_status' => 0,
        ];
        try{
            $info = $street_works_order_service->addStreetWorksOrder($data);
            $arr = [];
            $arr['info'] = $info;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes: 获取订单详情
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/2/25 11:00
     */
    public function streetOrderDetail() {
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $street_works_order_service = new StreetWorksOrderService();
        $where_order = [];
        $where_order[] = ['e.order_id','=',$order_id];
        $where_order[] = ['e.order_type','=',0];
        $where_order[] = ['e.uid','=',$uid];
        $where_order[] = ['e.del_time','=',0];
        $arr = [];
        $field = 'e.*,g.polygon_name';
        $order_detail = $street_works_order_service->getUserWorksOrder($where_order,$field,true);
        $arr['order_detail'] = $order_detail;
        return api_output(0, $arr);
    }

    /**
     * Notes: 用户更改工单状态
     * @return \json
     * @author: wanzy
     * @date_time: 2021/2/25 13:58
     */
    public function streetOrderChange() {
        $log_name = $this->request->post('log_name');
        $order_id = $this->request->post('order_id',0);
        if(!$order_id || !$log_name) {
            return api_output_error(1001,'缺少必传参数');
        }
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $order_imgs = $this->request->post('order_imgs');
        $order_content = $this->request->post('order_content');
        $order_content = htmlspecialchars(trim($order_content));
        $street_works_order_service = new StreetWorksOrderService();
        $data = [
            'uid' => $uid,
            'order_id' => $order_id,
            'log_name' => $log_name,
            'order_imgs' => $order_imgs?$order_imgs:'',
            'order_content' => $order_content?$order_content:'',
        ];
        try{
            $log_arr = $street_works_order_service->filterAddWorkOrderLog(0,$data);
            $arr = [];
            $arr['log'] = $log_arr;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes: 用户获取工单处理记录
     * @return \json
     * @author: wanzy
     * @date_time: 2021/2/25 16:05
     */
    public function userGetOrderLog() {
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $order_id = $this->request->post('order_id',0);
        if(!$order_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $street_works_order_service = new StreetWorksOrderService();
            $log_list = $street_works_order_service->userGetOrderLog($order_id, $uid);
            $arr['log'] = $log_list;
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}