<?php
/**
 * 商家微信扫码service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/07/03 09:59
 */

namespace app\merchant\model\service\weixin;
use app\merchant\model\service\MerchantService;
use app\common\model\service\weixin\RecognitionService;

class MerchantQrcodeService {
    public $merchantService = null;
    public function __construct()
    {
        $this->merchantService = new MerchantService();
    }

    /**
     * 获得商家登录二维码
     * @param $param array 登录信息
     * @return array
     */
    public function seeLoginQrcode($param=[]){
        $result = (new RecognitionService())->getLoginQrcode($param);
        return $result;
    }

    /**
     * 获得商家二维码
     * @param $param array 登录信息
     * @return array
     */
    public function seeQrcode($merId){
        $nowMerchant = $this->getQrcode($merId);
        if(empty($nowMerchant)){
            return false;
        }

        $result = (new RecognitionService())->seeQrcode($nowMerchant['qrcode_id'],'merchant', $merId);
        return $result;
    }

    /**
     * 获得商家二维码id
     * @param $param array 登录信息
     * @return array
     */
    public function getQrcode($merId){
        $where = [
            'mer_id' => $merId
        ];
        $nowMerchant = (new MerchantService())->getOne($where);
        if(empty($nowMerchant)){
            return false;
        }
        return $nowMerchant;
    }

    public function saveQrcode($merId,$qrcodeId){
        $where = [
            'mer_id' => $merId
        ];
        $data = [
            'qrcode_id' => $qrcodeId
        ];
        $result = (new MerchantService())->updateByMerId($merId,$data);

        if(!$result){
            throw new \think\Exception(L_("保存二维码至商家信息失败！请重试。"), 1003);
        }
        return true;
    }

    public function delQrcode($merId){
        $data = [
            'qrcode_id' => ''
        ];
        $result = (new MerchantService())->updateByMerId($merId,$data);

        if(!$result){
            throw new \think\Exception(L_("保存二维码至商家信息失败！请重试。"), 1003);
        }
        return true;
    }



}