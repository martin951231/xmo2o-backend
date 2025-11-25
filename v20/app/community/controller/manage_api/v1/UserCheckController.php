<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/11 18:20
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;

use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\UserService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\common\model\service\weixin\TemplateNewsService;

use think\facade\Log;
class UserCheckController extends BaseController{
    /**
     * 获取配置项
     * @author: wanziyang
     * @date_time: 2020/6/12 16:25
     * @return \json
     */
    public function index()  {
        $arr = $this->getLoginInfo();
        $out = [];
        $head_tab = [];

        if (empty($this->auth) || in_array(101,$this->auth)) {
            $head_tab[] = [
                'title' => '待审核业主',
                'type' => 1
            ];
        }
        if (empty($this->auth) || in_array(103,$this->auth)) {
            $head_tab[] = [
                'title' => '待审核家属',
                'type' => 2
            ];
        }
        if (empty($this->auth) || in_array(410,$this->auth)) {
            $head_tab[] = [
                'title' => '待审核租客',
                'type' => 3
            ];
        }
        if (empty($this->auth) || in_array(107,$this->auth)) {
            $head_tab[] = [
                'title' => '待申请解绑',
                'type' => 4
            ];
        }
        $out['head_tab'] = $head_tab;


        return api_output(0,$out);
    }


