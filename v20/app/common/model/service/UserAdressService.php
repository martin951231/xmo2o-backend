<?php
/**
 * 用户收获地址
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/7/6 13:46
 */

namespace app\common\model\service;

use app\common\model\db\UserAdress;
use app\common\model\service\AreaService;

class UserAdressService
{
    public $userAdressObj = null;
    public function __construct()
    {
        $this->userAdressObj = new UserAdress();
    }

    /**
     * 通过 uid 获取用户搜获地址
     * User: chenxiang
     * Date: 2020/7/6 14:19
     * @param $uid
     * @param int $default
     * @return \json
     */
    public function getAdressByUid($uid, $default = 1) {
        if($default == 1) { //获取默认地址
            $where['default'] = 1;
        }
        $where['uid'] = $uid;
        $field = '*';
        $userAddress = $this->userAdressObj->getAdress($field, $where);
        if(empty($userAddress)) return [];
        foreach ($userAddress as $key => $add) {
            $areas = (new AreaService)->getNameByIds([$add['province'], $add['city'], $add['area']]);
            $userAddress[$key]['title'] = ($areas[$add['province']] ?? '').' '.($areas[$add['city']] ?? '').' '.($areas[$add['area']] ?? '').' '.$add['adress'].' '.$add['detail'];
        }
        

        return $userAddress;
    }

    /**
     * 通过 adress_id 获取用户搜获地址
     * User: chenxiang
     * Date: 2020/7/6 14:25
     * @param $addressId
     * @return \json
     */
    public function getAdressByAdressid($addressId) {
        $where['adress_id'] = $addressId;
        $field = true;
        $userAddress = $this->userAdressObj->getAdress($field, $where);
        if(empty($userAddress)) return [];
        $userAddress = $userAddress[0];
        $areas = (new AreaService)->getNameByIds([$userAddress['province'], $userAddress['city'], $userAddress['area']]);
        $userAddress['title'] = ($areas[$userAddress['province']] ?? '').' '.($areas[$userAddress['city']] ?? '').' '.($areas[$userAddress['area']] ?? '').' '.$userAddress['adress'].' '.$userAddress['detail'];
        return $userAddress;
    }

    /**
     * 获取用户一条收货地址
     * User: 衡婷妹
     * Date: 2021/05/21 11:20
     * @param int $uid
     * @param int $addressId
     * @return array
     */
	public function getOneAdress($uid, $addressId = 0){
		$condition['uid'] = $uid;
		if($addressId){
			$condition['adress_id'] = $addressId;
		}
		$userAddress = $this->getOne($condition, true, 'default DESC,adress_id ASC');
		if($userAddress){
			if (!($userAddress['latitude'] != 0 &&  $userAddress['longitude'] != 0)) return false;

            // 拼接详细地址
            $areas = (new AreaService)->getNameByIds([$userAddress['province'], $userAddress['city'], $userAddress['area']]);
            $userAddress['show_provinces'] = ($areas[$userAddress['province']] ?? '').($areas[$userAddress['city']] ?? '').($areas[$userAddress['area']] ?? '');

            $userAddress['show_address'] = $userAddress['adress'].' '.$userAddress['detail'];
		}	
		return $userAddress;
	}

     /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where, $field=true, $order=[]){
        $result = $this->userAdressObj->getOne($where, $field, $order);
        if(!$result) {
            return [];
        }
        return $result->toArray();
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
        $result = $this->userAdressObj->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->userAdressObj->getCount($where);
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
        $result = $this->userAdressObj->add($data);
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

        $result = $this->userAdressObj->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}