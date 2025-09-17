<?php


namespace app\community\model\service;


use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\HouseFaceDevice;

class DaHuaService
{
//    public $dahuaUrl = 'http://192.168.0.72';
    public $dahuaUrl = 'http://60.173.195.178:9500';
    public $loginName = 'system'; // 账号
    public $loginPass = 'sys123456';// 密码
    public $type = 'dahua_device';

    public function __construct()
    {
        $this->dahuaUrl = cfg('dahua_url');
        $this->loginName = cfg('dahua_login_name');
        $this->loginPass = cfg('dahua_login_pass');
    }

    /**
     * 刷卡记录查询
     * @author lijie
     * @date_time 2021/12/06
     * @param array $authInfo
     * @param int $pageNum
     * @param int $pageSize
     * @param int $startSwingTime
     * @param int $endSwingTime
     * @param string $openResult
     * @param string $personCode
     * @return array|bool|mixed|string
     */
    public function swingCardRecord($authInfo=[],$pageNum=1,$pageSize=20,$startSwingTime=0,$endSwingTime=0,$openResult='',$personCode='')
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/swingCardRecord/bycondition/combined?userId=$userId&userName=$userName&token=$token";
        $postParams['pageNum'] = $pageNum;
        $postParams['pageSize'] = $pageSize;
        if($startSwingTime){
            $postParams['startSwingTime'] = $startSwingTime;
        }
        if($endSwingTime){
            $postParams['endSwingTime'] = $endSwingTime;
        }
        if($personCode){
            $postParams['personCode'] = $personCode;
        }
        if($openResult != ''){
            $postParams['openResult'] = $openResult;
        }
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 认证记录查询
     * @author lijie
     * @date_time 2021/12/06
     * @param array $authInfo
     * @param int $pageNum
     * @param int $pageSize
     * @param int $startTime
     * @param int $endTime
     * @param string $compareResult
     * @param string $personName
     * @return array|bool|mixed|string
     */
    public function integrateRecord($authInfo=[],$pageNum=1,$pageSize=20,$startTime=0,$endTime=0,$compareResult='',$personName='')
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/swingCardRecord/bycondition/combined?userId=$userId&userName=$userName&token=$token";
        $postParams['pageNum'] = $pageNum;
        $postParams['pageSize'] = $pageSize;
        if($startTime){
            $postParams['startTime'] = $startTime;
        }
        if($endTime){
            $postParams['endTime'] = $endTime;
        }
        if($personName){
            $postParams['personName'] = $personName;
        }
        if($compareResult != ''){
            $postParams['compareResult'] = $compareResult;
        }
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 获取认证记录抓拍图片
     * @param array $authInfo
     * @param int $id
     * @return array|bool|mixed|string
     */
    public function getFacePhotoUrl($authInfo=[],$id=0)
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/swingCardRecord/bycondition/combined?userId=$userId&userName=$userName&token=$token";
        $postParams['id'] = $id;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 查询门禁设备信息
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param string $deviceName
     * @param string $deviceIp
     * @param int $pageNum
     * @param int $pageSize
     * @return array|bool|mixed|string
     */
    public function getDoorDevice($authInfo=[],$deviceName='',$deviceIp='',$pageNum=1,$pageSize=20)
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/device/bycondition/combined?userId=$userId&userName=$userName&token=$token";
        $postParams['pageNum'] = $pageNum;
        $postParams['pageSize'] = $pageSize;
        if($deviceName){
            $postParams['deviceName'] = $deviceName;
        }
        if($deviceIp){
            $postParams['deviceIp'] = $deviceIp;
        }
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        if ($data&&isset($data['data'])&&isset($data['data']['pageData'])&&isset($data['data']['pageData'][0])) {
            $pageData = $data['data']['pageData'];
            $house_face_device = new HouseFaceDevice();
            foreach ($pageData as $item) {
                if (isset($item['deviceName'])&&isset($item['deviceIp'])) {
                    $where_save = [];
                    $where_save[] = ['device_name','=',$item['deviceName']];
                    $where_save[] = ['is_del','=',0];
                    $where_save[] = ['device_ip','=',$item['deviceIp']];
                    if ($item['deviceStatus']=='OFF') {
                        $device_status = 2;
                    } else {
                        $device_status = 1;
                    }
                    fdump_api(['item' => $item,'where_save' => $where_save,'line' => __LINE__,'funs' => 'service\DaHuaService/getDoorDevice', 'Funs' => __FUNCTION__],'dahua/getDoorDevice',1);
                    $house_face_device->saveData($where_save,['device_status' => $device_status,'last_time' => time()]);
                }
            }
        }
        return $data;
    }

    /**
     * 查询门禁通道信息
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param int $pageNum
     * @param int $pageSize
     * @return bool|string
     */
    public function getDoorChannel($authInfo=[],$pageNum=1,$pageSize=20)
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/channel/bycondition/combined?userId=$userId&userName=$userName&token=$token";
        $postParams['pageNum'] = $pageNum;
        $postParams['pageSize'] = $pageSize;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 设备开门
     * @author lijie
     * @date_time 2021/12/06
     * @param array $authInfo
     * @param array $channelCodeList
     * @return array|bool|mixed|string
     */
    public function openDoor($authInfo=[],$channelCodeList=[])
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/channelControl/openDoor?userId=$userId&userName=$userName&token=$token";
        $postParams['channelCodeList'] = $channelCodeList;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 设备关门
     * @author lijie
     * @date_time 2021/12/06
     * @param array $authInfo
     * @param array $channelCodeList
     * @return array|bool|mixed|string
     */
    public function closeDoor($authInfo=[],$channelCodeList=[])
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/channelControl/closeDoor?userId=$userId&userName=$userName&token=$token";
        $postParams['channelCodeList'] = $channelCodeList;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 添加部门
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param string $name 部门名称
     * @param string $description 部门描述
     * @param int $parentId 上级部门id
     * @return bool|string
     */
    public function addDepartment($authInfo=[],$name='',$description='',$parentId=1)
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/department?userId=$userId&userName=$userName&token=$token";
        $postParams['name'] = $name;
        $postParams['description'] = $description;
        $postParams['parentId'] = $parentId;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 删除部门信息
     * @author lijie
     * @date_time 2021/12/09
     * @param array $authInfo
     * @param int $department_id
     * @return array|bool|mixed|string
     */
    public function delDepartment($authInfo=[],$department_id=0)
    {
        if(!$authInfo || !$department_id)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/department/delete/$department_id?userId=$userId&userName=$userName&token=$token";
        $data = curlPost($url,json_encode([]),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 添加身份
     * @author lijie
     * @date_time 2021/10/04
     * @param array $authInfo
     * @param string $name 身份
     * @param int $subsidyAmount 补贴金额
     * @param int $isCashRecharge 现金充值
     * @param int $isMachineRecharge 充值机充值
     * @param string $description 描述
     * @return bool|string
     */
    public function addIdentity($authInfo=[],$name='',$subsidyAmount=0,$isCashRecharge=1,$isMachineRecharge=1,$description='')
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/person/personidentity?userId=$userId&userName=$userName&token=$token";
        $postParams['name'] = $name;
        $postParams['description'] = $description;
        $postParams['subsidyAmount'] = $subsidyAmount;
        $postParams['isCashRecharge'] = $isCashRecharge;
        $postParams['isMachineRecharge'] = $isMachineRecharge;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 删除身份
     * @author lijie
     * @date_time 2021/12/09
     * @param array $authInfo
     * @param array $identity_ids
     * @return array|bool|mixed|string
     */
    public function delIdentity($authInfo=[],$identity_ids=[])
    {
        if(!$authInfo || !$identity_ids)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/person/personidentity/delete?userId=$userId&userName=$userName&token=$token";
        $postParams['personIdentityIds'] = $identity_ids;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 添加人员
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param string $paperType 证件类型
     * @param string $paperNumber 证件号码
     * @param string $name 姓名
     * @param string $code 人员编号(必填8至10位纯数字或者字母)
     * @param int $deptId 所属部门id
     * @param string $sex 性别
     * @param string $birthday 生日
     * @param string $phone 手机号码
     * @param string $status 人员状态
     * @param int $personIdentityId 身份ID
     * @return bool|string
     */
    public function addPerson($authInfo=[],$paperType='身份证',$paperNumber='',$name='',$code='',$deptId=0,$sex='男',$birthday='2018-11-03',$phone='',$status='在职',$personIdentityId=0)
    {
        if(!$authInfo)
            return false;
        if(!$paperNumber || !$name || !$code || !$deptId || !$phone || !$personIdentityId)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/person?userId=$userId&userName=$userName&token=$token";
        $postParams = [];
        $postParams['name'] = $name;
        $postParams['paperType'] = $paperType;
        $postParams['paperNumber'] = $paperNumber;
        $postParams['code'] = $code;
        $postParams['deptId'] = $deptId;
        $postParams['sex'] = $sex;
        $postParams['birthday'] = $birthday;
        $postParams['phone'] = $phone;
        $postParams['status'] = $status;
        $postParams['personIdentityId'] = $personIdentityId;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    public function updatePerson($authInfo=[],$id=0,$paperType='身份证',$paperNumber='',$name='',$code='',$deptId=0,$sex='男',$birthday='2018-11-03',$phone='',$status='在职',$personIdentityId=0)
    {
        if(!$authInfo)
            return false;
        if(!$paperNumber || !$id || !$name || !$code || !$deptId || !$phone || !$personIdentityId)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/person/update?userId=$userId&userName=$userName&token=$token";
        $postParams = [];
        $postParams['id'] = $id;
        $postParams['name'] = $name;
        $postParams['paperType'] = $paperType;
        $postParams['paperNumber'] = $paperNumber;
        $postParams['code'] = $code;
        $postParams['deptId'] = $deptId;
        $postParams['sex'] = $sex;
        $postParams['birthday'] = $birthday;
        $postParams['phone'] = $phone;
        $postParams['status'] = $status;
        $postParams['personIdentityId'] = $personIdentityId;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    public function bycondition($authInfo=[],$phone='',$param=[]) {
        if(!$authInfo || !$phone) {
            fdump_api(['查询参数缺失' => $authInfo,'phone' => $phone,'funs' => 'service\DaHuaService/bycondition', 'Funs' => __FUNCTION__,'line' => __LINE__],'dahua/errbycondition',1);
            return false;
        }
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/person/bycondition/combined?userId=$userId&userName=$userName&token=$token";
        $postParams = [];
        $postParams['phone'] = $phone;
        if (isset($param['pageNum'])&&$param['pageNum']) {
            $postParams['pageNum'] = $param['pageNum'];
        } else {
            $postParams['pageNum'] = 1;
        }
        if (isset($param['pageSize'])&&$param['pageSize']) {
            $postParams['pageSize'] = $param['pageSize'];
        } else {
            $postParams['pageSize'] = 10;
        }
        if (isset($param['deptIdsString'])&&$param['deptIdsString']) {
            // 部门id 返回其下人员信息,包含子部门的人员信息
            $postParams['deptIdsString'] = $param['deptIdsString'];
        }
        if (isset($param['name'])&&$param['name']) {
            // 姓名
            $postParams['name'] = $param['name'];
        }
        if (isset($param['code'])&&$param['code']) {
            // 人员编号
            $postParams['code'] = $param['code'];
        }
        if (isset($param['cardNumber'])&&$param['cardNumber']) {
            // 卡号
            $postParams['cardNumber'] = $param['cardNumber'];
        }
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        fdump_api(['列表查询' => $url,'postParams' => $postParams,'data' => $data,'funs' => 'service\DaHuaService/bycondition', 'Funs' => __FUNCTION__,'line' => __LINE__],'dahua/bycondition',1);
        $rtData = [];
        if (isset($data['data'])&&isset($data['data']['pageData'])) {
            $pageData = $data['data']['pageData'];
            $dhUser = reset($pageData);
            fdump_api(['$dhUser' => $dhUser,'line' => __LINE__],'dahua/bycondition',1);
            if (isset($dhUser['id'])&&$dhUser['id']) {
                $userDetail = $this->byUserId($authInfo,$dhUser['id']);
                if ($userDetail&&isset($userDetail['data'])&&isset($userDetail['data']['personIdentityId'])) {
                    $dhUser['personIdentityId'] = $userDetail['data']['personIdentityId'];
                }
            }
        } else {
            $dhUser = [];
        }
        $rtData['dhUser'] = $dhUser;
        return $rtData;
    }

    public function byUserId($authInfo=[],$id=0) {
        if(!$authInfo || !$id) {
            fdump_api(['查询参数缺失' => $authInfo,'id' => $id,'funs' => 'service\DaHuaService/byUserId', 'Funs' => __FUNCTION__,'line' => __LINE__],'dahua/errbyUserId',1);
            return false;
        }
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/person/queryById?userId=$userId&userName=$userName&token=$token";
        $postParams = [];
        $postParams['id'] = $id;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    public function returnByNumber($authInfo=[],$cardNumber='') {
        if(!$authInfo || !$cardNumber) {
            return false;
        }
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/card/returnByNumber/{$cardNumber}?userId=$userId&userName=$userName&token=$token";
        $postParams['cardNumber'] = $cardNumber;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 删除人员
     * @author lijie
     * @date_time 2021/12/09
     * @param array $authInfo
     * @param array $person_ids
     * @return array|bool|mixed|string
     */
    public function delPerson($authInfo=[],$person_ids=[])
    {
        if(!$authInfo || !$person_ids) {
            return false;
        }
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/person/delete?userId=$userId&userName=$userName&token=$token";
        $postParams['personIds'] = $person_ids;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 人员图片上传
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param string $personCode 人员编号
     * @param string $base64file 人员头像（base64,照片大小需要现在在100K以内,否有可能下发设备失败）
     * @return bool|string
     */
    public function saveMobileBase64ImageToByte($authInfo=[],$personCode='',$base64file='')
    {
        if(!$authInfo)
            return false;
        if(!$personCode || !$base64file)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/common/saveMobileBase64ImageToByte?userId=$userId&userName=$userName&token=$token";
        $postParams['personCode'] = $personCode;
        $postParams['base64file'] = $base64file;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 人员开卡
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param int $personId 人员id
     * @param string $cardNumber 卡号（8位16进制0~9A~F）
     * @param int $cardType 卡类型（0/普通卡;1/VIP卡;2/来宾卡;3/巡逻卡;6/巡检卡;11/管理员卡）
     * @param int $category 卡类比（0/IC卡;1/有源RFIID;2/CPU卡）
     * @param string $cardStatus 卡状态（ACTIVE/激活;BLANK /空白;FROZEN/冻结;WITHDRAWN/注销）
     * @param string $startDate 开始日期
     * @param string $endDate 结束时间
     * @param string $cardPassword 卡密码
     * @param int $subSystems 开应用（1/门禁系统;3/消费系统;4/梯控系统;5/考勤系统;6/巡更系统）
     * @return bool|string
     */
    public function openBatch($authInfo=[],$personId=0,$cardNumber='',$cardType=0,$category=0,$cardStatus='ACTIVE',$startDate='',$endDate='',$cardPassword='',$subSystems=1)
    {
        if(!$authInfo)
            return false;
        if(!$personId || !$cardNumber || !$startDate || !$endDate || !$cardPassword)
            return false;
        $cardPassword = $this->publicEncrypt($cardPassword); //卡密码RSA加密
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/card/open/batch?userId=$userId&userName=$userName&token=$token";
        $postParams['personId'] = $personId;
        $postParams['cardNumber'] = $cardNumber;
        $postParams['cardType'] = $cardType;
        $postParams['category'] = $category;
        $postParams['cardStatus'] = $cardStatus;
        $postParams['startDate'] = $startDate;
        $postParams['endDate'] = $endDate;
        $postParams['cardPassword'] = $cardPassword;
        $postParams['subSystems'] = $subSystems;
        $postParams = json_encode(['objectList'=>[$postParams]],true);
        $data = curlPost($url,$postParams,45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 新增开门计划
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param string $detail 时段内容
     * @param string $memo
     * @param string $name 开门计划名称
     * @param int $type 时段类型（填1）
     * @return bool|string
     */
    public function timeQuanTum($authInfo=[],$detail='',$memo='',$name='',$type=1)
    {
        if(!$authInfo)
            return false;
        /*if(!$name || !$detail)
            return false;*/
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/timeQuantum?userId=$userId&userName=$userName&token=$token";
        $postParams['detail'] = $detail;
        $postParams['memo'] = $memo;
        $postParams['name'] = $name;
        $postParams['type'] = $type;
        $data = curlPost($url,'{
    "detail": "{\"monday\":[\"00:00-23:59\"],\"tuesday\":[\"00:00-23:59\"],\"wednesday\":[\"00:00-23:59\"],\"thursday\":[\"00:00-23:59\"],\"friday\":[\"00:00-23:59\"],\"saturday\":[\"00:00-23:59\"],\"sunday\":[\"00:00-23:59\"]}",
    "memo": "全天候可以开门",
    "name": "24小时开门",
    "type": 1
}',45,"Content-type: application/json;charset=utf-8");  //先默认24小时全天候开门
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 添加/修改卡片授权
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param string $cardNumber 卡号
     * @param int $timeQuantumId 开门计划id
     * @param array $cardPrivilegeDetails 卡权限细节数组
     * @return bool|string
     */
    public function doorAuthorityUpdate($authInfo=[],$cardNumber='',$timeQuantumId=0,$cardPrivilegeDetails=[])
    {
        if(!$authInfo)
            return false;
        if(!$cardNumber || !$timeQuantumId || !$cardPrivilegeDetails)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/doorAuthority/update?userId=$userId&userName=$userName&token=$token";
        $postParams['cardNumber'] = $cardNumber;
        $postParams['timeQuantumId'] = $timeQuantumId;
        $postParams['cardPrivilegeDetails'] = $cardPrivilegeDetails;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 添加门组
     * @author lijie
     * @date_time 2021/12/04
     * @param array $authInfo
     * @param string $name 门组名称
     * @param string $memo 备注
     * @param array $doorGroupDetail 门禁通道点编码数组
     * @return bool|string
     */
    public function addDoorGroup($authInfo=[],$name='',$memo='',$doorGroupDetail=[])
    {
        if(!$authInfo)
            return false;
        if(!$name || !$doorGroupDetail)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/accessControl/doorGroup?userId=$userId&userName=$userName&token=$token";
        $postParams['name'] = $name;
        $postParams['memo'] = $memo;
        $postParams['doorGroupDetail'] = $doorGroupDetail;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 卡片查询
     * @author lijie
     * @date_time 2021/12/08
     * @param array $authInfo
     * @param string $personCode
     * @param string $cardNumber
     * @param string $cardStatus
     * @param int $pageNum
     * @param int $pageSize
     * @return array|bool|mixed|string
     */
    public function cardInfo($authInfo=[],$personCode='',$cardNumber='',$cardStatus='',$pageNum=1,$pageSize=20)
    {
        if(!$authInfo)
            return false;
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/card/bycondition/combined?userId=$userId&userName=$userName&token=$token";
        $postParams['pageNum'] = $pageNum;
        $postParams['pageSize'] = $pageSize;
        if($personCode)
            $postParams['personCode'] = $personCode;
        if($cardNumber)
            $postParams['cardNumber'] = $cardNumber;
        if($cardStatus)
            $postParams['cardStatus'] = $cardStatus;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 修改卡片信息
     * @author lijie
     * @date_time 2021/12/08
     * @param array $authInfo
     * @param string $cardNumber
     * @param string $cardPassword
     * @param string $startDate
     * @param string $endDate
     * @param int $id
     * @return array|bool|mixed|string
     */
    public function updateCard($authInfo=[],$cardNumber='',$cardPassword='',$startDate='',$endDate='',$id=0)
    {
        if(!$authInfo)
            return false;
        if(!$cardPassword || !$cardNumber || !$startDate || !$endDate || !$id)
            return false;
        $cardPassword = $this->publicEncrypt($cardPassword); //卡密码RSA加密
        $userId = $authInfo['userId'];
        $userName = $authInfo['userName'];
        $token = $authInfo['token'];
        $url = $this->dahuaUrl."/CardSolution/card/card/update?userId=$userId&userName=$userName&token=$token";
        $postParams['cardNumber'] = $cardNumber;
        $postParams['cardPassword'] = $cardPassword;
        $postParams['startDate'] = $startDate;
        $postParams['endDate'] = $endDate;
        $postParams['id'] = $id;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 获取鉴权token，userId，userName
     * @author lijie
     * @date_time 2021/12/04
     * @return bool|mixed
     */
    public function getToken()
    {
        $db_access_token_common_expires = new AccessTokenCommonExpires();
        $access_token_info = $db_access_token_common_expires->getOne(['type'=>$this->type]);
        if (empty($access_token_info) || $access_token_info['access_token_expire'] < time() + 60) {
            $info = $this->getNewToken();
            return $info;
        } else {
            $info['token'] = $access_token_info['access_token'];
            $info['userId'] = $access_token_info['id'];
            $info['userName'] = $access_token_info['other_txt'];
            return $info;
        }
    }

    /**
     * 用于获取令牌token步骤中密码的加密,加密方式为RSA加密
     * @author lijie
     * @date_time 2021/12/04
     * @return bool|mixed
     */
    public function getPublicKey()
    {
        $url = $this->dahuaUrl . '/WPMS/getPublicKey';
        $postParams['loginName'] = $this->loginName;
        $res = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if (!is_array($res)) {
            $res = json_decode($res, true);
        }
        if ($res['success'] == true) {
            $publicKey = $res['publicKey'];
            return "-----BEGIN PUBLIC KEY-----\n".wordwrap($publicKey,64,"\n",true)."\n-----END PUBLIC KEY-----";
        } else {
            return false;
        }
    }

    /**
     * token不存在或者过期时重新获取
     * @author lijie
     * @date_time 2021/12/04
     * @return bool|mixed
     */
    public function getNewToken()
    {
        $loginPass = $this->publicEncrypt($this->loginPass);  //密码通过RSA加密
        $url = $this->dahuaUrl . '/WPMS/login';
        $postParams['loginName'] = $this->loginName;
        $postParams['loginPass'] = $loginPass;
        $res = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8"); //获取
        if (!is_array($res)) {
            $res = json_decode($res, true);
        }
        if ($res['success'] == 'true') {
            $db_access_token_common_expires = new AccessTokenCommonExpires();
            $access_token_info = $db_access_token_common_expires->getOne(['type'=>$this->type]);
            if (empty($access_token_info)) {
                $db_access_token_common_expires->addOne([
                    'access_token' => $res['token'],
                    'access_token_expire' => time() + 30 * 60,
                    'type' => $this->type,
                    'access_id' => $res['id'],
                    'other_txt' => $res['loginName']
                ]);
            } else {
                $db_access_token_common_expires->saveOne(['id'=>$access_token_info['id']],[
                    'access_token' => $res['token'],
                    'access_token_expire' => time() + 30 * 60,
                    'type' => $this->type,
                    'access_id' => $res['id'],
                    'other_txt' => $res['loginName']
                ]);
            }
            $info['token'] = $res['token'];
            $info['userId'] = $res['id'];
            $info['userName'] = $res['loginName'];
            return $info;
        } else {
            return false;
        }
    }

    /**
     * RSA加密
     * @author lijie
     * @date_time 2021/12/04
     * @param $data
     * @return string|void|null
     */
    public function publicEncrypt($data)
    {
        if (!is_string($data)) {
            return false;
        }
        return openssl_public_encrypt($data, $encrypted, $this->getPublicKey()) ? base64_encode($encrypted) : null;
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