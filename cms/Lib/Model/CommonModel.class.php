<?php
class CommonModel extends Model{
    public function LbsTranform($url,$module_name = MODULE_NAME , $action_name = ACTION_NAME ){
        if(stripos($url , 'LBS://')!==FALSE){
            $url = parse_url($url);
            $long_lat = explode(',',$url['host']);
            $long = $long_lat[0];
            $lat = $long_lat[1];
            $url = U($module_name.'/'.$action_name,array('long'=>$long,'lat'=>$lat));
        }
        return $url;
    }

    /**
     * 商品列表
     * @param string $type
     * @param array $productIds
     * @return mixed
     */
    public function businessProductList(string $type, $lng = '', $lat = '', int $distance = 0) : array
    {
        $pageSize = 3;//默认返回3条数据
        $typeFunc = [
            'shop'    => 'Shop_goods',
            'mall'    => 'Mall_goods',
            'group'   => 'Village_group_goods',
            'appoint' => 'Appoint',
        ];
        
        return D($typeFunc[$type])->goodsList($distance, $pageSize, $lng, $lat);
    }    
}
?>