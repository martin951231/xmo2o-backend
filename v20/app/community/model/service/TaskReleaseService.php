<?php
/**
 * @author : liukezhu
 * @date : 2022/4/24
 */
namespace app\community\model\service;

use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Area;
use app\community\model\db\AreaStreet;
use app\community\model\db\AreaStreetCommunityCare;
use app\community\model\db\AreaStreetConfig;
use app\community\model\db\AreaStreetEpidemicPreventRecord;
use app\community\model\db\AreaStreetEpidemicPreventSeries;
use app\community\model\db\AreaStreetEpidemicPreventType;
use app\community\model\db\AreaStreetMeetingLessonCategory;
use app\community\model\db\AreaStreetNews;
use app\community\model\db\AreaStreetPartyBranch;
use app\community\model\db\AreaStreetPartyBuild;
use app\community\model\db\AreaStreetPartyBuildCategory;
use app\community\model\db\AreaStreetTaskRelease;
use app\community\model\db\AreaStreetTaskReleaseBind;
use app\community\model\db\AreaStreetTaskReleaseNotice;
use app\community\model\db\AreaStreetTaskReleaseRecord;
use app\community\model\db\AreaStreetWorkers;
use app\community\model\db\AreaStreetWorkersOrder;
use app\community\model\db\HouseCameraDevice;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageGridMember;
use app\community\model\db\AreaStreetOrganization;
use app\community\model\db\HouseVillageUserLabel;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserBindAttribute;
use app\community\model\db\HouseVillageLabelCat;
use app\community\model\db\PartyActivity;
use app\community\model\db\User;
use app\community\model\db\HouseDeviceChannel;
use app\community\model\db\WisdomQrcodePositionRecord;
use app\community\model\db\HouseVillageLabel;
use app\community\model\db\HouseVillageUserLabelBind;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\HouseCityRegionManageService;
use think\facade\Db;
use think\facade\Cache;
use customization\customization;

class TaskReleaseService{

    use customization;

    protected $time;
    protected $AreaStreet;
    protected $AreaStreetTaskRelease;
    protected $AreaStreetTaskReleaseBind;
    protected $AreaStreetTaskReleaseRecord;
    protected $communityCare;
    protected $educateLevel;
    protected $marriage;
    protected $age;
    protected $AreaStreetCommunityCare;
    protected $AreaStreetEpidemicPreventSeries;
    protected $AreaStreetEpidemicPreventType;
    protected $AreaStreetEpidemicPreventRecord;
    protected $HouseVillageGridMember;
    protected $HouseProgrammeService;
    protected $AreaStreetOrganization;
    protected $AreaStreetWorkers;
    protected $HouseVillageGridService;
    protected $AreaStreetService;
    protected $AreaStreetPartyBranch;
    protected $PartyBranchService;
    protected $AreaStreetMeetingLessonCategory;
    protected $MeetingLessonService;
    protected $AreaStreetPartyBuild;
    protected $AreaStreetPartyBuildCategory;
    protected $AreaStreetNews;
    protected $AreaStreetWorkersOrder;
    protected $PartyActivity;
    protected $HouseVillage;
    protected $HouseVillageUserBind;
    protected $HouseVillageUserLabel;
    protected $HouseVillageUserLabelService;
    protected $HouseVillageUserBindAttribute;
    protected $AreaStreetTaskReleaseNotice;
    protected $TemplateNewsService;
    protected $User;
    protected $SmartQrCodeService;
    protected $WisdomQrcodePositionRecord;

    protected $HouseCameraDevice;
    protected $taskTypeList=[
        ['key'=>1,'value'=>'街道任务'],
        ['key'=>2,'value'=>'社区任务']
    ];
    protected $taskTypeAll=[
        1=>'街道任务',
        2=>'社区任务'
    ];

    public function __construct(){
        $this->time=time();
        $this->communityCare=[
            ['key'=>1,'value'=>'商家店铺'],
            ['key'=>2,'value'=>'学校'],
            ['key'=>3,'value'=>'医院'],
            ['key'=>4,'value'=>'银行'],
            ['key'=>5,'value'=>'其它']
        ];
        $this->educateLevel=[
            ['key'=>'初中及以下','value'=>'junior'],
            ['key'=>'高中或中专','value'=>'senior'],
            ['key'=>'大专','value'=>'junior_college'],
            ['key'=>'本科','value'=>'undergraduate'],
            ['key'=>'硕士','value'=>'master'],
            ['key'=>'博士','value'=>'doctor']
        ];
        $this->marriage=[
            ['key'=>'未婚','value'=>1,'color'=>'RGBA(140, 211, 246, 1)'],
            ['key'=>'已婚','value'=>2,'color'=>'RGBA(21, 240, 225, 1)'],
            ['key'=>'离婚','value'=>3,'color'=>'RGBA(229, 215, 84, 1)'],
            ['key'=>'丧偶','value'=>4,'color'=>'RGBA(68, 103, 227, .5)']
        ];
        $this->age=[
            ['key'=>'0-18岁','value'=>[0,18]],
            ['key'=>'19-30岁','value'=>[19,30]],
            ['key'=>'31-50岁','value'=>[31,50]],
            ['key'=>'51-65岁','value'=>[51,65]],
            ['key'=>'65岁以上','value'=>[66,500]]
        ];
        $this->nation_arr=[
            ['key'=>'汉族','value'=>'hanzu'],['key'=>'满族','value'=>'manzu'],
            ['key'=>'蒙古族','value'=>'mengguzu'], ['key'=>'回族','value'=>'huizu'], 
            ['key'=>'藏族','value'=>'zangzu'], ['key'=>'维吾尔族','value'=>'weiwuerzu'], 
            ['key'=>'苗族','value'=>'miaozu'], ['key'=>'彝族','value'=>'yizu'], 
            ['key'=>'壮族','value'=>'zhuangzu'], ['key'=>'布依族','value'=>'buyizu'], 
            ['key'=>'侗族','value'=>'dongzu'], ['key'=>'瑶族','value'=>'yaozu'], 
            ['key'=>'白族','value'=>'baizu'], ['key'=>'土家族','value'=>'tujiazu'], 
            ['key'=>'哈尼族','value'=>'hanizu'], ['key'=>'哈萨克族','value'=>'hasakezu'], 
            ['key'=>'傣族','value'=>'daizu'], ['key'=>'黎族','value'=>'lizu'], 
            ['key'=>'傈僳族','value'=>'lisuzu'], ['key'=>'佤族','value'=>'wazu'],
            ['key'=>'畲族','value'=>'shezu'], ['key'=>'高山族','value'=>'gaoshanzu'],
            ['key'=>'拉祜族','value'=>'lahuzu'], ['key'=>'水族','value'=>'shuizu'], 
            ['key'=>'东乡族','value'=>'dongxiangzu'], ['key'=>'纳西族','value'=>'naxizu'], 
            ['key'=>'景颇族','value'=>'jingpozu'], ['key'=>'柯尔克孜族','value'=>'keerkezizu'], 
            ['key'=>'土族','value'=>'tuzu'], ['key'=>'达斡尔族','value'=>'dawoerzu'], 
            ['key'=>'仫佬族','value'=>'mulaozu'], ['key'=>'羌族','value'=>'qiangzu'],
            ['key'=>'布朗族','value'=>'bulangzu'], ['key'=>'撒拉族','value'=>'salazu'], 
            ['key'=>'毛南族','value'=>'maonanzu'], ['key'=>'仡佬族','value'=>'gelaozu'], 
            ['key'=>'锡伯族','value'=>'xibozu'], ['key'=>'阿昌族','value'=>'achangzu'], 
            ['key'=>'普米族','value'=>'pumizu'], ['key'=>'朝鲜族','value'=>'chaoxianzu'],
            ['key'=>'塔吉克族','value'=>'tajikezu'], ['key'=>'怒族','value'=>'nuzu'],
            ['key'=>'乌孜别克族','value'=>'wuzibiekezu'], ['key'=>'俄罗斯族','value'=>'eluosizu'],
            ['key'=>'鄂温克族','value'=>'ewenkezu'], ['key'=>'德昂族','value'=>'deangzu'], 
            ['key'=>'保安族','value'=>'baoanzu'], ['key'=>'裕固族','value'=>'yuguzu'], 
            ['key'=>'京族','value'=>'jingzu'], ['key'=>'塔塔尔族','value'=>'tataerzu'],
            ['key'=>'独龙族','value'=>'dulongzu'], ['key'=>'鄂伦春族','value'=>'elunchunzu'], 
            ['key'=>'赫哲族','value'=>'hezhezu'], ['key'=>'门巴族','value'=>'menbazu'],
            ['key'=>'珞巴族','value'=>'luobazu'], ['key'=>'基诺族','value'=>'jinuozu'],
        ];
        $this->AreaStreet=new AreaStreet();
        $this->AreaStreetTaskRelease=new AreaStreetTaskRelease();
        $this->AreaStreetTaskReleaseBind=new AreaStreetTaskReleaseBind();
        $this->AreaStreetTaskReleaseRecord=new AreaStreetTaskReleaseRecord();
        $this->AreaStreetCommunityCare=new AreaStreetCommunityCare();
        $this->AreaStreetEpidemicPreventSeries=new AreaStreetEpidemicPreventSeries();
        $this->AreaStreetEpidemicPreventType=new AreaStreetEpidemicPreventType();
        $this->AreaStreetEpidemicPreventRecord=new AreaStreetEpidemicPreventRecord();
        $this->HouseProgrammeService=new HouseProgrammeService();
        $this->HouseVillageGridMember=new HouseVillageGridMember();
        $this->AreaStreetOrganization=new AreaStreetOrganization();
        $this->AreaStreetWorkers=new AreaStreetWorkers();
        $this->HouseVillageGridService=new HouseVillageGridService();
        $this->AreaStreetService=new AreaStreetService();
        $this->AreaStreetPartyBranch=new AreaStreetPartyBranch();
        $this->PartyBranchService=new PartyBranchService();
        $this->AreaStreetMeetingLessonCategory=new AreaStreetMeetingLessonCategory();
        $this->MeetingLessonService=new MeetingLessonService();
        $this->AreaStreetPartyBuild=new AreaStreetPartyBuild();
        $this->AreaStreetPartyBuildCategory=new AreaStreetPartyBuildCategory();
        $this->AreaStreetNews=new AreaStreetNews();
        $this->AreaStreetWorkersOrder=new AreaStreetWorkersOrder();
        $this->PartyActivity=new PartyActivity();
        $this->HouseVillage=new HouseVillage();
        $this->HouseVillageUserBind=new HouseVillageUserBind();
        $this->HouseVillageUserLabel=new HouseVillageUserLabel();
        $this->HouseVillageUserLabelService=new HouseVillageUserLabelService();
        $this->HouseVillageUserBindAttribute=new HouseVillageUserBindAttribute();
        $this->AreaStreetTaskReleaseNotice=new AreaStreetTaskReleaseNotice();
        $this->TemplateNewsService=new TemplateNewsService();
        $this->User=new User();
        $this->SmartQrCodeService=new SmartQrCodeService();
        $this->WisdomQrcodePositionRecord=new WisdomQrcodePositionRecord();
    }

    //todo 用户同步写入属性表记录
    public function userAttributeRecord($pigcms_id,$field){
        if ($field && is_string($field)) {
            $field = unserialize($field);
        }
        if(empty($field) || !is_array($field)){
            return false;
        }
        $data=[];
        if(isset($field['sex']['value'])){ //性别
            $data['sex']=($field['sex']['value'] == '男') ? 1 : 2;
        }
        if(isset($field['birthday']['value'])){ //年龄
            $birthday=intval(substr($field['birthday']['value'], 0, 4));
            $data['age']= ($birthday > 0) ? date('Y') - $birthday : 0;
        }
        $data['nation']='';
        if(isset($field['nation']) && !empty($field['nation']['value'])){ //民族
            $nationStr=$field['nation']['value'];
            $nationStr=trim($nationStr);
            $data['nation']=$nationStr;
            foreach ($this->nation_arr as $nv){
                if($nv['key'] == $nationStr){
                    $data['nation']=$nv['value'];
                    break;
                }
            }
        }
        if(isset($field['edcation']) && !empty($field['edcation']['value'])){ //教育水平
            $educate_level='';
            $edcationStr=$field['edcation']['value'];
            $edcationStr=trim($edcationStr);
            foreach ($this->educateLevel as $v){
                if($v['key'] ==$edcationStr){
                    $educate_level=$v['value'];
                    break;
                }
            }
            $data['educate_level']=$educate_level;
        }
        if(isset($field['marriage_status']['value'])){ //婚姻状况
            $marriage=0;
            foreach ($this->marriage as $v){
                if($v['key'] == $field['marriage_status']['value']){
                    $marriage=$v['value'];
                    break;
                }
            }
            $data['marriage']=$marriage;
        }
        fdump_api(['$data=='.__LINE__,$pigcms_id,$field,$data],'task/attribute',1);
        if(empty($data)){
            return true;
        }
        $rr=$this->HouseVillageUserBindAttribute->getFinds([['a.pigcms_id','=',$pigcms_id]],'a.*,b.street_id as sid,b.community_id as cid');
        if($rr && !$rr->isEmpty()){
            $rr=$rr->toArray();
            $data['street_id']=$rr['sid'];
            $data['community_id']=$rr['cid'];
            fdump_api(['$rr=='.__LINE__,$rr,$data],'task/attribute',1);
            $this->HouseVillageUserBindAttribute->saveOne([['id','=',$rr['id']]],$data);
        }
        else{
            $user=$this->HouseVillageUserBind->getVillageUserBind([
                ['a.pigcms_id','=',$pigcms_id]
            ],'a.pigcms_id,a.village_id,a.uid,v.street_id,v.community_id');
            fdump_api(['$user=='.__LINE__,$user],'task/attribute',1);
            if($user){
                $data['street_id']=intval($user['street_id']);
                $data['community_id']=intval($user['community_id']);
                $data['village_id']=$user['village_id'];
                $data['uid']=intval($user['uid']);
                $data['pigcms_id']=$user['pigcms_id'];
                $data['add_time']=$this->time;
                $this->HouseVillageUserBindAttribute->addFind($data);
            }
        }
        return true;
    }

