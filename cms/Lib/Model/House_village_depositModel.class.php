<?php

class House_village_depositModel extends Model
{
	/**
	 * [get_deposit_list 获取押金列表]
	 * @param  [type] $field [description]
	 * @param  [type] $join1 [description]
	 * @param  [type] $Join2 [description]
	 * @param  [type] $where [description]
	 * @param  [type] $order [description]
	 * @return [type]        [description]
	 */
	public function get_deposit_list($field='',$join,$where,$order){
		import('@.ORG.merchant_page');
        $count = D('House_village_deposit')->alias('a')->join($join)->where($where)->count();
        $p = new Page($count,20,'page');
		$info_list['info_list'] = D('House_village_deposit')
                                ->alias('a')
                                ->field($field)
                                ->join($join)
                                ->where($where)
                                ->order($order)
                                ->limit($p->firstRow.','.$p->listRows)
                                ->select();
        $info_list['pagebar'] = $p->show();
        $info_list['total'] = D('House_village_deposit')
            ->alias('a')
            ->field($field)
            ->join($join)
            ->where($where)
            ->sum('actual_money-refund_money');
        if($info_list){
        	return $info_list;
        }else{
        	return false;
        }
	}


	/**
	 * [get_deposit_one 获取单个押金信息]
	 * @return [type] [description]
	 */
	public function get_deposit_one($field='',$join,$where){
		$result = D('House_village_deposit')->alias('a')->field($field)->join($join)->where($where)->find();
		if($result !==false){
		    // 查询下 地址返回信息
            if ($result && $result['pigcms_id']) {
                $where_bind = [
                    'pigcms_id' => $result['pigcms_id']
                ];
                $bind_info = M('House_village_user_vacancy')->where($where_bind)->find();
                if (!empty($bind_info)) {
                    $result['address'] = D('House_village')->get_single_floor_room($bind_info['single_id'],$bind_info['floor_id'],$bind_info['layer_id'],$bind_info['pigcms_id'],$bind_info['village_id']);
                }
            }
			return $result;
		}else{
			return false;
		}
	}

	/**
	 * [deposit_add 添加单个]
	 * @return [type] [description]
	 */
	public function house_deposit_add($data){
		$result = D('House_village_deposit')->add($data);
		if($result){
			return $result;
		}else{
			return false;
		}
	}

	/**
	 * [deposit_del 删除]
	 * @return [type] [description]
	 */
	public function deposit_delete($where){
		$result = D('House_village_deposit')->where($where)->delete();
		if($result){
			return $result;
		}else{
			return false;
		}
	}
}

?>