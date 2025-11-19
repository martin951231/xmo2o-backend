<?php

/**
 * 店员后台打印设备
 * author by hengtingmei
 * Date Time: 2020/12/09 18:14
 */

namespace app\storestaff\controller\storestaff;

use app\merchant\model\service\print_order\OrderprintService;

class PrintDeviceController extends AuthBaseController
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * desc: 获取蓝牙打印设备接口
     * return :array
     */
    public function getBluetoothPrintList()
    {
        $where['store_id'] = $this->staffUser['store_id'];
        $where['print_type'] = 3;

        $appType = $this->request->param('app_type') ?? '0'; // 1-ios 2-android

        $deviceType = ($appType == 2 || $appType == 'android') ? 1 : 2; // 1-android 2-ios
        $where = [
            ['store_id', '=', $this->staffUser['store_id']],
            ['print_type', '=', 3],
        ];
        //        $deviceType && $where[] =  ['device_type', '=',$deviceType ];
        if ($appType == 'ios') {
            $where[] =  ['print_mobile_code', '<>', ''];
        } else {
            $where[] =  ['print_mobile_code', '=', ''];
        }
        $res = (new OrderprintService())->getList($where);
        return api_output(0, ['list' => $res]);
    }

    /**
     * desc: 查找当前打印机是否存在 没有则自动添加
     * return :array
     */
    public function addPrint()
    {
        $param = $this->request->param();
        $param['staff'] = $this->staffUser;
        $res = (new OrderprintService())->addPrintAutoByStaff($param);
        return api_output(0, []);
    }

    /**
     * desc: 打印轮询打印
     * return :array
     */
    public function ownPrintWork()
    {
        $param = $this->request->param();
        fdump($param,'debug',1);
        $param['staff'] = $this->staffUser;
        $res = (new OrderprintService())->ownPrintWork($param);
        return api_output(0, $res);
    }

    /**
     * desc: PC店员查看打印机是否存在
     * return :array
     */
    public function getPrintHas()
    {
        $mkey = $this->request->param('mkey');
        if (empty($mkey)) {
            throw new \think\Exception(L_('请携带密钥值'), 1001);
        }

        $condition['mkey']     = $mkey;
        $condition['store_id']    = $this->staffUser['store_id'];
        $res = (new OrderprintService())->getOne($condition);
        if ($res) {
            return api_output(0, ['status' => 1], L_('打印机存在'));
        } else {
            return api_output(0, ['status' => 0], L_('打印机不存在'));
        }
    }

    /**
     * desc: PC店员打印机打印
     * return :array
     */
    public function getOwnPrinter()
    {
        $mkey = $this->request->param('mkey');
        if (empty($mkey)) {
            throw new \think\Exception(L_('请携带密钥值'), 1001);
        }

        $res = (new OrderprintService())->getOwnPrinter($mkey);
        return api_output(0, ['info' => $res]);
    }
}
