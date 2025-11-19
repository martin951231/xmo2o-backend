<?php

namespace app\merchant\model\db;

use think\Model;

class MerchantCustomMenu extends Model
{
   public function searchNoAuthAttr($query, $value, $data)
   {
       $noAuth =Merchant::merchantNoAuthMenu($data['merchant_id']);
       !empty($noAuth) && $query->whereNotIn('type',$noAuth);
   }
    
   public function getNameAttr($value,$data)
   {
       $typeName = [
           //'merchant' => cfg('merchant_alias_name'),
           'shop'     => cfg('shop_alias_name'),
           'new_mall' => cfg('mall_alias_name'),
           'appoint'  => cfg('appoint_alias_name'),
           'group'    => cfg('group_alias_name')
       ];
       $name = !empty($typeName[$data['type']]) ? $typeName[$data['type']] : $value;
       
       return L_($name);
   }
}