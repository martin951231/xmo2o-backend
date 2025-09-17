<?php
/**
 * 搜索
 * Created by subline.
 * Author: lumin
 */

namespace app\recruit\controller\api;
use app\recruit\model\service\JobService;
use app\merchant\model\service\MerchantService;
use app\recruit\model\db\NewRecruitCompany;
use app\common\model\service\AreaService;
use app\recruit\model\service\RecruitIndustryService;
use app\recruit\model\service\RecruitWelfareService;
use app\recruit\model\service\JobIntentionService;
use app\recruit\model\service\RecruitBannerService;
use app\recruit\model\service\CompanyService;

class SearchController extends ApiBaseController
{
	//联想接口
	public function autocomplete(){
		$keywords = $this->request->param("keywords", "", "trim");
		if(empty($keywords)){
			return api_output_error(1001, 'keywords必传!');
		}
		$JobService = new JobService();
		//先搜索5条职位信息
		$job = $JobService->searchNotRepeat($keywords, 5);

		$NewRecruitCompany = new NewRecruitCompany();
		//再搜索5条公司信息
		$company = $NewRecruitCompany->getSome([['name', 'like', "%".$keywords."%"]], 'mer_id, name');

		$output = [];
		foreach ($job as $key => $value) {
			$output[] = [
				'type' => 'job',
				'id' => 0,
				'name' => $value['job_name']
			];
		}

		foreach ($company as $key => $value) {
			$output[] = [
				'type' => 'company',
				'id' => $value['mer_id'],
				'name' => $value['name']
			];
		}
		return api_output(0, $output);
	}

	//搜索职位 根据关键字
	public function job(){
		$keywords = $this->request->param("keywords", "", "trim");
		$page = $this->request->param("page", 1, "intval");
		$pageSize = $this->request->param("pageSize", 10, "intval");
		$mer_id = $this->request->param("mer_id", 0, "intval");
		$JobService = new JobService();
		if($mer_id > 0){
			$data = $JobService->searchByMerId($mer_id, $page, $pageSize);
		}
		else{
			if(empty($keywords)){
				return api_output_error(1001, 'keywords必传!');
			}
			$data = $JobService->searchByKeywords($keywords, $page, $pageSize);
		}
		return api_output(0, $data);
	}

	//搜索公司 根据关键字
	public function company(){
		$keywords = $this->request->param("keywords", "", "trim");
		$page = $this->request->param("page", 1, "intval");
		$pageSize = $this->request->param("pageSize", 10, "intval");
		$JobService = new JobService();
		if(empty($keywords)){
			return api_output_error(1001, 'keywords必传!');
		}
		$data = $JobService->getCompany($keywords, $page, $pageSize);
		
		return api_output(0, $data);
	}

	//搜索条件
	public function searchCondition(){
		$city_id = $this->request->param("city_id", 0, "intval");
		$output = [
			'area' => [],
			'education' => [],//学历
			'wages' => [],//薪资
			'job_age' => [],//经验
			'industry' => [],//行业
			'people_scale' => [],//公司规模
			'welfare' => [],//福利
			'type' => [],//工作性质
		];
		$JobService = new JobService();
		if(!$city_id){
			$city_id = $this->userInfo['city_id'] ?? 0;
		}
		
		// $output['area'] = (new AreaService)->getAreaAndCircle($city_id);
        $output['area'] = (new AreaService)->getAllArea(0,'*','sons');
		foreach ($JobService->education as $key => $value) {
			$output['education'][] = [
				'education_id' => $key,
				'val' => $value
			];
		}
		$output['education'] = array_merge([['education_id'=>-1,'val'=>'全部']], $output['education']);

		$output['wages'] = [
			[
				'wages_id' => '0',
				'val' => '全部',
			],
			[
				'wages_id' => '0, 3000',
				'val' => '3k以下',
			],
			[
				'wages_id' => '3000,5000',
				'val' => '3-5k',
			],
			[
				'wages_id' => '5000,10000',
				'val' => '5-10k',
			],
			[
				'wages_id' => '10000,20000',
				'val' => '10-20k',
			],
			[
				'wages_id' => '20000,50000',
				'val' => '20-50k',
			],
			[
				'wages_id' => '50000,2000000',
				'val' => '50k',
			]
		];

		$output['job_age'] = [
			[
				'job_age_id' => '-2',
				'val' => '全部',
			],
			[
				'job_age_id' => '0,0',
				'val' => '经验不限',
			],
			[
				'job_age_id' => '-1,0',
				'val' => '应届生',
			],
			[
				'job_age_id' => '1,2',
				'val' => '1-2年',
			],
			[
				'job_age_id' => '3,5',
				'val' => '3-5年',
			],
			[
				'job_age_id' => '6,10',
				'val' => '6-10年',
			],
			[
				'job_age_id' => '10,30',
				'val' => '10年以上',
			]
		];


		$output['industry'] = array_merge([['industry_id'=>0,'val'=>'全部']], (new RecruitIndustryService)->getFirstIndustry());

		$output['people_scale'] = [
			[
				'people_scale_id' => 0,
				'val' => "全部"
			],
			[
				'people_scale_id' => 1,
				'val' => "<50人"
			],
			[
				'people_scale_id' => 2,
				'val' => "50~100人"
			],
			[
				'people_scale_id' => 3,
				'val' => "101-200人"
			],
			[
				'people_scale_id' => 4,
				'val' => "201~500人"
			],
			[
				'people_scale_id' => 5,
				'val' => "500人~1000人以上"
			]
		];

		$output['welfare'] = array_merge([['welfare_id'=>0,'val'=>'全部']], (new RecruitWelfareService)->getAllWelfare());

		$output['type'] = [
			[
				'type_id' => 0,
				'val' => "全部"
			],
			[
				'type_id' => 1,
				'val' => "全职"
			],
			[
				'type_id' => 2,
				'val' => "兼职"
			],
			[
				'type_id' => 3,
				'val' => "实习"
			]
		];
		return api_output(0, $output);
	}

