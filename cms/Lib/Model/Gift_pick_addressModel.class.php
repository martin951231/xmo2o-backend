<?php
class Gift_pick_addressModel extends Model{
	public function get_pick_address($where=array()){
	    $lng_lat['long'] = cookie('userLocationLong');
	    $lng_lat['lat'] = cookie('userLocationLat');
	    if (empty($lng_lat['long']) || empty($lng_lat['lat'])) {
	        $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
	    }
	    if ($where) {
			$pick_addr_list = $this->where($where)->select();
	    }else{
	    	$pick_addr_list = $this->select();
	    }
        foreach($pick_addr_list as $k=>$v){
            $area[]  =$v['province_id'];
            $area[]  =$v['city_id'];
            $area[]  =$v['area_id'];
			$distance  = -1;
			if(!empty($lng_lat['lat']) && !empty($lng_lat['long']) && !empty($v['lat']) && !empty($v['long'])){
				$distance  = getDistance($lng_lat['lat'],$lng_lat['long'],$v['lat'],$v['long']);
			}
			$pick_addr[]=array('name'=>$v['pick_addr'],'area_info'=>array('province'=>$v['province_id'],'city'=>$v['city_id'],'area'=>$v['area_id']),'pick_id'=>$v['pick_id'],'is_open'=>$v['is_open'],'phone'=>$v['phone'],'long'=>$v['long'],'lat'=>$v['lat'],'addr_type'=>2,'distance'=>$distance);
		}
		$where['area_id']=array('in',implode(',',$area));
		$area_name = M('Area')->where($where)->getField('area_id,area_name');
		foreach($pick_addr as &$v){
			$v['area_info']['province'] = $area_name[$v['area_info']['province']];
			$v['area_info']['city'] = $area_name[$v['area_info']['city']];
			$v['area_info']['area'] = $area_name[$v['area_info']['area']];
		}
		$pick_addr = sortArrayAsc($pick_addr,'distance');
		return $pick_addr;
	}
	public function get_one_pick($pick_id=0,$is_open=1){ 
		$lng_lat['long'] = cookie('userLocationLong');
	    $lng_lat['lat'] = cookie('userLocationLat');
	    if (empty($lng_lat['long']) || empty($lng_lat['lat'])) {
	        $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
	    }
		if($pick_id){
			$conditions['pick_id'] = $pick_id;
		}
		if($is_open){
			$conditions['is_open'] = 1; 
		}
		$pick_addr_list = $this->field(true)->where($conditions)->select();
		foreach($pick_addr_list as $k=>$v){
            $area[]  =$v['province_id'];
            $area[]  =$v['city_id'];
            $area[]  =$v['area_id'];
			$distance  = -1;
			if(!empty($lng_lat['lat']) && !empty($lng_lat['long']) && !empty($v['lat']) && !empty($v['long'])){
				$distance  = getDistance($lng_lat['lat'],$lng_lat['long'],$v['lat'],$v['long']);
			}
			$pick_addr[]=array('name'=>$v['pick_addr'],'area_info'=>array('province'=>$v['province_id'],'city'=>$v['city_id'],'area'=>$v['area_id']),'pick_id'=>$v['pick_id'],'is_open'=>$v['is_open'],'phone'=>$v['phone'],'long'=>$v['long'],'lat'=>$v['lat'],'addr_type'=>2,'distance'=>$distance);
		}
		$where['area_id']=array('in',implode(',',$area));
		$area_name = M('Area')->where($where)->getField('area_id,area_name');
		foreach($pick_addr as &$v){
			$v['area_info']['province'] = $area_name[$v['area_info']['province']];
			$v['area_info']['city'] = $area_name[$v['area_info']['city']];
			$v['area_info']['area'] = $area_name[$v['area_info']['area']];
		}
		$pick_addr = sortArrayAsc($pick_addr,'distance');
		return $pick_addr[0];
	}

}