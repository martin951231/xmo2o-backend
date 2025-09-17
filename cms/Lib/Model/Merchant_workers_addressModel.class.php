<?php
class Merchant_workers_addressModel extends Model{
	/*得到所有地址*/
	public function get_adress_list($uid , $flag = true){
		$condition_user_adress['uid'] = $uid;
		$user_adress_list = $this->field(true)->where($condition_user_adress)->order('`default` DESC,`adress_id` ASC')->select();
		foreach($user_adress_list as $key=>$value){
			if ((!($value['latitude'] != 0 &&  $value['longitude'] != 0))&& $flag) {
				unset($user_adress_list[$key]);
				continue;
			}
			$province = D('Area')->get_area_by_areaId($value['province'],false);
			$user_adress_list[$key]['province_txt'] = $province['area_name'];
			
			$city = D('Area')->get_area_by_areaId($value['city'],false);
			$user_adress_list[$key]['city_txt'] = $city['area_name'];
			
			$area = D('Area')->get_area_by_areaId($value['area'],false);
			$user_adress_list[$key]['area_txt'] = $area['area_name'];
			$user_adress_list[$key]['zip_code'] = $area['zip_code'];
			$user_adress_list[$key]['country_code'] = $area['country_code'];
			
			if(!C('config.open_multilingual')){
				$user_adress_list[$key]['show_address'] = $user_adress_list[$key]['province_txt'] . $user_adress_list[$key]['city_txt'] . $user_adress_list[$key]['area_txt'] . $user_adress_list[$key]['adress'] . $user_adress_list[$key]['detail'];
			}else if($area['country_code'] == 'nz'){
				$user_adress_list[$key]['show_address'] = trim(($user_adress_list[$key]['detail'] ? $user_adress_list[$key]['detail']  . ', ' : '') . $user_adress_list[$key]['adress']  . ', ' . $user_adress_list[$key]['area_txt'] . ' ' . $user_adress_list[$key]['city_txt'] . ' ' . $user_adress_list[$key]['zip_code']);
			}else if(C('config.country_code') == 'au'){
				$user_adress_list[$key]['show_address'] = trim(($user_adress_list[$key]['detail'] ? $user_adress_list[$key]['detail']  . ', ' : '') . $user_adress_list[$key]['adress']  . ', ' . $user_adress_list[$key]['area_txt'] . ' ' . $user_adress_list[$key]['province_txt'] . ' ' . $user_adress_list[$key]['zip_code']);
			}else{
				$user_adress_list[$key]['show_address'] = trim(($user_adress_list[$key]['detail'] ? $user_adress_list[$key]['detail']  . ', ' : '') . $user_adress_list[$key]['adress']  . ', ' . $user_adress_list[$key]['area_txt'] . ' ' . $user_adress_list[$key]['city_txt']);
			}
		}
		return $user_adress_list;
	}

    /*保存地址*/
    public function post_form_save($uid){
        // 统一去除 经纬度信息的前后空格
        if ($_POST['longitude']) {
            $_POST['longitude'] = trim($_POST['longitude']);
        }
        if ($_POST['latitude']) {
            $_POST['latitude'] = trim($_POST['latitude']);
        }
        $_POST['phone'] = str_replace(' ', '', trim($_POST['phone']));
        $_POST['phone'] = phone_format($_POST['phone_country_type'], $_POST['phone']);
        $_POST['last_time'] = time();
        if($_POST['adress_id']){
            $condition_user_adress['adress_id'] = $_POST['adress_id'];
            $condition_user_adress['uid'] = $uid;
            unset($_POST['adress_id']);
            if(!empty($_POST['default'])){
                $condition_default_user_adress['uid'] = $uid;
                $this->where($condition_default_user_adress)->setField('default','0');
            }else{
                $_POST['default'] = 0;
            }

            return $this->where($condition_user_adress)->data($_POST)->save();
        }else{
            $_POST['uid'] = $uid;
            if(!empty($_POST['default'])){
                $condition_default_user_adress['uid'] = $uid;
                $this->where($condition_default_user_adress)->setField('default','0');
            }else{
                $_POST['default'] = 0;
            }
            return $this->data($_POST)->add();
        }
    }

    public function get_adress($uid,$adress_id){
        $condition_user_adress['uid'] = $uid;
        $condition_user_adress['adress_id'] = $adress_id;
        return $this->field(true)->where($condition_user_adress)->find();
    }

    public function delete_adress($uid,$adress_id){
        $condition_user_adress['uid'] = $uid;
        $condition_user_adress['adress_id'] = $adress_id;
        return $this->where($condition_user_adress)->delete();
    }
}
?>