<?php


namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageGridService;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseUserLogService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\ParkService;
use app\community\model\service\PartyMemberService;
use app\community\model\service\TaskReleaseService;

class GridCustomController extends CommunityBaseController
{
    /**
     * 获取网格员列表
     * @author lijie
     * @date_time 2020/12/19
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridCustomList()
    {
        $street_id = $this->adminUser['area_id'];
        $page = $this->request->post('page',0);
        if(!$street_id || !$page){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_area_street = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $service_area_street->getAreaStreet($where_street);
        if ($street_info && $street_info['area_type']==1){
            $street_id = $street_info['area_pid'];
        }
        $select_option = $this->request->post('select_option','');
        $con = $this->request->post('con','');

        $str_sql='';
        if($select_option && $con){
            $str_sql = " AND `w`.`{$select_option}` LIKE '%{$con}%' ";
        }
        if($this->adminUser['area_type'] == 1){//社区
            $where_sql='w.work_status<4 AND g.area_id = '.$street_id.' and g.business_type = 2 and g.business_id = '.$this->adminUser['area_id'].$str_sql;
        }else{//街道
            $where_sql='(w.work_status<4 AND g.area_id = '.$street_id.' and g.business_type = 0 '.$str_sql.') or (w.work_status<4 AND g.area_id = '.$street_id.' and g.business_type = 1 '.$str_sql.')';
        }
        $service = new HouseVillageGridService();
        $field = 'g.id,w.work_name as name,w.work_phone as phone,w.work_addr as address,w.work_id_card as id_card,g.business_id,g.business_type,g.community_id';
        $data = $service->getGridMemberList($where_sql,$field,$page,15,'g.id DESC');
        $data['total_limit'] = 15;
        $data['area_type'] = $street_info['area_type'];
        if($data['list']){
            foreach ($data['list'] as $kk=>$vv){
                if(isset($vv['phone']) && !empty($vv['phone'])){
                    $vv['phone']=phone_desensitization($vv['phone']);
                }
            }
        }
        return api_output(0,$data);
    }

    /**
     * 添加网格员
     * @author lijie
     * @date_time 2020/12/19
     * @return \json
     */
    public function addGridCustom()
    {
        $street_id = $this->adminUser['area_id'];
        $arr = $this->request->post('workers_id_arr',[]);
        $org_id = $this->request->post('community_id','','trim');
        if(empty($arr)){
            return api_output_error(1001,'必传参数缺失');
        }
        if(empty($org_id)){
            return api_output_error(1001,'请选择社区');
        }
        try{
            $list=(new HouseVillageGridService())->addAllGirdCustom($street_id,$arr,$org_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
      /*  if(isset($postParams['system_type'])){
            unset($postParams['system_type']);
        }
        if(isset($postParams['workers_id_arr'])){
            $service = new HouseVillageGridService();
            foreach ($postParams['workers_id_arr'] as $v){
                $data['workers_id'] = $v['worker_id'];
                $data['name'] = $v['work_name'];
                $data['area_id'] = $street_id;
                $data['phone'] = $v['work_phone'];
                $data['address'] = $v['work_addr'];
                $data['id_card'] = $v['work_id_card'];
                $data['avatar'] = $v['work_head'];
                $data['create_time'] = time();
                $res = $service->addGirdCustom($data);
            }
            return api_output(0,[]);
        }
        unset($postParams['id']);
        $postParams['create_time'] = time();
        $postParams['area_id'] = $street_id;
        $service = new HouseVillageGridService();
        $res = $service->addGirdCustom($postParams);
        if($res)
            return api_output(0,[]);
        else
            return api_output_error(1001,'服务器异常');*/
    }

    /**
     * 更新网格员
     * @author lijie
     * @date_time 2020/12/19
     * @return \json
     */
    public function saveGridCustom()
    {
        $postParams = $this->request->post();
        $where['id'] = $postParams['id'];
        unset($postParams['id']);
        $service = new HouseVillageGridService();
        $res = $service->saveGirdCustom($where,$postParams);
        return api_output(0,[]);
    }

    /**
     * 获取网格员详情
     * @author lijie
     * @date_time 2020/12/19
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridCustomDetail()
    {
        $id = $this->request->post('id',0);
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service = new HouseVillageGridService();
        $data = $service->getGridCustomDetail(['id'=>$id]);
        if(isset($data['avatar'])&&$data['avatar']) {
            $data['avatar'] = dispose_url($data['avatar']);
        } else {
            $data['avatar'] = cfg('site_url') . '/static/images/user_avatar.jpg';
        }
        if($data && isset($data['phone']) && !empty($data['phone'])){
            $data['phone']=phone_desensitization($data['phone']);
        }
        return api_output(0,$data);
    }

    /**
     * 删除网格员
     * @author lijie
     * @date_time 2021/02/22
     * @return bool|\json
     * @throws \Exception
     */
    public function delGridCustom()
    {
        $id = $this->request->post('id',0);  //删除
        $workers_id = $this->request->post('workers_id',0);  //移除
        if(!$id && !$workers_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service = new HouseVillageGridService();
        if($id){
            $where['id'] = $id;
        }else{
            $where['workers_id'] = $workers_id;
        }
        (new TaskReleaseService())->taskUnboundWorker($where);
        $res = $service->delGridCustom($where);
        return api_output(0,[]);
    }

    /**
     * 获取街道/社区的中心点坐标
     * @author lijie
     * @date_time 2020/12/22
     * @return \json
     */
    public function getStreetDetail()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_area_street = new AreaStreetService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'long,lat,area_name,phone');
        return api_output(0,$data);
    }

    /**
     * 添加街道/社区网格
     * @author lijie
     * @date_time 2020/12/22
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addGridRange()
    {
        $street_id = $this->adminUser['area_id'];
        $manage_range_polygon = $this->request->post('manage_range_polygon','');
        $zoom = $this->request->post('zoom','');
        $lng = $this->request->post('lng','');
        $lat = $this->request->post('lat','');
        $area_id = $this->request->post('area_id',0);  //社区id
        $village_id = $this->request->post('village_id',0);  //小区id
        $single_id = $this->request->post('single_id',0);  //楼栋id
        $grid_member_id = $this->request->post('grid_member_id',0); //网格员id
        $polygon_name = $this->request->post('polygon_name','');
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if(empty($manage_range_polygon) || empty($zoom)){
            return api_output_error(1001,'未绘制任何区域');
        }
        if (empty($polygon_name)){
            return api_output_error(1001,'请先填写网格名');
        }
        $community_id=0;
        $service_area_street = new AreaStreetService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type,area_pid');
        if($data['area_type'] == 1){
            $community_id = $street_id;
            $street_id = $data['area_pid'];
        }
        $service = new HouseVillageGridService();
        /*$rtn = $service->getGridRangeType([['b.zoom','<',$zoom],['b.f_street_id','=',$street_id]],'b.type,a.bind_id,a.manage_range_polygon','b.zoom DESC',$polygon_center_code);
        $type = $rtn['type'] +1;*/
        $f_single_ids='';
        $f_street_id = $street_id;
        if ($village_id>0 && !empty($single_id)){
            $type = 4;
            $f_street_id = $street_id;
            $f_area_id = $area_id;
            $f_village_id = $village_id;
            $fid= $village_id;
            if(is_array($single_id)){
                $f_single_id = $single_id['0'];
                $bind_id = $single_id['0'];
                $f_single_ids=implode(',',$single_id);
            }else{
                $f_single_id = $single_id;
                $bind_id = $single_id;
            }

        }else if($area_id>0 && $village_id>0){
            $type = 3;
            $f_street_id = $street_id;
            $f_area_id = $area_id;
            $f_village_id = $village_id;
            $f_single_id = 0;
            $fid = $area_id;
            $bind_id = $village_id;
        }elseif($area_id>0){
            $type = 2;
            $f_street_id = $street_id;
            $f_area_id = $area_id;
            $f_village_id = 0;
            $f_single_id = 0;
            $fid = $street_id;
            $bind_id = $area_id;
        }else{
            if($data['area_type'] == 1){
                $type = 2;
                $bind_id = $community_id;
            }else{
                $type = 1;
                $bind_id = $street_id;
            }
            $f_street_id = $street_id;
            $f_area_id = 0;
            $f_village_id = 0;
            $f_single_id = 0;
            $fid = 0;
        }
        $postData['type'] = $type;
        $postData['grid_member_id'] = $grid_member_id;
        $postData['manage_range_polygon'] = $manage_range_polygon;
        $postData['bind_id'] = $bind_id;
        $postData['fid'] = $fid;
        $postData['f_street_id'] = $f_street_id;
        $postData['f_area_id'] = $f_area_id;
        $postData['f_village_id'] = $f_village_id;
        $postData['f_single_id'] = $f_single_id;
        $postData['polygon_name'] = $polygon_name;
        $postData['create_time'] = time();
        if($f_single_ids){
            $postData['f_single_ids']=$f_single_ids;
        }
        $res = $service->addGridRange($postData);
        if($res){
            $data = $service->getGridCenter(['bind_id'=>$postData['bind_id'],'type'=>$type]);
            if(empty($data)){
                $insertData['type'] = $postData['type'];
                $insertData['bind_id'] = $postData['bind_id'];
                $insertData['lng'] = $lng;
                $insertData['lat'] = $lat;
                $insertData['zoom'] = $zoom;
                $insertData['add_time'] = time();
                $service->addGridCenter($insertData);
            }
        }
        return api_output(0,[]);
    }

