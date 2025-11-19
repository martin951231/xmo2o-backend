<?php
/**
 * 职位管理service
 * Author: lumin
 */

namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitJobCategory;
use app\recruit\model\db\NewRecruitHr;
use app\common\model\db\Area;
use app\merchant\model\service\MerchantService;
use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\db\NewRecruitIndustry;
use app\recruit\model\db\NewRecruitResumeSend;
use app\recruit\model\service\RecruitWelfareService;
use app\common\model\db\Merchant;

use net\Http;

class JobService{
	public $education = [
		'0' => '不限学历',
		'1' => '初中',
		'2' => '高中',
		'3' => '中技',
		'4' => '中专',
		'5' => '大专',
		'6' => '本科',
		'7' => '硕士',
		'8' => '博士',
	];

	public $job_age = [
		'-1' => '应届毕业生',
		'0' => '不限经验',
		'1' => '一年以上',
		'2' => '两年以上',
		'3' => '三年以上',
		'4' => '四年以上',
		'5' => '五年以上',
		'6' => '六年以上',
		'7' => '七年以上',
		'8' => '八年以上',
		'9' => '九年以上',
		'10' => '十年以上'
	];
	public function getJobList($condition = [], $page = 1, $pageSize = 10){
		$NewRecruitJob = new NewRecruitJob();
		$where = [];
		if(isset($condition['mer_id'])){
			$where[] = [
				'mer_id', '=', $condition['mer_id']
			];
		}

		if(isset($condition['first_cate'])){
			$where[] = [
				'first_cate', '=', $condition['first_cate']
			];
		}
		if(isset($condition['second_cate'])){
			$where[] = [
				'second_cate', '=', $condition['second_cate']
			];
		}
		if(isset($condition['third_cate'])){
			$where[] = [
				'third_cate', '=', $condition['third_cate']
			];
		}

		if(isset($condition['author'])){
			$where[] = [
				'author', '=', $condition['author']
			];
		}

		if(isset($condition['education'])){
			$where[] = [
				'education', '=', $condition['education']
			];
		}

		if(isset($condition['job_age'])){
			if(strpos($condition['job_age'], ',')){
				$job_age = explode(',', $condition['job_age']);
				$where[] = [
					'job_age', 'between', [$job_age[0], $job_age[1]]
				];
			}
			else{
				$where[] = [
					'job_age', '=', $condition['job_age']
				];
			}
		}

		if(isset($condition['status'])){
			$where[] = [
				'status', '=', $condition['status']
			];
		}
		$where[] = ['add_type', '=', 0];
		$where[] = ['is_del', '=', 0];
		$keyword = isset($condition['keyword']) ? trim($condition['keyword']) : '';
		$data = $NewRecruitJob->getJobList($where, $keyword, ($page-1)*$pageSize, $pageSize);
		$data = $this->dealJobData($data->toArray());
		return ['list' => $data, 'count' => $NewRecruitJob->getJobCount($where, $keyword)];
	}

