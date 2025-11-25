<?php
/**
 * 外卖商品model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 09:35
 */

namespace app\shop\model\db;
use think\Model;
use think\facade\Env;
class ShopGoods extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    
    protected $pk = 'goods_id';
    /**
     * 根据条件获取商品列表
     * @param $where
     * @param $order 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsListByCondition($where, $order=[], $limit='0', $page='1') {
       if(empty($where)) {
            return false;
        }
        $this->name = _view($this->name);
        if($limit>0){
            $result = $this->where($where)
                            ->order($order)
                            ->page($page,$limit)
                            ->select();
        }else{
            $result = [];
            $this->withoutField('des,describe')->where($where)->order($order)->chunk(1000,function ($item) use(&$result){
                foreach ($item as $k => $v){
                    array_push($result,$v);
                }
            });
        }
        return $result;
    }

    
    /**
     * 根据id获取商品
     * @param $goodsId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsByGoodsId($goodsId) {
        if(empty($goodsId)) {
             return false;
        }
        
        $where = [
            'goods_id' => $goodsId
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 获取详情
     * @param $where
     * @return array
     */
    public function getDetail($where, $field = '*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'merchant_store ms', 'ms.store_id = r.store_id')
            ->join($prefix . 'merchant m', 'm.mer_id = ms.mer_id')
            ->where($where)
            ->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

	/**
     * 根据条件获取其他模块商品列表
     * @param $tableName string 其它商品表名
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsListByModule($tableName, $where=[], $order=[], $limit='0', $page='1', $field = '*') {
        if(empty($tableName)) {
            return false;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $this->name = _view($this->name);
        if($tableName == 'mall_goods'){
            if($limit>0){
                $result = $this->alias('s')
                    ->field($field)
                    ->join($prefix.$tableName.' t','s.goods_id = t.common_goods_id')
                    ->where($where)
                    ->page($page,$limit)
                    ->order($order)
                    ->select();
            }else{
                $result = $this->alias('s')
                    ->field($field)
                    ->join($prefix.$tableName.' t','s.goods_id = t.common_goods_id')
                    ->where($where)
                    ->order($order)
                    ->select();
            }
        }else{
            $this->name = _view($this->name);
            if($limit>0){
                $result = $this->alias('s')
                    ->field($field)
                    ->join($prefix.$tableName.' t','s.goods_id = t.goods_id')
                    ->where($where)
                    ->page($page,$limit)
                    ->order($order)
                    ->select();
            }else{
                $result = $this->alias('s')
                    ->field($field)
                    ->join($prefix.$tableName.' t','s.goods_id = t.goods_id')
                    ->where($where)
                    ->order($order)
                    ->select();
            }
        }
        return $result;
    }


    /**
     * 根据条件获取其他模块商品总数
     * @param $tableName string 其它商品表名
     * @param $where array 条件
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsCountByModule($tableName, $where=[]) {
        if(empty($tableName)) {
            return false;
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $this->name = _view($this->name);
        $result = $this ->alias('s')
            ->join($prefix.$tableName.' t','s.goods_id = t.goods_id')
            ->where($where)
            ->count();
//        echo $this->getLastSql();die;
        return $result;
    }
    
	/**
     * 根据条件获取其他模块商品详情
     * @param $tableName string 其它商品表名
     * @param $goodsId intval 商品id
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsDetailByModule($tableName, $goodsId) {
        if(empty($tableName)) {
            return false;
        }
        
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $where = [
            's.goods_id' => $goodsId
        ];

        $this->name = _view($this->name);
        $result = $this ->alias('s')
                        ->join($prefix.$tableName.' t','s.goods_id = t.goods_id')
                        ->where($where)
                        ->find();
        return $result;
     }
    
    /**
     * 根据id更新数据
     * @param $goodsId
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateByGoodsId($goodsId,$data) {
        if(!$goodsId || !$data){
            return false;
        }

        $where = [
            'goods_id' => $goodsId
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * @param $field_spec
     * @param $where
     * @return array
     *根据商品id获取规格信息
     * @author 朱梦群
     */
    public function getSpec($field_spec, $where)
    {
        $prefix = config('database.connection.mysql.prefix');

        $this->name = _view($this->name);
        $arr = $this->alias('s')
            ->join([$prefix . _view('shop_goods_spec') => 'gs'], 's.goods_id = gs.goods_id')
            ->field($field_spec)
            ->where($where)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @param $whereTime
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 获取时间段数据
     */
    public function getList($where,$whereTime){
        $result = $this->where($where)->whereTime('last_time',$whereTime)->count();
        return $result;
    }

    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getListTool($where, $field = 'r.*',$page=1,$pageSize=10)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'merchant_store ms', 'ms.store_id = r.store_id')
            ->join($prefix . 'merchant m', 'm.mer_id = ms.mer_id')
            ->where($where)
            ->order('r.goods_id desc');
        $out['total']=$arr->count();
        $out['list']=$arr->page($page, $pageSize)
            ->select()->toArray();
        return $out;
    }

    /**
     * 获取列表
     * @param $where array
     * @return array
     */
    public function getActivityList($where = [], $limit = 0, $field = 'r.goods_id,r.price,r.employee_lables,r.name as goods_name,ms.name as store_name,m.name as mer_name', $order = 'r.goods_id desc') {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix . 'merchant_store ms', 'ms.store_id = r.store_id')
                ->join($prefix . 'merchant m', 'm.mer_id = ms.mer_id')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit > 0) {
            $data = $this->alias('r')
                ->field($field)
                ->join($prefix . 'merchant_store ms', 'ms.store_id = r.store_id')
                ->join($prefix . 'merchant m', 'm.mer_id = ms.mer_id')
                ->where($where)
                ->limit($limit)
                ->order($order)
                ->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        } else {
            $data = $this->alias('r')
                ->field($field)
                ->join($prefix . 'merchant_store ms', 'ms.store_id = r.store_id')
                ->join($prefix . 'merchant m', 'm.mer_id = ms.mer_id')
                ->where($where)
                ->order($order)
                ->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        }
        return $arr;
    }

    public function getShopList($where,$field='r.*',$limit=10,$order='a.goods_id desc')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->field($field)
            ->join($prefix . 'merchant_store c', 'a.store_id = c.store_id')
            ->join($prefix . 'merchant b', 'c.mer_id = b.mer_id')
            ->where($where)
            ->order($order)
            ->paginate($limit)
            ->toArray();
        return $arr;
    }
    
    public function getWarn($goodsInfo)
    {
        $warn = '';
        if($goodsInfo['audit_status'] != 1){
            $warn = '商品审核状态异常';
        }
        if($goodsInfo['status'] != 1){
            $warn = $warn ? $warn.'/商品已下架' : '商品已下架';
        }
        if($goodsInfo['store_status'] != 1){
            $warn = $warn ? $warn.'/店铺状态异常' : '店铺状态异常';
        }
        if(!$goodsInfo['have_shop']){
            $warn = $warn ? $warn.'/未开启外卖店铺' : '未开启外卖店铺';
        }
        return $warn;
    }

    public function merchantStore()
    {
        return $this->belongsTo(\app\mall\model\db\MerchantStore::class, 'store_id', 'store_id');
    }

    public function merchantGoodsList($merId, $pageSize = 15, $params = [])
    {
        $prefix = config('database.connections.mysql.prefix');
        
        $result = $this->alias('s')
            ->join('merchant_store_shop m', 's.store_id = m.store_id')
            ->join('merchant_store g', 's.store_id = g.store_id')
            ->join('merchant mt', 'mt.mer_id = g.mer_id')
            ->field(['s.goods_id', 's.name as goods_name', 's.price', 's.store_id','s.image'])
            ->where([
                'g.mer_id'       => $merId,
                'g.have_shop' => 1,
                's.status' => 1,
                'g.status'       => 1,
            ])
            ->with(['merchantStore' => function ($query) {
                $query->field('store_id, name');
            }])
            ->where('s.goods_id', 'NOT IN', function ($query)use($prefix) {
                $query->table($prefix.'wechat_live_goods')
                    ->where('goods_type', 'shop')
                    ->field('goods_id');
            });
            if(!empty($params['goods_name'])){
                $result = $result->where('s.name', 'like', "%{$params['goods_name']}%");
            }
            
            $result = $result->paginate(min($pageSize, 100))
            ->each(function ($item) {
                $item['store_name'] = $item['merchantStore']['name'];
                $imgs = explode(';', $item['image']);
                $item['cover_image'] = $imgs ? replace_file_domain($imgs[0]) : '';
                unset($item['merchantStore']);
            });

        return $result;
    }
}