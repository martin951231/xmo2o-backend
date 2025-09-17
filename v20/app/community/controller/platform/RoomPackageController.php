<?php
/**
 * 房间套餐相关
 * @author weili
 * @datetime 2020/8/13
 */

namespace app\community\controller\platform;

use app\community\model\service\RoomPackageService;
use app\community\controller\platform\AuthBaseController as BaseController;
class RoomPackageController extends BaseController
{
    /**
     * Notes: 获取房间套餐列表
     * @return \json
     * @author: weili
     * @datetime: 2020/8/13 10:57
     */
    public function getList()
    {
        $page = $this->request->param('page','0','intval');
        $limit = 10;
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $serviceRoomPackage = new RoomPackageService();
        $where[] = ['status','<>','-1'];
        $order = 'room_id desc';
        $field = 'room_id,room_title,room_count,room_price,sort,status,create_time';
        try {
            $data = $serviceRoomPackage->getRoomPackageList($where,$field,$order,$page,$limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $data, "成功");
    }

    /**
     * Notes: 添加或编辑房间套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/13 11:27
     */
    public function addRoomPackage()
    {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $room_title = $this->request->param('room_title', '', 'trim');
        if(empty($room_title)){
            return api_output_error(1001,'请填写房间套餐标题！');
        }
        $room_count = $this->request->param('room_count', 0, 'intval');
        if($room_count<=0){
            return api_output_error(1001,'请填写大于房间数量！');
        }
        $room_price = $this->request->param('room_price', '', 'trim');
        if($room_price<=0){
            return api_output_error(1001,'请填写大于0的套餐价格！');
        }
        $status = $this->request->param('status', 0, 'intval');
        $sort = $this->request->param('sort', 0, 'intval');
        $room_id = $this->request->param('room_id', 0, 'intval');
        $data=[
            'room_title'=>$room_title,
            'room_count'=>$room_count,
            'room_price'=>$room_price,
            'status'=>$status,
            'sort'=>$sort,
            'create_time'=>time(),
            'create_uid'=>$this->_uid,
        ];
        $serviceRoomPackage = new RoomPackageService();
        try {
            $data = $serviceRoomPackage->saveRoomPackage($data,$room_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($room_id)
        {
            $msg = '编辑';
        }else{
            $msg = '添加';
        }
        if($data){
            return api_output(0, $data, $msg."成功");
        }else{
            return api_output_error(-1, $msg."失败");
        }

    }

    /**
     * Notes: 获取房间套餐详情
     * @return \json
     * @author: weili
     * @datetime: 2020/8/13 11:27
     */
    public function getDetails()
    {
        $room_id = $this->request->param('room_id', 0, 'intval');
        if(!$room_id)
        {
            return api_output_error(1001,'请上传房间套餐id！');
        }
        $where[] = ['room_id','=',$room_id];
        $serviceRoomPackage = new RoomPackageService();
        try{
            $info = $serviceRoomPackage->detailsRoomPackage($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($info)
        {
            return api_output(0, $info, "成功");
        }else{
            return api_output_error(-1, "失败");
        }
    }

    /**
     * Notes:删除房间套餐  （软删除）
     * @return \json
     * @author: weili
     * @datetime: 2020/8/13 11:31
     */
    public function delRoomPackage()
    {
        $room_id = $this->request->param('room_id', 0, 'intval');
        if(!$room_id)
        {
            return api_output_error(1001,'请上传房间套餐id！');
        }
        $where[] = ['room_id','=',$room_id];
        $data=[
            'status'=>'-1'
        ];
        $serviceRoomPackage = new RoomPackageService();
        try{
            $res = $serviceRoomPackage->deleteRoomPackage($where,$data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res)
        {
            return api_output(0, $res, "删除成功");
        }else{
            return api_output_error(-1, "删除失败");
        }
    }
}