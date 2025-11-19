<?php
/**
 * 扫码领积分接口
 */

namespace app\scan\controller\api;
use app\common\model\service\CacheSqlService;
use app\scan\model\service\ScanSendService;
use app\scan\model\service\ScanSendRecordService;
use app\common\model\service\userService;

class ScanController extends ApiBaseController
{

    //扫二维码跳转接口
    public function getScan() {
        $id = $this->request->param('id', 0, 'intval');
        $ewm_no = $this->request->param('ewm_no', '');
        $time = time();

        if (!$ewm_no) {
            return api_output_error(1001, '非法操作!');
        }
        if(empty($this->_uid)){//新用户跳转注册页面执行注册逻辑
            return api_output_error(1002, '当前接口需要登录');
        }

        //扫码领取
        $record = (new ScanSendRecordService)->getData(['send_id' => $id, 'ewm_no' => $ewm_no]);
        if(empty($record)){
            return api_output_error(1001, '卡密不存在');
        }
        if ($record['uid'] > 0) {//已领取
            return api_output(1301, $scanData['ad_url'] ?? cfg('site_url') . '/packapp/plat/pages/plat_menu/index');
        }

        $param = [['id', '=', $id], ['status', '=', 1], ['end_time', '>', $time]];
        $scanData = (new ScanSendService)->getData($param);
        if (!$scanData) {
            return api_output_error(1001, '活动已结束!');
        }
        if ($scanData['start_time'] > $time) { //活动未开始，敬请期待
            return api_output_error(1001, '活动未开始，敬请期待!');
        }
        
        $note = $scanData->name;
        try {
            if ($scanData['balance_deno'] > 0) { //增加用户余额
                (new userService)->ScanGiveDeno($this->_uid, 1, $scanData['balance_deno'], $note);
            }
            if ($scanData['score_deno'] > 0) { //增加用户积分
                (new userService)->ScanGiveDeno($this->_uid, 2, $scanData['score_deno'], $note);
            }
            (new ScanSendService)->getNumInc($id, 1);
            $data = [
                'uid' => $this->_uid,
                'balance' => $scanData['balance_deno'],
                'score' => $scanData['score_deno'],
                'get_time' => $time
            ];
            (new ScanSendRecordService)->saveData($record['id'], $data);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, [], '领取成功');
    }

    /**
     * 卡密兑换
     *
     * @return void
     * @author: zt
     * @date: 2022/09/02
     */
    public function cardExchange()
    {
        $ewm_no = $this->request->param('ewm_no', '');
        $time = time();
        if (!$ewm_no) {
            return api_output_error(1001, '非法操作!');
        }
        if (empty($this->_uid)) { //新用户跳转注册页面执行注册逻辑
            return api_output_error(1002, '当前接口需要登录');
        }

        $nowUser = (new \app\common\model\service\UserService())->getUser($this->_uid);
        if (empty($nowUser)) {
            return api_output_error(1002, '当前接口需要登录');
        }
        if (empty($nowUser['phone'])) {
            $result =  [
                'success_msg' => '请先绑定手机号之后再进行领取操作',
                'left_btn' => ['text' => '暂不领取', 'href' => ''],
                'right_btn' => ['text' => '去绑定', 'href' => get_base_url('pages/my/newnumber')],
            ];
            return api_output(0, $result, '请先绑定手机号之后再进行领取操作');
        }

        //输入卡密兑换
        $record = (new ScanSendRecordService)->getData(['ewm_no' => $ewm_no]);
        if (empty($record)) {
            return api_output_error(1001, '卡密不存在');
        }
        if ($record['uid'] > 0) { //已领取
            return api_output_error(1001, '卡密已兑换或失效');
        }

        $id = $record->send_id;
        $param = [['id', '=', $id], ['status', '=', 1], ['end_time', '>', $time]];
        $scanData = (new ScanSendService)->getData($param);
        if (!$scanData) {
            return api_output_error(1001, '活动已结束!');
        }
        if ($scanData['start_time'] > $time) { //活动未开始，敬请期待
            return api_output_error(1001, '活动未开始，敬请期待!');
        }

        $note = $scanData->name;
        try {
            if ($scanData['balance_deno'] > 0) { //增加用户余额
                (new userService)->ScanGiveDeno($this->_uid, 1, $scanData['balance_deno'], $note);
                $successMsg = "已成功领取" . $scanData['balance_deno'] . "元";
            }
            if ($scanData['score_deno'] > 0) { //增加用户积分
                (new userService)->ScanGiveDeno($this->_uid, 2, $scanData['score_deno'], $note);
                $successMsg = "已成功领取" . $scanData['score_deno'] . "积分";
            }

            if ($scanData['balance_deno'] > 0 && $scanData['score_deno'] > 0) {
                $successMsg = "已成功领取" . $scanData['balance_deno'] . "元和" . $scanData['score_deno'] . "积分";
            }

            (new ScanSendService)->getNumInc($id, 1);
            $data = [
                'uid' => $this->_uid,
                'balance' => $scanData['balance_deno'],
                'score' => $scanData['score_deno'],
                'get_time' => $time
            ];
            (new ScanSendRecordService)->saveData($record['id'], $data);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        $result =  [
            'success_msg' => $successMsg,
            'left_btn' => ['text' => '返回首页', 'href' => ''],
            'right_btn' => ['text' => '我的钱包', 'href' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=my_money'],
        ];
        return api_output(0, $result, '兑换成功');
    }

    //获取扫码活动数据
    public function getScanData()
    {
        $id = $this->request->param('id', 0, 'intval');
        $ewm_no = $this->request->param('ewm_no', '');
        if (!$id || !$ewm_no) {
            return api_output_error(1001, '二维码有误!');
        }
        $param = [
            ['id', '=', $id],
        ];
        $scanData = (new ScanSendService)->getData($param);
        if (!$scanData) {
            return api_output_error(1001, '活动不存在!');
        }
        $record = (new ScanSendRecordService)->getData(['send_id' => $id, 'ewm_no' => $ewm_no]);
        if (!$record) {
            return api_output_error(1001, '二维码有误!');
        }
        if ($record['uid'] > 0) {
            return api_output(1301, $scanData['ad_url'] ?: cfg('site_url') . '/packapp/plat/pages/plat_menu/index');
        }
//        if (!empty($this->_uid)) {
//            $record1 = (new ScanSendRecordService)->getData(['send_id' => $id, 'uid' => $this->_uid]);
//            if ($record1) {//已领取
//                return api_output(1301, $scanData['ad_url'] ?: cfg('site_url') . '/packapp/plat/pages/plat_menu/index');
//            }
//        }
        if (cfg('scan_timeout') === '') {//第一次进入清除缓存
            (new CacheSqlService())->clearCache();
        }
        $scanData['scan_money_desc'] = cfg('scan_money_desc');
        $scanData['scan_score_desc'] = cfg('scan_score_desc');
        $scanData['scan_timeout'] = cfg('scan_timeout');
        if (!$scanData) {
            return api_output_error(1001, '该活动已关闭或未开始!');
        }
        return api_output(0, $scanData, '获取成功');
    }
}