<?php
/**
 * 商家和关系表 model
 * Author: chenxiang
 * Date Time: 2020/5/25 14:06
 */

namespace app\common\model\db;

use think\Model;

class MerchantUserRelation extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取商家和用户关系信息
     * User: chenxiang
     * Date: 2020/5/25 19:42
     * @param array $where
     * @return array|mixed|Model|null
     */
    public function getDataMerUserRel($where = [])
    {
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 添加商家和用户关系 信息
     * User: chenxiang
     * Date: 2020/5/25 20:04
     * @param array $data
     * @return int|string
     */
    public function addMerUserRel($data = [])
    {
        $result = $this->insert($data);
        return $result;
    }

    public function delOne($where)
    {
        return $this->where($where)->delete();
    }
    public function get_merchant_fans($where){
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('m')
            ->join($prefix.'user u','`m`.`openid`=`u`.`openid`')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 获得用户收藏的店铺列表
     * User: hengtingmei
     * Date: 2021/05/18 
     * @param array $data
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $pageSize
     * @return Model
     */
    public function getStoreCollectList($where, $field='c.*s.*', $order=[], $page=1, $pageSize=10, $lng='', $lat=''){
        $prefix = config('database.connections.mysql.prefix');

        if ($lng > 0 && $lat > 0) {
            $field .= ", ACOS(
                SIN(({$lat} * 3.1415) / 180) * SIN((s.lat * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.lat * 3.1415) / 180) * COS(
                    ({$lng} * 3.1415) / 180 - (s.long * 3.1415) / 180
                )
            ) * 6370.996 AS distance";
            // $order['distance'] = 'DESC';
        }

        $res = $this->alias('c')
            ->field($field)
            ->join($prefix.'merchant_store s','`s`.`store_id`=`c`.`store_id`')
            ->where($where)
            ->where('c.store_id','>',0)
            ->where('c.type','in','foodshop,shop,mall,store')
            ->where('s.status','=',1)
            ->order($order)
            ->page($page,$pageSize)
            ->select();
        return $res;
    }

    

    /**
     * 获得用户收藏的店铺总数
     * User: hengtingmei
     * Date: 2021/05/28 
     * @param array $where
     * @return Model
     */
    public function getStoreCollectCount($where){
        $prefix = config('database.connections.mysql.prefix');
        $res = $this->alias('c')
            ->join($prefix.'merchant_store s','`s`.`store_id`=`c`.`store_id`')
            ->where($where)
            ->where('c.type','in','foodshop,shop,mall,store')
            ->where('c.store_id','>',0)
            ->where('s.status','=',1)
            ->count();
        return $res;
    }
}
