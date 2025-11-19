<?php


namespace app\store_marketing\model\db;


use think\Model;

class StoreMarketingPersonStore extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 自增
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setInc($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->inc($field, $num)->update();
        return $result;
    }

    /**
     * 删除数据
     */
    public function delData($where){
        $result = $this->where($where)->delete();
        return $result;
    }
    /**
     * 查询分销员绑定的店铺
     * @param person_id 分销员id 
     * @param pageNum 每页显示数量
     * @return array;
     */

    public function getPersonStoreList($person_id, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field = array(
            'ps.id',
            'ps.person_id',
            'ps.mer_id',
            'ps.store_id',
            'ps.status',
            's.name',
            's.score',
            's.pic_info',
            's.have_mall',
            's.have_group'
        );
        $where = array(
            ['s.open_store_marketing', '=', 1],//是否开启店铺分销:是
            // ['s.auth', '=', 3],//审核状态：已通过
            ['s.name', '<>', ''], 
            ['ps.person_id', '=', $person_id],
            ['ps.is_del', '=', 0],
            ['ps.status', '<>', 3]
        ); 
        $list = $this->alias('ps')
            ->field($field)
            ->leftjoin($prefix.'merchant_store s', 'ps.store_id = s.store_id')
            ->where($where) 
            ->paginate($pageSize);

        return $list;
    }

    /**
     * 判断分销人员是否绑定店铺
     * @param person_id 分销员id 
     * @param store_id 店铺id
     * @return boolean;
     */
    public function checkPersonOfStore($person_id, $store_id)
    {
        $where = array(
            ['person_id', '=', $person_id],
            ['store_id', '=', $store_id],
            ['is_del', '=', 0]
        );
        return $this->field('id')->where($where)->count() > 0 ? true : false;
    }

    /**
     * 绑定/解绑店铺
     * 
     */
    public function personBindStore($psid)
    {
        $info = $this->find($psid);
        if(!$info){
            return false;
        }
        $info->status = $info->status == 1 ? 2 : 1;
        $info->save();
        return $info;
    }

    /**
     * 删除绑定
     * 
     */
    public function delPersonStore($psid)
    {
        $info = $this->find($psid);
        if(!$info){
            return false;
        }
        //软删除改为拒绝
        $info->status = 3;
        $info->save();
        return true;
    }

}

