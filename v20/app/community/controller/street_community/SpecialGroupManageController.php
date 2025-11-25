<?php


namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageUserLabelService;
use app\community\model\service\HouseVillageUserLabelRecordService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\AreaStreetService;

class SpecialGroupManageController extends CommunityBaseController
{
    /**
     * 获取街道困难群体
     * @author lijie
     * @date_time 2020/10/16
     * @return \json
     */
    public function getUserVulnerableGroupsList()
    {
        if ($this->adminUser['area_type']==1) {
            $street_id = 0;
            $community_id = $this->adminUser['area_id'];
        } else {
            $street_id = $this->adminUser['area_id'];
            $community_id = 0;
        }
        $type = $this->request->post('type',0);
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $select_option = $this->request->post('select_option',1);
        $type_name = $this->request->post('type_name','');
        if(!$type){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!$community_id && !$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if($type == 2){
            $service_house_village_user_bind = new HouseVillageUserBindService();
            if($street_id) {
                $where[] = ['v.street_id','=',$street_id];
            }
            if($community_id) {
                $where[] = ['v.community_id','=',$community_id];
            }
            if($this->request->post('con','')){
                if($select_option == 'name')
                    $where[] = ['hvb.name','like','%'.$this->request->post('con','').'%'];
                else
                    $where[] = ['hvb.phone','like','%'.$this->request->post('con','').'%'];
            }
            $field = 'hvb.pigcms_id,hvb.name,hvb.phone,hvb.address,hvb.authentication_field,u.cardid,hvb.single_id,hvb.floor_id,hvb.layer_id,hvb.vacancy_id,hvb.village_id,v.village_name';
            $service_area_street = new AreaStreetService();
            if($street_id){
                $info = $service_area_street->getAreaStreet(['area_id'=>$street_id],'age');
            } else{
                $info = $service_area_street->getAreaStreet(['area_id'=>$community_id],'age');
            }
            if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
                $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
                if(!empty($village_ids)){
                    $where[] = ['hvb.village_id','in',$village_ids];
                }
            }
            $user_lists = $service_house_village_user_bind->getUserBindList($where,'pigcms_id,id_card,village_name',0,$limit,$order='hvb.pigcms_id DESC');
            $user_lists = $user_lists['list'];
            foreach ($user_lists as $K=>$V){
                if(empty($V['id_card'])){
                    unset($user_lists[$K]);
                    continue;
                }else{
                    $age = $this->getAgeByID($V['id_card']);
                    if($age<$info['age']){
                        unset($user_lists[$K]);
                        continue;
                    }
                }
            }
            $pigcms_ids = [];
            foreach ($user_lists as $K1=>$V1){
                $pigcms_ids[] = $V1['pigcms_id'];
            }
            $where[] = ['hvb.pigcms_id','in',$pigcms_ids];
            $where[] = ['hvb.type','<>',4];
            $where[] = ['hvb.status','<>',4];
            $where[] = ['v.status','<>',5];

            $data = $service_house_village_user_bind->getUserBindList($where,$field,$page,10,$order='hvb.pigcms_id DESC');
            $data['total_limit'] =$limit;
            return api_output(0,$data);
        }
        $service_house_village_user_label = new HouseVillageUserLabelService();
        if($this->request->post('con','')){
            if($select_option == 'name')
                $where[] = ['b.name','like','%'.$this->request->post('con','').'%'];
            else
                $where[] = ['b.phone','like','%'.$this->request->post('con','').'%'];
        }
        if($type == 1){
            if($type_name)
                $where[] = ['l.user_vulnerable_groups','like','%'.$type_name.'%'];
            else
                $where[] = ['l.user_vulnerable_groups','<>',''];
        }
        elseif($type == 3){
            if($type_name)
                $where[] = ['l.user_focus_groups','like','%'.$type_name.'%'];
            else
                $where[] = ['l.user_focus_groups','<>',''];
        }
        elseif ($type == 4){
            if($type_name)
                $where[] = ['l.user_special_groups','like','%'.$type_name.'%'];
            else
                $where[] = ['l.user_special_groups','<>',''];
        }
        if($street_id) {
            $where[] = ['v.street_id','=',$street_id];
        }
        if($community_id) {
            $where[] = ['v.community_id','=',$community_id];
        }
        if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
            $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            if(!empty($village_ids)){
                $where[] = ['b.village_id','in',$village_ids];
            }
        }
        $where[] = ['b.status','<>',4];
        $field = 'b.name,b.phone,b.address,l.id,l.user_vulnerable_groups,l.bind_id,l.user_focus_groups,l.user_special_groups,b.pigcms_id,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,b.village_id,v.village_name';
        $data = $service_house_village_user_label->getStreetUser($where,$field,$page,$limit,'l.id DESC',$type);
        $data['total_limit'] = $limit;
        return api_output(0,$data);
    }


    /**
     * 获取跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpecialGroupsRecordList()
    {
        $bind_id = $this->request->post('bind_id',0);
        $type = $this->request->post('type',0);
        if(!$bind_id || !$type){
            return api_output_error(1001,'必传参数缺失');
        }
        $page = $this->request->post('page',1);
        $service_house_village_user_label_record = new HouseVillageUserLabelRecordService();
        $where['bind_id'] = $bind_id;
        $where['type'] = $type;
        $where['status'] = 1;
        $field = 'title,content,add_time,id';
        $data = $service_house_village_user_label_record->getRecordList($where,$field,$page,10,'id DESC');
        $data['total_limit'] = 10;
        return api_output(0,$data);
    }

    /**
     *添加跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @return \json
     */
    public function addSpecialGroupsRecord()
    {
        $post_datas = $this->request->post();
        unset($post_datas['system_type']);
        if(empty($post_datas['title']) || empty($post_datas['content']) || empty($post_datas['type']) || empty($post_datas['bind_id']))
            return api_output_error(1001,'必传参数缺失');
        $post_datas['add_time'] = time();
        $service_house_village_user_label_record = new HouseVillageUserLabelRecordService();
        $res = $service_house_village_user_label_record->addRecord($post_datas);
        if($res)
            return api_output(0,'','添加成功');
        return api_output_error(-1,'服务异常');
    }



    /**
     * 删除跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @return \json
     */
    public function delSpecialGroupsRecord()
    {
        $post_datas = $this->request->post();
        unset($post_datas['system_type']);
        $service_house_village_user_label_record = new HouseVillageUserLabelRecordService();
        $res = $service_house_village_user_label_record->saveRecord($post_datas,['status'=>2]);
        if($res)
            return api_output(0,'','删除成功');
        return api_output_error(-1,'服务异常');
    }

    /**
     * 综治人群信息
     * @author lijie
     * @date_time 2020/10/23
     * @return \json
     */
    public function getGroupDetail()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$pigcms_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $where['hvb.pigcms_id'] = $pigcms_id;
        $field = 'hvb.name,hvb.phone,hvb.address,hvb.id_card as cardid,hvb.authentication_field,hvb.single_id,hvb.floor_id,hvb.layer_id,hvb.vacancy_id,hvb.village_id';
        $data = $service_house_village_user_bind->getUserBindInfo($where,$field);
        if($data && isset($data['phone']) && !empty($data['phone'])){
            $data['phone']=phone_desensitization($data['phone']);
        }
        if($data && isset($data['cardid']) && !empty($data['cardid'])){
            $data['cardid']=idnum_desensitization($data['cardid']);
        }
        return api_output(0,$data);
    }

    /**
     * 跟踪记录详情
     * @author lijie
     * @date_time 2020/11/19
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordDetail()
    {
        $record_id = $this->request->post('record_id',0);
        if(!$record_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village_user_label_record = new HouseVillageUserLabelRecordService();
        $data = $service_house_village_user_label_record->getRecordDetail(['id'=>$record_id],'title,content,img');
        $data['img'] = cfg('site_url').$data['img'];
        return api_output(0,$data);
    }

    /**
     * 根据身份证号判断年龄
     * @param $id
     * @return float|int|string
     */
    public  function getAgeByID($id){ //过了这年的生日才算多了1周岁

        if (empty($id)) return '';

        $date = strtotime(substr($id, 6, 8)); //获得出生年月日的时间戳

        $today = strtotime('today'); //获得今日的时间戳

        $diff = floor(($today - $date) / 86400 / 365); //得到两个日期相差的大体年数

        //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比

        $age = strtotime(substr($id, 6,8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

        return $age;

    }

    /**
     * 获取用户标签属性
     * @author: liukezhu
     * @date : 2022/4/23
     * @return \json
     */
    public function getUserLabel(){
        try {
            $list=[];
            $info = (new HouseVillageUserLabelService())->vulnerable_groups_arr;
            if($info){
                foreach ($info as $k=>$v){
                    $list[]=[
                        'key'=>$k,
                        'value'=>$v
                    ];
                }
            }
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }
}