<?php

/**
 * 移动端新版商城首页访问记录
 */

namespace app\mall\model\service;


use app\common\model\service\export\ExportService as BaseExportService;
use app\mall\model\db\MallBrowseNew;
use function GuzzleHttp\Psr7\str;

class MallBrowseNewService{
    public $MallBrowseNewModel = null;

    public function __construct()
    {
        $this->MallBrowseNewModel = new MallBrowseNew();
    }

    /**
     * 写入记录
     * @author Nd
     * @date 2022/5/17
     */
    public function insertRecord($uid)
    {
        return $this->MallBrowseNewModel->insert([
            'uid' => $uid,
            'create_time' => time()
        ]);
    }
    /**
     * 查询浏览量
     * @author Nd
     * @date 2022/5/17
     */
    public function getBrowseNum($where)
    {
        $browseNum = $this->MallBrowseNewModel->where($where)->count();
        return $browseNum;
    }

    /**
     * 查询今日浏览量
     * @author Nd
     * @date 2022/5/17
     */
    public function getBrowseNumToday($goodsId)
    {
        $where = [];
        $where[] = ['goods_id','=',$goodsId];
        $where[] = ['create_time','>=',strtotime(date('Y-m-d'))];
        $where[] = ['create_time','<=',strtotime(date('Y-m-d').' 23:59:59')];
        $browseNum = $this->MallBrowseNewModel->where($where)->count();
        return $browseNum;
    }