	public function dealJobData($data){
		if(empty($data)) return [];
		$cateids = array_unique(array_merge(array_column($data, 'first_cate'), array_column($data, 'second_cate'), array_column($data, 'third_cate')));
		$NewRecruitJobCategory = new NewRecruitJobCategory();
		$cateData = $NewRecruitJobCategory->getSome([['cat_id', 'in', $cateids]], 'cat_id, cat_title');
		$cateData = $cateData ? $cateData->toArray() : [];
		$cateMap = [];
		if($cateData){
			$cateMap = array_column($cateData, 'cat_title', 'cat_id');
		}
		$areaids = array_unique(array_merge(array_column($data, 'province_id'), array_column($data, 'city_id'), array_column($data, 'area_id')));
		$Area = new Area();
		$areaData = $Area->getSome([['area_id', 'in', $areaids]], 'area_id, area_name');
		$areaData = $areaData ? $areaData->toArray() : [];
		$areaMap = [];
		if($areaData){
			$areaMap = array_column($areaData, 'area_name', 'area_id');
		}

		$merids = array_column($data, 'mer_id');
		$merData = (new NewRecruitCompany)->getSome([['mer_id', 'in', $merids]], 'mer_id, name');
		$merMap = [];
		if($merData){
			$merMap = array_column($merData->toArray(), 'name', 'mer_id');
		}

		$merLogo = [];
		$merData = (new Merchant)->getSome([['mer_id', 'in', $merids]], 'mer_id, logo');
		if($merData){
			$merLogo = array_column($merData->toArray(), 'logo', 'mer_id');
		}

		$welfares = array_column($data, 'fuli');
		$wf = [];
		foreach ($welfares as $welfare) {
			$wf = array_merge($wf, explode(',', $welfare));
		}
		$wfMap = [];
		$RecruitWelfareService = new RecruitWelfareService();
		if($wf){
			$wfData = $RecruitWelfareService->getFuli($wf);
			$wfMap = array_column($wfData, 'name', 'id');
		}

		foreach ($data as $key => &$value) {
			$job_cate = [];
			if(isset($value['first_cate'])){
				$job_cate[] = $cateMap[$value['first_cate']] ?? '';
			}
			if(isset($value['second_cate'])){
				$job_cate[] = $cateMap[$value['second_cate']] ?? '';
			}
			if(isset($value['third_cate'])){
				$job_cate[] = $cateMap[$value['third_cate']] ?? '';
			}
			$value['job_cate'] = implode('->', $job_cate);

			
			$value['logo'] = $merLogo[$value['mer_id']] ? replace_file_domain($merLogo[$value['mer_id']]) : "";
            $value['deliveries_nums'] =(new NewRecruitResumeSend())->getCount(['position_id'=>$value['job_id']]);
			$area = [];
			$value['province'] = "";
			$value['city'] = "";
			$value['area'] = "";
			if(isset($value['province_id'])){
				$area[] = $areaMap[$value['province_id']] ?? '';
				$value['province'] = $areaMap[$value['province_id']] ?? '';
			}
			if(isset($value['city_id'])){
				$area[] = $areaMap[$value['city_id']] ?? '';
				$value['city'] = $areaMap[$value['city_id']] ?? '';
			}
			if(isset($value['area_id'])){
				$area[] = $areaMap[$value['area_id']] ?? '';
				$value['area'] = $areaMap[$value['area_id']] ?? '';
			}
			$value['address_detail'] = implode('', $area).($value['address'] ?? '');

			if(isset($value['fuli'])){
				$fuli = explode(',', $value['fuli']);
				$value['welfare'] = [];
				foreach ($fuli as $fl) {
					if(isset($wfMap[$fl])){
						$value['welfare'][] = $wfMap[$fl];
					}
				}
				$value['fuli_txt'] = implode(', ', $value['welfare']);
			}

			if(isset($value['education'])){
				$value['education'] =  $this->education[$value['education']] ?? '不限学历';
			}
			if(isset($value['job_age'])){
				$value['job_age'] = $this->job_age[$value['job_age']] ?? '未知';
			}
			if(isset($value['age'])){
				if($value['age'] == '0,0' || $value['age'] == '0'){
					$value['age'] = '不限经验';
				}
				else{
					$age = explode(',', $value['age']);
					if($age[0] == $age[1]){
						$value['age'] = $age[0].'岁';		
					}
					else{
						if($age[0] == '0'){
							$value['age'] = $age[1].'岁以下';	
						}
						elseif($age[1] == '0'){
							$value['age'] = $age[0].'岁以上';		
						}
						else{
							$value['age'] = $age[0]."岁~".$age[1]."岁";
						}
					}
				}
			}
			$value['user_wages'] = "";
			if(isset($value['wages']) && strpos($value['wages'], ',') !== false){
				if($value['wages'] == '0,0'){
					$value['wages'] = '不限';
					$value['user_wages'] = "面议";
				}
				else{
					$wages = explode(',', $value['wages']);
					if($wages[0] == $wages[1]){
						$value['wages'] = $wages[0];		
						$value['user_wages'] = ($wages[0]/1000)."k";
					}
					else{
						if($wages[0] == '0'){
							$value['wages'] = $wages[1].'以下';	
							$value['user_wages'] = ($wages[1]/1000)."k以下";
						}
						elseif($wages[1] == '0'){
							$value['wages'] = $wages[0].'以上';		
							$value['user_wages'] = ($wages[0]/1000)."k以上";
						}
						else{
							$value['wages'] = $wages[0]."~".$wages[1];
							$value['user_wages'] = ($wages[0]/1000)."~".($wages[1]/1000)."k";
						}
					}
				}
			}
            if($value['wages']=='0'){
                $value['wages'] = '面议';
            }
			if(isset($value['status'])){
				$value['status_txt'] = $value['status'] == '1' ? '已上线' : '已下线';
			}
			if(!empty($value['create_time']) && !empty($value['end_time'])){
				$value['time'] = date("Y-m-d H:i:s", $value['create_time'])."~".date("Y-m-d H:i:s", $value['end_time']);
			}else{
                $value['time'] = "无";
            }
			if(isset($value['author'])){
				$value['author_name'] = $this->getAuthorName($value['author']);
			}
			if(isset($value['type'])){
				$value['type_txt'] = '';
				if($value['type'] == '1'){
					$value['type_txt'] = '全职';
				} 
				elseif($value['type'] == '2'){
					$value['type_txt'] = '兼职';
				} 
				elseif($value['type'] == '3'){
					$value['type_txt'] = '实习';
				} 
			}

			if(isset($value['mer_id'])){
				$value['mer_name'] = $merMap[$value['mer_id']] ?? '';
			}
		}
		return $data;
	}

