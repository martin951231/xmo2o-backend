<?php

namespace app\job\Miaomai;

use think\queue\Job;

class MiaomaiSyncJob
{
    public function fire(Job $job, $data)
    {
        try {
            if ($job->attempts() > 3) {
                $job->delete();
            }
            /*
             *  $data = [
                    'mer_id' => $merId,
                    'store_id' => $storeId,
                    'page' => $page,
                    'type' => 'goods'
                ];
             * */
            fdump_api(['job_queue_data' => $data], 'job/MiaomaiSyncJob',1);
            if(!empty($data['type']) && $data['type'] == 'goods'){
                invoke_cms_model('Merchant_store/miaomaiGoodsSync', [$data['mer_id'], $data['store_id'], $data['page']], true);
                $job->delete();
            }
        } catch (\Exception $e) {
            fdump_api([$data, $e->getMessage()], 'job/MiaomaiSyncJob', 1);
            $job->release(3); //$delay为延迟时间
        }
    }

    public function failed($data)
    {
        fdump_api(['MiaomaiSyncJobFailed' => $data], 'job/MiaomaiSyncJob', 1);
    }
}