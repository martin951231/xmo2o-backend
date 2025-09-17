<?php
/**
 * 课程、体育馆、景区service
 * @date 2021-12-17
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsScenicMap;
use app\life_tools\model\db\LifeToolsScenicMapLine;
use app\life_tools\model\db\LifeToolsScenicMapPlace;
use app\life_tools\model\db\LifeToolsScenicMapPlaceCategory;
use map\Coordinate;

class LifeToolsScenicMapService
{
    //region 后台管理
    //region 地图

    public function scenicList(int $merchantId)
    {
        $scenicIds = LifeToolsScenicMap::where('merchant_id',$merchantId)
            ->field('scenic_id')
            ->select()
            ->column('scenic_id');

        $scenicList = LifeTools::alias('t')
            ->join('merchant m','m.mer_id = t.mer_id')
            ->where('t.is_del','=', 0)
            ->where('t.mer_id','=', $merchantId)
            ->where('t.type','=', 'scenic')
            ->withSearch(['tools'],['tools' => $scenicIds])
            ->field('t.tools_id id, t.title name')
            ->order('t.tools_id','desc')
            ->select();

        return $scenicList;
    }

    public function mapList(array $params)
    {
        $mapList = LifeToolsScenicMap::where('merchant_id','=',$params['mer_id'])
            ->with(['scenic' => function($query){
                $query->field(['tools_id', 'title']);
            }])
            ->withSearch(['name'],$params)
            ->field(['id', 'name', 'scenic_id', 'status'])
            ->paginate($params['page_size'])
            ->each(function (&$item){
                $item['scenic_name'] = $item['scenic']['title'];
                unset($item['scenic']);
            });

        return $mapList;
    }

    public function saveMap(array $params)
    {
        $params['merchant_id'] = $params['mer_id'];
        $map = new LifeToolsScenicMap();
        if(!empty($params['id'])){
            $map = LifeToolsScenicMap::where('id',$params['id'])
                ->where('merchant_id',$params['mer_id'])
                ->find();
            empty($map) && custom_exception('景区地图不存在！',1003);
        }
        $map->setAttrs($params);
        $map->save();
        return $map->id;
    }

    public function saveMapStatus(array $params)
    {
        $map = LifeToolsScenicMap::where('id',$params['map_id'])
            ->where('merchant_id',$params['mer_id'])
            ->find();
        empty($map) && custom_exception('景区地图不存在！',1003);

        $map->status = $params['status'];
        return $map->save();
    }

    public function mapDel($map_id, array $params)
    {
        $map = LifeToolsScenicMap::where('id',$map_id)
            ->where('merchant_id',$params['mer_id'])
            ->with(['mapLine','mapPlace'])
            ->find();
        empty($map) && custom_exception('景区地图不存在！',1003);

        return $map->together(['mapLine','mapPlace'])->delete();
    }

    //endregion

    //region 地图标注

    public function saveMapPlace(array $params)
    {
        if(isset($params['longitude']) && isset($params['latitude']) && cfg('tianditu_web_key')){
            list($params['longitude'],$params['latitude']) = Coordinate::wgs84ToBd09($params['longitude'],$params['latitude']);
        }

        $place = new LifeToolsScenicMapPlace();
        $map = LifeToolsScenicMap::where([
            'id' => $params['map_id'],
            'merchant_id' => $params['mer_id']
        ])->field('id')->find();

        empty($map) && custom_exception('景区地图不存在！',1003);

        if(!empty($params['id'])){
            $place = LifeToolsScenicMapPlace::where('id',$params['id'])
                ->where('map_id',$params['map_id'])
                ->find();
            empty($place) && custom_exception('景区地图标注点不存在！',1003);
        }
        $place->setAttrs($params);
        $place->save();
        return $place->id;
    }

    public function mapPlaceList(array $params)
    {
        $mapList = LifeToolsScenicMapPlace::alias('mp')
            ->join('life_tools_scenic_map m','mp.map_id = m.id')
            ->where('m.merchant_id',$params['mer_id'])
            ->where('m.id',$params['map_id'])
            ->with(['category' => function($query){
                $query->field(['category_name', 'id']);
            }])
            ->field('mp.*')
            ->select()
            ->each(function (&$item){
                $item['category_name'] = $item['category']['category_name'];
                unset($item['category']);
            });

        return $mapList;
    }

    public function mapPlaceDel($place_id, array $params)
    {
        $mapPlace = LifeToolsScenicMapPlace::where('id',$place_id)
            ->where('map_id',$params['map_id'])
            ->find();
        empty($mapPlace) && custom_exception('景区地图标注点不存在！',1003);

        return $mapPlace->delete();
    }
    //endregion

    //region 地图路线
    public function mapLineList(array $params)
    {
        $mapList = LifeToolsScenicMapLine::alias('ml')
            ->join('life_tools_scenic_map m','ml.map_id = m.id')
            ->where('m.merchant_id',$params['mer_id'])
            ->where('m.id',$params['map_id'])
            ->field('ml.*')
            ->select();

        return $mapList;
    }

    public function saveMapLine(array $params)
    {
        $placeLine = new LifeToolsScenicMapLine();
        $map = LifeToolsScenicMap::where([
            'id' => $params['map_id'],
            'merchant_id' => $params['mer_id']
        ])->field('id')->find();

        empty($map) && custom_exception('景区地图不存在！',1003);

        if(!empty($params['id'])){
            $placeLine = LifeToolsScenicMapLine::where('id',$params['id'])
                ->where('map_id',$params['map_id'])
                ->find();
            empty($placeLine) && custom_exception('景区地图路线不存在！',1003);
        }
        $placeLine->setAttrs($params);
        $placeLine->save();
        return $placeLine->id;
    }

    public function mapLineDel(array $params)
    {
        $mapLine = LifeToolsScenicMapLine::where('id',$params['line_id'])
            ->where('map_id',$params['map_id'])
            ->find();
        empty($mapLine) && custom_exception('景区地图路线不存在！',1003);

        return $mapLine->delete();
    }
    //endregion

    //region 标记点分类

    public function categoryList(array $params)
    {
        $mapList = LifeToolsScenicMapPlaceCategory::where('merchant_id',$params['mer_id'])
            ->withSearch(['name'],$params)
            ->field('id, category_name name, sort, create_time')
            ->order('sort desc');
        if(!empty($params['page'])){
            $mapList = $mapList->paginate($params['page_size']);
        }else{
            $mapList = $mapList->select();
        }

        return $mapList;
    }

    public function saveCategory(array $params)
    {
        $params['merchant_id'] = $params['mer_id'];
        $placeCategory = new LifeToolsScenicMapPlaceCategory();

        if(!empty($params['id'])){
            $placeCategory = LifeToolsScenicMapPlaceCategory::where('id',$params['id'])->find();
            empty($placeCategory) && custom_exception('景区地图路线不存在！',1003);
        }
        $placeCategory->setAttrs($params);
        $placeCategory->save();
        return $placeCategory->id;
    }

    public function categoryDel(array $params)
    {
        $placeCategory = LifeToolsScenicMapPlaceCategory::where('id',$params['category_id'])->find();
        empty($placeCategory) && custom_exception('景区标注点分类不存在！',1003);

        return $placeCategory->delete();
    }
    //endregion
    //endregion
    //region  用户端
    public function scenicMapCategory(array $params)
    {
        $categoryList = LifeToolsScenicMap::alias('m')
            ->join('life_tools_scenic_map_place mp','m.id = mp.map_id')
            ->join('life_tools_scenic_map_place_category c','mp.category_id = c.id')
            ->where('scenic_id',$params['scenic_id'])
            ->group('c.id, c.category_name')
            ->order('c.sort desc')
            ->field('c.id, c.category_name')
            ->select();

        return $categoryList;
    }

    public function scenicMapPlace(array $params)
    {
        $where = ['scenic_id' => $params['scenic_id']];
        if(!empty($params['category_id'])){
            $where['c.id'] = $params['category_id'];
        }
        $mapPlaceList = LifeToolsScenicMapPlace::alias('mp')
            ->join('life_tools_scenic_map m','m.id = mp.map_id')
            ->join('life_tools_scenic_map_place_category c','mp.category_id = c.id')
            ->where($where)
            ->append(['tx_coordinate'])
            ->field('mp.*')
            ->select();

        return $mapPlaceList;
    }

    public function mapPlaceDetail(array $params)
    {
        $where = [
            'm.scenic_id' => $params['scenic_id'],
            'mp.id' => $params['place_id'],
        ];
        $mapPlace = LifeToolsScenicMapPlace::alias('mp')
            ->join('life_tools_scenic_map m','m.id = mp.map_id')
            ->where($where)
            ->append(['tx_coordinate'])
            ->field('mp.*')
            ->find();
        $btnArr = [];

        if(!empty($mapPlace->monitor)){
            array_push($btnArr,[
                'type' => 'monitor',
                "name" => "附近监控"
            ]);
        }

        $line = LifeToolsScenicMapLine::whereRaw("FIND_IN_SET({$mapPlace->id},location_ids)")->find();
        if(!empty($line)){
            array_push($btnArr,[
                'type' => 'scenic_line',
                "name" => "景区路线"
            ]);
        }

        array_push($btnArr,[
            'type' => 'navigation',
            "name" => "导航"
        ]);

        $mapPlace['btn'] = $btnArr;
        $mapPlace['desc'] = replace_file_domain_content_img($mapPlace['desc']);
        if(!empty($params['current_position_lat']) && !empty($params['current_position_lng'])){
            $mapPlace['current_position_distance'] = $this->getDistance($params['current_position_lng'], $params['current_position_lat'], $mapPlace['tx_coordinate']['lng'],  $mapPlace['tx_coordinate']['lat']);
        }
        return $mapPlace;
    }

    public function scenicMapLine(array $params)
    {
        $where = [
            'm.scenic_id' => $params['scenic_id']
        ];
        $mapLine = LifeToolsScenicMapLine::alias('mp')
            ->join('life_tools_scenic_map m','m.id = mp.map_id')
            ->where($where)
            ->append(['place','tx_scenic_location_line'])
            ->field('mp.*')
            ->select();

        return $mapLine;
    }

    public function scenicMapLineDistance(array $params)
    {
        $where = [
            'm.scenic_id' => $params['scenic_id'],
            'mp.id' =>  $params['line_id']
        ];
        $mapLine = LifeToolsScenicMapLine::alias('mp')
            ->join('life_tools_scenic_map m','m.id = mp.map_id')
            ->where($where)
            ->append(['place'])
            ->field('mp.*')
            ->find();

        if(empty($mapLine['place'])){
            return '';
        }
        $distance = 0;
        foreach ($mapLine['scenic_location_line'] as $key => $item){
            if(isset($mapLine['scenic_location_line'][$key+1])){
                $distance += $this->getDistance(
                    $mapLine['scenic_location_line'][$key]['longitude'],
                    $mapLine['scenic_location_line'][$key]['latitude'],
                    $mapLine['scenic_location_line'][$key+1]['longitude'],
                    $mapLine['scenic_location_line'][$key+1]['latitude']
                );
            }
        }

        $distance = bcdiv($distance, 1, 2);
        $minutes  = bcmul($distance, 10);
        $hours    = floor($minutes / 60);
        $minutes  = ($minutes % 60);
        $time     = '';
        if ($hours > 0) {
            $time .= $hours . '小时';
        }
        if ($minutes > 0) {
            $time .= $minutes . '分钟';
        }
        $distance   .= '公里';
        $scenic_num = count($mapLine['place']);
        return compact('scenic_num', 'time', 'distance');
    }

    //endregion

    //region 其他

    /**
     * 计算两点之间的距离
     * @param float $lng1 经度1
     * @param float 纬度1
     * @param float 经度2
     * @param float 纬度2
     * @param int $unit m，km
     * @param int $decimal 位数
     * @return float
     */
    public function getDistance($lng1, $lat1, $lng2, $lat2, $unit = 2, $decimal = 2)
    {
        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI           = 3.1415926535898;
        $radLat1 = $lat1 * $PI / 180.0;
        $radLat2 = $lat2 * $PI / 180.0;
        $radLng1 = $lng1 * $PI / 180.0;
        $radLng2 = $lng2 * $PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;
        if ($unit === 2) {
            $distance /= 1000;
        }
        return round($distance, $decimal);
    }
    //endregion









}
