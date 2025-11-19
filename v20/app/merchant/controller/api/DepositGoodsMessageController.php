<?php
/**
 * 用户端会员卡寄存消息
 * Author: fenglei
 * Date Time: 2021/11/09 13:49
 */

namespace app\merchant\controller\api;

use app\merchant\model\service\card\CardNewDepositGoodsMessageService;

class DepositGoodsMessageController extends ApiBaseController
{
    /**
     * 获取消息列表
     */
    public function getMessageList()
    {
        $params = array();
        $params['uid'] = $this->_uid;
        $params['mer_id'] = $this->request->post('mer_id', 0, 'trim,intval'); 
        $params['page'] = $this->request->post('page', 1, 'trim,intval'); 
        $params['pageSize'] = $this->request->post('pageSize', 10, 'trim,intval'); 
        if(empty($params['uid'])){
            return api_output(1002, '', '请登录！');
        }
        try {
            $data = (new CardNewDepositGoodsMessageService)->getMessageList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}