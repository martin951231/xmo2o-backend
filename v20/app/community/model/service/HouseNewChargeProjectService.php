<?php

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseNewCharge;
use app\community\model\db\HouseNewChargeNumber;
use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewChargeRule;
use app\community\model\db\HouseNewChargeTime;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\service\HouseNewChargeService;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;

class HouseNewChargeProjectService{


    protected $HouseNewChargeProject;
    protected $HouseNewChargeNumber;
    protected $HouseNewChargeService;
    protected $HouseNewChargeRule;

	use CommonLogTraits;
	use AdminLoginTraits;

    public function __construct()
    {
        $this->HouseNewChargeRule = new HouseNewChargeRule();
        $this->HouseNewChargeProject =  new HouseNewChargeProject();
        $this->HouseNewChargeNumber =  new HouseNewChargeNumber();
        $this->HouseNewChargeService =  new HouseNewChargeService();
    }
    /**
     * 收费项目列表
     * @author: liukezhu
     * @date : 2021/6/10
     * @param $param
     * @return mixed
     */
    public function getListALL($param)
    {
        if(intval($param['page']) > 0){
            $page = $param['page'];
        }
        if (empty($param['limit'])){
            $limit = 999999;
        }else{
            $limit = $param['limit'];
        }
        if (isset($param['village_id'])) {
            $where[] = ['p.village_id', '=', $param['village_id']];
        }
        if(isset($param['project_type']) && $param['project_type']=='cycle'){
            $where[]=['p.type', '=', 2];
        }else{
            $where[]=['p.type', '=', 1];
        }
        $where[]=['p.status', '=', $param['status']];


        $orderby='p.id desc';
        $field = 'p.id,p.village_id,p.name,c.charge_number_name as subject,p.status,p.type,c.charge_type,hr.unit_price,hr.fees_type,hr.charge_price,hr.bill_create_set,hr.unit_gage_type';
        $data = $this->HouseNewChargeProject->getListAll($where, $field,$orderby,$page,$limit)->toArray();

        var_dump($data);die;
        return $data;
    }


