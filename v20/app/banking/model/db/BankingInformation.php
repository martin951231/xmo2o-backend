<?php
/**
 * 农商行产品金融资讯
 */

namespace app\banking\model\db;
use think\Model;
class BankingInformation extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where, $limit, $field = '*', $sort = 'pigcms_id desc') {
        $arr = $this->where($where)->field($field)->order($sort)->paginate($limit)->toArray();
        return $arr;
    }

}