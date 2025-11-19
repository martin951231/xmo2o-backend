<?php
/**
 * @author : liukezhu
 * @date : 2021/10/8
 */
namespace app\community\controller\village_api\User;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseVenueActivityService;
use app\common\model\service\config\ConfigCustomizationService;
use token\Token;


class VenueApiController extends BaseController{

    protected $village_id;
    protected $pigcms_id;
    protected $user_arr=[];
    public function initialize()
    {
        parent::initialize();
        $this->village_id=$this->request->post('village_id',0);
        $pigcms_id=$this->request->post('pigcms_id',0);
        $action=strtolower(($this->app->request)->action());
        if(in_array($action,['getvenueactivitydetails','activitycollect','choicedate','subappoint','myappoint','getcancelappoint','subcancelappoint','appointdetails'])){
            $user=(new HouseVenueActivityService())->getUserBind($this->village_id,$pigcms_id,'pigcms_id,uid,name,phone');
            if($user){
                $this->user_arr=$user;
            }else{
                $pigcms_id=0;
            }
        }
        $this->pigcms_id=$pigcms_id;
        self::int_();
    }

    public function int_(){
        return (new HouseVenueActivityService())->appointRecord($this->village_id);
    }

    /**
     * 获取场馆活动列表
     * @author: liukezhu
     * @date : 2021/10/8
     * @return \json
     */
    public function getVenueActivity(){
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $where[] = ['village_id','=',$this->village_id];
        $where[] = ['is_del','=',0];
        $field='id as activity_id,title,img,work_txt,adress';
        $order = 'close_time asc,is_appoint desc,sort desc,id desc';
        try{
            $data = (new HouseVenueActivityService())->getActivityList($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *获取场馆活动详情
     * @author: liukezhu
     * @date : 2021/10/8
     * @return \json
     */
    public function getVenueActivityDetails(){
        $activity_id = $this->request->post('activity_id',0);
        if(!$activity_id)
            return api_output_error(1001,'缺少activity_id');
//        $where[] = ['a.village_id','=',$this->village_id];
        $where[] = ['a.id','=',$activity_id];
        $where[] = ['a.is_del','=',0];
        $field='a.id as activity_id,a.img,a.title,c.title as classify_name,a.work_txt,a.contacts,a.phone,a.content,a.long,a.lat,a.adress,a.is_collect,a.is_appoint,a.status,a.close_msg,a.appoint_cycle,a.add_time,a.close_time';
        try{
            $data = (new HouseVenueActivityService())->getActivityDetails($where,$field,$this->pigcms_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 场馆点击/取消收藏
     * @author: liukezhu
     * @date : 2021/10/8
     * @return \json
     */
    public function activityCollect(){
        $activity_id = $this->request->post('activity_id',0);
        $type = $this->request->post('type',1);  //1:收藏 2：取消收藏
        if(!$activity_id)
            return api_output_error(1001,'缺少activity_id');
        if(!$this->pigcms_id)
            return api_output_error(1001,'您暂未绑定该小区，请先绑定');
        $param=[
            'village_id'=>$this->village_id,
            'activity_id'=>$activity_id,
            'pigcms_id'=>$this->pigcms_id,
            'uid'=>$this->user_arr['uid'],
            'type'=>$type
        ];
        try{
            $data = (new HouseVenueActivityService())->activityCollect($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 预约场馆 返回日期数据
     * @author: liukezhu
     * @date : 2021/10/8
     * @return \json
     */
    public function choiceDate(){
        $activity_id = $this->request->post('activity_id',0);
        $day = $this->request->post('day');
        if(!$activity_id)
            return api_output_error(1001,'缺少activity_id');
        if(!$this->pigcms_id)
            return api_output_error(1001,'您暂未绑定该小区，请先绑定');
        if(!$day)
            return api_output_error(1001,'请选择预约日期');
        $param=[
            'village_id'=>$this->village_id,
            'activity_id'=>$activity_id,
            'pigcms_id'=>$this->pigcms_id,
            'day'=>$day
        ];
        try{
            $data = (new HouseVenueActivityService())->choiceDate($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *用户点击预约活动
     * @author: liukezhu
     * @date : 2021/10/9
     * @return \json
     */
    public function subAppoint(){
        $activity_id = $this->request->post('activity_id',0);
        $day = $this->request->post('day');
        $time = $this->request->post('time');
        $remarks = $this->request->post('remarks','');
        if(!$activity_id)
            return api_output_error(1001,'缺少activity_id');
        if(!$this->pigcms_id)
            return api_output_error(1001,'您暂未绑定该小区，请先绑定');
        if(!$day)
            return api_output_error(1001,'请选择预约日期');
        if(!$time)
            return api_output_error(1001,'请选择预约时间段');
        if(strpos($time,'-') == false)
            return api_output_error(1001,'数据不合法');
        $param=[
            'village_id'=>$this->village_id,
            'activity_id'=>$activity_id,
            'pigcms_id'=>$this->pigcms_id,
            'day'=>$day,
            'time'=>explode('-',$time),
            'remarks'=>$remarks,
            'user_arr'=>$this->user_arr
        ];
        try{
            $data = (new HouseVenueActivityService())->subAppoint($param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 我的预约列表
     * @author: liukezhu
     * @date : 2021/10/9
     * @return \json
     */
    public function myAppoint(){
        $day_type = $this->request->post('day_type',0);
        if(!$this->pigcms_id)
            return api_output_error(1001,'您暂未绑定该小区，请先绑定');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $where[] = ['r.uid','=',$this->user_arr['uid']];

//        $where[] = [ 'r.status','in',[0,1]];
        switch ($day_type) {
            case 1:
                //今天
                $where['_appoint_time']=date('Y-m-d',time());
                break;
            case 2:
                //明天
                $where['_appoint_time']=date('Y-m-d',strtotime("+1 day"));
                break;
            case 3:
                //后天
                $where['_appoint_time']=date('Y-m-d',strtotime("+2 day"));
                break;
        }
        $field='r.id as record_id,r.info,r.start_time,r.end_time,r.appoint_time,r.status,a.title,a.status as close_status,a.close_time,a.is_del';
        $order = 'r.appoint_time desc,r.id desc';
        try{
            $data = (new HouseVenueActivityService())->getMyAppoint($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取取消预约数据
     * @author: liukezhu
     * @date : 2021/10/9
     * @return \json
     */
    public function getCancelAppoint(){
        $record_id = $this->request->post('record_id',0);
        if(!$this->pigcms_id)
            return api_output_error(1001,'您暂未绑定该小区，请先绑定');
        if(!$record_id)
            return api_output_error(1001,'缺少必要参数');
        $where[] = ['r.id','=',$record_id];
        $where[] = ['r.uid','=',$this->user_arr['uid']];
        try{
            $data = (new HouseVenueActivityService())->getAppointInfo($where,'r.id,r.name,r.status,r.appoint_time,a.status as close_status,a.close_time,a.is_del');
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 提交取消预约
     * @author: liukezhu
     * @date : 2021/10/9
     * @return \json
     */
    public function subCancelAppoint(){
        $record_id = $this->request->post('record_id',0);
        $reason = $this->request->post('reason','');
        if(!$this->pigcms_id)
            return api_output_error(1001,'您暂未绑定该小区，请先绑定');
        if(!$record_id)
            return api_output_error(1001,'缺少必要参数');
        if(empty($reason))
            return api_output_error(1001,'请输入原因');
        $where[] = ['r.id','=',$record_id];
        $where[] = ['r.uid','=',$this->user_arr['uid']];
        $reason=trim($reason);
        if(strlen($reason) > 100){
            return api_output_error(1001,'请输入100字以内原因');
        }
        try{
            $data = (new HouseVenueActivityService())->subCancelAppoint($where,$reason);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 预约详情
     * @author: liukezhu
     * @date : 2021/10/9
     * @return \json
     */
    public function appointDetails(){
        $record_id = $this->request->post('record_id',0);
        if(!$this->pigcms_id)
            return api_output_error(1001,'您暂未绑定该小区，请先绑定');
        if(!$record_id)
            return api_output_error(1001,'缺少必要参数');
        $where[] = ['r.id','=',$record_id];
        $where[] = ['r.uid','=',$this->user_arr['uid']];
        $field='r.id as record_id,r.record_number,a.title as activity_name,c.title as classify_name,a.long,a.lat,a.adress,r.name,r.phone,r.appoint_time,r.start_time,r.end_time,r.remarks,r.status,r.cancel_time,r.cancel_msg,r.examine_msg,a.status as close_status,a.close_time,a.is_del';
        try{
            $data = (new HouseVenueActivityService())->getAppointDetails($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     *预约消息类型
     * @author: liukezhu
     * @date : 2021/10/11
     * @return \json
     */
    public function appointNewsType(){
        $source_type = $this->request->post('source_type',0);
        try{
            $configCustomizationService=new ConfigCustomizationService();
            $isCbztssqCustom=$configCustomizationService->getCbztssqCustom();
            if($isCbztssqCustom){
                $source_type=3;
            }
            $data = (new HouseVenueActivityService())->appointNewsType($source_type);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 我的预约消息
     * @author: liukezhu
     * @date : 2021/10/11
     * @return \json
     */
    public function myAppointNews(){
        if(!$this->request->log_uid)
            return api_output_error(1001,'会话失效，请登录');
        $type = $this->request->post('type',0,'intval');
        $status = $this->request->post('status',0,'intval');
        $page = $this->request->post('page',1,'intval');
        $limit = $this->request->post('limit',10,'intval');
        $pigcms_id = $this->request->param('pigcms_id',0,'intval');
        $source_type = $this->request->post('source_type',0,'intval');
        $where[] = ['uid','=',$this->request->log_uid];
        if($type > 0){
            $where[] = ['type','=',$type];
        }
        if($status > 0){
            $where[] = ['status','=',$status];
        }
        $configCustomizationService=new ConfigCustomizationService();
        $isCbztssqCustom=$configCustomizationService->getCbztssqCustom();
        if($isCbztssqCustom){
            $source_type=3;
        }
        if($source_type == 3){
            if(!$pigcms_id){
                $pigcms_id=0;
            }
            $where[] = ['pigcms_id','=',$pigcms_id];
            if($type == 0){
                $where[] = ['type','=',3];
            }
        }else{
            if($type == 0){
                $where[] = ['type','in',[1,2]];
            }
        }
        $field='title,content,status';
        $order = 'id desc';
        try{
            $data = (new HouseVenueActivityService())->myAppointNews($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 我的收藏
     * @author: liukezhu
     * @date : 2021/10/12
     * @return \json
     */
    public function myCollect(){
        if(!$this->request->log_uid)
            return api_output_error(1001,'会话失效，请登录');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $where[] = ['r.uid','=',$this->request->log_uid];
        $field='r.id as collect_id,a.id as activity_id,a.title,c.title as classify_name,a.img';
        $order = 'r.id desc';
        try{
            $data = (new HouseVenueActivityService())->myCollectList($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

}