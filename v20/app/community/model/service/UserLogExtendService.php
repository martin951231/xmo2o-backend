<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	namespace app\community\model\service;

	use app\community\model\db\UserLogExtend;


	class UserLogExtendService
	{
 
		
		public function addLog($data)
		{
		  return (new UserLogExtend())->insert($data);
		}

		/**
		 * 获取指定小区的登录日志
		 * @param     $villageID
		 * @param int $offset
		 * @param int $length
		 *
		 * @return \think\Collection
		 * @throws \think\db\exception\DataNotFoundException
		 * @throws \think\db\exception\DbException
		 * @throws \think\db\exception\ModelNotFoundException
		 */
		public function getVillageUserLoginLog($villageID,$offset=0,$length=20)
		{
			$loginRole = (new AdminLoginService())->getVillageLoginRoleNumberArr();
			$where[] = ['login_type','in',$loginRole];
			$where[] = ['login_id' ,'=', $villageID];
			$data =  (new UserLogExtend())->where($where)->limit($offset,$length)->order('id', 'DESC')->select();
		 
			if ($data){
				foreach ($data as &$item){
					$item['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
				}
			}
			
			return $data;
		}

		/**
		 * 获取登录日志详情
		 * @param $id
		 *
		 * @return array|\think\Model|null
		 * @throws \think\db\exception\DataNotFoundException
		 * @throws \think\db\exception\DbException
		 * @throws \think\db\exception\ModelNotFoundException
		 */
		public function getUserLoginLogDetail($id)
		{
			$data = (new UserLogExtend())->where(['id'=>$id])->find();
			if ($data){
				$data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
			}
			return $data;
		}

		public function getUserLoginLog($where)
		{
			return (new UserLogExtend())->where($where)->find();
		}

		/**
		 * 获取指定物业登录日志
		 * @param     $propertyId
		 * @param int $offset
		 * @param int $length
		 *
		 * @return \think\Collection
		 * @throws \think\db\exception\DataNotFoundException
		 * @throws \think\db\exception\DbException
		 * @throws \think\db\exception\ModelNotFoundException
		 */
		public function getPropertyUserLoginLog($propertyId,$offset=0,$length=20)
		{
			$loginRole = (new AdminLoginService())->getPropertyLoginRoleNumberArr();
			$where[] = ['login_type','in',$loginRole];
			$where[] = ['login_id' ,'=', $propertyId];
			$data =  (new UserLogExtend())->where($where)->limit($offset,$length)->order('id', 'DESC')->select();
			 
			if ($data){
				foreach ($data as &$item){
					$item['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
				}
			}

			return $data;
		}
		
	}