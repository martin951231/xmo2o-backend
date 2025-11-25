<?php
namespace app\scan\model\service;

use app\scan\model\db\ScanSend;
use app\scan\model\db\ScanSendRecord;
use think\Exception;


class ScanSendService
{

    public function __construct()
    {
        $this->ScanSendModel = new ScanSend();
        $this->ScanSendRecordModel = new ScanSendRecord();
    }

    //获取搜索列表
    public function getSearchList($param = [], $limit = []) {
        $list = $this->ScanSendModel->where($param)->order('id desc')->paginate($limit);
        return $list;
    }

    //设置上下架
    public function setStatus($id, $status) {
        $res = $this->ScanSendModel->where("id", $id)->update(['status' => $status]);
        return $res;
    }

    //保存数据
    public function saveData($id, $param, $numData) {
        if ($id) {
            $res = $this->ScanSendModel->where("id", $id)->update($param);
            if ($numData) {
                if ($numData['num_type'] == 1) {//增加
                    $data = [];
                    for ($i = 0; $i < $numData['num']; $i++) {
                        $uniqid = createRandomStr(10,false,false,true);
                        $data[$i] = [
                            'send_id' => $id,
                            'ewm_url' => cfg('site_url') . '/packapp/plat/pages/activity/scanDrawAward/draw?id=' . $id . '&ewm_no=' . $uniqid,
                            'ewm_no' => $uniqid,
                            'create_time' => time()
                        ];
                    }
                    $this->ScanSendRecordModel->insertAll($data);
                } else if ($numData['num_type'] == 2) {//减少
                    $this->ScanSendRecordModel->where([
                        'send_id' => $id,
                        'uid' => 0
                    ])->order('id desc')->limit($numData['num'])->delete();
                }
            }
        } else {
            $res = $this->ScanSendModel->insertGetId($param);
            if ($res) {
                $data = [];
                for ($i = 0; $i < $param['send_num']; $i++) {
                    $uniqid = createRandomStr(10,false,false,true);
                    $data[$i] = [
                        'send_id' => $res,
                        'ewm_url' => cfg('site_url') . '/packapp/plat/pages/activity/scanDrawAward/draw?id=' . $res . '&ewm_no=' . $uniqid,
                        'ewm_no' => $uniqid,
                        'create_time' => time()
                    ];
                }
                $this->ScanSendRecordModel->insertAll($data);
            }
        }
        return $res;
    }

    //获取数据
    public function getData($param) {
        $res = $this->ScanSendModel->where($param)->find();
        return $res;
    }

    //增加已领取数
    public function getNumInc($id, $num) {
        $res = $this->ScanSendModel->where('id', $id)->inc('get_num', $num)->update();
        return $res;
    }

}
