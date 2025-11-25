<?php
/**
 * 商家推广佣金service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/18 14:22
 */

namespace app\merchant\model\service\spread;
use app\merchant\model\db\MerchantPercentRate;
class MerchantPercentRateService {
    public $merchantPercentRateModel = null;
    public function __construct()
    {
        $this->merchantPercentRateModel = new MerchantPercentRate();
    }


    /**
     * 获得商家分佣比例
     * @param int $merId
     * @param string $type
     * @return bool
     * @author: 衡婷妹
     * @date: 2020/8/25
     */
    public function getMerchantrate($merId, $type)
    {

        if(cfg('system_take_percent_mer')>0 ){ //商家推广佣金比例
            return cfg('system_take_percent_mer');
        }

        if($type== 'dining'){//新餐饮
            $type = 'meal';
        }

        $where['mer_id'] = $merId;
        $now_mer_pr = $this->getOne($where);
        if ($merId>0 && $now_mer_pr) {
            if ($now_mer_pr[$type . '_rate'] >= 0 &&$now_mer_pr[$type . '_rate']!='') {
                return $now_mer_pr[$type . '_rate'];
            } elseif ( $now_mer_pr['merchant_rate'] >= 0 &&$now_mer_pr['merchant_rate']!='') {
                return $now_mer_pr['merchant_rate'];
            } elseif (cfg('' . $type . '_rate') >= 0) {
                return cfg('' . $type . '_rate');
            } elseif ( cfg('platform_get_merchant_rate') >= 0) {
                return cfg('platform_get_merchant_rate');
            } else {
                return 0;
            }
        } else {
            if (cfg('' . $type . '_rate') >= 0 ) {
                return cfg('' . $type . '_rate');
            } elseif (cfg('platform_get_merchant_rate') >= 0) {
                return cfg('platform_get_merchant_rate');
            } else {
                return 0;
            }
        }
    }

    /**
     * 获取多条记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getSome($where)
    {
        $row = $this->merchantPercentRateModel->getSome($where);
        return $row ? $row->toArray() : [];
    }
    /**
     * 获取记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getOne($where)
    {
        $row = $this->merchantPercentRateModel->where($where)->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 添加记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function add($data)
    {
        $id = $this->merchantPercentRateModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->merchantPercentRateModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

}