    //todo 批量处理 用户同步写入属性表记录
    public function userAttributeRecordBatch(){
        $list=$this->HouseVillageUserBind->getLimitUserList([
            ['status','=',1],
            ['authentication_field','<>','']
        ],0,'pigcms_id,authentication_field');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as $v){
                $this->userAttributeRecord($v['pigcms_id'],$v['authentication_field']);
            }
        }
        return true;
    }

    //todo 返回任务表头数据
    public function getTaskReleaseListColumns($area_type,$type){
        $data[]=['title'=>'任务名称','dataIndex'=>'title','key'=>'title'];
        if($area_type == 0){
            $data[]=['title'=>'任务类型','dataIndex'=>'type','key'=>'type'];
        }
        $data[]=['title'=>'完成时间','dataIndex'=>'complete_time','key'=>'complete_time','width'=>'12%'];
        $data[]=['title'=>'完成数量','dataIndex'=>'complete_num','key'=>'complete_num','width'=>'8%'];
        $data[]=['title'=>'任务内容','dataIndex'=>'content','key'=>'content'];
        if(($area_type == 0) || ($area_type ==1 && $type == 1)){
            $data[]=['title'=>'分配社区/人员','dataIndex'=>'work_list','key'=>'work_list','scopedSlots'=>['customRender'=>'tags']];
            $data[]=['title'=>'创建时间','dataIndex'=>'add_time','key'=>'add_time','width'=>'12%'];
            $data[]=['title'=>'完成情况','dataIndex'=>'complete_qk','key'=>'complete_qk','width'=>'8%','scopedSlots'=>['customRender'=>'complete_qk']];
        }


        $data[]=['title'=>'操作','dataIndex'=>'','key'=>'action','width'=>'10%','scopedSlots'=>['customRender'=>'action']];
        return $data;
    }

    /**
     * 获取任务下达列表
     * @author: liukezhu
     * @date : 2022/5/25
     * @param $street_id
     * @param $community_id
     * @param $where
     * @param $page
     * @param int $limit
     * @return mixed
     */
    public function getTaskReleaseList($street_id,$community_id,$where,$page,$limit=10){
        $where[]=['t.del_time','=',0];
        if(isset($where['_string'])){
            $where[] = $where['_string'];
            unset($where['_string']);
        }else{
            $where[]=['t.street_id','=',$street_id];
            $where[]=['t.community_id','=',$community_id];
        }
        $prefix = config('database.connections.mysql.prefix');
        $field='t.id,t.title,t.type,t.complete_time,t.complete_num,t.content,t.add_time,t.ids,(SELECT group_concat( w.work_name )  FROM '.$prefix.'area_street_task_release_bind AS b LEFT JOIN '.$prefix.'area_street_workers AS w ON w.worker_id = b.worker_id WHERE b.task_id = t.id  ) AS work_list ';
        $order='t.id DESC';
        $list = $this->AreaStreetTaskRelease->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                $v['complete_time']=date('Y-m-d',$v['complete_time']);
                $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                if($v['type'] == 2 && empty($community_id) && !empty($v['ids'])){
                    $v['work_list']=$this->AreaStreet->getColumn([
                        ['area_id','in',(explode(',',$v['ids']))],
                        ['area_type','=',1]
                    ],'area_name');
                    $v['work_color']='#1890ff';
                    $v['status_button']=false;
                }else{
                    $v['work_list']=explode(',',$v['work_list']);
                    $v['work_color']='#FCBE79';
                    $v['status_button']=true;
                }
                $v['type']=isset($this->taskTypeAll[$v['type']]) ? $this->taskTypeAll[$v['type']] : '--';
            }
            unset($v);
        }
        $count = $this->AreaStreetTaskRelease->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 查询单条任务数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @param bool $field
     * @return mixed
     * @throws \think\Exception
     */
    public function getTaskRelease($where,$field=true,$type=0){
        if(!$type){
            $data = $this->AreaStreetTaskRelease->getOne($where,$field);
        }else{
            $data = $this->AreaStreetTaskRelease->getOneOr($where,$field);
        }
        if (!$data || $data->isEmpty()){
            throw new \think\Exception("数据不存在");
        }
        return $data->toArray();
    }

    //todo 查询任务类型
    public function getTaskReleaseType($area_type,$street_id,$community_id){
        $list=$this->taskTypeList;
        return  ['list'=>$list,'typeStatus'=>($area_type == 1 ? false : true)];
    }

    /**
     * 获取任务数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function getTaskReleaseOne($where){
        $info=$this->getTaskRelease($where,'t.id,t.title,t.complete_time,t.complete_num,t.content,t.type,t.street_id,t.community_id,t.ids',1);
        if($info['community_id'] > 0){
            $info['type']=0;
        }
        $info['complete_time']=date('Y-m-d',$info['complete_time']);
        $info['wid_all']=$this->getTaskBindWorker($info);
        unset($info['street_id'],$info['community_id']);
        return $info;
    }

    //todo 获取任务绑定的人员
    public function getTaskBindWorker($param){
        $data=$list=[];
        if(intval($param['type']) == 2){
            $org=$this->HouseProgrammeService->org_s;
            if($param['ids']){
                $list=explode(',',$param['ids']);
            }
        }else{
            $org=$this->HouseProgrammeService->org_u;
            $list=$this->AreaStreetTaskReleaseBind->getList([
                ['task_id','=',$param['id']],
            ],'street_id,community_id,worker_id');
            if($list && !$list->isEmpty()){
                $list=$list->toArray();
            }
        }
        foreach ($list as $v){
            if(intval($param['type']) == 2){
                $data[]=$param['street_id'].$org.$v.$org.'0';
            }
            else{
                $data[]=$v['street_id'].$org.$v['community_id'].$org.$v['worker_id'];
            }
        }
        return $data;
    }

    //todo 校验分配人员参数
    public function checkTaskRelease($param){
        if(!isset($param['wid_all']) || empty($param['wid_all'])){
            throw new \think\Exception("请选择分配");
        }
        $org_s=$this->HouseProgrammeService->org_s;
        $org_u=$this->HouseProgrammeService->org_u;
        $data=[];
        foreach ($param['wid_all'] as $v){
            $org=[];
            if(strpos($v, $org_u) !== false){
                $org=explode($org_u,$v);
            }elseif (strpos($v, $org_s) !== false){
                $org=explode($org_s,$v);
            }
            if(is_array($org) && !empty($org)){
                if(intval($param['type']) == 2){
                    $data[]=$org[1];
                }else{
                    $data[]=[
                        'street_id'=>$org[0],
                        'community_id'=>$org[1],
                        'worker_id'=>$org[2],
                    ];
                }

            }
        }
        if(empty($data)){
            throw new \think\Exception("操作失败，所选分配的数据不合法");
        }
        return $data;
    }

    //todo 任务绑定人员
    public function taskBindWorker($type,$street_id,$community_id,$task_id,$wid=[],$param){
        if(isset($param['type']) && intval($param['type']) == 2){
            return true;
        }
        $worker_id=$widColumnArr=[];
        if($wid){
            $widColumnArr=array_column($wid, 'worker_id');
        }
        if(intval($type) == 2){ //来源编辑
            $worker_id=$this->AreaStreetTaskReleaseBind->getColumn([['task_id','=',$task_id]],'worker_id');
            if($worker_id && $widColumnArr){
                $dd=[];
                foreach ($worker_id as $v){
                    if(!in_array($v,$widColumnArr)){
                        $this->AreaStreetTaskReleaseBind->delFind([
                            ['task_id','=',$task_id],
                            ['worker_id','=',$v]
                        ]);
                        $dd[]=$v;
                    }
                }
                if($dd){
                    $this->taskReleaseSendNotice(2,$street_id,$community_id,$task_id,$dd,$param);
                }
            }
        }
        if(!empty($wid)){
            $data=[];
            $dd=[];
            foreach ($wid as $v){
                if(intval($type) == 2 && $worker_id){
                    if(in_array($v['worker_id'],$worker_id)){
                        continue;
                    }
                }
                $data[]=[
                    'street_id'=>$v['street_id'],
                    'community_id'=>$v['community_id'],
                    'task_id'=>$task_id,
                    'worker_id'=>$v['worker_id'],
                    'add_time'=>date('Y-m-d H:i:s',$this->time)
                ];
                $dd[]=$v['worker_id'];
            }
            $this->AreaStreetTaskReleaseBind->addAll($data);
            $this->taskReleaseSendNotice(1,$street_id,$community_id,$task_id,$dd,$param);
        }
        return true;
    }

    /**
     * 发送通知
     * @author: liukezhu
     * @date : 2022/5/10
     * @param $type  1：绑定 2：解绑
     * @param $street_id 街道id
     * @param $community_id 社区id
     * @param $task_id 任务ud
     * @param $wid 工作人员id集合
     * @param $param 任务相关参数
     * @return bool
     */
    public function taskReleaseSendNotice($type,$street_id,$community_id,$task_id,$wid,$param){
        if(empty($wid)){
            return true;
        }
        if(intval($type) == 1){ //绑定
            $href=cfg('site_url').'/packapp/community/pages/Community/networkManage/taskDetail?task_id='.$task_id;
            $first='您有新的任务待处理';
            $remark='请点击查看详细信息！';
        }else{//解绑
            $href='';
            $first='您的任务已被管理员关闭';
            $remark='';
        }
        $list=$this->AreaStreetWorkers->getAll([['worker_id', 'in', $wid],['work_phone','<>','']],'worker_id,work_phone');
        if (!$list || $list->isEmpty()){
            return true;
        }
        foreach ($list as $v){
            $info=$this->AreaStreetTaskReleaseNotice->getOne([
                ['task_id','=',$task_id],
                ['worker_id', '=', $v['worker_id']],
                ['type', '=', $type]
            ],'id');
            if($info && !$info->isEmpty()){
                continue;
            }
            $user=$this->User->getOne([['phone','=',$v['work_phone']],['openid','<>','']],'uid,openid');
            if(!$user || $user->isEmpty()){
                continue;
            }
            $arr=[
                'href' => $href,
                'wecha_id' => $user['openid'],
                'first' => $first,
                'keyword1' => $param['title'],
                'keyword2' => '已发送',
                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                'remark' => $remark
            ];
            $rr=$this->TemplateNewsService->sendTempMsg('TM0001801', $arr);
            $this->AreaStreetTaskReleaseNotice->addFind([
                'street_id'=>$street_id,
                'community_id'=>$community_id,
                'task_id'=>$task_id,
                'worker_id'=>$v['worker_id'],
                'type'=>$type,
                'info'=>json_encode([$rr,$arr],JSON_UNESCAPED_UNICODE),
                'add_time'=>date('Y-m-d H:i:s',$this->time)
            ]);
        }
        return true;
    }

    //todo 解绑人员
    public function taskUnboundWorker($where){
        $info= $this->HouseVillageGridService->getGridCustomDetail($where);
        if($info && !$info->isEmpty()){
            $this->AreaStreetTaskReleaseBind->delFind([
                ['street_id','=',$info['area_id']],
                ['worker_id','=',$info['workers_id']]
            ]);
        }
        return true;
    }

    /**
     * 添加任务
     * @author: liukezhu
     * @date : 2022/4/28
     * @param $param
     * @return int|string
     * @throws \think\Exception
     */
    public function taskReleaseAdd($param){
        $wid_all=$this->checkTaskRelease($param);
        $param['add_time']=$this->time;
        $param['del_time']=0;
        unset($param['wid_all']);
        if(intval($param['type']) == 2){
            $param['ids']=implode(',',$wid_all);
        }
        $id= $this->AreaStreetTaskRelease->addFind($param);
        $this->taskBindWorker(1,$param['street_id'],$param['community_id'],$id,$wid_all,$param);
        return $id;
    }

    /**
     * 编辑任务
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function taskReleaseEdit($where,$param){
        $info=$this->getTaskRelease($where,'t.id,t.street_id,t.community_id,t.type');
        if(empty($info['community_id']) && empty($param['type'])){
            throw new \think\Exception("请选择任务类型");
        }
        $wid_all=$this->checkTaskRelease($param);
        $param['update_time']=$this->time;
        if(intval($param['type']) == 2){
            $param['ids']=implode(',',$wid_all);
        }
        unset($param['wid_all'],$param['type']);
        $id= $this->AreaStreetTaskRelease->editFind($where,$param);
        $param['type']=$info['type'];
        $this->taskBindWorker(2,$info['street_id'],$info['community_id'],$info['id'],$wid_all,$param);
        return $id;
    }

    /**
     * 删除任务
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function taskReleaseDel($where){
        $info=$this->getTaskRelease($where,'t.id');
        $param['del_time']=$this->time;
        $param['update_time']=$this->time;
        $rr= $this->AreaStreetTaskRelease->editFind($where,$param);
        if($rr){
            $this->AreaStreetTaskReleaseBind->delFind([['task_id','=',$info['id']]]);
        }
        return $rr;
    }

    /**
     * 任务记录列表
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getTaskReleaseRecordList($where,$field=true,$page=0,$limit=10,$order='r.id DESC'){
        $list = $this->AreaStreetTaskReleaseRecord->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                if(isset($v['complete_time']) && !empty($v['complete_time'])){
                    $v['complete_time']=date('Y-m-d',$v['complete_time']);
                }
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
                if(isset($v['status']) ){
                    $v['status']=(intval($v['status']) == 1) ? '已完成' : '未完成';
                }
                if (isset($v['img']) && !empty($v['img']) && @unserialize($v['img'])) {
                    $img = unserialize($v['img']);
                    foreach ($img as &$vv){
                        $vv=replace_file_domain($vv);
                    }
                    $v['img']=$img;
                }
            }
            unset($v);
        }
        $count = $this->AreaStreetTaskReleaseRecord->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 获取社区关怀参数
     * @author: liukezhu
     * @date : 2022/5/11
     * @param int $type
     * @return array
     */
    public function getCommunityCareType($type=0){
        $list= $this->communityCare;
        if(!empty($type)){
            foreach ($list as $v){
                if($v['key'] == $type){
                    $list=$v['value'];
                    break;
                }
            }
        }
        return $list;
    }

    /**
     * 获取社区关怀列表
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getCommunityCareList($where,$field=true,$page=0,$limit=10,$order='id DESC'){
        $list = $this->AreaStreetCommunityCare->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
                $v['type']=$this->getCommunityCareType($v['type']);
            }
            unset($v);
        }
        $count = $this->AreaStreetCommunityCare->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 添加社区关怀
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $param
     * @return int|string
     */
    public function communityCareAdd($param){
        $param['add_time']=$this->time;
        $param['del_time']=0;
        return $this->AreaStreetCommunityCare->addFind($param);
    }

    /**
     *  查询单条任务数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @param bool $field
     * @return mixed
     * @throws \think\Exception
     */
    public function getCommunityCare($where,$field=true){
        $data = $this->AreaStreetCommunityCare->getOne($where,$field);
        if (!$data || $data->isEmpty()){
            throw new \think\Exception("数据不存在");
        }
        return $data->toArray();
    }

    /**
     * 获取社区关怀数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function communityCareOne($where){
        $info=$this->getCommunityCare($where,'id,type,num,remarks');
        return $info;
    }

    /**
     * 编辑社区关怀数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function communityCarEdit($where,$param){
        $this->getCommunityCare($where,'id');
        $param['update_time']=$this->time;
        return $this->AreaStreetCommunityCare->editFind($where,$param);
    }

    /**
     * 删除社区关怀
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function communityCarDel($where){
        $info=$this->getCommunityCare($where,'id');
        $param['del_time']=$this->time;
        $param['update_time']=$this->time;
        return $this->AreaStreetCommunityCare->editFind($where,$param);
    }

    /**
     * 疫情防控--系列列表
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getEpidemicPreventSeriesList($where,$field=true,$page=0,$limit=10,$order='sort desc,id desc'){
        $list = $this->AreaStreetEpidemicPreventSeries->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
            }
            unset($v);
        }
        $count = $this->AreaStreetEpidemicPreventSeries->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 疫情防控--添加系列
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $param
     * @return int|string
     */
    public function epidemicPreventSeriesAdd($param){
        if($param['status']){
            $this->checkEpidemicPreventSeries($param['street_id'],$param['community_id'],0);
        }
        $param['add_time']=$this->time;
        $param['del_time']=0;
        return $this->AreaStreetEpidemicPreventSeries->addFind($param);
    }

    //todo 校验系列状态开启的仅可添加五个
    public function checkEpidemicPreventSeries($street_id,$community_id,$id= 0){
        $where=[
            ['street_id','=',$street_id],
            ['community_id','=',$community_id],
            ['status','=',1],
            ['del_time','=',0],
        ];
        if($id > 0){
            $where[]=['id','<>',$id];
        }
        $cc=$this->AreaStreetEpidemicPreventSeries->getCount($where);
        $num=5;
        if($cc >= $num){
            throw new \think\Exception('当前系列状态仅可开启数量为'.$num.'个！请把状态选择关闭即可继续操作');
        }
        return true;
    }


    /**
     * 查询单条系列数据
     * @author: liukezhu
     * @date : 2022/5/7
     * @param $where
     * @param bool $field
     * @param bool $type
     * @return array
     * @throws \think\Exception
     */
    public function getEpidemicPreventSeries($where,$field=true,$type=true){
        $data = $this->AreaStreetEpidemicPreventSeries->getOne($where,$field);
        if (!$data || $data->isEmpty()){
            if($type){
                throw new \think\Exception("数据不存在");
            }else{
                return [];
            }
        }
        return $data->toArray();
    }

    /**
     * 获取系列数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventSeriesOne($where){
        $info=$this->getEpidemicPreventSeries($where,'id,title,status,sort');
        return $info;
    }

    /**
     * 编辑系列
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventSeriesEdit($where,$param){
        $rr= $this->getEpidemicPreventSeries($where,'id,street_id,community_id,status');
        if($param['status']){
            $this->checkEpidemicPreventSeries($rr['street_id'],$rr['community_id'],$rr['id']);
        }
        $param['update_time']=$this->time;
        return $this->AreaStreetEpidemicPreventSeries->editFind($where,$param);
    }

    /**
     * 删除系列
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventSeriesDel($where){
        $info=$this->getEpidemicPreventSeries($where,'id');
        $param['del_time']=$this->time;
        $param['update_time']=$this->time;
        return $this->AreaStreetEpidemicPreventSeries->editFind($where,$param);
    }

    /**
     * 获取类型列表
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getEpidemicPreventTypeList($where,$field=true,$page=0,$limit=10,$order='sort desc,id desc'){
        $list = $this->AreaStreetEpidemicPreventType->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
            }
            unset($v);
        }
        $count = $this->AreaStreetEpidemicPreventType->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 添加类型
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $param
     * @return int|string
     */
    public function epidemicPreventTypeAdd($param){
        $param['add_time']=$this->time;
        $param['del_time']=0;
        return $this->AreaStreetEpidemicPreventType->addFind($param);
    }

    /**
     * 查询单条类型数据
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param bool $field
     * @return mixed
     * @throws \think\Exception
     */
    public function getEpidemicPreventType($where,$field=true,$type=true){
        $data = $this->AreaStreetEpidemicPreventType->getOne($where,$field);
        if (!$data || $data->isEmpty()){
            if($type){
                throw new \think\Exception("数据不存在");
            }else{
                return [];
            }
        }
        return $data->toArray();
    }

    /**
     * 获取系列数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventTypeOne($where){
        $info=$this->getEpidemicPreventType($where,'id,title,status,sort');
        return $info;
    }

    /**
     * 编辑类型
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventTypeEdit($where,$param){
        $this->getEpidemicPreventType($where,'id');
        $param['update_time']=$this->time;
        return $this->AreaStreetEpidemicPreventType->editFind($where,$param);
    }

    /**
     * 删除类型
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventTypeDel($where){
        $info=$this->getEpidemicPreventType($where,'id');
        $param['del_time']=$this->time;
        $param['update_time']=$this->time;
        return $this->AreaStreetEpidemicPreventType->editFind($where,$param);
    }

    /**
     * 获取类型参数
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $street_id
     * @param $community_id
     * @return array
     */
    public function getEpidemicPreventParam($street_id,$community_id){
        $field='id,title';
        $where[]=['street_id','=',$street_id];
        $where[]=['community_id','=',$community_id];
        $where[]=['status','=',1];
        $where[]=['del_time','=',0];
        $order='sort desc,id desc';
        $series_list = $this->AreaStreetEpidemicPreventSeries->getList($where,$field,$order);
        $type_list = $this->AreaStreetEpidemicPreventType->getList($where,$field,$order);
        return ['series_list'=>$series_list,'type_list'=>$type_list];
    }

    /**
     * 获取疫情防控 记录列表
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getEpidemicPreventRecordList($where,$field=true,$page=0,$limit=10,$order='r.id desc'){
        $list = $this->AreaStreetEpidemicPreventRecord->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                if(isset($v['add_time']) && !empty($v['add_time'])){
                    $v['add_time']=date('Y-m-d H:i:s',$v['add_time']);
                }
            }
            unset($v);
        }
        $count = $this->AreaStreetEpidemicPreventRecord->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     *添加记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $param
     * @return int|string
     */
    public function epidemicPreventRecordAdd($param){
        $param['add_time']=$this->time;
        $param['del_time']=0;
        return $this->AreaStreetEpidemicPreventRecord->addFind($param);
    }

    /**
     *查询单条记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param bool $field
     * @return mixed
     * @throws \think\Exception
     *
     */
    public function getEpidemicPreventRecord($where,$field=true){
        $data = $this->AreaStreetEpidemicPreventRecord->getOne($where,$field);
        if (!$data || $data->isEmpty()){
            throw new \think\Exception("数据不存在");
        }
        return $data->toArray();
    }

    /**
     * 获取记录数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventRecordOne($where){
        $info=$this->getEpidemicPreventRecord($where,'id,series_id,type_id,num,remarks');
        $series_id=$this->getEpidemicPreventSeries([
            ['id','=',$info['series_id']],
            ['status','=',1],
            ['del_time','=',0]
        ],'id',false);
        if(!$series_id){
            $info['series_id']=0;
        }
        $type_id=$this->getEpidemicPreventType([
            ['id','=',$info['type_id']],
            ['status','=',1],
            ['del_time','=',0]
        ],'id',false);
        if(!$type_id){
            $info['type_id']=0;
        }
        return $info;
    }

    /**
     * 编辑记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventRecordEdit($where,$param){
        $this->getEpidemicPreventRecord($where,'id');
        $param['update_time']=$this->time;
        return $this->AreaStreetEpidemicPreventRecord->editFind($where,$param);
    }

    /**
     * 删除记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @param $where
     * @return mixed
     * @throws \think\Exception
     */
    public function epidemicPreventRecordDel($where){
        $info=$this->getEpidemicPreventRecord($where,'id');
        $param['del_time']=$this->time;
        $param['update_time']=$this->time;
        return $this->AreaStreetEpidemicPreventRecord->editFind($where,$param);
    }

    /**
     * 网格员绑定社区 查询社区和组织部门
     * @author: liukezhu
     * @date : 2022/4/27
     * @param $street_id
     * @param $community_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStreetCommunityTissueNav($street_id,$community_id){
        $key=[];
        $communityList=$this->getAreaStreetMethod($street_id,$community_id);
        if(!$communityList){
            return ['key'=>[],'data'=>[]];
        }
        $orgList=$this->AreaStreetOrganization->getSelectList([
            ['area_id','=',$street_id],
            ['is_del','=',0],
            ['area_type','=',0]
        ],'id,fid,name as title','sort desc');
        if($orgList && !$orgList->isEmpty()){
            $orgList=$orgList->toArray();
            foreach ($orgList as &$v){
                $v['key']=$this->HouseProgrammeService->org_b.$v['id'];
                $v['title']=$v['title'].'【部门】';
                $key[$v['key']]=$v['title'];
            }
            unset($v);
        }
        foreach ($communityList as &$v){
            $v['key']=$this->HouseProgrammeService->org_s.$v['id'];
            $v['title']=$v['title'].'【'.($v['type'] == 1 ? '社区' : '街道').'】';
            $key_all[]=$v['key'];
            if(!$v['type']){
                $v['disabled']=true;
            }
            $key[$v['key']]=$v['title'];
        }
        unset($v);
        if($orgList){
            $list[]=$communityList[0];
            unset($communityList[0]);
            $new_arr = (new OrganizationStreetService())->getTree($orgList);
            if($communityList){
                $llist=array_merge($new_arr,$communityList);
            }else{
                $llist=$new_arr;
            }
            $list[0]['children'] = $llist;
        }else{
            $list=$communityList;
        }
        return ['key'=>$key,'data'=>$list];
    }

    /**
     * 街道/社区返回组织人员数据
     * @author: liukezhu
     * @date : 2022/5/24
     * @param $type  1：街道任务  2：社区任务
     * @param $area_type
     * @param $street_id
     * @param $community_id
     * @param int $source todo $source == 1 针对街道、社区工单指派人员模块调用
     * @return array
     */
    public function getTaskReleaseTissueNav($type,$area_type,$street_id,$community_id,$source=0){
        $where=$list=[];
        $org_s=$this->HouseProgrammeService->org_s;
        $org_b=$this->HouseProgrammeService->org_b;
        $org_u=$this->HouseProgrammeService->org_u;
        if(intval($area_type) == 1){  // 社区
            $where[]=['g.area_id','=',$street_id];
            $where[]=['g.business_type','=',2];
            $where[]=['g.business_id','=',$community_id];
            $where[]=['w.work_status','=',1];
            $field='w.worker_id as id,w.work_name as title,w.work_phone';
            $order='g.id desc';
            $list = $this->HouseVillageGridMember->getLists($where,$field,$order);
            if($list && !$list->isEmpty()){
                $list=$list->toArray();
                foreach ($list as &$v){
                    if($source == 1){
                        $v['id']=$v['key']= $street_id.$org_u.$community_id.$org_u.$v['id'].$org_u.$v['title'];
                    }else{
                        $v['id']= $street_id.$org_u.$community_id.$org_u.$v['id'];
                    }
                    $v['title']=$v['title'].'/'.$v['work_phone'];
                }
                unset($v);
                if($source == 1){
                    $community_info= $this->AreaStreet->getOne([
                        ['area_id','=',$community_id],
                        ['area_type','=',1]
                    ],'area_id,area_pid,area_name');
                    if($community_info && !$community_info->isEmpty()){
                        $community_info=$community_info->toArray();
                    }
                    $list1=$list;
                    $list=[];
                    $list[0]=[
                        'id'=>$community_info['area_id'],
                        'key'=>$street_id.$org_u.$community_id.$org_u.'0',
                        'title'=>$community_info['area_name'],
                        'work_phone'=>'',
                        'children'=>$list1
                    ];
                }
            }
        }
        else{ //街道
            $attribution=[];
            $areaStreetList=$this->getAreaStreetMethod($street_id,$community_id,'area_id as id ,area_pid as fid,area_name as title,area_type');
            if($areaStreetList){
                foreach ($areaStreetList as &$v){
                    if(intval($type) == 1){
                        $disabled=true;
                        if(intval($v['area_type']) == 1){
                            $key=$v['fid'].$org_u.$v['id'].$org_u.'0';
                        }else{
                            $key=$v['fid'].$org_u.$v['id'].$org_u.'0';
                        }
                    }else{
                        $disabled=false;
                        if(intval($v['area_type']) == 0){
                            $disabled=true;
                        }
                        $key=$v['fid'].$org_s.$v['id'].$org_s.'0';
                    }
                    if($source == 1){
                        $key.=$org_u.$v['title'];
                    }
                    $v['disabled']=$disabled;
                    $v['key']=$key;
                }
                unset($v);
                if(intval($type) ==2){ //查询街道下所有社区
                    $list = (new OrganizationStreetService())->getTree($areaStreetList);
                }
                else{ //查询街道部门和社区
                    $list=$areaStreetList;
                    //查询街道组织架构
                    $orgList=$this->AreaStreetOrganization->getSelectList([
                        ['area_id','=',$street_id],
                        ['is_del','=',0],
                        ['area_type','=',0]
                    ],'id,fid,name as title','sort desc');
                    if($orgList && !$orgList->isEmpty()){
                        $orgList=$orgList->toArray();
                    }
                    $where[]=['g.area_id','=',$street_id];
                    $where[]=['g.business_type','in',[1,2]];
                    $where[]=['w.work_status','=',1];
                    $field='w.worker_id as id,w.work_name as title,w.work_phone,g.business_id as org_id,g.business_type as type';
                    $order='g.id desc';
                    //查询指向到部门的人员
                    $workers = $this->HouseVillageGridMember->getLists($where,$field,$order);
                    if($workers && !$workers->isEmpty()){
                        $workers = $workers->toArray();
                    }
                    $worker2=[];
                    if($orgList){
                        foreach ($orgList as &$v1){
                            if($workers){
                                foreach ($workers as &$v2){
                                    if(intval($v2['type']) == 2){
                                        $worker2[]=$v2;
                                        continue;
                                    }
                                    $org_id=[];
                                    $v2['key']= $street_id.$org_u.'0'.$org_u.$v2['id'];
                                    if($source == 1){
                                        $v2['key'].=$org_u.$v2['title'];
                                    }
                                    if(empty($v2['org_id'])){
                                        unset($v2['org_id']);
                                        $attribution[$v2['key']]=$v2;
                                    }
                                    else{
                                        if(strpos($v2['org_id'], ',') !== false){
                                            $org_id=explode(',',$v2['org_id']);
                                        }else{
                                            $org_id[]=$v2['org_id'];
                                        }
                                        if($org_id && in_array($v1['id'],$org_id)){
                                            $v1['children'][]=$v2;
                                        }
                                    }
                                }
                            }
                            $v1['key']= '0'.$org_b.$v1['id'];
                            $v1['disabled']=true;
                        }
                        unset($v1);
                    }
                    else{
                        if($workers){
                            foreach ($workers as &$v2){
                                if(intval($v2['type']) == 2){
                                    $worker2[]=$v2;
                                    continue;
                                }
                                $v2['key']= $street_id.$org_u.'0'.$org_u.$v2['id'];
                                if($source == 1){
                                    $v2['key'].=$org_u.$v2['title'];
                                }
                                if(empty($v2['org_id'])){
                                    unset($v2['org_id']);
                                    $attribution[$v2['key']]=$v2;
                                }
                            }
                        }
                    }
                    if($worker2){
                        foreach ($list as &$v){
                            if($v['area_type'] != 1){
                                continue;
                            }
                            $children=[];
                            foreach ($worker2 as $vv){
                                if($v['id'] == $vv['org_id']){
                                    $vv['key']= $v['fid'].$org_u.$v['id'].$org_u.$vv['id'];
                                    if($source == 1){
                                        $vv['key'].=$org_u.$vv['title'];
                                    }
                                    $children[$vv['id']]=$vv;
                                }
                            }
                            if($children){
                                $v['children']=array_values($children);
                            }
                        }
                    }
                    unset($v);
                    $new_arr = (new OrganizationStreetService())->getTree($orgList);
                    if($attribution){
                        $attribution=array_values($attribution);
                        if($new_arr){
                            $new_arr=array_merge($new_arr,$attribution);
                        }else{
                            $new_arr=$attribution;
                        }
                    }
                    $list[0]['children'] = $new_arr;
                }
            }
        }
        return ['typeStatus'=>($area_type == 1 ? false : true),'list'=>$list];
    }

    //==================todo 街道/社区管委会可视化方法============================
    //todo 获取缓存key
    public function getKeyMethod($str){
        return md5($str);
    }

    //todo 读取缓存
    public function getCacheMethod($key){
        $data= Cache::get($key);
        if($data){
            $data=json_decode($data,true);
        }
        return $data;
    }

    //todo 设置缓存
    public function setCacheMethod($key,$data){
        return Cache::set($key,json_encode($data,JSON_UNESCAPED_UNICODE),1800);
    }


    /**
     * 获取可视化初始化数据
     * @author: liukezhu
     * @date : 2022/5/11
     * @param $adminUser
     * @param $street_id
     * @param $community_id
     * region_level   1社区 2街道 3区县 4城市
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCommitteeIndex($adminUser,$street_id,$community_id=0,$region_level=0){
        if($region_level==3 || $region_level==4){
            $houseCityRegionManageService= new HouseCityRegionManageService();
            $region_manage= $houseCityRegionManageService->getRegionManageInfo($adminUser);
            $data=[
                'visualize_title'=>$region_manage['visualization_title'],
                'visualize_nav1'=>$region_manage['navinfo']['nav1'],
                'visualize_nav2'=>$region_manage['navinfo']['nav2'],
                'visualize_nav3'=>$region_manage['navinfo']['nav3'],
                'visualize_nav4'=>$region_manage['navinfo']['nav4'],
            ];
            $data['position']=[
                'lng'=>114.527570,
                'lat'=>26.319529,
            ];

            $data['position']['zoomLevel']=10;
            if($region_level==3){
                $data['position']['zoomLevel']=12;
            }
            $data['position']['area_name']=$region_manage['region_name'];
            $data['position']['icon']=cfg('site_url').'/static/images/house/community_committee/dw-wd.png';
            $data['area_name']=$region_manage['region_name'];
            if(isset($adminUser['next_area_id']) && $adminUser['next_area_id']>0){
                $areaInfo=$houseCityRegionManageService->getOneAreaInfo(array('area_id'=>$adminUser['next_area_id']),'*');
                if($areaInfo && $areaInfo['area_name']){
                    if($region_level==3){
                        $data['position']['zoomLevel']=12;
                    }
                    $data['area_name']=$data['area_name'].' / '.$areaInfo['area_name'];
                    $data['position']['area_name']=$areaInfo['area_name'];
                    if($areaInfo['area_lat']>0){
                        $region_manage['lat']=$areaInfo['area_lat'];
                    }
                    if($areaInfo['area_lng']>0){
                        $region_manage['lng']=$areaInfo['area_lng'];
                    }
                }
            }
            if($region_manage['lat']>0 && $region_manage['lng']>0){
                $data['position']['lng']=floatval($region_manage['lng']);
                $data['position']['lat']=floatval($region_manage['lat']);
            }
            $data['city_area_name']=$data['area_name'];
        }elseif($region_level>0 && is_array($street_id) && isset($adminUser['comfrom_type']) && $adminUser['comfrom_type']=='cityRegionManage'){
            $tmp_street_id=$street_id['0'];
            $info=$this->AreaStreetService->getAreaStreet(['area_id'=>$tmp_street_id,'area_type'=>'0']);
            $data=$this->AreaStreetService->getAreaStreetVisualizeNav($info,1);
            if($info['long'] && $info['lat']){
                $data['position']['lng']=floatval($info['long']);
                $data['position']['lat']=floatval($info['lat']);
            }
            $data['position']['zoomLevel']=42;
            $data['area_name']=$info['area_name'];
            $data['position']['icon']=cfg('site_url').'/static/images/house/community_committee/dw-wd.png';
            if(isset($adminUser['city_area_name']) && !empty($adminUser['city_area_name']) && strpos($adminUser['city_area_name'],' / ')===false && isset($adminUser['login_region_level']) && $adminUser['login_region_level']==4 && isset($adminUser['next_area_id']) && $adminUser['next_area_id']>0){
                $houseCityRegionManageService= new HouseCityRegionManageService();
                $areaInfo=$houseCityRegionManageService->getOneAreaInfo(array('area_id'=>$adminUser['next_area_id']),'*');
                $adminUser['city_area_name']=$adminUser['city_area_name'].' / '.$areaInfo['area_name'];
            }
            if(isset($adminUser['city_area_name']) && !empty($adminUser['city_area_name'])){
                $data['city_area_name']=$adminUser['city_area_name'];
                $data['area_name']=$adminUser['city_area_name'].' / '.$data['area_name'];
            }
            if($community_id>0){
                $communityinfo=$this->AreaStreetService->getAreaStreet(['area_id'=>$community_id,'area_type'=>'1']);
                if($communityinfo){
                    $data['area_name']=$data['area_name'].' / '.$communityinfo['area_name'];
                    if($communityinfo['long'] && $communityinfo['lat']){
                        $data['position']['lng']=floatval($communityinfo['long']);
                        $data['position']['lat']=floatval($communityinfo['lat']);
                    }
                }
            }
        }else{
            $data=$this->AreaStreetService->getAreaStreetVisualizeNav($adminUser,1);
            $data['street_name']='';
            $data['street_community_name']='';
            if($adminUser['area_type'] == 0){
                $data['street_name']=$adminUser['area_name'];
                $data['street_community_name']=$adminUser['area_name'];
            }
            if($community_id>0){
                $communityinfo=$this->AreaStreetService->getAreaStreet(['area_id'=>$community_id,'area_type'=>'1']);
                if($communityinfo){
                    if($data['street_community_name']){
                        $data['street_community_name']=$data['street_community_name'].' / '.$communityinfo['area_name'];
                    }else{
                        $data['street_community_name']=$communityinfo['area_name'];
                    }
                    if($communityinfo['long'] && $communityinfo['lat']){
                        $data['position']['lng']=floatval($communityinfo['long']);
                        $data['position']['lat']=floatval($communityinfo['lat']);
                    }
                }
            }
        }
        if($this->hasSuiChuan()){
            $position_trail_list=[];
        }else{
            $position_trail_list=$this->getWisdomQrcodePositionRecord($street_id,$community_id);
        }
        $data['work_list']=$this->getWisdomQrcodePositionMethod($street_id,$community_id);
        $data['partybranch_list']=$this->getPartyBranchPositionMethod($street_id,$community_id);
        $data['position_trail_list']=$position_trail_list;
        $data['work_position_list']=$this->getWorkPosition($street_id,$community_id);

        $configCustomizationService = new ConfigCustomizationService();
        $vr720yunCustomized = $configCustomizationService->getVr720yunCustomized();
        if ($vr720yunCustomized) {
            $dbAreaStreetConfig = new AreaStreetConfig();
            $whereConfig = [];
            if ($community_id > 0) {
                $whereConfig[] = ['area_id', '=', $community_id];
            } else {
                $whereConfig[] = ['area_id', '=', $street_id];
            }
            $vr720yunConfig = $dbAreaStreetConfig->getFind($whereConfig, 'area_id, vr_720yun_switch, vr_720yun_url');
            if (empty($vr720yunConfig)) {
                $vr720yunConfig = [
                    'area_id' => 0, 'vr_720yun_switch' => 0, 'vr_720yun_url' => '',
                ];
            }
            $data['vr720yunConfig'] = $vr720yunConfig;
        }
        return $data;
    }

    //todo 人员轨迹点
    public function getWorkPosition($street_id,$community_id){
        $prefix = config('database.connections.mysql.prefix');
        $village_id=$this->getVillageIdMethod($street_id,$community_id);
        $list=[];
        if($village_id){
            $start_time=strtotime(date('Y-m-d 00:00:00'));
            $end_time=strtotime(date('Y-m-d 23:59:59'));
            $sql='SELECT
                    * 
                FROM
                    (
                    SELECT
                        a.wid,
                        a.id,
                        `b`.`name`,
                        `b`.`phone`,
                        `a`.`long`,
                        `a`.`lat` 
                    FROM
                        `'.$prefix.'wisdom_qrcode_position_record` `a`
                        LEFT JOIN `'.$prefix.'house_worker` `b` ON `b`.`wid` = `a`.`wid` 
                    WHERE
                        `a`.`village_id` IN ( '.(implode(',',$village_id)).' ) 
                        AND `a`.`del_time` = 0 
                        AND `add_time` BETWEEN '.$start_time.' 
                        AND '.$end_time.' 
                    ORDER BY
                        a.id DESC 
                    ) a 
                GROUP BY
                    `a`.`wid` LIMIT 0,100';
            $data=$this->WisdomQrcodePositionRecord->query_sql($sql);
            if($data){
                foreach ($data as $k2=>$v2){
                    $list[]=[
                        'name' => $v2['name'],
                        'phone' => $v2['phone'],
                        'lng'=> (float)$v2['long'],
                        'lat' => (float)$v2['lat'],
                        'icon' => cfg('site_url').'/static/images/house/community_committee/dw-xjy.png'
                    ];
                }
            }
        }
        return $list;
    }

    //todo 巡检记录
    public function getWisdomQrcodePositionRecord($street_id,$community_id){
        $village_id=$this->getVillageIdMethod($street_id,$community_id);
        $list=[];
        if($village_id){
            $where=[
                ['i.village_id','in',$village_id],
            ];
            $list=$this->SmartQrCodeService->getPositionRecord(2,$where,'id asc',1,100);
        }
        return $list;
    }

    /**
     * 展示最新上报的三条工单数据
     * @author: liukezhu
     * @date : 2022/5/6
     * @param $street_id
     * @param $community_id
     * @return mixed
     */
    public function getAreaStreetWorkersOrder($street_id,$community_id){
        $area_id=$street_id;
        if($community_id > 0){
            $area_id=$community_id;
        }
        $whereArr=[
            ['event_status','<>',0],
            ['del_time','=',0],
        ];
        if(is_array($area_id)){
            $whereArr[]=array('area_id','in',$area_id);
        }else{
            $whereArr[]=array('area_id','=',$area_id);
        }
        $list=$this->AreaStreetWorkersOrder->getList($whereArr,'order_id,order_address,order_content','order_id desc',1,3);
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
        }
        foreach ($list as &$v){
            if(!empty($v['order_content'])){
                $title=htmlspecialchars_decode($v['order_content']);
            }elseif ($v['order_address']){
                $title=$v['order_address'];
            }else{
                $title='';
            }
            $v['title']=$title;
            unset($v['order_address'],$v['order_content']);
        }
        unset($v);
        return ['list'=>$list,'url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/gridCustom/GridEventCenter'];
    }

    //todo 获取街道下社区数据
    public function getAreaStreetMethod($street_id,$community_id,$field='area_id as id,area_pid as fid,area_name as title,area_type as type',$order='area_type asc,area_id asc'){
        $where='(is_open = 1 and area_id = '.$street_id.') or (is_open = 1 and area_pid= '.$street_id.')';
        $list=$this->AreaStreet->getLists($where,$field,0,10,$order);
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
        }else{
            $list=[];
        }
        return $list;
    }

    //todo 获取village_id集合
    public function getVillageIdMethod($street_id,$community_id){
        $where[] = ['status','=',1];
        if($community_id > 0){//社区
            $where[]=['community_id','=',$community_id];
        }else{//街道
            if(is_array($street_id)){
                $where[] = ['street_id','in',$street_id];
            }else{
                $where[] = ['street_id','=',$street_id];
            }
        }
        return $this->HouseVillage->getColumn($where,'village_id');
    }

    //todo 获取巡检人员经纬度
    public function getWisdomQrcodePositionMethod($street_id,$community_id){
        $whereMember=[
            ['w.is_del','=',0],
            ['w.status','<>',4],
            ['w.wid','>',0],
        ];
        if($community_id > 0){//社区
            $whereMember[] = ['g.business_type','=',2];
            $whereMember[] = ['g.business_id','=',$community_id];
        }
        else{//街道
            $whereMember[] = ['g.business_type','=',1];
            if(is_array($street_id)){
                $whereMember[] = ['g.area_id','in',$street_id];
            }else{
                $whereMember[] = ['g.area_id','=',$street_id];
            }
        }
        $work=[];
        $village_id=$this->getVillageIdMethod($street_id,$community_id);
        if($village_id){
            
            $whereMember[] = ['p.status','=',1];
            $whereMember[] = ['w.village_id','in',$village_id];
            $whereMember[] = ['q.long','<>',''];
            $work=$this->HouseVillageGridMember->getGridMember($whereMember,'g.id,w.wid,w.village_id,g.name,g.phone,q.long as lng,q.lat');
            if($work && !$work->isEmpty()){
                $work=$work->toArray();
                foreach ($work as &$v){
                    $v['lng']=(float)$v['lng'];
                    $v['lat']=(float)$v['lat'];
                    $v['icon']=cfg('site_url').'/static/images/house/community_committee/wgy.png';
                }
                unset($v);
            }
        }
        return $work;
    }

    //todo 查询党支部经纬度
    public function getPartyBranchPositionMethod($street_id,$community_id){

        $whereArr=[
            ['status','=',0],
            ['long','<>','']
        ];
        if(is_array($street_id)){
            $whereArr[]=['street_id','in',$street_id];
        }else{
            $whereArr[]=['street_id','=',$street_id];
        }
        $list=$this->AreaStreetPartyBranch->getList($whereArr,'id,name,`long` AS lng,lat,type,adress','id desc');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            $type=$this->PartyBranchService->PartyBranchTypeSing;
            foreach ($list as &$v){
                $v['lng']=(float)$v['lng'];
                $v['lat']=(float)$v['lat'];
                $v['type']=!empty($v['type']) ? $type[$v['type']] : '--';
                $v['icon']=cfg('site_url').'/static/images/house/community_committee/dw-dzb.png';
            }
            unset($v);
        }else{
            $list=[];
        }
        return $list;
    }

    //todo 党组织架构统计
    /***
     ** region_level   1社区 2街道 3区县 4城市 
     */
    public function getPartyBranchMethod($street_id,$community_id=0){
        $data=[];
        $whereArr=[
            ['status','=',0],
            ['type', '>', '0'],
        ];
        if(is_array($street_id)){
            $whereArr[]=array('street_id','in',$street_id);
        }else{
            $whereArr[]=array('street_id','=',$street_id);
        }
        $list=$this->AreaStreetPartyBranch->getListByGroup($whereArr,'type','id,type,count(*) as count');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
        }
        $type= $this->PartyBranchService->PartyBranchType;
        foreach ($type as $v){
            $num=0;
            if($list){
                foreach ($list as $v2){
                    if($v2['type'] == $v['key']){
                        $num=$v2['count'];
                        break;
                    }
                }
            }
            $data[]=[
                'title'=>$v['value'],
                'count'=>$num,
                'src'=>cfg('site_url').$v['src']
            ];
        }
        return ['list'=>$data, 'url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/partyWork/PartyWorkList'];
    }

    //todo 三会一课
    public function getMeetingLessonCategoryMethod($street_id,$community_id=0){
        $data=[];
        $area_id=$street_id;
        if($community_id > 0){
            $area_id=$community_id;
        }
        $whereArr=[
            ['c.cat_status','<>',-1],
            ['c.type', '>', '0'],
            ['t.status','=',1],
        ];
        if(is_array($area_id)){
            $whereArr[]=array('c.area_id','in',$area_id);
        }else{
            $whereArr[]=array('c.area_id','=',$area_id);
        }
        $list=$this->AreaStreetMeetingLessonCategory->getListByGroup($whereArr,'c.type','t.meeting_id,c.type,count(*) as count');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
        }
        $type= $this->MeetingLessonService->PartyBranchType;
        foreach ($type as $v){
            $num=0;
            if($list){
                foreach ($list as $v2){
                    if($v2['type'] == $v['key']){
                        $num=$v2['count'];
                        break;
                    }
                }
            }
            $data[]=[
                'name'=>$v['value'],
                'value'=>$num,
                'itemStyle'=>[
                    'opacity'=>1,
                    'color'=>$v['color']
                ]
            ];
        }
        return ['list'=>$data,'url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/ThreeLessons/ThreeLessonsList'];
    }

    //todo 党内咨询
    public function getPartyBuildMethod($street_id,$community_id){
        $area_id=$street_id;
        if($community_id > 0){
            $area_id=$community_id;
        }
        $whereArr=[
            ['c.cat_status','=',1],
            ['t.status','=',1],
        ];
        if(is_array($area_id)){
            $whereArr[]=array('c.area_id','in',$area_id);
        }else{
            $whereArr[]=array('c.area_id','=',$area_id);
        }
        $list=$this->AreaStreetPartyBuildCategory->getListByGroup($whereArr,'t.cat_id','count desc','c.cat_id,c.cat_name as title,count(*) as count',5);
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as $k=>&$v){
                $v['cat_id']=$k+1;
            }
            unset($v);
        }
        return ['list'=>$list,'url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/PartyBuild/CategoryList'];
    }

    //todo 热点新闻
    public function getStreetNewsMethod($street_id,$community_id){
        $area_id=$street_id;
        if($community_id > 0){
            $area_id=$community_id;
        }
        $whereArr=[
            ['status','=',1],
        ];
        if(is_array($area_id)){
            $whereArr[]=array('area_id','in',$area_id);
        }else{
            $whereArr[]=array('area_id','=',$area_id);
        }
        $list=$this->AreaStreetNews->getLists($whereArr,'news_id,title,title_img as img',1,5);
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            foreach ($list as &$v){
                $v['img']=!empty($v['img']) ? replace_file_domain($v['img']) : '';
            }
            unset($v);
        }
        return ['list'=>$list,'url'=>cfg('site_url').'/v20/public/platform/#/community/street_community.iframe/news_street_index'];
    }

    //todo 人口信息统计
    public function getPersonStatisticsMethod($street_id,$community_id,$village_ids=array()){
        $total_count=$register_count=$tenant_count=0;
        $where[] = ['status','=',1];
        if($community_id > 0){//社区
            $where[]=['community_id','=',$community_id];
        }else{//街道
            if(is_array($street_id)){
                $where[] = ['street_id','in',$street_id];
            }else{
                $where[] = ['street_id','=',$street_id];
            }
        }
        if(!empty($village_ids) && is_array($village_ids)){
            $where[] = ['village_id','in',$village_ids];
        }
        $village_id=$this->HouseVillage->getColumn($where,'village_id');
        if($village_id){
            //总人数
            $total_count=$this->HouseVillageUserBind->getVillageUserNum([
                ['village_id','in',$village_id],['status','=',1],['type','in',[0,1,2,3]]
            ]);
            //业主和家属
            $register_count=$this->HouseVillageUserBind->getVillageUserNum([
                ['village_id','in',$village_id],['status','=',1],['type','in',[0,1,3]]
            ]);
            //租客
            $tenant_count=$this->HouseVillageUserBind->getVillageUserNum([
                ['village_id','in',$village_id],['status','=',1],['type','=',2]
            ]);
        }
        return ['total_count'=>$total_count,'register_count'=>$register_count,'tenant_count'=>$tenant_count];
    }

    //todo 事件上报
    public function getEventMethod($street_id,$community_id){
        $area_id=$street_id;
        if($community_id > 0){
            $area_id=$community_id;
        }
        //已完成工单数
        $whereArr=[
            ['event_status','in',[4,6]],
        ];
        if(is_array($area_id)){
            $area_id_where=array('area_id','in',$area_id);
        }else{
            $area_id_where=array('area_id','=',$area_id);
        }
        $whereArr[]=$area_id_where;
        $t1=$this->AreaStreetWorkersOrder->getFindCount($whereArr);
        //未完成工单数
        $whereArr=[
            ['event_status','in',[1,2,3]],
        ];
        $whereArr[]=$area_id_where;
        $t2=$this->AreaStreetWorkersOrder->getFindCount($whereArr);
        //已完成任务数
        if(is_array($street_id)){
            $street_id_where=array('street_id','in',$street_id);
        }else{
            $street_id_where=array('street_id','=',$street_id);
        }
        $whereArr=[
            ['community_id','=',$community_id],
            ['status','=',1],
        ];
        $whereArr[]=$street_id_where;
        $t3 = $this->AreaStreetTaskReleaseRecord->getFindCount($whereArr);
        //未完成任务数
        $whereArr=[
            ['community_id','=',$community_id],
            ['status','=',0],
        ];
        $whereArr[]=$street_id_where;
        $t4 = $this->AreaStreetTaskReleaseRecord->getFindCount($whereArr);
        $data=[
            'order'=>[
              [
                  [
                      'name'=>'已完成',
                      'value'=>$t1,
                      'itemStyle'=>[
                          'opacity'=>1,
                          'color'=>'RGBA(61, 107, 217, .8)'
                      ],
                  ],
                  [
                      'name'=>'未完成',
                      'value'=>$t2,
                      'itemStyle'=>[
                          'opacity'=>1,
                          'color'=>'RGBA(0, 240, 240, .8)'
                      ],
                  ]
              ],2,'事件上报'
            ],
            'task'=>[
               [
                   [
                       'name'=>'已完成',
                       'value'=>$t3,
                       'itemStyle'=>[
                           'opacity'=>1,
                           'color'=>'RGBA(61, 107, 217, .8)'
                       ],
                   ],
                   [
                       'name'=>'未完成',
                       'value'=>$t4,
                       'itemStyle'=>[
                           'opacity'=>1,
                           'color'=>'RGBA(0, 240, 240, .8)'
                       ],
                   ]
               ],2,'任务下达'
            ],
            'order_url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/gridCustom/GridEventCenter',
            'task_url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/taskRelease/list'
        ];
        return $data;
    }

    //todo 社区关怀
    public function getCommunityCareMethod($street_id,$community_id){
        $data=[];
        $whereArr=[
            ['community_id','=',$community_id],
            ['del_time','=',0],
            ['type', '>', '0'],
        ];
        if(is_array($street_id)){
            $whereArr[]=array('street_id','in',$street_id);
        }else{
            $whereArr[]=array('street_id','=',$street_id);
        }
        $type= $this->communityCare;
        $list=$this->AreaStreetCommunityCare->getListByGroup($whereArr,'type','id,type,sum(num) as num');
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
        }
        foreach ($type as $v){
            $num=0;
            if($list){
                foreach ($list as $v2){
                    if($v2['type'] == $v['key']){
                        $num=$v2['num'];
                        break;
                    }
                }
            }
            $data['xAxis_data'][]=$v['value'];
            $data['series_data'][]=$num;
        }
        return ['list'=>$data,'url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/communityCare/list'];
    }

    //todo 社区物业统计
    public function getPropertyStatisticsMethod($street_id,$community_id){
        if($community_id > 0){
            $where[]=['a.community_id','=',$community_id];
        }else{
            if(is_array($street_id)){
                $where[]=['a.street_id','in',$street_id];
            }else{
                $where[]=['a.street_id','=',$street_id];
            }
            
        }
        $where[]=['a.status','=',1];
        $where1=$where;
        $where1[]=['b.is_virtual','=',0];
        //非虚拟物业
        $d1=$this->HouseVillage->getIsVirtualProperty($where1);
        $where2=$where;
        $where2[]=['b.is_virtual','=',1];
        //虚拟物业
        $d2=$this->HouseVillage->getIsVirtualProperty($where2);
        $isSuiChuan=$this->hasSuiChuan();
        $configCustomizationService=new ConfigCustomizationService();
        $have_property_virtual=$configCustomizationService->getHavePropertyVirtual();
        if($have_property_virtual>0){
            $isSuiChuan=1;
        }
        $data=[
            [
                [
                    'name'=>'有物业小区',
                    'value'=>$d1,
                    'itemStyle'=>[
                        'opacity'=>1,
                        'color'=>'RGBA(142, 215, 251, .8)'
                    ]
                ]
            ],2, '社区物业统计'
        ];

        if($isSuiChuan){
            $data['0'][]=[
                'name'=>'无物业的小区',
                'value'=>$d2,
                'itemStyle'=>[
                    'opacity'=>1,
                    'color'=>'RGBA(61, 107, 217, .8)'
                ]
            ];
        }
        return $data;
    }

    //todo 居民人口性质统计
    public function getUserLabelStatisticsMethod($street_id,$community_id,$village_ids=array()){
        if($community_id > 0){
            $where[] = ['v.community_id','=',$community_id];
        }else{
            if(is_array($street_id)){
                $where[] = ['v.street_id','in',$street_id];
            }else{
                $where[] = ['v.street_id','=',$street_id];
            }
            
        }
        $data=[
            [
                'key'   => 2,
                'title' => '残疾人',
                'count' => 0,
                'src'   => cfg('site_url').'/static/images/house/community_committee/icon_canjiren.png'
            ],
            [
                'key'   => 1,
                'title' => '低保人员',
                'count' => 0,
                'src'   => cfg('site_url').'/static/images/house/community_committee/icon_dibao.png'

            ],
            [
                'key'   => 3,
                'title' => '空巢老人',
                'count' => 0,
                'src'   => cfg('site_url').'/static/images/house/community_committee/icon_laoren.png'
            ],
            [
                'key'   => 7,
                'title' => '留守儿童',
                'count' => 0,
                'src'   => cfg('site_url').'/static/images/house/community_committee/icon_child.png'
            ]
        ];
        if(!empty($village_ids) && is_array($village_ids)){
            $where[]=['v.village_id','in',$village_ids];
        }
        $str=[];
        foreach ($data as $v){
            $str[]=' find_in_set("'.$v['key'].'", l.user_vulnerable_groups) ';
        }
        $where[] = ['', 'exp', Db::raw((implode('OR',$str)))];
        $list=$this->HouseVillageUserLabel->getStreetUserColumn($where,'l.user_vulnerable_groups');
        if($list){
            foreach ($data as &$v1){
                $n=0;
                foreach ($list as $v2){
                    $key=explode(',',$v2);
                    if(in_array($v1['key'],$key)){
                        $n++;
                    }
                }
                $v1['count']=$n;
                unset($v1['key']);
            }
            unset($v1);
        }
        return ['list'=>$data,'url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/specialGroup/userVulnerableGroupsLists'];
    }

    //todo 用户相关属性字段统计
    public function getUserBindAttributeMethod($street_id,$community_id,$where=[],$group,$order,$field=true){
        if ($community_id > 0) {
            $where[] = ['community_id', '=', $community_id];
        } else {
            if (is_array($street_id)) {
                $where[] = ['street_id', 'in', $street_id];
            } else {
                $where[] = ['street_id', '=', $street_id];
            }
        }
        $wherUserBind=[['status','=',4]];
        foreach ($where as $wvv){
            if($wvv['0']=='village_id'){
                $wherUserBind[]=$wvv;
                break;
            }
        }
        $pigcms_id=$this->HouseVillageUserBind->getOneColumn($wherUserBind,'pigcms_id');
        if($pigcms_id){
            $where[]=['pigcms_id','not in',$pigcms_id];
        }
        $cacheKey=$this->getKeyMethod(json_encode($where,JSON_UNESCAPED_UNICODE).$group.$order.$field);
        $cacheData=$this->getCacheMethod($cacheKey);
        if( $cacheData){
            $result= $cacheData;
        }else{
            $result=$this->HouseVillageUserBindAttribute->getListByGroup($where,$group,$order,$field);
            if($result && !$result->isEmpty()){
                $result=$result->toArray();
                $this->setCacheMethod($cacheKey,$result);
            }else{
                $result=[];
            }
        }
        return $result;
    }

    //todo 用户性别统计
    public function getUserSexStatisticsMethod($street_id,$community_id,$village_ids=array()){
        $where[]=['sex','>',0];
        if(!empty($village_ids) && is_array($village_ids)){
            $where[]=['village_id','in',$village_ids];
        }
        $list=$this->getUserBindAttributeMethod($street_id,$community_id,$where,'sex','sex asc','sex,count(*) AS count');
        $n0=$n1=0;
        if($list){
            foreach ($list as $v){
                if($v['sex'] == 1){
                    $n0=$v['count'];
                }else{
                    $n1=$v['count'];
                }
            }
        }
        $data=[
            [
                [
                    'name'=>'男',
                    'value'=>$n0,
                    'itemStyle'=>[
                        'opacity'=>1,
                        'color'=>'RGBA(21, 240, 225, .8)'
                    ]
                ],
                [
                    'name'=>'女',
                    'value'=>$n1,
                    'itemStyle'=>[
                        'opacity'=>1,
                        'color'=>'RGBA(140, 211, 246, 1)'
                    ]
                ]
            ], 2, '男女比例'
        ];
        return $data;
    }

    //todo 年龄段统计
    public function getUserAgeStatisticsMethod($street_id, $community_id, $village_ids = array())
    {
        $type = $this->age;
        $data = [
            'legend_data' => ['男性', '女性'],
            'xAxis_data' => array_column($type, 'key'),
            'series' => [],
        ];
        $where[] = ['sex', '>', 0];
        if (!empty($village_ids) && is_array($village_ids)) {
            $where[] = ['village_id', 'in', $village_ids];
        }
        $list = $this->getUserBindAttributeMethod($street_id, $community_id, $where, 'age,sex', 'id asc', 'sex,age,count(*) AS count');
        $sex1 = $sex2 = [];
        if ($list) {
            foreach ($type as $v) {
                $num1 = $num2 = 0;
                foreach ($list as $v2) {
                    if ($v2['sex'] == 1) {
                        if ($v2['age'] >= $v['value'][0] && $v2['age'] <= $v['value'][1]) {
                            $num1 += $v2['count'];
                        }
                    } elseif ($v2['sex'] == 2) {
                        if ($v2['age'] >= $v['value'][0] && $v2['age'] <= $v['value'][1]) {
                            $num2 += $v2['count'];
                        }
                    }

                }
                $sex1[] = $num1;
                $sex2[] = $num2;
            }
        }
        $data['series'] = [
            ['name' => '男', 'data' => $sex1],
            ['name' => '女', 'data' => $sex2],
        ];
        return $data;
    }

    public function getUserNationStatisticsMethod($street_id, $community_id, $village_ids = array())
    {

        $data = [
            'legend_data' => ['男性', '女性'],
            'xAxis_data' => '',
            'series' => [],
        ];
        $where = array();
        $where[] = ['sex', '>', 0];
        if (!empty($village_ids) && is_array($village_ids)) {
            $where[] = ['village_id', 'in', $village_ids];
        }
        $have_nation_arr = array();
        $all_nation_arr = array();
        foreach ($this->nation_arr as $nationV) {
            $all_nation_arr[$nationV['value']] = $nationV['key'];

        }
        $list = $this->getUserBindAttributeMethod($street_id, $community_id, $where, 'nation,sex', 'id asc', 'sex,nation,count(*) AS count');
        $sex1 = $sex2 = [];
        $xAxis_data = array();
        if (!empty($list)) {
            foreach ($list as $v2) {
                $nation = $v2['nation'] ? $v2['nation'] : 'wei_zhi_zu';
                if (!isset($have_nation_arr[$nation])) {
                    $nationStr = isset($all_nation_arr[$nation]) ? $all_nation_arr[$nation] : '未知';
                    $have_nation_arr[$nation] = $nationStr;
                }
            }
            ksort($have_nation_arr);

            foreach ($have_nation_arr as $knation => $vnation) {
                $xAxis_data[] = $vnation;
                $num1 = $num2 = 0;
                foreach ($list as $v2) {
                    $nation = $v2['nation'] ? $v2['nation'] : 'wei_zhi_zu';
                    if ($v2['sex'] == 1 && $nation == $knation) {
                        $num1 += $v2['count'];
                    } elseif ($v2['sex'] == 2 && $nation == $knation) {
                        $num2 += $v2['count'];
                    }
                }

                $sex1[] = $num1;
                $sex2[] = $num2;
            }
        }
        $data['xAxis_data'] = $xAxis_data;
        $data['series'] = [
            ['name' => '男', 'data' => $sex1],
            ['name' => '女', 'data' => $sex2],
        ];
        return $data;
    }
    //todo 教育水平统计
    public function getUserEducateStatisticsMethod($street_id,$community_id,$village_ids=array()){
        $where[]=['educate_level','<>', ''];
        if(!empty($village_ids) && is_array($village_ids)){
            $where[]=['village_id','in',$village_ids];
        }
        $list=$this->getUserBindAttributeMethod($street_id,$community_id,$where,'educate_level','id asc','educate_level as value,count(*) AS count');
        $data=[];
        $total=0;
        if($list){
            $total=array_sum(array_column($list, 'count'));
        }
        foreach ($this->educateLevel as $v){
            $count=0;
            if($list){
                foreach ($list as $v2){
                    if($v['value'] == $v2['value']){
                        $count=$v2['count'];
                        break;
                    }
                }
            }
            $data[]=[
                'name'=>$v['key'],
                'value'=>$count,
                'ratio'=>!empty($total) ? (intval(round_number(($count/$total)*100))) : 0,
            ];
        }
        return $data;
    }

    //todo 婚姻状态统计
    public function getUserMarriageStatisticsMethod($street_id,$community_id,$village_ids=array()){
        $where[]=['marriage','>', 0];
        if(!empty($village_ids) && is_array($village_ids)){
            $where[]=['village_id','in',$village_ids];
        }
        $list=$this->getUserBindAttributeMethod($street_id,$community_id,$where,'marriage','id asc','marriage as value,count(*) AS count');
        $data=[];
        $type=$this->marriage;
        foreach ($type as $v){
            $count=0;
            if($list){
                foreach ($list as $v2){
                    if($v['value'] == $v2['value']){
                        $count=$v2['count'];
                    }
                }
            }
            $data[0][]=[
                'name'=>$v['key'],
                'value'=>$count,
                'itemStyle'=>[
                    'opacity'=>1,
                    'color'=>$v['color']
                ]
            ];
        }
        $data[1]=2;
        $data[2]='婚姻状况统计';
        return $data;
    }

    /**
     * 社区党建
     * @author: liukezhu
     * @date : 2022/5/6
     * @param $street_id
     * @param $community_id
     * @return mixed
     */
    public function getPartyBuilding($street_id,$community_id){
        //党组织架构统计
        $data['org_list']=$this->getPartyBranchMethod($street_id,$community_id);
        //三会一课
        $data['meeting_list']=$this->getMeetingLessonCategoryMethod($street_id,$community_id);
        //党内咨询
        $data['party_list']=$this->getPartyBuildMethod($street_id,$community_id);
        //热点新闻
        $data['news_list']=$this->getStreetNewsMethod($street_id,$community_id);
        return $data;
    }

    /**
     * 党员数量统计
     * @author: liukezhu
     * @date : 2022/5/9
     * @param $street_id
     * @param $community_id
     * @param $page
     * @param $limit
     * @return array
     */
    public function getPartyMemberStatistics($street_id,$community_id,$page,$limit,$village_ids=array()){
        $data=['xAxis_data'=>[],'xAxis_yAxis'=>[],'count'=>0];
        $where[] = ['l.user_political_affiliation','=',1];
        $where[] =['pbu.party_status','=',1];
        $where[] =['p.type','>',0];
        if($community_id > 0){
            $where[] = ['v.community_id','=',$community_id];
        }else{
            if(is_array($street_id)){
                $where[] = ['v.street_id','in',$street_id];
            }else{
                $where[] = ['v.street_id','=',$street_id];
            }
        }
        $where[] = ['v.status','<',2];
        if(!empty($village_ids) && is_array($village_ids)){
            $where[] = ['v.village_id','in',$village_ids];
        }
        $whereArr=[
            ['status','=',0]
        ];
        $whereTmpArr=array(['work_status','<',4],['is_party_member','=',1]);
        if(is_array($street_id)){
            $whereArr[] = ['street_id','in',$street_id];
            $whereTmpArr[] = ['area_id','in',$street_id];
        }else{
            $whereArr[] = ['street_id','=',$street_id];
            $whereTmpArr[]= ['area_id','=',$street_id];
        }
        $configCustomizationService = new ConfigCustomizationService();
        $islantiancom=$configCustomizationService->getFuncZhihuilantiancomSpecial();
        $areaStreetWorkersDb=new AreaStreetWorkers();
        $list=$this->AreaStreetPartyBranch->getList($whereArr,'id,name','sort desc,id desc',$page,$limit);
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            $where[] = ['p.id','in',(array_column($list, 'id'))];
            $field='p.id,p.type,p.name AS party_name,count(*) AS count ';
            $user_label=$this->HouseVillageUserLabel->getListByGroup($where,$field,'p.id','l.id DESC');
            if($user_label && !$user_label->isEmpty()){
                $user_label=$user_label->toArray();
            }
            foreach ($list as $k=>$v){
                $cc=[];
                if($user_label){
                    foreach ($user_label as $k2=>$v2){
                        if($v['id'] == $v2['id']){
                            $cc[]=$v2['count'];
                        }
                    }
                }
                if($islantiancom){
                    $whereCountArr=$whereTmpArr;
                    $whereCountArr[]=array('party_id','=',$v['id']);
                    $xcount=$areaStreetWorkersDb->getCount($whereCountArr);
                    $cc[]=$xcount>0 ? $xcount:0;
                }
                $data['xAxis_data'][]=$v['name'];
                $data['xAxis_yAxis'][]=$cc ? array_sum($cc):0;
            }
        }
        $whereArr=[
            ['status','=',0]
        ];
        if(is_array($street_id)){
            $whereArr[] = ['street_id','in',$street_id];
        }else{
            $whereArr[] = ['street_id','=',$street_id];
        }
        $data['count']=$this->AreaStreetPartyBranch->getCount($whereArr);
        $data['total_limit'] = $limit;
        $data['url'] = cfg('site_url').'/v20/public/platform/#/community/streetCommunity/partyMember/partyMemberList';
        return $data;

    }

    /**
     * 党建活动
     * @author: liukezhu
     * @date : 2022/5/6
     * @param $street_id
     * @param $community_id
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function getPartyActivity($street_id,$community_id,$page,$limit){
        if($community_id > 0){//社区
            $where[]=['a.street_id','=',$community_id];
        }else{//街道
            $whereAreaArr=[
                ['area_type','=',1],
            ];
            $area_id_arr=array();
            if(is_array($street_id)){
                $whereAreaArr[]=array('area_pid','in',$street_id);
                $area_id_arr=$street_id;
            }else{
                $whereAreaArr[]=array('area_pid','=',$street_id);
                $area_id_arr[] = $street_id;
            }
            $area_id=$this->AreaStreet->getColumn($whereAreaArr,'area_id');
            if(!empty($area_id) && is_array($area_id)) {
                foreach ($area_id as $avv){
                    $area_id_arr[]=$avv;
                }
            }
            $area_id_arr=array_unique($area_id_arr);
            $where[] = ['a.street_id','in',$area_id_arr];
        }
        $where[] = ['a.status','=',1];
        $field='a.street_id,b.area_name,count(*) as count';
        $order='count asc,a.party_activity_id asc';
        $group='a.street_id';
        $list = $this->PartyActivity->getGroupList($where,$field,$order,$group,$page,$limit);
        if($list && !$list->isEmpty()){
            $list=$list->toArray();
            $total=array_sum(array_column($list, 'count'));
            foreach ($list as &$v){
                $v['ratio']=!empty($total) ? (intval(round_number(($v['count']/$total)*100))) : 0;
            }
            unset($v);
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $this->PartyActivity->getGroupCount($where,$group);
        $data['url'] = cfg('site_url').'/v20/public/platform/#/community/streetCommunity/PartyActivity/PartyActivityList';
        return $data;
    }

    /**
     * 人口分析
     * @author: liukezhu
     * @date : 2022/5/6
     * @param $street_id
     * @param $community_id
     * @return mixed
     */
    public function getPopulationAnaly($street_id,$community_id,$village_ids=array()){
        //人口信息
        $data['person_list']=$this->getPersonStatisticsMethod($street_id,$community_id,$village_ids);
        //男女比例统计
        $data['sex_list']=$this->getUserSexStatisticsMethod($street_id,$community_id,$village_ids);
        //年龄段统计
        $data['age_list']=$this->getUserAgeStatisticsMethod($street_id,$community_id,$village_ids);
        //居民人口性质统计
        $data['user_label_list']=$this->getUserLabelStatisticsMethod($street_id,$community_id,$village_ids);
        //教育水平统计
        $data['educate_list']=$this->getUserEducateStatisticsMethod($street_id,$community_id,$village_ids);
        //婚姻状况统计
        $data['marriage_list']=$this->getUserMarriageStatisticsMethod($street_id,$community_id,$village_ids);
        return $data;
    }

    /**
     * 疫情防控数据统计
     * @author: liukezhu
     * @date : 2022/5/9
     * @param $street_id
     * @param $community_id
     * @param $page
     * @param $limit
     * @return array
     */
    public function getEpidemicPrevent($street_id,$community_id,$page,$limit){
        $data=[
            'legend_data'=>[],
            'series'=>[],
            'xAxis_data'=>[],
            'count'=>0
        ];
        $where=[
            ['community_id','=',$community_id],
            ['status','=',1],
            ['del_time','=',0]
        ];
        if(is_array($street_id)){
            $where[]=array('street_id','in',$street_id);
        }else{
            $where[]=array('street_id','=',$street_id);
        }
        $order='sort desc,id desc';
        $field='id,title';
        $series_list = $this->AreaStreetEpidemicPreventSeries->getList($where,$field,$order);
        $color=['#177EED','#00FFFF','#91CC75','#FC8452','#73C0DE'];
        if($series_list && !$series_list->isEmpty()){
            $series_list=$series_list->toArray();
            $type_list = $this->AreaStreetEpidemicPreventType->getList($where,$field,$order,$page,$limit);
            if($type_list && !$type_list->isEmpty()){
                $type_list=$type_list->toArray();
                $type_id=array_column($type_list, 'id');
                $whereArr=[
                    ['community_id','=',$community_id],
                    ['type_id','in',$type_id],
                    ['del_time','=',0]
                ];
                if(is_array($street_id)){
                    $whereArr[]=array('street_id','in',$street_id);
                }else{
                    $whereArr[]=array('street_id','=',$street_id);
                }
                $record_list = $this->AreaStreetEpidemicPreventRecord->getLists($whereArr,'id,series_id,type_id,num');
                if($record_list && !$record_list->isEmpty()){
                    $record_list=$record_list->toArray();
                }
                foreach ($series_list as $k1=>$v1){
                    $num=[];
                    foreach ($type_list as $k2=>$v2){
                        $cc=[];
                        foreach ($record_list as $k3=>$v3){
                            if($v1['id'] == $v3['series_id'] && $v2['id'] == $v3['type_id']){
                                $cc[]=$v3['num'];
                            }else{
                                $cc[]=0;
                            }
                        }
                        $num[$k2]=array_sum($cc);
                    }
                    $data['legend_data'][]=$v1['title'];
                    $data['series'][]=[
                        'name'=>$v1['title'],
                        'smooth'=>true,
                        'symbolSize'=>1,
                        'data'=>$num,
                        'type'=>'line',
                        'itemStyle'=>[
                            'normal'=>[
                                'color'=>$color[$k1],
                                'lineStyle'=>[
                                    'color'=>$color[$k1]
                                ]
                            ]
                        ]
                    ];
                }
                $data['xAxis_data']=array_column($type_list, 'title');
            }
            $data['count']=$this->AreaStreetEpidemicPreventType->getCount($where);
        }
        return ['list'=>$data,'url'=>cfg('site_url').'/v20/public/platform/#/community/streetCommunity/epidemicPrevent/list'];

    }

    /**
     * 事件分析
     * @author: liukezhu
     * @date : 2022/5/6
     * @param $street_id
     * @param $community_id
     * @return mixed
     */
    public function getEventAnaly($street_id,$community_id){
        //事件上报
        $data['event_list']=$this->getEventMethod($street_id,$community_id);
        //社区关怀
        $data['care_list']=$this->getCommunityCareMethod($street_id,$community_id);
        //社区物业统计
        $data['property_list']=$this->getPropertyStatisticsMethod($street_id,$community_id);
        //视频监控一
        $data['video_url1']='http://wxsnsdy.tc.qq.com/105/20210/snsdyvideodownload?filekey=30280201010421301f0201690402534804102ca905ce620b1241b726bc41dcff44e00204012882540400&bizid=1023&hy=SH&fileparam=302c020101042530230204136ffd93020457e3c4ff02024ef202031e8d7f02030f42400204045a320a0201000400';
        //视频监控二
        $data['video_url2']='http://wxsnsdy.tc.qq.com/105/20210/snsdyvideodownload?filekey=30280201010421301f0201690402534804102ca905ce620b1241b726bc41dcff44e00204012882540400&bizid=1023&hy=SH&fileparam=302c020101042530230204136ffd93020457e3c4ff02024ef202031e8d7f02030f42400204045a320a0201000400';
        return $data;
    }

    /**
     * 查询工作人员的任务列表
     * @author:zhubaodi
     * @date_time: 2022/5/9 16:37
     */
    public function getTaskWorkerList($data){
        $where=[];
        $where['worker_id']=$data['wid'];
        if ($data['area_type']==1){
            $where['community_id']=$data['area_id'];
        }else{
            $where['street_id']=$data['area_id'];
        }
        $list=[];
        $task_ids=$this->AreaStreetTaskReleaseBind->getColumn($where,'task_id');
      //  print_r($task_ids);die;
        if (!empty($task_ids)){
            $where=[];
            $where['t.id']=$task_ids;
            if ($data['area_type']==1){
                $where['t.community_id']=$data['area_id'];
            }else{
                $where['t.street_id']=$data['area_id'];
            }
            $field='t.title,t.complete_time,t.complete_num,t.id,t.content,r.status,r.content as content_u';
            $order='r.status ASC,t.id DESC';
            $list=$this->AreaStreetTaskRelease->getLists($where,$field,$order,$data['page'],$data['limit']);
            $count=$this->AreaStreetTaskRelease->getCounts($where);
            if (!empty($list)){
                $list=$list->toArray();
            }
            if (!empty($list)){
                foreach ($list as &$v){
                    if ($v['complete_time']>1){
                        $v['complete_time']=date('Y-m-d H:i:s',$v['complete_time']);
                    }
                    if (empty($v['status'])&&empty($v['content_u'])){
                        $v['status']=2;
                    }
                }
            }
        }
        $out = [
            'list' => $list,
            'count' => isset($count) ? $count : 0
        ];
        return $out;
    }

    /**
     * 查询工作人员的任务详情
     * @author:zhubaodi
     * @date_time: 2022/5/9 16:37
     */
    public function getTaskWorkerDetail($data){
        $where=[];
        $where['worker_id']=$data['wid'];
        $where['task_id']=$data['task_id'];
        if ($data['area_type']==1){
            $where['community_id']=$data['area_id'];
        }else{
            $where['street_id']=$data['area_id'];
        }
        $task_release=[];
        $task_info=$this->AreaStreetTaskReleaseBind->getOne($where,'task_id');
        if (!empty($task_info)){
            $where1=[];
          //   $where1['r.worker_id']=$data['wid'];
            $where1['t.id']=$data['task_id'];
            if ($data['area_type']==1){
                $where1['t.community_id']=$data['area_id'];
            }else{
                $where1['t.street_id']=$data['area_id'];
            }
            $field='t.title,t.complete_time,t.complete_num,t.id,t.content as task_content';
            $task_release=$this->AreaStreetTaskRelease->getOne($where1,$field);
            $task_release['img']=[];
            if (!empty($task_release)){
                if ($task_release['complete_time']>1){
                    $task_release['complete_time']=date('Y-m-d H:i:s',$task_release['complete_time']);
                }
                $task_release_info= $this->AreaStreetTaskReleaseRecord->getOne($where,'complete_num_u,content,img');
                if (!empty($task_release_info)){
                    $task_release['complete_num_u']= $task_release_info['complete_num_u'];
                    $task_release['content']= $task_release_info['content'];
                    if (!empty($task_release_info['img']) &&  @unserialize($task_release_info['img'])){
                        $img = unserialize($task_release_info['img']);
                        foreach ($img as &$vv){
                            $vv=replace_file_domain($vv);
                        }
                        $task_release['img']=$img;
                    }

                }else{
                    $task_release['complete_num_u']= 0;
                    $task_release['content']= '';
                }
            }
        }

        return $task_release;
    }


    /**
     * 工作人员添加任务回复
     * @author:zhubaodi
     * @date_time: 2022/5/9 17:21
     */
    public function addTaskReleaseRecord($data){
        $where=[];
        $where['worker_id']=$data['wid'];
        $where['task_id']=$data['task_id'];
        if ($data['area_type']==1){
            $where['community_id']=$data['area_id'];
        }else{
            $where['street_id']=$data['area_id'];
        }
        $res=0;
        $task_info=$this->AreaStreetTaskReleaseBind->getOne($where,'task_id');
        if (!empty($task_info)) {
            $task_info1=$this->AreaStreetTaskRelease->getOne(['id'=>$task_info['task_id']]);
            $where1 = [];
           // $where1['r.worker_id'] = $data['wid'];
            $where1['t.id'] = $data['task_id'];
            $where1['t.del_time'] = 0;
            $where1['t.status'] = 1;
            if ($data['area_type'] == 1) {
                $where1['t.community_id'] = $data['area_id'];
            } else {
                $where1['t.street_id'] = $data['area_id'];
            }
            $field = 't.title,t.complete_time,t.complete_num,t.id';
            $task_release = $this->AreaStreetTaskRelease->getOne($where1, $field);
            $nowtime=time();
            if (!empty($task_release)){
                $data_arr=[];
                $data_arr['community_id']=$task_info1['community_id'];
                $data_arr['street_id']=$task_info1['street_id'];
                $data_arr['task_id']=$data['task_id'];
                $data_arr['worker_id']=$data['wid'];
                $data_arr['complete_time']=$task_release['complete_time'];
                $data_arr['complete_num']=$task_release['complete_num'];
                $data_arr['complete_num_u']=$data['complete_num_u'];
                $data_arr['content']=$data['content'];
                if (($task_release['complete_num']<=$data['complete_num_u']) && ($task_release['complete_time']>=$nowtime) ){
                    $data_arr['status']=1;
                }else{
                    $data_arr['status']=0;
                }
                $data_arr['add_time']=time();
                $data_arr['update_time']=time();
                if (!empty($data['img'])){
                    $data_arr['img']=serialize($data['img']);
                }
                $res = $this->AreaStreetTaskReleaseRecord->addFind($data_arr);
                $res=(int)$res;
            }
        }
        return $res;
    }

    public function getEventVideoStatistics($street_id,$community_id,$key=0)  {
        $village_id=$this->getVillageIdMethod($street_id,$community_id);
        //$this->HouseDeviceChannel = new HouseDeviceChannel();
        $house_camera_device = new HouseCameraDevice();
        $where = [];
        $where[] = ['a.camera_status','=',0];
        $where[] = ['a.village_id','in',$village_id];
        $field = 'b.*,a.look_url';
        $deviceChannel = $house_camera_device->getListLeftJoinChannel($where,$field,1,2,'a.camera_id DESC,b.isDel ASC');
        $nowTime = time() - 100;
        if (!empty($deviceChannel)) {
            $deviceChannel = $deviceChannel->toArray();
        }
        if (empty($deviceChannel)) {
            $deviceChannel = [];
        } else {
            if (isset($deviceChannel[$key])&&isset($deviceChannel[$key]['urlEndTime']) && $deviceChannel[$key]['urlEndTime']>0 &&$deviceChannel[$key]['urlEndTime']<=$nowTime) {
                $channelNo = isset($deviceChannel[$key]['channelNo'])?$deviceChannel[$key]['channelNo']:1;
                $deviceSerial = isset($deviceChannel[$key]['deviceSerial'])?$deviceChannel[$key]['deviceSerial']:'';
                $village_id = isset($deviceChannel[$key]['village_id'])?$deviceChannel[$key]['village_id']:0;
                invoke_cms_model('House_camera_device/liveGetVideoUrl',[$channelNo,$deviceSerial,$village_id]);
                $deviceChannel = $house_camera_device->getListLeftJoinChannel($where,$field,1,2,'a.camera_id DESC,b.isDel ASC');
                if (!empty($deviceChannel)) {
                    $deviceChannel = $deviceChannel->toArray();
                }
                if (empty($deviceChannel)) {
                    $deviceChannel = [];
                }
            }
        }
        $lookUrl='';
        if($deviceChannel && isset($deviceChannel[$key])){
            $lookUrl=$deviceChannel[$key]['flv'] ? $deviceChannel[$key]['flv']:$deviceChannel[$key]['hls'];
            if(empty($lookUrl)){
                $lookUrl=$deviceChannel[$key]['lookUrl'] ? $deviceChannel[$key]['lookUrl']:$deviceChannel[$key]['look_url'];
            }
        }
        return [
            'device' => $deviceChannel,
            'url' => $lookUrl,
            'street_id'=>$street_id,
            'village_id'=>$village_id,
        ];
    }
    /***
     *$xtype 0城市或者区县 1街道 2社区
     ***/
    public function getAllVillageIds($area_id = 0, $xtype = 0)
    {
        $houseVillageDb = new HouseVillage();
        $village_id_arr = array();
        if($area_id<1){
            return $village_id_arr;
        }
        if ($xtype < 1) {
            $areaDb = new Area();
            $whereArr = array(['area_id', '=', $area_id]);
            $resObj = $areaDb->getOne($whereArr, 'area_id,area_pid,area_type');
            if ($resObj && !$resObj->isEmpty()) {
                $res = $resObj->toArray();
                //2城市，3区域
                $whereArr = array();
                if ($res['area_type'] == 2) {
                    $city_id = $res['area_id'];
                    $whereArr[] = array('city_id', '=', $city_id);
                } else if ($res['area_type'] == 3) {
                    $county_id = $res['area_id'];
                    $whereArr[] = array('area_id', '=', $county_id);
                } else {
                    return $village_id_arr;
                }
                $whereArr[] = array('status', '<', 2);
                $houseVillageObj = $houseVillageDb->getList($whereArr, 'village_id', 0, 100);
                if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                    $houseVillages = $houseVillageObj->toArray();
                    if ($houseVillages) {
                        foreach ($houseVillages as $hvv) {
                            $village_id_arr[] = $hvv['village_id'];
                        }
                    }
                }
            }
        } elseif ($xtype == 1) {
            $whereArr = array();
            $whereArr[] = array('status', '<', 2);
            $whereArr[] = array('street_id', '=', $area_id);
            $houseVillageObj = $houseVillageDb->getList($whereArr, 'village_id', 0, 100);
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $houseVillages = $houseVillageObj->toArray();
                if ($houseVillages) {
                    foreach ($houseVillages as $hvv) {
                        $village_id_arr[] = $hvv['village_id'];
                    }
                }
            }
        } elseif ($xtype == 2) {
            $whereArr = array();
            $whereArr[] = array('status', '<', 2);
            $whereArr[] = array('community_id', '=', $area_id);
            $houseVillageObj = $houseVillageDb->getList($whereArr, 'village_id', 0, 100);
            if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                $houseVillages = $houseVillageObj->toArray();
                if ($houseVillages) {
                    foreach ($houseVillages as $hvv) {
                        $village_id_arr[] = $hvv['village_id'];
                    }
                }
            }
        }
        if($village_id_arr){
            $village_id_arr=array_unique($village_id_arr);
        }
        return $village_id_arr;
    }

    public function getStreetOrCommunityTotal($area_id=0,$region_level=4,$area_type=0){
        $areaStreetDb=new AreaStreet();
        $whereTotalArr=array(['area_type','=',$area_type]);
        $tmpTotal=0;
        $area_id_arr=array();
        if($region_level==2 && $area_type==1 ){
            //街道查社区
            $area_id_arr[]=$area_id;
        }else if($region_level==3 && $area_type==0){
            //区县查街道
            $area_id_arr[]=$area_id;
        }else if($region_level==3 && $area_type==1){
            //区县查社区 先查街道
            $whereStreetArr=array(['area_type','=','0']);
            $whereStreetArr[]=array('area_pid','=',$area_id);
            //$whereStreetArr[]=array('is_open','=',1);
            $areaStreetObj=$areaStreetDb->getLists($whereStreetArr,'area_id');
            if($areaStreetObj && !$areaStreetObj->isEmpty()){
                $streetArr=$areaStreetObj->toArray();
                foreach ($streetArr as $rvv){
                    $area_id_arr[]=$rvv['area_id'];
                }
            }

        }else if($region_level==4){
            //城市 先查区县
            $areaDb = new Area();
            $whereArr = array(['area_pid', '=', $area_id]);
            $whereArr[]=array('area_type','=','3');
            //$whereArr[]=array('is_open','=',1);
            $resObj = $areaDb->getList($whereArr, 'area_id,area_name');
            if($resObj && !$resObj->isEmpty()){
                $regionArr=$resObj->toArray();
                $region_id_arr=array();
                foreach ($regionArr as $rvv){
                    $region_id_arr[]=$rvv['area_id'];
                }
                if($area_type==0 && $region_id_arr){
                    $area_id_arr=$region_id_arr;
                }else if($area_type==1 && $region_id_arr){
                    //区县查社区 先查街道
                    $whereStreetArr=array(['area_type','=','0']);
                    $whereStreetArr[]=array('area_pid','in',$region_id_arr);
                    //$whereStreetArr[]=array('is_open','=',1);
                    $areaStreetObj=$areaStreetDb->getLists($whereStreetArr,'area_id');
                    if($areaStreetObj && !$areaStreetObj->isEmpty()){
                        $streetArr=$areaStreetObj->toArray();
                        foreach ($streetArr as $rvv){
                            $area_id_arr[]=$rvv['area_id'];
                        }
                    }
                }
            }
        }
        if($area_id_arr){
            $whereTotalArr[]=array('area_pid','in',$area_id_arr);
            //$whereTotalArr[]=array('is_open','=',1);
            $tmpTotal=$areaStreetDb->getStreetCommunityNum($whereTotalArr);
        }
        $tmpTotal=$tmpTotal>0 ? $tmpTotal:0;
        return $tmpTotal;
    }
    public function getVillagePeopleTotal($village_ids=array()){
        $houseVillageUserBind=new HouseVillageUserBind();
        $whereArr=array(['village_id','in',$village_ids]);
        $whereArr[]=array('type','in',array(0,1,2,3));
        $whereArr[]=array('status','=',1);
        $tmpTotal=$houseVillageUserBind->getVillageUserNum($whereArr);
        $tmpTotal=$tmpTotal>0 ? $tmpTotal:0;
        return $tmpTotal;
    }
    public function getStreetCommunitys($street_id=0){
        $streetCommunitys=array();
        $areaStreetDb=new AreaStreet();
        $whereStreetArr=array(['area_type','=','1']);
        $whereStreetArr[]=array('area_pid','=',$street_id);
        //$whereStreetArr[]=array('is_open','=',1);
        $areaCommunityObj=$areaStreetDb->getLists($whereStreetArr,'area_id,area_name,area_pid,area_type');
        if($areaCommunityObj && !$areaCommunityObj->isEmpty()){
            $streetCommunitys=$areaCommunityObj->toArray();
        }
        return $streetCommunitys;
    }

    public function getUserLableStatistics($village_id=0){
        $houseVillageLabelCatDb=new HouseVillageLabelCat();
        $whereCat=array(['village_id','=',$village_id],['status','=',1],['cat_function','=','userLabel']);
        $catListObj=$houseVillageLabelCatDb->getList($whereCat,'cat_id,cat_name,village_id',0,1000,'cat_id asc');
        $cat_ids=array();

        $returnDataArr=array('axis_label'=>array(),'series'=>array(),'village_id'=>$village_id);
        $axis_label=array();
        $seriesArrInfo=array();
        if($catListObj && !$catListObj->isEmpty()){
            $catList=$catListObj->toArray();
                foreach ($catList as $cvv){
                    $cat_ids[]=$cvv['cat_id'];
                }
                $dataValue=array();
                $houseVillageLabeDb =new HouseVillageLabel();
                $whereLabel=array(['village_id','=',$village_id],['cat_id','in',$cat_ids],['status','=','0'],['is_delete','=','0']);
                $labelObj=$houseVillageLabeDb->getLists($whereLabel,'id,cat_id,label_name',0,1000,'cat_id asc,id desc');
                if($labelObj && !$labelObj->isEmpty()){
                    $labelArr=$labelObj->toArray();
                    $houseVillageLabelUserLabelBindDb =new HouseVillageUserLabelBind();
                    foreach ($labelArr as $kk=>$lv){
                        $whereLabelBind=array(['village_label_id','=',$lv['id']],['is_delete','=','0'],['bind_id','>',0]);
                        $xcount=$houseVillageLabelUserLabelBindDb->getCount($whereLabelBind);
                        $labelArr[$kk]['bcount']=$xcount;
                        $dataValue[]='';
                    }
                    foreach ($labelArr as $kk=>$lv) {
                        $labelArr[$kk]['seriesData']=$dataValue;
                    }
                    foreach ($catList as $c_kk => $c_vv) {
                          $axis_label[]=$c_vv['cat_name'];
                          foreach ($labelArr as $kk=>$lv) {
                            if($lv['cat_id']==$c_vv['cat_id']){
                                $labelArr[$kk]['seriesData'][$c_kk]=$lv['bcount'];
                            }
                        }
                    }
                    foreach ($labelArr as $lv) {
                        $tmpArr=array('name'=>$lv['label_name'],'data'=>$lv['seriesData']);
                        /*
                        $tmpArr['type']='bar';
                        $tmpArr['stack']='total';
                        $tmpArr['label']=array( 'show'=>true);
                        $tmpArr['emphasis']=array('focus'=>'series');
                        $tmpArr['focus']='series';
                        */
                        $seriesArrInfo[]=$tmpArr;
                    }
                }
            }
        $returnDataArr['axis_label']=$axis_label;
        $returnDataArr['series']=$seriesArrInfo;
        return $returnDataArr;
    }
}
