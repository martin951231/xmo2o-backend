<?php


class Dahua_serviceModel extends Model
{
    private $charset = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789'; //随机因子
    private $card_num = 'ABCDEF0123456789'; //随机因子

    /**
     * 添加大华设备时候，给小区人员授权开卡
     * @author lijie
     * @date_time 2021/12/08
     * @param string $deviceName
     * @param string $deviceIp
     * @param int $village_id
     * @param int $device_id
     * @param int $floor_id
     * @return bool
     */
    public function face_authorization($deviceName='',$deviceIp='',$village_id=0,$device_id=0,$floor_id=-1)
    {
        if((empty($deviceName) && empty($deviceIp)) || !$village_id || !$device_id){
            return false;
        }
        $cardPrivilegeDetails = $this->get_door_device($deviceName,$deviceIp);
        fdump_api(['deviceName' => $deviceName,'deviceIp' => $deviceIp,'cardPrivilegeDetails' => $cardPrivilegeDetails,'line' => __LINE__,'funs' => __FUNCTION__],'dahua/cardPrivilegeDetails',1);
        if(empty($cardPrivilegeDetails)){
            return false;
        }
        //todo 添加查询开门记录的子计划任务
        $arr = [];
        $time = time();
        $unique_id = "dahua_door_record".$time.rand(1,9999);
        $arr['plan_time'] = time()+1800;
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'sub_dahua_door_record';
        $arr['time_type'] = 1;
        $arr['unique_id'] = $unique_id;
        $task_id = M('Process_sub_plan')->data($arr)->add();
        //todo 给住户开卡、绑定人脸
        $where['village_id'] = $village_id;
        $where['status'] = 1;
        $where['phone'] = array('neq','');
        $where['id_card'] = array('neq','');
        if($floor_id != -1){
            $where['floor_id'] = $floor_id;
        }
        $user_bind_list = M('House_village_user_bind')->where($where)->field('pigcms_id')->select();
        foreach ($user_bind_list as $v){
            $arr = [];
            $time = time();
            $unique_id = "dahua_authorization".$time.rand(1,9999);
            $arr['plan_time'] = -time();
            $arr['param'] = serialize(array('pigcms_id'=>$v['pigcms_id'],'device_id'=>$device_id,'cardPrivilegeDetails'=>$cardPrivilegeDetails));
            $arr['space_time'] = 0;
            $arr['add_time'] = time();
            $arr['file'] = 'sub_dahua_authorization';
            $arr['time_type'] = 1;
            $arr['unique_id'] = $unique_id;
            $task_id = M('Process_sub_plan')->data($arr)->add();
        }
        return true;
    }

    /**
     * 同步指定用户到大华设备
     * @author lijie
     * @date_time 2021/12/09
     * @param int $device_id
     * @param array $bind_arr_id
     * @return bool
     */
    public function dahua_arr_person_msg($device_id=0,$bind_arr_id=[])
    {
        if(!$device_id || empty($bind_arr_id)){
            return false;
        }
        $info = D('House_face_device')->where(array('device_id'=>$device_id))->find();
        $deviceName = $info['device_name'];
        $deviceIp = $info['device_ip'];
        $cardPrivilegeDetails = $this->get_door_device($deviceName,$deviceIp);
        if(empty($cardPrivilegeDetails)){
            return false;
        }
        foreach ($bind_arr_id as $v){
            $arr = [];
            $time = time();
            $unique_id = "dahua_authorization".$time.rand(1,9999);
            $arr['plan_time'] = 1;
            $arr['param'] = serialize(array('pigcms_id'=>$v,'device_id'=>$device_id,'cardPrivilegeDetails'=>$cardPrivilegeDetails));
            $arr['space_time'] = 0;
            $arr['add_time'] = time();
            $arr['file'] = 'sub_dahua_authorization';
            $arr['time_type'] = 1;
            $arr['unique_id'] = $unique_id;
            $task_id = M('Process_sub_plan')->data($arr)->add();
        }
        return true;
    }

