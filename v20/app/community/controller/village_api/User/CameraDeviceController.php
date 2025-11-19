<?php


namespace app\community\controller\village_api\User;


use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\iSecureCameraService;

class CameraDeviceController extends CommunityBaseController
{
    /**
     * 小区视频监控列表
     * @author lijie
     * @date_time 2022/01/11
     * @return \json
     */
    public function getCameraList()
    {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id)
            return api_output_error(1001,'缺少必传参数');
        $service_house_face_device = new HouseFaceDeviceService();
        $where['village_id'] = $village_id;
        $where['camera_status'] = 0;
        $field = 'camera_id,floor_id,public_area_id,camera_name';
        $page = $this->request->post('page',1);
        $limit = $this->request->post('page',15);
        $order = 'camera_id DESC';
        try{
            $data = $service_house_face_device->getCameraList($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取视频监控申请信息
     * @author lijie
     * @date_time 2022/01/11
     * @return \json
     */
    public function getCameraReplyInfo()
    {
        $village_id = $this->request->post('village_id',0);
        $camera_id = $this->request->post('camera_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$village_id || !$camera_id || !$pigcms_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $where['village_id'] = $village_id;
        $where['pigcms_id'] = $pigcms_id;
        $where['camera_id'] = $camera_id;
        $field = true;
        try{
            $data = $service_house_face_device->getCameraReplyInfo($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 住户信息
     * @author lijie
     * @date_time 2022/01/11
     * @return \json
     */
    public function getUserInfo()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $village_id = $this->request->post('village_id',0);
        $camera_id = $this->request->post('camera_id',0);
        if(!$pigcms_id || !$village_id || !$camera_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $where['pigcms_id'] = $pigcms_id;
        $where['village_id'] = $village_id;
        try{
            $camera_info = $service_house_face_device->getCameraInfo(['camera_id'=>$camera_id]);
            if(empty($camera_info)){
                return api_output_error(1001,'参数错误');
            }
            $data = $service_house_face_device->getUserInfo($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 添加视频监控申请
     * @author lijie
     * @date_time 2022/01/11
     */
    public function addCameraReply()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $reply_reason = $this->request->post('reply_reason');
        $village_id = $this->request->post('village_id','');
        $camera_id = $this->request->post('camera_id',0);
        $start_time = $this->request->post('start_time',0);
        $end_time = $this->request->post('end_time',0);
        if(!$pigcms_id || empty($reply_reason) || !$village_id || !$camera_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        try{
            $village_info = $service_house_face_device->getVillageInfo(['village_id'=>$village_id],'is_limit_date');
            if(isset($village_info['is_limit_date']) && $village_info['is_limit_date'] == 1){
                if(!$start_time || !$end_time){
                    return api_output_error(1001,'缺少开始时间或结束时间');
                }
            }
            $reply_info = $service_house_face_device->getCameraReplyDetail(['camera_id'=>$camera_id,'pigcms_id'=>$pigcms_id,'reply_status'=>1]);
            if($reply_info){
                return api_output_error(1001,'您已经申请过了！');
            }
            $bind_info = $service_house_village_user_bind->getBindInfo(['pigcms_id'=>$pigcms_id],'name,phone');
            $res = $service_house_face_device->addCaremaReply([
                'village_id'=>$village_id,
                'pigcms_id'=>$pigcms_id,
                'name'=>$bind_info['name'],
                'phone'=>$bind_info['phone'],
                'camera_id'=>$camera_id,
                'reply_status'=>1,
                'reply_reason' => $reply_reason,
                'start_time'=>$start_time?strtotime($start_time):0,
                'end_time'=>$end_time?strtotime($end_time):0,
                'add_time'=>time()
            ]);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res){
            return api_output(0,[]);
        } else {
            return api_output_error(1001,'申请失败请重试！');
        }
    }

    /**
     * 用户端预览视频监控
     * @author lijie
     * @date_time 2022/01/12
     * @return \json
     */
    public function previewURLs()
    {
        $village_id = $this->request->post('village_id',0);
        $camera_id = $this->request->post('camera_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$village_id || !$camera_id || !$pigcms_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $service_i_secure_camera = new iSecureCameraService();
        $where['village_id'] = $village_id;
        $where['pigcms_id'] = $pigcms_id;
        $where['camera_id'] = $camera_id;
        $field = true;
        try{
            $res = $service_house_face_device->getCameraReplyInfo($where,$field);
            if($res['status'] != 2){
                return api_output_error(1001,'您无权限查看');
            }
            $camera_info = $service_house_face_device->getCameraInfo(['camera_id'=>$camera_id]);
            if(empty($camera_info)){
                return api_output_error(1001,'参数错误');
            }
            $data = $service_i_secure_camera->previewURLs($camera_info['camera_sn']);
            if(!$data){
                return api_output_error(1001,'视频获取异常');
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

}