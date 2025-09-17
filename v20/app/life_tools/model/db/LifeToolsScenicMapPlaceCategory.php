<?php
declare (strict_types = 1);

namespace app\life_tools\model\db;

use think\Model;

/**
 * @mixin \think\Model
 */
class LifeToolsScenicMapPlaceCategory extends Model
{
    public static function onBeforeDelete($category)
    {
        $place = LifeToolsScenicMapPlace::where('category_id',$category->id)->find();
        !empty($place) && custom_exception('当前分类已被使用，不可删除！');
        return true;
    }

    public function searchNameAttr($query, $value)
    {
        !empty($value) && $query->where('category_name','like', "%$value%");
    }
}
