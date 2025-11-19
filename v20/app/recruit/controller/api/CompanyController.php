<?php
/**
 * 公司相关接口
 * Created by subline.
 * Author: lumin
 */

namespace app\recruit\controller\api;

use app\recruit\model\service\CompanyService;
use app\common\model\db\Area;

class CompanyController extends ApiBaseController
{
	//热门企业
	public function hotList(){
		$page = $this->request->param("page", 1, "intval");
		$pageSize = $this->request->param("pageSize", 10, "intval");

		$CompanyService = new CompanyService();
		$data = $CompanyService->getHostCompany($page, $pageSize);

		return api_output(0, $data);
	}

	//公司列表
	public function companyList(){
		$page = $this->request->param("page", 1, "intval");
		$pageSize = $this->request->param("pageSize", 10, "intval");

		$CompanyService = new CompanyService();
		$data = $CompanyService->companyList($page, $pageSize);

		return api_output(0, $data);
	}

	//公司主页
	public function index(){
		$mer_id = $this->request->param("mer_id", 0, "intval");
		if(empty($mer_id)){
			return api_output_error(1001, '请完善公司信息!');
		}
		$CompanyService = new CompanyService();
		$detail = $CompanyService->getCompanyInfo($mer_id);
		if(empty($detail)) return api_output_error(1003, '请完善公司信息!');
		$CompanyService->addViews($mer_id);
		$detail = $CompanyService->dealCompanyDatas([$detail])[0];

		$areaids = [$detail['province_id'], $detail['city_id'], $detail['area_id']];
		$Area = new Area();
		$areaData = $Area->getSome([['area_id', 'in', $areaids]], 'area_id, area_name');
		$areaData = $areaData ? $areaData->toArray() : [];
		$areaMap = [];
		if($areaData){
			$areaMap = array_column($areaData, 'area_name', 'area_id');
		}
		$detail['address'] = ($areaMap[$detail['province_id']] ?? "").($areaMap[$detail['city_id']] ?? "").($areaMap[$detail['area_id']] ?? "").$detail['address'];

		$detail['logo'] = replace_file_domain($detail['logo']);
		$output = [
			'detail' => $detail,
			'collect' => $CompanyService->getUserCollect($this->_uid, $mer_id),
			'forbidden' => $CompanyService->getUserForbidden($this->_uid, $mer_id),
		];
		return api_output(0, $output);
	}

	//用户收藏/取消收藏
	public function collect(){
		if(empty($this->_uid)) return api_output_error(1002, '未登录');
		$mer_id = $this->request->param("mer_id", 0, "intval");
		if(empty($mer_id)){
			return api_output_error(1001, '公司ID必传!');
		}
		$CompanyService = new CompanyService();
		$r = $CompanyService->doCollect($this->_uid, $mer_id);
		if($r){
			return api_output(0, []);
		}
		else{
			return api_output_error(1003, '操作失败!');	
		}
	}

	//用户屏蔽公司/取消屏蔽
	public function forbidden(){
		if(empty($this->_uid)) return api_output_error(1002, '未登录');
		$mer_id = $this->request->param("mer_id", 0, "intval");
		if(empty($mer_id)){
			return api_output_error(1001, '公司ID必传!');
		}
		$CompanyService = new CompanyService();
		$r = $CompanyService->doForbidden($this->_uid, $mer_id);
		if($r){
			return api_output(0, []);
		}
		else{
			return api_output_error(1003, '操作失败!');	
		}
	}
}