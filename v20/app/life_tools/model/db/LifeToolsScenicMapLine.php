<?php
declare (strict_types = 1);

namespace app\life_tools\model\db;

use map\longLat;
use think\Model;

/**
 * @mixin \think\Model
 */
class LifeToolsScenicMapLine extends Model
{
    protected $json = ['scenic_location_img', 'scenic_location_line', 'line_img'];

    protected $jsonAssoc = true;

    public function getPlaceAttr($value,$data)
    {
       $data['location_ids'] = explode(',',$data['location_ids']);
       $place = LifeToolsScenicMapPlace::whereIn('id',$data['location_ids'])
           ->append(['tx_coordinate'])
           ->select()
           ->toArray();

       $placeArr = array_column($place,null,'id');

       $placeSortData = [];
       foreach ($data['location_ids'] as $id){
           !empty($placeArr[$id]) && array_push($placeSortData,$placeArr[$id]);
       }

       return $placeSortData;
    }

    public function getLocationIdsAttr($value,$data)
    {
        if(!empty($value)){
            return explode(',',$value);
        }
        return [];
    }

    public function setLocationIdsAttr($value,$data)
    {
        if(!empty($value)){
            return implode(',',$value);
        }
        return $value;
    }

    public function getScenicLocationImgAttr($value,$data)
    {
        foreach ($value as &$item){
            $item = replace_file_domain($item);
        }

        return $value;
    }

    public function getTxScenicLocationLineAttr($value,$data)
    {
        $txScenicLocationLine = [];
        if($data['scenic_location_line']){
            foreach ($data['scenic_location_line'] as $item){
                array_push($txScenicLocationLine, (new longlat())->baiduToGcj02($item['latitude'], $item['longitude']));
            }
        }
        return $txScenicLocationLine;
    }
}
