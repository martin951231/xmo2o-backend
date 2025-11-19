<?php
/**
 * 商家会员寄存-核销
 */

namespace app\merchant\model\service\card;

use app\merchant\model\db\CardNewDepositGoodsVerification;

class CardNewDepositGoodsVerificationService {

    public function __construct()
    {
        $this->VerificationModel = new CardNewDepositGoodsVerification();
    }

    /**
     * 核销列表
     */
    public function getList($param = [], $limit = [], $order = 'id desc') {
        $list = $this->VerificationModel->where($param)->order($order)->paginate($limit);
        return $list->toArray();
    }

}