	public function getJobDetail($job_id){
		if(empty($job_id)) return [];
		$NewRecruitJob = new NewRecruitJob();
		$where = [
			['job_id', '=', $job_id]
		];
		$data = $NewRecruitJob->getSome($where);
		if(empty($data)){
			return [];
		}
		$data = $this->dealJobData($data->toArray());
		return $data[0];
	}

	public function updateJob($job_id, $update){
		if(empty($job_id) || empty($update)) return false;
		$update['update_time'] = time();
		$NewRecruitJob = new NewRecruitJob();
		return $NewRecruitJob->updateThis(['job_id'=>$job_id], $update);
	}

	public function getAllCates($merId=0){
		$NewRecruitJobCategory = new NewRecruitJobCategory();
		$first = $NewRecruitJobCategory->getSome([['is_del','=',0], ['cat_fid', '=', 0]], 'cat_id, cat_fid, cat_title', 'sort desc');
		$return = [];
		if($first){
			$first = $first->toArray();
			foreach ($first as $key => $value) {
			    if($merId){
                    $job_one=(new NewRecruitJob())->getOne(['first_cate'=>$value['cat_id'],'mer_id'=>$merId,'is_del'=>0]);
                    if(empty($job_one)){
                        unset($first[$key]);
                        continue;
                    }
                }
				$sons = $NewRecruitJobCategory->getSome([['is_del','=',0], ['cat_fid', '=', $value['cat_id']]], 'cat_id, cat_fid, cat_title', 'sort desc');
				if($sons){
					$first[$key]['sons'] = $sons->toArray();
					foreach ($first[$key]['sons'] as $key2 => $value2) {
                        if($merId){
                            $job_one=(new NewRecruitJob())->getOne(['second_cate'=>$value2['cat_id'],'mer_id'=>$merId,'is_del'=>0]);
                            if(empty($job_one)){
                                continue;
                            }
                        }
						$sons2 = $NewRecruitJobCategory->getSome([['is_del','=',0], ['cat_fid', '=', $value2['cat_id']]], 'cat_id, cat_fid, cat_title', 'sort desc');
						if($sons2){
							$first[$key]['sons'][$key2]['sons'] = $sons2->toArray();
                            foreach ($first[$key]['sons'][$key2]['sons'] as $key3 => $value3) {
                                if ($merId) {
                                    $job_one = (new NewRecruitJob())->getOne(['third_cate' => $value3['cat_id'], 'mer_id' => $merId, 'is_del' => 0]);
                                    if (empty($job_one)) {
                                        unset($first[$key]['sons'][$key2]['sons'][$key3]);
                                        continue;
                                    }
                                }
                            }
                            if ($merId) {
                                $first[$key]['sons'][$key2]['sons'] = array_values($first[$key]['sons'][$key2]['sons']);
                            }
						}
						else{
							$first[$key]['sons'][$key2]['sons'] = [];
						}
					}
				}
				else{
					$first[$key]['sons'] = [];
				}
			}
			if($merId){
                $first = array_values($first);
            }
			$return = $first;
		}
		return $return;
	}

	public function getAuthorName($hr_id){
		$NewRecruitHr = new NewRecruitHr();
		$data = $NewRecruitHr->getOne([['id','=', $hr_id]]);
		if($data){
			$data = $data->toArray();
			return $data['first_name'].$data['last_name'];
		}
		return "";
	}

	public function getAllHr($mer_id){
		$NewRecruitHr = new NewRecruitHr();
		$data = $NewRecruitHr->getSome([['status', '=', 0], ['mer_id', '=', $mer_id]], 'uid as hr_id,first_name, last_name');
		if($data){
			$data = $data->toArray();
			foreach ($data as $key => $value) {
				$data[$key]['hr_name'] = $value['first_name'].$value['last_name'];
			}
			return $data;
		}
		return [];
	}

	public function searchNotRepeat($keywords, $limit = 5){
		$NewRecruitJob = new NewRecruitJob();
		$where = [
			['job_name', 'like', $keywords."%"], 
			['is_del', '=', 0],
			['status', '=', 1]
		];
		$data = $NewRecruitJob->selectNotRepeat($where, 'job_name', $limit);
		if($data){
			return $data->toArray();
		}
		else{
			return [];
		}
	}