    /**
     * 获取浏览量统计信息
     * @author Nd
     * @date 2022/5/17
     */
    public function getMallBrowse($param)
    {
        $param['search_type'] = $param['search_type'] ?? 0;
        $param['time_type'] = $param['time_type'] ?? 0;
        $param['start_time'] = $param['start_time'] ?? '';
        $param['end_time'] = $param['end_time'] ?? '';
        $where = [];
        $whereWeek = [];
        $whereAll = [];
        if($param['search_type']){
            $whereAll[] = $whereWeek[] = $where[] = ['goods_id','>',0];
        }else{
            $whereAll[] = $whereWeek[] = $where[] = ['goods_id','=',0];
        }
        if($param['time_type'] == 4){
            $dateNum = (strtotime($param['end_time']) - strtotime($param['start_time'])) / (60 * 60 * 24);
        }
        $return = [];
        switch ($param['time_type']){
            case 0://今日
                $where[] = ['create_time','>=',strtotime(date('Y-m-d'))];
                $where[] = ['create_time','<=',strtotime(date('Y-m-d').' 23:59:59')];
                for ($i=0;$i<24;$i++){
                    $key = $i.':00~'.($i+1).':00';
                    $return[$key] = 0;
                }
                break;
            case 1://本周
                $where[] = ['create_time','>=',strtotime('Sunday -6 day', time())];
                $where[] = ['create_time','<',strtotime('Sunday', time()) + 86400];
                for ($i=1;$i<=7;$i++){
                    $key = date('Y-m-d' ,strtotime( '+' . $i-(date('w',time())) .' days', time()));
                    $return[$key] = 0;
                }
                break;
            case 2://本月
                $where[] = ['create_time','>=',strtotime(date("Y")."-".date("m")."-1")];
                $where[] = ['create_time','<',strtotime(date('Y-m-01', time()). ' 1 month')];
                for ($i=date('t',time());$i>0;$i--){
                    $key = date('Y-m-d', strtotime(date('Y-m-01', time())." +1 month -".$i." day"));
                    $return[$key] = 0;
                }
                break;
            case 3://全年
                $where[] = ['create_time','>=',strtotime(date('Y').'-01-01')];
                $where[] = ['create_time','<',strtotime(date('Y').'-12-31') + 86400];
                for ($i=1;$i<=12;$i++){
                    $key = $i.'月';
                    $return[$key] = 0;
                }
                break;
            case 4://自定义时间
                $where[] = ['create_time','>=',strtotime($param['start_time'])];
                $where[] = ['create_time','<',strtotime($param['end_time']) + 86400];
                if($dateNum < 1){//按小时展示
                    for ($i=0;$i<24;$i++){
                        $key = $i.':00~'.($i+1).':00';
                        $return[$key] = 0;
                    }
                }
                if($dateNum >= 1 && $dateNum<=32){//按天展示
                    for ($i=0;$i<=((strtotime($param['end_time'])-strtotime($param['start_time']))/86400);$i++){
                        $key = date('Y-m-d',strtotime($param['start_time']) + ($i * 86400));
                        $return[$key] = 0;
                    }
                }
                if($dateNum > 32 && $dateNum<=366){//按月展示
                    for ($i=date('m',strtotime($param['start_time']));$i<=date('m',strtotime($param['end_time']));$i++){
                        $key = ltrim($i,0).'月';
                        $return[$key] = 0;
                    }
                }
                if($dateNum > 366){//按年展示
                    for ($i=date('Y',strtotime($param['start_time']));$i<=date('Y',strtotime($param['end_time']));$i++){
                        $key = $i;
                        $return[$key] = 0;
                    }
                }
                break;
        }
        $data = $this->MallBrowseNewModel->where($where)->field('create_time')->select()->toArray();
        foreach ($data as &$v){
            if($param['time_type'] == 0 || ($param['time_type'] == 4 && $dateNum < 1)){//今天
                foreach ($return as $kk=>$vv){
                    if(ltrim(date('H',$v['create_time']),0).':00' == explode('~',$kk)[0]){
                        $return[$kk] += 1;
                    }
                }
            }
            if($param['time_type'] == 1 || $param['time_type'] == 2 || ($param['time_type'] == 4 && $dateNum >= 1 && $dateNum<=32)){//本周//本月//自定义时间
                foreach ($return as $kk=>$vv){
                    if(date('Y-m-d',$v['create_time']) == $kk){
                        $return[$kk] += 1;
                    }
                }
            }
            if($param['time_type'] == 3 || ($param['time_type'] == 4 && $dateNum > 32 && $dateNum<=366)){//本年
                foreach ($return as $kk=>$vv){
                    if(date('m',$v['create_time']) == explode('月',$kk)[0]){
                        $return[$kk] += 1;
                    }
                }
            }
            if($param['time_type'] == 4 && $dateNum > 366){//按年展示
                foreach ($return as $kk=>$vv){
                    if(date('Y',$v['create_time']) == $kk){
                        $return[$kk] += 1;
                    }
                }
            }
        }
        $returnData['list'] = $return;
        //总数量
        $returnData['total'] = $this->MallBrowseNewModel->where($whereAll)->count();
        //计算周浏览量
        $whereWeekAll = $whereWeek;
        $whereWeekAll[] = ['create_time','>=',strtotime('Sunday -6 day', time())];
        $whereWeekAll[] = ['create_time','<',strtotime('Sunday', time()) + 86400];
        $returnData['week'] = $this->MallBrowseNewModel->where($whereWeekAll)->count();

        //计算周环比（上个阶段）
        $whereWeekLast = $whereWeek;
        $whereWeekLast[] = ['create_time','>=',mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'))];
        $whereWeekLast[] = ['create_time','<=',mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'))];
        $last = $this->MallBrowseNewModel->where($whereWeekLast)->count();
        $returnData['week_last'] = getFormatNumber(($returnData['week']-$last)*100/100);
        $returnData['week_last'] = 100*$returnData['week_last'].'%';

        //计算周同比（去年的同阶段）
        $lastYear = strtotime('-1 year', time());
        $whereWeekYearLast = $whereWeek;
        $whereWeekYearLast[] = ['create_time','>=',strtotime('Sunday -6 day', $lastYear)];
        $whereWeekYearLast[] = ['create_time','<',strtotime('Sunday', $lastYear) + 86400];
        $yearLast = $this->MallBrowseNewModel->where($whereWeekLast)->count();
        $returnData['week_year_last'] = getFormatNumber(($returnData['week']-$yearLast)*100/100);
        $returnData['week_year_last'] = 100*$returnData['week_year_last'].'%';
        return $returnData;
    }

    /**
     * 导出商城商品浏览量列表
     * @author Nd
     * @date 2022/5/17
     */
    public function exportMallBrowse($param)
    {
        $goodsList = (new MallGoodsService())->getPlatformGoodsBrowseList($param['keyword'], $param['merList'], $param['storeList'], '', '',0,true,$param);
        $goodsList = $goodsList ?:[];
        $csvHead = array(
            L_('商品分类'),
            L_('商品名称'),
            L_('商家名称'),
            L_('店铺名称'),
            L_('售价'),
            L_('总销量'),
            L_('当前库存'),
            L_('状态'),
            L_('排序'),
            L_('浏览量'),
            L_('今日浏览量')
        );
        $csvData = [];
        if (!empty($goodsList)) {
            foreach ($goodsList['list'] as $goodsKey => $value) {
                $cate = $value['cate_first'] . ($value['cate_second'] ? '/' . $value['cate_second'] : '') . ($value['cate_three'] ? '/' . $value['cate_three'] : '');
                $csvData[$goodsKey] = [
                    $cate,
                    $value['goods_name'],
                    $value['mer_name'],
                    $value['store_name'],
                    $value['min_price'] . '-' . $value['max_price'],
                    $value['sale_num'],
                    $value['stock_num'],
                    $value['status'] == 1 ? '上架' : '下架',
                    $value['sort_platform'] ?: 0,
                    $value['browse_num_time'],
                    $value['browse_num_today']
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $_SERVER['REQUEST_TIME'] . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl,'file_name' => $downFileUrl];
    }

    /**
     * 导出商城商品浏览量汇总数据
     * @author Nd
     * @date 2022/5/17
     */
    public function exportMallBrowseTotal($params)
    {
        $csvHead = array(
            L_('日期'),
            L_('浏览量')
        );
        $data = $this->MallBrowseNewModel->getExportData($params);
        // return $data;
        $csvData = [];
        $total = [];
        $total['dates'] = '本页总计';
        $total['total'] = 0;
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    ' ' . $value['dates'] . ' ',
                    $value['total'],
                ];
                $total['total'] += $value['total'];
            }
        }
        $csvData[] = $total;
        $filename = date("Y-m-d", time()) . '-' . uniqid() . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }
}
