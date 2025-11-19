<?php


namespace app\job\order;

use think\queue\Job;

class StatisticOrderMoneyJob
{
    /**
     * v20/app/internal_card/model/service/InternalCardMoneyBillService->merchantStoreBill
     * @param Job $job
     * @param $data
     * @return void
     */
    public function fire(Job $job, $data)
    {
        if ($job->attempts() > 3) {
            $job->delete();
        }
        /*
         *  [
         *  'order_class' => $order,
            'store_id' => $storeId,
            'date' => $date,
            ]
         * */
        fdump_api(['job_queue_data' => $data], 'job/StatisticOrderMoneyJob',1);
        try {
            (new $data['order_class'])->merchantStoreStatistic($data['store_id'], $data['date']);
            $job->delete();
        } catch (\Exception $e) {
            fdump_api([$data, $e->getTraceAsString()], 'job/StatisticOrderMoneyJobErr', 1);
            $job->release(3); //$delay为延迟时间
        }       
    }


    public function failed($data)
    {
        fdump_api(['StatisticOrderMoneyJobFailed' => $data], 'job/StatisticOrderMoneyJob', 1);
    }
}