<?php
/**
 * 用户积分
 * Created by.PhpStorm
 * User: chenxiang
 * Date: 2020/5/26 16:27
 */

namespace app\common\model\service;

use app\common\model\db\UserScoreList;

use think\facade\Db;
class UserScoreListService
{
    public $userScoreListObj = null;
    public function __construct()
    {
        $this->userScoreListObj = new UserScoreList();
    }

    public function addRow($uid, $type, $score, $msg, $record_ip = true, $clean = false, $time = 0, $param = []) {
        if (!$time) {
            $time = time();
        }
        $data_user_score_list['uid'] = $uid;
        $data_user_score_list['type'] = $type;
        $data_user_score_list['score'] = $score;
        $data_user_score_list['desc'] = $msg;
        $data_user_score_list['time'] = $time;
        isset($param['mer_id']) && $param['mer_id'] > 0 && $data_user_score_list['mer_id'] = $param['mer_id'];
        isset($param['store_id']) && $param['store_id'] && $data_user_score_list['store_id'] = $param['store_id'];
        isset($param['score_type']) && $param['score_type'] && $data_user_score_list['score_type'] = $param['score_type'];
        isset($param['order_id']) && $param['order_id'] && $data_user_score_list['order_id'] = $param['order_id'];
        isset($param['order_type']) && $param['order_type'] && $data_user_score_list['order_type'] = $param['order_type'];
        if (strpos($msg, '返现') !== false) {
            $data_user_score_list['is_pay_back'] = 1;
        }
        if ($type == 1) {
            if (cfg('score_end_days') != 0) {
                $data_user_score_list['end_time'] = $_SERVER['REQUEST_TIME'] + cfg('score_end_days') * 86400;
            }
            $data_user_score_list['used_count'] = 0;
            $data_user_score_list['clean_time'] = 0;
            $data_user_score_list['clean_count'] = 0;
            $data_user_score_list['clean_status'] = 0;
        } else if ($type == 2 && !$clean) {
            $where[] = ['type', '=', 1];
            $where[] = ['uid', '=', $uid];
            $where[] = ['clean_status', '=', 0];
            $where[] = ['used_count', 'exp', Db::raw('< score')];
            $score_list = $this->userScoreListObj->getUserScoreList($where);
            foreach ($score_list as $index => $item) {
                $data = [];
                $item['score_have'] = $item['score'] - $item['used_count'];
                if ($score > $item['score_have']) {
                    $data['used_count'] = $item['score'];
                    $data['clean_status'] = 3;
                    $score -= $item['score_have'];

                    $data['id'] = $item['id'];
                    $this->userScoreListObj->saveUserScoreList($data);
                } else if ($score <= $item['score_have']) {
                    $data['used_count'] = $item['used_count'] + $score;
                    if ($score == $item['score_have']) {
                        $data['clean_status'] = 3;
                    }
                    $data['id'] = $item['id'];
                    $this->userScoreListObj->saveUserScoreList($data);
                    break;
                }
            }
        }
        if ($record_ip) {
            $data_user_score_list['ip'] = request()->ip();
        }
        if ($this->userScoreListObj->addUserScoreList($data_user_score_list)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取用户信息
     * User: chenxiang
     * Date: 2020/6/1 18:52
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getOne($where = [], $field = true) {
        $result = $this->userScoreListObj->getOne($where, $field);
        return $result;
    }
}
