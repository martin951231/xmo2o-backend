<?php
class AdminModel extends Model
{
	public function v20Login($id)
	{
		$where['id'] = $id;
		$now_admin = $this->where($where)->find();
		if(empty($now_admin)){
			return false;
		}
		
		if($now_admin['status'] != 1){
			return false;
		}

		$now_admin['show_account'] = '超级管理员';
		if ($now_admin['level'] == 1) {
			if ($now_admin['area_id']) {
				$area = D('Area')->field(true)->where(array('area_id' => $now_admin['area_id']))->find();
				$now_admin['show_account'] = $area['area_name'] . '管理员';
			}
		}else if($now_admin['level'] == 2) {
			$now_admin['show_account'] = '超级管理员';
		} else {
			$now_admin['show_account'] = '普通管理员';
		}
		
		
		if(!empty($now_admin['last_ip'])){
			import('ORG.Net.IpLocation');
			$IpLocation = new IpLocation();
			$last_location = $IpLocation->getlocation($now_admin['last_ip']);
			$now_admin['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
			$now_admin['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
		}
		session('system',$now_admin);
		return true;
	}

	// 获得网站所在git分支
	public function getGitInfo(){
		$retuenArr = [];
 		if(file_exists('.git/HEAD')){
            $git_branch = str_replace('ref: refs/heads/', '', file_get_contents('.git/HEAD'));

            $git_pull = false;
            if(function_exists('shell_exec')){
                $git_pull = true;
            }
            $retuenArr['git_branch'] = $git_branch;
            $retuenArr['git_pull'] = $git_pull;
        }

        $retuenArr['磁盘剩余空间'] = round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M';
        return $retuenArr;
	}


	// 通过管理员父id查找所有子管理员
	public function getAdminByFid($fid){
		if(empty($fid)){
 			return [$fid];
 		}

		$where = [
			'fid' => $fid
		];
 		$adminList = $this->where($where)->select();
 		if(empty($adminList)){
 			return [$fid];
 		}
 		$returnArr = [];
 		$returnArr = $adminIdArr = array_column($adminList,'id');
 		while($adminList){
 			$where = [
				'fid' => ['in', $adminIdArr]
			];
	 		$adminList = $this->where($where)->select();
 			$adminIdArr = array_column($adminList,'id');
 			if($adminIdArr){
 				$returnArr = array_merge($returnArr, $adminIdArr);
 			}
 		}
 		$returnArr[] = $fid;
        return $returnArr;
	}
    /**
     * 限时秒杀商品列表
     * @param $type
     * @param array $params
     * @return mixed
     */
    public function getSeckillGoodsList(string $type, array $params = []): array
    {
        switch ($type) {
            case 'shop':
                $data = D('Time_limited_discount_goods')->seckillGoods($params);
                break;
            case 'mall':
                $data = D('Mall_goods')->getLimitedList($params);
                break;
            case 'village_group':
                $data = D('Village_group_goods')->getActivityGoods($params);
                break;
            default:
                $data = [];
        }
        
        return $data;
    }
    
    public function getSeckillGoods(array $goodsList, bool $delTimeLimit = false): array
    {
        $seckillGoods = [];
        $type = [
            'village_group' => C('config.village_group_alias') ?: '社区团购',
            'shop' => C('config.shop_alias_name') ?: '外卖',
            'mall' => C('config.mall_alias_name') ?: '商城',
        ];

        foreach ($goodsList as $goods) {
            $data = $this->getSeckillGoodsList($goods['goods_type'], [
                'goods_id' => $goods['goods_id']
            ]);
            if(!$delTimeLimit){
                if (empty($data)) {
                    $seckillGoods[]=$goods;
                    continue;
                }
                $item=array_merge($goods,$data['0']);
            }else{
                if (empty($data)) {
                    continue;
                }
                $item=$data['0'];
                if(!empty($item['over_time'])){
                    continue;
                }
            }
            $item['goods_type'] = $goods['goods_type'];
            $item['goods_type_text'] = $type[$goods['goods_type']];

            $seckillGoods[] = $item;
            
        }

        return $seckillGoods;
    }

	//先检查是否属于绑定卡类型管理员，是则跟卡类型走。否，根据管理员绑定的城市区域所属单位走
	public function getCardTypeByAdmin($admin,$cardName = '')
	{
		$cardTypes = M('internal_membership_names')->where(['belong_admin_id' => $admin['id']])->getField('card_name', true);
		if (empty($cardTypes)) {
			$unitIds = M('internal_unit')->where(['area_id' => $admin['area_id']])->getField('id', true);
			if ($unitIds) {
				$cardTypes = M('internal_membership_card')->where(['unit_id' => ['IN', $unitIds]])->getField('card_name', true);
			}
		}
		if ($cardName) {
			$cardTypes = array_filter($cardTypes, function ($v) use ($cardName) {
				return strpos($v, $cardName) !== false;
			});
		}
		return array_values(array_unique($cardTypes));
	}

	//先检查是否绑定了卡类型，绑定了卡类型获取卡类型关联的单位ID
	public function getUnitIdsByAdmin($admin)
	{
		$unitIds = false;
		$cardTypes = M('internal_membership_names')->where(['belong_admin_id' => $admin['id']])->getField('card_name', true);
		if ($cardTypes) {
			//绑定了卡类型，则只读取卡类型关联的归属单位
			$unitIds = M('internal_membership_card')->where(['card_name' => ['IN', $cardTypes],'unit_id'=>['gt',0]])->field('distinct(unit_id)')->select();
			$unitIds = array_column($unitIds, 'unit_id');
		}
		return $unitIds;
	}
    
}
?>