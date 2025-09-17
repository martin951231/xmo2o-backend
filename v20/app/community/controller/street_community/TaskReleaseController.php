<?php
/**
 * @author : liukezhu
 * @date : 2022/4/24
 */
namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\TaskReleaseService;
use app\community\model\service\StorageService;
use think\facade\Db;

class TaskReleaseController extends CommunityBaseController{

    public $street_id=0;
    public $community_id=0;
    public $user_name;
    public function initialize(){
        parent::initialize();
        if ($this->adminUser['area_type'] == 1) {
            // 是社区
            $this->street_id=$this->adminUser['area_pid'];
            $this->community_id=$this->adminUser['area_id'];
        }
        else{
            //是街道
            $this->street_id=$this->adminUser['area_id'];
            $this->community_id=0;
        }
        $this->user_name=isset($this->adminUser['user_name']) ? $this->adminUser['user_name'] : '';
    }

    //=======================todo 任务下发===================

    /**
     * 任务列表表头
     * @author: liukezhu
     * @date : 2022/5/25
     * @return \json
     */
    public function getTaskReleaseListColumns(){
        $type = $this->request->param('type',0,'intval');
        try{
            $list = (new TaskReleaseService())->getTaskReleaseListColumns($this->adminUser['area_type'],$type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 任务下达列表
     * @author: liukezhu
     * @date : 2022/4/24
     * @return \json
     */
    public function getTaskReleaseList(){
        $page = $this->request->param('page',0,'intval');
        $title = $this->request->param('title','','trim');
        $date = $this->request->param('date',[]);
        $type = $this->request->param('type',0,'intval');
        $source = $this->request->param('source',0,'intval');
        $where=[];
        if(!empty($title)){
            $where[] = ['t.title', 'like', '%'.$title.'%'];
        }
        if(!empty($date)){
            $start_time = strtotime($date[0].' 00:00:00');
            $end_time = strtotime($date[1].' 23:59:59');
            $where[] = ['t.complete_time','between',[$start_time,$end_time]];
        }
        if($type){
            $where[]=['t.type','=',$type];
        }
        if($source == 2){
            $where['_string'] = ['', 'exp', Db::raw("FIND_IN_SET({$this->community_id},t.ids)")];
        }
        try {
            $list = (new TaskReleaseService())->getTaskReleaseList($this->street_id,$this->community_id,$where,$page,10);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['area_type'] = $this->adminUser['area_type'];
        return api_output(0, $list, "成功");
    }

    //todo 获取任务类型
    public function getTaskReleaseType(){
        try{
            $list = (new TaskReleaseService())->getTaskReleaseType($this->adminUser['area_type'],$this->street_id,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 街道/社区返回人员数据
     * @author: liukezhu
     * @date : 2022/4/27
     * @return \json
     */
    public function getTaskReleaseTissueNav(){
        $type = $this->request->param('type',0,'intval');
        try{
            $list = (new TaskReleaseService())->getTaskReleaseTissueNav($type,$this->adminUser['area_type'],$this->street_id,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *获取任务数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function getTaskReleaseOne(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $where[]=['t.id','=',$id];
            $where[]=['t.street_id','=',$this->street_id];
            $where[]=['t.community_id','=',$this->community_id];
            $where[]=['t.del_time','=',0];
            $where2[]=['t.id','=',$id];
            $where2[]=['t.type','=',2];
            $where2[]=['t.del_time','=',0];
            $list = (new TaskReleaseService())->getTaskReleaseOne([$where,$where2]);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 添加任务
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function taskReleaseAdd(){
        $type = $this->request->param('type',0,'intval');
        $title = $this->request->param('title','','trim');
        $complete_time = $this->request->param('complete_time','','trim');
        $complete_num = $this->request->param('complete_num','','trim');
        $content = $this->request->param('content','','trim');
        $wid_all = $this->request->param('wid_all',[]);
        if (empty($title)){
            return api_output(1001,[],'请输入任务名称');
        }
        if (empty($complete_time)){
            return api_output(1001,[],'请输入完成时间');
        }
        if (empty($complete_num)){
            return api_output(1001,[],'请输入完成数量');
        }
        if (empty($content)){
            return api_output(1001,[],'请输入任务内容');
        }
        if (empty($wid_all) || !is_array($wid_all)){
            return api_output(1001,[],'请选择分配');
        }
        $param=array(
            'street_id'=>$this->street_id,
            'community_id'=>$this->community_id,
            'title'=>$title,
            'complete_time'=>strtotime($complete_time.' 23:59:59'),
            'complete_num'=>$complete_num,
            'content'=>$content,
            'wid_all'=>$wid_all,
            'type'=>$type
        );
        try{
            $list = (new TaskReleaseService())->taskReleaseAdd($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 编辑任务数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function taskReleaseSub(){
        $id = $this->request->param('id',0,'intval');
        $type = $this->request->param('type',0,'intval');
        $title = $this->request->param('title','','trim');
        $complete_time = $this->request->param('complete_time','','trim');
        $complete_num = $this->request->param('complete_num','','trim');
        $content = $this->request->param('content','','trim');
        $wid_all = $this->request->param('wid_all',[]);
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        if (empty($title)){
            return api_output(1001,[],'请输入任务名称');
        }
        if (empty($complete_time)){
            return api_output(1001,[],'请输入完成时间');
        }
        if (empty($complete_num)){
            return api_output(1001,[],'请输入完成数量');
        }
        if (empty($content)){
            return api_output(1001,[],'请输入任务内容');
        }
        if (empty($wid_all) || !is_array($wid_all)){
            return api_output(1001,[],'请选择分配');
        }
        $param=array(
            'title'=>$title,
            'complete_time'=>strtotime($complete_time.' 23:59:59'),
            'complete_num'=>$complete_num,
            'content'=>$content,
            'wid_all'=>$wid_all,
            'type'=>$type
        );
        $where[]=['t.id','=',$id];
        $where[]=['t.street_id','=',$this->street_id];
        $where[]=['t.community_id','=',$this->community_id];
        $where[]=['t.del_time','=',0];
        try{
            $list = (new TaskReleaseService())->taskReleaseEdit($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除任务
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function taskReleaseDel(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        $where[]=['t.id','=',$id];
        $where[]=['t.street_id','=',$this->street_id];
        $where[]=['t.community_id','=',$this->community_id];
        $where[]=['t.del_time','=',0];
        try{
            $list = (new TaskReleaseService())->taskReleaseDel($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 任务记录
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function getTaskReleaseRecord(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        $page = $this->request->param('page',0,'intval');
        $where[]=['r.street_id','=',$this->street_id];
        $where[]=['r.community_id','=',$this->community_id];
        $where[]=['r.task_id','=',$id];
        $where[]=['r.del_time','=',0];
        $field='r.id,w.work_name,r.add_time,r.complete_num,r.complete_num_u,r.content,r.status,r.img';
        try {
            $list = (new TaskReleaseService())->getTaskReleaseRecordList($where,$field,$page,10);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }


    //=======================todo 疫情防控===================

    /**
     * 疫情防控 系列列表
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function getEpidemicPreventSeriesList(){
        $page = $this->request->param('page',0,'intval');
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        $field='id,title,status,sort,add_time';
        try {
            $list = (new TaskReleaseService())->getEpidemicPreventSeriesList($where,$field,$page,10);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * 添加系列
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventSeriesAdd(){
        $title = $this->request->param('title','','trim');
        $sort = $this->request->param('sort',0,'intval');
        $status = $this->request->param('status',0,'intval');
        if (empty($title)){
            return api_output(1001,[],'请输入名称');
        }
        $param=array(
            'street_id'=>$this->street_id,
            'community_id'=>$this->community_id,
            'title'=>$title,
            'sort'=>$sort,
            'status'=>$status,
        );
        try{
            $list = (new TaskReleaseService())->epidemicPreventSeriesAdd($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取系列
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventSeriesOne(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $where[]=['id','=',$id];
            $where[]=['street_id','=',$this->street_id];
            $where[]=['community_id','=',$this->community_id];
            $where[]=['del_time','=',0];
            $list = (new TaskReleaseService())->epidemicPreventSeriesOne($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *编辑系列
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventSeriesSub(){
        $id = $this->request->param('id',0,'intval');
        $title = $this->request->param('title','','trim');
        $sort = $this->request->param('sort',0,'intval');
        $status = $this->request->param('status',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        if (empty($title)){
            return api_output(1001,[],'请输入名称');
        }
        $param=array(
            'title'=>$title,
            'sort'=>$sort,
            'status'=>$status,
        );
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->epidemicPreventSeriesEdit($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除系列
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventSeriesDel(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->epidemicPreventSeriesDel($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 疫情防控 类型列表
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function getEpidemicPreventTypeList(){
        $page = $this->request->param('page',0,'intval');
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        $field='id,title,status,sort,add_time';
        try {
            $list = (new TaskReleaseService())->getEpidemicPreventTypeList($where,$field,$page,10);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * 添加类型
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventTypeAdd(){
        $title = $this->request->param('title','','trim');
        $sort = $this->request->param('sort',0,'intval');
        $status = $this->request->param('status',0,'intval');
        if (empty($title)){
            return api_output(1001,[],'请输入名称');
        }
        $param=array(
            'street_id'=>$this->street_id,
            'community_id'=>$this->community_id,
            'title'=>$title,
            'sort'=>$sort,
            'status'=>$status,
        );
        try{
            $list = (new TaskReleaseService())->epidemicPreventTypeAdd($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     *查询类型
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventTypeOne(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $where[]=['id','=',$id];
            $where[]=['street_id','=',$this->street_id];
            $where[]=['community_id','=',$this->community_id];
            $where[]=['del_time','=',0];
            $list = (new TaskReleaseService())->epidemicPreventTypeOne($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 编辑类型
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventTypeSub(){
        $id = $this->request->param('id',0,'intval');
        $title = $this->request->param('title','','trim');
        $sort = $this->request->param('sort',0,'intval');
        $status = $this->request->param('status',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        if (empty($title)){
            return api_output(1001,[],'请输入名称');
        }
        $param=array(
            'title'=>$title,
            'sort'=>$sort,
            'status'=>$status,
        );
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->epidemicPreventTypeEdit($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除类型
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventTypeDel(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->epidemicPreventTypeDel($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取疫情防护类型参数
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function getEpidemicPreventParamAll(){
        try{
            $list = (new TaskReleaseService())->getEpidemicPreventParam($this->street_id,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取疫情防控 记录列表
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function getEpidemicPreventRecordList(){
        $page = $this->request->param('page',0,'intval');
        $series_id = $this->request->param('series_id',0,'intval');
        $type_id = $this->request->param('type_id',0,'intval');
        $date = $this->request->param('date',[]);
        if(!empty($date)){
            $start_time = strtotime($date[0].' 00:00:00');
            $end_time = strtotime($date[1].' 23:59:59');
            $where[] = ['r.add_time','between',[$start_time,$end_time]];
        }
        if($series_id){
            $where[]=['r.series_id','=',$series_id];
        }
        if($type_id){
            $where[]=['r.type_id','=',$type_id];
        }
        $where[]=['r.street_id','=',$this->street_id];
        $where[]=['r.community_id','=',$this->community_id];
        $where[]=['r.del_time','=',0];
        $field='r.id,s.title as series_name,t.title as type_name,r.num,r.remarks,r.add_time';
        try {
            $list = (new TaskReleaseService())->getEpidemicPreventRecordList($where,$field,$page,10);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * 添加记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventRecordAdd(){
        $num = $this->request->param('num',0,'intval');
        $series_id = $this->request->param('series_id',0,'intval');
        $type_id = $this->request->param('type_id',0,'intval');
        $remarks = $this->request->param('remarks','','trim');
        if (empty($num)){
            return api_output(1001,[],'请输入数量');
        }
        if (empty($series_id)){
            return api_output(1001,[],'请选择系列');
        }
        if (empty($type_id)){
            return api_output(1001,[],'请选择类型');
        }
        $param=array(
            'street_id'=>$this->street_id,
            'community_id'=>$this->community_id,
            'series_id'=>$series_id,
            'type_id'=>$type_id,
            'num'=>$num,
            'remarks'=>$remarks
        );
        try{
            $list = (new TaskReleaseService())->epidemicPreventRecordAdd($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 查询记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventRecordOne(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $where[]=['id','=',$id];
            $where[]=['street_id','=',$this->street_id];
            $where[]=['community_id','=',$this->community_id];
            $where[]=['del_time','=',0];
            $list = (new TaskReleaseService())->epidemicPreventRecordOne($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 编辑记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventRecordSub(){
        $id = $this->request->param('id',0,'intval');
        $num = $this->request->param('num',0,'intval');
        $series_id = $this->request->param('series_id',0,'intval');
        $type_id = $this->request->param('type_id',0,'intval');
        $remarks = $this->request->param('remarks','','trim');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        if (empty($num)){
            return api_output(1001,[],'请输入数量');
        }
        if (empty($series_id)){
            return api_output(1001,[],'请选择系列');
        }
        if (empty($type_id)){
            return api_output(1001,[],'请选择类型');
        }
        $param=array(
            'series_id'=>$series_id,
            'type_id'=>$type_id,
            'num'=>$num,
            'remarks'=>$remarks
        );
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->epidemicPreventRecordEdit($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除记录
     * @author: liukezhu
     * @date : 2022/4/26
     * @return \json
     */
    public function epidemicPreventRecordDel(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->epidemicPreventRecordDel($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 网格员绑定社区 查询社区和组织部门
     * @author: liukezhu
     * @date : 2022/4/27
     * @return \json
     */
    public function getTissueNav(){
        try{
            $list = (new TaskReleaseService())->getStreetCommunityTissueNav($this->street_id,$this->community_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    //=======================todo 社区关怀===================

    /**
     * 获取社区关怀数据
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function getCommunityCareType(){
        try{
            $list = (new TaskReleaseService())->getCommunityCareType();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取社区关怀列表
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function getCommunityCareList(){
        $page = $this->request->param('page',0,'intval');
        $type = $this->request->param('type',0,'intval');
        $date = $this->request->param('date',[]);
        if(!empty($type)){
            $where[]=['type','=',$type];
        }
        if(!empty($date)){
            $start_time = strtotime($date[0].' 00:00:00');
            $end_time = strtotime($date[1].' 23:59:59');
            $where[] = ['add_time','between',[$start_time,$end_time]];
        }
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        $field='id,type,num,remarks,operator,add_time';
        try {
            $list = (new TaskReleaseService())->getCommunityCareList($where,$field,$page,10);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * 添加社区关怀
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function communityCareAdd(){
        $type = $this->request->param('type',0,'intval');
        $num = $this->request->param('num',0,'intval');
        $remarks = $this->request->param('remarks','','trim');
        if (empty($type)){
            return api_output(1001,[],'请选择类型');
        }
        if (empty($num)){
            return api_output(1001,[],'请输入数量');
        }
        $param=array(
            'street_id'=>$this->street_id,
            'community_id'=>$this->community_id,
            'type'=>$type,
            'num'=>$num,
            'operator'=>$this->user_name,
            'remarks'=>$remarks,
        );
        try{
            $list = (new TaskReleaseService())->communityCareAdd($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 获取社区关怀
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function communityCareOne(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $where[]=['id','=',$id];
            $where[]=['street_id','=',$this->street_id];
            $where[]=['community_id','=',$this->community_id];
            $where[]=['del_time','=',0];
            $list = (new TaskReleaseService())->communityCareOne($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 编辑社区关怀
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function communityCareSub(){
        $id = $this->request->param('id',0,'intval');
        $type = $this->request->param('type',0,'intval');
        $num = $this->request->param('num',0,'intval');
        $remarks = $this->request->param('remarks','','trim');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        if (empty($type)){
            return api_output(1001,[],'请选择类型');
        }
        if (empty($num)){
            return api_output(1001,[],'请输入数量');
        }
        $param=array(
            'type'=>$type,
            'num'=>$num,
            'operator'=>$this->user_name,
            'remarks'=>$remarks,
        );
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->communityCarEdit($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 删除社区关怀
     * @author: liukezhu
     * @date : 2022/4/25
     * @return \json
     */
    public function communityCareDel(){
        $id = $this->request->param('id',0,'intval');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        $where[]=['id','=',$id];
        $where[]=['street_id','=',$this->street_id];
        $where[]=['community_id','=',$this->community_id];
        $where[]=['del_time','=',0];
        try{
            $list = (new TaskReleaseService())->communityCarDel($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }





}