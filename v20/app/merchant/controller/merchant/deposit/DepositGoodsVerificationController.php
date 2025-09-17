<?php
/**
 * 商家后台寄存商品 - 核销
 */
namespace app\merchant\controller\merchant\deposit;

use app\common\model\service\UserService;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\db\CardNewDepositGoods;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\db\MerchantStoreStaff;
use app\merchant\model\service\card\CardNewDepositGoodsBindUserService;
use app\merchant\model\service\card\CardNewDepositGoodsVerificationService;
use app\merchant\model\service\card\CardUserlistService;

class DepositGoodsVerificationController extends AuthBaseController
{

    public function getVerificationList() {
        $page     = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $limit    = [
            'page'      => $page,
            'list_rows' => $pageSize
        ];
        $param = [
            'mer_id' => $this->merId
        ];
        try {
            $list = (new CardNewDepositGoodsVerificationService())->getList($param, $limit, 'use_time desc');
            if ($list['data']) {
                foreach ($list['data'] as $k => $v) {
                    $bindData = (new CardNewDepositGoodsBindUserService())->getOne(['id' => $v['bind_id']]);
                    $uid      = (new CardUserlistService())->getOne(['id' => $bindData['card_id']])['uid'] ?? 0;
                    $uData    = (new UserService())->getUser($uid);
                    $list['data'][$k]['username'] = $uData['nickname'] ?? '';
                    $list['data'][$k]['phone'] = !empty($uData['phone']) ? substr($uData['phone'], 0, 3) . '****' . substr($uData['phone'], 7) : '';
                    $list['data'][$k]['store_name'] = (new MerchantStore())->where(['store_id' => $v['store_id']])->value('name');
                    $list['data'][$k]['staff_name'] = (new MerchantStoreStaff())->where(['id' => $v['staff_id']])->value('name');
                    $list['data'][$k]['goods_name'] = (new CardNewDepositGoods())->where(['goods_id' => $bindData['goods_id']])->value('name');
                    $list['data'][$k]['use_time'] = date('Y-m-d H:i:s', $v['use_time']);
                }
            }
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}