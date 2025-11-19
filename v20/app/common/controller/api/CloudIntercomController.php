<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      云对讲 用户端获取参数或功能接口
 */

namespace app\common\controller\api;

use app\community\model\service\Device\DeviceHkNeiBuHandleService;

class CloudIntercomController extends ApiBaseController
{
    /**
     * 获取可视对讲对应参数.
     */
    public function getVisualParam() {
        $device_id = $this->request->param('device_id', 0, 'intval');
        $device_sn = $this->request->param('device_sn', '', 'trim');
        if (!$device_sn || !$device_id) {
            $this->returnCodeError('设备参数异常');
        }
        try {
            $info = (new DeviceHkNeiBuHandleService())->getVisualParam($device_id, $device_sn,$this->uid);
            return api_output(0, $info, 'success');
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
    
    public function offVideoEncrypt() {
        $device_id = $this->request->param('device_id', 0, 'intval');
        $device_sn = $this->request->param('device_sn', '', 'trim');
        if (!$device_sn || !$device_id) {
            $this->returnCodeError('设备参数异常');
        }
        $type = $this->request->param('device_id', 0, 'intval');
    }

}