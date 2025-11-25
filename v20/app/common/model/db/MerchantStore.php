<?php
/**
 * 商户店铺表
 * Created by PhpStorm.
 * User: wangchen
 * Date: 2021/9/1
 */

namespace app\common\model\db;

use think\Model;

class MerchantStore extends Model
{
    protected $pk = 'store_id';
    use \app\common\model\db\db_trait\CommonFunc;
    
    public function getMerUserPhoneAttr($value, $data)
    {
        return Merchant::where('mer_id', $data['mer_id'])->value('phone');
    }
}