<?php
/**
 * A4人脸识别门禁机相关处理
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/9 19:26
 */
class Face_door_a4_serviceModel extends Model {

    // A4 类
    public $a4_face_class = '';

    // 错误码集合数组
    public $a4_rstCode = [
        1000 => '请求成功',
        1001 => '账号或密码错误',
        1002 => '账号 ID 不存在',
        1003 => '账号名称已存在',
        1004 => '小区已存在',
        1005 => '提交参数错误',
        1006 => '分区已存在',
        1007 => '楼栋已存在',
        1008 => '单元已存在',
        1009 => '房屋-户室已存在',
        1010 => '房屋-业主-住户已存在',
        1011 => '设备已被绑定',
        1012 => '业主-住户不存在',
        1013 => '访客已预约-不能重复预约',
        1014 => '权限不够-无法操作',
        1015 => '数据为空',
        1016 => '该层级下有分级不允许删除',
        1017 => '小区、分区、楼栋单元等层级不存在',
        1018 => '方法名不存在',
        1019 => '设备不存在',
        1020 => '第三方调用错误',
        1021 => '该单元下没有设备组',
        1022 => '设备未绑定',
        1023 => '设备已被其他人绑定',
        1024 => 'Account 悦生活号已存在',
        1025 => '没有关怀人群',
        1026 => '登陆密码为空',
        1027 => '访客二维码生成失败',
        1028 => '广告信息不存在',
        1029 => '社区已被其它账号绑定',
        1030 => '广告已存在',
        1031 => '签名或密钥参数验证失败',
        1032 => '卡号不可用(已被使用或不存在)',
        1033 => '卡号已存在',
        1034 => '常用电话已存在',
        1035 => '物业公告已存在',
        1036 => '物业公告不存在',
        1037 => '没有社区账号',
        1038 => '房屋住户已达上限',
        1039 => '房号格式不正确',
        1040 => '单元（分区）下有可视对讲设备',
        1041 => '图片已审核',
        1050 => '小区下楼栋编号或单元编号超过 99，无法使用围墙机',
        1051 => '楼栋编号最大为 99',
        1052 => '单元编号最大为 99',
    ];

