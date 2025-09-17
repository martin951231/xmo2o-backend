<?php
   /**
     * 员工卡交易记录    
   */
namespace app\employee\model\db;

use app\common\model\db\MerchantStore;
use think\facade\Db;
use think\Model;

class EmployeeCardLog extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function merchant()
    {
        return $this->belongsTo(\app\common\model\db\Merchant::class, 'mer_id', 'mer_id');
    }

    public function card()
    {
        return $this->belongsTo(EmployeeCard::class, 'card_id', 'card_id');
    }

    public function cardUser()
    {
        return $this->belongsTo(EmployeeCardUser::class, 'user_id', 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    public function couponSend()
    {
        return $this->belongsTo(EmployeeCardCouponSend::class, 'coupon_id', 'pigcms_id');
    }

    public function store()
    {
        return $this->belongsTo(MerchantStore::class, 'store_id', 'store_id');
    }

    public function staff()
    {
        return $this->belongsTo(MerchantStoreStaff::class, 'operate_id', 'id');
    }

    /**
     * 列表
     */
    public function orderList($where = [], $field = true,$order=true,$page=0,$pageSize=0,$whereOr=[])
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->where(function($query) use($whereOr){
                $query->whereOr($whereOr);
            })
            ->field($field)
            ->join($prefix.'employee_card_user u','u.user_id = g.user_id')
            ->join($prefix.'employee_card c','c.card_id = g.card_id')
            ->join($prefix.'employee_card_coupon_send cous','cous.pigcms_id = g.coupon_id', 'left')
            ->join($prefix.'employee_card_coupon cou','cou.pigcms_id = cous.coupon_id', 'left');
        $ret['total']=$result->count();
        if(!empty($page)){
            $ret['list']=$result->order($order)
                ->group('g.pigcms_id')
                ->page($page,$pageSize)
                ->select()
                ->toArray();
            return $ret;
        }else{
            $ret['list']=$result->order($order)
                ->group('g.pigcms_id')
                ->select()
                ->toArray();
            return $ret;
        }

    }

    public function getCreateTimeAttr($value, $data)
    {
        return date('Y.m.d H:i', $data['add_time']);
    }

    /**
     * 获取统计数据
     */
    public function getDataCount($params, $type)
    {
        $endDate = date('Y-m-d');
        if(!in_array($type, ['coupon_price', 'grant_price', 'money', 'score'])){
            throw new \think\Exception('类型不存在！');
        }
        $limit = 10;
        if(!empty($params['start_date']) && !empty($params['end_date'])){
            $endDate = $params['end_date'];
            $startTime = new \DateTime($params['start_date']);
            $endTime = new \DateTime($params['end_date']);
            $limit = $startTime->diff($endTime)->days + 1;
        }

        $prefix = config('database.connections.mysql.prefix');

        switch($type){
            case 'coupon_price': 
            case 'grant_price': 
                $sql = "SELECT day_list.days AS x,IFNULL( datas.y, 0 ) y FROM ( SELECT FROM_UNIXTIME( add_time, '%Y-%m-%d' ) days, sum( {$type} ) y FROM {$prefix}employee_card_log 
                WHERE `mer_id` = {$params['mer_id']}  AND `change_type` = 'success'  AND `is_refund` = 0  AND (  `type` = 'coupon'  OR `type` = 'overdue' ) 
                GROUP BY days) datas RIGHT JOIN (SELECT @date := DATE_ADD( @date, INTERVAL - 1 DAY ) days FROM( SELECT @date := DATE_ADD( '{$endDate}', INTERVAL + 1 DAY ) FROM {$prefix}employee_card_log ) c LIMIT {$limit} ) day_list ON day_list.days = datas.days";
                break;

            case 'money':
                $sql = "SELECT day_list.days AS x,IFNULL( datas.y, 0 ) y FROM ( SELECT FROM_UNIXTIME( add_time, '%Y-%m-%d' ) days, sum( num ) y FROM {$prefix}employee_card_log 
                WHERE  `mer_id` = {$params['mer_id']}  AND `is_refund` = 0  AND (  (  `type` = 'money'  AND `change_type` = 'success' )  OR (  `type` = 'coupon'  AND `change_type` = 'success' ) ) 
                GROUP BY days) datas RIGHT JOIN (SELECT @date := DATE_ADD( @date, INTERVAL - 1 DAY ) days FROM( SELECT @date := DATE_ADD( '{$endDate}', INTERVAL + 1 DAY ) FROM {$prefix}employee_card_log ) c LIMIT {$limit} ) day_list ON day_list.days = datas.days";
                break;
            case 'score':
                $sql = "SELECT day_list.days AS x,IFNULL( datas.y, 0 ) y FROM ( SELECT FROM_UNIXTIME( add_time, '%Y-%m-%d' ) days, sum( num ) y FROM {$prefix}employee_card_log 
                WHERE  `mer_id` = {$params['mer_id']}   AND `is_refund` = 0  AND `type` = 'score'  AND `change_type` = 'success'
                GROUP BY days) datas RIGHT JOIN (SELECT @date := DATE_ADD( @date, INTERVAL - 1 DAY ) days FROM( SELECT @date := DATE_ADD( '{$endDate}', INTERVAL + 1 DAY ) FROM {$prefix}employee_card_log ) c LIMIT {$limit} ) day_list ON day_list.days = datas.days";
                break;
        } 
        return $this->query($sql); 
    }

    /**
     * 店铺消费数据
     */
    public function getStoreConsumerList($params)
    {
        $prefix = config('database.connections.mysql.prefix');
        $condition = [];
        $condition[] = ['l.mer_id', '=', $params['mer_id']];
        $condition[] = ['l.store_id', '<>', 0];

        $priceWhere = "`l`.`mer_id` = " . $params['mer_id'] . " AND `l`.`store_id` <> 0";
        $grantWhere = "`l1`.`mer_id` = " . $params['mer_id'] . " AND `l1`.`store_id` <> 0";
        $moneyWhere = "`l2`.`mer_id` = " . $params['mer_id'] . " AND `l2`.`store_id` <> 0";
        $scoreWhere = "`l3`.`mer_id` = " . $params['mer_id'] . " AND `l3`.`store_id` <> 0";
        if(!empty($params['keywords'])){
            $priceWhere .= " AND `s`.`name` LIKE '%{$params['keywords']}%'";
            $grantWhere .= " AND `s1`.`name` LIKE '%{$params['keywords']}%'";
            $moneyWhere .= " AND `s2`.`name` LIKE '%{$params['keywords']}%'";
            $scoreWhere .= " AND `s3`.`name` LIKE '%{$params['keywords']}%'";
            $condition[] = ['s.name', 'like', "%{$params['keywords']}%"];
        }
        if(!empty($params['start_date']) && !empty($params['end_date'])){
            if($params['start_date'] > $params['end_date']){
                throw new \think\Exception('开始日期不能大于结束日期！');
            } 
            $priceWhere .= " AND `l`.`add_time` BETWEEN ".strtotime($params['start_date'])." AND ".strtotime($params['end_date'] . ' 23:59:59');
            $grantWhere .= " AND `l1`.`add_time` BETWEEN ".strtotime($params['start_date'])." AND ".strtotime($params['end_date'] . ' 23:59:59');
            $moneyWhere .= " AND `l2`.`add_time` BETWEEN ".strtotime($params['start_date'])." AND ".strtotime($params['end_date'] . ' 23:59:59');
            $scoreWhere .= " AND `l3`.`add_time` BETWEEN ".strtotime($params['start_date'])." AND ".strtotime($params['end_date'] . ' 23:59:59');
            $condition[] = ['l.add_time', 'between', [strtotime($params['start_date']), strtotime($params['end_date'] . ' 23:59:59')]];
        }

        $priceWhere .= " AND `is_refund` = 0 AND ( 
                                ((  `change_type` = 'success' ) AND (  `type` = 'coupon'  AND `type` = 'overdue' ))
                                OR  ((  `type` = 'money'  AND `change_type` = 'decrease' )  OR (  `type` = 'coupon'  AND `change_type` = 'success' ))
                                OR  (`type` = 'score'  AND `change_type` = 'success') 
                            ) ";
        $grantWhere .= "";
        $moneyWhere .= " AND `is_refund` = 0  AND (  (  `type` = 'money'  AND `change_type` = 'success' )  OR (  `type` = 'coupon'  AND `change_type` = 'success' ) )";
        $scoreWhere .= " AND `is_refund` = 0  AND `type` = 'score'  AND `change_type` = 'success'";
 
        $limit = '';
        if(empty($params['request_type'])){
            $page = $params['page'];
            $page_size = $params['page_size'];
            $limit = "LIMIT ". ($page - 1) * $page_size .",{$page_size}";
        }
        $sql = "SELECT s.store_id,s.name,SUM(l.coupon_price) coupon_price,gra.grant_price,IFNULL(mon.money, 0) money,IFNULL(sco.score, 0) score
                FROM {$prefix}employee_card_log l
                LEFT JOIN {$prefix}merchant_store s ON l.store_id = s.store_id
                LEFT JOIN (
                    SELECT s1.store_id,s1.name,SUM(l1.grant_price) grant_price 
                    FROM {$prefix}employee_card_log l1
                    LEFT JOIN {$prefix}merchant_store s1 ON l1.store_id = s1.store_id 
                    WHERE {$grantWhere}
                    GROUP BY s1.store_id
                ) AS gra ON gra.store_id = s.store_id
                LEFT JOIN (
                    SELECT s2.store_id,s2.name,SUM(l2.num) money 
                    FROM {$prefix}employee_card_log l2
                    LEFT JOIN {$prefix}merchant_store s2 ON l2.store_id = s2.store_id 
                    WHERE {$moneyWhere}
                    GROUP BY s2.store_id
                ) AS mon ON mon.store_id = s.store_id
                LEFT JOIN (
                    SELECT s3.store_id,s3.name,SUM(l3.num) score 
                    FROM {$prefix}employee_card_log l3
                    LEFT JOIN {$prefix}merchant_store s3 ON l3.store_id = s3.store_id 
                    WHERE {$scoreWhere}
                    GROUP BY s3.store_id
                ) AS sco ON sco.store_id = s.store_id
                WHERE {$priceWhere}
                GROUP BY s.store_id
                {$limit}";
          
        $return = [];
        $data = $this->query($sql);  
            
        foreach($data as $key => $val){
            $data[$key]['total_money'] = formatNumber($val['grant_price'] + $val['money'] + $val['score']);
        }

        $return['data'] = $data;


        $sql = "SELECT COUNT(s.store_id) num
                FROM {$prefix}employee_card_log l
                LEFT JOIN {$prefix}merchant_store s ON l.store_id = s.store_id
                WHERE {$priceWhere}
                GROUP BY s.store_id";
        $total = $this->query($sql); 
        $return['total'] = count($total);
        return $return;
    }


    /**
     * 导出数据
     */
    public function getBillExportData($params)
    { 
        $prefix = config('database.connections.mysql.prefix');
        switch($params['export_type']){
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
        $dateWhere = '';
        if(!empty($params['start_date']) && !empty($params['end_date'])){
            $dateWhere = " AND `add_time` BETWEEN ".strtotime($params['start_date'])." AND ".strtotime($params['end_date'] . ' 23:59:59');
        }
        $sql = "SELECT l.times,IFNULL(gra.grants, 0) AS grants,IFNULL(mon.money, 0) AS money,IFNULL(sco.score, 0) AS score
                FROM
                    ( SELECT FROM_UNIXTIME( add_time, '{$dateText}' ) AS times FROM {$prefix}employee_card_log GROUP BY FROM_UNIXTIME( add_time, '{$dateText}' ) ) AS l
                LEFT JOIN (
                    SELECT
                        FROM_UNIXTIME( add_time, '{$dateText}' ) AS time,SUM(grant_price) AS grants
                    FROM
                        {$prefix}employee_card_log 
                    WHERE `mer_id` = {$params['mer_id']}  AND `change_type` = 'success'  AND `is_refund` = 0  AND (  `type` = 'coupon'  OR `type` = 'overdue' ) {$dateWhere} 
                    GROUP BY
                        FROM_UNIXTIME( add_time, '{$dateText}' ) 
                ) gra ON gra.time = l.times
                
                LEFT JOIN (
                    SELECT
                        FROM_UNIXTIME( add_time, '{$dateText}' ) AS time, SUM( num ) AS money 
                    FROM
                        {$prefix}employee_card_log 
                    WHERE  `mer_id` = {$params['mer_id']}  AND `is_refund` = 0  AND (  (  `type` = 'money'  AND `change_type` = 'success' )  OR (  `type` = 'coupon'  AND `change_type` = 'success' ) ) {$dateWhere}
                    GROUP BY
                        FROM_UNIXTIME( add_time, '{$dateText}' ) 
                ) mon ON mon.time = l.times 
                LEFT JOIN (
                    SELECT
                        FROM_UNIXTIME( add_time, '{$dateText}' ) AS time, SUM( num ) AS score
                    FROM
                        {$prefix}employee_card_log 
                    WHERE  `mer_id` = {$params['mer_id']}   AND `is_refund` = 0  AND `type` = 'score'  AND `change_type` = 'success' {$dateWhere}
                    GROUP BY
                        FROM_UNIXTIME( add_time, '{$dateText}' ) 
                ) sco ON sco.time = l.times  ";
        return $this->query($sql);   
    }

 
}