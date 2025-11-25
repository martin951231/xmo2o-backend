<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      人脸图片相关
 */

namespace app\community\model\service;

use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseFaceDeviceLog;
use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseVillageUserBind;
use app\common\model\service\UserService as CommonUserService;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\User;
use app\consts\DeviceConst;
use face\wordencryption;

class HouseFaceService
{
    // 	同步人脸状态 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
    public function getFaceImgStatusTxt($face_img_status, $face_img_reason = '') {
        $face_img_status_txt = '未操作';
        switch ($face_img_status) {
            case 0:
                $face_img_status_txt = '同步中';// 这里后面完善后要改成未操作
                $face_img_reason     = '';
                break;
            case 1:
                $face_img_status_txt = '同步成功';
                $face_img_reason     = '';
                break;
            case 2:
                $face_img_status_txt = '同步失败';
                break;
            case 5:
                $face_img_status_txt = '同步中';
                $face_img_reason     = '';
                break;
        }
        return [
            'face_img_status'     => $face_img_status,
            'face_img_status_txt' => $face_img_status_txt,
            'face_img_reason'     => $face_img_reason,
        ];
    }

    /**
     * 获取对应人员人脸图片信息
     * @param int $pigcms_id
     * @param int $uid
     * @param int $limit
     * @param int $page
     * @return array|\think\Collection
     */
    public function getHouseUserFaceImgs($pigcms_id = 0, $uid = 0, $limit = 1, $page = 1) {
        if (!$pigcms_id && !$uid) {
            return [];
        }
        if ($uid == -1) {
            return [];
        }
        $whereFaceImg = [];
        $whereFaceImg[] = ['status',  'in', [0, 3]];
        $whereFaceImg[] = ['img_url', '<>', ''];
        if (!$uid) {
            $db_user_bind     = new HouseVillageUserBind();
            $whereUserBind    = [];
            $whereUserBind[]  = ['pigcms_id', '=', $pigcms_id];
            $uidArr = $db_user_bind->getOneColumn($whereUserBind, 'uid');
            $whereFaceImg[] = ['uid', 'in', $uidArr];
        } else {
            $whereFaceImg[] = ['uid', '=', $uid];
        }
        $list = $this->getHouseFaceList($whereFaceImg,'id,img_url,uid,status,add_time', 'id DESC', $page, $limit);
        $wordEncryption    = new wordencryption();
        if (!empty($list)) {
            foreach ($list as &$item) {
                if (3 == $item['status']) {
                    // 加密的图片解密
                    $item['img_url'] = $wordEncryption->text_decrypt($item['img_url']);
                }
                $item['img_url'] = replace_file_domain($item['img_url']);
            }
        }
        return $list;
    }

    /**
     * 获取人脸图片列表
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $limit
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseFaceList($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $dbHouseFaceImg = new HouseFaceImg();
        $list = $dbHouseFaceImg->getList($where, $field, $order, $page, $limit);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 根据手机号和名称自动匹配创建平台用户 返回对应uid
     * @param string $phone
     * @param string $name
     * @param string $avatar
     * @param string $source
     * @return int|mixed
     */
    public function getFaceToRegUserUid($phone = '', $name = '', $avatar = '', $source = 'houseautoreg') {
        if (!$phone && !$name) {
            return 0;
        }
        $server_common_user = new CommonUserService();
        if (!$phone && $name) {
            // 添加名称为主的平台用户
            $add_user = [
                'nickname' => $name,
                'avatar'   => $avatar,
                'add_time' => time(),
                'source'   => $source,
            ];
        } else {
            $db_user = new User();
            $wherePhone = [];
            $wherePhone[] = ['phone', '=', $phone];
            $wherePhone[] = ['status', 'in', [0, 1]];
            $userInfo = $db_user->getOne($wherePhone,'uid');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            if (isset($userInfo['uid'])) {
                return $userInfo['uid'];
            }
            // 添加手机号为主的平台用户
            $add_user = [
                'phone'    => $phone,
                'avatar'   => $avatar,
                'add_time' => time(),
                'source'   => $source,
            ];
        }
        $reg_result = $server_common_user->autoReg($add_user,false,true);
        if (isset($reg_result['uid']) && intval($reg_result['uid']) > 0) {
            $uid = $reg_result['uid'];
        } else {
            $uid = 0;
        }
        return $uid;
    }

