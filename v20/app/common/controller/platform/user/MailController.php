<?php


namespace app\common\controller\platform\user;


use app\common\controller\api\ApiBaseController;
use app\common\model\service\user\MailService;
use app\common\model\service\user\UserService;
use app\common\model\service\UserLevelService;

class MailController extends ApiBaseController
{
    /**
     *系统后台站内信列表
     */
     public function mailList(){
         $category_type  = $this->request->param("category_type", "", "trim");
         $users  = $this->request->param("users", "", "trim");
         $set_send_time  = $this->request->param("set_send_time_date", "", "trim");
         $title  = $this->request->param("title", "", "trim");
         $where=array();
         if(!empty($category_type)){
             $arr=explode("-",$category_type);
             if($arr[0]==0){
                 $where[]=['category_id','=',$arr[1]];
             }else{
                 $where[]=['category_type','=',$arr[1]];
             }
         }

         if($users!=""){
             $where[]=['users','=',$users];
         }

         if(!empty($set_send_time)){
             $start=strtotime($set_send_time[0]);
             $end=strtotime($set_send_time[1]);
             $where[]=['set_send_time','>',$start];
             $where[]=['set_send_time','<',$end];
         }

         if(!empty($title)){
             $where[]=['title','like','%'.$title.'%'];
         }
         $page=$this->request->param("page", 1, 'intval');
         $pageSize=$this->request->param("pageSize", 10, "intval");
         $list['cat_sel']=(new MailService())->cat_sel();
         $list['list']=(new MailService())->mailList($where,$page,$pageSize);
         return api_output(1000, $list);
     }

    /**
     * @return \json
     * 后台站内信新增
     */
     public function addData(){
         $data  = $this->request->param();
         if(isset($data['category_type']) && empty($data['category_type'])){
             return api_output(1003, L_('所属分类类型缺失'));
         }
         if(!empty($data['category_type'])){
             $arr=explode("-",$data['category_type']);
             if($arr[0]==0){
                 $data['category_type']=1;
                 $data['category_id']=$arr[1];
                 //$where[]=['category_id','=',$arr[1]];
             }else{
                 $data['category_type']=$arr[1];
             }
         }
         if(!isset($data['title'])){
             return api_output(1003, L_('主标题必填'));
         }
         if($data['users']==1){
             $data['users_label']=serialize($data['user']);
         }

         if($data['set_send_time_date']!=0){
             $time=$data['set_send_time_date']." ".$data['set_send_time_min'];
             $data['set_send_time']=strtotime($time);
             if($data['set_send_time']<time()){
                 $data['send_status']=1;
             }
         }else{
             $data['send_status']=1;
         }
         unset($data['set_send_time_date']);
         unset($data['set_send_time_min']);
         unset($data['user']);
         unset($data['province_id']);
         unset($data['city_id']);
         if(isset($data['system_type'])){
             unset($data['system_type']);
         }
         $uid=(new MailService())->addData($data);
         if($uid){
             return api_output(1000, L_('新增成功'));
         }else{
             return api_output(1003, L_('新增失败'));
         }
     }

    /**
     * @return \json
     * 编辑添加返回数据
     */
     public function editMail(){
         $data  = $this->request->param();
         $msg['cat_sel']=(new MailService())->cat_sel();
         $msg['options']=(new MailService())->ajax_province();
         $msg['level_sel']=(new UserLevelService())->getSome([],true,'level asc');
         $msg['label_sel']=(new UserService())->userLabel();
         if(isset($data['id']) && $data['id']>0){
             //$where=[['id','=',$data['id']]];
             $msg['list']=(new MailService())->editMail($data);

         }
         return api_output(1000, $msg);
     }

    /**
     * @return \json
     * 后台站内信更新
     */
     public function saveData(){
         $data  = $this->request->param();
         if(isset($data['category_type'])){
             return api_output(1003, L_('所属分类类型缺失'));
         }

         if(isset($data['title'])){
             return api_output(1003, L_('主标题必填'));
         }
         $ret=(new MailService())->saveData($data);
         if($ret!==false){
             return api_output(1000, L_('更新成功'));
         }else{
             return api_output(1003, L_('更新失败'));
         }
     }

    /**
     * @return \json
     * 删除后台站内信
     */
     public function delData(){
         $id  = $this->request->param("id", "", "intval");
         if(empty($id)){
             return api_output(1003, L_('需要删除的信息id缺失'));
         }else{
             $where=[['id','=',$id]];
             $ret=(new MailService())->delData($where);
             if($ret){
                 return api_output(1000, L_('删除成功'));
             }else{
                 return api_output(1003, L_('删除失败'));
             }
         }
     }
}