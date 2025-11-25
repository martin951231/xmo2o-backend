<?php

namespace app\deliver\model\service;


use app\deliver\model\db\DeliverScoreLog;

/**
 * 配送员积分日志服务类
 * @author: 张涛
 * @date: 2020/9/12
 */
class DeliverScoreLogService
{
    public $deliverScoreLogMod;

    public function __construct()
    {
        $this->deliverScoreLogMod = new DeliverScoreLog();
    }

    /**
     * 增加配送员积分变化日志
     * @author: 张涛
     * @date: 2020/9/12
     */
    public function addRow($uid, $type, $from, $score, $msg, $time = 0, $param = array())
    {
        if (!$time) {
            $time = time();
        }
        $dataUserScoreList['uid'] = $uid;
        $dataUserScoreList['type'] = $type;
        $dataUserScoreList['score'] = $score;
        $dataUserScoreList['desc'] = $msg;
        $dataUserScoreList['from'] = $from;
        $dataUserScoreList['time'] = $time;

        (isset($param['supply_id']) && $param['supply_id']) && $dataUserScoreList['supply_id'] = $param['supply_id'];
        (isset($param['store_id']) && $param['store_id']) && $dataUserScoreList['store_id'] = $param['store_id'];
        (isset($param['order_id']) && $param['order_id']) && $dataUserScoreList['order_id'] = $param['order_id'];
        (isset($param['item']) && $param['item']) && $dataUserScoreList['item'] = $param['item'];

        if ($this->deliverScoreLogMod->insert($dataUserScoreList)) {
            return true;
        } else {
            return false;
        }
    }
}