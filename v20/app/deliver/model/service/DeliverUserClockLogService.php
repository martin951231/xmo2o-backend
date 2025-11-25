<?php

namespace app\deliver\model\service;

use app\deliver\model\db\DeliverUserClockLog;
use app\deliver\model\db\DeliverUserClockTime;

/**
 * 配送员打卡记录表
 * @package app\deliver\model\service
 */
class DeliverUserClockLogService
{
    public $logMod;

    public $timeMod;

    public function __construct()
    {
        $this->logMod = new DeliverUserClockLog();
        $this->timeMod = new DeliverUserClockTime();
    }

    /**
     * 写入打卡记录
     * @param $uid 配送员ID
     * @param $type 打卡类型1上班2下班
     * @date: 2021/09/16
     */
    public function record($uid, $type)
    {
        if ($uid < 1 || !in_array($type, [1, 2])) {
            return false;
        }

        $tm = time();

        //如果设置下线，则记录上线时长
        if ($type == 2) {
            $startWorkRecord = $this->logMod->where('uid', $uid)->order('id', 'desc')->find();
            if ($startWorkRecord) {
                $timeData = [
                    'uid' => $uid,
                    'start_time' => $startWorkRecord->add_time,
                    'end_time' => $tm,
                    'working_time' => $tm - $startWorkRecord->add_time
                ];
                $this->timeMod->insert($timeData);
            }
        }

        $logData = [
            'uid' => $uid,
            'clock_type' => $type,
            'add_time' => $tm
        ];
        $this->logMod->insert($logData);
        return true;
    }
}