	public function searchByKeywords($keywords, $page = 1, $pageSize = 10){
		$NewRecruitJob = new NewRecruitJob();
		$where = [
			['job_name', 'like', $keywords."%"], //产品说必须以开头搜
			['is_del', '=', 0],
			['status', '=', 1],
            ['add_type', '=', 0],
		];
		$data = $NewRecruitJob->getJobByName($where, ($page - 1)*$pageSize, $pageSize);
		$count = $NewRecruitJob->getCount($where);
		if($data){
			$data = $this->dealJobData($data->toArray());
		}
		else{
			$data = [];
		}
		return ['count'=>$count, 'data'=>$data];
	}

	public function getJobsCount($mer_id){
		$NewRecruitJob = new NewRecruitJob();
		$where = [
			['mer_id', '=', $mer_id], 
			['is_del', '=', 0],
			['status', '=', 1]
		];
		return $NewRecruitJob->getCount($where);
	}

	public function searchByMerId($mer_id, $page = 1, $pageSize = 10){
		$NewRecruitJob = new NewRecruitJob();
		$where = [
			['mer_id', '=', $mer_id], 
			['is_del', '=', 0],
			['add_type', '=', 0],
			['status', '=', 1]
		];
		$data = $NewRecruitJob->getSome($where, true, ($page - 1)*$pageSize, $pageSize);
		$count = $NewRecruitJob->getCount($where);
		if($data){
			$data = $this->dealJobData($data->toArray());
		}
		else{
			$data = [];
		}
		return ['count'=>$count, 'data'=>$data];
	}

	public function getCompany($keywords, $page = 1, $pageSize = 10){
		$NewRecruitCompany = new NewRecruitCompany();
		$where = [
			['name', 'like', "%".$keywords."%"]
		];
		$data = $NewRecruitCompany->getSome($where, true, true, ($page - 1)*$pageSize, $pageSize);
		$count = $NewRecruitCompany->getCount($where);
		if($data){
			$data = (new CompanyService)->dealCompanyData($data->toArray());
		}
		else{
			$data = [];
		}
		return ['count'=>$count, 'data'=>$data];
	}

