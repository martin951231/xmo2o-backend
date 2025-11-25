<?php
/**
 * 次卡service
 */

namespace app\life_tools\model\service;

use app\common\model\db\CardNew;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\merchant_card\MerchantCardService;
use app\common\model\service\order\SystemOrderService;
use app\common\model\service\UserService;
use app\group\model\db\TempOrderData;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsCard;
use app\life_tools\model\db\LifeToolsCardOrder;
use app\life_tools\model\db\LifeToolsCardOrderRecord;
use app\life_tools\model\db\LifeToolsCardTools;
use app\life_tools\model\db\LifeToolsMember;
use app\life_tools\model\db\LifeToolsMessage;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\db\LifeToolsTicketSaleDay;
use app\merchant\model\service\card\CardNewService;
use app\merchant\model\service\MerchantMoneyListService;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\PayService;
use think\facade\Db;
use think\Model;

class LifeToolsCardService
{
    /**
     * 添加编辑次卡时获取商家信息
     */
    public function getAddEditCardMerchantInfo($mer_id)
    {
        $data = [];
        $data['is_scenic'] = $data['is_sports'] = 0;

        $lifeToolsModel = new LifeTools();
        $condition = [];
        $condition[] = ['mer_id', '=', $mer_id];
        $condition[] = ['is_del', '=', 0];
        $type = $lifeToolsModel->field('type')->where($condition)->group('type')->column('type');
        if(in_array('scenic', $type)){
            $data['is_scenic'] = 1;
        }
        if(in_array('stadium', $type) || in_array('course', $type)){
            $data['is_sports'] = 1;
        }

        $data['scenic_list'] = $data['stadium_list'] = $data['course_list'] = [];
        $field = 'tools_id,title';
        $order = 'sort DESC,tools_id DESC';
        if($data['is_scenic']){
            $condition = [];
            $condition[] = ['type', '=', 'scenic'];
            $condition[] = ['mer_id', '=', $mer_id];
            $condition[] = ['is_del', '=', 0];
            $data['scenic_list'] = $lifeToolsModel->getList($condition, $field, $order, 0) ?: [];
        }
        
        if($data['is_sports']){
            $condition = [];
            $condition[] = ['type', '=', 'stadium'];
            $condition[] = ['mer_id', '=', $mer_id];
            $condition[] = ['is_del', '=', 0];
            $data['stadium_list'] = $lifeToolsModel->getList($condition, $field, $order, 0) ?: [];

            $condition = [];
            $condition[] = ['type', '=', 'course'];
            $condition[] = ['mer_id', '=', $mer_id];
            $condition[] = ['is_del', '=', 0];
            $data['course_list'] = $lifeToolsModel->getList($condition, $field, $order, 0) ?: [];
        }

        return $data;
    }
}