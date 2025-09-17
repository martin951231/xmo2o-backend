<?php


namespace app\wechatLive\controller\merchant;

use app\merchant\model\service\MerchantExtendsService;
use app\wechatLive\model\service\LiveAnchorService;
use app\wechatLive\model\service\LiveRoomService;

class LiveController extends AuthBaseController
{
    private function privilegeCheck()
    {
        $merId = $this->request->log_uid;
        $extend = (new \app\merchant\model\db\MerchantExtends())->where('mer_id', $merId)->find();

        if (!$extend || $extend->wechat_live_auth != 1) {
            $output = [
                'status' => 1302,
                'msg'      => '',
                'data'      => cfg('site_url') . '/v20/public/platform/#/merchant/liveBroadcast.merchant/baseSetting'
            ];
            echo json_encode($output);
            exit;
        }
    }

    /**
     * 获取商家直播权限审核信息
     *
     * @return void
     * @author: zt
     * @date: 2023/12/11
     */
    public function getWechatLiveApply()
    {
        $rs = (new MerchantExtendsService())->getWechatLiveApply($this->request->log_uid);
        return api_output(1000, $rs);
    }

    public function submitWechatLiveApply()
    {
        try {
            (new MerchantExtendsService())->submitWechatLiveApply($this->request->log_uid);
            return api_output(1000, 'success');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    public function getRoles(){
        $this->privilegeCheck();
        $merid = $this->request->log_uid;
        $roles = (new LiveAnchorService())->getRolesByMerid($merid);
        return api_output(1000, ['roles'=>$roles,'limit'=>cfg('wechat_mini_live_anchor_limit')]);
    }

    public function saveRole()
    {
        $this->privilegeCheck();
        $params = $this->request->param();
        $params['mer_id'] = $this->request->log_uid;
        try {
            (new LiveAnchorService())->saveRole($params);
            return api_output(1000, 'success');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    public function delRole()
    {
        $this->privilegeCheck();
        $merid = $this->request->log_uid;
        $id = $this->request->param('id', 0, 'intval');
        try {
            (new LiveAnchorService())->delRolesById($merid, $id);
            return api_output(1000, '');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    public function getRooms()
    {
        $this->privilegeCheck();
        $params['mer_id'] = $this->request->log_uid;
        $params['page'] = $this->request->param('current',1,'intval');
        $params['page_size'] = $this->request->param('pageSize',10,'intval');
        $rooms = (new LiveRoomService())->getRooms($params);
        return api_output(1000, $rooms);
    }

    public function delRoom()
    {
        $this->privilegeCheck();
        $roomid = $this->request->param('roomid');
        $merId = $this->request->log_uid;
        $rooms = (new LiveRoomService())->delRoom($roomid);
        return api_output(1000, $rooms);
    }

    public function getRoomShare()
    {
        $this->privilegeCheck();
        $roomid = $this->request->param('roomid');
        $share = (new LiveRoomService())->getRoomShare($roomid);
        return api_output(1000, $share);
    }

}
