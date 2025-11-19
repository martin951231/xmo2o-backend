<?php
/**
 * 消息发送
 * Author: hengtingmei
 * Date Time: 2020/10/27
 */

namespace app\foodshop\controller\api;
use app\common\model\service\weixin\WxappTemplateService;
use app\foodshop\model\service\message\WxMessageService;
use app\foodshop\model\service\order\DiningOrderService;

class MessageController extends ApiBaseController
{

    /**
     * 获得小程序订阅消息模板
     */
    public function getTemplateList()
    {
        $param['type'] = $this->request->param("type", "", "trim");

        try {
            $result = (new WxMessageService())->getNormalWxappTemplate($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $result);

    }

    /**
     * 发送小程序订阅消息
     */
    public function sendWxappMessage()
    {
        $param['order_id'] = $this->request->param("order_id", "", "intval");
        $param['type'] = $this->request->param("type", "", "intval");

        if($this->userInfo){
            $user = $this->userInfo;
        }else{
            $user = $this->userTemp;
        }

//        // 未获取到用户信息
//        $user = (new DiningOrderService())->getFormatUser($user);
//        if(empty($user)){
//            return api_output_error(1006);
//        }

        try {
            $result = (new WxMessageService())->sendWxappMessage($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
           
        return api_output(0, $result);
  
    }
}
