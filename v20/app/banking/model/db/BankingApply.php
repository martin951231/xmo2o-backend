<?php
/**
 * 金融产品申请列表
 * Author: hengtingmei
 * Date Time: 2022/01/08
 */

namespace app\banking\model\db;
use think\Model;
class BankingApply extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    public function getSomeAndPage($where = [], $field = 'a.*,h.village_name',$order=true,$page=0,$pageSize=0){

        if($pageSize){
            $limit = [
                'page' => $page ?? 1,
                'list_rows' => $pageSize
            ];
            $result =  $this->alias('a')
                            ->leftJoin($this->dbPrefix().'house_village h','h.village_id=a.village_id')
                            ->leftJoin($this->dbPrefix().'banking b','b.banking_id=a.banking_id')
                            ->where($where)
                            ->field($field)
                            ->order($order)
                            ->paginate($limit);
        }else{
            $result =  $this->alias('a')
                            ->leftJoin($this->dbPrefix().'house_village h','h.village_id=a.village_id')
                            ->leftJoin($this->dbPrefix().'banking b','b.banking_id=a.banking_id')
                            ->where($where)
                            ->field($field)
                            ->order($order)
                            ->select();
        }
        
        return $result;
    }

    public function getCount($where = []){
        $result = $this->alias('a')
                    ->leftJoin($this->dbPrefix().'house_village h','h.village_id=a.village_id')
                    ->where($where)
                    ->count();
        return $result;
    }
}