<?php


namespace app\mall\model\db;
use app\mall\model\db\MallFullLayer;
use think\Model;
use think\facade\Db;
class MallFullAct extends Model
{
    public function getOne($goods_id,$store_id){
        $where[] = [
            'is_del','=',0,
        ];
        $where[] = ['','exp',Db::raw("FIND_IN_SET($goods_id,goods_id)")];
        $info=[];
        $arr= $this->where($where)->select()->toArray();
        //var_dump($this->getLastSql());
        if($arr){
            $layer_id=$arr[0]['layer_id'];
            $info = (new MallFullLayer())->getOne($layer_id,$store_id);
        }
        return $info;
    }
}