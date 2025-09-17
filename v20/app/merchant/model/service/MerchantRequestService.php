<?php
/**
 * 粉丝行为分析
 * Author: hengtingmei
 * Date Time: 2021/5/26 10:37
 */

namespace app\merchant\model\service;
use app\merchant\model\db\MerchantRequest;
class MerchantRequestService {
    public $merchantRequestModel = null;
    public function __construct()
    {
        $this->merchantRequestModel = new MerchantRequest();
    }

	/*
	 * 为了追求处理速度，不做 商家是否有效、参数是否有效的判断,也不处理param值的判断处理。
	 *
	 * $param参数里填写 字段名、增量值  例如   array('img_num'=>1);
	 * 
	 */
	public function addRequest($merId,$param=array()){
		if(empty($merId)) return false;
		if(empty($param)) return false;

		//查找此商家今天的值，没有则添加
		$where['mer_id'] = $merId;
		$where['year'] = date('Y',time());
		$where['month'] = date('m',time());
		$where['day'] = date('d',time());
		
		$merchantRequest = $this->getOne($where);
		if(empty($merchantRequest)){
			$merchantRequest['id'] = $this->add($where);
		}
		
		if(empty($merchantRequest['id'])) return false;
		
		foreach($param as $key=>$value){
            $count = $merchantRequest[$key] ?? 0;
			$dataMerchantRequest[$key] = $count+$value;
		}
		$dataMerchantRequest['time'] = mktime(0,0,0,$where['month'],$where['day'],$where['year']);
		$condition['id'] = $merchantRequest['id'];
        $this->updateThis($condition, $dataMerchantRequest);
	}

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->merchantRequestModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回多条数据
     * @param $where array 条件
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        $detail = $this->merchantRequestModel->getSome($where,$field,$order,$page,$limit);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 添加数据
     * @param $data array
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->merchantRequestModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->merchantRequestModel->id;
    }

    /**
     * 更新数据
     * @param $where array 条件
     * @param $data array 更新数据
     * @return array
     */
    public function updateThis($where, $data) {
        if( empty($data) || empty($where)){
            return false;
        }

        $result = $this->merchantRequestModel->updateThis($where, $data);
        if(!$result) {
            return false;
        }

        return $result;
    }

}