    /**
     * 删除设备对应的人员
     * @author lijie
     * @date_time 2021/12/09
     * @param int $device_id
     * @return bool
     */
    public function del_device($device_id=0)
    {
        if(!$device_id) {
            return false;
        }
        $whereUserBind = ['bind_type'=>0,'bind_id'=>['gt',0],'device_id'=>$device_id,'device_type'=>88];
        $face_user_bind_device_info = M('Face_user_bind_device')->where($whereUserBind)->select();
        $person_ids = [];
        $resArr = [];
        if($face_user_bind_device_info){
            foreach ($face_user_bind_device_info as $v){
                $res =  $this->returnByNumber($v['bind_id'],$v['cardid']);
                if ($res['status']==1) {
                    M('Face_user_bind_device')->where(['id'=>$v['id']])->delete();
                }
                $resArr[] = $res;
                $person_ids[] = $v['person_id'];
            }
        }
        fdump(['msg' => '删除设备对应人员','device_id' => $device_id,'device_id' => $device_id,'person_ids' => $person_ids, 'resArr'=>$resArr],'api/log/dahua/del_device',true);
        return true;
    }

    /**
     * 获取授权通道
     * @author lijie
     * @date_time 2021/12/08
     * @param string $deviceName
     * @param string $deviceIp
     * @param bool $is_return
     * @return array|bool
     */
    public function get_door_device($deviceName='',$deviceIp='',$is_return=false)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'deviceName'=>$deviceName,'deviceIp'=>$deviceIp];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/getDoorDevice', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/getDoorDevice', 'Funs' => __FUNCTION__],'dahua/get_door_device',1);
        $res = $res['data']['retval'];
        $cardPrivilegeDetails = [];
        if($res['success'] == true){
            if ($is_return) {
                if ($res&&isset($res['data'])&&isset($res['data']['pageData'])&&isset($res['data']['pageData'][0])) {
                    return $res['data']['pageData'][0];
                } else {
                    return [];
                }
            }
            $data = $res['data']['pageData'][0]['accessControlChannelList'];
            foreach ($data as $v){
                $cardPrivilegeDetails[] = ['privilegeType'=>1,'resouceCode'=>$v['channelCode']];
            }
            return $cardPrivilegeDetails;
        }else{
            return [];
        }
    }

    /**
     * 添加部门
     * @param string $name
     * @param string $description
     * @param int $parentId
     * @return array
     */
    public function add_department($name='',$description='',$parentId=1)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'name'=>$name,'description'=>$description,'parentId'=>$parentId];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/addDepartment', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/addDepartment', 'Funs' => __FUNCTION__],'dahua/add_department',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1,'department_id'=>$res['data']];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 删除部门
     * @author lijie
     * @date_time 2021/12/09
     * @param int $department_id
     * @return array|int[]
     */
    public function del_department($department_id=0)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'department_id'=>$department_id];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/delDepartment', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/delDepartment', 'Funs' => __FUNCTION__],'dahua/del_department',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 添加身份
     * @author lijie
     * @date_time 2021/12/07
     * @param string $name
     * @param int $subsidyAmount
     * @param int $isCashRecharge
     * @param int $isMachineRecharge
     * @param string $description
     * @return array
     */
    public function add_identity($name='',$subsidyAmount=0,$isCashRecharge=1,$isMachineRecharge=1,$description='')
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = [
            'authInfo'=>$authInfo,'name'=>$name,
            'subsidyAmount'=>$subsidyAmount,'isCashRecharge'=>$isCashRecharge,
            'isMachineRecharge'=>$isMachineRecharge,'description'=>$description
        ];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/addIdentity', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/addIdentity', 'Funs' => __FUNCTION__],'dahua/add_identity',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1,'identity_id'=>$res['data']];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 删除身份
     * @author lijie
     * @date_time 2021/12/09
     * @param array $identity_ids
     * @return array
     */
    public function del_identity($identity_ids=[])
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'identity_ids'=>$identity_ids];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/delIdentity', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/delIdentity', 'Funs' => __FUNCTION__],'dahua/del_identity',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 添加人员
     * @author lijie
     * @date_time 2021/12/07
     * @param string $paperType
     * @param string $paperNumber
     * @param string $name
     * @param string $code
     * @param int $deptId
     * @param string $sex
     * @param string $birthday
     * @param string $phone
     * @param string $status
     * @param int $personIdentityId
     * @return array
     */
    public function addPerson($paperType='身份证',$paperNumber='',$name='',$code='',$deptId=0,$sex='男',$birthday='2018-11-03',$phone='',$status='在职',$personIdentityId=0)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = [
            'authInfo'=>$authInfo,'paperType'=>$paperType,'paperNumber'=>$paperNumber,
            'name'=>$name,'code'=>$code,'deptId'=>$deptId,'sex'=>$sex,'birthday'=>$birthday,
            'phone'=>$phone,'status'=>$status,'personIdentityId'=>$personIdentityId
        ];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/addPerson', $params);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/addPerson', 'Funs' => __FUNCTION__],'dahua/addPerson',1);
            return ['status'=>1,'person_id'=>$res['data']];
        }else{
            $rt = ['status'=>0,'errMsg'=>$res['errMsg']];
            if ($res['errMsg']=='电话号码已经存在!') {
                // 手机号重复查询下信息
                $params = [ 'authInfo'=>$authInfo,'phone' => $phone,'param' => []];
                $byUserData = invoke_v20_service('\app\community\model\service\DaHuaService/bycondition', $params);
                fdump_api(['params' => $params,'byUserData' => $byUserData,'line' => __LINE__,'funs' => 'service\DaHuaService/bycondition', 'Funs' => __FUNCTION__],'dahua/addPerson',1);
                $byUser = $byUserData['data']['retval'];
                if (isset($byUser['dhUser'])&&$byUser['dhUser']) {
                    $rt['dhUser'] = $byUser['dhUser'];
                    if (isset($rt['dhUser']['personIdentityId'])&&isset($rt['dhUser']['id'])&&isset($rt['dhUser']['code'])&&$rt['dhUser']['personIdentityId']!=$deptId) {
                        $params = [
                            'name'=>$name,'code'=>$rt['dhUser']['code'],'deptId'=>$deptId,'sex'=>$sex,'birthday'=>$birthday,
                            'phone'=>$phone,'status'=>$status,'personIdentityId'=>$personIdentityId
                        ];
                        $resUpdate = invoke_v20_service('\app\community\model\service\DaHuaService/updatePerson', $params);
                        $resUpdate = $resUpdate['data']['retval'];
                        if($resUpdate['success'] == true){
                            $rt['dhUser']['personIdentityId'] = $deptId;
                        }
                        fdump_api(['params' => $params,'resUpdate' => $resUpdate,'line' => __LINE__,'funs' => 'service\DaHuaService/updatePerson', 'Funs' => __FUNCTION__],'dahua/addPerson',1);
                    }
                }
            }
            fdump_api(['params' => $params,'rt' => $rt,'line' => __LINE__,'funs' => 'service\DaHuaService/addPerson', 'Funs' => __FUNCTION__],'dahua/addPerson',1);
            return $rt;
        }
    }

    /**
     * 删除人员
     * @author lijie
     * @date_time 2021/12/09
     * @param array $person_ids
     * @return array|int[]
     */
    public function delPerson($person_ids=[])
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'person_ids'=>$person_ids];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/delPerson', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/delPerson', 'Funs' => __FUNCTION__],'dahua/delPerson',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    public function returnByNumber($pigcms_id,$cardNumber='') {
        if (!$cardNumber) {
            $cardNumber = M('Face_user_bind_device')->where(['bind_id'=>$pigcms_id,'device_type'=>88])->getField('cardid');
        }
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'cardNumber'=>$cardNumber];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/returnByNumber', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/returnByNumber', 'Funs' => __FUNCTION__],'dahua/returnByNumber',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 人员图片上传
     * @author lijie
     * @date_time 2021/12/07
     * @param string $personCode
     * @param string $base64file
     * @return int[]
     */
    public function save_mobile_base64_image_to_byte($personCode='',$base64file='')
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'personCode'=>$personCode,'base64file'=>$base64file];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/saveMobileBase64ImageToByte', ['authInfo'=>$authInfo,'personCode'=>$personCode,'base64file'=>$base64file]);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/saveMobileBase64ImageToByte', 'Funs' => __FUNCTION__],'dahua/save_mobile_base64_image_to_bytes',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        } elseif (isset($res['errMsg']) && $res['errMsg'] == "编号为{$personCode}的人不存在！") {
            $whereBind = ['code' => $personCode,'bind_id' => ['gt', 0]];
            $bindInfo = D('Face_user_bind_device')->where($whereBind)->find();
            if (isset($bindInfo['bind_id'])) {
                $saveData = [
                    'bind_id' => -$bindInfo['bind_id'],
                ];
                D('Face_user_bind_device')->where($whereBind)->save($saveData);
            }
            return ['status'=>0,'errMsg'=>$res['errMsg'].'请重新同步'];
        }
        else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 人员开卡
     * @author lijie
     * @date_time 2021/12/07
     * @param int $personId
     * @param string $cardNumber
     * @param int $cardType
     * @param int $category
     * @param string $cardStatus
     * @param string $startDate
     * @param string $endDate
     * @param string $cardPassword
     * @param int $subSystems
     * @return array|int[]
     */
    public function open_batch($personId=0,$cardNumber='',$cardType=0,$category=0,$cardStatus='ACTIVE',$startDate='',$endDate='',$cardPassword='',$subSystems=1)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = [
            'authInfo'=>$authInfo,'personId'=>$personId,'cardNumber'=>$cardNumber,
            'cardType'=>$cardType,'category'=>$category,'cardStatus'=>$cardStatus,
            'startDate'=>$startDate,'endDate'=>$endDate,'cardPassword'=>$cardPassword,
            'subSystems'=>$subSystems
        ];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/openBatch', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/open_batch', 'Funs' => __FUNCTION__],'dahua/open_batch',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 新增开门计划
     * @author lijie
     * @date_time 2021/12/07
     * @param string $detail
     * @param string $memo
     * @param string $name
     * @param int $type
     * @return array|int[]
     */
    public function time_quan_tum($detail='',$memo='',$name='',$type=1)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'detail'=>$detail,'memo'=>$memo,'name'=>$name,'type'=>$type];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/timeQuanTum', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/time_quan_tum', 'Funs' => __FUNCTION__],'dahua/time_quan_tum',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1,'process_id'=>$res['data']];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 添加/修改卡片授权
     * @author lijie
     * @date_time 2021/12/07
     * @param string $cardNumber
     * @param int $timeQuantumId
     * @param array $cardPrivilegeDetails
     * @return array|int[]
     */
    public function door_authority_update($cardNumber='',$timeQuantumId=0,$cardPrivilegeDetails=[])
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'cardNumber'=>$cardNumber,'timeQuantumId'=>$timeQuantumId,'cardPrivilegeDetails'=>$cardPrivilegeDetails];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/doorAuthorityUpdate', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/doorAuthorityUpdate', 'Funs' => __FUNCTION__],'dahua/door_authority_update',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 查询卡片信息
     * @author lijie
     * @date_time 2021/12/09
     * @param string $personCode
     * @param string $cardNumber
     * @param string $cardStatus
     * @param int $pageNum
     * @param int $pageSize
     * @return array
     */
    public function card_info($personCode='',$cardNumber='',$cardStatus='',$pageNum=1,$pageSize=20)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'personCode'=>$personCode,'cardNumber'=>$cardNumber,'cardStatus'=>$cardStatus,'pageNum'=>$pageNum,'pageSize'=>$pageSize];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/cardInfo', $params);
        fdump_api(['params' => $params,'res' => $res,'line' => __LINE__,'funs' => 'service\DaHuaService/card_info', 'Funs' => __FUNCTION__],'dahua/card_info',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1,'data'=>$res['data']];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 修改卡片的服务周期
     * @author lijie
     * @date_time 2021/12/09
     * @param string $cardNumber
     * @param string $cardPassword
     * @param string $startDate
     * @param string $endDate
     * @param int $id
     * @return array|int[]
     */
    public function update_card($cardNumber='',$cardPassword='',$startDate='',$endDate='',$id=0)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'cardNumber'=>$cardNumber,'cardPassword'=>$cardPassword,'startDate'=>$startDate,'endDate'=>$endDate,'id'=>$id];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/updateCard', $params);
        fdump_api(['param' => $params,'res' => $res,'line' => __LINE__, 'func' => 'service\DaHuaService/updateCard', 'Funs' => __FUNCTION__],'dahua/update_card',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 刷卡记录查询
     * @author lijie
     * @date_time 2021/12/10
     * @param int $pageNum
     * @param int $pageSize
     * @param int $startSwingTime
     * @param int $endSwingTime
     * @param string $openResult
     * @param string $personCode
     * @return array
     */
    public function swing_card_record($pageNum=1,$pageSize=20,$startSwingTime=0,$endSwingTime=0,$openResult='',$personCode='')
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = [
            'authInfo'=>$authInfo,'pageNum'=>$pageNum,'pageSize'=>$pageSize,
            'startSwingTime'=>$startSwingTime,'endSwingTime'=>$endSwingTime,
            'openResult'=>$openResult,'personCode'=>$personCode
        ];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/swingCardRecord', $params);
        fdump_api(['param' => $params,'res' => $res,'line' => __LINE__, 'func' => 'service\DaHuaService/swingCardRecord', 'Funs' => __FUNCTION__],'dahua/swingCardRecord',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1,'data'=>$res['data']];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 认证记录查询
     * @author lijie
     * @date_time 2021/12/10
     * @param int $pageNum
     * @param int $pageSize
     * @param int $startTime
     * @param int $endTime
     * @param string $compareResult
     * @param string $personName
     * @return array
     */
    public function integrate_record($pageNum=1,$pageSize=20,$startTime=0,$endTime=0,$compareResult='',$personName='')
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = [
            'authInfo'=>$authInfo,'pageNum'=>$pageNum,'pageSize'=>$pageSize,
            'startTime'=>$startTime,'endTime'=>$endTime,'compareResult'=>$compareResult,
            'personName'=>$personName
        ];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/integrateRecord', $params);
        fdump_api(['param' => $params,'res' => $res,'line' => __LINE__, 'func' => 'service\DaHuaService/integrateRecord', 'Funs' => __FUNCTION__],'dahua/integrateRecord',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1,'data'=>$res['data']];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 取认证记录抓拍图片
     * @param int $id
     * @return array
     */
    public function get_face_photo_url($id=0)
    {
        $authInfo = invoke_v20_service('\app\community\model\service\DaHuaService/getToken');
        $authInfo = $authInfo['data']['retval'];
        $params = ['authInfo'=>$authInfo,'id'=>$id];
        $res = invoke_v20_service('\app\community\model\service\DaHuaService/getFacePhotoUrl', $params);
        fdump_api(['param' => $params,'res' => $res,'line' => __LINE__, 'func' => 'service\DaHuaService/getFacePhotoUrl', 'Funs' => __FUNCTION__],'dahua/getFacePhotoUrl',1);
        $res = $res['data']['retval'];
        if($res['success'] == true){
            return ['status'=>1,'data'=>$res['data']];
        }else{
            return ['status'=>0,'errMsg'=>$res['errMsg']];
        }
    }

    /**
     * 随机生成人员编号
     * @author lijie
     * @date_time 2021/12/07
     * @return string
     */
    public function create_code()
    {
        $_len = strlen($this->charset) - 1;
        $code = '';
        for ($i = 0; $i < 10; $i++) {
            $code .= $this->charset[mt_rand(0, $_len)];
        }
        if(M('Face_user_bind_device')->where(['code'=>$code])->find()){
            $this->create_code();
        }else{
            return $code;
        }
    }

    /**
     * 随机生成人员卡号
     * @author lijie
     * @date_time 2021/12/08
     * @return string
     */
    public function create_card_number()
    {
        $_len = strlen($this->card_num) - 1;
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $this->card_num[mt_rand(0, $_len)];
        }
        if(M('Face_user_bind_device')->where(['cardid'=>$code])->find()){
            $this->create_card_number();
        }else{
            return $code;
        }
    }

    /**
     * 把网络图片图片转成base64
     * @author lijie
     * @date_time 2021/12/04
     * @param string $img 图片地址
     * @return string
     */
    public function imgToBase64($img='')
    {
        $imageInfo = getimagesize($img);
        $base64 = "" . chunk_split(base64_encode(file_get_contents($img)));
        $base64_img = 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
        return $base64;
    }
    
}