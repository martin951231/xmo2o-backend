<?php
declare (strict_types = 1);

namespace app\life_tools\model\db;

use map\longLat;
use think\Model;

/**
 * @mixin \think\Model
 */
class LifeToolsScenicMapPlace extends Model
{
    protected $json = ['monitor', 'location_img'];

    protected $jsonAssoc = true;

    public function category()
    {
        return $this->belongsTo(LifeToolsScenicMapPlaceCategory::class, 'category_id','id');
    }

    public function getTxCoordinateAttr($value,$data): array
    {
        return (new longlat())->baiduToGcj02($data['latitude'], $data['longitude']);
    }

    public function getLocationImgAttr($value,$data)
    {
        foreach ($value as &$item){
            $item = replace_file_domain($item);
        }

        return $value;
    }

    public function getMarkIconAttr($value,$data)
    {
        return replace_file_domain($value);
    }

    public function getOrderIntroduceAttr($value,$data)
    {
        return replace_file_domain($value);
    }
}
