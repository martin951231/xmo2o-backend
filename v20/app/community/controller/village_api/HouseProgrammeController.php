<?php
/**
 * @author : liukezhu
 * @date : 2022/3/22
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseProgrammeService;
use app\community\model\service\PropertyFrameworkService;

class HouseProgrammeController extends CommunityBaseController{
    /**
     *获取权限方案列表
     * @author: liukezhu
     * @date : 2022/3/23
     * @return \json
     */
    public function getProgrammeList(){
        $page = $this->request->param('page',0,'int');
        $title= $this->request->param('title','','trim');
        $group_id= $this->request->param('group_id',0,'intval');
        try{
            $where[]=['p.village_id','=',$this->adminUser['village_id']];
            $where[]=['p.del_time', '=', 0];
            if(!empty($title)){
                $where[] = ['p.title', 'like', '%'.$title.'%'];
            }
            if(!empty($group_id)){
                $where[] = ['p.group_id', '=', $group_id];
            }
            $field='p.id,p.village_id,p.title,g.name as group_name,p.remarks';
            $order='p.id desc';
            $list = (new HouseProgrammeService())->getProgrammeList($where,$field,$order,$page,10);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);


    }

    /**
     * 获取分组数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @return \json
     */
    public function getGroupAll(){
        try{
            $where[]=['village_id','=',$this->adminUser['village_id']];
            $where[]=['status', '=', 1];
            $list = (new HouseProgrammeService())->getGroup($where,'group_id as id,name as title');
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 查询方案数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @return \json
     */
    public function programmeQuery(){
        $id = $this->request->param('id',0,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $list = (new HouseProgrammeService())->find($this->adminUser['village_id'],$id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }

    /**
     * 获取部门数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @return \json
     */
    public function getTissueNav(){
        try{
            $list = (new HouseProgrammeService())->businessNav($this->adminUser['village_id']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list['data']);
    }

    /**
     * 添加方案数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @return \json
     */
    public function programmeAdd(){
        $title= $this->request->param('title','', 'trim');
        $group_id= $this->request->param('group_id',0,'intval');
        $wid_all= $this->request->param('wid_all','');
        $remarks= $this->request->param('remarks','', 'trim');
        if (empty($title)){
            return api_output(1001,[],'请输入权限方案名称！');
        }
        if (empty($group_id)){
            return api_output(1001,[],'请选择分组！');
        }
        if (empty($wid_all)){
            return api_output(1001,[],'请选择人员！');
        }
        $param=array(
            'village_id'=>$this->adminUser['village_id'],
            'title'=>$title,
            'group_id'=>$group_id,
            'wid_all'=>$wid_all,
            'remarks'=>$remarks,
            'add_time'=>time()
        );
        try{
            $id= (new HouseProgrammeService())->add($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($id<1){
            return api_output_error(1001,'添加失败');
        }else{
            return api_output(0,$id);
        }
    }

    /**
     * 编辑方案数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @return \json
     */
    public function programmeSub(){
        $id= $this->request->param('id',0,'intval');
        $title= $this->request->param('title','', 'trim');
        $group_id= $this->request->param('group_id',0,'intval');
        $wid_all= $this->request->param('wid_all','');
        $remarks= $this->request->param('remarks','', 'trim');
        if (empty($title)){
            return api_output(1001,[],'请输入权限方案名称！');
        }
        if (empty($group_id)){
            return api_output(1001,[],'请选择分组！');
        }
        if (empty($wid_all)){
            return api_output(1001,[],'请选择人员！');
        }
        $param=array(
            'title'=>$title,
            'group_id'=>$group_id,
            'wid_all'=>$wid_all,
            'remarks'=>$remarks,
            'update_time'=>time()
        );
        try{
            $id= (new HouseProgrammeService())->edit($this->adminUser['village_id'],$id,$param);
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
     * 删除方案组
     * @author: liukezhu
     * @date : 2022/3/23
     * @return \json
     */
    public function programmeDel(){
        $id = $this->request->param('id',0,'int');
        if (empty($id)){
            return api_output(1001,[],'缺少必要参数');
        }
        try{
            $village_id=$this->adminUser['village_id'];
            $where[]=['id', '=', $id];
            $where[]=['del_time', '=', 0];
            $where[]=['village_id','=',$village_id];
            $list = (new HouseProgrammeService())->programmeEdit($where,['status'=>4,'del_time'=>time()]);
            //删除权限方案 同步删除权限方案标签
            (new HouseProgrammeService())->programmeRelationDel([['village_id','=',$village_id],['pid','=',$id]]);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$list){
            return api_output_error(1001,'数据不存在');
        }else{
            return api_output(0,$list);
        }
    }

}