    /**
     * 获取当前绘制区域所有街道/社区
     * @author lijie
     * @date_time 2020/12/31
     * @return \json
     */
    public function getNowType()
    {
        $street_id = $this->adminUser['area_id'];
        $service_area_street = new AreaStreetService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type,area_pid');
        if($data['area_type'] == 1)
            $street_id = $data['area_pid'];
        $zoom = $this->request->post('zoom','');
        $polygon_center_code = $this->request->post('polygon_center_code',0);
        $service = new HouseVillageGridService();
        $rtn = $service->getGridRangeType([['b.zoom','<',$zoom],['b.f_street_id','=',$street_id]],'b.type,a.bind_id,a.manage_range_polygon','b.zoom DESC',$polygon_center_code);
        $type = $rtn['type'] +1;
        $res['type'] = $type;
        return api_output(0,$res);
    }

    /**
     * 查找网格
     * @author lijie
     * @date_time 2020/12/22
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridRange()
    {
        $street_id = $this->adminUser['area_id'];
        $grid_member_id = $this->request->post('grid_member_id',0);
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_area_street = new AreaStreetService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type,area_pid');
        $f_area_id=0;
        if($data['area_type'] == 1){
            $f_area_id=$street_id;
            $type = 2;
            $street_id = $data['area_pid'];
        }else{
            $type = 1;
        }
        $service = new HouseVillageGridService();
        $where = array();
        $where[]=['f_street_id','=',$street_id];
        if($type==1){

        }else{
            $where[]=['f_area_id' ,'=', $f_area_id];
        }
        if($grid_member_id){
            $where[]=['grid_member_id' ,'=', $grid_member_id];
        }
        $data = $service->getGridRangeLists($where);
        if($data){
            foreach ($data as &$v){
                $v['manage_range_polygon'] = explode('|',$v['manage_range_polygon']);
                $info = $service->getGridCenter(['bind_id'=>$street_id,'type'=>$type],'zoom');
                $v['zoom'] = $info['zoom'];
            }
        }
        $res['data'] = $data;
        $res['type'] = $type;
        return api_output(0,$res);
    }

    /**
     * 删除网格
     * @author lijie
     * @date_time 2020/12/22
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delGridRange()
    {
        $street_id = $this->adminUser['area_id'];
        $manage_range_polygon = $this->request->post('manage_range_polygon','');
        if(!$street_id || empty($manage_range_polygon)){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_area_street = new AreaStreetService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type,area_pid');
        $community_id=0;
        if($data['area_type'] == 1){
            $community_id = $street_id;
            $street_id = $data['area_pid'];
        }
        $service = new HouseVillageGridService();
        $whereArr=[['r.f_street_id','=',$street_id],['r.manage_range_polygon','like','%'.$manage_range_polygon.'%']];
        if($community_id>0){
            $whereArr[]=['r.f_area_id','=',$community_id];
        }
        $info = $service->getGridRange($whereArr,'r.*');
        if($info){
            $where=array();
            $where[]=['id','=',$info['id']];
            $where[]=['f_street_id','=',$street_id];
            if($community_id >0){
                $where[] = ['f_area_id','=',$community_id];
            }elseif ($info['type'] == 3){
                $where[] = ['f_village_id','=',$info['f_village_id']];
            }else if($info['type'] == 4){
                $where[] = ['f_single_id','=',$info['f_single_id']];
                $where[] = ['manage_range_polygon','like','%'.$manage_range_polygon.'%'];
            }
           // $info = $service->getGridRangeLists($where);
            $res = $service->delGridRange($where);
            /*
            if($res){
                foreach ($info as $v){
                    $data = $service->getGridRange(['r.bind_id'=>$v['bind_id'],'r.type'=>$v['type'],'r.f_street_id'=>$v['f_street_id']],'r.*');
                    if(empty($data)){
                        $service->delGridCenter(['bind_id'=>$v['bind_id'],'type'=>$v['type']]);
                    }
                }
            }
            */
        }
        return api_output(0,[]);
    }

    /**
     * 获取网格信息
     * @author lijie
     * @date_time 2021/02/20
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridRangeInfo()
    {
        $id = $this->request->post('id',0);
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village_grid = new HouseVillageGridService();
        $data = $service_house_village_grid->getGridRange(['r.id'=>$id],'r.*,m.phone');
        if($data && !$data->isEmpty()){
            $data=$data->toArray();
            $data['xkey']='';
            if($data['type']==1){
                $data['xkey']='street_area';
            }elseif($data['type']==2){
                $data['xkey']='community_area';
            }elseif($data['type']==3){
                $data['xkey']='village_area';
            }elseif($data['type']==4){
                $data['xkey']='single_area';
            }
            if(isset($data['f_single_ids']) && !empty($data['f_single_ids'])){
                $f_single_ids=explode(',',$data['f_single_ids']);
                $f_single_ids[]=$data['f_single_id'];
                $f_single_ids=array_unique($f_single_ids);
                $f_single_ids=array_map('intval',$f_single_ids);
                $data['f_single_id']=$f_single_ids;
            }
        }
        return api_output(0,$data);
    }

    /**
     * 获取街道下社区列表
     * @author lijie
     * @date_time 2020/12/28
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAreaList()
    {
        $street_id = $this->adminUser['area_id'];
        $service_area_street = new AreaStreetService();
        $area_info = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type,area_pid');
        if($area_info['area_type'] == 1)
            $data = $service_area_street->getStreetLists(['area_id'=>$street_id],'area_id,area_name',0);
        else
            $data = $service_area_street->getStreetLists(['area_pid'=>$street_id],'area_id,area_name',0);
        return api_output(0,$data);
    }

    /**
     * 获取社区下小区列表
     * @author lijie
     * @date_time 2020/01/04
     * @return \json
     */
    public function getVillageList()
    {
        $area_id = $this->request->post('area_id',0);
        if(!$area_id)
            return api_output_error(1001,'必传参数缺失');
        $service_house_village = new HouseVillageService();
        $data = $service_house_village->getList(['community_id'=>$area_id,'status'=>1],'*','','','',0);
        return api_output(0,$data);
    }

    /**
     * 获取小区下楼栋列表
     * @author lijie
     * @date_time 2020/01/04
     * @return \json
     */
    public function getSingleList()
    {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $service_house_village = new HouseVillageService();
        $data = $service_house_village->getSingleList(['village_id'=>$village_id,'status'=>1],'id as single_id,single_name');
        return api_output(0,$data);
    }

    /**
     * 获取网格员列表-不分页
     * @author lijie
     * @date_time 2020/12/28
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridMember()
    {
        $service = new HouseVillageGridService();
        $field = 'g.id,w.work_name as name,w.work_phone as phone,w.work_addr as address,w.work_id_card as id_card';
        $street_id = $this->adminUser['area_id'];
        $xkey = $this->request->post('xkey','');
        $area_id = $this->request->post('area_id',0);
        $area_id = !empty($area_id) ? intval($area_id):0;
        $community_id=0;
        if(!empty($area_id)){
            $community_id= intval($area_id);
        }
        $xkey =trim($xkey);
        $service_area_street = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id', '=', $street_id];
        $street_info = $service_area_street->getAreaStreet($where_street);
        if ($street_info && $street_info['area_type']==1){
            $community_id=$street_id;
            $street_id = $street_info['area_pid'];
        }
        $whereArr=array();
        $whereArr[]=['g.area_id','=',$street_id];
        if($street_info['area_type']<1 && (empty($xkey) || $xkey=='street_area')){
            $whereArr[]=['g.community_id','=',0];
        }else if($community_id>0){
            $whereArr[]=['g.community_id','=',$community_id];
        }else{
            $whereArr[]=['g.community_id','>',0];
        }
        $whereArr[]=['w.work_status','<',4];
        $data = $service->getGridMember($whereArr,$field,0,500,'id DESC');
        return api_output(0,$data);
    }

    /**
     * 获取离当前层级最近的网格
     * @author lijie
     * @date_time 2020/12/30
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getZoomLastGrid()
    {
        $street_id = $this->adminUser['area_id'];
        $street_ids = $this->request->post('street_id',0,'intval');
        if($street_ids){
            $street_id=$street_ids;
        }
        $service_area_street = new AreaStreetService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type,area_pid');
        if($data['area_type'] == 1){
            $community_id = $street_id;
            $street_id = $data['area_pid'];
        }
        $zoom = $this->request->post('zoom',0);
        $str_lng_lat = $this->request->post('str_lng_lat','');
        $is_jian = $this->request->post('is_jian',0);
        if(!$zoom || empty($str_lng_lat))
            return api_output(0,[]);
        $service_house_village_grid = new HouseVillageGridService();
        $info = $service_house_village_grid->getGridRange([['r.manage_range_polygon','like','%'.$str_lng_lat.'%']],'r.*,c.zoom');
        if(empty($info))
            return api_output(0,[]);
        if($is_jian == false){
            if($info['zoom'] >= $zoom){
                return api_output(0,[]);
            }
        }
        $where = [];
        if($info['type'] == 1 && $data['area_type'] == 1)
            $where[] = ['a.bind_id','=',$community_id];
        $where[] = ['a.f_street_id','=',$street_id];
        if(!$is_jian){
            $where[] = ['a.fid','=',$info['bind_id']];
            $where[] = ['a.type','>',$info['type']];
        } else{
            $where[] = ['a.bind_id','=',$info['fid']];
            $where[] = ['a.type','<',$info['type']];
        }
        $where[] = ['b.zoom','>=',$zoom];
        if($data['area_type'] == 1)
            $where[] = ['a.type','<>',1];
        $field = 'a.type,a.manage_range_polygon,b.zoom';
        $order = 'b.zoom ASC';
        $data = $service_house_village_grid->getLastGrid($where,$field,$order);
        return api_output(0,$data);
    }

    /**
     * 单机获取绘制区域信息
     * @author lijie
     * @date_time 2020/01/07
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function showInfo()
    {
        $street_id = $this->adminUser['area_id'];
        $street_ids = $this->request->post('street_id',0,'intval');
        if($street_ids){
            $street_id=$street_ids;
        }
        $service_area_street = new AreaStreetService();
        $service_house_village = new HouseVillageService();
        $data = $service_area_street->getAreaStreet(['area_id'=>$street_id],'area_type,area_pid');
        if($data['area_type'] == 1)
            $street_id = $data['area_pid'];
        $str_lng_lat = $this->request->post('str_lng_lat','');
        if(empty($str_lng_lat))
            return api_output_error(1001,'必传参数缺失');
        $service_house_village_grid = new HouseVillageGridService();
        $info = $service_house_village_grid->getGridRange([['r.manage_range_polygon','like','%'.$str_lng_lat.'%']],'r.*');
        if(empty($info))
            return api_output(0,[]);
        if($info['type'] == 1 || $info['type'] == 2)
            $data = $service_area_street->getAreaStreet(['area_id'=>$info['bind_id']],'area_name,phone');
        $grid_member_info = $service_house_village_grid->getGridCustomDetail(['id'=>$info['grid_member_id']],'name,phone');
        $data['grid_name'] = $grid_member_info['name'];
        $data['grid_phone'] = $grid_member_info['phone'];
        $data['polygon_name'] = $info['polygon_name'];
        $data['id'] = $info['id'];
        if($info['type'] == 1){
            $service_house_village_user_bind = new HouseVillageUserBindService();
            $count = $service_house_village_user_bind->getStreetUserBindCount(['v.street_id'=>$street_id]);
            $data['type'] = 1;
            $data['count'] = $count;
        }elseif($info['type'] == 2){
            $data['type'] = 2;
        }elseif($info['type'] == 3){
            $data['type'] = 3;
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$info['bind_id']],'village_name,community_id,property_phone');
            $area_info = $service_area_street->getAreaStreet(['area_id'=>$village_info['community_id']],'area_name,phone');
            $village_people_count = $service_house_village->getVillageUserNum([['village_id','=',$info['bind_id']],['status','in','1,2']]);
            $data['area_name'] = $area_info['area_name'];
            $data['phone'] = $area_info['phone'];
            $data['village_name'] = $village_info['village_name'];
            $data['village_id'] = $info['bind_id'];
            $data['property_phone'] = $village_info['property_phone'];
            $data['village_people_count'] = $village_people_count;
        }else{
            $single_info = $service_house_village->getSingleVillageName(['s.id'=>$info['bind_id']],'s.single_name,v.village_name,v.community_id,s.id,v.street_id');
            $area_info = $service_area_street->getAreaStreet(['area_id'=>$single_info['community_id']],'area_name,phone');
            $street_info = $service_area_street->getAreaStreet(['area_id'=>$single_info['street_id']],'area_name,phone');
            $single_name=$single_info['single_name'];
            $data['single_id'] = $single_info['id'];
            if(isset($info['f_single_ids']) && !empty($info['f_single_ids']) && strpos($info['f_single_ids'],',')){
                $f_single_ids=explode(',',$info['f_single_ids']);
                $data['single_id']=array();
                $singleListObj = $service_house_village->getSingleList(array(['id','in',$f_single_ids]),'id,single_name');
                if($singleListObj){
                    $single_name_arr=array();
                    foreach ($singleListObj as $singV){
                        $single_name_arr[]=$singV['single_name'];
                        $data['single_id'][]=$singV['id'];
                    }
                    $single_name=implode('，',$single_name_arr);
                }
            }
            $data['single_name'] = $single_name;
            $data['village_name'] = $single_info['village_name'];
            $data['area_name'] = $area_info['area_name'];
            $data['phone'] = $area_info['phone'];
            $data['type'] = 4;
            
            $data['street_name'] = $street_info['area_name'];
        }
        return api_output(0,$data);
    }

    /**
     * 获取小区开门记录
     * @author lijie
     * @date_time 2021/01/12
     * @return \json
     */
    public function getOpenDoorList()
    {
        $village_id = $this->request->post('village_id',0);
        $floor_id = $this->request->post('floor_id',0);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('results','5');
        $service_house_user_log = new HouseUserLogService();
        $where[] = ['f.village_id','=',$village_id];
        $where[] = ['f.is_del','=',0];
        $where[] = ['l.log_from','in',[1,2,6]];
        if($floor_id)
            $where[] = ['l.log_more_id','=',$floor_id];
        $field = 'l.log_name,l.log_detail,l.log_time,ub.phone,f.device_name,l.log_status,ub.name,l.log_id';
        $res = $service_house_user_log->getDoorLogList($where,$field,$village_id,$page,$limit,$floor_id);
        return api_output(0,$res);
    }

    /**
     * 获取楼栋和单元名
     * @author lijie
     * @date_time 2020/08/18 14:27
     * @return \json
     */
    public function getFloorInfo()
    {
        $single_id = $this->request->post('single_id',0);
        if(!$single_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $whereArr=array(['f.status','=',1]);
        if(is_array($single_id)){
            $whereArr[]=array('f.single_id','in',$single_id);
        }else{
            $whereArr[]=array('f.single_id','=',$single_id);
        }
        $service_house_village = new HouseVillageService();
        $data = $service_house_village->getFloorInfo($whereArr,'f.floor_id,f.single_id,f.floor_name,f.village_id,s.single_name','s.id DESC');
        if($data){
            $data = $data->toArray();
        }
        return api_output(0,$data);
    }

    /**
     * 获取楼栋信息
     * @author lijie
     * @date_time 2020/01/12
     * @return \json
     */
    public function getSingleInfo()
    {
        $single_id = $this->request->post('single_id',0);
        if(!$single_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $g_id=$this->request->post('g_id',0,'intval');
        if(!is_array($single_id)){
            $single_id=array($single_id);
        }
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $service_house_village_grid = new HouseVillageGridService();
        $single_info = $service_house_village->get_house_village_single_where(array(['id','in',$single_id]),'single_name,sum(measure_area) as sum,sum(upper_layer_num)  as upper_layer_num');
        $count = $service_house_village_user_bind->getUserCount(array(['single_id','in',$single_id]));
        $floor_count = $service_house_village->getFloorCount(array(['single_id','in',$single_id],['status','=',1]));
        $room_count = $service_house_village_user_vacancy->getRoomCount(array(['single_id','in',$single_id],['is_del','=',1]));
        
        $whereArr=array(['a.type','=',4]);
        if($g_id>0){
            $whereArr[]=array('a.id','=',$g_id);
        }else{
            $whereArr[]=array('a.bind_id','in',$single_id);
        }
        $gird_info = $service_house_village_grid->getSingleGridMemberInfo($whereArr,'b.name,b.phone');
        $single_name=$single_info['single_name'];
        $singleListObj = $service_house_village->getSingleList(array(['id','in',$single_id]),'id,single_name');
        if($singleListObj){
            $single_name_arr=array();
            foreach ($singleListObj as $singV){
                $single_name_arr[]=$singV['single_name'];
                $data['single_id'][]=$singV['id'];
            }
            $single_name=implode('，',$single_name_arr);
        }
        
        $data['single_name'] = $single_name;
        $data['measure_area'] = $single_info['measure_area'];
        $data['upper_layer_num'] = $single_info['upper_layer_num'];
        $data['people_count'] = $count;
        $data['floor_count'] = $floor_count;
        $data['room_count'] = $room_count;
        $data['grid_name'] = $gird_info['name'];
        $data['grid_phone'] = $gird_info['phone'];
        return api_output(0,$data);
    }

    /**
     * 获取单元下楼层信息
     * @author lijie
     * @date_time 2020/01/12
     * @return \json
     */
    public function getLayerInfo()
    {
        $floor_id = $this->request->post('floor_id',0);
        if(!$floor_id)
            return api_output_error(1001,'必传参数缺失');
        $service_house_village = new HouseVillageService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
        $data = $service_house_village->getLayerInfo(['floor_id'=>$floor_id,'status'=>1],'id,layer_name');
        $info = $service_house_village_user_vacancy->getRoomCount(['floor_id'=>$floor_id,'is_del'=>0],'house_type');
        $home = $store = $com = 0;
        if($info){
            foreach ($info as $v){
                if($v['house_type'] == 1)
                    $home = $v['count'];
                if($v['house_type'] == 2)
                    $store = $v['count'];
                if($v['house_type'] == 3)
                    $com = $v['count'];
            }
        }
        $res['list'] = $data;
        $res['home'] = $home;
        $res['store'] = $store;
        $res['com'] = $com;
        return api_output(0,$res);
    }

    /**
     * 获取房间下住户信息
     * @author lijie
     * @date_time 2020/01/12
     * @return \json
     */
    public function getRoomUserList()
    {
        $vacancy_id = $this->request->post('vacancy_id',0);
        if(!$vacancy_id)
            return api_output_error(1001,'必传参数缺失');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('page',10);
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $data = $service_house_village_user_bind->getRoomUserBindList(['vacancy_id'=>$vacancy_id,'status'=>1],'name,phone,single_id,floor_id,layer_id,vacancy_id,village_id,type,relatives_type,pigcms_id',$page,$limit,'pigcms_id DESC',$vacancy_id);
        return api_output(0,$data);
    }

    /**
     * 小区车辆进出场记录
     * @author lijie
     * @date_time 2020/01/15
     * @return \json|mixed
     */
    public function getInOutRecord()
    {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('results','5');
        $service_park = new ParkService();
        $data = $service_park->getInOutRecord(['v.village_id'=>$village_id],'p.car_number',$page,$limit,'p.id DESC');
        return api_output(0,$data);
    }

    /**
     * 住户信息
     * @author lijie
     * @date_time 2020/01/15
     * @return \json
     */
    public function getUserInfo()
    {
        $street_id = $this->adminUser['area_id'];
        $area_type = isset($this->adminUser['area_type']) ? $this->adminUser['area_type'] : 0;
        $street_ids = $this->request->post('street_id',0,'intval');
        if($street_ids){
            $street_id=$street_ids;
        }
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyMember = new PartyMemberService();
        try {
            $data = $servicePartyMember->getInfoOfGrid($id,$street_id,$area_type);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 更新网格名
     * @author lijie
     * @date_time 2020/01/26
     * @return \json
     */
    public function saveRange()
    {
        $id = $this->request->post('id', 0);
        //$polygon_name = $this->request->post('polygon_name','');
        $manage_range_polygon = $this->request->post('str_lng_lat', '');
        $new_str_lng_lat = $this->request->post('new_str_lng_lat', '');
        $paramsPost = $this->request->post();
        if (isset($paramsPost['system_type'])) {
            unset($paramsPost['system_type']);
        }
        $street_id = $this->adminUser['area_id'];
        if (!$id && empty($manage_range_polygon))
            return api_output_error(1001, '必传参数缺失');
        $service_house_village_grid = new HouseVillageGridService();
        if ($manage_range_polygon) {
            $res = $service_house_village_grid->saveRange([['manage_range_polygon', 'like', '%' . $manage_range_polygon . '%']], ['manage_range_polygon' => $new_str_lng_lat]);
        } else {
            $old_bind_id = $paramsPost['old_bind_id'];
            unset($paramsPost['old_bind_id']);
            if (isset($paramsPost['single_id'])) {
                if(is_array($paramsPost['single_id'])){
                    $paramsPost['f_single_id'] = $paramsPost['single_id']['0'];
                    $paramsPost['f_single_ids']= implode(',',$paramsPost['single_id']);
                }else{
                    $paramsPost['f_single_id'] = $paramsPost['single_id'];
                }
                if ($paramsPost['type'] == 4) {
                    $paramsPost['bind_id'] = $paramsPost['f_single_id'];
                }
                unset($paramsPost['single_id']);
            }
            if (isset($paramsPost['village_id'])) {
                $paramsPost['f_village_id'] = $paramsPost['village_id'];
                if ($paramsPost['type'] == 3) {
                    $paramsPost['bind_id'] = $paramsPost['village_id'];
                }
                unset($paramsPost['village_id']);
            }
            if ($paramsPost['type'] == 1) {
                $paramsPost['bind_id']=$street_id;
            }
            if($paramsPost['type']!=1 && (!isset($paramsPost['bind_id']) || empty($paramsPost['bind_id']))){
                return api_output_error(1001, '必传参数bind_id缺失');
            }
            if (isset($paramsPost['bind_id']) && !empty($paramsPost['bind_id'])) {
                $service_house_village_grid->saveGridCenter(['type' => $paramsPost['type'], 'bind_id' => $old_bind_id], ['bind_id' => $paramsPost['bind_id']]);
            }
            $res = $service_house_village_grid->saveRange(['id' => $id], $paramsPost);
        }
        return api_output(0, []);
    }

    public function  getBindType(){
        $area_type = $this->adminUser['area_type'];;   //0 街道/乡镇 1 社区/村
        $area_type = intval($area_type);
        try {
            $service_house_village_grid = new HouseVillageGridService();
            $retdata = $service_house_village_grid->getBindTypeData($area_type);
            return api_output(0, $retdata);
        }catch (\Exception  $e){
                return api_output_error(-1, $e->getMessage());
         }
    }
    /**
     * 批量修改空的网格名称
     * @author:zhubaodi
     * @date_time: 2022/5/16 9:33
     */
    public function test()
    {
        $service = new HouseVillageGridService();
        $res = $service->saveRange1();
        return api_output(0,$res);
    }
}