    /**
     * 添加人脸图片
     * @param $uid
     * @param $img_url
     * @return false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addFaceImg($uid, $img_url) {
        if (!$img_url) {
            return false;
        }
        $data = [];
        $data['uid'] = $uid;
        $wordEncryption    = new wordencryption();
        $imgUrl = $wordEncryption->text_encryption($img_url);
        if ($imgUrl) {
            $data['status'] = 3;
            $img_url = $imgUrl;
        } else {
            $data['status'] = 0;
        }
        $dbHouseFaceImg = new HouseFaceImg();
        $whereFaceNum = [];
        $whereFaceNum[] = ['uid',    '=', $uid];
        $whereFaceNum[] = ['status', 'in', [0,3]];
        $face_num = $dbHouseFaceImg->getCount($whereFaceNum);
        $data['img_url'] = $img_url;
        $data['add_time'] = time();
        $id = $dbHouseFaceImg->addOne($data);
        $judgeNum = DeviceConst::USER_MAX_FACE_IMG_NUM;
        if (!$judgeNum) {
            $judgeNum = 3;
        }
        if ($id) {
            $judgeNum = $judgeNum - 1;
        }
        if ($id && $face_num > $judgeNum) {
            $whereFaceDel = [];
            $whereFaceDel[] = ['uid',    '=', $uid];
            $whereFaceDel[] = ['status', 'in', [0,3]];
            $limit = $face_num - $judgeNum;
            $list = $this->getHouseFaceList($whereFaceDel,'id,img_url,uid,status,add_time', 'id ASC', 1, $limit);
            $idArr = [];
            foreach ($list as $item) {
                $idArr[] = $item['id'];
            }
            if (!empty($idArr)) {
                // 超出限定部分 隐藏图片
                $whereSave = [];
                $whereSave[] = ['id', 'in', $idArr];
                $saveDel = [
                    'status' => 1,
                    'del_time' => time(),
                ];
                $dbHouseFaceImg->saveOne($whereSave, $saveDel);
            }
        }
        return $id;
    }

    /**
     * 同步人脸 只支持计划任务 目前
     * @param $uid
     * @param int $pigcms_id
     * @param int $village_id
     * @param bool $synOut
     * @param array $otherParam
     * @return array|bool
     */
    public function houseUserFaceDeviceInfo($uid, $pigcms_id = 0, $village_id = 0, $synOut = true, $otherParam = [])
    {
        if (!$uid && !$pigcms_id) {
            return true;
        }
        $planParam = [
            'synOut' => $synOut,
        ];
        $unique_id = 'subUserToDevice';
        if ($uid) {
            $planParam['uid'] = $uid;
            $unique_id .= '_uid' . $uid;
        }
        if ($pigcms_id) {
            $planParam['pigcms_id'] = $pigcms_id;
            if (!$uid) {
                $unique_id .= '_pigcmsId' . $pigcms_id;
            }
        }
        $orderGroupId = isset($otherParam['orderGroupId']) && $otherParam['orderGroupId'] ? trim($otherParam['orderGroupId']) : md5(uniqid() . $_SERVER['REQUEST_TIME']);// 标记统一执行命令
        if ($orderGroupId) {
            $planParam['orderGroupId'] = $orderGroupId;
        }
        $now_time = time();
        $arr = [];
        $arr['param']       = serialize($planParam);
        $arr['plan_time']   = -9999;
        // 优先级暂定为高
        $arr['space_time']  = 0;
        $arr['add_time']    = $now_time;
        $arr['file']        = 'sub_user_to_face_device';
        $arr['time_type']   = 1;
        $arr['unique_id']   = $unique_id;
        $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
        $dbProcessSubPlan = new ProcessSubPlan();
        $task_id = $dbProcessSubPlan->add($arr);

        try{
            $dbHouseFaceDevice = new HouseFaceDevice();
            $whereFaceDevice = [];
            $whereFaceDevice[] = ['is_del', '=', 0];
            $whereFaceDevice[] = ['thirdProtocol', '>', 0];
            $deviceCount = $dbHouseFaceDevice->getCount($whereFaceDevice);
            if ($deviceCount > 0) {
                $sFaceDeviceService = new FaceDeviceService();
                $sFaceDeviceService->userSynDevice($pigcms_id, $village_id);
            }
        }catch (\Exception $e){
            
        }

        if (isset($otherParam['log_name'])&&$otherParam['log_name']) {
            $log_name = $otherParam['log_name'];
        } else {
            $log_name = 'AdminUserToDevicePlan';
        }
        if (isset($otherParam['logType'])&&$otherParam['logType']) {
            $logType = $otherParam['logType'];
        } else {
            $logType = 'houseAdmin';
        }
        if (isset($otherParam['operator_type'])&&$otherParam['operator_type']) {
            $operator_type = $otherParam['operator_type'];
            $log_content = '用户同步数据至设备计划任务';
        } else {
            $operator_type = 'house_admin';
            $log_content = '管理员操作用户同步数据至设备计划任务';
        }
        if (isset($otherParam['log_operator'])&&$otherParam['log_operator']) {
            $log_operator = $otherParam['log_operator'];
        } else {
            $log_operator = '';
        }
        if (isset($otherParam['operator_id'])&&$otherParam['operator_id']) {
            $operator_id = $otherParam['operator_id'];
        } elseif ($operator_type=='user' && $pigcms_id) {
            $operator_id = $pigcms_id;
        } else {
            $operator_id = 0;
        }
        if (isset($otherParam['log_phone'])&&$otherParam['log_phone']) {
            $log_phone = $otherParam['log_phone'];
        } else {
            $log_phone = '';
        }
        $logParam = [
            'log_name' => $log_name,'logType' => $logType,'log_status' => 101,'orderGroupId' => $orderGroupId,
            'log_content' => $log_content,'log_uid' => $uid?$uid:0,'bind_id' => $pigcms_id?$pigcms_id:0,
            'log_operator' => $log_operator,'operator_id' => $operator_id,'log_phone' => $log_phone,
            'uid' => $uid?$uid:0,'addTime' => time(),
        ];
        try {
            // todo 写入日志操作未迁移
            $dbHouseFaceDeviceLog = new HouseFaceDeviceLog();
//            D('House_face_device_log')->addLog($logParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[设备添加权限组计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        return array('task_id'=>$task_id);
    }
}