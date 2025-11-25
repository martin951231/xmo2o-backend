<?php

namespace app\employee\model\service;

use app\common\model\db\Merchant;
use app\common\model\service\export\ExportService as BaseExportService;
use app\employee\model\db\EmployeeCardLog;
use app\foodshop\model\service\order\DiningOrderService;
class ExportService {
    
	/**
     * 添加导出计划任务
     * @return array
     */
    public function addExport($params){
        $title = '员工卡消费记录';

        
		// $params['type'] = 'cardLog';
		$params['service_path'] = '\app\employee\model\service\ExportService';
		$params['service_name'] = 'cardLog';
		$params['rand_number'] = time();
        $params['system_user']['area_id'] = 0;
        $params['merchant_user']['mer_id'] = $params['mer_id'] ? $params['mer_id'] : 0;
        $params['staff_user']['id'] =  0;
        $params['staff_user']['store_id'] = 0;
        return $this->cardLog($params);
        // $result = (new BaseExportService())->addExport($title, $params);
        // return $result; 
    }

	/**
     * 添加导出计划任务
     * @return array
     */
    public function addBillDataExport($params){
        $title = '员工卡财务报表数据统计'; 
		$params['type'] = 'bill_data';
		$params['service_path'] = '\app\employee\model\service\ExportService';
		$params['service_name'] = 'exportBillData';
		$params['rand_number'] = time();
        $params['system_user']['area_id'] = 0;
        $params['merchant_user']['mer_id'] = $params['mer_id'] ? $params['mer_id'] : 0;
        $params['staff_user']['id'] =  0;
        $params['staff_user']['store_id'] = 0;
        return $this->exportBillData($params);
        // $result = (new BaseExportService())->addExport($title, $params);
        // return $result; 
    }

	/**
     * 添加导出计划任务
     * @return array
     */
    public function addStoreListExport($params){
        $title = '员工卡财务报表食堂列表'; 
		$params['type'] = 'bill_store_list';
		$params['service_path'] = '\app\employee\model\service\ExportService';
		$params['service_name'] = 'exportBillStoreList';
		$params['rand_number'] = time();
        $params['system_user']['area_id'] = 0;
        $params['merchant_user']['mer_id'] = $params['mer_id'] ? $params['mer_id'] : 0;
        $params['staff_user']['id'] =  0;
        $params['staff_user']['store_id'] = 0;
        return $this->exportBillStoreList($params);
        // $result = (new BaseExportService())->addExport($title, $params);
        // return $result; 
    }