    /**
     * 获取业主待审核列表
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'page'=> '页数 必传',
     *  'search_val'=> '查询字段 支持姓名 手机 物业编号 模糊查询 选传',
     *  'begin_time'=> '时间范围查询 起始时间 选传',
     *  'end_time'=> '时间范围查询 结束时间',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/12 11:52
     * @return \json
     */
    public function ownerCheckList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(101,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $page = $this->request->param('page','1','intval');
        if (!$page) {
            $page = 1;
        }
        $page_size = $this->request->param('page_size','10','intval');

        $service_house_village = new HouseVillageService();


        $search_val = $this->request->param('search_val','','trim');
        $begin_time = $this->request->param('begin_time','','trim');
        $end_time = $this->request->param('end_time','','trim');

        $where = [];
        $where[] = ['h.village_id','=',$village_id];
        $where[] = ['h.status','=','2'];
        $where[] = ['h.type','in','0,3'];
        if ($search_val) {
            $where[] = ['h.name|h.phone|h.usernum|h.bind_number','LIKE', '%'.$search_val.'%'];
        } else {
            $where[] = ['h.uid|h.phone|h.name', 'not in', [0,'']];
        }
        if ($begin_time && !$end_time) {
            $where[] = ['h.add_time','>',strtotime($begin_time)];
        }
        if (!$begin_time && $end_time) {
            $where[] = ['h.add_time','<',strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))];
        }
        if ($begin_time && $end_time) {
            $where[] = ['h.add_time','between',[strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))]];
        }
        $field = 'a.pigcms_id,a.village_id,h.pigcms_id as bind_id,h.name,h.phone,h.type,h.status,h.add_time as application_time,a.floor_id,a.layer,a.room,a.single_id,a.usernum,h.bind_number,a.add_time,b.single_name,c.floor_name,c.floor_layer,d.avatar,a.layer_id';
        $order = 'a.pigcms_id desc,h.pigcms_id desc';
        $user_list = $service_house_village->getUserVacancyPageList($where,$field,$page,$order,$page_size);
        $out = [];
        $out['list'] = $user_list;
        $out['page'] = $page;
        return api_output(0,$out);
    }

    /**
     * 审核业主
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '审核对象id 前一个接口的pigcms_id(房屋id) 必传',
     *  'bind_id'=> '审核对象bind_id 前一个接口的bind_id(用户绑定id) 必传',
     *  'check_status'=> '审核状态  1 通过  2 拒绝 必传',
     *  'memo'=> '审核备注 选填',
     *  'reason'=> '审核拒绝原因 选填',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/12 15:02
     * @return \json
     */
    public function ownerCheck() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(102,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        $bind_id = $this->request->param('bind_id','','intval');
        if (!$pigcms_id && !$bind_id) {
            return api_output(1001,[],'缺少审核对象！');
        }
        $service_house_village = new HouseVillageService();
        $service_user = new HouseVillageUserService();
        if ($pigcms_id) {
            $where_room = [];
            $where_room[] = ['village_id', '=', $village_id];
            $where_room[] = ['pigcms_id', '=', $pigcms_id];
            $info = $service_house_village->getRoomInfoWhere($where_room);
        }

        // 审核状态  1 通过  2 拒绝
        $check_status = $this->request->param('check_status','','intval');
        if (empty($check_status)) {
            return api_output(1001,[],'缺少审核结果！');
        }

        // 审核备注
        $memo = $this->request->param('memo','','trim');

        //检测用户是否已存在 业主
        $where_bind = [];
        if ($bind_id) {
            $where_bind[] = ['pigcms_id','=',$bind_id];
        } else {
            $where_bind[] = ['vacancy_id','=',$pigcms_id];
            $where_bind[] = ['status','=',2];
        }
        $where_bind[] = ['type','in',[0,3]];
        $bind_info = $service_user->getHouseUserBindWhere($where_bind);
        if (empty($bind_info)) {
            return api_output(1001,[],'缺少审核对象！');
        }
        if (!$info && $bind_info) {
            $where_room = [];
            $where_room[] = ['village_id', '=', $village_id];
            $where_room[] = ['pigcms_id', '=', $bind_info['vacancy_id']];
            $info = $service_house_village->getRoomInfoWhere($where_room);
        }
        if (empty($info)) {
            return api_output(1001,[],'对应审核房屋不存在！');
        }
        $service_user_info = new UserService();
        fdump_api(['单个审核业主'.__LINE__,$_GET,$_POST,$check_status,$arr],'house/user/ownerCheckLog',1);
        if (1==$check_status) {
            // 审核通过
            $uid = 0;
            if ($bind_info['phone']) {
                $where_user = [];
                $where_user[] = ['phone','=',$bind_info['phone']];
                $user = $service_user_info->getUserOne($where_user,'uid');
                if ($user && $user['uid']) {
                    $uid = $user['uid'];
                }
            }
            $data['uid'] = $uid;
            $whereUserArr=array();
            if($uid>0){
                $whereUserArr[]=['uid','=',$uid];
            }elseif($bind_info['phone']){
                $whereUserArr[] = ['phone','=',$bind_info['phone']];
                $whereUserArr[] = ['status', '=',1];
            }
            $now_user = $service_user_info->getUserOne($whereUserArr,'*');
            $data['usernum'] = $info['usernum'];
            $data['name'] = $bind_info['name'];
            $data['phone'] = $bind_info['phone'];
            $data['floor_id'] = $info['floor_id'];
            $data['memo'] = $memo ? $memo : '';
//            $data['address'] = $address;
            $data['village_id'] = $village_id;

            $data['layer_num'] = $info['layer'];
            $data['room_addrss'] =  $info['room'];
            $data['housesize'] = $info['housesize'];

            $data['park_flag'] = $info['park_flag'];
            $data['add_time'] = time();
            $data['vacancy_id'] = $info['pigcms_id'];
            $data['type'] = 0;
            $data['status'] = 1;
            $y_pigcms_id =0;
            if($bind_info){
                // 如果是更换业主
                fdump('业主审核>>>'.__LINE__,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                fdump($bind_info,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                if (3==$bind_info['type']) {
                    $where_con = [];
                    $where_con[] = ['pigcms_id','<>',$bind_info['pigcms_id']];
                    $where_con[] = ['vacancy_id','=',$bind_info['vacancy_id']];
                    // 将现有的家属租客等改为审核拒绝状态
                    $set = ['status'=>0];
                    fdump('将现有的家属租客等改为待审核状态>>>'.__LINE__,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                    fdump($where_con,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                    fdump($set,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                    $service_user->saveUserBindOne($where_con,$set);
                }
                if($info){
                    $room_usernum = $info['usernum'];
                    // 查询下是否存在虚拟业主  存在直接替换 然后删除现在的申请数据
                    if (0==$bind_info['type'] || 3==$bind_info['type']) {
                        $where_virtual = [];
                        $where_virtual[] = ['vacancy_id', '=', $bind_info['vacancy_id']];
                        $where_virtual[] = ['usernum', '=', $room_usernum];
                        $where_virtual[] = ['pigcms_id', '<>', $bind_info['pigcms_id']];
                        $where_virtual[] = ['type', 'in', '0,3'];
                        $where_virtual[] = ['name', '=', ''];
                        $where_virtual[] = ['phone', '=', ''];
                        $where_virtual[] = ['uid', '=', 0];
                        $virtual_owner = $service_user->getHouseUserBindWhere($where_virtual);
                        if ($virtual_owner) {
                            fdump('虚拟业主>>>'.__LINE__,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                            fdump($virtual_owner,'api/log/'.date('Ymd').'/user_check/owner_check_log',true);
                            // 存在虚拟业主
                            // 记录原来用户id
                            $pigcms_id = $virtual_owner['pigcms_id'];
                            if ($bind_id) {
                                $y_pigcms_id = $pigcms_id;
                                $where_bind = [];
                                $where_bind[] = ['pigcms_id','=',$pigcms_id];
                            }
                            $data['add_time'] = $info['add_time'];
                            $data['id_card'] = $info['id_card'];
                            $data['single_id'] = $info['single_id'];
                            $data['layer_id'] = $info['layer_id'];
                            $data['floor_id'] = $info['floor_id'];
                            $data['address'] = $info['address'] ? $info['address'] : '';
                            $data['authentication_field'] = $info['authentication_field'] ? $info['authentication_field'] : '';
                        }
                        if ($room_usernum) {
                            $where_other = [
                                'vacancy_id' => $bind_info['vacancy_id'],
                                'usernum' => $room_usernum
                            ];
                            $set = [
                                'usernum' => $room_usernum.'-y-'.createRandomStr(11,true),
                                'status' => 0
                            ];
                            $a = $service_user->saveUserBindOne($where_other,$set);
                            if ($a!==false) {
                                $data['usernum'] = createRandomStr(11,true).'-'.$pigcms_id;
                            } else {
                                fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/user_check/owner_check_err_log',true);
                                fdump($a,'api/log/'.date('Ymd').'/user_check/owner_check_err_log',true);
                                fdump($where_other,'api/log/'.date('Ymd').'/user_check/owner_check_err_log',true);
                                fdump($set,'api/log/'.date('Ymd').'/user_check/owner_check_err_log',true);
                                return api_output(1003,[],'审核失败，请稍后重试!');
                            }
                        }
                    }
                }
                $face_pigcms_id = $pigcms_id;
                $insert_id = $service_user->saveUserBindOne($where_bind,$data);
            }else{
                $insert_id = $service_user->addUserBindOne($data);
                $face_pigcms_id = $insert_id;
            }
            if($insert_id){
                if ($y_pigcms_id) {
                    $where_bind_del = [
                        'pigcms_id' => $y_pigcms_id
                    ];
                    $del_id = $service_user->delUserBindOne($where_bind_del);
                    fdump('如果存在原数据id 删除申请数据>>'.__LINE__,'api/log/'.date('Ymd').'/user_check/check_delete_log',true);
                    fdump($info,'api/log/'.date('Ymd').'/user_check/check_delete_log',true);
                    fdump($where_bind_del,'api/log/'.date('Ymd').'/user_check/check_delete_log',true);
                    fdump($data,'api/log/'.date('Ymd').'/user_check/check_delete_log',true);
                }
                $data_room =[];
                $data_room['status'] = 3;
                if ($data['uid']) {
                    $data_room['uid'] = $data['uid'];
                } elseif($info['uid']) {
                    $data_room['uid'] = $info['uid'];
                }
                if ($data['name']) {
                    $data_room['name'] = $data['name'];
                } elseif($info['name']) {
                    $data_room['name'] = $info['name'];
                }
                if ($data['phone']) {
                    $data_room['phone'] = $data['phone'];
                } elseif($info['phone']) {
                    $data_room['phone'] = $info['phone'];
                }
                $data_room['memo'] = $memo ? $memo : "";
                $service_user->saveHouseRoom($where_room,$data_room);
                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$bind_id,'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$bind_id;
                $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
                $service_user->add_third_log($arr);
                //发送模板消息
                if ($now_user && $now_user['openid']) {
                    $now_village = $service_house_village->getHouseVillageInfo([['village_id', '=', $village_id]], 'village_id,village_name,village_address,property_id');
                    $href = cfg('site_url') . '/wap.php?g=Wap&c=House&a=my_village_list';
                    $templateNewsService = new TemplateNewsService();
                    $datamsg = [
                        'tempKey' => 'OPENTM405462911',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => '您在【' . $now_village['village_name'] . '】的业主入住申请审核已通过\n',
                            'keyword1' => '业主入住申请',
                            'keyword2' => '已发送',
                            'keyword3' => date('Y-m-d H:i:s', time()),
                            'keyword4' => $now_user['nickname'],
                            'remark' => '\n请点击查看详细信息！'
                        ]
                    ];
                    //调用微信模板消息
                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, $now_village['property_id'], 1);
                }
                if (intval($face_pigcms_id)>0) {
                    $house_village_user_vacancy = new HouseVillageUserVacancyService();
                    $change_param = [];
                    $change_param['pigcms_id'] = $face_pigcms_id;
                    $house_village_user_vacancy->changeUserBindNumber($change_param);
                }

                return api_output(0,['pigcms_id' => $insert_id],'审核操作成功！');
            }else{
                fdump('>>>'.__LINE__,'api/log/'.date('Ymd').'/user_check/owner_check_err_log',true);
                fdump($insert_id,'api/log/'.date('Ymd').'/user_check/owner_check_err_log',true);
                fdump($service_user,'api/log/'.date('Ymd').'/user_check/owner_check_err_log',true);
                return api_output(1003,[],'审核失败，请稍后重试!');
            }
        } else {
            // 审核拒绝
            $reason = $this->request->param('reason','','trim');
            $edit_data = array();
            $edit_data['uid'] = 0;
            $edit_data['status'] = 1;
            $edit_data['phone'] = '';
            $edit_data['reason'] = trim($reason) ? htmlspecialchars(trim($reason)) : '无';
            $insert_id = $service_user->saveHouseRoom(['pigcms_id'=>$pigcms_id],$edit_data);
            if($bind_info){ //存在业主信息
                // 查询有无家属信息
                $where_family = [
                    ['village_id', '=', $village_id],
                    ['type','=','1,2']
                ];
                $family_count = $service_house_village->getVillageUserNum($where_family);
                if ($family_count) { // 更新为虚拟房主
                    $edit_data = array();
                    $edit_data['uid'] = 0;
                    $edit_data['name'] = '';
                    $edit_data['phone'] = '';
                    $edit_data['status'] = 0;
                    $where_bind = [];
                    $where_bind[] = ['pigcms_id', '=', $bind_info['pigcms_id']];
                    $service_user->saveUserBindOne($where_bind,$edit_data);
                }else{
                    // 删除房主信息
                    $where_bind = [];
                    $where_bind[] = ['pigcms_id', '=', $bind_info['pigcms_id']];
                    $service_user->delUserBindOne($where_bind);
                }
                if($insert_id!==false){
                    //发送模板消息
                    $whereUserArr=array();
                    if($bind_info['phone']){
                        $whereUserArr[] = ['phone','=',$bind_info['phone']];
                        $whereUserArr[] = ['status', '=',1];
                    } else if($bind_info['uid']>0){
                        $whereUserArr[]=['uid','=',$bind_info['uid']];
                    }
                    $now_user = $service_user_info->getUserOne($whereUserArr, '*');
                    if ($now_user && $now_user['openid']) {
                        $now_village = $service_house_village->getHouseVillageInfo([['village_id', '=', $village_id]], 'village_id,village_name,village_address,property_id');
                        $href = cfg('site_url') . '/wap.php?g=Wap&c=House&a=my_village_list';
                        $templateNewsService = new TemplateNewsService();
                        $datamsg = [
                            'tempKey' => 'OPENTM405462911',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $now_user['openid'],
                                'first' => '您在【' . $now_village['village_name'] . '】的业主入住申请审核未通过。原因：' . $edit_data['reason'] . '\n',
                                'keyword1' => '业主入住申请',
                                'keyword2' => '已发送',
                                'keyword3' => date('Y-m-d H:i:s', time()),
                                'keyword4' => $now_user['nickname'],
                                'remark' => '\n请点击查看详细信息！'
                            ]
                        ];
                        //调用微信模板消息
                        $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, $now_village['property_id'], 1);
                    }
                    return api_output(0,['pigcms_id' => $bind_info['pigcms_id']],'审核操作成功！');
                }else{
                    return api_output(1003,[],'审核失败，请稍后重试!');
                }
            }
        }
    }

    /**
     * 业主审核详情页面
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '房屋id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/13 16:18
     * @return \json
     */
    public function ownerCheckDetail() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(101,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        $bind_id = $this->request->param('bind_id','','intval');
        if (!$pigcms_id) {
            return api_output(1001,[],'缺少对应业主对象！');
        }
        $service_user = new HouseVillageUserService();
        $where = [];
        $where[] = ['pigcms_id','=',$pigcms_id];
        $list = $service_user->getRoomUserWhere($where,$village_id,false,true, $bind_id, 2);
        $out = [];
        $out['list'] = $list;
        $out['now_bind_info'] = $list;
        $out['dataList'] = $list['dataList'];
        $out['is_edit'] = true;
        if ($this->auth && !in_array(102,$this->auth)) {
            $out['is_edit'] = false;
        }
        return api_output(0,$out);
    }


        /**
     * 获取家属、租客待审核列表
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'page'=> '页数 必传',
     *  'type'=> '获取类型 家属传1 租客传2  默认家属 选传',
     *  'status'=> '状态 获取待审核不必传 默认2',
     *  'search_val'=> '查询字段 支持姓名 手机 物业编号 模糊查询 选传',
     *  'begin_time'=> '时间范围查询 起始时间 选传',
     *  'end_time'=> '时间范围查询 结束时间 选传',
     *  'pigcms_id'=> '业主的小区绑定id 不必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/12 11:52
     * @return \json
     */
    public function bindCheckList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(103,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $page = $this->request->param('page','1','intval');
        if (!$page) {
            $page = 1;
        }
        $page_size = $this->request->param('page_size','10','intval');

        // 状态  默认审核中
        $status = $this->request->param('status','2','intval');
        if (!$status) {
            $status = 2;
        }
        // 业主小区绑定id
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        // 查询身份 默认家属  1 家属  2 租客
        $type = $this->request->param('type','1','intval');

        $where = [];
        $where[] = ['a.status','=',$status];
        $where[] = ['a.village_id','=',$village_id];
        $where[] = ['a.type','=',$type];
        if ($pigcms_id) {
            $where[] = ['a.parent_id','=',$pigcms_id];
        }
        $search_val = $this->request->param('search_val','','trim');
        $begin_time = $this->request->param('begin_time','','trim');
        $end_time = $this->request->param('end_time','','trim');
        if ($search_val) {
            $where[] = ['a.name|a.phone|a.usernum|a.bind_number','LIKE', '%'.$search_val.'%'];
        } else {
            //$where[] = ['a.phone','<>',''];
           // $where[] = ['a.name','<>',''];
        }
        if ($begin_time && !$end_time) {
            $where[] = ['a.add_time','>',strtotime($begin_time)];
        }
        if (!$begin_time && $end_time) {
            $where[] = ['a.add_time','<',strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))];
        }
        if ($begin_time && $end_time) {
            $where[] = ['a.add_time','between',[strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))]];
        }
        $service_user = new HouseVillageUserService();
        $field = 'a.pigcms_id,a.single_id,a.floor_id,a.layer_id,a.vacancy_id,a.add_time,c.avatar,a.type,a.name,a.relatives_type,a.village_id,a.uid,a.usernum,a.bind_number,a.name,a.phone,a.address,a.layer_num,a.room_addrss,a.pass_time,a.parent_id,hvf.floor_name,hvf.floor_layer,hvs.single_name';
        $list = $service_user->getLimitRoomList($where,$page,$field,'a.pigcms_id DESC',true);
        $housVillageServicer = new HouseVillageService();
        $village_info = $housVillageServicer->getHouseVillageInfo(array('village_id'=>$village_id),'*');
        $user_list = [];
        $service_login = new ManageAppLoginService();
        $user_bind_type_color = $service_login->user_bind_type_color;
        $user_bind_type_arr = $service_login->user_bind_type_arr;
        $user_bind_relatives_type_arr = $service_login->user_bind_relatives_type_arr;
        $site_url = cfg('site_url');
        $static_resources = static_resources(true);
        foreach ($list as &$val) {
            $msg = [];
            $msg['pigcms_id'] = $val['pigcms_id'];
            $val['add_time'] = $val['add_time'] ? date('Y-m-d H:i:s',$val['add_time']) : '';
            if (1==$val['type'] && $type!=2) {
                $msg['identity_tag'] = $user_bind_type_arr[$val['type']] . ' | ' . $user_bind_relatives_type_arr[$val['relatives_type']];
                $msg['tag_color'] = $user_bind_type_color[$val['type']];
            } elseif($type!=2) {
                $msg['identity_tag'] = $user_bind_type_arr[$val['type']];
                $msg['tag_color'] = $user_bind_type_color[$val['type']];
            }
            if (empty($val['avatar'])) {
                $val['avatar'] =  $site_url . $static_resources . 'images/avatar.png';
            }
            $msg['name'] = $val['name'];
            $msg['avatar'] = $val['avatar'];
            if(isset($val['phone']) && !empty($val['phone'])){
                $val['phone']=$housVillageServicer->phoneDesensitizationV($val['phone'],$village_id,$village_info);
            }
            $content = [];
            $content[] = [
                'title' => '手机号码',
                'info' => $val['phone']
            ];
            $content[] = [
                'title' => '家庭住址',
                'info' => $val['address']
            ];
            if ($val['parent_info']) {
                if(isset($val['parent_info']['phone']) && !empty($val['parent_info']['phone'])){
                    $val['parent_info']['phone']=$housVillageServicer->phoneDesensitizationV($val['parent_info']['phone'],$village_id,$village_info);
                }
                if (!$val['parent_info']['name'] && !$val['parent_info']['phone']) {
                    $info = '无';
                } else {
                    $info = $val['parent_info']['name'] . ' ' .$val['parent_info']['phone'];
                }
                $content[] = [
                    'title' => '业主信息',
                    'info' => $info
                ];
            }
            if ($val['add_time_txt']) {
                $content[] = [
                    'title' => '申请时间',
                    'info' => $val['add_time_txt']
                ];
            }
            $msg['content'] = $content;

            $user_list[] = $msg;
        }

        $out['list'] = $user_list;
        $out['page'] = $page;
        return api_output(0,$out);
    }

    /**
     * 家属、租客审核
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '用户小区绑定id 必传',
     *  'check_status'=> '审核状态  1 通过  2 拒绝 必传',
     *  'reason'=> '审核备注 选填',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/12 19:12
     * @return \json
     */
    public function bindCheck() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(468,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        //小区信息
        $service_house_village = new HouseVillageService();
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,property_id');
        // 小区用户绑定id
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少审核对象！');
        }
        // 审核状态  1 通过  2 拒绝
        $check_status = $this->request->param('check_status','','intval');
        if (empty($check_status)) {
            return api_output(1001,[],'缺少审核结果！');
        }
        // 审核理由
        $reason = $this->request->param('reason','','trim');
        $reason = $reason ? htmlspecialchars($reason) : '无';

        $service_user = new HouseVillageUserService();
        // 当前业主信息
        $bind_where['pigcms_id'] = $pigcms_id;
        $field = 'pigcms_id,village_id,uid,usernum,bind_number,name,phone,type,status,vacancy_id';
        $now_bind_info = $service_user->getHouseUserBindWhere($bind_where,$field);
        if (empty($now_bind_info)) {
            return api_output(1001,[],'审核信息不存在！');
        }
        if(2==$check_status) {
            if ($now_bind_info['status'] == 1) { // 已通过审核！
                return api_output(1001,[],'已通过审核！');
            }
        }
        fdump_api(['单个审核家属业主'.__LINE__,$_GET,$_POST,$reason,$check_status,$arr],'house/user/bindCheckLog',1);
        if (2==$check_status) {
            $where_bind = [];
            $where_bind[] = ['pigcms_id','=',$pigcms_id];
            $edit_data = [
                'pass_time' => time(),
                'status' => 0,
                'reason' => $reason,
            ];
            $insert_id = $service_user->saveUserBindOne($where_bind,$edit_data);
            $face_pigcms_id = $pigcms_id;
            if($now_bind_info['type'] != 3){
                $temp_name = cfg('open_house_user_name')?'亲友':'家属';
                if ($now_bind_info['status'] == 1 ) {
                    $first = "您在【{$now_village['village_name']}】的{$temp_name}入住申请已解绑。原因：{$reason}";
                    $keyword1 = "{$temp_name}入住申请";
                }else{
                    $first = "您在【{$now_village['village_name']}】的{$temp_name}入住申请审核未通过。原因：{$reason}";
                    $keyword1 =  "{$temp_name}入住申请";
                }
            }else{
                $first = "您在【{$now_village['village_name']}】的替换业主申请审核未通过。原因：{$reason}";
                $keyword1 = '替换业主申请';
            }
        } else {
            if (3==$now_bind_info['type']) {
                // 更新原房主信息
                $user_data['uid'] = $now_bind_info['uid'];
                $user_data['name'] = $now_bind_info['name'];
                $user_data['phone'] = $now_bind_info['phone'];
                $user_data['pass_time'] = time();
                $user_data['parent_id'] = 0;
                $where_bind = [];
                $where_bind[] = ['pigcms_id','=',$pigcms_id];
                $insert_id = $service_user->saveUserBindOne($where_bind,$user_data);
                $face_pigcms_id = $pigcms_id;

                #要修改 房间house_village_user_vacancy 表信息
                $vacancy_data['uid'] = $now_bind_info['uid'];
                $vacancy_data['name'] = $now_bind_info['name'];
                $vacancy_data['phone'] = $now_bind_info['phone'];
                $vacancy_data['status'] = 3;

                $vacancy_where = [];
                $vacancy_where[] = ['pigcms_id','=',$now_bind_info['vacancy_id']];
                $vacancy_where[] = ['floor_id','=',$now_bind_info['floor_id']];
                $vacancy_where[] = ['village_id','=',$now_bind_info['village_id']];


                $service_user->saveHouseRoom($vacancy_where,$vacancy_data);

                // 删除申请记录
                $service_user->delUserBindOne(['pigcms_id'=>$pigcms_id]);

                $first = "您在【{$now_village['village_name']}】的替换业主申请审核已通过{$reason}";
                $keyword1 = '替换业主申请';
                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$pigcms_id,'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$pigcms_id;
                $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
                $service_user->add_third_log($arr);
            } else {
                // 房屋信息
                $where_room = [];
                $where_room[] = ['pigcms_id', '=', $now_bind_info['vacancy_id']];
                $info = $service_house_village->getRoomInfoWhere($where_room);

                if(isset($info['phone']) && isset($now_bind_info['phone']) && $now_bind_info['phone'] == $info['phone']){
                    if ($info['status']==2) {
                        return api_output(1003,[],'该手机号已提交申请!');
                    }else{
                        return api_output(1003,[],'手机号不能与业主一致!');
                    }
                }
                $user_data = [
                    'pass_time' => time(),
                    'status' => 1
                ];
                $where_bind = [];
                $where_bind[] = ['pigcms_id','=',$pigcms_id];
                $insert_id = $service_user->saveUserBindOne($where_bind,$user_data);
                $face_pigcms_id = $pigcms_id;
                $temp_name = cfg('open_house_user_name')? '亲友' : '家属';
                $first = "您在【{$now_village['village_name']}】的{$temp_name}入住申请审核已通过";
                $keyword1 = "{$temp_name}入住申请";
                $arr = array();
                $arr['param'] = serialize(array('bind_id' =>$pigcms_id,'type'=>'user'));
                $arr['plan_time'] = time()+60;
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_village_third';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_village_third_user_'.$pigcms_id;
                $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
                $service_user->add_third_log($arr);

            }
        }
        if($insert_id){
            $service_user_info = new UserService();
            $whereUserArr=array();
            if ($now_bind_info['phone']) {
                $whereUserArr[] = ['phone', '=', $now_bind_info['phone']];
                $whereUserArr[] = ['status', '=', 1];
            } else if ($now_bind_info['uid'] > 0) {
                $whereUserArr[] = ['uid', '=', $now_bind_info['uid']];
            }
            $now_user = $service_user_info->getUserOne($whereUserArr, '*');

            //发送模板消息
            if ($now_user && $now_user['openid']) {
                $href = cfg('site_url') . '/wap.php?g=Wap&c=House&a=my_village_list';
                $templateNewsService = new TemplateNewsService();
                $datamsg = [
                    'tempKey' => 'OPENTM405462911',
                    'dataArr' => [
                        'href' => $href,
                        'wecha_id' => $now_user['openid'],
                        'first' => $first,
                        'keyword1' => $keyword1,
                        'keyword2' => '已发送',
                        'keyword3' => date('Y-m-d H:i:s', time()),
                        'keyword4' => $now_user['nickname'],
                        'remark' => '\n请点击查看详细信息！'
                    ]
                ];
                //调用微信模板消息
                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'], 0, $now_village['property_id'], 1);
            }
//            if (intval($face_pigcms_id)>0) {
//                $house_village_user_vacancy = new HouseVillageUserVacancyService();
//                $change_param = [];
//                $change_param['pigcms_id'] = $face_pigcms_id;
//                $house_village_user_vacancy->changeUserBindNumber($change_param);
//            }
            return api_output(0, ['pigcms_id' => $insert_id], '审核操作成功！');
        } else {
            return api_output(1003, [], '审核失败，请稍后重试!');
        }
    }

    /**
     * 家属租客审核详情页面
     * array (
     *  'village_id'=> '小区id 必传',
     *  'pigcms_id'=> '小区用户绑定id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/13 16:18
     * @return \json
     */
    public function bindDetail() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(103,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $pigcms_id = $this->request->param('pigcms_id','','intval');
        if (empty($pigcms_id)) {
            return api_output(1001,[],'缺少对应房屋对象！');
        }
        $service_user = new HouseVillageUserService();
        $where = [];
        $where[] = ['pigcms_id','=',$pigcms_id];
        $field = 'pigcms_id,village_id,uid,usernum,bind_number,name,phone,floor_id,single_id,type,id_card,ic_card,attribute,position_id,park_flag,authentication_field,vacancy_id as room_id';
        $list = $service_user->getHouseUserWhere($where,$village_id,$field);
        $out = [];
        $out['list'] = $list;
        $out['now_bind_info'] = $list;
        if(isset($list['dataList'])){
            $out['dataList'] = $list['dataList'];
        }
        $out['is_edit'] = true;
        if ($this->auth && !in_array(116,$this->auth)) {
            $out['is_edit'] = false;
        }
        return api_output(0,$out);
    }

    /**
     * 获取解绑待审核列表
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'page'=> '页数 必传',
     *  'search_val'=> '查询字段 支持姓名 手机 物业编号 模糊查询 选传',
     *  'begin_time'=> '时间范围查询 起始时间 选传',
     *  'end_time'=> '时间范围查询 结束时间',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/12 11:52
     * @return \json
     */
    public function unBindCheckList() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(107,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $page = $this->request->param('page','1','intval');
        if (!$page) {
            $page = 1;
        }
        $page_size = $this->request->param('page_size','10','intval');

        $service_house_village = new HouseVillageService();


        $search_val = $this->request->param('search_val','','trim');
        $begin_time = $this->request->param('begin_time','','trim');
        $end_time = $this->request->param('end_time','','trim');
        // 状态 1 申请 2 未通过 3 已通过
        $status = $this->request->param('status','1','intval');

        $where = [];
        $where[] = ['a.village_id','=',$village_id];
        $where[] = ['a.status','=',$status];
        if ($search_val) {
            $where[] = ['a.name|a.phone','LIKE', '%'.$search_val.'%'];
        }
        if ($begin_time && !$end_time) {
            $where[] = ['a.addtime','>',strtotime($begin_time)];
        }
        if (!$begin_time && $end_time) {
            $where[] = ['a.addtime','<',strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))];
        }
        if ($begin_time && $end_time) {
            $where[] = ['a.addtime','between',[strtotime($begin_time),strtotime(date('Y-m-d 23:59:59',strtotime($end_time)))]];
        }
        $field = 'a.*,a.bind_id as pigcms_id,b.layer,b.room,ub.address,ub.relatives_type,c.floor_name,c.floor_layer,d.single_name,u.avatar,b.house_type';
        $order = 'a.itemid desc';
        $list = $service_house_village->getUnbindUserLimitList($where,$page,$field,$order,$page_size);

        $service_login = new ManageAppLoginService();
        $user_bind_type_color = $service_login->user_bind_type_color;
        $user_bind_type_arr = $service_login->user_bind_type_arr;
        $user_bind_type_arr_other = $service_login->user_bind_type_arr_other;
        $user_bind_relatives_type_arr = $service_login->user_bind_relatives_type_arr;
        $site_url = cfg('site_url');
        $static_resources = static_resources(true);
        $service_house_village = new HouseVillageService();
        $village_info = $service_house_village->getHouseVillageInfo(array('village_id'=>$village_id),'*');
        $user_list = [];
        foreach ($list as $value) {
            $msg = [];
            $msg['itemid'] = $value['itemid'];
            $msg['bind_id'] = $value['bind_id'];
            $msg['pigcms_id'] = $value['pigcms_id'];
            if (1==$value['type']) {
                $msg['identity_tag'] = ($value['type']&&$value['relatives_type'])?$user_bind_type_arr[$value['type']] . ' | ' . $user_bind_relatives_type_arr[$value['relatives_type']]:'';
                $msg['tag_color'] = $user_bind_type_color[$value['type']];
            } else {
                if($value['house_type'] != 1)
                    $msg['identity_tag'] = $value['type']?$user_bind_type_arr_other[$value['type']]:'';
                else
                    $msg['identity_tag'] = $value['type']?$user_bind_type_arr[$value['type']]:'';
                $msg['tag_color'] = $value['type']?$user_bind_type_color[$value['type']]:'';
            }
            if (empty($value['avatar'])) {
                $value['avatar'] =  $site_url . $static_resources . 'images/avatar.png';
            }
            $msg['name'] = $value['name'];
            $msg['avatar'] = $value['avatar'];

            $value['addtime'] = date('Y-m-d H:i:s',$value['addtime']);
            $word_msg = [
                'single_name' => $value['single_name'] ? $value['single_name'] : $value['floor_layer'],
                'floor_name' => $value['floor_name'],
                'layer' => $value['layer'],
                'room' => $value['room'],
            ];
            $value['address'] = $service_house_village->word_replce_msg($word_msg);
            if(isset($value['phone']) && !empty($value['phone'])){
                $value['phone']=$service_house_village->phoneDesensitizationV($value['phone'],$village_id,$village_info);
            }
            $content = [];
            $content[] = [
                'title' => '手机号码',
                'info' => $value['phone']
            ];
            $content[] = [
                'title' => '家庭住址',
                'info' => $value['address']
            ];
            $content[] = [
                'title' => '解绑原因',
                'info' => $value['note'] ? $value['note'] : '无'
            ];
            $content[] = [
                'title' => '申请时间',
                'info' => $value['addtime']
            ];
            $msg['content'] = $content;

            $user_list[] = $msg;
        }
        $out = [];
        $out['list'] = $user_list;
        $out['page'] = $page;
        return api_output(0,$out);
    }

    /**
     * 解绑审核
     * @param 传参
     * array (
     *  'village_id'=> '小区id 必传',
     *  'itemid'=> '解绑申请id 必传',
     *  'check_status'=> '审核状态  1 通过  2 拒绝 必传',
     *  'reason'=> '审核备注 选填',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/12 19:12
     * @return \json
     */
    public function unbindCheck() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(108,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        // 前置接口获取的解绑申请id
        $itemid =  $this->request->param('itemid','','intval');
        if (empty($itemid)) {
            return api_output(1001,[],'缺少对应解绑申请对象！');
        }
        // 审核状态  1 通过  2 拒绝 必传
        $check_status =  $this->request->param('check_status','','intval');
        if (empty($check_status)) {
            return api_output(1001,[],'缺少对应解绑审核结果！');
        }

        $reason = $this->request->param('reason','','trim');
        //小区信息
        $service_house_village = new HouseVillageService();
        $service_user = new HouseVillageUserService();
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_address,property_id');

        $where_unbind = [];
        $where_unbind[] = ['itemid','=',$itemid];

        $data['status']   = ($check_status==1)?3:2;
        $data['edittime'] = time();
        $data['reason'] = $reason ? $reason : '无';
        $save_id = $service_house_village->saveUnbindUser($where_unbind,$data);

        $where_unbind[] = ['village_id','=',$village_id];
        $info = $service_house_village->getUnbindUser($where_unbind);

        $service_user_info = new UserService();
        $whereUserArr=array();
        if($info['phone']){
            $whereUserArr[] = ['phone','=',$info['phone']];
            $whereUserArr[] = ['status', '=',1];
        }else if($info['uid']>0){
            $whereUserArr[]=['uid','=',$info['uid']];
        }
        $now_user = $service_user_info->getUserOne($whereUserArr, '*');
        $templateNewsService = new TemplateNewsService();
        if (2==$check_status && $save_id) { // 审核不通过
            //发送模板消息
            if ($now_user['openid']) {
                $href = cfg('site_url').'/wap.php?g=Wap&c=House&a=my_village_list';
                if ($info['type'] == 0 || $info['type'] == 3) {
                    $datamsg = [
                        'tempKey' => 'OPENTM405462911',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' =>'您在【'.$now_village['village_name'].'】的业主解绑申请审核未通过。原因：'.$data['reason'].'\n',
                            'keyword1'  => '业主解绑申请',
                            'keyword2'  => '已发送',
                            'keyword3'  => date('Y-m-d H:i:s',time()),
                            'keyword4' => $now_user['nickname'],
                            'remark'    => '\n请点击查看详细信息！'
                        ]
                    ];

                }else{
                    $temp_name = cfg('open_house_user_name')?'亲友':'家属';
                    $datamsg = [
                        'tempKey' => 'OPENTM405462911',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' =>'您在【'.$now_village['village_name'].'】的'.$temp_name.'解绑申请审核未通过。原因：'.$data['reason'].'\n',
                            'keyword1'  => $temp_name.'解绑申请',
                            'keyword2'  => '已发送',
                            'keyword3'  => date('Y-m-d H:i:s',time()),
                            'keyword4' => $now_user['nickname'],
                            'remark'    => '\n请点击查看详细信息！'
                        ]
                    ];
                }
                //调用微信模板消息
                $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,$now_village['property_id'],1);
            }
            return api_output(0,['itemid' => $itemid],'审核操作成功！');
        } elseif ($save_id) {
            // 审核通过 删除绑定
            //如果是业主 先清空房间绑定信息 vacancy 在删除绑定信息/亲属/租客 bind
            if($info['type']==0 || $info['type']==3){
                // 发送模板消息
                if ($now_user['openid']) {

                    $href = cfg('site_url').'/wap.php?g=Wap&c=House&a=my_village_list';
                    $datamsg = [
                        'tempKey' => 'OPENTM405462911',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' =>'您在【'.$now_village['village_name'].'】的业主解绑申请审核已通过\n',
                            'keyword1'  => '业主解绑申请',
                            'keyword2'  => '已发送',
                            'keyword3'  => date('Y-m-d H:i:s',time()),
                            'keyword4' => $now_user['nickname'],
                            'remark'    => '\n请点击查看详细信息！'
                        ]
                    ];
                    //调用微信模板消息
                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,$now_village['property_id'],1);
                }
                $room['status'] = 1;
                $room['uid'] = $room['type'] = $room['park_flag']= 0;
                $room['name'] = $room['phone'] = $room['memo'] = "";
                $room['housesize'] = 0.00;

                $room_where = [];
                $room_where[] = ['pigcms_id','=',$info['room_id']];
                $room_where[] = ['village_id','=',$info['village_id']];

                $reset_id = $service_user->saveHouseRoom($room_where,$room);

                //房间清除完成 删除绑定信息
                if($reset_id){
                    //先删除房主信息
                    $bind_info['uid']        = $info['uid'];
                    $bind_info['name']       = $info['name'];
                    $bind_info['phone']      = $info['phone'];
                    $bind_info['floor_id']   = $info['floor_id'];
                    $bind_info['vacancy_id'] = $info['room_id'];
                    $bind_info['village_id'] = $info['village_id'];
                    $bind_info['village_id'] = $info['village_id'];
                    $bind_info['type']       = $info['type'];
                    $bind_info['pigcms_id']  = $info['bind_id'];
                    $del_0_id = $service_user->delUserBindOne($bind_info);
                    #再删除亲属/租客信息
                    if($del_0_id){
                        $bind_info_1['parent_id'] = $info['bind_id'];
                        $bind_info_1['type']      = array('in' , '1,2');
                        $del_1_id = $service_user->delUserBindOne($bind_info_1);
                    }
                    return api_output(0,['itemid' => $itemid],'审核操作成功！');
                }else{
                    return api_output(1003,[],'房屋信息清除失败！');
                }
            }elseif($info['type']==1 || $info['type']==2){
                //如果是亲属/租客	删除绑定信息 bind
                //发送模板消息
                if ($now_user['openid']) {
                    $href = cfg('site_url').'/wap.php?g=Wap&c=House&a=my_village_list';
                    $temp_name = cfg('open_house_user_name')?'亲友':'家属';
                    $datamsg = [
                        'tempKey' => 'OPENTM405462911',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' =>'您在【'.$now_village['village_name'].'】的'.$temp_name.'解绑申请审核已通过\n',
                            'keyword1'  => $temp_name.'解绑申请',
                            'keyword2'  => '已发送',
                            'keyword3'  => date('Y-m-d H:i:s',time()),
                            'keyword4' => $now_user['nickname'],
                            'remark'    => '\n请点击查看详细信息！'
                        ]
                    ];
                    //调用微信模板消息
                    $templateNewsService->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr'],0,$now_village['property_id'],1);
                }

                $bind_info['uid']        = $info['uid'];
                $bind_info['name']       = $info['name'];
                $bind_info['phone']      = $info['phone'];
                $bind_info['floor_id']   = $info['floor_id'];
                $bind_info['vacancy_id'] = $info['room_id'];
                $bind_info['village_id'] = $info['village_id'];
                $bind_info['village_id'] = $info['village_id'];
                $bind_info['type']       = $info['type'];
                $bind_info['pigcms_id']  = $info['bind_id'];
                $del_0_id = $service_user->delUserBindOne($bind_info);
                if ($del_0_id) {
                    return api_output(0,['itemid' => $itemid],'审核操作成功！');
                } else {
                    return api_output(1003,[],'绑定信息清除失败！');
                }
            }

        } else {
            return api_output(1003,[],'审核失败，请稍后重试!');
        }
    }

    /**
     * 解绑审核详情页面
     * array (
     *  'village_id'=> '小区id 必传',
     *  'itemid'=> '解绑申请id 必传',
     *  'ticket' => '', 登录标识 必传
     *  'Device-Id' => '设备号'  建议前端统一请求接口处理
     *  'version_code' => '1001', 版本号  建议前端统一请求接口处理
     * )
     * @author: wanziyang
     * @date_time: 2020/5/13 16:18
     * @return \json
     */
    public function unbindCheckDetail() {
        // 获取登录信息
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if ($this->auth && !in_array(107,$this->auth)) {
            return api_output(1003,[],'暂无此权限！');
        }
        $village_id = $this->request->param('village_id','','intval');
        if (empty($village_id)) {
            $village_id = $this->login_info['village_id'];
            if (empty($village_id)) {
                return api_output(1001,[],'缺少对应小区！');
            }
        }
        $itemid = $this->request->param('itemid','','intval');
        if (empty($itemid)) {
            return api_output(1001,[],'缺少对应解绑申请对象！');
        }
        $service_user = new HouseVillageUserService();
        $where = [];
        $where[] = ['itemid','=',$itemid];
        $list = $service_user->getUnbindUser($where,$village_id);
        $out = [];
        $out['list'] = $list;
        $out['now_bind_info'] = $list;
        if(isset($list['dataList'])){
            $out['dataList'] = $list['dataList'];
        }
        $out['is_edit'] = false;
        return api_output(0,$out);
    }
}