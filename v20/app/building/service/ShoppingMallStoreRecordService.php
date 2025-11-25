<?php


namespace app\building\service;


use app\building\model\ShoppingMallStoreRecord;

class ShoppingMallStoreRecordService
{
    public function addRecord($params)
    {
        $request = request();
        $save_data = [
            'mall_id' => $params['mall_id'] ?? 0,
            'store_id' => $params['store_id'],
            'uid' => $request->log_uid,
            'action' => $request->server('PATH_INFO'),
            'content' => $params['data'] ? json_encode($params['data']) : '',
            'create_time' => date('Y-m-d H:i:s'),
            'create_ip' => $request->server('REMOTE_ADDR'),
            'note' => $params['note'] ?? '',
        ];
        try {
            (new ShoppingMallStoreRecord())->save($save_data);
        } catch (\Exception $e) {
            fdump_api([json_encode($save_data, JSON_UNESCAPED_UNICODE), $e->getMessage()], 'shopping_mall_store_record/addRecord', 1);
        }
    }
}