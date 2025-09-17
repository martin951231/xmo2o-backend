<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/31
 * Time: 11:34
 */

namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingPersonSalesman extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

    //获取数据
    public function getOneData($param) {
        $res = $this->where($param)->find();
        return $res;
    }

    //根据条件查出数据的所有ID
    public function getIdsByWhere($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $ids = $this->alias('a')
            ->leftJoin($prefix.'new_marketing_team b','b.id = a.team_id')
            ->where($where)
            ->column('a.person_id');
        return $ids;
    }

    //根据条件查出数据
    public function getCanSalesmanFind($where,$field=true)
    {
        $prefix = config('database.connections.mysql.prefix');
        $ids = $this->alias('g')
            ->leftJoin($prefix.'new_marketing_person b','b.id = g.person_id')
            ->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $ids;
    }
}