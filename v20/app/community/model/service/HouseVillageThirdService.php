<?php


namespace app\community\model\service;


use app\community\model\db\Area;
use app\community\model\db\AreaCode;
use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageThird;
use app\community\model\db\HouseVillageThirdLog;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;

class HouseVillageThirdService
{

   public $clientId = '1d9108966a95b2593db4168072be56dd';//测试数据
    // public $clientId = '4b458a43e94202cac9b03666cbc993ba';
    public $orgCode='100103114101';
    public $organizationId='179';
    public $phone = '13380078888';
    public $username = 'admin';

    public function __construct()
    {

    }

    /**
     * 获取token
     * @author:zhubaodi
     * @date_time: 2021/11/24 16:44
     */
    public function getToken()
    {
        $data['clientId'] = $this->clientId;
        $db_house_village_third = new HouseVillageThird();
        $third_info = $db_house_village_third->getOne(['clientId' => $data['clientId']]);
        $token = '';
        $time = time();
        if (!empty($third_info) && !empty($third_info['token']) && $third_info['expires_in'] > $time) {
            $token = $third_info['token'];
        } else {
            $service_house_village_third_sdk = new HouseVillageThirdSDKService();
            $data1 = [];
            $data1['clientId'] = $data['clientId'];
            $res = $service_house_village_third_sdk->getToken($data1);
            if ($res[0] == 200) {
                $token_info = json_decode($res[1], true);
                if ($token_info['code'] == 0) {
                    $data_arr = [];
                    $token = $token_info['returnData']['access_token'];
                    $data_arr['token'] = $token_info['returnData']['access_token'];
                    $data_arr['expires_in'] = $token_info['returnData']['expires_in'] + time();
                    $data_arr['last_time'] = time();
                    if (empty($third_info)) {
                        $data_arr['village_id'] = 0;
                        $data_arr['clientId'] = $data['clientId'];
                        $data_arr['add_time'] = time();
                        $data_arr['orgCode'] = $this->orgCode;
                        $data_arr['organizationId'] = $this->organizationId;
                        $data_arr['organizationName'] = '三台县社区';
                        $data_org = [];
                        $data_org['token'] = $token_info['returnData']['access_token'];
                        $org_info = $service_house_village_third_sdk->getOrg($data_org);
                        if ($org_info[0] == 200) {
                            $org_info_arr = json_decode($org_info[1], true);
                            if ($org_info_arr['code'] == 0) {
                                $data_arr['orgCode'] = $token_info['returnData']['orgCode'];
                                $data_arr['organizationId'] = $token_info['returnData']['organizationId'];
                                $data_arr['organizationName'] = $token_info['returnData']['organizationName'];
                            }
                        }
                        $db_house_village_third->addOne($data_arr);
                    } else {
                        $db_house_village_third->saveOne(['clientId' => $data['clientId']], $data_arr);
                    }

                }

            }
        }
        return $token;
    }