	/**
	 * 用户首页搜索职位列表
	 * @param  [type] $user_intention 用户求职意向
	 * @param  [type] $user_params    用户选择的临时搜索项
	 * @param  [type] $page           分页
	 * @param  [type] $pageSize       分页
	 * @return [type]                 [description]
	 */
	public function searchJobList($user_intention, $user_params, $page, $pageSize){
		$where = "j.is_del = 0 AND j.status = 1 AND j.add_type = 0 AND m.recruit_status=1";

		//城市区域筛选条件
		/*if(!empty($user_params['circle_ids']) && $user_params['circle_ids'] != '-5'){
			$where .= " AND j.circle_id in (".$user_params['circle_ids'].")";
		}elseif(isset($user_intention['circle_id']) && !empty($user_intention['circle_id']) && $user_params['circle_ids'] == '-5'){
			$where .= " AND j.circle_id in (".$user_intention['circle_id'].")";
		}else*/if(!empty($user_params['area_id']) && $user_params['area_id'] != -5){
			$where .= " AND j.area_id = ".$user_params['area_id'];
		}elseif(isset($user_intention['area_id']) && !empty($user_intention['area_id']) && $user_params['area_id'] == -5){
			$where .= " AND j.area_id = ".$user_intention['area_id'];
		}elseif(!empty($user_params['city_id']) && $user_params['city_id'] != -5){
			$where .= " AND j.city_id = ".$user_params['city_id'];
		}elseif(isset($user_intention['city_id']) && !empty($user_intention['city_id']) && $user_params['city_id'] == -5){
			$where .= " AND j.city_id = ".$user_intention['city_id'];
		}elseif(!empty($user_params['province_id']) && $user_params['province_id'] != -5){
			$where .= " AND j.province_id = ".$user_params['province_id'];
		}elseif(isset($user_intention['province_id']) && !empty($user_intention['province_id']) && $user_params['province_id'] == -5){
			$where .= " AND j.province_id = ".$user_intention['province_id'];
		}

		//学历
		if($user_params['education_id'] >= 0){
			$where .= " AND j.education = ".$user_params['education_id'];
		}

		//薪资
		$wages_s = 0;
		$wages_e = 0;
		if(strpos($user_params['wages_id'], ',') !== false){
			$wages_id = explode(',', $user_params['wages_id']);
			$wages_s = $wages_id[0];
			$wages_e = $wages_id[1];
		}elseif(isset($user_intention['salary']) && !empty($user_intention['salary'])){
			$wages_id = explode(',', $user_intention['salary']);
			$wages_s = $wages_id[0];
			$wages_e = $wages_id[1];
		}
		if($wages_s > 0 || $wages_e > 0){//计算薪资交集的算法
			$temp = [];
			$temp[] = "(j.wages_start >= ".$wages_s." AND j.wages_start <= ".$wages_e.")";
			$temp[] = "(j.wages_end >= ".$wages_s." AND j.wages_end <= ".$wages_e.")";
			$temp[] = "(j.wages_start <= ".$wages_s." AND j.wages_end >= ".$wages_e.")";
			$where .= " AND (". implode(' OR ', $temp). ")";
		}

		//工作经验
		if(strpos($user_params['job_age_id'], ',') !== false){
			$job_age_id = explode(',', $user_params['job_age_id']);
			$where .= " AND j.job_age >= ".$job_age_id[0]." AND j.job_age <= ".$job_age_id[1];
		}

		//企业规模
		if($user_params['people_scale_id'] > 0){
			$where .= " AND c.people_scale = ".$user_params['people_scale_id'];
		}

		//福利
		$whereFindInSet = '';
		if(!empty($user_params['welfare_id']) && $user_params['welfare_id'] != '-5'){
			$welfare_id = explode(',', $user_params['welfare_id']);
			$temp = [];
			foreach ($welfare_id as $value) {
				$temp[] = "FIND_IN_SET(".$value.", j.fuli)";
			}
			$whereFindInSet = "(" .implode(" or ", $temp). ")";
			$where .= " AND ".$whereFindInSet;
		}

		//行业
		if($user_params['industry_id'] > 0){
			$where .= " AND c.industry_id1 = ".$user_params['industry_id'];
		}elseif(isset($user_intention['industry_ids']) && !empty($user_intention['industry_ids']) && $user_params['industry_id'] == -5){
			$where .= " AND c.industry_id2 in (".$user_intention['industry_ids'].")";
		}

		//工作性质
		if($user_params['type_id'] > 0){
			$where .= " AND j.type = ".$user_params['type_id'];
		}elseif(isset($user_intention['job_properties']) && $user_intention['job_properties'] > 0 && $user_params['type_id'] == -5){
			$where .= " AND j.type = ".$user_intention['job_properties'];
		}

		//岗位分类
		if(isset($user_intention['job_id']) && $user_intention['job_id'] > 0){
			$where .= " AND j.third_cate = ".$user_intention['job_id'];
		}


		$lat = $user_params['lat'];
		$lng = $user_params['lng'];

		$field = "j.*";
		if($lat > 0 && $lng > 0){
			$field .= ", sqrt((((".$lng."-c.long)*PI()*12656*cos(((".$lat."+c.lat)/2)*PI()/180)/180) * ((".$lng."-c.long)*PI()*12656*cos (((".$lat."+c.lat)/2)*PI()/180)/180))+(((".$lat."-c.lat)*PI()*12656/180)*((".$lat."-c.lat)*PI()*12656/180))) as distance";
		}

		$order = "";
		if($user_params['order'] == 'default'){
			$order = " ORDER BY j.recruit_nums desc, j.views desc, j.deliveries_nums desc, j.collect_nums desc";
		}
		elseif($user_params['order'] == 'distance'){
			if($lat == 0 || $lng == 0){
				$order = " ORDER BY j.recruit_nums desc, j.views desc, j.deliveries_nums desc, j.collect_nums desc";
			}
			else{
				$order = " ORDER BY distance desc";
			}
		}
		elseif($user_params['order'] == 'new'){
			$order = " ORDER BY j.update_time desc";
		}

		$limit = " LIMIT ".($page-1)*$pageSize." , ".$pageSize;

		$NewRecruitJob = new NewRecruitJob();

		$data = $NewRecruitJob->searchJobList1($where, $field, $order, $limit);

		$count = $NewRecruitJob->searchJobCount1($where);

		if($data){
			$data = $this->dealJobData($data);
			return [
				'count' => $count[0]['count'],
				'data' => $data
			];
		}
		return [
			'count' => 0,
			'data' => []
		];
	}

	// 职位列表
	public function recruitJobList($mer_id, $fields){
		$return = (new NewRecruitJob())->where(['mer_id'=>$mer_id])->field($fields)->select()->toArray();
		return $return;
	}

	// HR职位列表
	public function recruitJobHrList($where, $fields){
		$return = (new NewRecruitJob())->where($where)->field($fields)->select()->toArray();
		return $return;
	}
}