    /**
     * 收费项目列表
     * @author: liukezhu
     * @date : 2021/6/10
     * @param $param
     * @return mixed
     */
    public function getList($param){
        if (empty($param['limit'])){
            $limit = 20;
        }else{
            $limit = $param['limit'];
        }
        $where=array();
        if (isset($param['villageIds'])) {
            $where[] = ['p.village_id', 'in', $param['villageIds']];
        } else {
            $where[] = ['p.village_id', '=', $param['from_id']];
        }
        $isselectdata=false;
        $orderby='p.id desc';
        if(isset($param['type']) && $param['type']=='selectdata'){
            //取所有包括删除的
            $isselectdata=true;
            $orderby='p.status ASC,p.id desc';
        }elseif(isset($param['type']) && $param['type']=='selectdata_nodel'){
            $orderby='p.status ASC,p.id desc';
            $where[]=['p.status', 'in', [1,2]];
        }else{
            $where[]=['p.status', 'in', [1,2]];
        }
        if(isset($param['charge_type']) && $param['charge_type']){
            $where[]=['c.charge_type', '=', $param['charge_type']];
        }
        if(isset($param['project_type']) && $param['project_type']=='cycle'){
            $where[]=['p.type', '=', 2];
        }
        if(isset($param['xresource']) && $param['xresource']=='water_electric_gas'){
            $charge_type_arr=array('electric','gas','water');
            $where[]=['c.charge_type', 'in', $charge_type_arr];
        }
        $where[] = ['p.status', '<>', 4];
        if(intval($param['page']) > 0){
           $page = isset($param['page']) && $param['page'] ? $param['page'] : 0;
           if(isset($param['keyword']) && !empty($param['keyword'])){
               $where[] = ['p.name','like','%'.$param['keyword'].'%'];
           }
           $field = 'p.id,p.village_id,p.name,c.charge_number_name as subject,p.status,p.type,c.charge_type';
           $list = $this->HouseNewChargeProject->getList($where, $field,$orderby,$page,$limit);
           $count = $this->HouseNewChargeProject->getCount($where);
           $data['list'] = $list;
           $data['total_limit'] = $limit;
           $data['count'] = $count;
           $serviceHouseNewPorperty = new HouseNewPorpertyService();
           $isSoftwareMew = isSoftwareMew();
           if ($isSoftwareMew) {
               $oldVersion = '';
           } else {
               $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($param['property_id']);
               if ($takeEffectTimeJudge) {
                   $oldVersion = cfg('site_url').'/v20/public/platform/#/village/village.iframe/house_village_money_money_list/requestType=oldVersion';
               } else {
                   $oldVersion = '';
               }
           }
           $data['oldVersion'] = $oldVersion;
        }else{
            $field = 'p.id,p.village_id,p.name,c.charge_number_name as subject,p.status,p.type,c.charge_type';
            $listObj = $this->HouseNewChargeProject->getList($where, $field,$orderby);
            $list=array();
            if($listObj && !$listObj->isEmpty()){
                $list=$listObj->toArray();
            }
            if(isset($param['charge_type']) && $param['charge_type']) {
                $tmp_name='请选择收费项目';
                if(empty($list)){
                    $tmp_name='没有数据可选';
                }
                array_unshift($list, ['id'=> '', 'name' => $tmp_name,'charge_type'=>'','type'=>2]);
            }
            $data['list'] = $list;
       }
        foreach ($data['list'] as &$value){
            if($isselectdata){
                if($value['status']==4){
                    $value['name']=$value['name'].'(已删除)';
                }
            }
            $value['type_txt'] = in_array($value['charge_type'],['water','electric','gas']) ? '-' : (($value['type'] != 2) ? "一次性费用" : "周期性费用");
            $value['qrcode'] = $value['charge_type']=='qrcode' ? $this->createProjectQrcode($value['id'],$value['village_id']): '';
        }
        $configCustomizationService= new ConfigCustomizationService();
        $isfuncKaidalaicnSpecial=$configCustomizationService->getfuncKaidalaicnSpecial();
        if($isfuncKaidalaicnSpecial && $isselectdata){
            $whereArr=array(['village_id','=',0],['subject_id','=',0],['status','=',5],['from_type','in',['red_reversal','append_money']]);
            $ruleList=$this->HouseNewChargeRule->getList($whereArr,'id,village_id,charge_project_id,from_type','id desc',0,10);
            $projectIds=array();
            if($ruleList && !$ruleList->isEmpty()){
                 foreach ($ruleList as $rvv){
                     $projectIds[]=$rvv['charge_project_id'];
                 }
            }
            if($projectIds){
                $chargeProjectSpecial=$this->HouseNewChargeProject->getLists(array(['id','in',$projectIds]),'id,name,status,type','id desc',0,10);
                if($chargeProjectSpecial && !$chargeProjectSpecial->isEmpty()){
                    foreach ($chargeProjectSpecial as $spp){
                        $tmpProject=array('id'=>$spp['id'],'name'=>$spp['name'],'type'=>$spp['type']);
                        $data['list'][]=$tmpProject;
                    }
                    
                }
            }
        }
        return $data;
    }
    
