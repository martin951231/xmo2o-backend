<?php
/**
 * 扫码领余额/积分
 */

namespace app\scan\controller\platform;
use app\common\model\service\CacheSqlService;
use app\common\model\service\ConfigService;
use app\scan\model\service\ScanSendService;
use app\scan\model\service\ScanSendRecordService;
require_once '../extend/phpqrcode/phpqrcode.php';

class ScanSendController extends AuthBaseController
{

    /**
     * 获取列表
     * @return \json
     */
    public function index() {
        $param = [];
        $name = $this->request->param('name', '', 'trim');
        $status = $this->request->param('status', -1, 'intval');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        if ($name) {
            $param['name'] = $name;
        }
        if ($status != -1) {
            $param['status'] = $status;
        }
        $limit = [
            'page' => $page,
            'list_rows' => $pageSize
        ];
        try {
            $list = (new ScanSendService)->getSearchList($param, $limit)->toArray();
            if ($list['data']) {
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
                    $list['data'][$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
                    $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                }
            }
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 更新上下架
     * @return \json
     */
    public function setStatus() {
        $id = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');
        if (!$id) {
            return api_output_error(1003, 'ID不存在');
        }
        try {
            (new ScanSendService)->setStatus($id,$status);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加
     * @return \json
     */
    public function add() {
        $param = $this->request->param();
        unset($param['system_type']);
        $param['create_time'] = time();
        $param['name'] = trim($param['name']);
        if (!$param['name']) {
            return api_output_error(1003, '活动名称不能为空');
        }
        $param['start_time'] = strtotime($param['start_time']);
        $param['end_time'] = strtotime($param['end_time']);
        if ($param['end_time'] <= $param['create_time']) {
            return api_output_error(1003, '结束时间不得小于当前时间');
        }
        if ($param['start_time'] >= $param['end_time']) {
            return api_output_error(1003, '开始时间不得大于结束时间');
        }
        if (!$param['balance_deno'] && !$param['score_deno']) {
            return api_output_error(1003, '赠送积分/余额数量至少有一个大于0');
        }
        if ($param['send_num'] <= 0) {
            return api_output_error(1003, '可领取数必须大于0');
        }
        try {
            $id = (new ScanSendService)->saveData(0, $param, []);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \json
     */
    public function edit() {
        $param = $this->request->param();
        if ($this->request->isPost()) {
            unset($param['system_type']);
            $param['create_time'] = time();
            $param['name'] = trim($param['name']);
            if (!$param['name']) {
                return api_output_error(1003, '活动名称不能为空');
            }
            $param['start_time'] = strtotime($param['start_time']);
            $param['end_time'] = strtotime($param['end_time']);
            if ($param['end_time'] <= $param['create_time']) {
                return api_output_error(1003, '结束时间不得小于当前时间');
            }
            if ($param['start_time'] >= $param['end_time']) {
                return api_output_error(1003, '开始时间不得大于结束时间');
            }
            if (!$param['balance_deno'] && !$param['score_deno']) {
                return api_output_error(1003, '赠送积分/余额数量至少有一个大于0');
            }
            $numData = [];
            if ($param['num'] > 0) {
                if ($param['num_type'] == 1) {
                    $param['send_num'] += $param['num'];
                }
                if ($param['num_type'] == 2) {
                    if ($param['send_num'] - $param['get_num'] < $param['num']) {
                        return api_output_error(1003, '剩余可领取数不足！');
                    }
                    $param['send_num'] -= $param['num'];
                }
                $numData = [
                    'num_type' => $param['num_type'],
                    'num' => $param['num']
                ];
            }
            unset($param['num_type'],$param['num']);
            try {
                (new ScanSendService)->saveData($param['id'], $param, $numData);
                return api_output(0, [], 'success');
            } catch (\Exception $e) {
                return api_output_error(1003, $e->getMessage());
            }
        } else {
            try {
                $data = (new ScanSendService)->getData(['id' => $param['id']]);
                $data['start_time'] = date('Y-m-d H:i:s',$data['start_time']);
                $data['end_time'] = date('Y-m-d H:i:s',$data['end_time']);
                return api_output(0, $data, 'success');
            } catch (\Exception $e) {
                return api_output_error(1003, $e->getMessage());
            }
        }
    }

    /**
     * 获取领取记录列表
     * @return \json
     */
    public function getRecordList() {
        $param = [];
        $send_id = $this->request->param('send_id', 0, 'intval');
        if (!$send_id) {
            return api_output_error(1003, '活动ID不存在');
        }
        $type = $this->request->param('type', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $date = $this->request->param('date', 0);
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $param[] = ['s.uid', '>', 0];
        $param[] = ['send_id', '=', $send_id];
        if ($type) {
            $param[] = $type == 1 ? ['u.nickname', 'like', '%'.$keyword.'%'] : ['u.phone', 'like', '%'.$keyword.'%'];
        } else if ($keyword) {
            $param[] = ['u.nickname|u.phone', 'like', '%'.$keyword.'%'];
        }
        if ($date) {
            $param[] = ['get_time', '>=', strtotime($date[0])];
            $param[] = ['get_time', '<', strtotime($date[1])+86400];
        }
        $limit = [
            'page' => $page,
            'list_rows' => $pageSize
        ];
        try {
            $list = (new ScanSendRecordService)->getSearchList($param, $limit)->toArray();
            if ($list['data']) {
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['get_time'] = date('Y-m-d H:i:s', $v['get_time']);
                }
            }
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 设置提示语
     * @return \json
     */
    public function setConfig() {
        if ($this->request->isPost()) {
            $data = [
                [
                    'name' => 'scan_money_desc' ,
                    'value' => trim($this->request->param('scan_money_desc', ''))
                ],
                [
                    'name' => 'scan_score_desc',
                    'value' => trim($this->request->param('scan_score_desc', ''))
                ],
                [
                    'name' => 'scan_timeout',
                    'value' => trim($this->request->param('scan_timeout', ''))
                ]
            ];
            try {
                foreach ($data as $k => $v) {
                    (new ConfigService())->saveConfigData(['name' => $v['name'], 'value' => $v['value']]);
                }
                (new CacheSqlService())->clearCache();
                return api_output(0, [], 'success');
            } catch (\Exception $e) {
                return api_output_error(1003, $e->getMessage());
            }
        } else {
            if (cfg('scan_timeout') === '') {//第一次进入清除缓存
                (new CacheSqlService())->clearCache();
            }
            $data = [
                'scan_money_desc' => cfg('scan_money_desc'),
                'scan_score_desc' => cfg('scan_score_desc'),
                'scan_timeout' => cfg('scan_timeout')
            ];
            return api_output(0, $data, 'success');
        }
    }

    /**
     * 生成以活动ID为参数的链接二维码，供扫码领积分/余额
     * @param  [type] $id 活动ID
     */
    public function createQrcode($id)
    {
        $dir = '/upload/scan/' . date('Ymd');
        $path = '../..' . $dir;
        $filename = time() . $id . '.png';
        if (file_exists($path . '/' . $filename)) {
            return $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = cfg('site_url') . '/packapp/plat/pages/activity/scanDrawAward/draw?id=' . $id;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return $dir . '/' . $filename;
    }

    /**
     * 导出二维码
     * @return \json
     */
    public function exportEwm()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!$id) {
            return api_output_error(1003, '活动ID不存在');
        }
        try {
            $arr = (new ScanSendRecordService)->addOrderExport(['send_id' => $id]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 导出领取记录表
     */
    public function exportRecordList()
    {
        $param['date'] = $this->request->param('date', [], 'trim');//查询日期
        $param['type'] = $this->request->param('type', 0, 'trim');//查询类型（0-全部，1-用户昵称，2-用户手机号）
        $param['keyword'] = $this->request->param('keyword', '', 'trim');//查询内容
        $param['send_id'] = $this->request->param('send_id', 0, 'trim');//活动id
        $param['is_all'] = $this->request->param('is_all', 0, 'trim');//1-获取所有记录，0-获取已领取记录
        try {
            $arr = (new ScanSendRecordService)->exportRecordList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取所有记录
     * @return \json
     */
    public function getAllRecordList(){
        $param = [];
        $send_id = $this->request->param('send_id', 0, 'intval');
        if (!$send_id) {
            return api_output_error(1003, '活动ID不存在');
        }
        $type = $this->request->param('type', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $date = $this->request->param('date', 0);
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $param[] = ['send_id', '=', $send_id];
        if ($type) {
            $param[] = $type == 1 ? ['u.nickname', 'like', '%'.$keyword.'%'] : ['u.phone', 'like', '%'.$keyword.'%'];
        } else if ($keyword) {
            $param[] = ['u.nickname|u.phone', 'like', '%'.$keyword.'%'];
        }
        if ($date) {
            $param[] = ['get_time', '>=', strtotime($date[0])];
            $param[] = ['get_time', '<', strtotime($date[1])+86400];
        }
        $limit = [
            'page' => $page,
            'list_rows' => $pageSize
        ];
        try {
            $list = (new ScanSendRecordService)->getList($param, $limit)->toArray();
            if ($list['data']) {
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['get_time'] = $v['get_time']?date('Y-m-d H:i:s', $v['get_time']):'';
                    $list['data'][$k]['get_text'] = $v['uid']>0?'已领取':'未领取';
                }
            }
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 单个记录删除
     */
    public function singleDelete(){
        $param['id'] = $this->request->param('id',0,'trim,intval');
        try {
            (new ScanSendRecordService)->recordDelete($param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 批量删除
     */
    public function multipleDelete(){
        $param['id'] = $this->request->param('ids',0,'trim,intval');
        try {
            (new ScanSendRecordService)->recordDelete($param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}