<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 14:31:39
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-12 14:38:49
 */
namespace app\mall\model\db;
use think\Model;


class MallStore extends Model {

	public function getOne($id){
		$where = [
			['id','=',$id],
			['is_del','=',0],
		];
		$return=$this->where($where)->find();
		if(!empty($return)){
            $return=$return->toArray();
        }

		return $return;
	}
}