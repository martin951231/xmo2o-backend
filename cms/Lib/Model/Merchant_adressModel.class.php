<?php
class Merchant_adressModel extends Model{
    /*得到所有地址*/
    public function get_adress_list($mer_id , $flag = true){
        $condition_adress['mer_id'] = $mer_id;
        $adress_list = $this->field(true)->where($condition_adress)->order('`default` DESC,`adress_id` ASC')->select();
        foreach($adress_list as $key=>$value){
            if ((!($value['latitude'] != 0 &&  $value['longitude'] != 0))&& $flag) {
                unset($adress_list[$key]);
                continue;
            }
            $province = D('Area')->get_area_by_areaId($value['province'],false);
            $adress_list[$key]['province_txt'] = $province['area_name'];

            $city = D('Area')->get_area_by_areaId($value['city'],false);
            $adress_list[$key]['city_txt'] = $city['area_name'];

            $area = D('Area')->get_area_by_areaId($value['area'],false);
            $adress_list[$key]['area_txt'] = $area['area_name'];
        }
        return $adress_list;
    }
    /*设置默认地址*/
    public function set_default($mer_id,$adress_id){
        $condition_default_adress['mer_id'] = $mer_id;
        $this->where($condition_default_adress)->setField('default','0');

        $condition_adress['adress_id'] = $adress_id;
        $condition_adress['mer_id'] = $mer_id;

        return $this->where($condition_adress)->setField('default','1');
    }
    /*保存地址*/
    public function post_form_save($mer_id){
        // 统一去除 经纬度信息的前后空格
        if ($_POST['longitude']) {
            $_POST['longitude'] = trim($_POST['longitude']);
        }
        if ($_POST['latitude']) {
            $_POST['latitude'] = trim($_POST['latitude']);
        }
        if (empty($_POST['province']) || empty($_POST['city']) || empty($_POST['area'])) {
            $area_info = D('Area')->cityMatching($_POST['latitude'], $_POST['longitude']);
            if ($area_info['area_info']['province_id']) {
                $_POST['province'] = $area_info['area_info']['province_id'];
            }
            if ($area_info['area_info']['city_id']) {
                $_POST['city'] = $area_info['area_info']['city_id'];
            }
            if ($area_info['area_info']['area_id']) {
                $_POST['area'] = $area_info['area_info']['area_id'];
            }
        }
        if($_POST['adress_id']){
            $condition_user_adress['adress_id'] = $_POST['adress_id'];
            $condition_user_adress['mer_id'] = $mer_id;
            unset($_POST['adress_id']);
            if(!empty($_POST['default'])){
                $condition_default_user_adress['mer_id'] = $mer_id;
                $this->where($condition_default_user_adress)->setField('default','0');
            }else{
                $_POST['default'] = 0;
            }

            return $this->where($condition_user_adress)->data($_POST)->save();
        }else{
            $_POST['mer_id'] = $mer_id;
            if(!empty($_POST['default'])){
                $condition_default_user_adress['mer_id'] = $mer_id;
                $this->where($condition_default_user_adress)->setField('default','0');
            }else{
                $_POST['default'] = 0;
            }
            return $this->data($_POST)->add();
        }
    }

    public function delete_adress($mer_id,$adress_id){
        $condition_user_adress['mer_id'] = $mer_id;
        $condition_user_adress['adress_id'] = $adress_id;
        return $this->where($condition_user_adress)->delete();
    }

    public function get_adress($mer_id,$adress_id){
        $condition_user_adress['mer_id'] = $mer_id;
        $condition_user_adress['adress_id'] = $adress_id;
        return $this->field(true)->where($condition_user_adress)->find();
    }
    public function get_one_adress($mer_id,$adress_id=0){
        $condition_user_adress['mer_id'] = $mer_id;
        if($adress_id){
            $condition_user_adress['adress_id'] = $adress_id;
        }
        $user_adress = $this->field(true)->where($condition_user_adress)->order('`default` DESC,`adress_id` ASC')->find();
        if($user_adress){
            if (!($user_adress['latitude'] > 0 &&  $user_adress['longitude'] > 0)) return false;
            $province = D('Area')->get_area_by_areaId($user_adress['province'],false);
            $user_adress['province_txt'] = $province['area_name'];

            $city = D('Area')->get_area_by_areaId($user_adress['city'],false);
            $user_adress['city_txt'] = $city['area_name'];

            $area = D('Area')->get_area_by_areaId($user_adress['area'],false);
            $user_adress['area_txt'] = $area['area_name'];
        }

        return $user_adress;
    }
    //得到距离在1千米之内的一个地址
    public function getNearAdress($mer_id, $lat, $lng){
        $where = "`mer_id`='$mer_id' AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`latitude`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`latitude`*PI()/180)*POW(SIN(({$lng}*PI()/180-`longitude`*PI()/180)/2),2)))*1000) <= 100000";
        $oneAdress = $this->field("*,ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`latitude`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`latitude`*PI()/180)*POW(SIN(({$lng}*PI()/180-`longitude`*PI()/180)/2),2)))*1000) AS juli")->where($where)->order('`juli` ASC')->find();

        return $oneAdress;
    }
}
?>