<?php
/**
 * 手机国家区号
 * Created by PhpStorm.
 * User: zhumengqun
 * Date: 2020/8/28
 */
namespace app\common\model\db;
use think\Model;
class NationalPhone extends Model
{

    public function getNationlCode($where){
        $code = $this->field(true)->where($where)->order("sort desc")->select();
        return $code;
    }
}