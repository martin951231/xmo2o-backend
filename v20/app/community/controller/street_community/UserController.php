<?php
/**
 * @author : liukezhu
 * @date : 2021/11/5
 */
namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseVillageUserBindService;

class UserController extends CommunityBaseController{


    /**
     * 获取业主列表
     * @author: liukezhu
     * @date : 2021/11/5
     * @return \json
     */
    public function getUerList(){
        $page = $this->request->post('page',0);
        $community_id = $this->request->post('community_id',0);
        $village_id = $this->request->post('village_id',0);
        $single_id = $this->request->post('single_id',0);
        $name = $this->request->post('name','','trim');
        $phone = $this->request->post('phone','','trim');
        $id_card = $this->request->post('id_card','','trim');
        $limit=$this->request->post('limit',10);
//        $whereArea = [];
//        if ($this->adminUser['area_type']==0) {
//            $whereArea[] = ['area_pid','=',$this->adminUser['area_id']];
//        } else {
//            $whereArea[] = ['area_id','=',$this->adminUser['area_id']];
//        }
//        $village_id_all=(new HouseVillageService())->getVillageIds($whereArea,'area_id');
//        $list=[
//            'list'=>[],
//            'count'=>0
//        ];
        $where=[];
        if ($this->adminUser['area_type']==0){
            $where[] = ['v.street_id','=',$this->adminUser['area_id']];
        }else{
            $where[] = ['v.community_id','=',$this->adminUser['area_id']];
        }
        $where[] = ['h.type','in', [0,1,2,3]];
        $where[] = ['h.vacancy_id','>',0];
        $where[] = ['h.status','=',1];
        $where[] = ['v.status', '<>', 5];
        if($community_id > 0){
            $where[] = ['s.area_id','=',$community_id];
        }
        if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
            $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            if(!empty($village_ids)){
                if($village_id > 0 && in_array($village_id,$village_ids)){
                    $where[] = ['v.village_id','=',$village_id];
                }elseif($village_id<1){
                    $where[] = ['v.village_id', 'in', $village_ids];
                }

            }
        }else if($village_id > 0){
            $where[] = ['v.village_id','=',$village_id];
        }
        if($single_id > 0){
            $where[] = ['h.single_id','=',$single_id];
        }
        if(!empty($name)){
            $where[] = ['h.name', 'like', '%'.$name.'%'];
        }
        if(!empty($phone)){
            $where[] = ['h.phone', 'like', '%'.$phone.'%'];
        }
        if(!empty($id_card)){
            $where[] = ['h.id_card', 'like', '%'.$id_card.'%'];
        }
        $where[] = ['v.status','=',1];
        $field='h.pigcms_id,s.area_name,h.name,v.village_name,b.single_name,c.floor_name,y.layer_name,a.room as room_name,h.phone,h.id_card,h.usernum,h.type';
        $order='h.pigcms_id desc';
        $list=(new HouseVillageUserBindService())->getStreetUserBindList($where,$field,$order,$page,$limit);
        if(!empty($list['list'])){
            foreach ($list['list'] as $kk=>$uvv){
                if($uvv['phone']){
                    $list['list'][$kk]['phone']=phone_desensitization($uvv['phone']);
                }
                if($uvv['id_card']){
                    $list['list'][$kk]['id_card']=idnum_desensitization($uvv['id_card']);
                }
            }
        }
        $list['adminUser']=$this->adminUser;
        return api_output(0,$list);
    }



    /**
     * 获取社区数据
     * @author: liukezhu
     * @date : 2021/11/8
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCommunityAll(){
        $where = [];
        if ($this->adminUser['area_type']==0) { //0:街道 1:社区
            $where[] = ['area_pid','=',$this->adminUser['area_id']];
        } else {
            $where[] = ['area_id','=',$this->adminUser['area_id']];
        }
        $list=(new AreaStreetService())->getList($where,'area_id as id,area_name as title');
        $area_id=0;
        if($this->adminUser['area_type'] == 1 && isset($list[0]['id'])){
            $area_id=$list[0]['id'];
        }
        return api_output(0,['list'=>$list,'area_id'=>$area_id]);
    }

    /**
     * 获取小区数据
     * @author: liukezhu
     * @date : 2021/11/8
     * @return \json
     */
    public function getVillageAll(){
        $area_id = $this->request->post('area_id',0);
        $isnotall = $this->request->post('isnotall',0);
        $list=[];
        if(intval($area_id) > 0){
            $where[] = ['community_id','=',$area_id];
            $where[] = ['status','in', [0,1,2]];
            $field='village_id as id ,village_name as title';
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
                if(!empty($village_ids)){
                    $where[] = ['village_id', 'in', $village_ids];
                }
            }
            $list=(new HouseVillageService())->getList($where,$field,1,99999,'village_id desc',0);
            if($list){
                $list=$list->toArray();
            }
            if($isnotall==1){

            }else{
                $list=array_merge([['id'=>0,'title'=>'全部小区']],$list);
            }

        }
        return api_output(0,$list);
    }

    /**
     * 获取楼栋数据
     * @author: liukezhu
     * @date : 2022/1/5
     * @return \json
     */
    public function getSingleAll(){
        $village_id = $this->request->post('village_id',0);
        $isnotall = $this->request->post('isnotall',0);
        $list=[];
        if(intval($village_id) > 0){
            $where[] = ['village_id','=',$village_id];
            $where[] = ['status','in', [1]];
            $field='id,single_name as title';
            $list=(new HouseVillageSingleService())->getList($where,$field,'sort desc,id desc');
            if($list){
                $list=$list->toArray();
            }
            if($isnotall==1){

            }else{
                $list=array_merge([['id'=>0,'title'=>'全部楼栋']],$list);
            }

        }
        return api_output(0,$list);
    }

}