    /**
     * Notes: 同步添加设备-需要先同步上级信息
     * @param $device_id
     * @param array $face_device_data
     * @param int $is_plan
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/11 17:27
     */
    public function faceDeviceSynchro($device_id, $face_device_data = [], $is_plan=1) {
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        $config_data = D('Config_data')->get_config_data();
        $a4_to_new_traits_job = isset($config_data['a4_to_new_traits_job']) ? intval($config_data['a4_to_new_traits_job']) : 0;
        if ($a4_to_new_traits_job) {
            $is_plan = 3;
        }
        
        // 判断对应上级是否进行过同步  如果没有一一进行同步
        $village_id = $face_device_data['village_id'];
        $d_face_bind_about_info = M('Face_bind_about_info');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $village_id,
            'device_type' => 4,
        ];
        $communityId = $d_face_bind_about_info->where($where_face_bind_about)->getField('third_id');
        if (!$communityId) {
            // 对应社区没有同步, 直接从同步社区开始
            if ($is_plan == 3) {
                $params = ['type' => 'add_a4_community', 'village_id' => $village_id, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4CommunityJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                $param = [
                    'type' => 'add_a4_community',
                    'village_id' =>$village_id,
                    'device_id' =>$device_id,
                ];
                $this->add_plan($param);
            } else {
                $this->addA4Community($village_id, [], $device_id);
            }
            return true;
        }
        $where_face_bind_about_parent = [
            'bind_type' => 1,
            'device_type' => 4,
            'bind_id' => $village_id,
        ];
        $zoneId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        if (!$zoneId) {
            // 对应分区没有同步, 直接从同步分区开始
            if ($is_plan == 3) {
                $params = ['type' => 'add_a4_zone', 'village_id' => $village_id, 'communityId' => $communityId, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4ZoneJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                $param = [
                    'type' => 'add_a4_zone',
                    'village_id' => $village_id,
                    'communityId' => $communityId,
                    'device_id' => $device_id,
                ];
                $this->add_plan($param);
            } else {
                $this->addA4Zone($village_id, $communityId, [], $device_id);
            }
            return true;
        }
        if ($is_plan == 3) {
            $params = ['type' => 'add_all_a4_property', 'village_id' => $village_id, 'communityId' => $communityId, 'zoneId' =>$zoneId, 'device_id' => $device_id];
            $invokeV20ServiceParams = ['params' => $params];
            $models = '\app\community\model\service\Device\A4DeviceService/addAllA4PropertyJobBox';
            $result = invoke_v20_service($models, $invokeV20ServiceParams);
        } elseif ($is_plan) {
            // 如果是计划任务后续同步执行为计划任务
            $param = [
                'type' => 'add_all_a4_property',
                'village_id' =>$village_id,
                'communityId' =>$communityId,
                'zoneId' =>$zoneId,
                'device_id' =>$device_id,
            ];
            $this->add_plan($param);
        } else {
            $this->addAllA4Property($village_id, $zoneId, $communityId);
        }

        if ($face_device_data && $face_device_data['floor_id'] != -1) {
            $db_house_village_floor = M('House_village_floor');
            $floor_info = $db_house_village_floor->where(['floor_id'=> $face_device_data['floor_id']])->field('floor_id,single_id')->find();
            $where_face_bind_about_parent = [
                'bind_type' => 2,
                'device_type' => 4,
                'bind_id' => $floor_info['single_id'],
            ];
            $buildingId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
            if (!$buildingId) {
                // 对应楼栋没有同步, 直接从同步楼栋开始
                if ($is_plan == 3) {
                    $params = ['type' => 'add_a4_building', 'single_id' => $floor_info['single_id'], 'communityId' => $communityId, 'zoneId' =>$zoneId, 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4BuildingJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    $param = [
                        'type' => 'add_a4_building', 'single_id' => $floor_info['single_id'], 'communityId' => $communityId,
                        'zoneId' => $zoneId, 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addA4Building($floor_info['single_id'], $zoneId, $communityId, [], $device_id);
                }
                return true;
            }
            $where_face_bind_about_parent = [
                'bind_type' => 3,
                'device_type' => 4,
                'bind_id' => $floor_info['floor_id'],
            ];
            $unitId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
            if (!$unitId) {
                // 对应单元没有同步, 直接从单元开始
                if ($is_plan == 3) {
                    $params = ['type' => 'add_a4_unit', 'floor_id' => $floor_info['floor_id'], 'communityId' => $communityId, 'buildingId' => $buildingId, 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4UnitJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    $param = [
                        'type' => 'add_a4_unit',
                        'floor_id' => $floor_info['floor_id'],
                        'communityId' => $communityId,
                        'buildingId' => $buildingId,
                        'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addA4Unit($floor_info['floor_id'],$buildingId, $communityId, [], $device_id);
                }
                return true;
            }
            // 如果存在设备 同步下设备
            if ($device_id) {
                if ($is_plan == 3) {
                    $params = ['type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => $zoneId, 'unitId' => $unitId];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4DeviceJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_a4_device',
                        'device_id' =>$device_id,
                        'zoneId' =>$zoneId,
                        'unitId' =>$unitId,
                    ];
                    $this->add_plan($param);
                } else {
                    // 非计划任务直接执行
                    $this->addA4Device($device_id, $zoneId, $unitId, $face_device_data);
                }
            }
        } else {
            if ($is_plan == 3) {
                $params = ['type' => 'add_village_a4_single', 'village_id' => $village_id, 'communityId' => $communityId, 'zoneId' => $zoneId, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addVillageA4SingleJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'add_village_a4_single',
                    'village_id' =>$village_id,
                    'communityId' =>$communityId,
                    'zoneId' =>$zoneId,
                    'device_id' =>$device_id,
                ];
                $this->add_plan($param);
            } else {
                $this->addVillageA4Single($village_id, $zoneId, $communityId, $device_id);
            }
            // 如果存在设备 同步下设备
            if ($device_id) {
                if ($is_plan == 3) {
                    $params = ['type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => $zoneId, 'unitId' => 0];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4DeviceJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_a4_device',
                        'device_id' =>$device_id,
                        'zoneId' =>$zoneId,
                        'unitId' =>0,
                    ];
                    $this->add_plan($param);
                } else {
                    // 非计划任务直接执行
                    $this->addA4Device($device_id, $zoneId, 0, $face_device_data);
                }
            }
            return true;
        }
        return true;
    }

    /**
     * Notes: 添加计划任务
     * @param $param
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/9/21 11:52
     */
    public function add_plan($param){
        if (!$param['type']) {
            // 需要传值type 否则直接返回
            return false;
        }
        $unique_id = $param['type'];
        // 处理下字段问题-如果存在拼接到唯一键值上
        if (isset($param['village_id'])) {
            $unique_id .='_'.$param['village_id'];
        }
        if (isset($param['communityId'])) {
            $unique_id .='_'.$param['communityId'];
        }
        if (isset($param['zoneId'])) {
            $unique_id .='_'.$param['zoneId'];
        }
        if (isset($param['single_id'])) {
            $unique_id .='_'.$param['single_id'];
        }
        if (isset($param['buildingId'])) {
            $unique_id .='_'.$param['buildingId'];
        }
        if (isset($param['vacancy_id'])) {
            $unique_id .='_'.$param['vacancy_id'];
        }
        if (isset($param['roomId'])) {
            $unique_id .='_'.$param['roomId'];
        }
        if (isset($param['floor_id'])) {
            $unique_id .='_'.$param['floor_id'];
        }
        if (isset($param['unitId'])) {
            $unique_id .='_'.$param['unitId'];
        }
        if (isset($param['bind_id'])) {
            $unique_id .='_'.$param['bind_id'];
        }
        if (isset($param['userId'])) {
            $unique_id .='_'.$param['userId'];
        }
        if (isset($param['device_id'])) {
            $unique_id .='_'.$param['device_id'];
        }

        $now_time = time();
        $arr= array();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = 0;
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_house_village_face_device';
        $arr['time_type'] = 1;
        $arr['unique_id'] = $unique_id;
		$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        $task_id = M('Process_sub_plan')->data($arr)->add();
        return array('task_id'=>$task_id);
    }

    //-->同步省市区信息
    /**
     * Notes:同步省
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2020/9/21 11:52
     */
    public function getProvinces() {
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'getProvinces',
            'accountId'=> intval($accountId),
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $provinces_data =  $this->a4_face_class->curlPost($url, $postData);


        if ($provinces_data['provinces']) {
            $d_area = M('Area');
            $provinces = $provinces_data['provinces'];
            $d_face_area = M('Face_area');
            foreach($provinces as $val) {
                $name = str_replace('省','',$val['name']);
                $name = str_replace('市','',$name);
                $name = str_replace('自治区','',$name);
                $name = str_replace('壮族自治区','',$name);
                $where = ['area_name'=> ['like','%'.$name.'%'],'area_type' => 1,'is_open' => array('neq', 2)];
                $area_id = $d_area->where($where)->order('is_open DESC')->getField('area_id');
                if ($area_id) {
                    $face_area_data = [
                        'area_type' => 1,
                        'pid' => $val['pid'],
                        'cid' => 0,
                        'aid' => 0,
                        'device_type' => 4
                    ];
                    $id = $d_face_area->where($face_area_data)->getField('id');
                    $face_area_data['area_id'] = $area_id;
                    $face_area_data['name'] = $val['name'];
                    if ($id) {
                        $face_area_data['last_time'] = time();
                        $d_face_area->where(['id'=> $id])->data($face_area_data)->save();
                    } else {
                        $face_area_data['add_time'] = time();
                        $d_face_area->data($face_area_data)->add();
                    }
                    $this->getCitys($val['pid'],$name);
                }
            }
        } else {
        }
        return $provinces_data;
    }

    /**
     * Notes:同步城市
     * @param $provinceId
     * @param $province_name
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2020/9/21 11:53
     */
    public function getCitys($provinceId, $province_name) {
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'getCitys',
            'provinceId'=> $provinceId,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $citys_data =  $this->a4_face_class->curlPost($url, $postData);

        if ($citys_data['city']) {
            $d_area = M('Area');
            $city = $citys_data['city'];
            $d_face_area = M('Face_area');
            foreach($city as $val) {
                $name = str_replace('市辖区',$province_name,$val['name']);
                $name = str_replace('省','',$name);
                $name = str_replace('市','',$name);
                $name = str_replace('特别行政区','',$name);
                $name = str_replace('特別行政區','',$name);
                $name = str_replace('蒙古自治州','',$name);
                $name = str_replace('回族自治州','',$name);
                $name = str_replace('柯尔克孜自治州','',$name);
                $name = str_replace('哈萨克自治州','',$name);
                $name = str_replace('土家族苗族自治州','',$name);
                $name = str_replace('藏族羌族自治州','',$name);
                $name = str_replace('傣族自治州','',$name);
                $name = str_replace('布依族苗族自治州','',$name);
                $name = str_replace('苗族侗族自治州','',$name);
                $name = str_replace('壮族苗族自治州','',$name);
                $name = str_replace('澳門','澳门',$name);
                $name = str_replace('朝鲜族自治州','',$name);
                $name = str_replace('哈尼族彝族自治州','',$name);
                $name = str_replace('傣族景颇族自治州','',$name);
                $name = str_replace('藏族自治州','',$name);
                $name = str_replace('彝族自治州','',$name);
                $name = str_replace('白族自治州','',$name);
                $name = str_replace('傈僳族自治州','',$name);
                $where = ['area_name'=> ['like','%'.$name.'%'],'area_type' => 2,'is_open' => array('neq', 2)];
                $area_id = $d_area->where($where)->order('is_open DESC')->getField('area_id');
                if ($area_id) {
                    $face_area_data = [
                        'area_type' => 2,
                        'pid' => $provinceId,
                        'cid' => $val['cid'],
                        'aid' => 0,
                        'device_type' => 4
                    ];
                    $id = $d_face_area->where($face_area_data)->getField('id');
                    $face_area_data['area_id'] = $area_id;
                    $face_area_data['name'] = $val['name'];
                    if ($id) {
                        $face_area_data['last_time'] = time();
                        $d_face_area->where(['id'=> $id])->data($face_area_data)->save();
                    } else {
                        $face_area_data['add_time'] = time();
                        $d_face_area->data($face_area_data)->add();
                    }
                    $this->getAreas($val['cid'], $provinceId);
                }
            }
        }
        return $citys_data;
    }

    /**
     * Notes:同步区域
     * @param $provinceId
     * @param $province_name
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2020/9/21 11:53
     */
    public function getAreas($cityId, $provinceId) {
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'getAreas',
            'cityId'=> $cityId,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $areas_data =  $this->a4_face_class->curlPost($url, $postData);

        if ($areas_data['area']) {
            $d_area = M('Area');
            $area = $areas_data['area'];
            $d_face_area = M('Face_area');
            foreach($area as $val) {
                $name = $val['name'];
                $where = ['area_name'=> ['like','%'.$name.'%'],'area_type' => 3,'is_open' => array('neq', 2)];
                $area_id = $d_area->where($where)->order('is_open DESC')->getField('area_id');
                if ($area_id) {
                    $face_area_data = [
                        'area_type' => 3,
                        'pid' => $provinceId,
                        'cid' => $cityId,
                        'aid' => $val['aid'],
                        'device_type' => 4
                    ];
                    $id = $d_face_area->where($face_area_data)->getField('id');
                    $face_area_data['area_id'] = $area_id;
                    $face_area_data['name'] = $val['name'];
                    if ($id) {
                        $face_area_data['last_time'] = time();
                        $d_face_area->where(['id'=> $id])->data($face_area_data)->save();
                    } else {
                        $face_area_data['add_time'] = time();
                        $d_face_area->data($face_area_data)->add();
                    }
                }
            }
        }
        return $areas_data;
    }
    //<----同步省市区信息

    //---> 同步小区消息
    /**
     * 同步小区到A4
     * @author: wanziyang
     * @date_time: 2020/6/10 10:53
     * @param int $village_id 小区id
     * @param array $village_info
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @return array|mixed
     */
    public function addA4Community($village_id, $village_info = [], $device_id = 0, $is_plan = 0)
    {
        if (!$village_id) {
            return false;
        }
        if (empty($village_info)) {
            $village_info = M('House_village')->where(['village_id'=> $village_id])->field('village_id,village_name,area_id,property_id,village_address')->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        $where_face_bind_about = [
            'bind_type' => 0,
            'device_type' => 4,
            'bind_id' => $village_id,
        ];
        $third_id = $d_face_bind_about_info->where($where_face_bind_about)->getField('third_id');
        // 暂时不进行编辑 直接调用下一级
        if ($third_id) {
            // 同步添加 分区
            if ($is_plan == 3) {
                $params = ['type' => 'add_a4_zone', 'village_id' => $village_id, 'communityId' => $third_id, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4ZoneJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = ['type' => 'add_a4_zone', 'village_id' => $village_id, 'communityId' => $third_id, 'device_id' => $device_id];
                $this->add_plan($param);
            } else {
                $this->addA4Zone($village_id,$third_id, $village_info, $device_id);
            }
            return true;
        }
        // 获取相关省市区信息
        $area_id = $village_info['area_id'];
        if (!$area_id) {
            // 如果存在上级物业去物业地址
            if ($village_info['property_id']>0) {
                $area_id = M('house_property')->where(['id' => $village_info['property_id']])->getField('area_id');
            }
            if (!$area_id) {
                // 如果均不存在地址 取对应主城市
                $area_info = M('Area')->where(['area_id' => C('config.now_city')])->field('area_id,area_pid,area_type')->find();
                if (3==$area_info['area_type']) {
                    $area_id = $area_info['area_id'];
                } elseif(2==$area_info['area_type']) {
                    $area_id = M('Area')->where(['area_pid' => $area_info['area_id'],'is_open'=> array('neq',2)])->order('area_sort')->getField('area_pid');
                } elseif(1==$area_info['area_type']) {
                    // 查询下一级
                    $area_pid = M('Area')->where(['area_pid' => $area_info['area_id'],'is_open'=> array('neq',2)])->order('area_sort')->getField('area_pid');
                    // 再次查询下一级
                    $area_id = M('Area')->where(['area_pid' => $area_pid,'is_open'=> array('neq',2)])->order('area_sort')->getField('area_pid');
                }
            }
        }
        $face_area = M('Face_area')->where(['area_id' => $area_id])->find();

        $pid = 11;
        $cid = 1101;
        $aid = 110108;
        $province = '北京市';
        $city = '市辖区';
        $area = '海淀区';

        if ($face_area && $face_area['pid'] && $face_area['cid'] && $face_area['aid']) {
            $pid = $face_area['pid'];
            $cid = $face_area['cid'];
            $aid = $face_area['aid'];
            $area = $face_area['name'];
            $city = M('Face_area')->where(['pid' => $pid,'cid'=>$cid,'aid'=> 0])->getField('name');
            $province = M('Face_area')->where(['pid' => $pid,'cid'=>0,'aid'=> 0])->getField('name');
        }
        $village_address = $village_info['village_address'];
        $name = $village_info['village_name'];

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        // type   小区类型，1:高端住宅; 2:精品住宅; 3:商业; 4:城中村; 5:公租房; 6;其他保障房
        // syscfg   1-12-5-11-7-8-9  对应 格式为：智能家居系统-可视对讲系统-小区视频监控系统-家庭视频监控系统-门禁系统-停车系统-人闸人脸识别系统
        // cattr  小区属性 bit0:是否有围墙机
        $arrContent = array(
            'apikey' => $apikey, 'timestamp' => $timestamp, 'method' => 'addCommunity', 'accountId' => intval($accountId),
            'name' => $name, 'province' => $province, 'pid' => intval($pid), 'city' => $city, 'cid' => intval($cid),
            'area' => $area, 'aid' => intval($aid), 'address' => $village_address, 'type' => 2, 'syscfg' => '12-7', 'cattr' => 1,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $community_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '添加小区信息', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'community_info' => $community_info], 'A4/add_a4_community_log', 1);
        if ($community_info['communityId']) {
            $face_bind_about_info = [
                'bind_type' => 0, 'bind_id' => $village_id, 'bind_name' => $name, 'third_id' => $community_info['communityId'],
                'third_info' => serialize($community_info), 'device_type' => 4, 'device_id' => 0,
            ];
            if ($third_id) {
                $face_bind_about_info['last_time'] = time();
                $add_id = $d_face_bind_about_info->where($where_face_bind_about)->data($face_bind_about_info)->save();
            } else {
                $face_bind_about_info['add_time'] = time();
                $add_id = $d_face_bind_about_info->data($face_bind_about_info)->add();
            }
            if (!$add_id) {
                fdump_api(['msg' => '添加小区成功记录失败', 'third_id' => $third_id, 'where_face_bind_about' => $where_face_bind_about, 'face_bind_about_info' => $face_bind_about_info, 'community_info' => $community_info], 'A4/add_a4_community_err_log', 1);
            } else {
                // 添加小区子账号
                $this->addA4CommunityAccount($village_id, $community_info['communityId'], $village_info);
                // 同步添加 分区
                if ($is_plan == 3) {
                    $params = ['type' => 'add_a4_zone', 'village_id' => $village_id, 'communityId' => $community_info['communityId'], 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4ZoneJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_a4_zone', 'village_id' => $village_id,
                        'communityId' => $community_info['communityId'], 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addA4Zone($village_id,$community_info['communityId'], $village_info);
                }
            }
        } else {
            fdump_api(['msg' => '添加小区失败', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'community_info' => $community_info], 'A4/add_a4_community_err_log', 1);
        }
        return $community_info;
    }

    /**
     * Notes: 添加小区子账号
     * @param $village_id
     * @param int $communityId
     * @param array $village_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/16 20:51
     */
    public function addA4CommunityAccount($village_id, $communityId=0, $village_info = []) {
        if (!$village_id) {
            return false;
        }
        if (empty($village_info)) {
            $village_info = M('House_village')->where(['village_id'=> $village_id])->field('village_id,village_name,area_id,property_id,village_address,property_phone,property_address')->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 小区没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $village_id,
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$communityId) {
            fdump_api(['msg' => '云端小区id不存在', 'line' => __LINE__, 'village_id' => $village_id, 'communityId' => $communityId, 'village_info' => $village_info], 'A4/add_a4_community_account_err_log', 1);
            return false;
        }
        // 查看是否已经存在关联值
        $where_face_bind_about = [
            'bind_type' => 0,
            'device_type' => 4,
            'bind_id' => $village_id,
        ];
        $third_info = $d_face_bind_about_info->where($where_face_bind_about)->find();
        if ($third_info['account_id']) {
            // 暂不做编辑
            return true;
        }
        // type=3,社区管理专用
        $type = 3;
        $alias = $village_info['village_name'] ? $village_info['village_name'] : '小区'.$communityId;
        $contactName = $village_info['village_name'] ? $village_info['village_name'] : '小区'.$communityId;

//        $property_phone_arr = explode(' ',$village_info['property_phone']);
//
//        $contactNumber = $property_phone_arr[0] ? $property_phone_arr[0] : '';
        $contactNumber = '12345678910';

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $account = 'house'.$village_id.$communityId;
        $password = $type.$village_id.$communityId;
        
//        $account = $this->a4_face_class->account;
//        $password = $this->a4_face_class->password;
        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        // type   小区类型，1:高端住宅; 2:精品住宅; 3:商业; 4:城中村; 5:公租房; 6;其他保障房
        $method = 'addAccount';
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'accountId'=> intval($accountId),
            'method'=> $method,
            'account'=> $account,
            'password'=> md5($password),
            'type'=> $type,
            'alias'=> $alias,
            'contactName'=> $contactName,
            'contactNumber'=> $contactNumber,
            'communityId'=> intval($communityId),
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $account_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '添加小区子账号', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'account_info' => $account_info], 'A4/add_a4_community_account_log', 1);
        if ($account_info['accountId']) {
            $data = [
                'account_id' => $account_info['accountId'],
                'account' => $account,
                'password' => $password,
                'account_alias' => $alias,
                'account_name' => $contactName,
                'account_phone' => $contactNumber,
            ];
            $d_face_bind_about_info->where($where_face_bind_about)->data($data)->save();
            return $account_info['accountId'];
        } else {
            fdump_api(['msg' => '添加小区子账号-失败', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'account_info' => $account_info], 'A4/add_a4_community_account_err_log', 1);
            return false;
        }
    }

    /**
     * 同步分区（小区）到A4  暂时没有分区概念 使用小区信息
     * @author: wanziyang
     * @date_time: 2020/6/10 13:53
     * @param int $village_id 小区id
     * @param int $communityId A4返回的小区id
     * @param array $village_info
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @return array|mixed
     */
    public function addA4Zone($village_id, $communityId = 0, $village_info = [], $device_id = 0, $is_plan = 0)
    {
        if (!$village_id) {
            return false;
        }
        if (empty($village_info)) {
            $village_info = M('House_village')->where(['village_id'=> $village_id])->field('village_id,village_name,area_id,property_id,village_address')->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 小区没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $village_id,
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$communityId) {
            if ($is_plan == 3) {
                $params = ['type' => 'add_a4_community', 'village_id' => $village_id, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4CommunityJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } else {
                $param = ['type' => 'add_a4_community', 'village_id' => $village_id, 'device_id' => $device_id];
                $this->add_plan($param);
            }
            fdump_api(['msg' => '小区未同步', 'line' => __LINE__, 'village_id' => $village_id, 'communityId' => $communityId, 'village_info' => $village_info], 'A4/add_a4_zone_err_log', 1);
            return false;
        }
        // 查看是否已经存在关联值
        $where_face_bind_about = [
            'bind_type' => 1,
            'device_type' => 4,
            'bind_id' => $village_id,
        ];
        $third_id = $d_face_bind_about_info->where($where_face_bind_about)->getField('third_id');
        if ($third_id) {
            // 当前不进行编辑 直接调用下一级
            if ($is_plan == 3) {
                $params = ['type' => 'add_village_a4_single', 'village_id' => $village_id, 'communityId' => $communityId, 'zoneId' => $third_id, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addVillageA4SingleJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'add_village_a4_single', 'village_id' => $village_id, 'communityId' => $communityId,
                    'zoneId' => $third_id, 'device_id' => $device_id,
                ];
                $this->add_plan($param);
            } else {
                $this->addVillageA4Single($village_id, $third_id, $communityId, $device_id);
            }
            if ($device_id) {
                $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
                if ($face_device_data['floor_id']==-1) {
                    if ($is_plan == 3) {
                        $params = ['type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => $third_id, 'unitId' => 0];
                        $invokeV20ServiceParams = ['params' => $params];
                        $models = '\app\community\model\service\Device\A4DeviceService/addA4DeviceJobBox';
                        $result = invoke_v20_service($models, $invokeV20ServiceParams);
                    } elseif ($is_plan) {
                        // 如果是计划任务后续同步执行为计划任务
                        $param = [
                            'type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => $third_id, 'unitId' => 0,
                        ];
                        $this->add_plan($param);
                    } else {
                        // 非计划任务直接执行
                        $this->addA4Device($device_id, $third_id, 0, $face_device_data);
                    }
                }
            }
            if ($is_plan == 3) {
                $params = ['type' => 'add_all_a4_property', 'village_id' => $village_id, 'communityId' => $communityId, 'zoneId' => $third_id, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addAllA4PropertyJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'add_all_a4_property', 'village_id' => $village_id, 'communityId' => $communityId,
                    'zoneId' => $third_id, 'device_id' => $device_id,
                ];
                $this->add_plan($param);
            } else {
                $this->addAllA4Property($village_id, $third_id, $communityId);
            }
            return true;
        }

        $name = $village_info['village_name'] . '(分区)';
        // type：小区类型，1:高端住宅; 2:精品住宅; 3:商业; 4:城中村; 5:公租房; 6;其他保障房；
        $type = 2;

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        // type   小区类型，1:高端住宅; 2:精品住宅; 3:商业; 4:城中村; 5:公租房; 6;其他保障房
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'addZone',
            'communityId'=> $communityId,
            'name'=> $name,
            'type'=> $type,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $zone_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '添加分区信息', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'zone_info' => $zone_info], 'A4/add_a4_zone_log', 1);
        if ($zone_info['zoneId']) {
            $face_bind_about_info = [
                'bind_type' => 1,
                'bind_id' => $village_id,
                'bind_name' => $name,
                'third_id' => $zone_info['zoneId'],
                'third_info' => serialize($zone_info),
                'device_type' => 4,
                'device_id' => 0,
            ];
            if ($third_id) {
                $face_bind_about_info['last_time'] = time();
                $add_id = $d_face_bind_about_info->where($where_face_bind_about)->data($face_bind_about_info)->save();
            } else {
                $face_bind_about_info['add_time'] = time();
                $add_id = $d_face_bind_about_info->data($face_bind_about_info)->add();
            }
            if (!$add_id) {
                fdump_api(['msg' => '分区同步记录失败', 'line' => __LINE__, 'third_id' => $third_id, 'where_face_bind_about' => $where_face_bind_about, 'face_bind_about_info' => $face_bind_about_info, 'zone_info' => $zone_info], 'A4/add_a4_zone_err_log', 1);
            } else {
                // 同步添加 该小区-分区-下所有楼栋进行同步处理
                if ($is_plan == 3) {
                    $params = ['type' => 'add_village_a4_single', 'village_id' => $village_id, 'communityId' => $communityId, 'zoneId' => $zone_info['zoneId'], 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addVillageA4SingleJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_village_a4_single', 'village_id' => $village_id, 'communityId' => $communityId,
                        'zoneId' => $zone_info['zoneId'], 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addVillageA4Single($village_id, $zone_info['zoneId'], $communityId);
                }
                if ($device_id) {
                    $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
                    if ($face_device_data['floor_id']==-1) {
                        if ($is_plan == 3) {
                            $params = ['type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => $zone_info['zoneId'], 'unitId' => 0];
                            $invokeV20ServiceParams = ['params' => $params];
                            $models = '\app\community\model\service\Device\A4DeviceService/addA4DeviceJobBox';
                            $result = invoke_v20_service($models, $invokeV20ServiceParams);
                        } elseif ($is_plan) {
                            // 如果是计划任务后续同步执行为计划任务
                            $param = ['type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => $zone_info['zoneId'], 'unitId' => 0];
                            $this->add_plan($param);
                        } else {
                            // 非计划任务直接执行
                            $this->addA4Device($device_id, $zone_info['zoneId'], 0, $face_device_data);
                        }
                    }
                }
                if ($is_plan == 3) {
                    $params = ['type' => 'add_all_a4_property', 'village_id' => $village_id, 'communityId' => $communityId, 'zoneId' => $zone_info['zoneId'], 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addAllA4PropertyJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_all_a4_property', 'village_id' => $village_id, 'communityId' => $communityId,
                        'zoneId' => $zone_info['zoneId'], 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addAllA4Property($village_id, $zone_info['zoneId'], $communityId);
                }
            }
        } else {
            fdump_api(['msg' => '分区同步失败', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'zone_info' => $zone_info], 'A4/add_a4_zone_err_log', 1);
        }
        return $zone_info;
    }

    /**
     * 该小区下所有楼栋进行同步处理
     * @author: wanziyang
     * @date_time: 2020/6/10 14:35
     * @param int $village_id 小区id
     * @param int $zoneId 硬件返回的小区分区id
     * @param int $communityId 硬件返回的小区id
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     */
    public function addVillageA4Single($village_id, $zoneId = 0, $communityId = 0, $device_id = 0, $is_plan = 0)
    {
        $single_list = M('house_village_single')->where(['village_id' => $village_id,'status' => 1])->select();
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$zoneId) {
            $where_face_bind_about_parent = [
                'bind_type' => 1,
                'device_type' => 4,
                'bind_id' => $village_id,
            ];
            $zoneId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        $paramLog = [
            'msg' => '该小区下所有楼栋进行同步处理', 'line' => __LINE__, 'village_id' => $village_id, 'zoneId' => $zoneId,
            'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan, 'single_list_count' => count($single_list),
        ];
        fdump_api($paramLog, 'A4/single_list_log', 1);
        if ($single_list) {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            if ($is_plan == 3) {
                $buildNum = 0;
                $buildMax = 8;
                foreach($single_list as $val) {
                    $buildNum = $buildNum + 1;
                    if ($buildNum > $buildMax) {
                        $params = ['type' => 'add_a4_building', 'single_id' => $val['id'], 'communityId' => $communityId, 'zoneId' =>$zoneId, 'device_id' => $device_id];
                        $invokeV20ServiceParams = ['params' => $params];
                        $models = '\app\community\model\service\Device\A4DeviceService/addA4BuildingJobBox';
                        $result = invoke_v20_service($models, $invokeV20ServiceParams);
                        continue;
                    }
                    $param = [
                        'type' => 'add_a4_building', 'single_id' => $val['id'], 'communityId' => $communityId,
                        'zoneId' => $zoneId, 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                }
            } elseif ($is_plan) {
                foreach($single_list as $val) {
                    $param = [
                        'type' => 'add_a4_building',
                        'single_id' => $val['id'],
                        'communityId' => $communityId,
                        'zoneId' => $zoneId,
                        'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                }
            } else {
                foreach($single_list as $val) {
                    try{
                        $this->addA4Building($val['id'], $zoneId, $communityId, $val, $device_id,$is_plan);
                    } catch (Exception $e) {
                        fdump(['添加楼栋报错=>'.__LINE__,$zoneId,$val,$e->getMessage()],'api/log/'.date('Ymd').'/A4/add_village_a4_single_err_log',true);
                    }
                }
            }
        }
        return $single_list;
    }

    /**
     * 同步单个楼栋
     * @author: wanziyang
     * @date_time: 2020/6/10 14:42
     * @param int $single_id 楼栋id
     * @param int $zoneId 硬件返回的分区id
     * @param int $communityId 硬件返回的小区id
     * @param array $single_info 楼栋信息
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @return array|mixed
     */
    public function addA4Building($single_id, $zoneId = 0, $communityId = 0, $single_info = [], $device_id = 0, $is_plan = 0)
    {
        if (empty($single_info)) {
            $single_info = M('House_village_single')->where(['id' => $single_id])->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$zoneId) {
            $where_face_bind_about_parent = [
                'bind_type' => 1,
                'device_type' => 4,
                'bind_id' => $single_info['village_id'],
            ];
            $zoneId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$zoneId) {
            fdump_api(['msg' => '分区同步id不存在', 'single_id' => $single_id, 'zoneId' => $zoneId, 'single_info' => $single_info], 'A4/add_a4_building_err_log', 1);
            if ($is_plan == 3) {
                $params = ['type' => 'add_a4_zone', 'village_id' => $single_info['village_id'], 'communityId' => $communityId, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4ZoneJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($single_info['village_id']) {
                $param = [
                    'type' => 'add_a4_zone', 'village_id' => $single_info['village_id'], 'communityId' => $communityId, 'device_id' => $device_id,
                ];
                $this->add_plan($param);
            }
            return false;
        }
        // 查看是否已经存在关联值
        $where_face_bind_about = [
            'bind_type' => 2,
            'device_type' => 4,
            'bind_id' => $single_id,
        ];
        $third_id = $d_face_bind_about_info->where($where_face_bind_about)->getField('third_id');
        if ($third_id) {
            // 当前不进行编辑 直接调用下一级
            if ($is_plan == 3) {
                $params = ['type' => 'add_single_a4_floor', 'single_id' => $single_id, 'communityId' => $communityId, 'buildingId' => $third_id, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addSingleA4FloorJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'add_single_a4_floor', 'single_id' => $single_id, 'communityId' => $communityId,
                    'buildingId' => $third_id, 'device_id' => $device_id,
                ];
                $this->add_plan($param);
            } else {
                $this->addSingleA4Floor($single_id, $third_id, $communityId, $device_id);
            }
            return true;
        }
        // 楼栋
        if ($single_info['single_number']) {
            $singleNum = $single_info['single_number'];
        } else {
            $singleName = $single_info['single_name'];
            // 把其中汉字去除
            $pattern = "/[\x{4E00}-\x{9FFF}]+/u";
            $singleNum = preg_replace($pattern,'',$singleName);
            if ($singleNum) {
                // 不能生成  需要获取实际的楼栋
                $singleNum = str_pad($singleNum,2,"0",STR_PAD_LEFT);
            } else {
                fdump(['楼栋没有数字编号，无法同步>>>'.__LINE__,$single_info,$singleName,$singleNum],'api/log/'.date('Ymd').'/A4/add_a4_num_err_log',true);
                return false;
            }
            $singleNum = substr($singleNum, -2,2);
            // 修改下楼栋id
            M('House_village_single')->where(['id' => $single_id])->setField('third_single_id', $singleNum);
        }

        $name = $single_info['single_name'] ? $single_info['single_name'] : '楼栋-'.$singleNum;

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'addBuilding',
            'zoneId'=> $zoneId,
            'name'=> $name,
            'code' => $singleNum,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $building_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '添加楼栋-结果', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'building_info' => $building_info], 'A4/add_a4_building_log', 1);
        if ($building_info['buildingId']) {
            $face_bind_about_info = [
                'bind_type' => 2,
                'bind_id' => $single_id,
                'bind_name' => $name,
                'third_id' => $building_info['buildingId'],
                'third_info' => serialize($building_info),
                'device_type' => 4,
                'device_id' => 0,
            ];
            if ($third_id) {
                $face_bind_about_info['last_time'] = time();
                $add_id = $d_face_bind_about_info->where($where_face_bind_about)->data($face_bind_about_info)->save();
            } else {
                $face_bind_about_info['add_time'] = time();
                $add_id = $d_face_bind_about_info->data($face_bind_about_info)->add();
            }
            if (!$add_id) {
                fdump_api(['msg' => '添加楼栋记录失败', 'third_id' => $third_id, 'where_face_bind_about' => $where_face_bind_about, 'face_bind_about_info' => $face_bind_about_info], 'A4/add_a4_building_err_log', 1);
            } else {
                // 同步添加 该小区-分区-楼栋-下所有单元进行同步处理
                if ($is_plan == 3) {
                    $params = ['type' => 'add_single_a4_floor', 'single_id' => $single_id, 'communityId' => $communityId, 'buildingId' => $building_info['buildingId'], 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addSingleA4FloorJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_single_a4_floor', 'single_id' => $single_id, 'communityId' => $communityId,
                        'buildingId' => $building_info['buildingId'], 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addSingleA4Floor($single_id, $building_info['buildingId'], $communityId);
                }
            }
        } else {
            fdump_api(['msg' => '添加楼栋失败', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'building_info' => $building_info], 'A4/add_a4_building_err_log', 1);
        }
        return $building_info;
    }

    /**
     * 该楼栋下所有单元进行同步处理
     * @author: wanziyang
     * @date_time: 2020/6/10 14:35
     * @param int $single_id 楼栋id
     * @param int $buildingId 硬件返回的楼栋id
     * @param int $communityId 硬件返回的小区id
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     */
    public function addSingleA4Floor($single_id, $buildingId = 0, $communityId = 0, $device_id = 0, $is_plan = 0)
    {
        $floor_list = M('House_village_floor')->where(['single_id' => $single_id,'status' => 1])->select();
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$buildingId) {
            $where_face_bind_about_parent = [
                'bind_type' => 2,
                'device_type' => 4,
                'bind_id' => $single_id,
            ];
            $buildingId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if ($floor_list) {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            $unitNum = 0;
            $unitMax = 8;
            foreach($floor_list as $val) {
                $unitNum = $unitNum + 1;
                if ($unitNum > $unitMax) {
                    $params = ['type' => 'add_a4_unit', 'floor_id' => $val['floor_id'], 'buildingId' => $buildingId, 'communityId' => $communityId, 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4UnitJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                    continue;
                }
                try{
                    $this->addA4Unit($val['floor_id'], $buildingId, $communityId, $val, $device_id, $is_plan);
                } catch (Exception $e) {
                    fdump_api(['msg' => '添加单元失败', 'err' => $e->getMessage(), 'floor_id' => $val['floor_id'], 'buildingId' => $buildingId, 'communityId' => $communityId, 'val' => $val, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_single_a4_floor_err_log', 1);
                }
            }
        }
    }

    /**
     * 同步楼栋下单元
     * @author: wanziyang
     * @date_time: 2020/6/10 15:06
     * @param int $floor_id 单元id
     * @param int $communityId 硬件返回的小区id
     * @param int $buildingId 硬件返回的楼栋id
     * @param array $floor_info 单元信息
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @return array|mixed
     */
    public function addA4Unit($floor_id, $buildingId = 0, $communityId = 0, $floor_info = [], $device_id = 0, $is_plan = 0)
    {
        if (empty($floor_info) || !isset($floor_info['single_id']) || !$floor_info['single_id']) {
            $floor_info = M('House_village_floor')->where(['floor_id' => $floor_id])->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$buildingId) {
            $where_face_bind_about_parent = [
                'bind_type' => 2,
                'device_type' => 4,
                'bind_id' => $floor_info['single_id'],
            ];
            $buildingId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$buildingId) {
            fdump_api(['msg' => '同步的楼栋id不存在', 'line' => __LINE__, 'floor_id' => $floor_id, 'buildingId' => $buildingId, 'floor_info' => $floor_info], 'A4/add_a4_unit_err_log', 1);
            if ($is_plan == 3) {
                $params = ['type' => 'add_a4_building', 'single_id' => $floor_info['single_id'], 'communityId' => $communityId, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4BuildingJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($floor_info['single_id']) {
                $param = [
                    'type' => 'add_a4_building',
                    'single_id' => $floor_info['single_id'],
                    'communityId' => $communityId,
                    'device_id' => $device_id,
                ];
                $this->add_plan($param);
            }
            return false;
        }
        // 查看是否已经存在关联值
        $where_face_bind_about = [
            'bind_type' => 3,
            'device_type' => 4,
            'bind_id' => $floor_id,
        ];
        $third_info = $d_face_bind_about_info->where($where_face_bind_about)->find();
        if ($third_info) {
            $third_msg = unserialize($third_info['third_info']);
        }
        $third_id = $third_info ? $third_info['third_id'] : '';
        if ($third_id && (!$third_msg || $third_msg['rstCode']!=1008)) {
            // 当前不进行编辑 直接进行下一级
            fdump_api(['msg' => '同步的楼栋已存在', 'line' => __LINE__, 'where_face_bind_about' => $where_face_bind_about, 'third_msg' => $third_msg], 'A4/add_a4_unit_log', 1);
            if ($is_plan == 3) {
                $params = ['type' => 'add_unit_a4_room', 'floor_id' => $floor_id, 'communityId' => $communityId, 'unitId' => $third_id, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addUnitA4RoomJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'add_unit_a4_room', 'floor_id' => $floor_id, 'communityId' => $communityId,
                    'unitId' => $third_id, 'device_id' => $device_id,
                ];
                $this->add_plan($param);
            } else {
                $this->addUnitA4Room($floor_id, $communityId, $third_id, $floor_info, $device_id);
            }
            // 如果存在设备 同步下设备
            if ($device_id) {
                $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
                if ($face_device_data['floor_id']==$floor_id) {
                    if ($is_plan == 3) {
                        $params = ['type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => 0, 'unitId' => $third_id];
                        $invokeV20ServiceParams = ['params' => $params];
                        $models = '\app\community\model\service\Device\A4DeviceService/addA4DeviceJobBox';
                        $result = invoke_v20_service($models, $invokeV20ServiceParams);
                    } elseif ($is_plan) {
                        // 如果是计划任务后续同步执行为计划任务
                        $param = [
                            'type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => 0, 'unitId' => $third_id,
                        ];
                        $this->add_plan($param);
                    } else {
                        // 非计划任务直接执行
                        $this->addA4Device($device_id, 0, $third_id, $face_device_data);
                    }
                }
            }
            return true;
        }
        // 单元
        if ($floor_info['floor_number']) {
            $floorNum = $floor_info['floor_number'];
        } else {
            $floorName = $floor_info['floor_name'];
            // 把其中汉字去除
            $pattern = "/[\x{4E00}-\x{9FFF}]+/u";
            $floorNum = preg_replace($pattern, '', $floorName);
            if ($floorNum) {
                $floorNum = str_pad($floorNum, 2, "0", STR_PAD_LEFT);
            } else {
                fdump_api(['msg' => '单元没有数字编号，无法同步', 'line' => __LINE__, 'floor_info' => $floor_info, 'floorName' => $floorName, 'floorNum' => $floorNum], 'A4/add_a4_unit_err_log', 1);
                return false;
            }
            $floorNum = substr($floorNum, -2, 2);
            // 修改下楼栋id
            M('House_village_floor')->where(['floor_id' => $floor_id])->setField('third_floor_id', $floorNum);
        }
        
        $name = $floor_info['floor_name'] ? $floor_info['floor_name'] : '单元-'.$floorNum;
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $arrContent = array(
            'apikey' => $apikey, 'timestamp' => $timestamp, 'method' => 'addUnit',
            'buildingId' => $buildingId, 'name' => $name, 'code' => $floorNum,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $unit_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '添加单元-结果', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'unit_info' => $unit_info], 'A4/add_a4_unit_log', 1);
        if ($unit_info['unitId']) {
            $face_bind_about_info = [
                'bind_type' => 3, 'bind_id' => $floor_id, 'bind_name' => $name, 'third_id' => $unit_info['unitId'],
                'third_info' => serialize($unit_info), 'device_type' => 4, 'device_id' => 0,
            ];
            if ($third_id) {
                $face_bind_about_info['last_time'] = time();
                $add_id = $d_face_bind_about_info->where($where_face_bind_about)->data($face_bind_about_info)->save();
            } else {
                $face_bind_about_info['add_time'] = time();
                $add_id = $d_face_bind_about_info->data($face_bind_about_info)->add();
            }
            if (!$add_id) {
                fdump_api(['msg' => '同步单元记录失败', 'line' => __LINE__, 'third_id' => $third_id, 'where_face_bind_about' => $where_face_bind_about, 'unit_info' => $unit_info], 'A4/add_a4_unit_err_log', 1);
            } else {
                // 单元下所有房屋信息同步
                if ($is_plan == 3) {
                    $params = ['type' => 'add_unit_a4_room', 'floor_id' => $floor_id, 'communityId' => $communityId, 'unitId' => $third_id, 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addUnitA4RoomJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_unit_a4_room', 'floor_id' => $floor_id, 'communityId' => $communityId,
                        'unitId' => $third_id, 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addUnitA4Room($floor_id, $communityId, $unit_info['unitId'], $floor_info, $device_id);
                }
                // 如果存在设备 同步下设备
                if ($device_id) {
                    $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
                    if ($face_device_data['floor_id'] == $floor_id) {
                        if ($is_plan == 3) {
                            $params = ['type' => 'add_a4_device', 'device_id' => $device_id, 'zoneId' => 0, 'unitId' => $unit_info['unitId']];
                            $invokeV20ServiceParams = ['params' => $params];
                            $models = '\app\community\model\service\Device\A4DeviceService/addA4DeviceJobBox';
                            $result = invoke_v20_service($models, $invokeV20ServiceParams);
                        } elseif ($is_plan) {
                            // 如果是计划任务后续同步执行为计划任务
                            $param = [
                                'type' => 'add_a4_device', 'device_id' => $device_id,
                                'zoneId' => 0, 'unitId' => $unit_info['unitId'],
                            ];
                            $this->add_plan($param);
                        } else {
                            // 非计划任务直接执行
                            $this->addA4Device($device_id, 0, $unit_info['unitId'], $face_device_data);
                        }
                    }
                }
            }
        } else {
            fdump_api(['msg' => '同步单元失败', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'unit_info' => $unit_info], 'A4/add_a4_unit_err_log', 1);
        }
        return $unit_info;
    }

    /**
     * 单元下所有房屋信息同步
     * @author: wanziyang
     * @date_time: 2020/6/10 15:52
     * @param int $floor_id 单元id
     * @param int $communityId 硬件返回的小区id
     * @param int $unitId 硬件返回的单元id
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @param $floor_info
     */
    public function addUnitA4Room($floor_id, $communityId = 0, $unitId = 0, $floor_info, $device_id = 0, $is_plan = 0)
    {
        if (empty($floor_info)) {
            $floor_info = M('House_village_floor')->where(['floor_id' => $floor_id])->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $floor_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$unitId) {
            $where_face_bind_about_parent = [
                'bind_type' => 3,
                'device_type' => 4,
                'bind_id' => $floor_id,
            ];
            $unitId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        $vacancy_list = M('House_village_user_vacancy')->where(['floor_id' => $floor_id,'status' => array('in', [1,3]),'is_del'=>0])->select();
        if ($vacancy_list) {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            foreach($vacancy_list as $val) {
                if ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_a4_room',
                        'bind_id' =>$val['pigcms_id'],
                        'communityId' =>$communityId,
                        'unitId' =>$unitId,
                    ];
                    $this->add_plan($param);
                } else {
                    try{
                        $this->addA4Room($val['pigcms_id'], $communityId, $unitId, $val, $device_id, $is_plan);
                    } catch (Exception $e) {
                        fdump(['同步房屋错误=>'.__LINE__,$communityId,$unitId,$val,$e->getMessage()],'api/log/'.date('Ymd').'/A4/add_unit_a4_room_err_log',true);
                    }
                }
            }
        }
    }

    /**
     * 同步房屋信息
     * @author: wanziyang
     * @date_time: 2020/6/10 15:46
     * @param int $vacancy_id 房屋id
     * @param int $communityId 硬件返回小区id
     * @param int $unitId 硬件返回单元id
     * @param array $vacancy_info 房屋信息
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @return array|mixed
     */
    public function addA4Room($vacancy_id, $communityId = 0, $unitId = 0, $vacancy_info = [], $device_id = 0, $is_plan = 0)
    {
        if (empty($vacancy_info)) {
            $vacancy_info = M('House_village_user_vacancy')->field('pigcms_id,village_id,floor_id,room,room_number,layer_id')->where(['pigcms_id' => $vacancy_id])->find();
        }
        $layer_info = M('House_village_layer')->where(['id' => $vacancy_info['layer_id']])->field('layer_name,layer_number')->find();
        $layer_name = $layer_info['layer_number']?$layer_info['layer_number']:$layer_info['layer_name'];
        if (!$layer_name && $vacancy_info['layer']) {
            $layer_name = $vacancy_info['layer'];
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $vacancy_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$unitId) {
            $where_face_bind_about_parent = [
                'bind_type' => 3,
                'device_type' => 4,
                'bind_id' => $vacancy_info['floor_id'],
            ];
            $unitId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$communityId || !$unitId) {
            fdump_api(['msg' => '小区或者单元同步id不存在', 'line' => __LINE__, 'is_plan' => $is_plan, 'vacancy_id' => $vacancy_id, 'communityId' => $communityId, 'unitId' => $unitId, 'vacancy_info' => $vacancy_info], 'A4/add_a4_room_err_log', 1);
            if ($is_plan == 3 && !$unitId && $communityId){
                $params = ['type' => 'add_a4_unit', 'floor_id' => $vacancy_info['floor_id'], 'communityId' => $communityId, 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4UnitJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan == 3 && !$communityId && $vacancy_info['village_id']){
                $params = ['type' => 'add_a4_community', 'village_id' => $vacancy_info['village_id'], 'device_id' => $device_id];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4CommunityJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif (!$unitId && $communityId) {
                $param = [
                    'type' => 'add_a4_unit',
                    'floor_id' => $vacancy_info['floor_id'],
                    'communityId' => $communityId,
                    'device_id' => $device_id,
                ];
                $this->add_plan($param);
            } elseif (!$communityId && $vacancy_info['village_id']) {
                $param = [
                    'type' => 'add_a4_community',
                    'village_id' =>$vacancy_info['village_id'],
                    'device_id' =>$device_id,
                ];
                $this->add_plan($param);
            }
            return false;
        }
        // 查看是否已经存在关联值
        $where_face_bind_about = [
            'bind_type' => 4,
            'device_type' => 4,
            'bind_id' => $vacancy_id,
        ];
        $third_id = $d_face_bind_about_info->where($where_face_bind_about)->getField('third_id');
        if ($third_id) {
            // 当前不进行编辑
            if ($is_plan) {
                if ($is_plan == 3) {
                    $params = ['type' => 'add_a4_room_holds', 'vacancy_id' => $vacancy_id, 'communityId' => $communityId, 'roomId' => $third_id, 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4RoomHoldsJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } else {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_a4_room_holds',
                        'vacancy_id' => $vacancy_id,
                        'communityId' => $communityId,
                        'roomId' => $third_id,
                        'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                }
            } elseif ($device_id) {
                $this->addA4RoomHolds($vacancy_id, $communityId, $third_id, $device_id);
            }
            return true;
        }
        
        if ($vacancy_info['room_number']) {
            $roomNum = $vacancy_info['room_number'];
            if (intval($roomNum)<100) {
                // 实际房屋编号小于三位数拼接楼层
                $roomNum = str_pad(intval($roomNum),2,"0",STR_PAD_LEFT);
                if (strlen($layer_name)==1) {
                    $layer_name = str_pad($layer_name,2,"0",STR_PAD_LEFT);
                }
                $roomNum = $layer_name . $roomNum;
            } elseif (strlen($roomNum)<3) {
                $roomNum = str_pad($roomNum,2,"0",STR_PAD_LEFT);
                if (strlen($layer_name)==1) {
                    $layer_name = str_pad($layer_name,2,"0",STR_PAD_LEFT);
                }
                $roomNum = $layer_name . $roomNum;
            } elseif(strlen($roomNum)<4) {
                $roomNum = str_pad($roomNum,4,"0",STR_PAD_LEFT);
            }
        } else {
            $roomNum = $vacancy_info['room'];
            // 把房屋中汉字去除
            $pattern = "/[\x{4E00}-\x{9FFF}]+/u";
            $roomNum = str_replace('-','',$roomNum);
            $roomNum = preg_replace($pattern,'',$roomNum);
            $layer_name = preg_replace($pattern,'',$layer_name);
            $roomNum = ''.$roomNum;
            if (strlen($roomNum)==1) {
                $roomNum = str_pad($roomNum,2,"0",STR_PAD_LEFT);
                if (strlen($layer_name)==1) {
                    $layer_name = str_pad($layer_name,2,"0",STR_PAD_LEFT);
                }
                $roomNum = $layer_name . $roomNum;
            } elseif (strlen($roomNum)==2) {
                if (strlen($layer_name)==1) {
                    $layer_name = str_pad($layer_name,2,"0",STR_PAD_LEFT);
                }
                $roomNum = $layer_name . $roomNum;
            } elseif (strlen($roomNum)==3) {
                $roomNum = str_pad($roomNum,4,"0",STR_PAD_LEFT);
            }
        }
        if (strlen($roomNum) > 4) {
            $roomNum = str_pad($roomNum, 4, "0", STR_PAD_LEFT);
            fdump_api(['msg' => '房屋位数大于4', 'line' => __LINE__, 'roomNum' => $roomNum], 'A4/add_a4_room_log', 1);
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'addRoom',
            'communityId'=> $communityId,
            'unitId'=> $unitId,
            'roomNum'=> $roomNum,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $room_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '同步房屋', 'line' => __LINE__, 'url' => $url, 'postData' => $postData, 'arrContent' => $arrContent, 'room_info' => $room_info], 'A4/add_a4_room_log', 1);
        if ($room_info['roomId']) {
            $face_bind_about_info = [
                'bind_type' => 4,
                'bind_id' => $vacancy_id,
                'bind_name' => $roomNum,
                'third_id' => $room_info['roomId'],
                'third_info' => serialize($room_info),
                'device_type' => 4,
                'device_id' => 0,
            ];
            if ($third_id) {
                $face_bind_about_info['last_time'] = time();
                $add_id = $d_face_bind_about_info->where($where_face_bind_about)->data($face_bind_about_info)->save();
            } else {
                $face_bind_about_info['add_time'] = time();
                $add_id = $d_face_bind_about_info->data($face_bind_about_info)->add();
            }
            if (!$add_id) {
                fdump_api(['msg' => '同步房屋记录失败', 'line' => __LINE__, 'third_id' => $third_id, 'where_face_bind_about' => $where_face_bind_about, 'face_bind_about_info' => $face_bind_about_info, 'room_info' => $room_info], 'A4/add_a4_room_err_log', 1);
            }
        } else {
            fdump_api(['msg' => '同步房屋失败', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'room_info' => $room_info], 'A4/add_a4_room_err_log', 1);
        }
        return $room_info;
    }

    /**
     * Notes:批量房屋人员信息
     * @author: wanzy
     * @date_time: 2020/6/22 11:48
     * @param int $vacancy_id 小区房屋id
     * @param int $communityId 硬件返回的小区id
     * @param int $roomId 硬件返回的房屋id
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @return array|bool|mixed
     */
    public function addA4RoomHolds($vacancy_id, $communityId=0, $roomId=0, $device_id=0, $is_plan=0) {
        $d_face_bind_about_info = M('Face_bind_about_info');
        if (!$roomId) {
            $where_face_bind_about_room = [
                'bind_type' => 4,
                'device_type' => 4,
                'bind_id' => $vacancy_id,
            ];
            $roomId = $d_face_bind_about_info->where($where_face_bind_about_room)->getField('third_id');
        }
        $vacancy_info = M('House_village_user_vacancy')->field('pigcms_id,village_id,floor_id,room')->where(['pigcms_id' => $vacancy_id])->find();
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $vacancy_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        $where = [
            'village_id' => $vacancy_info['village_id']
        ];
        $village_info = M('House_village')->where($where)->field('owe_property_open_door, owe_property_open_door_day')->find();
        // 整体同步的时候不对工作人员进行同步
        $where_room = [
            'vacancy_id' => $vacancy_id,
            'status' => 1,
            'type' => ['neq', 4]
        ];
        $bind_list = M('House_village_user_bind')->where($where_room)->select();
        fdump_api(['msg' => '整体同步对象', 'where_room' => $where_room, 'bind_list' => $bind_list], 'A4/add_a4_room_holds_log', 1);

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $apikey = $this->a4_face_class->apikey;
        $accountId = $this->a4_face_class->accountId;
        $timestamp = time();
        $members = [];
        if ($bind_list) {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            foreach ($bind_list as $bind_info) {
                // 业主姓名
                $userName = trim($bind_info['name']) ? trim($bind_info['name']) : '住户_'.$bind_info['pigcms_id'];
                // 性别
                $gender = 0;
                // 手机号
                $mobilePhone = trim($bind_info['phone']);
                // 身份证
                $Idnumber = trim($bind_info['id_card']) ? trim($bind_info['id_card']) : '';
                if (!$Idnumber) {
                    $data_set = [
                        'face_img_status' => 2,
                        'face_img_reason' => '请先填写对应用户身份证件号码',
                    ];
                    D('House_village_user_bind')->where(['pigcms_id' => $bind_info['pigcms_id']])->data($data_set)->save();
                    fdump_api(['msg' => '请先填写对应用户身份证件号码', 'pigcms_id' => $bind_info['pigcms_id'], 'communityId' => $communityId, 'roomId' => $roomId, 'bind_info' => $bind_info], 'A4/add_a4_room_holds_err_log', 1);
                    return false;
                } else if (strpos($Idnumber, '*') !== false) {
                    $data_set = [
                        'face_img_status' => 2,
                        'face_img_reason' => '身份证不能使用脱敏信息',
                    ];
                    D('House_village_user_bind')->where(['pigcms_id' => $bind_info['pigcms_id']])->data($data_set)->save();
                    fdump_api(['msg' => '身份证不能使用脱敏信息', 'pigcms_id' => $bind_info['pigcms_id'], 'communityId' => $communityId, 'roomId' => $roomId, 'bind_info' => $bind_info], 'A4/add_a4_room_holds_err_log', 1);
                    return false;
                }
                // type：住户类型：1 房主，2 房主附属（无手机，例：儿童），3 亲属，4 租客，5 其它；
                if (0==$bind_info['type'] || 3==$bind_info['type']) {
                    $type = 1;
                } elseif(1==$bind_info['type']) {
                    $type = 3;
                } elseif(2==$bind_info['type']) {
                    $type = 4;
                } else {
                    $type = 5;
                }
                $userTimes =  D('House_village_user_bind')->getUserEndTime($bind_info['pigcms_id']);
                if (isset($userTimes['propertyEndTime'])) {
                    $residenceTime = date('Y-m-d', $userTimes['propertyEndTime']);
                } else {
                    // 没有数据时候 暂时定个时间长点的日期
                    $residenceTime = '2058-12-26';
                }
                if ($bind_info['ic_card']) {
                    $cardNumber = $bind_info['ic_card'];
                } else {
                    $cardNumber = '' . 90000000000 + $bind_info['pigcms_id'];
                }
                $this->addA4Card($cardNumber);
                // cardType：0 普通卡，1 巡更卡，2 保洁卡；下同；
                $cardType = 0;
                $method = 'addHousehold';
                // 卡有效期
                $cardTimelimit = '2038-12-26';
                $arrContent = array(
                    'bind_id' => $bind_info['pigcms_id'], 'apikey' => $apikey, 'timestamp' => $timestamp,
                    'method' => $method, 'accountId' => intval($accountId), 'communityId' => $communityId,
                    'roomId' => $roomId, 'userName' => $userName, 'gender' => $gender, 'mobilePhone' => $mobilePhone,
                    'Idnumber' => $Idnumber, 'type' => $type, 'residenceTime' => $residenceTime, 'cardNumber' => $cardNumber,
                    'cardType' => $cardType, 'cardTimelimit' => $cardTimelimit
                );
                $members[] = $arrContent;
                fdump_api(['msg' => '同步人员信息', 'bind_id' => $arrContent['bind_id'], 'arrContent' => $arrContent, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_room_holds_err_log', 1);
                try{
                    $this->addA4MemberHousehold($arrContent['bind_id'], $arrContent, $roomId, $communityId, $device_id, $is_plan);
                } catch (Exception $e) {
                    fdump_api(['msg' => '同步人员信息错误', 'err' => $e->getMessage(), 'arrContent' => $arrContent, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_room_holds_err_log', 1);
                }
            }
        }
        return $members;
    }

    /**
     * Notes: 多个用户同步
     * @param $device_id
     * @param $bind_arr_id
     * @author: wanzy
     * @date_time: 2020/9/21 14:18
     */
    public function a4_arr_person_msg($device_id, $bind_arr_id,$orderGroupId='') {
        foreach ($bind_arr_id as $val) {
            // 转为计划任务执行
            $param = [
                'type' => 'add_a4_member_household',
                'bind_id' =>$val,
                'communityId' =>0,
                'roomId' => 0,
                'device_id' =>$device_id,
            ];
            if ($orderGroupId) {
                $param['orderGroupId'] = $orderGroupId;
            }
            $this->add_plan($param);
//            $this->addA4MemberHousehold($val, [], 0, 0, $device_id, 1);
        }
        return true;
    }

    /**
     * Notes: 单个同步用户信息-含有成员信息，直接同步
     * @author: wanzy
     * @date_time: 2020/6/22 9:44
     * @param int $bind_id 用户小区绑定id
     * @param int $roomId 硬件返回房屋id
     * @param int $communityId 硬件返回小区id
     * @param int $device_id 智能硬件id
     * @param int $is_plan 是否为计划任务执行 0 不是  1是
     * @return array|bool|mixed
     */
    public function addA4MemberHousehold($bind_id, $member = [], $roomId = 0, $communityId = 0, $device_id = 0, $is_plan = 0)
    {
        fdump_api(['msg' => '单个同步用户信息', 'line' => __LINE__, 'bind_id' => $bind_id, 'member' => $member, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_member_household_log', 1);
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        // 查看是否已经存在关联值
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (empty($bind_user)) {
            $method = 'addHousehold';
        } elseif ($bind_user['person_id']) {
            // 如果绑定过 编辑信息
            $method = 'editHousehold';
        } else {
            fdump_api(['msg' => '添加人员错误', 'line' => __LINE__, 'bind_id' => $bind_id, 'member' => $member, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
            return false;
        }
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;
        if (empty($member)) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
            if (in_array($bind_info['type'],[4,5,6])) {
                $this->addA4Property($bind_id, 0, $communityId, $bind_info, $is_plan);
                fdump_api(['msg' => '工作人员走同步工作人员', 'line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId, 'bind_info' => $bind_info, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
                return true;
            }
            if (!$communityId) {
                $where_face_bind_about_parent = [
                    'bind_type' => 0,
                    'device_type' => 4,
                    'bind_id' => $bind_info['village_id'],
                ];
                $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
            }
            if (!$roomId) {
                $where_face_bind_about_room = [
                    'bind_type' => 4,
                    'device_type' => 4,
                    'bind_id' => $bind_info['vacancy_id'],
                ];
                $roomId = $d_face_bind_about_info->where($where_face_bind_about_room)->getField('third_id');
            }
            if (!$communityId || !$roomId) {
                fdump_api(['msg' => '参数缺失', 'line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId, 'roomId' => $roomId, 'bind_info' => $bind_info, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
                if ($is_plan == 3 && !$roomId && $communityId) {
                    $params = ['type' => 'add_a4_room', 'bind_id' => $bind_info['vacancy_id'], 'communityId' => $communityId];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4RoomJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan == 3 && !$communityId && $bind_info['village_id']) {
                    $params = ['type' => 'add_a4_community', 'village_id' => $bind_info['village_id'], 'device_id' => $device_id];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4CommunityJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif (!$roomId && $communityId) {
                    $param = [
                        'type' => 'add_a4_room',
                        'bind_id' =>$bind_info['vacancy_id'],
                        'communityId' =>$communityId,
                    ];
                    $this->add_plan($param);
                } elseif (!$communityId && $bind_info['village_id']) {
                    $param = [
                        'type' => 'add_a4_community',
                        'village_id' =>$bind_info['village_id'],
                        'device_id' =>$device_id,
                    ];
                    $this->add_plan($param);
                }
                return false;
            }
            // 业主姓名
            $userName = trim($bind_info['name']) ? trim($bind_info['name']) : '住户_'.$bind_info['pigcms_id'];
            // 性别
            $gender = 0;
            // 手机号
            $mobilePhone = trim($bind_info['phone']);
            if (!$mobilePhone) {
                // 手机号不存在无法同步
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => '手机号不存在无法同步',
                ];
                D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                fdump_api(['msg' => '手机号不存在无法同步', 'line' => __LINE__, 'bind_id' => $bind_id, 'bind_user' => $bind_user, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
                return false;
            }
            // 手机号
            $Idnumber = trim($bind_info['id_card']) ? trim($bind_info['id_card']) : '';
            if (!$Idnumber) {
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => '请先填写对应用户身份证件号码',
                ];
                D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                fdump_api(['msg' => '请先填写对应用户身份证件号码', 'line' => __LINE__, 'bind_id' => $bind_id, 'bind_user' => $bind_user, 'bind_info' => $bind_info, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
                return false;
            } elseif (0 && !is_idcard($Idnumber)) {
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => '请先填写对应用户正确身份证件号码',
                ];
                D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                fdump_api(['msg' => '请先填写对应用户正确身份证件号码', 'line' => __LINE__, 'bind_id' => $bind_id, 'bind_user' => $bind_user, 'bind_info' => $bind_info, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
                return false;
            } else if (strpos($Idnumber, '*') !== false) {
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => '身份证不能使用脱敏信息',
                ];
                D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                fdump_api(['msg' => '身份证不能使用脱敏信息', 'line' => __LINE__, 'bind_id' => $bind_id, 'bind_user' => $bind_user, 'bind_info' => $bind_info, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
                return false;
            }
            // type：住户类型：1 房主，2 房主附属（无手机，例：儿童），3 亲属，4 租客，5 其它；
            if (0==$bind_info['type'] || 3==$bind_info['type']) {
                $type = 1;
            } elseif(1==$bind_info['type']) {
                $type = 3;
            } elseif(2==$bind_info['type']) {
                $type = 4;
            } else {
                $type = 5;
            }
            $residenceTime = '2038-12-26';
//        if ($bind_info['property_endtime']) {
//            $residenceTime = date('Y-m-d', $bind_info['property_endtime']);
//        }
            if ($bind_info['ic_card']) {
                $cardNumber = $bind_info['ic_card'];
            } else {
                $cardNumber = '' . 90000000000 + $bind_info['pigcms_id'];
            }
            $this->addA4Card($cardNumber);
            // cardType：0 普通卡，1 巡更卡，2 保洁卡；下同；
            $cardType = 0;
            // 卡有效期
            $cardTimelimit = '2038-12-26';

            $accountId = $this->a4_face_class->accountId;
            $timestamp = strval(time());
            if ($method=='addHousehold') {
                $arrContent = array(
                    'apikey'=> $apikey,
                    'timestamp'=>$timestamp,
                    'method'=>$method,
                    'accountId' => intval($accountId),
                    'communityId'=> $communityId,
                    'roomId'=> $roomId,
                    'userName'=> $userName,
                    'gender'=> $gender,
                    'mobilePhone'=> $mobilePhone,
                    'Idnumber'=> $Idnumber,
                    'type'=> $type,
                    'residenceTime'=> $residenceTime,
                    'cardNumber'=> $cardNumber,
                    'cardType' => $cardType,
                    'cardTimelimit' => $cardTimelimit
                );
            } elseif ($bind_user['person_id']) {
                $arrContent = array(
                    'apikey'=> $apikey,
                    'timestamp'=>$timestamp,
                    'method'=>$method,
                    'accountId' => intval($accountId),
                    'householdId'=> $bind_user['person_id'],
                    'userName'=> $userName,
                    'gender'=> $gender,
                    'mobilePhone'=> $mobilePhone,
                    'Idnumber'=> $Idnumber,
                    'type'=> $type,
                    'residenceTime'=> $residenceTime,
                    'cardNumber'=> $cardNumber,
                    'cardType' => $cardType,
                    'cardTimelimit' => $cardTimelimit
                );
            } else {
                fdump_api(['msg' => '其他错误', 'line' => __LINE__, 'bind_id' => $bind_id, 'bind_user' => $bind_user, 'bind_info' => $bind_info, 'roomId' => $roomId, 'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
                return false;
            }
        } else {
            unset($member['bind_id']);
            if ($method=='editHousehold' && isset($member['communityId'])) {
                $member['householdId'] = $bind_user['person_id'];
                $member['method'] = $method;
                unset($member['communityId']);
                unset($member['roomId']);
            }
            $arrContent = $member;
        }
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $hold_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '添加编辑用户信息', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'hold_info' => $hold_info, 'bind_info' => $bind_info, 'is_plan' => $is_plan], 'A4/add_a4_member_household_log', 1);
        if ($hold_info['householdId'] && $method=='addHousehold' && $hold_info['householdId']) {
            $face_user_bind_device_info = [
                'bind_type' => 0,
                'bind_id' => $bind_id,
                'person_id' => $hold_info['householdId'],
                'info' => serialize($hold_info),
                'device_type' => 4,
                'device_id' => 0,
                'group_id' => $communityId,
                'groupID' => $roomId,
            ];
            if ($bind_user) {
                $face_user_bind_device_info['last_time'] = time();
                $add_id = $d_face_user_bind_device->where($where_face_bind_about)->data($face_user_bind_device_info)->save();
            } else {
                $face_user_bind_device_info['add_time'] = time();
                $add_id = $d_face_user_bind_device->data($face_user_bind_device_info)->add();
            }
            if (!$add_id) {
                fdump_api(['msg' => '记录失败', 'line' => __LINE__, 'bind_user' => $bind_user, 'where_face_bind_about' => $where_face_bind_about, 'face_user_bind_device_info' => $face_user_bind_device_info, 'is_plan' => $is_plan], 'A4/add_a4_member_household_err_log', 1);
            } elseif ($is_plan) {
                // 同步查询下然后把对应userId记录下来
                $this->searchA4ActHouseholds($bind_id, $hold_info['householdId'], $communityId);
                // 如果是计划任务后续同步执行为计划任务
                if ($is_plan == 3) {
                    $params = ['type' => 'notify_a4_img_up', 'bind_id' =>$bind_id, 'communityId' =>$communityId, 'userId' => $hold_info['householdId']];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/notifyA4ImgUpJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                    fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'result' => $result, 'models' => $models, 'invokeV20ServiceParams' => $invokeV20ServiceParams], 'A4/add_a4_member_household_log', 1);
                } else {
                    $param = [
                        'type' => 'notify_a4_img_up',
                        'bind_id' =>$bind_id,
                        'communityId' =>$communityId,
                        'userId' => $hold_info['householdId'],
                    ];
                    $this->add_plan($param);
                    fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'param' => $param], 'A4/add_a4_member_household_log', 1);
                }
            } else {
                // 同步查询下然后把对应userId记录下来
                $this->searchA4ActHouseholds($bind_id, $hold_info['householdId'], $communityId);
                if (!$bind_info) {
                    $bind_info = [];
                }
                fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'bind_id' => $bind_id, 'householdId' => $hold_info['householdId'], 'communityId' => $communityId, 'bind_info' => $bind_info], 'A4/add_a4_member_household_log', 1);
                $this->notifyA4ImgUp($bind_id, $hold_info['householdId'], $communityId, $bind_info);
            }
        } elseif (1000==$hold_info['rstCode']) {
            if ($bind_user) {
                $face_user_bind_device_info['last_time'] = time();
                $add_id = $d_face_user_bind_device->where($where_face_bind_about)->data($face_user_bind_device_info)->save();
            } else {
                $face_user_bind_device_info['add_time'] = time();
                $add_id = $d_face_user_bind_device->data($face_user_bind_device_info)->add();
            }

            if (!$add_id) {
                fdump_api(['msg' => '记录失败', 'line' => __LINE__, 'bind_user' => $bind_user, 'where_face_bind_about' => $where_face_bind_about, 'face_user_bind_device_info' => $face_user_bind_device_info, 'hold_info' => $hold_info, 'arrContent' => $arrContent], 'A4/add_a4_member_household_err_log', 1);
            }
            // 同步查询下然后把对应userId记录下来
            $this->searchA4ActHouseholds($bind_id, $hold_info['householdId'], $communityId);
            if ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                if ($is_plan == 3) {
                    $params = ['type' => 'notify_a4_img_up', 'bind_id' =>$bind_id, 'communityId' =>$communityId, 'userId' => 0];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/notifyA4ImgUpJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                    fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'result' => $result, 'models' => $models, 'invokeV20ServiceParams' => $invokeV20ServiceParams], 'A4/add_a4_member_household_log', 1);
                } else {
                    $param = [
                        'type' => 'notify_a4_img_up',
                        'bind_id' =>$bind_id,
                        'communityId' =>$communityId,
                        'userId' => 0,
                    ];
                    $this->add_plan($param);
                    fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'param' => $param], 'A4/add_a4_member_household_log', 1);
                }
            } else {
                if (!$bind_info) {
                    $bind_info = [];
                }
                fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'bind_id' => $bind_id, 'householdId' => 0, 'communityId' => $communityId, 'bind_info' => $bind_info], 'A4/add_a4_member_household_log', 1);
                $this->notifyA4ImgUp($bind_id, 0, $communityId, $bind_info);
            }
        } elseif (1012==$hold_info['rstCode']){
            // 明确返回用户不存在  删除当前用户重新同步一次
            $where_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $bind_id,
                'device_type' => 4,
            ];
            // 以防出现问题 当前设备方不存在了 直接记录为类型-1
            $saveData = [
                'bind_type' => -1,
                'device_type'=> -4,
                'bind_id' => -$bind_id,
                'last_time' => time(),
            ];
            $save_id = $d_face_user_bind_device->where($where_face_bind_about)->save($saveData);
            if ($save_id) {
                // 如果是计划任务后续同步执行为计划任务
                if ($is_plan == 3) {
                    $params = [
                        'type' => 'add_a4_member_household', 'bind_id' => $bind_id, 'communityId' => $communityId,
                        'roomId' => $roomId, 'device_id' => $device_id
                    ];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4MemberHouseholdJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                    fdump_api(['msg' => '同步人员计划', 'line' => __LINE__, 'result' => $result, 'models' => $models, 'invokeV20ServiceParams' => $invokeV20ServiceParams], 'A4/add_a4_member_household_log', 1);
                } else {
                    $param = [
                        'type' => 'add_a4_member_household', 'bind_id' => $bind_id, 'communityId' => $communityId,
                        'roomId' => $roomId, 'device_id' => $device_id,
                    ];
                    $this->add_plan($param);
                }
            }
        } else {
            //失败记录日志
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => $hold_info['rstCodeMsg'],
            ];
            D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();

            if ($method=='editHousehold' && 1038!==$hold_info['rstCode']) {
                $this->searchA4ActHouseholds($bind_id, $bind_user['person_id'], $communityId);
                if (!$bind_info) {
                    $bind_info = [];
                }
                if ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    if ($is_plan == 3) {
                        $params = ['type' => 'notify_a4_img_up', 'bind_id' =>$bind_id, 'communityId' =>$communityId, 'userId' => 0];
                        $invokeV20ServiceParams = ['params' => $params];
                        $models = '\app\community\model\service\Device\A4DeviceService/notifyA4ImgUpJobBox';
                        $result = invoke_v20_service($models, $invokeV20ServiceParams);
                        fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'result' => $result, 'models' => $models, 'invokeV20ServiceParams' => $invokeV20ServiceParams], 'A4/add_a4_member_household_log', 1);
                    } else {
                        $param = ['type' => 'notify_a4_img_up', 'bind_id' => $bind_id, 'communityId' => $communityId, 'userId' => 0];
                        $this->add_plan($param);
                        fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'param' => $param], 'A4/add_a4_member_household_log', 1);
                    }
                } else {
                    if (!$bind_info) {
                        $bind_info = [];
                    }
                    fdump_api(['msg' => '同步人脸计划', 'line' => __LINE__, 'bind_id' => $bind_id, 'householdId' => 0, 'communityId' => $communityId, 'bind_info' => $bind_info], 'A4/add_a4_member_household_log', 1);
                    $this->notifyA4ImgUp($bind_id, 0, $communityId, $bind_info);
                }
            }
            fdump_api(['msg' => '其他1错误', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'hold_info' => $hold_info], 'A4/add_a4_member_household_err_log', 1);
        }
        return $hold_info;
    }
    /**
     * Notes: 单个同步用户信息
     * @author: wanzy
     * @date_time: 2020/6/22 9:44
     * @param int $bind_id 用户小区绑定id
     * @param int $roomId 硬件返回房屋id
     * @param int $communityId 硬件返回小区id
     * @param array $bind_info 用户小区绑定相关信息
     * @return array|bool|mixed
     */
    public function addA4Household($bind_id, $roomId=0, $communityId=0, $bind_info=[], $is_plan=0) {
        if (empty($bind_info) || !trim($bind_info['id_card'])) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$roomId) {
            $where_face_bind_about_room = [
                'bind_type' => 4,
                'device_type' => 4,
                'bind_id' => $bind_info['vacancy_id'],
            ];
            $roomId = $d_face_bind_about_info->where($where_face_bind_about_room)->getField('third_id');
        }
        if (!$communityId || !$roomId) {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($communityId,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($roomId,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            return false;
        }
        // 查看是否已经存在关联值
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (empty($bind_user)) {
            $method = 'addHousehold';
        } elseif ($bind_user['person_id']) {
            // 如果绑定过 编辑信息
            $method = 'editHousehold';
        } else {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($communityId,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($roomId,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($bind_user,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            return false;
        }

        // 业主姓名
        $userName = trim($bind_info['name']) ? trim($bind_info['name']) : '住户_'.$bind_info['pigcms_id'];
        // 性别
        $gender = 0;
        // 手机号
        $mobilePhone = trim($bind_info['phone']);
        // 手机号
        $Idnumber = trim($bind_info['id_card']) ? trim($bind_info['id_card']) : '';
        if (!$Idnumber) {
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => '请先填写对应用户身份证件号码',
            ];
            D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
            fdump_api([$bind_id, $communityId, $roomId, $bind_user, $bind_info], 'A4/add_a4_property_err_log', true);
            return false;
        } else if (strpos($Idnumber, '*') !== false) {
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => '身份证不能使用脱敏信息',
            ];
            D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
            fdump_api([$bind_id, $communityId, $roomId, $bind_user, $bind_info], 'A4/add_a4_property_err_log', true);
            return false;
        }
        // type：住户类型：1 房主，2 房主附属（无手机，例：儿童），3 亲属，4 租客，5 其它；
        if (0==$bind_info['type'] || 3==$bind_info['type']) {
            $type = 1;
        } elseif(1==$bind_info['type']) {
            $type = 3;
        } elseif(2==$bind_info['type']) {
            $type = 4;
        } else {
            $type = 5;
        }
        $residenceTime = '2038-12-26';
//        if ($bind_info['property_endtime']) {
//            $residenceTime = date('Y-m-d', $bind_info['property_endtime']);
//        }
        if ($bind_info['ic_card']) {
            $cardNumber = $bind_info['ic_card'];
        } else {
            $cardNumber = '' . 90000000000 + $bind_info['pigcms_id'];
        }
        $this->addA4Card($cardNumber);
        // cardType：0 普通卡，1 巡更卡，2 保洁卡；下同；
        $cardType = 0;
        // 卡有效期
        $cardTimelimit = '2038-12-26';

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());

        if ($method=='addHousehold') {
            $arrContent = array(
                'apikey'=> $apikey,
                'timestamp'=>$timestamp,
                'method'=>'addHousehold',
                'accountId' => intval($accountId),
                'communityId'=> $communityId,
                'roomId'=> $roomId,
                'userName'=> $userName,
                'gender'=> $gender,
                'mobilePhone'=> $mobilePhone,
                'Idnumber'=> $Idnumber,
                'type'=> $type,
                'residenceTime'=> $residenceTime,
                'cardNumber'=> $cardNumber,
                'cardType' => $cardType,
                'cardTimelimit' => $cardTimelimit
            );
        } elseif ($bind_user['person_id']) {
            $arrContent = array(
                'apikey'=> $apikey,
                'timestamp'=>$timestamp,
                'method'=>$method,
                'accountId' => intval($accountId),
                'householdId'=> $bind_user['person_id'],
                'userName'=> $userName,
                'gender'=> $gender,
                'mobilePhone'=> $mobilePhone,
                'Idnumber'=> $Idnumber,
                'type'=> $type,
                'residenceTime'=> $residenceTime,
                'cardNumber'=> $cardNumber,
                'cardType' => $cardType,
                'cardTimelimit' => $cardTimelimit
            );
        } else {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($communityId,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($roomId,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($bind_user,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            return false;
        }
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $hold_info =  $this->a4_face_class->curlPost($url, $postData);
        if ($hold_info['householdId']) {
            $face_user_bind_device_info = [
                'bind_type' => 0,
                'bind_id' => $bind_id,
                'person_id' => $hold_info['householdId'],
                'info' => serialize($hold_info),
                'device_type' => 4,
                'device_id' => 0,
                'group_id' => $communityId,
                'groupID' => $roomId,
            ];
            if ($bind_user) {
                $face_bind_about_info['last_time'] = time();
                $add_id = $d_face_user_bind_device->where($where_face_bind_about)->data($face_user_bind_device_info)->save();
            } else {
                $face_bind_about_info['add_time'] = time();
                $add_id = $d_face_user_bind_device->data($face_user_bind_device_info)->add();
            }
            if (!$add_id) {
                fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
                fdump($bind_user,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
                fdump($where_face_bind_about,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
                fdump($face_user_bind_device_info,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
                fdump($d_face_user_bind_device->getDbError(),'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
                fdump($hold_info,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            }
            // 同步查询下然后把对应userId记录下来
            $this->searchA4ActHouseholds($bind_id, $hold_info['householdId'], $communityId);
            if ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'notify_a4_img_up',
                    'bind_id' =>$bind_id,
                    'communityId' =>$communityId,
                    'userId' => 0,
                ];
                $this->add_plan($param);
            } else {
                if (!$bind_info) {
                    $bind_info = [];
                }
                $this->notifyA4ImgUp($bind_id, 0, $communityId, $bind_info);
            }
        } else {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($postData,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
            fdump($hold_info,'api/log/'.date('Ymd').'/A4/add_a4_house_hold_err_log',true);
        }
        return $hold_info;
    }

    /**
     * Notes: 添加卡号
     * @author: wanzy
     * @date_time: 2020/6/22 19:42
     * @param string $cardNumber 卡号  需要纯数字组合
     * @return bool
     */
    public function addA4Card($cardNumber) {
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;

        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'addCard',
            'accountId' => intval($accountId),
            'cardNumber'=> $cardNumber
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $card_num_info =  $this->a4_face_class->curlPost($url, $postData);

        if (1000 == $card_num_info['rstCode']) {
            fdump_api(['msg' => '添加卡片成功', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'card_num_info' => $card_num_info], 'A4/add_a4_card_log', 1);
            return true;
        } elseif (1033 == $card_num_info['rstCode']) {
            fdump_api(['msg' => '添加卡片成功（卡片已经存在）', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'card_num_info' => $card_num_info], 'A4/add_a4_card_log', 1);
            return true;
        } else {
            fdump_api(['msg' => '添加卡片失败', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'card_num_info' => $card_num_info], 'A4/add_a4_card_err_log', 1);
            return false;
        }
    }

    /**
     * Notes: 添加设备
     * @param $device_id
     * @param int $zoneId
     * @param int $unitId
     * @param array $face_device_data
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/10 10:36
     */
    public function addA4Device($device_id,$zoneId=0, $unitId=0, $face_device_data = []) {
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 查看是否已经存在关联值
        $where_face_bind_about = [
            'bind_type' => 0,
            'device_type' => 4,
            'bind_id' => $face_device_data['village_id'],
        ];
        $third_info = $d_face_bind_about_info->where($where_face_bind_about)->find();
        $accountId = $this->a4_face_class->accountId;
        fdump_api(['msg' => '添加设备-关联记录', 'line' => __LINE__, 'third_info' => $third_info, 'accountId' => $accountId], 'A4/add_a4_device_log', 1);
        if (!$third_info['account_id']) {
            // 现行同步创建小区子账号
            $accountId = $this->addA4CommunityAccount($face_device_data['village_id'], $third_info['third_id']);
            if (!$accountId) {
                $errLog = [
                    'msg' => '添加设备-账号id不存在', 'line' => __LINE__, 'device_id' => $device_id, 'zoneId' => $zoneId, 
                    'unitId' => $unitId, 'face_device_data' => $face_device_data, 'accountId' => $accountId,
                ];
                fdump_api($errLog, 'A4/add_a4_device_err_log', 1);
                return false;
            }
        } else {
            $accountId = $third_info['account_id'];
        }
        // 没有传值 自动获取
        if (!$zoneId) {
            $where_face_bind_about_parent = [
                'bind_type' => 1,
                'device_type' => 4,
                'bind_id' => $face_device_data['village_id'],
            ];
            $zoneId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$unitId && $face_device_data['floor_id']>0) {
            $where_face_bind_about_parent = [
                'bind_type' => 3,
                'device_type' => 4,
                'bind_id' => $face_device_data['floor_id'],
            ];
            $unitId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$zoneId && !$unitId) {
            $errLog = [
                'msg' => '添加设备-不存在分区和不存在单元', 'line' => __LINE__, 'device_id' => $device_id, 'zoneId' => $zoneId,
                'unitId' => $unitId, 'face_device_data' => $face_device_data, 'accountId' => $accountId,
            ];
            fdump_api($errLog, 'A4/add_a4_device_err_log', 1);
            return false;
        }


        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;
        // 层级 ID
        // parentId 表示层级 ID，根据 systemType 的意义而改变，分别可为 communityId、roomId、unitId;
        //[1=智能家居系统 roomid]； [2=可视对讲系统 dCode=0，unitid；dCode=1, zoneid]；
        // [3=小区视频监控系统 communityid]； [4=家庭视频监控系统 roomid]； [5=门禁系统 communityid]；
        // [6=停车系统 communityid]； [7=人脸识别服务器 communityid]； [8=人脸识别摄像机 roomid]；
        if ($face_device_data['floor_id']<=0) {
            // 设备码 0 单元机，1 围墙机；type=3 专用，其余均为 0；
            $dCode = 1;
            $parentId = $zoneId;
        } else {
            $dCode = 0;
            $parentId = $unitId;
        }
        // 设备序列号
        $deviceSn = $face_device_data['device_sn'];
        // 设备名称
        $name = $face_device_data['device_name'];
        // 子系统类型
        $systemType = 2;
        $timestamp = strval(time());
        if ($face_device_data['ai_device_sn']) {
            $method = 'editDevice';
            $arrContent = array(
                'apikey'=> $apikey,
                'timestamp'=>$timestamp,
                'method'=>$method,
                'accountId' => intval($accountId),
                'deviceId'=> $face_device_data['ai_device_sn'],
                'name'=> $name,
                'systemType'=> $systemType,
            );
        } else {
            $method = 'addDevice';
            $arrContent = array(
                'apikey'=> $apikey,
                'timestamp'=>$timestamp,
                'method'=>$method,
                'accountId' => intval($accountId),
                'parentId'=> intval($parentId),
                'deviceSn'=> $deviceSn,
                'name'=> $name,
                'systemType'=> $systemType,
                'dCode'=> $dCode
            );
        }

        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $add_device_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '添加设备-结果', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'add_device_info' => $add_device_info], 'A4/add_a4_device_log', 1);
        if ($add_device_info['deviceId'] && $method == 'addDevice') {
            $data = [
                'ai_device_sn' => $add_device_info['deviceId'],
                'login_time' => time(),
                'device_info' => serialize($add_device_info)
            ];
            M('House_face_device')->where(array('device_id' => $device_id))->data($data)->save();
            fdump_api(['msg' => '添加设备成功记录', 'device_id' => $device_id, 'data' => $data], 'A4/add_a4_device_log', 1);
            return true;
        } elseif (1000==$add_device_info['rstCode'] && $method == 'editDevice') {
            $data = [
                'login_time' => time()
            ];
            M('House_face_device')->where(array('device_id' => $device_id))->data($data)->save();
            fdump_api(['msg' => '更新设备成功记录', 'device_id' => $device_id, 'data' => $data], 'A4/add_a4_device_log', 1);
            return true;
        } else {
            fdump_api(['msg' => '添加更新设备失败记录', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'add_device_info' => $add_device_info], 'A4/add_a4_device_err_log', 1);
            return false;
        }
    }

    /**
     * Notes: 删除设备
     * @param $device_id
     * @param array $face_device_data
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/10 10:36
     */
    public function delA4Device($device_id, $face_device_data = []) {
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$face_device_data['ai_device_sn']) {
            fdump_api(['msg' => '删除的设备不存在云设备id', 'device_id' => $device_id, 'face_device_data' => $face_device_data], 'A4/del_a4_device_err_log', 1);
            return false;
        }
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        // 同步设备id
        $ai_device_sn = $face_device_data['ai_device_sn'];

        $timestamp = strval(time());
        $arrContent = array(
            'apikey' => $apikey, 'timestamp' => $timestamp, 'method' => 'delDevice',
            'accountId' => intval($accountId), 'deviceId' => $ai_device_sn
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $del_device_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '删除的设备-结果', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'del_device_info' => $del_device_info], 'A4/del_a4_device_log', 1);
        if (1000==$del_device_info['rstCode']) {
            $data = [
                'ai_device_sn' => 0
            ];
            M('House_face_device')->where(array('device_id' => $device_id))->data($data)->save();
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
            return $info;
        } else {
            fdump_api(['msg' => '删除的设备-错误', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'del_device_info' => $del_device_info], 'A4/del_a4_device_err_log', 1);
            $info['error'] = 1;
            $a4_rstCode = $this->a4_rstCode;
            if ($info['rstCode'] && $a4_rstCode[$del_device_info['rstCode']]) {
                $info['msg'] = $a4_rstCode[$del_device_info['rstCode']];
            }else{
                $info['msg'] = '同步删除失败';
            }
            return $info;
        }
    }

    /**
     * Notes: 获取设备状态
     * @param $device_id
     * @param array $face_device_data
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/10 10:36
     */
    public function getA4Device($device_id, $face_device_data = []) {
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$face_device_data['ai_device_sn']) {
            fdump_api(['msg' => '获取的设备不存在云设备id', 'device_id' => $device_id, 'face_device_data' => $face_device_data], 'A4/get_a4_device_err_log', 1);
            return false;
        }
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        // 同步设备id
        $ai_device_sn = $face_device_data['ai_device_sn'];

        $timestamp = strval(time());
        $arrContent = array(
            'apikey' => $apikey, 'timestamp' => $timestamp, 'method' => 'getDevice',
            'accountId' => intval($accountId), 'deviceId' => $ai_device_sn
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $get_device_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['msg' => '获取的设备-结果', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'get_device_info' => $get_device_info], 'A4/get_a4_device_log', 1);
        if (1000==$get_device_info['rstCode']) {
            $devices = $get_device_info['devices'];
            // online 标识设备在线状态，0-离线，1-在线；
            fdump_api(['msg' => '获取的设备-记录', 'devices' => $devices], 'A4/get_a4_device_log', 1);
            $set = [];
            if ($devices['online']==1) {
                //todo 故障解除 A4
                D('House_maintenan')->normalLog(1,$device_id);
                $set['login_time'] = time();
                $set['device_status'] = 1;
            } else {
                //todo 故障通知 A4
                D('House_maintenan')->faultLog(1,$device_id,'设备不在线');
                $set['device_status'] = 2;
                $set['last_time'] = time();
            }
            M('House_face_device')->where(array('device_id' => $device_id))->save($set);
            return $devices;
        } else {
            fdump_api(['msg' => '获取的设备-错误', 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'get_device_info' => $get_device_info], 'A4/get_a4_device_err_log', 1);
            return false;
        }
    }
    
    /**
     * Notes: 获取住户信息
     * @param $bind_id
     * @param int $householdId 业主 ID
     * @param int $communityId
     * @param array $bind_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/9/10 16:38
     */
    public function searchA4ActHouseholds($bind_id, $householdId=0, $communityId=0, $unitId=0, $bind_info=[]) {
        $d_face_bind_about_info = M('Face_bind_about_info');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 没有传值 自动获取
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (!$householdId && $bind_user['person_id']) {
            $householdId = $bind_user['person_id'];
        }
        $bind_info = $db_house_village_user_bind->where(['pigcms_id' => $bind_id])->find();
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        // 没有传值 自动获取
        if (!$unitId && $bind_info['floor_id']>0) {
            $where_face_bind_about_parent = [
                'bind_type' => 3,
                'device_type' => 4,
                'bind_id' => $bind_info['floor_id'],
            ];
            $unitId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$householdId || !$unitId) {
            fdump_api(['msg' => '缺少用户或者单元id', 'householdId' => $householdId, 'bind_id' => $bind_id, 'bind_info' => $bind_info, '_sql' => $d_face_bind_about_info->_sql()], 'A4/search_a4_act_households_err_log', 1);
            return false;
        }

        // 业主姓名
        $userName = $bind_info['name'] ? $bind_info['name'] : '住户_'.$bind_info['pigcms_id'];
        // 手机号
        $mobilePhone = $bind_info['phone'];

        if (!$mobilePhone) {
            fdump_api(['msg' => '缺少手机号', 'userName' => $userName, 'mobilePhone' => $mobilePhone, 'bind_id' => $bind_id, 'bind_info' => $bind_info], 'A4/search_a4_act_households_err_log', 1);
            return false;
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;
        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $method = 'searchComHouseholds';


        if ($bind_info['ic_card']) {
            $cardNumber = $bind_info['ic_card'];
        } else {
            $cardNumber = '' . 90000000000 + $bind_info['pigcms_id'];
        }

//        $optional = [
//            'userName' => strval($userName),
//            'mobilePhone' => strval($mobilePhone),
//            'unitId' => intval($unitId),
//            'cardNumber' => strval($cardNumber)
//        ];
//        $optional = [];
//        $optional[] = ['userName' => strval($userName)];
//        $optional[] = ['mobilePhone' => strval($mobilePhone)];
//        $optional[] = ['unitId' => intval($unitId)];
//        $optional[] = ['cardNumber' => strval($cardNumber)];

        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=> $method,
            'accountId' => intval($accountId),
            'communityId' => intval($communityId),
            'offset' => 0,
            'size' => 100,
            'userName' => strval($userName),
            'mobilePhone' => strval($mobilePhone),
            'unitId' => intval($unitId),
            'cardNumber' => strval($cardNumber)
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);
        if (1000 == $info['rstCode'] || $info['result']) {
            // 获取信息
            fdump_api(['msg' => '查询信息', 'url' => $url, 'postData' => $postData, 'arrContent' => $arrContent, 'info' => $info], 'A4/search_a4_act_households_log', 1);

            if ($info['households']) {
                $households = $info['households'];
                foreach ($households as $val) {
                    $where_face_bind_about_val = [
                        'bind_type' => 0,
                        'person_id' => $val['householdId'],
                        'device_type' => 4
                    ];
                    $id = $d_face_user_bind_device->where($where_face_bind_about_val)->getField('id');
                    if ($id) {
                        $data = [
                            'personID' => $val['userId'],
                        ];
                        if ($val['cardid']) {
                            $data['card_status'] = 1;
                            $data['cardid'] = $val['cardid'];
                        }
                        $d_face_user_bind_device->where($where_face_bind_about_val)->data($data)->save();
                    }
                }
            }
            return $info;
        } else{
            $errLog = [
                'msg' => '查询信息', 'url' => $url, 'secret_key' => $secret_key, 'apikey' => $apikey, 'bind_id' => $bind_id,
                'householdId' => $householdId, 'bind_info' => $bind_info, 'postData' => $postData, 'arrContent' => $arrContent, 'info' => $info
            ];
            fdump_api($errLog, 'A4/search_a4_act_households_err_log', 1);
            // 获取信息失败
            return false;
        }
    }

    /**
     * Notes: 远程开门
     * @param $bind_id
     * @param $device_id
     * @param int $userId
     * @param array $face_device_data
     * @param bool $is_owner
     * @param int $uid
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/9/21 13:29
     */
    public function deviceA4Unlock($device_id, $bind_id, $userId=0, $face_device_data = [],$is_owner=true,$uid=0) {
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (!$userId && $bind_user['personID']) {
            $userId = $bind_user['personID'];
        }
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$userId || !$face_device_data['ai_device_sn']) {
            // 没有图片不进行人脸同步，没有用户id不进行同步
            fdump('line:------------'.__LINE__,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($userId,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($face_device_data,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            return false;
        }
        $ai_device_sn = $face_device_data['ai_device_sn'];
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'deviceUnlock',
            'accountId' => intval($accountId),
            'deviceId'=> $ai_device_sn,
            'userId'=> $userId
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);

        $d_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_village_single = D('House_village_single');

        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $house_face_device_info['floor_id']))->field('floor_id,floor_layer,floor_name,single_id')->find();
        $house_village_single_info = $d_house_village_single->where(['id' => $house_village_floor_info['single_id']])->field('id as single_id,single_name')->find();
        $house_village_info = $d_house_village->where(array('village_id' => $house_face_device_info['village_id']))->field('village_id,village_name,city_id')->find();
        $village_name = $house_village_info['village_name'];

        if ( $house_face_device_info['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $house_face_device_info['public_area_id']])->getField('public_area_name');
            $floor_name = '';
            $floor_layer = $public_area_name;
        } elseif ( $house_face_device_info['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
        }else{
            $floor_layer = $house_village_single_info['single_name'] ? $house_village_single_info['single_name'] : $house_village_floor_info['floor_layer'];
            $floor_name = $house_village_floor_info['floor_name'];
        }
        $log = array();
        $house_village_bind = $d_house_village_user_bind->where(['pigcms_id' => $bind_id])->field('name,phone,uid,pigcms_id')->find();
        if (!$is_owner && $uid) {
            $users = D('User')->where(array('uid'=>$uid))->field('nickname,phone')->find();
            if (!$users['nickname'] && $users['phone']) {
                $user_tip = substr_replace($users['phone'],'****',3,4);
            } elseif ($users['nickname']){
                $user_tip = $users['nickname'];
            }
            $log_name = $village_name . ' ' . $floor_layer . ' ' . $floor_name."【访客{$user_tip}开门】";
        } else {
            if (!$uid) {
                $uid = $house_village_bind['uid'];
            }
            if ($house_village_bind['name']) {
                $user_tip = $house_village_bind['name'];
            } elseif ($house_village_bind['phone']) {
                $user_tip = substr_replace($house_village_bind['phone'],'****',3,4);
            } else {
                $users = D('User')->where(array('uid'=>$uid))->field('nickname,phone')->find();
                if (!$users['nickname'] && $users['phone']) {
                    $user_tip = substr_replace($users['phone'],'****',3,4);
                } elseif ($users['nickname']){
                    $user_tip = $users['nickname'];
                }
            }
            $log_name = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
        }
        $log['uid'] = $uid;
        $log['log_bind_id'] = $bind_id;
        $log['log_name'] = $log_name;
        $log['log_from'] = 2; // 2用户操作开门
        $log['log_business_id'] = $house_face_device_info['village_id']; // 社区id
        $log['log_more_id'] = $house_face_device_info['floor_id']; // 单元id
        $log['device_id'] = $device_id; // 设备id
        $log['log_time'] = time(); // 记录时间

        if (1000==$info['rstCode']) {
            $log['log_status'] = 0;
            // 0 开门成功，1 开门失败
            $log['log_detail'] = serialize([$url, $postData, $info]);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();
            $d_house_face_device->where(array('device_id' => $device_id))->setField('last_open_time', time());
            // 开门成功
            // 比对成功呼叫电梯
            if (1==$house_face_device_info['device_model'] && $house_face_device_info['floor_id'] && $house_face_device_info['floor_id']!=-1) {
                D('Face_door_a7_service')->add_send_rs485($house_face_device_info['device_id']);
            }
            if ($house_face_device_info['village_id']>0) {
                fdump('同步公安>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_log',true);
                fdump($house_face_device_info,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_log',true);
                $param = [];
                $param['village_id'] = $house_face_device_info['village_id'];
                $param['type'] = 'person_inout_info';
                D('House_village_third_user_info')->add_plan($param, 'person_inout_info', time() + (mt_rand(5, 20)));
            }
            return array('code'=>1,'msg'=>'开门成功','data'=>$info);
        } else {
            $log['log_status'] = 1;
            // 0 开门成功，1 开门失败
            $log['log_detail'] = serialize($info);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($sign,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($postData,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            $a4_rstCode_msg = $this->a4_rstCode[$info['rstCode']] ? $this->a4_rstCode[$info['rstCode']] : '开门失败！';
            fdump($a4_rstCode_msg,'api/log/'.date('Ymd').'/A4/device_a4_un_lock_err_log',true);
            return array('code'=>0,'msg'=>$a4_rstCode_msg);
        }


    }


    /**
     * Notes: 删除住户信息
     * @param $bind_id
     * @param int $householdId
     * @param int $communityId
     * @param array $bind_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/9/10 16:38
     */
    public function delA4Household($bind_id, $householdId=0, $communityId=0, $bind_info=[]) {
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (!$householdId && $bind_user['person_id']) {
            $householdId = $bind_user['person_id'];
        }
        if (!$householdId || !$communityId) {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($householdId,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            return false;
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;
        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $method = 'delHousehold';
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=> $method,
            'accountId' => intval($accountId),
            'communityId'=> $communityId,
            'householdId'=> $householdId,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);
        if (1000==$info['rstCode'] || $info['result']) {
            // 用户删除成功
            $where_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $bind_id,
                'device_type' => 4,
            ];
            $d_face_user_bind_device->where($where_face_bind_about)->delete();
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_household_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/del_a4_household_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/del_a4_household_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/del_a4_household_log',true);
            return $info;
        } else{
            // 人脸图片同步删除失败
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($secret_key,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($apikey,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($householdId,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/del_a4_household_err_log',true);
            return false;
        }
    }

    // 人脸部分--->
    /**
     * Notes: 同步添加编辑人脸
     * @param $bind_id
     * @param int $userId
     * @param int $communityId
     * @param array $bind_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/9/10 16:15
     */
    public function notifyA4ImgUp($bind_id, $userId=0, $communityId=0, $bind_info=[]) {
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (!$userId && $bind_user['personID']) {
            $userId = $bind_user['personID'];
        }
        $d_house_face_img = D('House_face_img');
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
        }
        $uid = $bind_info['uid'];
        if (!$uid || !$userId) {
            // 没有图片不进行人脸同步，没有用户id不进行同步
            $errLog = [
                'msg' => '没有图片不进行人脸同步，没有用户id不进行同步', 'bind_id' => $bind_id, 'userId' => $userId,
                'communityId' => $communityId, 'bind_info' => $bind_info,
            ];
            fdump_api($errLog, 'A4/notify_a4_img_up_err_log', 1);
            return false;
        }
        $img_info = $d_house_face_img->where(array('uid' => $uid, 'status' => ['in', [0,3]], 'img_url' => array('neq', '')))->order('add_time ASC')->limit(3)->field('id, status, img_url')->select();

        $paramLog = [
            'msg' => '图片', 'bind_id' => $bind_id, 'uid' => $uid, 'userId' => $userId,
            'communityId' => $communityId, 'img_info' => $img_info, 'bind_info' => $bind_info,
        ];
        fdump_api($paramLog, 'A4/notify_a4_img_up_log', 1);
        
        $d_face_bind_about_info = M('Face_bind_about_info');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$userId || !$communityId) {
            $paramLog = [
                'msg' => '缺少用户身份或者小区id', 'bind_id' => $bind_id, 'uid' => $uid, 'userId' => $userId,
                'communityId' => $communityId, 'img_info' => $img_info, 'bind_info' => $bind_info,
            ];
            fdump_api($paramLog, 'A4/notify_a4_img_up_err_log', 1);
            return false;
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $word_encryption = '';

        $method = 'notifyImgUp';
        if ($bind_user && $bind_user['face_img']) {
            $method = 'editFaceImg';
        }

        import('@.ORG.Image');
        import('@.ORG.file_handle');
        $fileHandle = new file_handle();
        foreach ($img_info as $val) {
            $img_url = $val['img_url'];
            if (3==$val['status']) {
                if (!$word_encryption) {
                    import('@.ORG.face.word_encryption');
                    $word_encryption = new word_encryption();
                }
                // 加密的图片解密
                $img_url = $word_encryption->text_decrypt($img_url);
            }
            try {
                $faceImg = $img_url;
                $faceImg = replace_file_domain($faceImg);
                if($fileHandle->check_open_oss()) {
                    $fileHandle->download($faceImg);
                }
                $image_path = $fileHandle->get_path($faceImg);
                $pathinfo = pathinfo($image_path);
                $format   = 'jpg';
                $save_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_newChange.' . $format;
                $imgPath  = $_SERVER['DOCUMENT_ROOT'].$image_path;
                $savePath = $_SERVER['DOCUMENT_ROOT'].$save_path;
                $param = [
                    'imgPath'  => $imgPath,
                    'savePath' => $savePath,
                    'with'     => 480,
                    'heiht'    => 640,
                    'format'   => $format,
                ];
                $filterImgChange = invoke_v20_service('\app\community\model\service\FaceDeviceService/filterImg', $param);
                $filterImgChange = $filterImgChange['data']['retval'];
                if ($filterImgChange) {
                    $faceImg = $save_path;
                    if($fileHandle->check_open_oss()) {
                        $fileHandle->download('.'.$save_path);
                    }
                    $imgUrl = replace_file_domain($faceImg);
                } else {
                    $imgUrl = D('Image')->transform_image($img_url);
                    $imgUrl = thumb_img($imgUrl,480,640,'',true);
                    $imagesize = getimagesize($imgUrl);
                    if ($imagesize[0]&&$imagesize[0]<480) {
                        // 如果压缩出来的图片小于宽度480  执行裁剪
                        $imgUrl = thumb_img($imgUrl,480,640,'fill',true);
                    }
                }
            } catch( \Exception $e) {
                $imgUrl = '';
            }
			
            $arrContent = array(
                'apikey'=> $apikey,
                'timestamp'=>$timestamp,
                'method'=> $method,
                'accountId' => intval($accountId),
                'communityId'=> intval($communityId),
                'userId'=> intval($userId),
                'imgUrl'=> strval($imgUrl),
            );

            // 参数加密部分
            $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
            $arrContent['sign'] = $sign;
            // 上传参数处理部分
            $postData = $this->a4_face_class->get_encrypt_key($arrContent);
            $info =  $this->a4_face_class->curlPost($url, $postData);
            $paramLog = ['msg' => '上传图片', 'url' => $url, 'postData' => $postData, 'arrContent' => $arrContent, 'info' => $info];
            fdump_api($paramLog, 'A4/notify_a4_img_up_log', 1);
            if (1000==$info['rstCode'] || $info['result']) {
                // 图片同步成功
                $bind_data = [
                    'last_time' => time(),
                    'face_num' => 1,
                    'face_id' => $val['id'],
                    'face_img' => $imgUrl,
                    'face_txt' => serialize($info),
                ];
                $d_face_user_bind_device->where($where_face_bind_about)->data($bind_data)->save();
                $data_set = [
                    'face_img_status' => 1,
                    'face_img_reason' => '',
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                
                $paramLog = ['msg' => '上传图片-记录', 'bind_id' => $bind_id, 'data_set' => $data_set];
                fdump_api($paramLog, 'A4/notify_a4_img_up_log', 1);
                return $info;
            } else{
                // 人脸图片同步失败
                $a4_rstCode = $this->a4_rstCode;
                if ($info['rstCode'] && $a4_rstCode[$info['rstCode']]) {
                    $remark = $a4_rstCode[$info['rstCode']];
                }else{
                    $remark = '人脸同步添加失败';
                }
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => $remark,
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $bind_id])->data($data_set)->save();

                $paramLog = [
                    'msg' => '人脸图片同步失败-记录', 'url' => $url, 'secret_key' => $secret_key, 'data_set' => $data_set, 'apikey' => $apikey,
                    'bind_id' => $bind_id, 'userId' => $userId, 'bind_info' => $bind_info, 'arrContent' => $arrContent, 'info' => $info,
                ];
                fdump_api($paramLog, 'A4/notify_a4_img_up_err_log', 1);
            }
        }
    }

    /**
     * Notes: 转换base64
     * @param string $img
     * @param bool $imgHtmlCode
     * @return string
     * @author: wanzy
     * @date_time: 2020/9/5 14:05
     */
    public function imgtobase64($img='', $imgHtmlCode=true)
    {
//        $imageInfo = getimagesize($img);
//        $base64 = chunk_split(base64_encode(file_get_contents($img)));
//        return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
        return base64_encode(file_get_contents($img));
    }

    /**
     * Notes: 同步删除人脸
     * @param $bind_id
     * @param int $userId
     * @param int $communityId
     * @param array $bind_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/9/10 16:24
     */
    public function delA4FaceImg($bind_id, $userId=0, $communityId=0, $bind_info=[]) {
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (!$userId && $bind_user['person_id']) {
            $userId = $bind_user['person_id'];
        }
        if (!$userId || !$communityId) {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($userId,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            return false;
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $method = 'delFaceImg';
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=> $method,
            'accountId' => intval($accountId),
            'communityId'=> $communityId,
            'userId'=> $userId,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);
        if (1000==$info['rstCode'] || $info['result']) {
            // 图片删除成功
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_face_img_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/del_a4_face_img_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/del_a4_face_img_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/del_a4_face_img_log',true);
            return $info;
        } else{
            // 人脸图片同步删除失败
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($secret_key,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($apikey,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($userId,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/del_a4_face_img_err_log',true);
            return false;
        }
    }
    // 《--人脸部分

    // 获取开门记录
    /**
     * Notes: 获取开门记录
     * @param $village_id
     * @param int $communityId
     * @param int $offset
     * @param int $size
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/9/11 11:19
     */
    public function getA4SearchUnlockRecords($village_id, $communityId=0,$offset=0,$size=100,$startTime=0,$is_plan=0) {
        $d_face_bind_about_info = M('Face_bind_about_info');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $village_id,
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$communityId) {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($communityId,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($offset,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($size,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            return false;
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $method = 'searchUnlockRecords';
        $out_limit = false;
        if ($is_plan && $size>50) {
            $out_limit = true;
            $size = 50;
        }
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=> $method,
            'accountId' => intval($accountId),
            'communityId'=> $communityId,
            'offset'=> $offset,
            'size'=> $size
        );
        if ($startTime>0) {
            $arrContent['startTime'] = $startTime;
        }
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);
        if (1000==$info['rstCode']) {
            // 获取成功
            $unlockRecords = $info['unlockRecords'];
            if ($unlockRecords) {
                foreach ($unlockRecords as $val) {
                    $record_log = $this->a4_face_class->get_encrypt_key($val);
                    $this->face_a4_door_open_log($val, $record_log);
                }
                if ($out_limit) {
                    // 超出限制数量 生成下一条计划任务
                    $param = array();
                    $arr= array();
                    $now_time = time();
                    $param['type'] = 'searchUnlockRecordCount';
                    $unique_id = 'searchUnlockRecordCount';
                    $param['village_id'] = $village_id;
                    $param['log_time_max'] = 0;
                    $param['device_info'] = [];
                    if (isset($param['village_id'])) {
                        $unique_id .='_'.$param['village_id'];
                    }
                    $param['unique_id'] = $unique_id;
                    $param['time'] = $now_time;
                    $arr['param'] = serialize($param);
                    $arr['plan_time'] = 0;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = $now_time;
                    $arr['file'] = 'sub_house_village_face_device';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    $add_id = M('process_sub_plan')->data($arr)->add();
                    if (!$add_id) {
                        fdump_api(['getA4SearchUnlockRecords添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'A4/plan_a4_door_open_record_err_log',1);
                    }
                }
            }
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_log',true);
            fdump($unlockRecords,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_log',true);
            return $info;
        } else{
            // 人脸图片同步删除失败
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($secret_key,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($apikey,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($communityId,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($offset,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($size,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_records_err_log',true);
            return false;
        }

    }

    /**
     * Notes: 获取开门记录数量列表
     * @param $village_id
     * @param int $communityId
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/9/11 11:27
     */
    public function getA4SearchUnlockRecordCount($village_id, $communityId=0, $startTime=0,$is_plan=0)  {
        $d_face_bind_about_info = M('Face_bind_about_info');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $village_id,
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$communityId) {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($communityId,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            return false;
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $method = 'searchUnlockRecordCount';
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=> $method,
            'accountId' => intval($accountId),
            'communityId'=> $communityId,
        );
        if ($startTime>0) {
            $arrContent['startTime'] = $startTime;
        } else {
            // 如果没有给予起始时间 自行取下记录的最后时间
            // todo 过滤蓝牙开门记录
            $db_house_user_log = M('House_user_log');
            $house_user_log = $db_house_user_log->where([
                'village_id'=>$village_id,
                'log_from'=>array('neq',21)
            ])->field('MAX(log_time) as log_time_max')->find();
            if ($house_user_log['log_time_max']) {
                $startTime = $house_user_log['log_time_max'];
                $arrContent['startTime'] = $startTime;
            }
        }
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);
        if (1000==$info['rstCode'] && isset($info['unlockRecordCount'])) {
            // 获取成功
            $unlockRecordCount = $info['unlockRecordCount'];
            if ($unlockRecordCount>0) {
                $this->getA4SearchUnlockRecords($village_id, $communityId, 0, $unlockRecordCount,$startTime,$is_plan);
            }
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_log',true);
            fdump($unlockRecordCount,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_log',true);
            return $unlockRecordCount;
        } else{
            // 人脸图片同步删除失败
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($secret_key,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($apikey,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($communityId,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/get_a4_search_unlock_record_count_err_log',true);
            return false;
        }

    }

    /**
     * Notes: 记录开门
     * @param $data
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/11 11:18
     */
    private function face_a4_door_open_log($data, $record_log) {
        $d_house_user_log = M('House_user_log');
        $msg = $d_house_user_log->where(array('record_log' => $record_log))->find();
        if ($msg) {
            // 添加过直接返回
            return true;
        }
        $d_house_face_device = M('House_face_device');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');

        $deviceName = $data['deviceName'];// 设备名称
        $deviceid = $data['deviceid'];// 设备 ID
        $roomNum = $data['roomNum']?$data['roomNum']:'';// 房间名称
        $userid = $data['userid'];// 用户 ID
        $userName = $data['userName'];// 住户姓名
        $mobilePhone = $data['mobilePhone'];// 手机号
        $comNumber = $data['comNumber'];// 卡号/手机号
        // 1 手机开门，2 刷卡开门，3WiFi 开门，4 临时密码开门，5 住户密码开门，6 平台刷卡开门，7 蓝
        //牙开门，8 室内机开门，10，呼叫住户开门，13 人脸识别开门；
        $reason = $data['reason'];// 开门类型
        $timestamp = $data['timestamp'];// 开门时间

        if (13==$reason) {
            $log_from =1;
        } else {
            $log_from =2;
        }

        $log = array();
        $house_face_device_info = $d_house_face_device->where(array('ai_device_sn' => $deviceid))->find();

        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'personID' => $userid,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        $house_village_bind = $d_house_village_user_bind->where(array('pigcms_id' => $bind_user['bind_id']))->find();
        $house_village_info = $d_house_village->where(array('village_id' => $house_village_bind['village_id']))->find();
        $d_house_village_floor = M('House_village_floor');
        $log_name = '';
        $log_name .= $house_village_info['village_name'];
        if ($house_face_device_info['floor_id'] != -1) {
            $floor_arr = explode(",",$house_face_device_info['floor_id']);
            $house_village_floor_info = $d_house_village_floor
                ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                ->field('a.*, b.single_name')
                ->where(array('floor_id' => ['in', $floor_arr]))
                ->select();
            if ($house_village_floor_info) {
                $data_msg = [];
                foreach ($house_village_floor_info as $val) {
                    if ($val && $val['floor_name']) {
                        $data_msg[] = ($val['single_name'] ? $val['single_name'] : $val['floor_layer']) . $val['floor_name'];
                    }
                }
                if ($data_msg) {
                    $msg = implode($data_msg,';');
                    $log_name .= ' ' . $msg;
                }
            }
        } elseif ( $house_face_device_info['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $house_face_device_info['public_area_id']])->getField('public_area_name');
            $log_name .= ' ' . $public_area_name;
        } elseif ( $house_face_device_info['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
            $log_name .= ' ' . $floor_layer.$floor_name;
        }

        $uid = $house_village_bind['uid']?$house_village_bind['uid']:0;
        $log['uid'] = $uid;
        $log['log_bind_id'] = $house_village_bind['pigcms_id'];
        $log['log_name'] = $log_name;
        $log['log_from'] = $log_from; // 人脸识别门禁
        $log['log_business_id'] = $house_village_bind['village_id']; // 社区id
        $log['log_more_id'] = $house_village_bind['floor_id']; // 单元id
        $log['device_id'] = $house_face_device_info['device_id']; // 设备id
        if ($timestamp) {
            $time = strtotime($timestamp);
        } else {
            $time = time();
        }

        $log['log_time'] = $time; // 记录时间
        $log['record_log'] = $record_log;
        $detail=$data;
        if ($data['imgUrl']) {
            $detail['face_img'] = D('Face_door_service')->face_to_img($data['imgUrl'],$house_face_device_info['device_id']);
        }
        if (isset($detail['face_img']) && trim($detail['face_img'])) {
            $log['cut_image'] = trim($detail['face_img']);
        }
        $log['log_detail'] = serialize($detail);
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/A4/face_a4_door_open_log',true);
        fdump($detail,'api/log/'.date('Ymd').'/A4/face_a4_door_open_log',true);
        fdump($log,'api/log/'.date('Ymd').'/A4/face_a4_door_open_log',true);
        $log['add_time']=time();
        $d_house_user_log->data($log)->add();
    }

    /**
     * Notes:
     * @param $base_img
     * @param $id
     * @return bool|string
     * @author: wanzy
     * @date_time: 2020/9/11 11:16
     */
    private function face_to_img($base_img, $id) {
//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = './upload/face_log/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'png';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            # 远程文件处理
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$base_img);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);  # 过期时间
            //当请求https的数据时，会要求证书，加上下面这两个参数，规避ssl的证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            $img = curl_exec($ch);
            curl_close($ch);
            $fp2 = @fopen($new_file ,'a');
            fwrite($fp2,$img);
            fclose($fp2);
            unset($img,$url);
            //判断是否需要上传至云存储
//            $file_handle = new file_handle();
//            $file_handle->upload($new_file);
            $img_path = trim($new_file,'.');
            return $img_path;
        } else {
            return false;
        }
    }

    //<---同步小区消息
    public function addAllA4Property($village_id, $zoneId = 0, $communityId = 0, $is_plan = 0)
    {
        $d_face_bind_about_info = M('Face_bind_about_info');
        if (!$zoneId) {
            $where_face_bind_about_parent = [
                'bind_type' => 1,
                'device_type' => 4,
                'bind_id' => $village_id,
            ];
            $zoneId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $village_id,
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        $bind_list = D('House_village_user_bind')->where(['village_id' =>$village_id, 'type' => 4, 'status' => 1])->select();
        fdump_api(['msg' => '同步所有物业信息', 'line' => __LINE__, 'village_id' => $village_id, 'bind_list' => $bind_list], 'A4/add_all_a4_property_log', 1);
        if ($bind_list) {
            foreach ($bind_list as $val) {
                if ($is_plan == 3) {
                    $params = ['type' => 'add_a4_property', 'bind_id' => $val['pigcms_id'], 'village_id' => $village_id, 'communityId' => $communityId, 'zoneId' => $zoneId];
                    $invokeV20ServiceParams = ['params' => $params];
                    $models = '\app\community\model\service\Device\A4DeviceService/addA4PropertyJobBox';
                    $result = invoke_v20_service($models, $invokeV20ServiceParams);
                } elseif ($is_plan) {
                    // 如果是计划任务后续同步执行为计划任务
                    $param = [
                        'type' => 'add_a4_property', 'bind_id' => $val['pigcms_id'], 'village_id' => $village_id,
                        'communityId' => $communityId, 'zoneId' => $zoneId
                    ];
                    $this->add_plan($param);
                } else {
                    $this->addA4Property($val['pigcms_id'], $zoneId, $communityId, $val);
                }
            }
        }
        return true;
    }

    // 添加编辑物业工作人员
    public function addA4Property($bind_id, $zoneId = 0, $communityId = 0, $bind_info = [], $is_plan = 0)
    {
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
        }
        if (!in_array($bind_info['type'],[4,5,6])) {
            $errLog = [
                'msg' => '非工作人员[通行证和临时访客]', 'line' => __LINE__, 'bind_id' => $bind_id, 
                'communityId' => $communityId, 'zoneId' => $zoneId, 'bind_info' => $bind_info,
            ];
            fdump_api($errLog, 'A4/add_a4_property_err_log', 1);
            return false;
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$zoneId) {
            $where_face_bind_about_parent = [
                'bind_type' => 1,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $zoneId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$communityId || !$zoneId) {
            $errLog = [
                'line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId,
                'zoneId' => $zoneId, 'bind_info' => $bind_info,
            ];
            fdump_api($errLog, 'A4/add_a4_property_err_log', 1);
            return false;
        }
        // 查看是否已经存在关联值
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (empty($bind_user)) {
            $method = 'addProperty';
        } elseif ($bind_user['person_id']) {
            // 如果绑定过 编辑信息
            $method = 'editProperty';
        } else {
            $errLog = [
                'line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId,
                'zoneId' => $zoneId, 'bind_user' => $bind_user,
            ];
            fdump_api($errLog, 'A4/add_a4_property_err_log', 1);
            return false;
        }

        // 业主姓名
        $userName = trim($bind_info['name']) ? trim($bind_info['name']) : '住户_'.$bind_info['pigcms_id'];;
        // 性别
        $gender = 0;
        // 身份证
        $Idnumber = trim($bind_info['id_card']) ? trim($bind_info['id_card']) : '';
        if(intval($bind_info['type']) == 4){
            $id_cards=M('House_worker')->where([
                'village_id'=>$bind_info['village_id'],
                'phone'=>$bind_info['phone'],
                'status'=>array('neq', 4)
            ])->getField('id_card');
            $Idnumber=trim($id_cards) ? trim($id_cards) : '';
        }
        if (!$Idnumber) {
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => '请先填写对应用户身份证件号码',
            ];
            D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
            $errLog = [
                'msg' => '请先填写对应用户身份证件号码', 'line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId,
                'zoneId' => $zoneId, 'bind_user' => $bind_user, 'bind_info' => $bind_info,
            ];
            fdump_api($errLog, 'A4/add_a4_property_err_log', 1);
            return false;
        } else if (strpos($Idnumber, '*') !== false) {
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => '身份证不能使用脱敏信息',
            ];
            D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
            $errLog = [
                'msg' => '身份证不能使用脱敏信息', 'line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId,
                'zoneId' => $zoneId, 'bind_user' => $bind_user, 'bind_info' => $bind_info,
            ];
            fdump_api($errLog, 'A4/add_a4_property_err_log', 1);
            return false;
        }
        // 手机号 使用身份证
        //$mobilePhone = $bind_info['phone'] ? $bind_info['phone']:$Idnumber;
        $mobilePhone=$Idnumber; //手机号替换成身份证号码
        // type：住户类型：1 管理员，2 普通工作人员；
        $type = 2;
        if (in_array($bind_info['type'],[5,6])) {
            // 临时访客和通行证必定有时效 没有时效不做同步
            $end_time = date('y-m-d',$bind_user['property_endtime']);
            if (!$end_time) {
                fdump_api(['msg' => '临时访客和通行证必定有时效 没有时效不做同步', 'line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId, 'zoneId' => $zoneId, 'bind_user' => $bind_user, 'bind_info' => $bind_info], 'A4/add_a4_property_err_log', 1);
                return false;
            }
            $residenceTime = $cardTimelimit = $end_time;
        } else {
            $residenceTime = '2038-12-26';
            // 卡有效期
            $cardTimelimit = '2038-12-26';
        }
//        if ($bind_info['property_endtime']) {
//            $residenceTime = date('Y-m-d', $bind_info['property_endtime']);
//        }
        if ($bind_info['ic_card']) {
            $cardNumber = $bind_info['ic_card'];
        } else {
            $cardNumber = '' . 90000000000 + $bind_info['pigcms_id'];
        }
        $this->addA4Card($cardNumber);
        // cardType：0 普通卡，1 巡更卡，2 保洁卡；下同；
        $cardType = 0;

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());

        if ($method=='addProperty') {
            $arrContent = array(
                'apikey'=> $apikey,
                'timestamp'=>$timestamp,
                'method'=>$method,
                'accountId' => intval($accountId),
                'communityId'=> $communityId,
                'zoneId'=> $zoneId,
                'userName'=> $userName,
                'gender'=> $gender,
                'mobilePhone'=> $mobilePhone,
                'Idnumber'=> $Idnumber,
                'type'=> $type,
                'residenceTime'=> $residenceTime,
                'cardNumber'=> $cardNumber,
                'cardType' => $cardType,
                'cardTimelimit' => $cardTimelimit
            );
        } elseif ($bind_user['person_id']) {
            $arrContent = array(
                'apikey'=> $apikey,
                'timestamp'=>$timestamp,
                'method'=>$method,
                'accountId' => intval($accountId),
                'householdId'=> $bind_user['person_id'],
                'userName'=> $userName,
                'gender'=> $gender,
                'mobilePhone'=> $mobilePhone,
                'Idnumber'=> $Idnumber,
                'type'=> $type,
                'residenceTime'=> $residenceTime,
                'cardNumber'=> $cardNumber,
                'cardType' => $cardType,
                'cardTimelimit' => $cardTimelimit
            );
        } else {
            fdump_api(['line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId, 'zoneId' => $zoneId, 'bind_user' => $bind_user], 'A4/add_a4_property_err_log', 1);
            return false;
        }
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $hold_info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['line' => __LINE__, 'url' => $url, 'postData' => $postData, 'hold_info' => $hold_info], 'A4/add_a4_property_log', 1);
        if ($hold_info['householdId']) {
            $face_user_bind_device_info = [
                'bind_type' => 0,
                'bind_id' => $bind_id,
                'person_id' => $hold_info['householdId'],
                'info' => serialize($hold_info),
                'device_type' => 4,
                'device_id' => 0,
                'group_id' => $communityId,
                'groupID' => $zoneId,
            ];
            if ($bind_user) {
                $face_user_bind_device_info['last_time'] = time();
                $add_id = $d_face_user_bind_device->where($where_face_bind_about)->data($face_user_bind_device_info)->save();
            } else {
                $face_user_bind_device_info['add_time'] = time();
                $add_id = $d_face_user_bind_device->data($face_user_bind_device_info)->add();
            }
            if (!$add_id) {
                $fdumpApiArr = [
                    'line' => __LINE__, 'bind_user' => $bind_user, 'where_face_bind_about' => $where_face_bind_about,
                    'face_user_bind_device_info' => $face_user_bind_device_info, 'hold_info' => $hold_info
                ];
                fdump_api($fdumpApiArr, 'A4/add_a4_property_err_log', 1);
            }
            // 同步查询下然后把对应userId记录下来
            $this->searchA4Propertys($bind_id, $communityId,$zoneId);
            if ($is_plan == 3) {
                $params = ['type' => 'notify_a4_img_up', 'bind_id' =>$bind_id, 'communityId' =>$communityId, 'userId' => 0];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/notifyA4ImgUpJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'notify_a4_img_up',
                    'bind_id' =>$bind_id,
                    'communityId' =>$communityId,
                    'userId' => 0,
                ];
                $this->add_plan($param);
            } else {
                if (!$bind_info) {
                    $bind_info = [];
                }
                $this->notifyA4ImgUp($bind_id, 0, $communityId, $bind_info);
            }
        }
        elseif (1000==$hold_info['rstCode']) {
            // 同步查询下然后把对应userId记录下来
            $this->searchA4Propertys($bind_id, $communityId,$zoneId);
            if ($is_plan == 3) {
                $params = ['type' => 'notify_a4_img_up', 'bind_id' =>$bind_id, 'communityId' =>$communityId, 'userId' => 0];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/notifyA4ImgUpJobBox';
                $result = invoke_v20_service($models, $invokeV20ServiceParams);
            } elseif ($is_plan) {
                // 如果是计划任务后续同步执行为计划任务
                $param = [
                    'type' => 'notify_a4_img_up',
                    'bind_id' =>$bind_id,
                    'communityId' =>$communityId,
                    'userId' => 0,
                ];
                $this->add_plan($param);
            } else {
                if (!$bind_info) {
                    $bind_info = [];
                }
                $this->notifyA4ImgUp($bind_id, 0, $communityId, $bind_info);
            }
        }
        elseif (1010==$hold_info['rstCode']){
            if (!$bind_info) {
                $bind_info = [];
            }
            $this->notifyA4ImgUp($bind_id, 0, $communityId, $bind_info);
        }
        elseif (1012==$hold_info['rstCode']){
            if($bind_user){
                $d_face_user_bind_device->where(['id'=>$bind_user['id'],'device_type'=>4])->delete();
                fdump_api(['msg' => '删除关联数据', 'line' => __LINE__, 'url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'hold_info' => $hold_info, 'bind_user' => $bind_user], 'A4/add_a4_property_log', 1);
            }
        }
        else {
            fdump_api(['url' => $url, 'arrContent' => $arrContent, 'postData' => $postData, 'hold_info' => $hold_info], 'A4/add_a4_property_err_log', 1);
        }
        return $hold_info;

    }

    /**
     * Notes: 获取工作人员信息
     * @param $bind_id
     * @param int $householdId
     * @param int $communityId
     * @param int $zoneId
     * @param array $bind_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/11/3 15:36
     */
    public function searchA4Propertys($bind_id, $communityId=0, $zoneId=0, $bind_info=[]) {
        $d_face_bind_about_info = M('Face_bind_about_info');
        // 没有传值 自动获取
        $d_face_user_bind_device = M('Face_user_bind_device');
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
        }
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        // 没有传值 自动获取
        if (!$zoneId) {
            $where_face_bind_about_parent = [
                'bind_type' => 1,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $zoneId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        if (!$zoneId) {
            fdump_api(['line' => __LINE__, 'bind_id' => $bind_id, '_sql' => $d_face_bind_about_info->_sql(), 'communityId' => $communityId, 'zoneId' => $zoneId, 'bind_info' => $bind_info], 'A4/searchA4PropertysErrLog', 1);
            return false;
        }

        // 手机号 使用身份证
        $mobilePhone = trim($bind_info['id_card']) ? trim($bind_info['id_card']) : '';

        if(intval($bind_info['type']) == 4){
            $id_cards=M('House_worker')->where([
                'village_id'=>$bind_info['village_id'],
                'phone'=>$bind_info['phone'],
                'status'=>array('neq', 4)
            ])->getField('id_card');
            $mobilePhone=trim($id_cards) ? trim($id_cards) : '';
        }

        if (!$mobilePhone) {
            fdump_api(['line' => __LINE__, 'bind_id' => $bind_id, 'communityId' => $communityId, 'zoneId' => $zoneId, 'bind_info' => $bind_info], 'A4/searchA4PropertysErrLog', 1);
            return false;
        }
        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;
        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $method = 'searchPropertys';


        if ($bind_info['ic_card']) {
            $cardNumber = $bind_info['ic_card'];
        } else {
            $cardNumber = '' . 90000000000 + $bind_info['pigcms_id'];
        }

        $userName = trim($bind_info['name']) ? trim($bind_info['name']) : '住户_'.$bind_info['pigcms_id'];
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=> $method,
            'accountId' => intval($accountId),
            'communityId' => intval($communityId),
            'offset' => 0,
            'size' => 100,
            'userName' => strval($userName),
            'mobilePhone' => strval($mobilePhone),
            'zoneId' => intval($zoneId),
            'cardNumber' => strval($cardNumber)
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);
        fdump_api(['line' => __LINE__, 'url' => $url, 'postData' => $postData, 'info' => $info], 'A4/searchA4PropertysLog', 1);
        if (1000 == $info['rstCode'] || $info['result']) {
            // 获取信息
            $where_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $bind_id,
                'device_type' => 4,
            ];
            $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();

            if ($info['households']) {
                $households = $info['households'];
                foreach ($households as $val) {
                    $where_face_bind_about_val = [
                        'bind_type' => 0,
                        'person_id' => $val['householdId'],
                        'device_type' => 4
                    ];
                    $id = $d_face_user_bind_device->where($where_face_bind_about_val)->getField('id');
                    if ($id) {
                        $data = [
                            'personID' => $val['userId'],
                        ];
                        $d_face_user_bind_device->where($where_face_bind_about_val)->data($data)->save();
                    } else {
                        $face_user_bind_device_info = [
                            'bind_type' => 0,
                            'bind_id' => $bind_id,
                            'person_id' => $val['householdId'],
                            'info' => serialize($val),
                            'device_type' => 4,
                            'device_id' => 0,
                            'group_id' => $communityId,
                            'groupID' => $zoneId,
                            'personID' => $val['userId'],
                        ];
                        if ($val['cardid']) {
                            $face_user_bind_device_info['card_status'] = 1;
                            $face_user_bind_device_info['cardid'] = $val['cardid'];
                        }
                        if ($bind_user) {
                            $face_user_bind_device_info['last_time'] = time();
                            $add_id = $d_face_user_bind_device->where($where_face_bind_about)->data($face_user_bind_device_info)->save();
                        } else {
                            $face_user_bind_device_info['add_time'] = time();
                            $add_id = $d_face_user_bind_device->data($face_user_bind_device_info)->add();
                        }
                        if (!$add_id) {
                            fdump_api(['line' => __LINE__, 'add_id' => $add_id, '_sql' => $d_face_user_bind_device->_sql(), 'face_user_bind_device_info' => $face_user_bind_device_info], 'A4/searchA4PropertysErrLog', 1);
                        }
                    }
                }
            }
            return $info;
        } else{
            // 获取信息失败
            fdump_api(['line' => __LINE__, 'url' => $url, 'secret_key' => $secret_key, 'apikey' => $apikey, 'bind_id' => $bind_id, 'bind_info' => $bind_info, 'arrContent' => $arrContent, 'info' => $info], 'A4/searchA4PropertysErrLog', 1);
            return false;
        }
    }

    // 删除物业工作人员
    public function delA4Property($bind_id, $householdId=0, $communityId=0, $bind_info=[]) {
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(['pigcms_id' => $bind_id])->find();
        }
        $d_face_bind_about_info = M('Face_bind_about_info');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 没有传值 自动获取
        if (!$communityId) {
            $where_face_bind_about_parent = [
                'bind_type' => 0,
                'device_type' => 4,
                'bind_id' => $bind_info['village_id'],
            ];
            $communityId = $d_face_bind_about_info->where($where_face_bind_about_parent)->getField('third_id');
        }
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_type' => 4,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (!$householdId && $bind_user['person_id']) {
            $householdId = $bind_user['person_id'];
        }
        if (!$householdId || !$communityId) {
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($householdId,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            return false;
        }

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;
        $accountId = $this->a4_face_class->accountId;
        $timestamp = strval(time());
        $method = 'delProperty';
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=> $method,
            'accountId' => intval($accountId),
            'communityId'=> $communityId,
            'householdId'=> $householdId,
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $info =  $this->a4_face_class->curlPost($url, $postData);
        if (1000==$info['rstCode'] || $info['result']) {
            // 用户删除成功
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_property_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/del_a4_property_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/del_a4_property_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/del_a4_property_log',true);
            return $info;
        } else{
            // 人脸图片同步删除失败
            fdump('========>>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($url,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($secret_key,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($apikey,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($householdId,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($arrContent,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            fdump($info,'api/log/'.date('Ymd').'/A4/del_a4_property_err_log',true);
            return false;
        }
    }


}