    /**
     * 导出
     * @return array
     */
    public function cardLog($params)
    {
        // $params['type'] = 'coupon';
        $params['page_size'] = 10000000;

        $data = (new EmployeeCardService())->cardLogListNew($params);
        
        // $merchant = (new Merchant())->getInfo($params['mer_id']);
        $csvData = [];
        switch($params['type']){
            case 'coupon':
                $csvHead = array(
                    L_('会员名称'),
                    L_('会员卡号'),
                    L_('会员身份'),
                    L_('会员部门'),
                    L_('会员手机号'),
                    L_('店铺名称'),
                    L_('消费券名称'),
                    L_('补助'),
                    L_('个人消费'),
                    L_('总计消费'),
                    L_('状态'),
                    L_('转积分'),
                    L_('转积分扣除余额'),
                    L_('核销时间')
                );

                if (!empty($data)) {
                    $tmp_id = 0;
                    foreach ($data as $key => $value) {
                        $type = '食堂消费券';
                        switch($value->type){
                            case 'overdue': 
                                $type = '自动转积分';
                                break;
                            case 'score': 
                                $type = '积分消费';
                                break;
                            case 'to_score': 
                                $type = '手动转积分';
                                break;
                            case 'money': 
                                $type = '余额消费';
                                break;
                            default:
                                $type = '食堂消费券';
                                break;
                        }
                        $csvData[$key] = [
                            $value->card_user->name.' ',
                            $value->card_user->card_number,
                            $value->card_user->identity,
                            $value->card_user->department,
                            $value->user->phone,
                            $value->store->name ?? '',
                            $value->coupon_name,
                            $value->grant_price,
                            $value->num,
                            $value->coupon_price,
                            $type . ($value->is_refund ? '(已退款:'. $value->refund_remark .')' : ''),
                            $value->add_score_num,
                            $value->deduct_money,
                            date('Y-m-d H:i', $value->add_time)
                        ];
                    }
                }
                    
                
                break;

            case 'money':

                $csvHead = array(
                    L_('会员名称'),
                    L_('会员卡号'),
                    L_('会员身份'),
                    L_('会员部门'),
                    L_('会员手机号'),
                    L_('店铺名称'),
                    L_('消费金额'),
                    L_('消费时间'),
                    L_('状态'),
                    L_('备注')
                );
                
                if (!empty($data)) {
                    $tmp_id = 0;
                    foreach ($data as $key => $value) {
                        $csvData[$key] = [
                            $value->card_user->name.' ',
                            $value->card_user->card_number,
                            $value->card_user->identity,
                            $value->card_user->department,
                            $value->user->phone,
                            $value->store->name ?? '',
                            $value->num,
                            date('Y-m-d H:i', $value->add_time),
                            '余额消费' . ($value->is_refund ? '(已退款:'. $value->refund_remark .')' : ''),
                            $value->remark
                        ];
                    }
                }
                break;
            
            case 'score':
                $csvHead = array(
                    L_('会员名称'),
                    L_('会员卡号'),
                    L_('会员身份'),
                    L_('会员部门'),
                    L_('会员手机号'),
                    L_('店铺名称'),
                    L_('消费积分'),
                    L_('消费时间'),
                    L_('状态'),
                    L_('备注')
                );
                
                if (!empty($data)) {
                    $tmp_id = 0;
                    foreach ($data as $key => $value) {
                        $csvData[$key] = [
                            $value->card_user->name.' ',
                            $value->card_user->card_number,
                            $value->card_user->identity,
                            $value->card_user->department,
                            $value->user->phone,
                            $value->store->name ?? '',
                            $value->num,
                            date('Y-m-d H:i', $value->add_time),
                            '积分消费' . ($value->is_refund ? '(已退款:'. $value->refund_remark .')' : ''),
                            $value->remark
                        ];
                    }
                }
                break;

            case 'overdue':
                 
                $csvHead = array(
                    L_('会员名称'),
                    L_('会员卡号'),
                    L_('会员身份'),
                    L_('会员部门'),
                    L_('会员手机号'),
                    L_('过期时间'),
                    L_('消费券名称'), 
                    L_('未核销原因')
                );
                if (!empty($data)) { 
                    foreach ($data as $key => $value) {
                        $csvData[$key] = [
                            $value->card_user->name.' ',
                            $value->card_user->card_number,
                            $value->card_user->identity,
                            $value->card_user->department,
                            $value->user->phone,
                            date('Y-m-d H:i:s', $value->add_time),
                            $value->coupon_name,
                            $value->description
                        ];
                    }
                }

                break;
        }

        

       
        
        
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv';
// //        $csvFileName = './runtime/' . iconv("utf-8", "gb2312", $filename);
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
        
        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }


    /**
     * 财务报表导出
     * @return array
     */
    public function exportBillData($params)
    { 
        $csvHead = array(
            L_('日期'),
            L_('总收入'),
            L_('补贴'),
            L_('余额支付'),
            L_('积分支付')
        );

        
        $data = (new EmployeeCardLogService())->getBillExportData($params);
       
        $csvData = [];
        $total = [];
        $total['times'] = '本页总计';
        $total['total'] = 0;
        $total['grants'] = 0;
        $total['money'] = 0;
        $total['score'] = 0;
        if (!empty($data)) { 
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    ' ' . $value['times'] . ' ',
                    $value['total'],
                    $value['grants'],
                    $value['money'],
                    $value['score']
                ];
                $total['total'] += $value['total'];
                $total['grants'] += $value['grants'];
                $total['money'] += $value['money'];
                $total['score'] += $value['score'];
            }
        } 
        $csvData[] = $total;
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv'; 
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead); 

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 食堂列表导出
     */
    public function exportBillStoreList($params)
    {
        $csvHead = array(
            L_('食堂名称'),
            L_('食堂总收入'),
            L_('食堂总补贴'),
            L_('员工现金总支付'),
            L_('员工积分总支付')
        ); 
        
        $data = (new EmployeeCardLog())->getStoreConsumerList($params);
       
        $csvData = [];
 
        if (!empty($data['data'])) { 
            foreach ($data['data'] as $key => $value) {
                $csvData[$key] = [
                    $value['name'].' ',
                    $value['total_money'],
                    $value['grant_price'],
                    $value['money'],
                    $value['score']
                ]; 
            }
        } 
        $filename = date("Y-m-d", time()) . '-' . $params['rand_number'] . '.csv'; 
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead); 

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

}