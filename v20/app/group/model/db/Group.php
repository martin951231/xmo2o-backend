<?php

/**
 * 团购
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/10 19:12
 */
namespace app\group\model\db;

use think\Model;
use think\facade\Db;
class Group extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time:  2020/6/15 11:27
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getGroupGoodsListByJoin($where, $field, $order, $page, $pageSize) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($pageSize==0){
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'merchant_store ms','ms.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->order($order)
                ->group('g.group_id')
                ->select();

        }else{
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'merchant_store ms','ms.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->order($order)
                ->group('g.group_id')
                ->page($page,$pageSize)
                ->select();

        }
        return $result;
    }

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getGroupGoodsListByJoin1($where, $field, $order, $page, $pageSize,$group_by_store='') {
        $group_by = $group_by_store=='group_by_store'?'gs.store_id':'g.group_id';
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($pageSize==0){
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->leftJoin($prefix.'group_store gs','g.group_id = gs.group_id')
                ->leftJoin($prefix.'merchant_store ms','ms.mer_id = g.mer_id and gs.store_id = ms.store_id')
                ->order($order)
                ->group($group_by)
                ->select();

        }else{
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->leftJoin($prefix.'group_store gs','g.group_id = gs.group_id')
                ->leftJoin($prefix.'merchant_store ms','ms.mer_id = g.mer_id and gs.store_id = ms.store_id')
                ->order($order)
                ->group($group_by)
                ->page($page,$pageSize)
                ->select();

        }
        return $result;
    }
    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getGroupCategoryListByJoin($where, $field, $order, $page, $pageSize) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($pageSize==0){
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'group_category c','g.cat_id = c.cat_id')
                ->order($order)
                ->group('g.cat_id')
                ->select();

        }else{
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'group_category c','g.cat_id = c.cat_id')
                ->order($order)
                ->group('g.cat_id')
                ->page($page,$pageSize)
                ->select();

        }

        return $result;
    }


    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getGroupGoodsCountByJoin($where) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('g')
                ->where($where)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'merchant_store ms','ms.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->group('g.group_id')
                ->count();
        return $result;
    }

    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getGroupGoodsCountByJoin1($where) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('g')
            ->where($where)
            ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
            ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
            ->leftJoin($prefix.'merchant_store ms','ms.mer_id = g.mer_id')
            ->leftJoin($prefix.'group_store gs','gs.store_id = ms.store_id')
            ->group('g.group_id')
            ->count();
        return $result;
    }

    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getGroupCountBy($where, $field) {

        $rs = $this->where($where)->field($field)->group('mer_id')->select();

        if (!empty($rs)) {
            $result = $rs->toArray();
        } else {
            return [];
        }
        return $result;
    }

    //获取特价拼团
    public function getGroupsDistance($where, $lng, $lat, $page, $limit, $order)
    {
        empty($order) ? $sort = 's.score_mean DESC,s.sale_count DESC' : $sort = $order;
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $time = time();
//        AND s.begin_time < {$time}
//        AND s.end_time > {$time}
        $sql = "SELECT s.*,m.name as merchant_name,c.cat_name,
            ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180
                )
            ) * 6370.996 AS distance
        FROM
            `{$prefix}group` AS s
        LEFT JOIN {$prefix}merchant AS m ON s.mer_id = m.mer_id
        LEFT JOIN {$prefix}group_category AS c ON s.cat_fid = c.cat_id
        WHERE
            {$where}
        AND s.`status` = 1
        AND  s.pin_num > 2
        ORDER BY {$sort} LIMIT {$page},{$limit} ";
        $list = $this->query($sql);
        return $list;
    }
    //得到店铺下的团购
    public function platRecommendGroupList($where,$field,$order){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->join($prefix.'group_store gs','g.group_id = gs.group_id')
            ->order($order)
            ->select()->toArray();
        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @return array
     * 团购名称
     */
    public function getGroupName($where,$field){
        $ret=(new Group())->where($where)->value($field);
        return $ret;
    }
    /**
     * 获取使用时间限制描述
     *
     * @param int $isGeneral
     * @return void
     * @author: 张涛
     * @date: 2021/05/11
     */
    public function getIsGeneralDesc($isGeneral)
    {
        $desc = [
            '0' => L_('周末、法定节假日通用'),
            '1' => L_('周末不能使用'),
            '2' => L_('法定节假日不能使用'),
            '3' => L_('周末、法定节假日不能通用'),
        ];
        return $desc[$isGeneral] ?? '';
    }

    /**
     * 酒店列表
     */
    public function getHotelList($param, $limit, $field = 'g.*,g.group_id as recommend_id,g.name as title,m.name as merchant_name', $order = 'g.sort desc, g.group_id desc')
    {
        $prefix = config('database.connections.mysql.prefix');
        $res    = $this->alias('g')
            ->join($prefix . 'merchant m', 'm.mer_id = g.mer_id', 'left')
            ->join($prefix . 'group_store gs', 'gs.group_id = g.group_id', 'left')
            ->join($prefix . 'merchant_store ms', 'ms.store_id = gs.store_id', 'left')
            ->where($param)
            ->order($order)
            ->group('g.group_id')
            ->field($field)
            ->paginate($limit)
            ->toArray();
        return $res;
    }
    
    public function getStockWarnGoodsInfo($where,$page=0,$pageSize=20,$field){
        $query = $this->field($field)->alias('a')
            ->join('group_specifications b','a.group_id = b.group_id','left')
            ->where($where)
            ->order('group_id asc');
        if($page){
            $data = $query->page($page,$pageSize)->select()->toArray();
        }else{
            $data = $query->select()->toArray();
        }
            
        return $data;
    }

    public function getStockWarnGoodsCount($where){
        $data = $this->alias('a')
            ->join('group_specifications b','a.group_id = b.group_id','left')
            ->where($where)
            ->count();
        return $data;
    }


    public function getGoodsInfoAndMerchant($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix . 'merchant b','a.mer_id = b.mer_id')
            ->where($where)
            ->field($field)
            ->find()
            ->toArray();
        return $data;
    }
    
    public function getGroupList($where,$field,$limit)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('a')
            ->join($prefix . 'merchant b','a.mer_id = b.mer_id')
            ->where($where)
            ->field($field)
            ->order('a.group_id desc')
            ->paginate($limit)
            ->toArray();
        return $data;
    }

    public function getWarn($goodsInfo)
    {
        $warn = '';
        if($goodsInfo['status'] != 1){
            $warn = '商品状态异常';
        }
        return $warn;
    }


    public function merchantGoodsList($merId, $pageSize = 15, $params = [])
    {
        $prefix = config('database.connections.mysql.prefix');
        
        $result = $this->alias('s')
            ->join('merchant mt', 'mt.mer_id = s.mer_id')
            ->field(['s.group_id AS goods_id', 's.s_name as goods_name', 's.price', 's.mer_id','s.pic'])
            ->where([
                's.mer_id' => $merId,
                's.group_cate'=>'normal',
                's.status' => 1,
                'mt.status'=> 1,
            ])
            ->where('s.group_id', 'NOT IN', function ($query)use($prefix) {
                $query->table($prefix.'wechat_live_goods')
                    ->where('goods_type', 'group')
                    ->field('goods_id');
            });
            if(!empty($params['goods_name'])){
                $result = $result->where('s.s_name', 'like', "%{$params['goods_name']}%");
            }
            
            $result = $result->paginate(min($pageSize, 100))
            ->each(function ($item) {
                $imgs = explode(';', $item['pic']);
                $item['cover_image'] = $imgs ? replace_file_domain($imgs[0]) : '';
                $item['store_name'] = '--';
                $item['store_id'] = 0;
            });

        return $result;
    }
    
    public function getGoodsCustomList($where,$field='*',$page=0,$pageSize=10,$order='gt.id asc',$group='',$custom_id=0){
        $query = $this->alias('g')
            ->field($field)
            ->join('group_store gs','gs.group_id = g.group_id')
            ->join('merchant_store s','gs.store_id = s.store_id')
            ->join('merchant m','s.mer_id = m.mer_id')
            ->join('group_renovation_goods_custom_top gt','g.group_id = gt.group_id'.($custom_id?' and gt.custom_id = '.$custom_id:''),'left')
            ->where($where)
            ->order($order);
        if($group){
            $query = $query->group($group);
        }
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}