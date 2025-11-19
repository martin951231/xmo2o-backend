<?php

namespace app\wechatLive\controller\platform;

use app\merchant\model\service\MerchantExtendsService;
use app\wechatLive\model\service\LiveRoomService;

class LiveController  extends AuthBaseController
{
    /**
     * 直播商家列表
     *
     * @return void
     * @author: zt
     * @date: 2023/12/12
     */
    public function merchantLists()
    {
        $lists = (new MerchantExtendsService())->getLists($this->request->param());
        return api_output(1000, $lists);
    }

    /**
     * 审核
     *
     * @return void
     * @author: zt
     * @date: 2023/12/13
     */
    public function handleAudit()
    {
        $adminId = $this->request->log_uid;
        $auditResult = $this->request->param('audit_result', 0, 'intval');
        $auditMessage = $this->request->param('audit_message', '', 'trim');
        $merId = $this->request->param('mer_id', 0, 'intval');
        try {
            (new MerchantExtendsService())->handleAudit($merId, $auditResult, $auditMessage, $adminId);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 禁播
     *
     * @return void
     * @author: zt
     * @date: 2023/12/13
     */
    public function changeLiveAuth()
    {
        $merId = $this->request->param('mer_id', 0, 'intval');
        $liveAuth = $this->request->param('wechat_live_auth', 0, 'intval');
        $liveMessage = $this->request->param('wechat_live_auth_message', '', 'trim');
        try {
            (new MerchantExtendsService())->changeLiveAuth($merId, $liveAuth,$liveMessage);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    public function getRooms()
    {
        $params['keyword'] = $this->request->param('keyword','','trim');
        $params['page'] = $this->request->param('current',1,'intval');
        $params['page_size'] = $this->request->param('pageSize',10,'intval');
        $rooms = (new LiveRoomService())->getRooms($params);
        return api_output(1000, $rooms);
    }

    public function delRoom()
    {
        $roomid = $this->request->param('roomid');
        $rooms = (new LiveRoomService())->delRoom($roomid);
        return api_output(1000, $rooms);
    }

    public function getRoomShare()
    {
        $roomid = $this->request->param('roomid');
        $share = (new LiveRoomService())->getRoomShare($roomid);
        return api_output(1000, $share);
    }
}
