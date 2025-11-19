<?php

namespace app\scan\model\service;

use app\common\model\service\export\ExportService as BaseExportService;
use app\common\model\service\UserService;
use app\scan\model\db\ScanSendRecord;
use app\scan\model\db\ScanSendRecordDel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ScanSendRecordService
{
    public function __construct() {
        $this->ScanSendRecordModel = new ScanSendRecord();
    }

    //获取记录列表
    public function getSearchList($param = [], $limit = []) {
        if($limit){
            $list = $this->ScanSendRecordModel
                ->alias('s')
                ->join(config('database.connections.mysql.prefix').'user u', 's.uid = u.uid')
                ->where($param)
                ->field('s.*,u.nickname,u.phone,u.score_count,u.now_money')
                ->order('s.id desc')
                ->paginate($limit);
        }else{
            $list = $this->ScanSendRecordModel
                ->alias('s')
                ->join(config('database.connections.mysql.prefix').'user u', 's.uid = u.uid')
                ->where($param)
                ->field('s.*,u.nickname,u.phone,u.score_count,u.now_money')
                ->order('s.id desc')
                ->select();
        }
        return $list;
    }

    //获取所有记录列表
    public function getList($param = [], $limit = []) {
        if($limit){
            $list = $this->ScanSendRecordModel
                ->alias('s')
                ->join(config('database.connections.mysql.prefix').'user u', 's.uid = u.uid','left')
                ->where($param)
                ->field('s.*,u.nickname,u.phone,u.score_count,u.now_money')
                ->order('s.id desc')
                ->paginate($limit);
        }else{
            $list = $this->ScanSendRecordModel
                ->alias('s')
                ->join(config('database.connections.mysql.prefix').'user u', 's.uid = u.uid','left')
                ->where($param)
                ->field('s.*,u.nickname,u.phone,u.score_count,u.now_money')
                ->order('s.id desc')
                ->select();
        }
        return $list;
    }

    //保存数据
    public function saveData($id, $param) {
        if ($id) {
            $res = $this->ScanSendRecordModel->where("id", $id)->update($param);
        } else {
            $res = $this->ScanSendRecordModel->insertGetId($param);
        }
        return $res;
    }

    //获取数据
    public function getData($param) {
        $res = $this->ScanSendRecordModel->where($param)->find();
        return $res;
    }

    //获取导出列表
    public function getExportList($param) {
        $list = $this->ScanSendRecordModel
            ->alias('sr')
            ->join(config('database.connections.mysql.prefix').'scan_send ss', 'ss.id = sr.send_id')
            ->where(['sr.send_id' => $param['send_id']])
            ->field('sr.*,ss.name')
            ->select();
        return $list;
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function addOrderExport($param)
    {
        $title = '二维码导出';
        $param['type'] = 'pc';
        $param['rand_number'] = time();
        $param['service_path'] = '\app\scan\model\service\ScanSendRecordService';
        $param['service_name'] = 'ewmExportPhpSpreadsheet';
        $param['status'] = 1;
        $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        $this->ewmExportPhpSpreadsheet($param);
        return $result;
    }

    /**
     * 导出二维码(Spreadsheet方法)
     * @param $param
     */
    public function ewmExportPhpSpreadsheet($param)
    {
        $dataList = $this->getExportList($param);
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '扫码活动名称');
        $worksheet->setCellValueByColumnAndRow(2, 1, '预生成二维码的地址');
        $worksheet->setCellValueByColumnAndRow(3, 1, '卡密');
        $worksheet->setCellValueByColumnAndRow(4, 1, '是否已领取');
        $worksheet->setCellValueByColumnAndRow(5, 1, '领取用户ID');
        $worksheet->setCellValueByColumnAndRow(6, 1, '领取用户手机号');
        $worksheet->setCellValueByColumnAndRow(7, 1, '领取余额数');
        $worksheet->setCellValueByColumnAndRow(8, 1, '领取积分数');
        $worksheet->setCellValueByColumnAndRow(9, 1, '领取时间');
        $worksheet->setCellValueByColumnAndRow(10, 1, '创建时间');
        //设置单元格样式
        $worksheet->getStyle('A1:O1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:O')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $len = count($dataList);
        $j = 0;
        $row = 0;
        $i = 0;
        foreach ($dataList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $val['name']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $val['ewm_url']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $val['ewm_no']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $val['uid'] > 0 ? '已领取' : '未领取');
                $worksheet->setCellValueByColumnAndRow(5, $j, $val['uid']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $val['uid'] ? (new userService)->userFind('phone', ['uid' => $val['uid']])['phone'] : 0);
                $worksheet->setCellValueByColumnAndRow(7, $j, $val['balance']);
                $worksheet->setCellValueByColumnAndRow(8, $j, $val['score']);
                $worksheet->setCellValueByColumnAndRow(9, $j, $val['get_time'] ? date('Y-m-d H:i:s', $val['get_time']) : '无');
                $worksheet->setCellValueByColumnAndRow(10, $j, date('Y-m-d H:i:s', $val['create_time']));
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:O' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

    }

    /**
     * 导出领取记录列表
     * @author Nd
     * @date 2022/5/19
     */
    public function exportRecordList($param)
    {
        $is_all = $param['is_all']??0;
        $csvHead = array(
            L_('扫码活动名称'),
            L_('预生成二维码的地址'),
            L_('是否已领取'),
            L_('昵称'),
            L_('手机号'),
            L_('当前积分'),
            L_('当前余额'),
            L_('领取时间')
        );
        $where = [];
        if($is_all!=1){
            $where[] = ['s.uid', '>', 0];
        }
        $where[] = ['send_id', '=', $param['send_id']];
        if($param['type']){
            $where[] = $param['type'] == 1 ? ['u.nickname', 'like', '%'.$param['keyword'].'%'] : ['u.phone', 'like', '%'.$param['keyword'].'%'];
        }elseif($param['keyword']){
            $where[] = ['u.nickname|u.phone', 'like', '%'.$param['keyword'].'%'];
        }
        if ($param['date']) {
            $where[] = ['get_time', '>=', strtotime($param['date'][0])];
            $where[] = ['get_time', '<', strtotime($param['date'][1])+86400];
        }
        if($is_all!=1){
            $data = $this->getSearchList($where, [])->toArray();
        }else{
            $data = $this->getList($where, [])->toArray();
        }
        $sendActivityName = (new \app\scan\model\db\ScanSend())->where('id',$param['send_id'])->value('name');
        $csvData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $getTime = $value['get_time'] ? date('Y-m-d H:i:s', $value['get_time']) : '';
                $csvData[$key] = [
                    $sendActivityName,
                    $value['ewm_url'],
                    $value['uid']>0?'已领取':'未领取',
                    $value['nickname'],
                    $value['phone'],
                    $value['score_count'],
                    $value['now_money'],
                    $getTime
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . time() . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 记录删除
     * @param $param
     * @throws \think\Exception
     */
    public function recordDelete($param){
        $id = $param['id'];
        if(!$id){
            throw new \think\Exception(L_('请选择要删除的记录'));
        }
        if(!is_array($id)){
            $id = array($id);
        }
        //查询要删除数据
        $del_list = (new ScanSendRecord())->where([['id','in',$id],['uid','<',1]])->select()->toArray();
        $save_del_data = [];
        foreach ($del_list as $item){
            $item['del_time'] = time();
            $save_del_data[] = $item;
        }
        try {
            (new ScanSendRecord())->where([['id','in',$id],['uid','<',1]])->delete();
            $res = (new ScanSendRecordDel())->insertAll($save_del_data);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }
}