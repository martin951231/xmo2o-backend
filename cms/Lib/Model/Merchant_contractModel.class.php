<?php

/**
 * 商家合同
 */
class Merchant_contractModel extends Model
{
    public function getContractContentByMerid($merId)
    {
        $tm = time();
        //商家
        $nowMerchant = D('Merchant')->where(['mer_id' => $merId])->find();
        $area = D('Area')->where(['area_id' => ['IN', [$nowMerchant['province_id'], $nowMerchant['city_id'], $nowMerchant['area_id']]]])->order('area_type ASC')->getField('area_name', true);
        $nowMerchant['address'] = implode($area, ' ');
        $percentRate = D('Merchant_percent_rate')->where(['mer_id' => $merId])->find();

        //平台
        $fields = ['contract_must_sign', 'contract_second_company', 'contract_second_name', 'contract_second_tel', 'contract_second_address', 'contract_content', 'contract_official_seal','contract_effective_time','contract_effective_type','contract_can_renewed','contract_few_days_notice'];
        $configs = D('Config_data')->where(['name' => ['IN', $fields]])->select();
        $configs = array_column($configs, 'value', 'name');
        
        $contract_number = '';
        //签署合同
        $signData = $this->where(['mer_id' => $merId,'status'=>1])->find();
        if($signData&&$nowMerchant['is_sign_contract']!=2){
            if($configs['contract_can_renewed']&&$signData['end_time']&&($signData['end_time']-time())<=$configs['contract_few_days_notice']*24*3600){
                $end_time = strtotime('+'.$configs['contract_effective_time'].' '.$configs['contract_effective_type'],$signData['end_time']);
                $date_info = ($signData['end_time']?date('Y年m月d日',$signData['sign_time']).'至':'').date('Y年m月d日',$end_time);
                $contract_number = date('ymdHis').('1000' + $nowMerchant['mer_id']);
            }else{
                $contract_number = $signData['contract_number'];
                $date_info = ($signData['sign_time']?date('Y年m月d日',$signData['sign_time']).'至':'').($signData['end_time']?date('Y年m月d日',$signData['end_time']):'--');
            }
        }else{
            $contract_number = date('ymdHis').('1000' + $nowMerchant['mer_id']);
            $end_time = strtotime('+'.$configs['contract_effective_time'].' '.$configs['contract_effective_type'],$tm);
            $date_info = date('Y年m月d日').'至'.date('Y年m月d日',$end_time);
        }
        $rs = [
            'partner_a' => [
                'name' => $nowMerchant['name'],
                'represent' => $nowMerchant['legal_person']?:'',
                'phone' => $nowMerchant['phone'],
                'address' => $nowMerchant['address'],
                'sign_pic' => $signData['sign_name_base64'] ?? '',
                'date' => date('Y年m月d日', $tm)
            ],
            'partner_b' => [
                'name' => $configs['contract_second_company'] ?? '',
                'represent' => $configs['contract_second_name'] ?? '',
                'phone' => $configs['contract_second_tel'] ?? '',
                'address' => $configs['contract_second_address'] ?? '',
                'sign_pic' => $configs['contract_official_seal'] ? replace_file_domain($configs['contract_official_seal']) : '',
                'date' => date('Y年m月d日', $tm)
            ],
            'rate' => $percentRate['merchant_percent'] > 0 ? $percentRate['merchant_percent'] : 0,
            'contract_content' => $configs['contract_content'] ? htmlspecialchars_decode($configs['contract_content'], ENT_QUOTES) : '',
            'contract_number' => $contract_number,
            'mer_id' => $merId,
            'date_info' => $date_info,
            'sign_time' => $tm,
            'end_time' => $end_time
        ];
        return $rs;
    }
}