<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      指纹锁指纹器等相关指纹设备 控制器
 */

namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\DeviceFingerprintService;

class DeviceFingerprintController extends CommunityBaseController
{
    /**
     * 获取指纹设备信息列表 支持条件搜索
     * @return \json
     */
    public function getFingerprintDeviceList() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page        = $this->request->post('page',1);
        $pageSize    = $this->request->post('pageSize',20);
        $device_name = $this->request->post('device_name','');
        $device_sn   = $this->request->post('device_sn','');
        $where = [];
        $where[] = ['village_id',  '=', $village_id];
        $where[] = ['delete_time', '=', 0];
        if ($device_name) {
            $where[] = ['device_name', 'like', '%' . $device_name . '%'];
        }
        if ($device_sn) {
            $where[] = ['device_sn', 'like', '%' . $device_sn . '%'];
        }
        try{
            $service_device_fingerprint = new DeviceFingerprintService();
            $field = true;
            $data  = $service_device_fingerprint->getFingerprintDeviceList($where, $field, $page, $pageSize, 'device_id DESC');
            $count = $service_device_fingerprint->getFingerprintDeviceCount($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res = [];
        $res['list']     = $data;
        $res['count']    = $count;
        $res['pageSize'] = $pageSize;
        return api_output(0,$res);
    }

    /**
     * 获取指纹锁设备相关设备品牌
     * @return \json
     */
    public function getFingerprintBrandList() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        try {
            $service_device_fingerprint = new DeviceFingerprintService();
            $brand_list = $service_device_fingerprint->getFingerprintBrandList();
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $res = [];
        $res['brand_list'] = $brand_list;
        return api_output(0, $res);
    }

    /**
     * 获取指纹锁设备相关设备品牌的系列
     * @return \json
     */
    public function getFingerprintBrandSeriesList() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $brand_id = $this->request->post('brand_id');
        try {
            $service_device_fingerprint = new DeviceFingerprintService();
            $brand_series_list = $service_device_fingerprint->getFingerprintBrandSeriesList($brand_id, 7);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $res = [];
        $res['brand_series_list'] = $brand_series_list;
        return api_output(0, $res);
    }

    /**
     * 添加编辑指纹锁
     * @return \json
     */
    public function addFingerprintDevice() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id        = $this->request->post('device_id', 0, 'intval');
        $brand_type       = $this->request->post('brand_type', '', 'intval');
        $brand_key        = $this->request->post('brand_key', '', 'trim');
        $brand_series     = $this->request->post('brand_series', '', 'intval');
        $brand_series_key = $this->request->post('brand_series_key', '', 'trim');
        $device_name      = $this->request->post('device_name', '', 'trim');
        $device_sn        = $this->request->post('device_sn', '', 'trim');
        $remark           = $this->request->post('remark', '', 'trim');
        $device_admin     = $this->request->post('device_admin', '', 'trim');
        $device_password  = $this->request->post('device_password', '', 'trim');
        $single_id        = $this->request->post('single_id', '', 'intval');
        $floor_id         = $this->request->post('floor_id', '', 'intval');
        $layer_id         = $this->request->post('layer_id', '', 'intval');
        $room_id          = $this->request->post('room_id', '', 'intval');
        $third_protocol   = $this->request->post('third_protocol', '', 'intval');
        $param = [
            'device_id'        => $device_id,
            'village_id'       => $village_id,
            'brand_type'       => $brand_type,
            'brand_key'        => $brand_key,
            'brand_series'     => $brand_series,
            'brand_series_key' => $brand_series_key,
            'device_name'      => $device_name,
            'device_sn'        => $device_sn,
            'remark'           => $remark,
            'device_admin'     => $device_admin,
            'device_password'  => $device_password,
            'single_id'        => $single_id,
            'floor_id'         => $floor_id,
            'layer_id'         => $layer_id,
            'room_id'          => $room_id,
            'mustPost'         => 1,
            'third_protocol'   => $third_protocol,
        ];
        try {
            $service_device_fingerprint = new DeviceFingerprintService();
            $data = $service_device_fingerprint->addFingerprintDevice($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取设备详情
     * @return \json
     */
    public function getFingerprintDeviceDetail() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id        = $this->request->post('device_id', 0, 'intval');
        try {
            $param = [
                'village_id' => $village_id
            ];
            $service_device_fingerprint = new DeviceFingerprintService();
            $data = $service_device_fingerprint->getFingerprintDeviceDetail($device_id, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 删除设备
     * @return \json
     */
    public function deleteDevice() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $device_id        = $this->request->post('device_id', 0, 'intval');
        try {
            $param = [
                'village_id' => $village_id
            ];
            $service_device_fingerprint = new DeviceFingerprintService();
            $data = $service_device_fingerprint->deleteFingerprintDevice($device_id, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取开门记录
     * @return \json
     */
    public function getHouseUserlog() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page        = $this->request->post('page',1);
        $pageSize    = $this->request->post('pageSize',20);
        $device_id   = $this->request->post('device_id', 0, 'intval');
        $date        = $this->request->post('date');
        $device_name = $this->request->post('device_name', '', 'trim');
        $device_sn   = $this->request->post('device_sn', '', 'trim');
        if (!empty($date) && isset($date[0]) && isset($date[1])) {
            $open_start_time = strtotime($date[0]) ? strtotime($date[0]) : '';
            $open_end_time   = strtotime($date[1]) ? strtotime($date[1]) : '';
        } else {
            $open_start_time = '';
            $open_end_time   = '';
        }
        try {
            $param = [
                'device_id'       => $device_id,
                'page'            => $page,
                'pageSize'        => $pageSize,
                'getCount'        => 1,
                'device_name'     => $device_name,
                'device_sn'       => $device_sn,
                'open_start_time' => $open_start_time,
                'open_end_time'   => $open_end_time,
            ];
            $service_device_fingerprint = new DeviceFingerprintService();
            $data = $service_device_fingerprint->getHouseUserlog($village_id, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取指纹相关详情
     * @return \json
     */
    public function getPersonFingerprintDetail() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $pigcms_id        = $this->request->post('pigcms_id', 0, 'intval');
        try {
            $param = [
                'village_id' => $village_id
            ];
            $service_device_fingerprint = new DeviceFingerprintService();
            $data = $service_device_fingerprint->getPersonFingerprintDetail($pigcms_id, $param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        $data['fingerprintImg']   = cfg('site_url').'/static/images/house/device/fingerprint.png';
        $data['tip_url'] = 'https://www.cloud-dahua.com/console/community/console/communityPersonManage';
        $data['tip']     = [
            '1. 登录后进入对应大华云睿后台；',
            '2. 【人员管理】>【人员档案】 下找到对应人员；',
            '3. 点击对应人员右上角【笔形】编辑按钮；',
            '4. 进入编辑人员页面信息加载完成后点击下面【认证信息】；',
            '5. 拉到最下面【添加指纹】，确认插上了指纹采集器，点击下面图标+部分按照提示操作进行录取指纹；',
        ];
        $data['tip_url_title']   = '点击跳转大华后台';
        return api_output(0, $data);
    }
}