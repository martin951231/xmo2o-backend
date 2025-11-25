<?php
/**
 * 企业微信应用
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/4/2 17:23
 */
namespace app\community\model\db;
use think\Model;

class VillageQywxAgent extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function dataUpdate($data){
        $result = $this->update($data);
        return $result;
    }
}