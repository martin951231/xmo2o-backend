<?php

/**
 * 商城首页浏览记录
 */
namespace app\mall\model\db;
use think\Exception;
use think\Model;

use think\facade\Config;

class MallBrowseNew extends Model {
    /**
     * 导出数据
     */
    public function getExportData($params)
    {
        $export_type = $params['export_type']??0;
        $search_type = $params['search_type']??0;
        $time_type = $params['time_type']??5;
        $params['start_time'] = isset($params['start_time']) && $params['start_time'] ? $params['start_time'] : date('Y-m-d');
        $params['end_time'] = isset($params['end_time']) && $params['end_time'] ? $params['end_time'] : date('Y-m-d');
        $prefix = config('database.connections.mysql.prefix');
        switch($export_type){
            case 0: //日
                $dateText = '%Y-%m-%d';
                break;
            case 1: //月
                $dateText = '%Y-%m';
                break;
            case 2: //年
                $dateText = '%Y';
                break;
        }
        $where = '';
        if($search_type){
            $where .= " goods_id > 0";
        }else{
            $where .= " goods_id = 0";
        }
        switch ($time_type){
            case 0://今日
                $where .= " AND create_time >= ".strtotime(date('Y-m-d'));
                $where .= " AND create_time <= ".strtotime(date('Y-m-d').' 23:59:59');
            break;
            case 1://本周
                $where .= " AND create_time >= ".strtotime('Sunday -6 day', time());
                $where .= " AND create_time < ".(strtotime('Sunday', time()) + 86400);
                break;
            case 2://本月
                $where .= " AND create_time >= ".strtotime(date("Y")."-".date("m")."-1");
                $where .= " AND create_time < ".strtotime(date('Y-m-01', time()). ' 1 month');
            break;
            case 3://全年
                $where .= " AND create_time >= ".strtotime(date('Y').'-01-01');
                $where .= " AND create_time < ".(strtotime(date('Y').'-12-31') + 86400);
            break;
            case 4://自定义时间
                $where .= " AND create_time >= ".strtotime($params['start_time']);
                $where .= " AND create_time < ".(strtotime($params['end_time']) + 86400);
            break;
            case 5:
            break;
        }
        $sql = "SELECT FROM_UNIXTIME( create_time, '{$dateText}' ) AS dates,
                    COUNT(id) AS total
                FROM {$prefix}mall_browse_new
                WHERE {$where}
                GROUP BY FROM_UNIXTIME( create_time, '{$dateText}' )";
        return $this->query($sql);
    }

}