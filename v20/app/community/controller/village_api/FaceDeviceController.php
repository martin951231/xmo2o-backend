<?php


namespace app\community\controller\village_api;


use app\common\model\service\image\ImageService;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseFaceImg;
use app\community\model\service\HouseFaceService;
use app\community\model\service\HouseUserTrajectoryLogService;
use app\community\model\service\HouseVillageUserBindService;
use app\consts\DeviceConst;
use file_handle\FileHandle;

class FaceDeviceController extends CommunityBaseController
{
    /**
     * 获取住户的图片信息
     * @return \json
     */
    public function capFaceInfo() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $pigcms_id = $this->request->post('pigcms_id');
        $limit     = $this->request->post('limit',1);// 暂定获取一条图片信息
        try{
            if ($pigcms_id) {
                $sHouseFaceService            = new HouseFaceService();
                $sHouseVillageUserBindService = new HouseVillageUserBindService();
                $where = [];
                $where[] = ['pigcms_id',  '=', $pigcms_id];
                $where[] = ['village_id', '=', $village_id];
                $faceInfo = $sHouseVillageUserBindService->getBindInfo($where, 'uid, face_img_status, face_img_reason');
                if ($faceInfo && !is_array($faceInfo)) {
                    $faceInfo = $faceInfo->toArray();
                }
                $uid = isset($faceInfo['uid']) && $faceInfo['uid'] ? $faceInfo['uid'] : -1;
                if (isset($faceInfo['face_img_status'])) {
                    $faceInfo = $sHouseFaceService->getFaceImgStatusTxt($faceInfo['face_img_status'], $faceInfo['face_img_reason']);
                }
                $list = $sHouseFaceService->getHouseUserFaceImgs($pigcms_id, $uid, $limit);
            } else {
                $list     = [];
                $faceInfo = [];
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $arr = [
            'list'     => $list,
            'faceInfo' => $faceInfo,
        ];
        return api_output(0,$arr);
    }
    private function base64_to_img($base_img, $id) {
        $img_mer_id = sprintf("%09d", $id);
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $up_dir = './upload/houseface/'.$rand_num.'/';
        if ($base_img) {
            $type = 'jpg';
            $filename=date('YmdHis_').uniqid().'.'.$type;
            $new_file = $up_dir.$filename;
            $up_dir_tmp=root_path().ltrim($up_dir,'.');
            $up_dir_tmp=str_replace('v20//','',$up_dir_tmp);
            if (!is_dir($up_dir_tmp)) {
                mkdir($up_dir_tmp, 0777, true);
            }
            $saveTmpPath =$up_dir_tmp.$filename;
            if(file_put_contents($saveTmpPath, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new FileHandle();
                if($file_handle->check_open_oss()){
                    $info = $file_handle->upload($saveTmpPath);
                    fdump_api(['info'=>$info],'00FaceDevice',1);
                }
                $img_path = $new_file;
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function uploadFaceImg() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $imageBase64 = $this->request->post('imageBase64');
        if (!$imageBase64) {
            return api_output(1001, [], '图片不能为空！');
        }
        $pigcms_id = $this->request->post('pigcms_id');
        if (!$pigcms_id) {
            return api_output(1001, [], '缺少住户身份！');
        }
        $sHouseVillageUserBindService = new HouseVillageUserBindService();
        $where = [];
        $where[] = ['pigcms_id',  '=', $pigcms_id];
        $where[] = ['village_id', '=', $village_id];
        $faceInfo = $sHouseVillageUserBindService->getBindInfo($where, 'pigcms_id,uid,type,phone,name,village_id');
        if ($faceInfo && !is_array($faceInfo)) {
            $faceInfo = $faceInfo->toArray();
        }
        if (!isset($faceInfo['pigcms_id'])) {
            return api_output(1001, [], '缺少住户身份！');
        }
        /*
        $img_mer_id = sprintf("%09d", $pigcms_id);
        $rand_num   = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $baseFile   = "/upload/houseface/".$rand_num.'/'; 
        $up_dir     = root_path() . '/..'.$baseFile;
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        $new_file = '.' . $baseFile . date('YmdHis_').uniqid().'.jpg';//统一默认转 jpg
        */
        $imageBase64Temp=str_replace('data:image/png;base64,', '', $imageBase64);
        $imageBase64Temp=str_replace('data:image/jpeg;base64,', '', $imageBase64);
        $url = $this->base64_to_img($imageBase64Temp,$pigcms_id);
        $new_file=$url;
        $savePath = root_path() . ltrim($new_file,'.');
        $savePath=str_replace('v20//','',$savePath);
        //$arr = getimagesize($imageBase64);

        $sImageService = new ImageService();
        $param = [
            'imgPath'      => $savePath,
            'savePath'     => $savePath,
            'format'       => 'jpg',  //统一默认转 jpg
            'heiht'        => DeviceConst::PC_UPLOAD_FACE_IMG_HIGH,
            'with'         => DeviceConst::PC_UPLOAD_FACE_IMG_WIDTH
        ];
        $imgToJpg = $sImageService->encodePngToJpg($param);
        if (!$imgToJpg) {
            return api_output(1003, [], '上传失败请重试！');
        }
        $img_path = trim($new_file,'.');
        $imageUrl = replace_file_domain($img_path);

        $sHouseFaceService            = new HouseFaceService();
        if (empty($faceInfo['uid'])) {
            $uid = $sHouseFaceService->getFaceToRegUserUid($faceInfo['phone'], $faceInfo['name'], $img_path);
            if (!$uid) {
                // 没有uid 生成或者说匹配上后记录下
                $whereUserBind   = [];
                $whereUserBind[] = ['pigcms_id',  '=', $faceInfo['pigcms_id']];
                $saveUserBind    = ['uid' => $uid];
                $sHouseVillageUserBindService->saveUserBind($whereUserBind, $saveUserBind);
            }
        } else {
            $uid = $faceInfo['uid'];
        }
        $id = $sHouseFaceService->addFaceImg($uid, $img_path);
        //todo 轨迹信息 用户上传家属或租客的人脸照片
        $houseUserTrajectoryLogService=new HouseUserTrajectoryLogService();
        $houseUserTrajectoryLogService->addLog(4, $village_id, 0, $uid, $pigcms_id, 2, '上传了人脸照片', '后台更新了人员数据');

        $ret = ['id' => $id, 'img_path' => $img_path, 'imageUrl' => $imageUrl,'savePath'=>$savePath];
        return api_output(0, $ret);
    }

    /**
     * 同步
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function synToDeviceUserImg() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $pigcms_id = $this->request->post('pigcms_id');
        if (!$pigcms_id) {
            return api_output(1001, [], '缺少住户身份！');
        }
        $sHouseVillageUserBindService = new HouseVillageUserBindService();
        $where = [];
        $where[] = ['pigcms_id',  '=', $pigcms_id];
        $where[] = ['village_id', '=', $village_id];
        $faceInfo = $sHouseVillageUserBindService->getBindInfo($where, 'pigcms_id,uid,type,phone,name,village_id');
        if ($faceInfo && !is_array($faceInfo)) {
            $faceInfo = $faceInfo->toArray();
        }
        if (!isset($faceInfo['pigcms_id'])) {
            return api_output(1001, [], '缺少住户身份！');
        }
        $sHouseFaceService            = new HouseFaceService();
        if (empty($faceInfo['uid'])) {
            $uid = $sHouseFaceService->getFaceToRegUserUid($faceInfo['phone'], $faceInfo['name']);
            if (!$uid) {
                // 没有uid 生成或者说匹配上后记录下
                $whereUserBind   = [];
                $whereUserBind[] = ['pigcms_id',  '=', $faceInfo['pigcms_id']];
                $saveUserBind    = ['uid' => $uid];
                $sHouseVillageUserBindService->saveUserBind($whereUserBind, $saveUserBind);
            }
        } else {
            $uid = $faceInfo['uid'];
        }
        if (!$uid) {
            return api_output(1001, [], '请先绑定为平台用户！');
        }
        try{
            $info = $sHouseFaceService->houseUserFaceDeviceInfo($uid, $pigcms_id, $village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($info && (!isset($info['err_msg']) || !$info['err_msg'])){
            $whereUserBind   = [];
            $whereUserBind[] = ['pigcms_id',  '=', $faceInfo['pigcms_id']];
            $saveUserBind    = [
                'face_img_status' => '0',
                'face_img_reason' => '',
                'add_face_time' => time(),
            ];;
            $sHouseVillageUserBindService->saveUserBind($whereUserBind, $saveUserBind);

            if (in_array($faceInfo['type'], [0, 3])) {
                $where = [];
                $where[] = ['parent_id',  '=', $pigcms_id];
                $where[] = ['village_id', '=', $village_id];
                $childrenList = $sHouseVillageUserBindService->getList($where, 'pigcms_id,uid,type,phone,name,village_id');
                if ($childrenList && !is_array($childrenList)) {
                    $childrenList = $childrenList->toArray();
                }
                if (!empty($childrenList)) {
                    foreach ($childrenList as $items) {
                        $sHouseFaceService->houseUserFaceDeviceInfo($items['uid'], $items['pigcms_id'], $village_id);
                    }
                }
            }
            
        } else {
            return api_output(1001, [], $info['err_msg']);
        }
        return api_output(0, ['msg' => '同步命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更']);
    }
    
    /**
     * 删除人脸图片
     */
    public function delFaceImg()
    {
        $param['id'] = $this->request->post('id');
        if(!$param['id']){
            return api_output(1003, [], '请选择需要删除的图片！');
        }
        (new HouseFaceImg())->where('id',$param['id'])->update(['status'=>1]);
        return api_output(0, $param['id']);
    }
}