<?php
/**
 * @author : liukezhu
 * @date : 2021/10/12
 */
namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVenueActivityService;
use file_handle\FileHandle;

class VenueController extends CommunityBaseController{

    public function initialize()
    {
        parent::initialize();
    }

    /**
     *活动列表
     * @author: liukezhu
     * @date : 2021/10/12
     * @return \json
     */
    public function activityList(){
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $title = $this->request->post('title','');
        $classify_id = $this->request->post('classify_id',0);
        $contacts = $this->request->post('contacts','');
        try{
            $where[] = ['a.village_id','=',$this->adminUser['village_id']];
            $where[] = ['a.is_del','=',0];
            if(!empty($title)){
                $where[] = ['a.title', 'like', '%'.$title.'%'];
            }
            if(!empty($classify_id)){
                $where[] = ['c.id','=',$classify_id];
            }
            if(!empty($contacts)){
                $where[] = ['a.contacts', 'like', '%'.$contacts.'%'];
            }
            $field='a.id,a.title,c.title as classify_name,a.work_txt,a.contacts,a.phone,a.sort,a.adress,a.status,a.close_time,a.add_time';
            $order = 'a.close_time asc,a.is_appoint desc,a.sort desc,a.id desc';
            $list = (new HouseVenueActivityService())->HtActivityList($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 活动删除
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function activityDel(){
        $id= $this->request->param('id');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        try{
            $id= (new HouseVenueActivityService())->activityDel($id,$this->adminUser['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动关闭
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function activityClose(){
        $id= $this->request->param('id');
        $close_time= $this->request->param('close_time');
        $close_msg= $this->request->param('close_msg');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        if (empty($close_time)){
            return api_output(1001,[],'请选择关闭场馆截止日期！');
        }
        if (empty($close_msg)){
            return api_output(1001,[],'请输入关闭场馆原因！');
        }
        $close_time=strtotime($close_time);
        if(date('Ymd',$close_time) < date('Ymd',time())){
            return api_output(1001,[],'关闭场馆截止日期不可低于今天！');
        }
        $param=array(
            'id'=>$id,
            'village_id'=>$this->adminUser['village_id'],
            'close_time'=>$close_time,
            'close_msg'=>$close_msg,
        );
        try{
            $id= (new HouseVenueActivityService())->activityClose($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动开启
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function activityOpen(){
        $id= $this->request->param('id');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        try{
            $id= (new HouseVenueActivityService())->activityOpen($id,$this->adminUser['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动分类
     * @author: liukezhu
     * @date : 2021/10/12
     * @return \json
     */
    public function classifyData(){
        try{
            $where[] = ['village_id','=',$this->adminUser['village_id']];
            $where[] = ['status','=',1];
            $field='id,title';
            $order = 'sort desc,id desc';
            $list = (new HouseVenueActivityService())->HtClassify($where,$field,$order);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 添加活动
     * @author: liukezhu
     * @date : 2021/10/14
     * @return \json
     */
    public function activityAdd(){
        $title= $this->request->param('title','', 'trim');
        $classify_id= $this->request->param('classify_id');
        $work_txt= $this->request->param('work_txt','', 'trim');
        $content= $this->request->param('content','', 'trim');
        $contacts= $this->request->param('contacts','', 'trim');
        $phone= $this->request->param('phone','', 'trim');
        $img= $this->request->param('img');
        $long= $this->request->param('long');
        $lat= $this->request->param('lat');
        $adress= $this->request->param('adress','', 'trim');
        $sort= $this->request->param('sort','0');
        $is_collect= $this->request->param('is_collect','1');
        $is_appoint= $this->request->param('is_appoint','1');
        $appoint_cycle= $this->request->param('appoint_cycle');
        $appoint_num= $this->request->param('appoint_num','', 'trim');
        $startTime= $this->request->param('startTime','');
        $endTime= $this->request->param('endTime','');
        if (empty($title)){
            return api_output(1001,[],'请输入活动场馆名称！');
        }
        if (empty($classify_id)){
            return api_output(1001,[],'请选择活动场馆类型！');
        }
        if (empty($work_txt)){
            return api_output(1001,[],'请输入办公时间！');
        }
        if (empty($content)){
            return api_output(1001,[],'请输入场馆介绍');
        }
        if(empty($long) || empty($lat)){
            return api_output(1001,[],'请点击选取场馆地址！');
        }
        if(intval($is_appoint) == 1){
            if (empty($appoint_cycle)){
                return api_output(1001,[],'请输入预约周期(天)！');
            }
            if (empty($appoint_num)){
                return api_output(1001,[],'请输入最大预约数！');
            }
            if(empty($startTime) || empty($endTime)){
                return api_output(1001,[],'请设置时间段！');
            }
        }else{
            $appoint_cycle=0;
            $appoint_num=0;
        }
        $param=array(
            'classify_id'=>$classify_id,
            'village_id'=>$this->adminUser['village_id'],
            'title'=>$title,
            'work_txt'=>$work_txt,
            'img'=>$img,
            'contacts'=>$contacts,
            'phone'=>$phone,
            'content'=>$content,
            'long'=>$long,
            'lat'=>$lat,
            'adress'=>$adress,
            'sort'=>$sort,
            'is_collect'=>$is_collect,
            'is_appoint'=>$is_appoint,
            'appoint_cycle'=>$appoint_cycle,
            'appoint_num'=>$appoint_num,
            'start'=>$startTime,
            'end'=>$endTime
        );
        try{
            $id=(new HouseVenueActivityService())->HtActivityAdd($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动数据渲染
     * @author: liukezhu
     * @date : 2021/10/14
     */
    public function activityEdit(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        try{
            $where[] = ['village_id','=',$this->adminUser['village_id']];
            $where[] = ['id','=',$id];
            $where[] = ['is_del','=',0];
            $field='id,classify_id,title,work_txt,img,contacts,phone,content,long,lat,adress,sort,is_collect,is_appoint,appoint_cycle,appoint_num,appoint_time,status';
            $id=$list = (new HouseVenueActivityService())->HtActivityEdit($where,$field);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动数据提交
     * @author: liukezhu
     * @date : 2021/10/14
     */
    public function activityEditSub(){
        $id= $this->request->param('id');
        $title= $this->request->param('title','', 'trim');
        $classify_id= $this->request->param('classify_id');
        $work_txt= $this->request->param('work_txt','', 'trim');
        $content= $this->request->param('content','', 'trim');
        $contacts= $this->request->param('contacts','', 'trim');
        $phone= $this->request->param('phone','', 'trim');
        $img= $this->request->param('img');
        $long= $this->request->param('long');
        $lat= $this->request->param('lat');
        $adress= $this->request->param('adress','', 'trim');
        $sort= $this->request->param('sort','0');
        $is_collect= $this->request->param('is_collect','1');
        $is_appoint= $this->request->param('is_appoint','1');
        $appoint_cycle= $this->request->param('appoint_cycle');
        $appoint_num= $this->request->param('appoint_num','', 'trim');
        $startTime= $this->request->param('startTime','');
        $endTime= $this->request->param('endTime','');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        if (empty($title)){
            return api_output(1001,[],'请输入活动场馆名称！');
        }
        if (empty($classify_id)){
            return api_output(1001,[],'请选择活动场馆类型！');
        }
        if (empty($work_txt)){
            return api_output(1001,[],'请输入办公时间！');
        }
        if (empty($content)){
            return api_output(1001,[],'请输入场馆介绍');
        }
        if(empty($long) || empty($lat)){
            return api_output(1001,[],'请点击选取场馆地址！');
        }
        if(intval($is_appoint) == 1){
            if (empty($appoint_cycle)){
                return api_output(1001,[],'请输入预约周期(天)！');
            }
            if (empty($appoint_num)){
                return api_output(1001,[],'请输入最大预约数！');
            }
            if(empty($startTime) || empty($endTime)){
                return api_output(1001,[],'请设置时间段！');
            }
        }else{
            $appoint_cycle=0;
            $appoint_num=0;
        }
        $param=array(
            'id'=>$id,
            'classify_id'=>$classify_id,
            'village_id'=>$this->adminUser['village_id'],
            'title'=>$title,
            'work_txt'=>$work_txt,
            'img'=>$img,
            'contacts'=>$contacts,
            'phone'=>$phone,
            'content'=>$content,
            'long'=>$long,
            'lat'=>$lat,
            'adress'=>$adress,
            'sort'=>$sort,
            'is_collect'=>$is_collect,
            'is_appoint'=>$is_appoint,
            'appoint_cycle'=>$appoint_cycle,
            'appoint_num'=>$appoint_num,
            'start'=>$startTime,
            'end'=>$endTime
        );
        try{
            $id=(new HouseVenueActivityService())->HtActivitySub($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 分类列表
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function classifyList(){
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        try{
            $where[] = ['village_id','=',$this->adminUser['village_id']];
            $where[] = ['status','=',1];
            $field='id,title,sort';
            $order = 'sort desc,id desc';
            $list = (new HouseVenueActivityService())->HtClassifyList($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 添加活动分类
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function classifyAdd(){
        $title= $this->request->param('title','', 'trim');
        $sort= $this->request->param('sort','0');
        if (empty($title)){
            return api_output(1001,[],'请输入活动场馆名称！');
        }
        $title=trim($title);
        if(strlen($title) >= 100){
            return api_output(1001,[],'请输入【100字以内】活动场馆类型！');
        }
        $param=array(
            'village_id'=>$this->adminUser['village_id'],
            'title'=>$title,
            'sort'=>intval($sort)
        );
        try{
            $id=$list = (new HouseVenueActivityService())->HtClassifyAdd($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动分类渲染
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function classifyEdit(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        try{
            $id=$list = (new HouseVenueActivityService())->HtClassifyEdit($id,$this->adminUser['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动分类提交
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function classifySub(){
        $id= $this->request->param('id');
        $title= $this->request->param('title','', 'trim');
        $sort= $this->request->param('sort','0');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        if (empty($title)){
            return api_output(1001,[],'请输入活动场馆名称！');
        }
        $title=trim($title);
        if(strlen($title) >= 100){
            return api_output(1001,[],'请输入【100字以内】活动场馆类型！');
        }
        $param=array(
            'id'=>$id,
            'village_id'=>$this->adminUser['village_id'],
            'title'=>$title,
            'sort'=>intval($sort)
        );
        try{
            $id=$list = (new HouseVenueActivityService())->HtClassifySub($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动分类删除
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function classifyDel(){
        $id= $this->request->param('id');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        $param=array(
            'id'=>$id,
            'village_id'=>$this->adminUser['village_id']
        );
        try{
            $where[] = ['village_id','=',$this->adminUser['village_id']];
            $where[] = ['id','=',$id];
            $where[] = ['status','=',1];
            $param['status']=4;
            $param['update_time']=time();
            $id=$list = (new HouseVenueActivityService())->classifySave($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 预约记录列表
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function recordList(){
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $activity_id= $this->request->param('activity_id');
        $record_number = $this->request->post('record_number','');
        $name = $this->request->post('name','');
        $status = $this->request->post('status','');
        if (empty($activity_id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        try{
            $where[] = ['village_id','=',$this->adminUser['village_id']];
            $where[] = ['activity_id','=',$activity_id];
            if(!empty($record_number)){
                $where[] = ['record_number','like','%'.$record_number.'%'];
            }
            if(!empty($name)){
                $where[] = ['name','like','%'.$name.'%'];
            }
            if(!empty($status)){
                $where[] = ['status','=',(intval($status) - 1)];
            }
            $field='id,record_number,name,phone,start_time,end_time,appoint_time,status';
            $order = 'status asc,appoint_time desc,id desc';
            (new HouseVenueActivityService())->activityExpire($activity_id);
            $list = (new HouseVenueActivityService())->HtRecordList($where,$field,$page,$limit,$order);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 预约记录渲染
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function recordEdit(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        try{
            $id=$list = (new HouseVenueActivityService())->HtRecordEdit($id,$this->adminUser['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 审核预约
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function recordSub(){
        $id= $this->request->param('id');
        $status= $this->request->param('status','0');
        $examine_msg= $this->request->param('examine_msg','', 'trim');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        $param=array(
            'id'=>$id,
            'village_id'=>$this->adminUser['village_id'],
            'status'=>$status,
            'examine_msg'=>$examine_msg,
        );
        try{
            $id=$list = (new HouseVenueActivityService())->HtRecordSub($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动预约设置渲染
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function activityGetSet(){
        $id = $this->request->param('id','','intval');
        if (empty($id)){
            return api_output(1001,[],'id不能为空！');
        }
        try{
            $where[] = ['village_id','=',$this->adminUser['village_id']];
            $where[] = ['id','=',$id];
            $where[] = ['is_del','=',0];
            $field='id,is_examine';
            $id=$list = (new HouseVenueActivityService())->HtActivityEdit($where,$field,2);
            $id['is_examine']= $id['is_examine'] + 1;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$id){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 活动预约设置提交
     * @author: liukezhu
     * @date : 2021/10/15
     * @return \json
     */
    public function activitySubSet(){
        $id= $this->request->param('id');
        $is_examine= $this->request->param('is_examine','');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数！');
        }
        if (empty($is_examine)){
            return api_output(1001,[],'请设置审核开关！');
        }
        $is_examine=intval($is_examine) - 1;
        if(!in_array($is_examine,[0,1])){
            return api_output(1001,[],'参数不合法！');
        }
        try{
            $where[] = ['village_id','=',$this->adminUser['village_id']];
            $where[] = ['id','=',$id];
            $where[] = ['is_del','=',0];
            $param['is_examine']=$is_examine;
            $param['update_time']=time();
            $id=$list = (new HouseVenueActivityService())->activitySave($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'编辑失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     *获取默认经纬度参数
     * @author: liukezhu
     * @date : 2021/10/18
     * @return \json
     */
    public function activityLocation(){
        try{
            $list = (new HouseVenueActivityService())->HtActivityLocation();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }


    /**
     * Notes: 上传图片
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 13:04
     */
    public function upload(){
        $file = $this->request->file('img');
        try {
            // 验证
            validate(['active_img' => [
                'fileSize' => 1024 * 1024 * 10,   //10M
                'fileExt' => 'jpg,png,jpeg',
                'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
            ]])->check(['active_img' => $file]);
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile('partyActivity', $file);
            if (strpos($savename, "\\") !== false) {
                $savename = str_replace('\\', '/', $savename);
            }
            $imgurl = '/upload/' . $savename;
            $params = ['savepath'=>'/upload/' . $imgurl];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            return json($imgurl);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}