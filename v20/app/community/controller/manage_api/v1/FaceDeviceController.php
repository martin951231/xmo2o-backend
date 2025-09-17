<?php


namespace app\community\controller\manage_api\v1;


use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseFaceDeviceService;

class FaceDeviceController extends CommunityBaseController
{
    /**
     * 人脸识别门禁
     * @author lijie
     * @date_time 2020/08/18 14:52
     * @return \json
     */
    public function getFaceDevice()
    {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id)
            return api_output_error(1001,'缺少必传参数');
        $house_fave_device = new HouseFaceDeviceService();
        $where['village_id'] = $village_id;
        $where['is_del'] = 0;
        $field = 'device_name,public_area_id,floor_id,device_id';
        $data = $house_fave_device->getDeviceLists($where,$field,1,'device_id desc');
        return api_output(0,$data,'获取成功');
    }

    /**
     * 人脸识别开门记录
     * @author lijie
     * @date_time 2020/08/18 15:16
     * @return \json
     */
    public function faceDeviceOpenDoorRecord()
    {
        $device_id = $this->request->post('device_id',0);
        if(!$device_id)
            return api_output_error(1001,'缺少必传参数');
        $con = $this->request->post('con','');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $where[] = ['l.device_id','=',$device_id];
        $where[] = ['l.log_from','=',1];
        if($con)
            $where[] = ['ub.phone|ub.name','like','%'.$con.'%'];
        $field = 'ub.phone,ub.name,l.log_status,ub.address,l.log_time';
        $order='l.log_id desc';
        $house_face_device = new HouseFaceDeviceService();
        $data = $house_face_device->faceDeviceOpenDoorRecord($where,$field,$page,$limit,$order);
        return api_output(0,$data,'获取成功');
    }
}