<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      消息
 */

namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\message\PushMessageHandleService;
use app\consts\HouseWorkConst;

class MessageController extends BaseController
{
    /**
     * 获取消息类型.
     */
    public function getMessageClassifyList() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $result = [];
        $login_role = $this->login_role;
        $_uid = $this->_uid;
        if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            return api_output_error(1008,'当前仅支持小区工作人员查看');
        }
        try {
            $result['message'] = (new PushMessageHandleService())->getHouseWorkMessageClassifyList($_uid);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
    
    /**
     * 获取消息.
     */
    public function getHouseWorkMessage() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $classify_id = $this->request->post('classify_id',0);
        $page = $this->request->post('page',1);
        $pageSize = $this->request->post('pageSize',15);
        $result = [];
        $login_role = $this->login_role;
        $_uid = $this->_uid;
        if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            return api_output_error(1008,'当前仅支持小区工作人员查看');
        }
        try {
            $params = [
                'page' => $page, 'pageSize' => $pageSize, 'nowTime' => time()
            ];
            $result['count'] = (new PushMessageHandleService())->getHouseWorkMessageCount($_uid, $classify_id, $params);
            $result['message'] = (new PushMessageHandleService())->getHouseWorkMessage($_uid, $classify_id, $params);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 标记消息为已读.
     */
    public function viewWorkMessage() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $result = [];
        $login_role = $this->login_role;
        $_uid = $this->_uid;
        if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            return api_output_error(1008,'当前仅支持小区工作人员查看');
        }
        $message_id = $this->request->post('message_id',0);
        try {
            $result['message'] = (new PushMessageHandleService())->viewWorkMessage($_uid, $message_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 标记消息为已删除.
     */
    public function deleteWorkMessage() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        $result = [];
        $login_role = $this->login_role;
        $_uid = $this->_uid;
        if ($login_role != HouseWorkConst::MANAGE_APP_VILLAGE_WORK) {
            return api_output_error(1008,'当前仅支持小区工作人员操作');
        }
        $message_id = $this->request->post('message_id',0);
        try {
            $result['message'] = (new PushMessageHandleService())->deleteWorkMessage($_uid, $message_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $result);
    }
}