    /**
     * 创建/编辑社区
     * @author:zhubaodi
     * @date_time: 2021/11/24 16:44
     */
    public function insertOrUpdateCommunity($village_id)
    {
       fdump_api(['创建/编辑社区'.__LINE__,$village_id],'insertOrUpdateCommunity',1);
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id);
        $data = [];
        $villageId = '';
        fdump_api(['创建/编辑社区'.__LINE__,$village_info->toArray()],'insertOrUpdateCommunity',1);
        if (!empty($village_info)) {
            $data['token'] = $this->getToken();
            fdump_api(['创建/编辑社区'.__LINE__,$data['token']],'insertOrUpdateCommunity',1);
            $db_house_village_third = new HouseVillageThird();
            $third_info = $db_house_village_third->getOne(['clientId' => $this->clientId]);
            fdump_api(['创建/编辑社区'.__LINE__,$third_info],'insertOrUpdateCommunity',1);
            $data['organizationId'] = $third_info['organizationId'];
            $data['communityAddress'] = $village_info['village_address'];
            $data['communityName'] = $village_info['village_name'];
            $db_area_code = new AreaCode();
            $code_info = $db_area_code->getOne(['area_id' => $village_info['area_id'], 'area_type' => 3]);
            fdump_api(['创建/编辑社区'.__LINE__,$code_info],'insertOrUpdateCommunity',1);
            $data['provinceId'] = $code_info['pid'];
            $data['cityId'] = $code_info['cid'];
            $data['regionId'] = $code_info['aid'];
            if (!empty($village_info['property_phone'])) {
                $phone = explode(' ', $village_info['property_phone']);
            }
            $data['communityManagerAccountBindForm'] = [
                'mobile' => $this->phone,
                'username' => $this->username . $village_id,
            ];
            $db_house_village_info = new HouseVillageInfo();
            $village_info_data = $db_house_village_info->getOne(['village_id' => $village_id]);
            fdump_api(['创建/编辑社区'.__LINE__,$village_info_data->toArray()],'insertOrUpdateCommunity',1);
            if (!empty($village_info_data['village_third_id'])) {
                $data['Id'] = $village_info_data['village_third_id'];
            }
            $data_log = [];

            if (empty($data['token'])) {
                $data_log['fair_reason'] = '未获取到token';
            }
            if (empty($data['organizationId'])) {
                $data_log['fair_reason'] = '未获取到组织id';
            }
            if (empty($data['communityAddress'])) {
                $data_log['fair_reason'] = '未获取到小区地址';
            }
            if (empty($data['communityName'])) {
                $data_log['fair_reason'] = '未获取到小区名称';
            }
            if (empty($data['provinceId'])) {
                $data_log['fair_reason'] = '未获取到省编码';
            }
            if (empty($data['cityId'])) {
                $data_log['fair_reason'] = '未获取到市编码';
            }
            if (empty($data['regionId'])) {
                $data_log['fair_reason'] = '未获取到区域编码';
            }
            if (empty($data['communityManagerAccountBindForm']['mobile'])) {
                $data_log['fair_reason'] = '未获取到管理员手机号';
            }
            if (empty($data['communityManagerAccountBindForm']['username'])) {
                $data_log['fair_reason'] = '未获取到管理员账号';
            }
            fdump_api(['创建/编辑社区'.__LINE__,$data_log],'insertOrUpdateCommunity',1);
            if (!empty($data_log)) {
                $data_log['village_id'] = $village_id;
                $data_log['business'] = 'village';
                $data_log['data_id'] = $village_id;
                $data_log['status'] = 2;
                $data_log['content'] = serialize($data);
                $data_log['admin_id'] = 1;
                $this->add_third_log($data_log);
                return $villageId;
            }
            $data_log['fair_reason'] = '';
            $service_house_village_third_sdk = new HouseVillageThirdSDKService();
            $res = $service_house_village_third_sdk->insertOrUpdateCommunity($data);
            fdump_api(['创建/编辑社区'.__LINE__,$res,$data],'insertOrUpdateCommunity',1);
         //    print_r($res);exit;
            if ($res[0] == 200) {
                $res_arr = json_decode($res[1], true);
                if ($res_arr['code'] == 0) {
                    $villageId = $res_arr['returnData']['id'];
                    fdump_api(['创建/编辑社区'.__LINE__,$villageId],'insertOrUpdateCommunity',1);
                    if (empty($village_info_data['village_third_id'])) {
                        $db_house_village_info->saveOne(['village_id' => $village_id], ['village_third_id' => $villageId]);
                    }
                    $data_log['village_id'] = $village_id;
                    $data_log['business'] = 'village';
                    $data_log['data_id'] = $village_id;
                    $data_log['status'] = 1;
                    $data_log['content'] = serialize($data);
                    $data_log['admin_id'] = 1;
                    fdump_api(['创建/编辑社区'.__LINE__,$data_log],'insertOrUpdateCommunity',1);
                    $this->add_third_log($data_log);
                } else {
                    $data_log['village_id'] = $village_id;
                    $data_log['business'] = 'village';
                    $data_log['data_id'] = $village_id;
                    $data_log['status'] = 2;
                    $data_log['content'] = serialize($data);
                    $data_log['admin_id'] = 1;
                    $data_log['fair_reason'] = $res_arr['message'];
                    fdump_api(['创建/编辑社区'.__LINE__,$data_log],'insertOrUpdateCommunity',1);
                    $this->add_third_log($data_log);
                }
            } else {
                $data_log['village_id'] = $village_id;
                $data_log['business'] = 'village';
                $data_log['data_id'] = $village_id;
                $data_log['status'] = 2;
                $data_log['content'] = serialize($data);
                $data_log['admin_id'] = 1;
                $data_log['fair_reason'] = '访问失败';
                fdump_api(['创建/编辑社区'.__LINE__,$data_log],'insertOrUpdateCommunity',1);
                $this->add_third_log($data_log);
            }
        }
        return $villageId;
    }

    /**
     * 添加或者编辑房屋
     * @author:zhubaodi
     * @date_time: 2021/11/25 11:36
     */
    public function addOrUpdateBuilding($vacancy_id)
    {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $vacancy_info = $db_house_village_user_vacancy->getOne(['pigcms_id' => $vacancy_id]);
        $data = [];
        $vacancyId = '';
        if (!empty($vacancy_info)) {
            $data['token'] = $this->getToken();
            $db_house_village_info = new HouseVillageInfo();
            $village_info = $db_house_village_info->getOne(['village_id' => $vacancy_info['village_id']]);
            $db_house_village_layer = new HouseVillageLayer();
            $layer_info = $db_house_village_layer->getOne(['id' => $vacancy_info['layer_id']]);
            $db_house_village_floor = new HouseVillageFloor();
            $floor_info = $db_house_village_floor->getOne(['floor_id' => $vacancy_info['floor_id']]);
            $db_house_village_single = new HouseVillageSingle();
            $single_info = $db_house_village_single->getOne(['id' => $vacancy_info['single_id']]);
            $data['communityId'] = $village_info['village_third_id'];
            $data['buildNum'] = $single_info['single_number'];
            $data['unitNum'] = $floor_info['floor_number'];
            $data['roomNum'] = $vacancy_info['room_number'];
            $data['houseFloor'] = $layer_info['layer_number'];
            $data['roomArea'] = $vacancy_info['housesize'];
            $data_log = [];
            if (!empty($vacancy_info['vacancy_third_id'])) {
                $data['id'] = $vacancy_info['vacancy_third_id'];
                if (empty($data['id'])) {
                    $data_log['fair_reason'] = '未获取到同步的房屋编号';
                }
            }
            if (empty($data['token'])) {
                $data_log['fair_reason'] = '未获取到token';
            }
            if (empty($data['communityId'])) {
                $data_log['fair_reason'] = '未获取到小区同步id';
            }
            if (empty($data['buildNum'])) {
                $data_log['fair_reason'] = '未获取到楼栋编号';
            }
            if (empty($data['unitNum'])) {
                $data_log['fair_reason'] = '未获取到单元编号';
            }
            if (empty($data['roomNum'])) {
                $data_log['fair_reason'] = '未获取到房间编号';
            }
            if (empty($data['houseFloor'])) {
                $data_log['fair_reason'] = '未获取到楼层编号';
            }
            if (empty($data['roomArea'])) {
                $data_log['fair_reason'] = '未获取到房屋面积';
            }
            if (!empty($data_log)) {
                $data_log['village_id'] = $vacancy_info['village_id'];
                $data_log['business'] = 'house';
                $data_log['data_id'] = $vacancy_info['village_id'];
                $data_log['status'] = 2;
                $data_log['content'] = serialize($data);
                $data_log['admin_id'] = 1;
                $this->add_third_log($data_log);
                return $vacancyId;
            }
            $data_log['fair_reason'] = '';
            $service_house_village_third_sdk = new HouseVillageThirdSDKService();
            $res = $service_house_village_third_sdk->addOrUpdateBuilding($data);
            fdump_api(['创建/编辑社区'.__LINE__,$res,$data],'addOrUpdateBuilding',1);
            $data_log['village_id'] = $vacancy_info['village_id'];
            $data_log['business'] = 'house';
            $data_log['data_id'] = $vacancy_info['village_id'];
            $data_log['content'] = serialize($data);
            $data_log['admin_id'] = 1;
            if ($res[0] == 200) {
                $res_arr = json_decode($res[1], true);
                if ($res_arr['code'] == 0) {
                    $vacancyId = isset($res_arr['returnData'])?$res_arr['returnData']:'';
                    if (empty($vacancy_info['vacancy_third_id'])) {
                        $db_house_village_user_vacancy->saveOne(['pigcms_id' => $vacancy_id], ['vacancy_third_id' => $vacancyId]);
                    }
                    $data_log['status'] = 1;
                } else {
                    $data_log['status'] = 2;
                    $data_log['fair_reason'] = $res_arr['message'];
                }
            } else {
                $data_log['status'] = 2;
                $data_log['fair_reason'] = '访问失败';
            }
        } else {

            $data_log['status'] = 2;
            $data_log['fair_reason'] = '未查询到数据';

        }
        $this->add_third_log($data_log);
        return $vacancyId;
    }


    /**
     * 添加住户
     * @author:zhubaodi
     * @date_time: 2021/11/25 11:36
     */
    public function addPersonInfo($bind_id)
    {
        $db_house_village_user_bind = new HouseVillageUserBind();
	    $db_house_face_img = new HouseFaceImg();
        $bind_info = $db_house_village_user_bind->getOne(['pigcms_id' => $bind_id]);
        $data = [];
        $vacancyId = '';
        if (!empty($bind_info)) {
            $data['token'] = $this->getToken();
            $db_user = new User();
            $user_info = $db_user->getOne(['uid' => $bind_info['uid']]);
            $db_house_village_user_vacancy = new HouseVillageUserVacancy();
            $vacancy_info = $db_house_village_user_vacancy->getOne(['pigcms_id' => $bind_info['vacancy_id']]);
            $data['identityCard'] = $bind_info['id_card'];
            $data['personName'] = $bind_info['name'];
            $data['sex'] = $user_info['sex'] == 2 ? 0 : 1;
            $data_log = [];
            $img_info=$db_house_face_img->getOne(['uid'=>$bind_info['uid']]);
            if (!empty($img_info)&&!empty($img_info['img_url'])){
                $data['imgBase64'] =$img_info['img_url'];
            }else{
                $data['imgBase64'] = base64_encode(file_get_contents(cfg('site_url') . '/static/images/tx.png'));
            }
            $data['mobile'] = $bind_info['phone'];
            $data['buildingId'] = $vacancy_info['vacancy_third_id'];
            if (empty($data['token'])) {
                $data_log['fair_reason'] = '未获取到token';
            }
            if (empty($data['identityCard'])) {
                $data_log['fair_reason'] = '未获取到身份证号';
            }
            if (empty($data['personName'])) {
                $data_log['fair_reason'] = '未获取到住户姓名';
            }
           /* if (empty($data['sex'])) {
                $data_log['fair_reason'] = '未获取到住户性别';
            }*/
            if (empty($data['mobile'])) {
                $data_log['fair_reason'] = '未获取到住户的联系方式';
            }
            if (empty($data['buildingId'])) {
                $data_log['fair_reason'] = '未获取到第三方房屋编号';
            }
            if (!empty($data_log)) {
                $data_log['village_id'] = $bind_info['village_id'];
                $data_log['business'] = 'user';
                $data_log['data_id'] = $bind_info['bind_number'];
                $data_log['status'] = 2;
                $data_log['content'] = serialize($data);
                $data_log['admin_id'] = 1;
                $this->add_third_log($data_log);
                return $vacancyId;
            }
            $data_log['fair_reason'] = '';
            $data_log['village_id'] = $bind_info['village_id'];
            $data_log['business'] = 'user';
            $data_log['data_id'] = $bind_info['bind_number'];
            $data_log['content'] = serialize($data);
            $data_log['admin_id'] = 1;
            if (empty($bind_info['bind_third_id'])) {
                $service_house_village_third_sdk = new HouseVillageThirdSDKService();
                $res = $service_house_village_third_sdk->addPersonInfo($data);
		fdump_api(['添加住户'.__LINE__,$res,$data],'addPersonInfo',1);
              //   print_r($res);exit;
                if ($res[0] == 200) {
                    $res_arr = json_decode($res[1], true);
                    if ($res_arr['code'] == 0) {
                        $vacancyId = $res_arr['returnData'];
                        $db_house_village_user_bind->saveOne(['pigcms_id' => $bind_id], ['bind_third_id' => $vacancyId['bindId'],'uid_third_id' => $vacancyId['personInfoId']]);
                        $data_log['status'] = 1;
                    } else {
                        $data_log['status'] = 2;
                        $data_log['fair_reason'] = $res_arr['message'];
                    }

                } else {
                    $data_log['status'] = 2;
                    $data_log['fair_reason'] = '访问失败';
                }
            } else {
                $data['bindId'] = $bind_info['bind_third_id'];
                $data['personInfoId']=$bind_info['uid_third_id'];
                $service_house_village_third_sdk = new HouseVillageThirdSDKService();
                $res = $service_house_village_third_sdk->updatePersonInfo($data);
                fdump_api(['添加住户'.__LINE__,$res,$data],'updatePersonInfo',1);
                if ($res[0] == 200) {
                    $res_arr = json_decode($res[1], true);
                    if ($res_arr['code'] == 0) {
                        $data_log['status'] = 1;
                        $vacancyId = $res_arr['returnData'];
                    } else {
                        $data_log['status'] = 2;
                        $data_log['fair_reason'] = $res_arr['message'];
                    }
                } else {
                    $data_log['status'] = 2;
                    $data_log['fair_reason'] = '访问失败';
                }
            }
        } else {
            $data_log['status'] = 2;
            $data_log['fair_reason'] = '未查询到数据';
        }
        $this->add_third_log($data_log);
        return $vacancyId;
    }


    /**
     * 添加同步日志
     * @author:zhubaodi
     * @date_time: 2021/11/26 16:58
     */
    public function add_third_log($data)
    {
        $data_arr = [];
        $data_arr['village_id'] = $data['village_id'];
        $data_arr['business'] = $data['business'];
        $data_arr['data_id'] = $data['data_id'];
        $data_arr['status'] = $data['status'];
        $data_arr['fair_reason'] = $data['fair_reason'];
        $data_arr['content'] = $data['content'];
        $data_arr['admin_id'] = $data['admin_id'];
        $data_arr['addtime'] = time();
        $db_house_village_third_log = new HouseVillageThirdLog();
        $id = $db_house_village_third_log->addOne($data_arr);
        return $id;
    }


    public function getAllProvinceCityCounty()
    {
        $data['token'] = $this->getToken();
        $service_house_village_third_sdk = new HouseVillageThirdSDKService();
        $db_area = new Area();
        $db_area_code = new AreaCode();
        $res = $service_house_village_third_sdk->getAllProvinceCityCounty($data);
        if ($res[0] == 200) {
            $res_arr = json_decode($res[1], true);
            if ($res_arr['code'] == 0) {
                $code_list = $res_arr['returnData'];
                if ($code_list) {
                    foreach ($code_list as $v) {
                        if (!empty($v['regionId']) && !empty($v['regionName'])) {
                            $where = [
                                ['area_name','like','%'.mb_substr($v['regionName'], 0, -1).'%'],
                                ['area_type','=',1]
                            ];
                            $area_code = $db_area->getOne($where);
                            $add_data = [];
                            if (!empty($area_code['area_id'])) {
                                $add_data['area_id'] = $area_code['area_id'];
                            }
                            $add_data['area_type'] = 1;
                            $add_data['pid'] = $v['regionId'];
                            $add_data['cid'] = '0';
                            $add_data['aid'] = '0';
                            $add_data['name'] = $v['regionName'];
                            $add_data['add_time'] = time();
                            $add_data['last_time'] = time();
                            $db_area_code->addOne($add_data);
                        }
                        if (isset($v['childList']) && !empty($v['childList'])) {
                            foreach ($v['childList'] as $v1) {
                                if (!empty($v1['regionId']) && !empty($v1['regionName'])) {
                                    $where = [];
                                    $where['area_name'] = mb_substr($v1['regionName'], 0, -1);
                                    $where['area_type'] = 2;
                                    $area_code = $db_area->getOne($where);
                                    $add_data = [];
                                    if (!empty($area_code['area_id'])) {
                                        $add_data['area_id'] = $area_code['area_id'];
                                    }
                                    $add_data['area_type'] = 2;
                                    $add_data['pid'] = $v['regionId'];
                                    $add_data['cid'] = $v1['regionId'];
                                    $add_data['aid'] = '0';
                                    $add_data['name'] = $v1['regionName'];
                                    $add_data['add_time'] = time();
                                    $add_data['last_time'] = time();
                                    $db_area_code->addOne($add_data);
                                }
                                if (isset($v1['childList']) && !empty($v1['childList'])) {
                                    foreach ($v1['childList'] as $v2) {
                                        if (!empty($v2['regionId']) && !empty($v2['regionName'])) {
                                            $where = [
                                                ['area_name','like','%'.mb_substr($v2['regionName'], 0, -1).'%'],
                                                ['area_type','=',3]
                                            ];
                                            $area_code = $db_area->getOne($where);
                                            $add_data = [];
                                            if (!empty($area_code['area_id'])) {
                                                $add_data['area_id'] = $area_code['area_id'];
                                            }
                                            $add_data['area_type'] = 3;
                                            $add_data['pid'] = $v['regionId'];
                                            $add_data['cid'] = $v1['regionId'];
                                            $add_data['aid'] = $v2['regionId'];;
                                            $add_data['name'] = $v2['regionName'];
                                            $add_data['add_time'] = time();
                                            $add_data['last_time'] = time();
                                            $db_area_code->addOne($add_data);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    public function test()
    {
        $db_area = new Area();
        $db_area_code = new AreaCode();
        $list=$db_area_code->getList(['area_id'=>0,'area_type'=>3]);
        if (!empty($list)){
            $list=$list->toArray();
            foreach ($list as $vv){
                $where = [
                    ['area_name','like','%'.mb_substr($vv['name'], 0, -1).'%'],
                    ['area_type','=',3]
                ];
                $area_code = $db_area->getOne($where);
                if (!empty($area_code)){
                    $add_data = [];
                    $add_data['area_id'] = $area_code['area_id'];
                    $db_area_code->saveOne(['id'=>$vv['id'],'area_type'=>3],$add_data);
                }
            }
        }

        return true;
    }
}