<?php


namespace app\common\controller\api;


use app\common\model\service\BaiduMapService;
use map\Coordinate;
use map\Tianditu;

class BaiduMapController extends ApiBaseController
{
    public function reverseGeocoding(){
        $params['lat'] = $this->request->param('lat','','trim,string');
        $params['lng'] = $this->request->param('lng','','trim,string');
        try {
            if (request()->param('coordinate_type') == 'wgs84' && cfg('tianditu_key')) {
                $data = Tianditu::reverseGeocoder($params['lng'], $params['lat'], cfg('tianditu_key'));
                $data['result']['pois'] = [];
                $addressComponent = $data['result']['addressComponent'] ?? [];
                $location = $data['result']['location'] ?? [];
                if ($addressComponent && $location) {
                    $data['result']['pois'] = [
                        [
                            "long" => $location['lon'],
                            "lat" => $location['lat'],
                            "point" => [
                                "x" => $location['lon'],
                                "y" => $location['lat'],
                            ],
                            "city" => $addressComponent['city'],
                            "addr" => $addressComponent['address'],
                            "name" => $data['result']['formatted_address'],
                        ]
                    ];
                }
            } else {
                $data = (new BaiduMapService())->reverseGeocoding($params);
            }
            return api_output(0,$data,'');
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
        
    }
}