<?php


namespace app\group\controller\merchant;

use app\group\model\db\GroupAppoint;
use app\group\model\service\appoint\GroupAppointService;
use think\facade\Config;

/**
 * Class AppointManageController
 * @package app\group\controller\merchant
 * 团购预约管理
 */
class AppointManageController extends AuthBaseController
{
    /**
     * @return \json
     * 获取商家店铺列表
     */
    public function getStoreList()
    {
        $mer_id = $this->merchantUser['mer_id'];
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = Config::get('api.page_size');
        try {
            if (empty($mer_id)) {
                return api_output(1003, "获取商家id失败");
            } else {
                $list = (new GroupAppointService())->getStoreAppointGiftList($mer_id, $page, $pageSize);
                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 店铺预约礼信息
     */
    public function getGiftMsg()
    {
        $store_id = $this->request->param('store_id', 0, 'intval');
        try {
            if (empty($store_id)) {
                return api_output(1003, "获取店铺id失败");
            } else {
                $list = (new GroupAppointService())->getAppointGiftMsg($store_id);
                return api_output(0, $list);
            }
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     *编辑店铺预约礼
     */
    public function updateAppointGift()
    {
        $store_id = $this->request->param('store_id', 0, 'intval');
        $mer_id = $this->merchantUser['mer_id'];
        $gift1 = $this->request->param('gift1', "", 'trim');
        $gift2 = $this->request->param('gift2', "", 'trim');
        $gift3 = $this->request->param('gift3', "", 'trim');
        try {
            $ret = (new GroupAppointService())->updateAppointGift($store_id, $mer_id, $gift1, $gift2, $gift3);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 预约到店列表
     */
    public function getAppointArriveList()
    {
        $mer_id = $this->merchantUser['mer_id'];
        $page = $this->request->param('page', 1, 'intval');

        $store_id = $this->request->param('store_id', '', 'intval');
        $status = $this->request->param('status','', 'trim');
        $phone = $this->request->param('phone', '', 'trim');
        $pageSize = Config::get('api.page_size');
        try {
            $list = (new GroupAppointService())->getAppointArriveList($mer_id, $page, $pageSize,$store_id,$status,$phone);
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 更新到店预约状态
     */
    public function updateAppointArriveStatus()
    {
        $id = $this->request->param('id', '', 'intval');
        $status = $this->request->param('status', '', 'intval');
        try {
            if (empty($id) || empty($status)) {
                return api_output(1003, "参数缺失,请刷新下页面");
            }
            $where = [['id', '=', $id]];
            $data['status'] = $status;
            if($status==2){
                $data['arrive_time'] = time();
            }
            $ret = (new GroupAppointService())->updateAppointArriveStatus($where, $data);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 客户预约
     */
    public function addAppointArrive()
    {
        $data['mer_id'] = $this->request->param('mer_id', '', 'intval');
        $data['store_id'] = $this->request->param('store_id', '', 'intval');
        $data['uid'] = $this->request->param('uid', '', 'intval');
        $data['phone'] = $this->request->param('phone', '', 'intval');
        $data['group_id'] = $this->request->param('group_id', '', 'intval');
        $data['appoint_type'] = $this->request->param('appoint_type', '', 'intval');
        $data['appoint_content'] = $this->request->param('appoint_content', '', 'trim');
        $data['appoint_time'] = $this->request->param('appoint_time', '', 'intval');
        $data['arrive_time'] = $this->request->param('arrive_time', '', 'intval');
        try {
            $ret = (new GroupAppointService())->addAppointArrive($data);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }
}