    /**
     * @param $project_id
     * @return string
     * 获取二维码
     */
    public function createProjectQrcode($project_id,$village_id)
    {
        if (empty($project_id) || empty($village_id)) return '';
        require_once '../extend/phpqrcode/phpqrcode.php';
        $dir = '/runtime/qrcode/village/project/' . $project_id.'_'.$village_id;
        $path = '../..' . $dir;
        $filename = md5($project_id.'_'.$village_id) . '.png';
        if (file_exists($path . '/' . $filename)) {
            return cfg('site_url') . $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = cfg('site_url').'/packapp/village/pages/houseMeter/NewCollectMoney/ercodePayment?project_id='.$project_id.'&village_id='.$village_id;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return cfg('site_url') . $dir . '/' . $filename;
    }
    /**
     * 收费项目列表
     * @author: lijie
     * @date : 2021/6/10
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return mixed
     */
    public function getLists($where=[],$field=true,$order='p.id desc')
    {
        $data = $this->HouseNewChargeProject->getList($where,$field,$order,0);
        return $data;
    }
    
    public function getProjectList($where=[],$field=true,$order='p.id desc')
    {
        $data = $this->HouseNewChargeProject->getList($where,$field,$order,0);
        return $data;
    }

    public function getchargeProjectList($where=[],$field='*',$order='id desc')
    {
        $data = $this->HouseNewChargeProject->getLists($where,$field,$order,0);
        return $data;
    }
    
    public function getProjectListByChargeType($where=[],$field=true)
    {
        $data = $this->HouseNewChargeProject->getProjectListByChargeType($where,$field);
        return $data;
    }

    /**
     * 获取收费科目
     * @author: liukezhu
     * @date : 2021/6/11
     * @param $property_id
     * @return array|\think\Collection
     */
    public function getSubject($property_id, $noHasChargeTypeArr = [])
    {
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['status', '=', 1];
        if (!empty($noHasChargeTypeArr)) {
            $where[] = ['charge_type', 'not in', $noHasChargeTypeArr];
        }
        $list = $this->HouseNewChargeNumber->getList($where, 'id,charge_type as type,charge_number_name as name');
        if (!empty($list)) {
            $list = $list->toArray();
        }
        if ($list) {
            foreach ($list as &$v) {
                $status = 1;
                if (in_array($v['type'], ['water', 'electric', 'gas'])) {
                    $status = 0;
                }
                $v['status'] = $status;
            }
            unset($v);
        }
        return $list;
    }

    public function getIcon($type){
       $img= $this->HouseNewChargeService->charge_img;
       $url='';
       if(isset($img[$type]) && !empty($img[$type])){
           $url= cfg('site_url').$img[$type];
       }
       return $url;
    }
    public function getChargeNanmeStr($chargeType=''){
        $houseNewChargeService =  new HouseNewChargeService();
        $chargeNanmeStr='';
        $charge_type_arr= $this->HouseNewChargeService->charge_type;
        if($chargeType && !is_array($chargeType)){
            $chargeNanmeStr=isset($charge_type_arr[$chargeType]) ? $charge_type_arr[$chargeType]:'';
        }else if($chargeType && is_array($chargeType)){
            $chargeType=array_unique($chargeType);
            $chargeNanmeArr=array();
            foreach ($chargeType as $cvv){
                if(isset($charge_type_arr[$cvv])){
                    $chargeNanmeArr[]=$charge_type_arr[$cvv];
                }
            }
            $chargeNanmeStr=implode(',',$chargeNanmeArr);
        }
        return $chargeNanmeStr;
    }
    /**
     * 添加收费项目
     * @author: liukezhu
     * @date : 2021/6/11
     * @param $param
     * @return int|string
     */
    public function add($param){
	    $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $dbHouseNewChargeNumber=$this->HouseNewChargeNumber->getColumn(['id'=>$param['subject_id'],'status'=>1],'charge_type');
        if(empty($dbHouseNewChargeNumber)){
            throw new \think\Exception("该收费科目不存在");
        }
        if(!in_array($dbHouseNewChargeNumber[0],['water','electric','gas']) && empty($param['type'])){
            throw new \think\Exception("请选择收费模式");
        }
        if (in_array($dbHouseNewChargeNumber[0],['qrcode','electricity_loss'])) {
            // 	收费模式 1:一次性费用
            $param['type'] = 1;
        }
        $param['add_time']=time();
		
	    $queuData = [
		    'logData' => [
			    'tbname' => '新版收费-收费项目表',
			    'table'  => 'house_new_charge_project',
			    'client' => '小区后台',
			    'trigger_path' => '收费管理->收费项目管理',
			    'trigger_type' => $this->getAddLogName(),
			    'addtime'      => time(),
			    'village_id'   => $param['village_id'],
			    'op_id'        => $parseInfo['op_id'],
			    'op_type'      => $parseInfo['op_type'],
			    'op_name'      => $parseInfo['op_name'],
		    ],
		    'newData' => $param,
		    'oldData' => []
	    ];
		$this->laterLogInQueue($queuData);

        return  $this->HouseNewChargeProject->addFind($param);
    }

    /**
     * 编辑收费项目
     * @author: liukezhu
     * @date : 2021/6/11
     * @param $param
     * @param bool $id
     * @return bool
     */
    public function edit($param,$id=false){
        $list= $this->HouseNewChargeProject->getOne([
            'id'=>$param['id'],
            'village_id'=>$param['village_id']
        ]);
        if(!$list){
            return false;
        }
		$parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $list=$list->toArray();
	    $listCopy = $list;
        if($id){
            $data=array(
                'name'=>$param['name'],
                'img'=>$param['img'],
                'status'=>$param['status'],
                'refund_period'=>$param['refund_period'],
                'update_time'=>time(),
            );
            $list=$this->HouseNewChargeProject->editFind(['id'=>$id,'village_id'=>$param['village_id']],$data);

	        $queuData = [
		        'logData' => [
			        'tbname' => '新版收费-收费项目表',
			        'table'  => 'house_new_charge_project',
			        'client' => '小区后台',
			        'trigger_path' => '收费管理->收费项目管理',
			        'trigger_type' => $this->getUpdateNmae(),
			        'addtime'      => time(),
			        'village_id'   => $param['village_id'],
			        'op_id'   => $parseInfo['op_id'],
			        'op_type'   => $parseInfo['op_type'],
			        'op_name'   => $parseInfo['op_name'],
		        ],
		        'newData' => $data,
		        'oldData' => $listCopy
	        ];
	        $this->laterLogInQueue($queuData);

        }else{
            $subject=$this->HouseNewChargeNumber->getColumn(['id'=>$list['subject_id']],'charge_number_name')[0];
            $list=array(
                'id'=>$list['id'],
                'name'=>$list['name'],
                'img'=>replace_file_domain($list['img']),
                'type'=>$list['type'],
                'refund_period'=>$list['refund_period'],
                'status'=>$list['status'],
                'subject_name'=>$subject,
                'typeStatus'=>(empty($list['type']) ? false : true)
            );
        }
        return $list;
    }
    public function getChargeRuleList($where=array(),$field='*',$order='id desc'){

        $ruleList=$this->HouseNewChargeRule->getList($where,$field,$order);
        if($ruleList && !$ruleList->isEmpty()){
            $ruleList=$ruleList->toArray();
            return $ruleList;
        }
        return array();
    }

    /**
     * 删除收费项目
     * @author: zhubaodi
     * @date : 2021/11/22
     * @param $param
     * @param bool $id
     */
    public function del($param,$id=false){
        $list= $this->HouseNewChargeProject->getOne([
            'id'=>$param['id'],
            'village_id'=>$param['village_id']
        ]);
        if(!$list){
            throw new \think\Exception("该收费项目不存在，无法删除");
        }
        $where=array();
        $where[] = ['village_id', '=', $param['village_id']];
        $where[] = ['status', 'in', array(1,2)];
        $where[] = ['charge_project_id', '=', $param['id']];
        $rule_list = $this->HouseNewChargeRule->getList($where, 'id,charge_name', 'id desc');
	    $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $res=0;
        if (empty($rule_list) || $rule_list->isEmpty()){
            $data=array(
                'status'=>4,
                'update_time'=>time(),
            );
            $res=$this->HouseNewChargeProject->editFind(['id'=>$param['id'],'village_id'=>$param['village_id']],$data);

	        $queuData = [
		        'logData' => [
			        'tbname' => '新版收费-收费项目表',
			        'table'  => 'house_new_charge_project',
			        'client' => '小区后台',
			        'trigger_path' => '收费管理->收费项目管理',
			        'trigger_type' => $this->getDeleteName(),
			        'addtime'      => time(),
			        'village_id'   => $param['village_id'],
			        'op_id'   => $parseInfo['op_id'],
			        'op_type'   => $parseInfo['op_type'],
			        'op_name'   => $parseInfo['op_name'],
		        ],
		        'newData' => $data,
		        'oldData' => $list
	        ];
	        $this->laterLogInQueue($queuData);

        }else{
            $rule_list=$rule_list->toArray();
            if (empty($rule_list)){
                $data=array(
                    'status'=>4,
                    'update_time'=>time(),
                );
                $res=$this->HouseNewChargeProject->editFind(['id'=>$param['id'],'village_id'=>$param['village_id']],$data);
	            $queuData = [
		            'logData' => [
			            'tbname' => '新版收费-收费项目表',
			            'table'  => 'house_new_charge_project',
			            'client' => '小区后台',
			            'trigger_path' => '收费管理->收费项目管理',
			            'trigger_type' => $this->getDeleteName(),
			            'addtime'      => time(),
			            'village_id'   => $param['village_id'],
			            'op_id'   => $parseInfo['op_id'],
			            'op_type'   => $parseInfo['op_type'],
			            'op_name'   => $parseInfo['op_name'],
		            ],
		            'newData' => $data,
		            'oldData' => $list
	            ];
	            $this->laterLogInQueue($queuData);
            }else{

                throw new \think\Exception('该收费项目现在已关联（'.count($rule_list).'）条收费标准，请到【收费标准管理】里先删除收费标准”！！');
            }
        }

        return $res;
    }


    /**
     * 编辑收费设置
     * @author:zhubaodi
     * @date_time: 2021/6/16 14:27
     */
    public function editChargeSet($data){

        /*if (empty($data['refund_term'])&&$data['call_date']&&$data['call_type']){
            throw new \think\Exception("请先填写相关参数，"); // 目前不知道当时出于什么原因考虑，不允许填写空 暂时注释
        }*/
	    $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $db_charge=new HouseNewCharge();
        $chargeInfo=$db_charge->get_one(['village_id'=>$data['village_id']]);
        if (empty($chargeInfo)){
            $chargeData=[
                'village_id'=>$data['village_id'],
                'refund_term'=>$data['refund_term'],
                'call_date'=>$data['call_date'],
                'call_type'=>$data['call_type'],
                'is_combine'=>$data['is_combine'],
                'add_time'=>time(),
                'update_time'=>time(),
            ];
            if(isset($data['wids'])){
                $chargeData['wids']=$data['wids'];
            }
            if(isset($data['can_modify_zero'])){
                $chargeData['can_modify_zero']=$data['can_modify_zero'];
            }
            if(isset($data['order_type_cutday'])){
                $chargeData['order_type_cutday']=$data['order_type_cutday'];
            }
            $charge_id=$db_charge->addOne($chargeData);

	        $queuData = [
		        'logData' => [
			        'tbname' => '物业新版收费管理设置表',
			        'table'  => 'house_new_charge',
			        'client' => '小区后台',
			        'trigger_path' => '收费管理->收费设置',
			        'trigger_type' => $this->getAddLogName(),
			        'addtime'      => time(),
			        'village_id'   => $data['village_id'],
			        'op_id'   => $parseInfo['op_id'],
			        'op_type'   => $parseInfo['op_type'],
			        'op_name'   => $parseInfo['op_name'],
		        ],
		        'newData' => $chargeData,
		        'oldData' => []
	        ];



        }else{
            $chargeData=[
                'village_id'=>$data['village_id'],
                'refund_term'=>$data['refund_term'],
                'call_date'=>$data['call_date'],
                'call_type'=>$data['call_type'],
                'is_combine'=>$data['is_combine'],
                'add_time'=>time(),
                'update_time'=>time(),
            ];
            if(isset($data['wids'])){
                $chargeData['wids']=$data['wids'];
            }
            if(isset($data['can_modify_zero'])){
                $chargeData['can_modify_zero']=$data['can_modify_zero'];
            }
            if(isset($data['order_type_cutday'])){
                $chargeData['order_type_cutday']=$data['order_type_cutday'];
            }
            $charge_id=$db_charge->save_one(['id'=>$chargeInfo['id']],$chargeData);
	        $queuData = [
		        'logData' => [
			        'tbname' => '物业新版收费管理设置表',
			        'table'  => 'house_new_charge',
			        'client' => '小区后台',
			        'trigger_path' => '收费管理->收费设置',
			        'trigger_type' => $this->getUpdateNmae(),
			        'addtime'      => time(),
			        'village_id'   => $data['village_id'],
			        'op_id'   => $parseInfo['op_id'],
			        'op_type'   => $parseInfo['op_type'],
			        'op_name'   => $parseInfo['op_name'],
		        ],
		        'newData' => $chargeData,
		        'oldData' => $chargeInfo
	        ];
        }
		$this->laterLogInQueue($queuData);
        return $charge_id;

    }
    /**
     * 查询收费设置
     * @author:zhubaodi
     * @date_time: 2021/6/16 14:27
     */
    public function getChargeSetInfo($village_id){
        $db_charge=new HouseNewCharge();
        $chargeInfoObj=$db_charge->get_one(['village_id'=>$village_id]);
        $chargeInfo=array();
        if($chargeInfoObj && !$chargeInfoObj->isEmpty()){
            $chargeInfo=$chargeInfoObj->toArray();
            if(isset($chargeInfo) && !empty($chargeInfo['wids'])){
                $chargeInfo['wids']=json_decode($chargeInfo['wids'],1);
            }else{
                $chargeInfo['wids']=array();
            }
        }
        return $chargeInfo;
    }

    /**
     * 收费项目信息
     * @author lijie
     * @date_time 2021/11/23
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getProjectInfo($where=[],$field=true)
    {
        $data = $this->HouseNewChargeProject->getOne($where,$field);
        return $data;
    }

    /**
     * 获取一条指定条件的收费项目
     * @param $where
     * @param string $field
     * @return array|\think\Model
     * @author cc
     */
    public function getOneChargeProject($where,$field= '*')
    {
        return  $this->HouseNewChargeProject->getOne($where,$field);
    }

    /**
     * 假删除收费项目
     * @return array|\think\Model
     * @author cc
     */
    public function deleteChargeProject($where)
    {
        $data['status'] = 4;
        $data['update_time'] = time();
        return  $this->HouseNewChargeProject->editFind($where,$data);
    }

}