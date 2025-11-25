<?php
/**
 * 店铺model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/16 10:48
 */

namespace app\merchant\model\db;

use think\facade\Config;
use think\Model;

class MerchantStore extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    const AUTH_SUCCESS = 3;//审核状态（0：未提交，1：审核中，2：已拒绝，3：已通过，4：再次提交审核5：已驳回）

    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
    
    /**
     * 根据条件获取其他模块店铺列表
     * @param $tableName string 其它店铺表名
     * @param $where '' 条件
     * @param $order array 排序
     * @param $field string 查询字段
     * @param $page int 当前页数
     * @param $pageSize int 每页显示数量
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreListByModule($tableName, $where = '', $order = [], $field = 's.*,ms.*,mm.*', $page = '1', $pageSize = '10')
    {
        if (empty($tableName)) {
            return false;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $this->name = _view($this->name);
        if (!$page) {
            $result = $this->alias('s')
                ->field($field)
                ->join($prefix . $tableName . ' ms', 'ms.store_id=s.store_id')
                ->join($prefix . _view('merchant') . ' mm', 'mm.mer_id = s.mer_id')
                ->whereRaw($where)
                ->order($order)
                ->limit($pageSize)
                ->select();
        } else {
            $result = $this->alias('s')
                ->field($field)
                ->join($prefix . $tableName . ' ms', 'ms.store_id=s.store_id')
                ->join($prefix . _view('merchant') . ' mm', 'mm.mer_id = s.mer_id')
                ->whereRaw($where)
                ->order($order)
                ->page($page, $pageSize)
                ->select();

        }
        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param int $page
     * @param int $pageSize
     * @return mixed
     * 店铺管理---店铺列表
     */
    public function getStoreByWhereList($where, $field, $order, $page = 1, $pageSize = 20)
    {
        $prefix = config('database.connections.mysql.prefix');

        $result = $this->alias('s')
            ->leftJoin($prefix . 'area' . ' a', 's.area_id=a.area_id')
            ->where($where);
        $count = $result->count();
        if ($count < 1) {
            $list = [];
        } else {
            if($pageSize){
                $list = $result->page($page, $pageSize)->field($field)->order($order)->select()->toArray();
            }else{
                $list = $result->field($field)->order($order)->select()->toArray();
            }
        }
        $res['list'] = $list;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;
    }

    /**
     * 根据条件获取其他模块店铺总数
     * @param $tableName string 其它店铺表名
     * @param $where '' 条件
     * @param $order array 排序
     * @param $field string 查询字段
     * @param $page int 当前页数
     * @param $pageSize int 每页显示数量
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreCountByModule($tableName, $where = '')
    {
        if (empty($tableName)) {
            return false;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this->alias('s')
            ->join($prefix . $tableName . ' ms', 'ms.store_id=s.store_id')
            ->join($prefix . 'merchant mm', 'mm.mer_id = s.mer_id')
            ->whereRaw($where)
            ->count();
        return $result;
    }

    /**
     * 根据店铺ID获取店铺
     * @param $storeId 店铺ID
     * @return array|bool|Model|null
     */
    public function getStoreByStoreId($storeId)
    {
        if (!$storeId) {
            return null;
        }

        $where = [
            'store_id' => $storeId
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }

    public function getStoresByIds($ids)
    {
        if (empty($ids)) return [];
        $where = [
            ['store_id', 'in', $ids]
        ];

        $this->name = _view($this->name);
        return $this->getSome($where);
    }


    /**附近好店 按当前位置距离、评分展示店铺
     * @param $where
     * @param $lng
     * @param $lat
     * @param $limit
     * @return mixed
     *
     */
    public function getStoresDistance($where, $lng, $lat, $limit)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = "SELECT s.mer_id,s.store_id,s.logo,s.name,s.pic_info,m.score_mean as score";
        if ($lng > 0 && $lat > 0) {
            $sql .= ",ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180
                )
            ) * 6370.996 AS distance";
        }


        $sql .= " FROM
                    `{$prefix}merchant_store` AS s
                LEFT JOIN {$prefix}merchant AS mm ON s.mer_id = mm.mer_id
                LEFT JOIN {$prefix}merchant_store_meal AS m ON s.store_id = m.store_id
                WHERE
                    {$where}
                ORDER BY ";
        if ($lng > 0 && $lat > 0) {
            $sql .= "distance ASC,";
        }

        $sql .= "m.score_mean DESC LIMIT {$limit}";
        $list = $this->query($sql);
        return $list;
    }

    /**团购商品详情 更多商家(显示距该用户直线距离最近的10家店铺)
     * @param $where
     * @param $lng
     * @param $lat
     * @param $limit
     * @return mixed
     *
     */
    public function getGroupRecommendStoreList($where, $lng, $lat, $limit)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = "SELECT s.*";

        $where .= ' AND g.end_time>' . time() . ' AND g.begin_time<' . time() . ' AND g.status=1 AND s.status=1 ';
        if ($lng > 0 && $lat > 0) {
            $sql .= ",ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180
                )
            ) * 6370.996 AS distance";
        }


        $sql .= " FROM
                    `{$prefix}merchant_store` AS s
                JOIN {$prefix}group_store AS gs ON gs.store_id = s.store_id
                JOIN {$prefix}group AS g ON g.group_id = gs.group_id
                WHERE
                    {$where}
                ORDER BY ";
        if ($lng > 0 && $lat > 0) {
            $sql .= "distance ASC,";
        }

        $sql .= "s.store_id DESC LIMIT {$limit}";
        $list = $this->query($sql);
        return $list;
    }

    /**优选好店 按照分类下店铺评分以及销量展示店铺
     * @param $where
     * @param $lng
     * @param $lat
     * @param $limit
     * @return mixed
     *
     */
    public function getStoresSelect($where, $lng, $lat, $limit)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = "SELECT s.mer_id,s.store_id,s.logo,s.name,s.pic_info,s.score";
        if ($lng && $lat) {
            $sql .= ",ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180
                )
            ) * 6370.996 AS distance";
        }

        $sql .= " FROM
                `{$prefix}merchant_store` AS s
            LEFT JOIN {$prefix}merchant_store_meal AS m ON s.store_id = m.store_id
            LEFT JOIN {$prefix}merchant AS mt ON mt.mer_id = s.mer_id
            WHERE
                {$where}
            ORDER BY score DESC,m.sale_count DESC LIMIT {$limit} ";
        $list = $this->query($sql);
        return $list;
    }

    /**top打卡店 所有团购商品店铺分类中 默认展示评分最高 且销量最高的店铺
     * @param $where
     * @param $lng
     * @param $lat
     * @param $limit
     * @return mixed
     *
     */
    public function getStoresTop($where, $lng, $lat, $limit)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = "SELECT s.mer_id,s.store_id,s.logo,s.name,s.pic_info,m.score_mean as score";
        if ($lng > 0 && $lat > 0) {
            $sql .= ",ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180
                )
            ) * 6370.996 AS distance";
        }

        $sql .= " FROM
            `{$prefix}merchant_store` AS s
        LEFT JOIN {$prefix}merchant_store_meal AS m ON s.store_id = m.store_id
        LEFT JOIN {$prefix}merchant AS mm ON s.mer_id = mm.mer_id
        WHERE
            {$where}
        ORDER BY score DESC,m.sale_count DESC LIMIT {$limit} ";
        $list = $this->query($sql);
        return $list;
    }


    /**根据用户经纬度获取最近一家店铺信息
     * @param $where
     * @param $lng
     * @param $lat
     * @param $order
     * @param $limit
     * @return mixed
     */
    public function getUserDistance($where, $lng, $lat, $order, $page, $pageSize)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $limit_str = '';
        if ($page > 0) {
            $limit = ($page - 1) * $pageSize;
            $limit_str .= " LIMIT  {$limit},{$pageSize} ";
        }
        $sql = "SELECT s.*,m.score_mean as score";

        if ($lng > 0 && $lat > 0) {
            $sql .= ",ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180
                )
            ) * 6370.996 AS distance";
        }

        $sql .= " FROM `{$prefix}merchant_store` AS s
                LEFT JOIN {$prefix}merchant_store_meal AS m ON s.store_id = m.store_id
                WHERE
                    {$where}
                GROUP BY s.store_id
                ORDER BY {$order} {$limit_str} ";
        $list = $this->query($sql);
        return $list;
    }


    public function getList($where = [], $field = true, $order = [], $page = 0, $limit = 0)
    {
        if (!$where) {
            return null;
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if ($limit == 0) {
            $result = $this->alias('s')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix . 'merchant_category c', 's.cat_id = c.cat_id')
                ->leftJoin($prefix . 'merchant_store_meal l', 's.store_id = l.store_id')
                ->order($order)
                ->select();
        } else {
            $result = $this->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix . 'merchant_category c', 's.cat_id = c.cat_id')
                ->leftJoin($prefix . 'merchant_store_meal l', 's.store_id = l.store_id')
                ->page($page, $limit)
                ->order($order)
                ->select();
        }

        if (empty($result)) {
            return [];
        } else {
            return $result->toArray();
        }
    }

    public function getStoreList($where, $page, $pageSize,$field=true)
    {
        $arr = $this->where($where)->field($field);
        if($page==0){
            $arr=$arr->select();
        }else{
            $arr=$arr->page($page, $pageSize)->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function getStoreListCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * 根据条件查找店员列表
     */
    public function getStaffManagementList($where, $field, $order, $page, $pageSize)
    {

    }

    /**
     * @param $where
     * @param $page
     * @param $pageSize
     * @param string $order
     * @return mixed
     * 团购预约礼店铺列表
     */
    public function getAppointGiftList($where, $page, $pageSize, $order = 'b.store_id asc')
    {
        $result = $this->alias('b')
            ->where($where)
            ->field("b.name,b.store_id,c.gift")
            ->leftJoin('group_appoint_gift c', 'c.store_id = b.store_id');

        $assign['count'] = $result->count();
        $assign['list'] = $result->page($page, $pageSize)
            ->order($order)
            ->select()
            ->toArray();

        return $assign;
    }

    /**
     * @param $where
     * @return mixed
     * 查询店铺以及分类
     */
    public function getStoreByCategory($where,$page){
        $result = $this->alias('s')
            ->where($where)
            ->field("s.*,c.cat_name,a.area_name")
            ->join('merchant_category c', 's.cat_id = c.cat_id')
            ->join('area a', 's.circle_id = a.area_id')
            ->join('merchant m', 'm.mer_id = s.mer_id')
            ->page($page,10)
            ->select()
            ->toArray();
        return $result;
    }

    /**
     * @param $where
     * @return mixed
     * 查询店铺以及分类
     */
    public function getStoreByCategoryNew($where,$page){
        $result = $this->alias('s')
            ->where($where)
            ->field("s.*,c.cat_name")
            ->join('merchant_category c', 's.cat_id = c.cat_id')
            ->join('merchant m', 'm.mer_id = s.mer_id')
            ->page($page,10)
            ->select()
            ->toArray();
        return $result;
    }

    /**
     * @param $where
     * @return mixed
     * 店铺预约礼信息
     */
    public function getAppointGiftMsg($where)
    {
        $result = $this->alias('b')
            ->where($where)
            ->field("b.name,b.store_id,c.gift")
            ->leftJoin('group_appoint_gift c', 'c.store_id = b.store_id');
        $assign['list'] = $result
            ->find()
            ->toArray();
        return $assign;
    }

    /**
     * @param $where
     * @return mixed
     * 查询店铺以及分类
     */
    public function getStoreByCategoryList($condition_where, $order, $condition_field,$page,$pageSize){
        $result = $this->alias('s')
            ->field($condition_field)
            ->join('merchant mm', 'mm.mer_id = s.mer_id')
            ->whereRaw($condition_where)
            ->order($order)
            ->page($page,$pageSize)
            ->select();
        return $result;
    }

    

    /* 种草文章绑定的店铺列表
     * @param $where
     * @param $lng
     * @param $lat
     * @param $limit
     * @return mixed
     *
     */
    public function getGrowGrassStoreList($where, $lng, $lat, $page, $limit)
    {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = "SELECT s.*,fs.is_queue,fs.is_book";
        $start = ($page-1)*$limit;
        if(!empty($where)){
            $where = $where.' AND s.status=1 AND a.is_del=0 AND b.is_del=0 AND a.is_system_del = 0 AND a.status = 20 AND a.is_manuscript=0';
        }else{
            $where = ' s.status=1  AND a.is_del=0 AND b.is_del=0 AND a.is_system_del = 0 AND a.status = 20 AND a.is_manuscript=0';
        }
        if ($lng > 0 && $lat > 0) {
            $sql .= ",ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180
                )
            ) * 6370.996 AS distance";
        }


        $sql .= " FROM
                    `{$prefix}merchant_store` AS s
                LEFT JOIN {$prefix}merchant_store_foodshop as fs ON fs.store_id = s.store_id
                JOIN {$prefix}grow_grass_bind_store as b ON b.store_id = s.store_id
                JOIN {$prefix}grow_grass_article as a ON b.article_id = a.article_id
                WHERE
                    {$where}
                ORDER BY ";
        if ($lng > 0 && $lat > 0) {
            $sql .= "distance ASC,";
        }

        $sql .= "a.publish_time DESC,s.store_id DESC LIMIT {$start},{$limit}";
        $list = $this->query($sql);
        return $list;
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除店铺
     */
    public function storeDel($where){
       return $this->where($where)->delete();
    }

   /**
     * 根据条件返回分组总数
     * @param $where array 条件
     * @param $groupField string 分组字段
     * @return object
     */
    public function getCountByGroup($where, $groupField = ''){

        return $this->where($where)->field('count(store_id) as count, '.$groupField)->group($groupField)->select();
     }

    // 多语言获取商家店铺总数
    public function getFoodshopStoreCountByMerId($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $this->name = _view($this->name);
        $count = $this->alias('s')
            ->join([$prefix . _view('merchant_store_foodshop') => 'f'], 's.store_id=f.store_id', 'LEFT')
            ->join([$prefix . _view('merchant') => 'm'], 'm.mer_id=s.mer_id')
            ->where($where)
            ->count();
        return $count;
    }

    // 多语言获取商家店铺列表
    public function getFoodshopStoreListByMerId($where, $fields, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $this->name = _view($this->name);
        $lists = $this->alias('s')
            ->join([$prefix . _view('merchant_store_foodshop') => 'f'], 's.store_id=f.store_id', 'LEFT')
            ->join([$prefix . _view('merchant') => 'm'], 'm.mer_id=s.mer_id')
            ->field($fields)
            ->where($where)
            ->order('s.sort', 'DESC')
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select()
            ->toArray();
        return $lists;
    }

    //根据条件查出数据
    public function getIdsByWhere($where,$field)
    {
        $ids = $this->where($where)->column($field);
        return $ids;
    }
    
    /**
     * 查询店铺列表
     */
    public function getStore($tableName,$where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('s')
            ->field($field)
            ->join($prefix . $tableName . ' ms', 'ms.store_id=s.store_id')
            ->where($where)
            ->field($field)
            ->order('s.store_id desc')
            ->select()
            ->toArray();
        return $result;
    }
}
