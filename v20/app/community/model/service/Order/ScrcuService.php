<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      四川农信支付
 */

namespace app\community\model\service\order;

use app\pay\model\service\PayService;
use app\traits\CommonLogTraits;
use think\facade\Cache;
use think\facade\Db;

class ScrcuService
{
    use CommonLogTraits;

    public $scrcuCacheTag = 'scrcuInteractiveTag';
    public $scrcuCacheKey = 'scrcuInteractive:traits:';
    
    public function hasScrcu($params = []) {
        $stepNumber = isset($params['stepNumber']) ? intval($params['stepNumber']) : 1;
        $scrcuCount = Db::name('Scrcu_record')->count();
        $stringScrcu = date('Hi');
        $cacheKey = $this->scrcuCacheKey;
        $jobRedis  = Cache::store('redis')->get($cacheKey);
        if ($scrcuCount > 0 && !$jobRedis && $stepNumber <= 5) {
            $queueData = ['jobType' => 'scrcuQueryResult', 'stepNumber' => $stepNumber, 'execution_time' => $this->scrcuQuerySpaceTimes, 'nowTime' => time()];
            $job_id = $this->traitCommonLvCC($queueData, $this->scrcuQuerySpaceTimes);
            fdump_api(['queueData' => $queueData, 'params' => $params, 'stringScrcu' => $stringScrcu, 'line' => __LINE__, 'job_id' => $job_id], 'scrcuService/hasScrcuLog', 1);
            $cacheTag = $this->scrcuCacheTag;
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, 120);
        }
        if (trim($stringScrcu) == '0112') {
            // todo 凌晨1点12分 执行清除已经查询了的支付
            $queueData = ['jobType' => 'clearScrcuQueryResult', 'execution_time' => $this->scrcuQuerySpaceTimes, 'nowTime' => time()];
            $job_id = $this->traitCommonLvCC($queueData);
            fdump_api(['msg' => '凌晨1点12分 执行清除已经查询了的支付', 'stringScrcu' => $stringScrcu, 'queueData' => $queueData, 'line' => __LINE__, 'job_id' => $job_id], 'scrcuService/hasScrcuLog', 1);
        }
    }
    
    /**
     * 四川农银 异步通知
     * @return [type] [description]
     */
    public function scrcu($params)
    {
        $stepNumber = isset($params['stepNumber']) ? intval($params['stepNumber']) : 1;
        $cacheKey = $this->scrcuCacheKey;
        if ($stepNumber >= 6) {
            Cache::store('redis')->delete($cacheKey);
            return true;
        }
        
        $iscron = isset($params['iscron']) ? $params['iscron'] : true;
        //1、获取order_no（本系统的支付单号）
        $limit = 30;
        $hour = date('H');
        if ($iscron) {
            $limit = 100;
            $where = [['add_time', '>=', time() - 1000], ['over', '=', 0], ['limit', '<', 150], ['order_type', '=', 'pay_order_info']];
            $data = Db::name('Scrcu_record')->order("limit asc,id asc")->where($where)->limit($limit)->select();
        } else {
            $where = [['add_time', '>=', time() - 300], ['over', '=', 0], ['limit', '<', 110], ['order_type', '=', 'pay_order_info']];
            $data = Db::name('Scrcu_record')->order("limit asc,id asc")->where($where)->limit($limit)->select();
        }
        if ($data && !is_array($data)) {
            $data = $data->toArray();
        } else {
            $data = [];
        }
        if (count($data) < $limit) {
            // 首先查询再时间范围内的一次没有查询过的订单不足20条， 查询没有查询过的订单补足20条
            $limit1 = $limit - count($data);
            $q_add_time = time() - 90 * 24 * 3600; //90天
            $whereQArr = array(['limit', '<', 130], ['over', '=', 0], ['order_type', '=', 'pay_order_info'], ['add_time', '>=', $q_add_time]);

            $data1 = Db::name('Scrcu_record')->order("limit asc,id asc")->where($whereQArr)->limit($limit1)->select();
            if ($data1 && !is_array($data1)) {
                $data1 = $data1->toArray();
                fdump_api(['msg' => '订单查询', 'line' => __LINE__, 'iscron' => $iscron, '$data' => $data, '$limit1' => $limit1, '$data1' => $data1], 'scrcuService/noticeLog', 1);
                $data = array_merge($data, $data1);
            } else {
                $data1 = [];
            }
        }
        if (!empty($data)) {
            $noticeArr = [];
            foreach ($data as $key => $value) {
                if (isset($noticeArr[$value['id']]) && $noticeArr[$value['id']]) {
                    continue;
                }
                try {
                    $pay_service = new PayService;
                    request()->orderid = $value['order_number'];
                    $notice = $pay_service->notice($value['order_number']);
                    fdump_api(['iscron' => $iscron, 'line' => __LINE__, 'notice' => $notice, 'order_number' => $value['order_number']], 'scrcuService/noticeLog', 1);
                } catch (\Exception $e) {
                    $code = $e->getCode();
                    fdump_api(['iscron' => $iscron, 'line' => __LINE__, 'value' => $value, 'message' => $e->getMessage(), 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'scrcuService/noticeLog', 1);
                    Db::name('Scrcu_record')->where(['id' => $value['id']])->data(['limit' => $value['limit'] + 1])->save();
                    if ($code == 22204) {
                        //交易关闭了 不要再查了
                        Db::name('Scrcu_record')->where(['id' => $value['id']])->delete();
                    }
                    continue;
                }
                fdump_api(['iscron' => $iscron, 'line' => __LINE__, 'notice' => $notice, 'order_number' => $value['order_number']], 'scrcuService/noticeLog', 1);
                //调用业务方service  after_pay
                if ($notice['after_pay']) {
                    fdump_api(['iscron' => $iscron, 'line' => __LINE__, 'notice' => $notice, 'order_number' => $value['order_number']], 'scrcuService/noticeLog', 1);
                    $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                }
                // 已经拿到了支付结果并记录 删除轮询记录
                Db::name('Scrcu_record')->where(['id' => $value['id']])->delete();
                $noticeArr[$value['id']] = $value['order_number'];
            }
        }
        $limit = 3;
        if ($iscron) {
            $limit = 15;
        }
        $otherData = Db::name('Scrcu_record')->order("id asc")->where([['limit', '=', 0], ['over', '=', 0], ['order_type', '=', 'pay_order_info']])->limit($limit)->select();
        if (!empty($otherData)) {
            foreach ($otherData as $key => $value) {
                try {
                    $pay_service = new PayService;
                    request()->orderid = $value['order_number'];
                    $notice = $pay_service->notice($value['order_number']);
                    fdump_api(['iscron' => $iscron, 'line' => __LINE__, 'notice' => $notice, 'order_number' => $value['order_number']], 'scrcuService/noticeLog', 1);
                    Db::name('Scrcu_record')->where(['id' => $value['id']])->data(['over' => 1, 'over_time' => time()])->save();
                } catch (\Exception $e) {
                    $code = $e->getCode();
                    fdump_api(['iscron' => $iscron, 'value' => $value, 'line' => __LINE__, 'notice' => $notice, 'message' => $e->getMessage(), 'errline' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'scrcuService/noticeLog', 1);
                    Db::name('Scrcu_record')->where(['id' => $value['id']])->data(['limit' => $value['limit'] + 1])->save();
                    if ($code == 22204) {
                        //交易关闭了 不要再查了
                        Db::name('Scrcu_record')->where(['id' => $value['id']])->delete();
                    }
                }
                fdump_api(['iscron' => $iscron, 'line' => __LINE__, 'notice' => $notice, 'order_number' => $value['order_number']], 'scrcuService/noticeLog', 1);
                //调用业务方service  after_pay
                if ($notice['after_pay']) {
                    fdump_api(['iscron' => $iscron, 'notice' => $notice, 'order_number' => $value['order_number']], 'scrcuService/noticeLog', 1);
                    $pay_service->afterPay($notice['business'], $notice['business_order_id'], $notice['extra']);
                }
            }
        }
        $stepNumber = $stepNumber + 1;
        $hasParams = ['stepNumber' => $stepNumber];
        Cache::store('redis')->delete($cacheKey);
        $this->hasScrcu($hasParams);
        return true;
    }

    public function clearScrcuQueryResult($params) 
    {
        try {
            $nowTime = isset($params['nowTime']) && intval($params['nowTime']) > 0 ? intval($params['nowTime']) : time();
            $add_time = $nowTime - 86400;
            $where1 = [['over', '=', 1], ['add_time', '<', $add_time], ['order_type', '=', 'pay_order_info']];
            Db::name('Scrcu_record')->where($where1)->delete();
            $where2 = [['limit', '>=', 130], ['add_time', '<', $add_time], ['order_type', '=', 'pay_order_info']];
            Db::name('Scrcu_record')->where($where2)->delete();
        } catch (\Exception $e){
            fdump_api([ 'msg' => '清除查询支付记录-异常捕捉盒子', 'line' => $e->getLine(), 'file' => $e->getFile(), 'err' => $e->getMessage(), 'code' =>$e->getCode()], 'errClearScrcuQueryResult', 1);
        }
        return true;
    }
}