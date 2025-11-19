<?php

namespace app\merchant\model\db;

use think\Model;

class MerchantAccountCustomMenu extends Model
{
   public function setMenuIdsAttr(array $value)
   {
       return implode(',',$value);
   }
   
   public function getMenuIdsAttr(string $value)
   {
       return explode(',',$value);
   }

}