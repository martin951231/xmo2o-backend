<?php
/**
 * 定制企业 校园充值管理功能
 * User: lumin
 * Date: 2020/06/08
 */

namespace app\common\model\service\qiye;

use app\common\model\db\QiyeStaff;
use app\common\model\db\QiyeMerchant;

class QiyeService
{
	/**
     * 获取员工
     */
    public function getStaff($uid)
    {
    	if(empty($uid)){
    		throw new Exception("uid 未传递");    		
    	}
        $rs = (new QiyeStaff)->getOne(['job_number' => $uid]);
        return $rs ?: [];
    }

	/**
     * 判断商户和用户是否同一企业
     * @return bool
     */
    public function checkTheSameQiye($uid, $mer_id)
    {
    	if(empty($uid) || empty($mer_id)){
    		throw new Exception("uid|mer_id 未传递");    		
    	}
        $staff = $this->getStaff($uid);
        $merchant = (new QiyeMerchant)->getOne(['mer_id' => $mer_id]);
        if (!$staff || !$merchant) {
            return false;
        } else {
            return $staff['qiye_id'] == $merchant['qiye_id'];
        }
    }
}