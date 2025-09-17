<?php
/**
 * 自提地址
 * Author: hengtingmei
 * Date Time: 2021/5/21 13:31
 */

namespace app\merchant\model\service\pick;

use app\common\model\service\AreaService;
use app\common\model\service\user\UserLongLatService;
use app\merchant\model\db\PickAddress;
use app\merchant\model\service\MerchantStoreService;

class PickAddressService {
    public $pickAddressModel = null;
    public function __construct()
    {
        $this->pickAddressModel = new PickAddress();
    }

    
    /**
     * 获取自提地址列表
     * @param int $merId 
     * @param bool $isSystem 
     * @param int $storeId 
     * @param bool $isPickMsg 
     * @param array $pickInfo 
     * @param int $isOpen 
     * @return array
     */
    public function getPickAddressByMerId($merId, $isSystem=false, $storeId = 0, $isPickMsg = false, $pickInfo = array(),$isOpen=0){
        $lngLat = [
            'long' => request()->lng,
            'lat'  => request()->lat
        ];
	    if (empty($lngLat['long']) || empty($lngLat['lat'])) {
            $user = request()->user;
            $openid = $user['openid'] ?? '';
	        $lngLat = (new UserLongLatService)->getLocation($openid, 0);
	    }
		
		if(!$isSystem){
			$storeList = (new MerchantStoreService())->getStoreListByMerId($merId);
			foreach($storeList as $key=>$vs) {
				$area[]  =$vs['province_id'];
				$area[]  =$vs['city_id'];
				$area[]  =$vs['area_id'];
				if(isset($lngLat['lat']) && $lngLat['lat']){
					$distance  = get_range(getDistance($lngLat['lat'],$lngLat['long'],$vs['lat'],$vs['long']),false);
				}else{
					$distance = '';
				}
				$pick_addr[] = array(
                    'name'=>$vs['adress'],
                    'area_info'=>
                        array(
                            'province'=>$vs['province_id'],
                            'city'=>$vs['city_id'],
                            'area'=>$vs['area_id']
                        ),
                        'pick_addr'=>$vs['adress'],
                        'pick_addr_id'=>'s'.$vs['store_id'],
                        'phone'=>$vs['phone'],
                        'long'=>$vs['long'],
                        'lat'=>$vs['lat'],
                        'addr_type'=>1,
                        'distance'=>$distance
                    );
			}
		} elseif ($storeId) {
			$store = (new MerchantStoreService())->getStoreByStoreId($storeId);
		    $area[] = $store['province_id'];
		    $area[] = $store['city_id'];
		    $area[] = $store['area_id'];
		    if(isset($lngLat['lat']) && $lngLat['lat']){
				$distance  = getDistance($lngLat['lat'], $lngLat['long'], $store['lat'], $store['long']);
		    }else{
				$distance  = 0;
		    }
		    $pick_addr[] = [
                'name'=> $store['adress'],
                'area_info' => [
                    'province' => $store['province_id'], 
                    'city' => $store['city_id'], 
                    'area' => $store['area_id']
                ],
                'pick_addr'=>$store['adress'],
                'pick_addr_id' => 's' . $store['store_id'], 
                'phone' => $store['phone'], 
                'long' => $store['long'], 
                'lat' => $store['lat'], 
                'addr_type' => 1,
                'distance' => $distance
            ];
		}
		$pick_condition['mer_id'] = $merId;
		if($isOpen){
			$pick_condition['is_open'] = 1;
		}
		$pick_addr_list = $this->getSome($pick_condition);
        foreach($pick_addr_list as $k=>$v){
            $area[]  =$v['province_id'];
            $area[]  =$v['city_id'];
            $area[]  =$v['area_id'];
			if(isset($lngLat['lat']) && $lngLat['lat']){
                $distance  = get_range(getDistance($lngLat['lat'],$lngLat['long'],$v['lat'],$v['long']), false);
			}else{
                $distance = '';
			}
			$pick_addr[] = array(
                'name'=>$v['pick_addr'],
                'province'=>$v['province_id'],
                'city'=>$v['city_id'],
                'area'=>$v['area_id'],
                'area_info'=>
                    array(
                        'province'=>$v['province_id'],
                        'city'=>$v['city_id'],
                        'area'=>$v['area_id']
                    ),
                'pick_addr'=>$v['pick_addr'],
                'pick_addr_id'=>'p'.$v['id'],
                'phone'=>$v['phone'],
                'long'=>$v['long'],
                'lat'=>$v['lat'],
                'is_open'=>$v['is_open'],
                'addr_type'=>2,
                'distance'=>$distance
            );
		}

        $where  = [
            ['area_id', 'in', implode(',',$area)]
        ];
		$areaList = (new AreaService())->getAreaListByCondition($where);
        $areaListFormat = [];
        foreach($areaList as $_area){
            $areaListFormat[$_area['area_id']] = $_area;
        }
        $pick_msg = array();
		foreach($pick_addr as &$v){
			$v['area_info']['province'] = $areaListFormat[$v['area_info']['province']]['area_name'] ?? '';
			$v['area_info']['city'] = $areaListFormat[$v['area_info']['city']]['area_name'] ?? '';
            $v['area_info']['zip_code'] = $areaListFormat[$v['area_info']['area']]['zip_code'] ?? '';
			$v['area_info']['area'] = $areaListFormat[$v['area_info']['area']]['area_name'] ?? '';

            if ($isPickMsg) {
                if ($pickInfo && in_array($v['pick_addr_id'], $pickInfo)) {
                    $pick_msg[$v['pick_addr_id']] = $v;
                } elseif (empty($pickInfo)) {
                    $pick_msg[$v['pick_addr_id']] = $v;
                }
            }
		}
        if ($isPickMsg) {
            return $pick_msg;
        }
		$pick_addr = sortArrayAsc($pick_addr,'distance');
		return $pick_addr;
	}

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->pickAddressModel->getOne($where);
        if(!$result) {
            return [];
        }
        return $result;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->pickAddressModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->pickAddressModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     *添加一条数据
     * @param $where array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }
        $result = $this->pickAddressModel->add($data);
        if(empty($result)) return false;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->pickAddressModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * 获取商家下面自提点列表
     * @param $param
     */
    public function getPickAddress($param){
        $data = $this->pickAddressModel->field('id,pick_addr')->where(['mer_id'=>$param['mer_id'],'is_open'=>1])->select();
        return $data;
    }
}