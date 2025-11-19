<?php

namespace app\common\controller\api;

use app\common\model\db\WithdrawRequestInfo;
use app\common\model\service\user\UserNoticeService;
use emoji\Emoji;
use think\response\Json;

class UserNoticeController extends ApiBaseController
{
    /**
     * 查询消息板块列表
     */
    public function getNoticeType()
    {
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['uid'] = $this->uid;
        try {
            $arr = (new UserNoticeService())->getNoticeType($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 消息板块-置顶
     */
    public function topNoticeType()
    {
        $param['id']     = $this->request->param('id', 0, 'intval');
        $param['uid'] = $this->uid;
        $param['status'] = $this->request->param('status', 1, 'intval');
        try {
            $arr = (new UserNoticeService())->topNoticeType($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 消息板块-删除
     */
    public function delNoticeType()
    {
        $param['id']     = $this->request->param('id', 0, 'intval');
        $param['uid'] = $this->uid;
        try {
            $arr = (new UserNoticeService())->delNoticeType($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 查询订单消息列表（查询是否已读，没的话，改为已读）
     */
    public function getOrderNoticeList()
    {
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['uid'] = $this->uid;
        $param['type'] = input('type',0);
        try {
            $arr = (new UserNoticeService())->getOrderNoticeList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 全部已读
     */
    public function readAll()
    {
        $param['uid'] = $this->uid;
        $param['read_all'] = $this->request->param('pageSize', 1, 'intval');//1：全部已读
        try {
            $arr = (new UserNoticeService())->readNotice($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 查询表情数组
     */
    public function getEmojiDB()
    {
        $arr = (new Emoji())->emojiDB();
        return api_output(0, $arr, 'success');
    }

    public function withdrawWechatInfo(): Json
    {
        $outBillNo = input('out_bill_no');
        if(empty($outBillNo)) {
            throw_exception('商户订单号参数错误');
        }
        $data = WithdrawRequestInfo::where(['out_bill_no' => $outBillNo])
            ->field('out_bill_no, weixin_mchid, weixin_appid, package_info, withdraw_money')
            ->find();
        if(empty($data)) {
            throw_exception('未找到该提现申请');
        }
        return api_output(0, $data, 'success');
    }
}