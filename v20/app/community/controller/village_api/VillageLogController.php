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

	namespace app\community\controller\village_api;

	use app\common\model\service\CommonSystemLogService;
	use app\community\controller\CommunityBaseController;
	use app\community\model\service\UserLogExtendService;
	use app\traits\house\AdminLoginTraits;
	use Sabberworm\CSS\Settings;

	class VillageLogController extends CommunityBaseController
	{
		use AdminLoginTraits;
		
		public function VillageSettingLog()
		{
			$village_id = $this->adminUser['village_id'];
			$service = new CommonSystemLogService();
			$page = $this->request->param('page',1,'int');
            $total_limit = 12;
			try{
				$count = $service->getVillageSettingLogCountByVillageId($village_id);
				$list  = $service->getVillageSettingLogByVillageId($village_id,$page,$total_limit);
				if (!empty($list)){
					foreach ($list as &$item){
						$item['addtime'] = date('Y-m-d H:i:s',$item['addtime']);
						$item['op_type'] = $this->traitGetOpTypeName($item['op_type']);
					}
				}
				
			}catch (\Exception $e){
				return api_output_error(2, $e->getMessage());
			}

			return api_output(0,['list'=>$list,'count'=>$count,'total_limit'=>$total_limit]);
		}
		
		public function VillageSettingLogDetail()
		{
			$village_id = $this->adminUser['village_id'];
			$service = new CommonSystemLogService();
			$fid = $this->request->post('log_fid',1,'int');

			$checkLog = $service->getCommonSystemLog(['id'=>$fid,'village_id'=>$village_id]);
			if ($checkLog){
				$logInfo = $service->getCommonSystemLogInfoByFid($checkLog['id']);
				$logExtend = $service->getCommonSystemLogExtendByFid($checkLog['id']);

				return api_output(0,['logInfo'=>$logInfo,'logExtend'=>$logExtend]);
			}
			return api_output_error(2, "非法访问");
		}
		
		
		public function VillageLoginLog()
		{
			$village_id = $this->adminUser['village_id'];
			$service = new UserLogExtendService();
			$page = $this->request->param('page',1,'int');
			$page = $page - 1;
			$list = $service->getVillageUserLoginLog($village_id,$page);
			$count = 0;
			$total_limit = 12;
			return api_output(0,['list'=>$list,'count'=>$count,'total_limit'=>$total_limit]);
			
		}
		
		public function VillageLoginLogDetail()
		{
			$village_id = $this->adminUser['village_id'];
			$service = new UserLogExtendService();
			$fid = $this->request->post('log_fid',1,'int');
			$checkLog = $service->getUserLoginLog(['id'=>$fid,'login_id'=>$village_id]);
			if ($checkLog){
				$logInfo = $service->getUserLoginLogDetail($checkLog['id']);
				return api_output(0,['logInfo'=>$logInfo]);
			}
			return api_output_error(2, "非法访问");
		}
	}