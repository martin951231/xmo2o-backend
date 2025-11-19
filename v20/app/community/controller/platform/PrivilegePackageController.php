<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/8/6 16:01
 */

namespace app\community\controller\platform;


use app\community\model\service\PrivilegePackageService;
use app\community\controller\platform\AuthBaseController as BaseController;
class PrivilegePackageController extends BaseController
{
    public $servicePrivilegePackageService;
    public function initialize()
    {
        parent::initialize();
        $this->servicePrivilegePackageService = new PrivilegePackageService();
    }

    /**
     * Notes: 添加权限套餐
     * param
     * [
     *   'package_title' => '套餐标题',
     *   'package_try_days' => '套餐试用天数（必须大于0）',
     *   'package_price' => '套餐价格（年：366天）',
     *   'package_limit_num' => '套餐一次最多购买期限（单位：年）',
     *   'txt_des' => '文字说明（用于副标题介绍）',
     *   'room_num' => '套餐中所含房间',
     *   'status' => '状态 1 开启  2 关闭  4 删除',
     *   'sort' => '排序值 数字越大越靠前',
     *   'bind_arr' => [1,2,3,4] // 数组形式的，里面的id是对应的权限套餐可选功能id
     *   'package_id' => '套餐id' // 编辑的时候上传
     * ]
     * @return \json
     * @author: wanzy
     * @date_time: 2020/8/6 16:31
     */
    public function addPrivilegePackage() {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $package_title = $this->request->param('package_title', '', 'trim');
        if(empty($package_title)){
            return api_output_error(1001,'请填写套餐标题！');
        }
        $package_try_days = $this->request->param('package_try_days', 0, 'intval');
        if($package_try_days<=0){
            return api_output_error(1001,'请填写大于0的套餐试用天数！');
        }
        $package_limit_num = $this->request->param('package_limit_num', 0, 'intval');
        if($package_limit_num<=0){
            return api_output_error(1001,'请填写大于0的套餐一次最多购买期限！');
        }
        $package_price = $this->request->param('package_price', 0, 'floatval');
        $txt_des = $this->request->param('txt_des', '', 'trim');
        $room_num = $this->request->param('room_num', 0, 'intval');
        $status = $this->request->param('status', 1, 'intval');
        $sort = $this->request->param('sort', 0, 'intval');
        $details = $this->request->param('details', '', 'trim');
        $operate_uid = $this->_uid;

        $package_id = $this->request->param('package_id', 0, 'intval');
        $data = [
            'package_title' => $package_title,
            'package_try_days' => $package_try_days,
            'package_limit_num' => $package_limit_num,
            'package_price' => $package_price,
            'txt_des' => $txt_des,
            'room_num' => $room_num,
            'status' => $status==0?2:$status,
            'sort' => $sort,
            'operate_uid' => $operate_uid,
            'details'=>$details,
        ];
        $bind_arr = $this->request->param('bind_arr');
        if ($bind_arr) {
            $bind_data = $bind_arr;
        } else {
            $bind_data = [];
        }
        try {
            $data = $this->servicePrivilegePackageService->addPrivilegePackage($data, $bind_data, $package_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($package_id)
        {
            $msg = '编辑';
        }else{
            $msg = '添加';
        }
        if($data) {
            return api_output(0, $data, $msg."成功");
        }
        return api_output_error(-1, $msg."失败");
    }

    /**
     * Notes: 套餐详情
     * @return \json
     * @author: weili
     * @datetime: 2020/8/11 14:15
     */
    public function detailPrivilegePackage() {
        $package_id = $this->request->param('package_id', 0, 'intval');
//        if($package_id<=0){
//            return api_output_error(1001,'请上传套餐id！');
//        }
        if($package_id<=0){
            return api_output(0, '', "成功");
        }
        try {
            $info = $this->servicePrivilegePackageService->detailPrivilegePackage($package_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }

    /**
     * Notes: 获取套餐功能应用
     * @return \json
     * @author: weili
     * @datetime: 2020/8/11 14:25
     */
    public function getFunctionApplication()
    {
        try {
            $list = $this->servicePrivilegePackageService->getPrivilegePackageContent();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * Notes: 获取套餐功能列表
     * @return \json
     * @author: weili
     * @datetime: 2020/8/11 15:12
     */
    public function getList()
    {
        $page = $this->request->param('page','0','intval');
        $limit = 10;
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $where[] = ['status','<>',4];
        $field = 'package_id,package_title,package_try_days,package_price,room_num,status,sort,details';
        try {
            $list = $this->servicePrivilegePackageService->getPrivilegePackageList($where,$field,$page,$limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * Notes: 删除套餐
     * @return \json
     * @author: weili
     * @datetime: 2020/8/11 15:31
     */
    public function delPrivilegePackage()
    {
        $package_id = $this->request->param('package_id', 0, 'intval');
        if(!$package_id){
            return api_output_error(1001,'请上传套餐id！');
        }
        $where[] = ['package_id','=',$package_id];
        $data['status'] = 4;
        try {
            $res = $this->servicePrivilegePackageService->deletePrivilegePackage($where,$data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($res)
        {
            return api_output(0, '', "删除成功");
        }else{
            return api_output_error(-1, "删除失败");
        }

    }
}