	//用户端首页
	public function index(){
		$intention_id = $this->request->param("intention_id", 0, "intval");
		$output = [
			'intention' => [],//求职意向  未登录情况下为空
			'banner' => [],
			'now_cityname' => '',
			'hot_list' => []
		];

		$output['intention'] = (new JobIntentionService)->getUserIntentions($this->_uid, $intention_id);
		$output['banner'] = (new RecruitBannerService)->getBanners(5);
		$city_id = $this->userInfo['city_id'] ?? cfg("now_city");
		if (isset($output['intention'][0]['city_id'])){
			$city_id = $output['intention'][0]['city_id'];
		}
		$city = (new AreaService)->getAreaByAreaId($city_id);
		$output['now_cityname'] = $city['area_name'] ?? "未配置城市";
		$output['hot_list'] = (new CompanyService)->getHostCompany(1, 5);
		return api_output(0, $output);

	}

	public function list(){

		$page = $this->request->param("page", 1, "intval");
		$pageSize = $this->request->param("pageSize", 10, "intval");

		//以下是用户选择的  -5就走求职意向
		$user_params = [];
		$user_params['province_id'] = $this->request->param("province_id", -5, "intval");
		$user_params['city_id'] = $this->request->param("city_id", -5, "intval");
		$user_params['area_id'] = $this->request->param("area_id", -5, "intval");
		$user_params['circle_ids'] = $this->request->param("circle_ids", "-5", "trim");
		$user_params['education_id'] = $this->request->param("education_id", -5, "intval");
		$user_params['wages_id'] = $this->request->param("wages_id", '-5', "trim");
		$user_params['job_age_id'] = $this->request->param("job_age_id", '-5', "trim");
		$user_params['industry_id'] = $this->request->param("industry_id", -5, "intval");
		$user_params['people_scale_id'] = $this->request->param("people_scale_id", -5, "intval");
		$user_params['welfare_id'] = $this->request->param("welfare_id", '-5', "trim");
		$user_params['type_id'] = $this->request->param("type_id", -5, "intval");

		$user_params['lng'] = $this->request->param("lng", 0);
		$user_params['lat'] = $this->request->param("lat", 0);

		$user_params['order'] = $this->request->param("order", 'default');
		
		//以下是用户求职意向
		$intention_id = $this->request->param("intention_id", 0, "intval");
		if($intention_id == 0){
			//如果用户没有选择，那就默认选中用户的求职意向的第一个
			$intentions = (new JobIntentionService)->getUserIntentions($this->_uid);
			$intention_id = $intentions[0]['id'] ?? 0;
		}
		$user_intention = (new JobIntentionService)->getInfo($intention_id);

		/**
		 * 规则是：如果用户选择了求职意向，也可以再临时选择其他选项
		 */
		$data = (new JobService)->searchJobList($user_intention, $user_params, $page, $pageSize);

		return api_output(0, $data);
	}
}