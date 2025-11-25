<?php


namespace app\community\model\service;


use app\community\model\db\AreaStreet;
use app\community\model\db\HouseVillageGridMember;
use app\community\model\db\HouseVillageGridRange;
use app\community\model\db\HouseVillageGridCenter;

class HouseVillageGridService
{
    public $model = '';
    public $modelRange = '';
    public $modelCenter = '';

    public function __construct()
    {
        $this->model = new HouseVillageGridMember();
        $this->modelRange = new HouseVillageGridRange();
        $this->modelCenter = new HouseVillageGridCenter();
    }

    /**
     * 获取网格员列表
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridCustomList($where,$field=true,$page=1,$limit=15,$order='id DESC')
    {
        $data = $this->model->getList($where,$field,$page,$limit,$order);
        $count = $this->model->getCount($where);
        $res['list'] = $data;
        $res['count'] = $count;
        return $res;
    }

    /**
     * 获取网格员列表-不分页
     * @author lijie
     * @date_time 2020/12/28
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridMember($where,$field=true,$page=0,$limit=15,$order='id DESC')
    {
        $data = $this->model->getLists($where,$field,$order,$page,$limit);
        return $data;
    }

    /**
     * 添加网格员
     * @author lijie
     * @date_time 2020/12/19
     * @param $data
     * @return int|string
     */
    public function addGirdCustom($data)
    {
        if(isset($data['system_type'])){
            unset($data['system_type']);
        }
        $res = $this->model->addOne($data);
        return $res;
    }

    //todo 社区人员绑定街道
    public function addAllGirdCustom($street_id,$param,$org_id){
        $str_b=(new HouseProgrammeService())->org_b;
        $str_c=(new HouseProgrammeService())->org_s;
        $business_id=0;$business_type=0;$community_id=0;
        if(strpos($org_id, $str_b) !== false){//街道部门
            $business_type=1;
            $business_id=explode($str_b,$org_id)[1];
        }
        if(strpos($org_id, $str_c) !== false){ //社区
            $business_type=2;
            $community_id=$business_id=explode($str_c,$org_id)[1];

        }
        if(empty($business_id) || empty($business_type)){
            throw new \think\Exception('社区参数不合法');
        }
        $data=[];
        foreach ($param as $v){
            $data[]=[
                'workers_id'=>$v['worker_id'],
                'name'=>$v['work_name'],
                'area_id'=>$street_id,
                'community_id'=>$community_id,
                'business_id'=>$business_id,
                'business_type'=>$business_type,
                'phone'=>$v['work_phone'],
                'address'=>$v['work_addr'],
                'id_card'=>$v['work_id_card'],
                'avatar'=>$v['work_head'],
                'create_time'=>time(),
            ];
        }
        if($data){
            $this->model->addAll($data);
        }
        return true;
    }

    /**
     * 更新网格员
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveGirdCustom($where,$data)
    {
        $res = $this->model->saveOne($where,$data);
        return $res;
    }

    /**
     * 获取网格员详情
     * @author lijie
     * @date_time 2020/12/19
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridCustomDetail($where,$field=true)
    {
        $data = $this->model->getOne($where,$field);
        return $data;
    }

    /**
     * 删除网格员
     * @author lijie
     * @date_time 2021/02/22
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delGridCustom($where)
    {
        $grid_member_info=$this->model->getOne($where);
        $res=0;
        if($grid_member_info && !$grid_member_info->isEmpty()){
            $grid_member_info=$grid_member_info->toArray();
            $res = $this->model->delOne($where);
            if($res){
                $whereArr=array();
                $whereArr[]=array('grid_member_id','=',$grid_member_info['id']);
                $this->modelRange->delOne($whereArr);
            }
        }
        return $res;
    }

    /**
     * 添加网格多边形
     * @author lijie
     * @date_time 2020/12/22
     * @param $data
     * @return int|string
     */
    public function addGridRange($data)
    {
        $res = $this->modelRange->addOne($data);
        return $res;
    }

    /**
     * 删除网格多边形
     * @author lijie
     * @date_time 2020/12/22
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delGridRange($where)
    {
        $res = $this->modelRange->delOne($where);
        return $res;
    }

    /**
     * 查找网格
     * @author lijie
     * @date_time 2020/12/22
     * @param $where
     * @param $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridRange($where,$field=true)
    {
        $res = $this->modelRange->getOne($where,$field);
        return $res;
    }

    public function getGridRangeLists($where,$field=true)
    {
        $res = $this->modelRange->getLists($where);
        return $res;
    }

    /**
     * 增加中心点
     * @author lijie
     * @date_time 2020/12/22
     * @param $data
     * @return int|string
     */
    public function addGridCenter($data)
    {
        $res = $this->modelCenter->addOne($data);
        return $res;
    }

    /**
     * 修改中心点
     * @author lijie
     * @date_time 2021/02/20
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveGridCenter($where,$data)
    {
        $res = $this->modelCenter->saveOne($where,$data);
        return $res;
    }

    /**
     * 删除中心点
     * @author lijie
     * @date_time 2020/12/22
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delGridCenter($where)
    {
        $res = $this->modelCenter->delOne($where);
        return $res;
    }

    /**
     * 查找中心点
     * @author lijie
     * @date_time 2020/12/22
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGridCenter($where,$field=true)
    {
        $data = $this->modelCenter->getOne($where,$field);
        return $data;
    }

    /**
     * 获取最近的层级
     * @author lijie
     * @date_time 2020/12/25
     * @param $where
     * @param bool $field
     * @param string $order
     * @return mixed
     */
    public function getLastGrid($where,$field=true,$order='b.zoom ASC')
    {
        $data = $this->modelRange->getLastOne($where,$field,$order)->toArray();
        $zoom = 0;
        $type = 1;
        if($data){
            foreach ($data as $k=>&$v){
                if($k == 0){
                    $zoom = $v['zoom'];
                    $type = $v['type'];
                }else{
                    if($v['zoom'] > $zoom && $v['type'] != $type){
                        unset($data[$k]);
                        continue;
                    }
                }
                $v['manage_range_polygon'] = explode('|',$v['manage_range_polygon']);
            }
        }
        $data = array_merge($data);
        return $data;
    }

    /**
     * 判断一个坐标是否在一个多边形内（由多个坐标围成的）
     * 基本思想是利用射线法，计算射线与多边形各边的交点，如果是偶数，则点在多边形外，否则
     * 在多边形内。还会考虑一些特殊情况，如点在多边形顶点上，点在多边形边上等特殊情况。
     * @param $point 指定点坐标
     * @param $pts 多边形坐标 顺时针方向
     * @param $point
     * @param $pts
     * @return bool
     */
    public function is_point_in_polygon($point, $pts) {
        $N = count($pts);
        $boundOrVertex = true; //如果点位于多边形的顶点或边上，也算做点在多边形内，直接返回true
        $intersectCount = 0;//cross points count of x
        $precision = 2e-10; //浮点类型计算时候与0比较时候的容差
        $p1 = 0;//neighbour bound vertices
        $p2 = 0;
        $p = $point; //测试点

        $p1 = $pts[0];//left vertex
        for ($i = 1; $i <= $N; ++$i) {//check all rays
            // dump($p1);
            if ($p['lng'] == $p1['lng'] && $p['lat'] == $p1['lat']) {
                return $boundOrVertex;//p is an vertex
            }

            $p2 = $pts[$i % $N];//right vertex
            if ($p['lat'] < min($p1['lat'], $p2['lat']) || $p['lat'] > max($p1['lat'], $p2['lat'])) {//ray is outside of our interests
                $p1 = $p2;
                continue;//next ray left point
            }

            if ($p['lat'] > min($p1['lat'], $p2['lat']) && $p['lat'] < max($p1['lat'], $p2['lat'])) {//ray is crossing over by the algorithm (common part of)
                if($p['lng'] <= max($p1['lng'], $p2['lng'])){//x is before of ray
                    if ($p1['lat'] == $p2['lat'] && $p['lng'] >= min($p1['lng'], $p2['lng'])) {//overlies on a horizontal ray
                        return $boundOrVertex;
                    }

                    if ($p1['lng'] == $p2['lng']) {//ray is vertical
                        if ($p1['lng'] == $p['lng']) {//overlies on a vertical ray
                            return $boundOrVertex;
                        } else {//before ray
                            ++$intersectCount;
                        }
                    } else {//cross point on the left side
                        $xinters = ($p['lat'] - $p1['lat']) * ($p2['lng'] - $p1['lng']) / ($p2['lat'] - $p1['lat']) + $p1['lng'];//cross point of lng
                        if (abs($p['lng'] - $xinters) < $precision) {//overlies on a ray
                            return $boundOrVertex;
                        }

                        if ($p['lng'] < $xinters) {//before ray
                            ++$intersectCount;
                        }
                    }
                }
            } else {//special case when ray is crossing through the vertex
                if ($p['lat'] == $p2['lat'] && $p['lng'] <= $p2['lng']) {//p crossing over p2
                    $p3 = $pts[($i+1) % $N]; //next vertex
                    if ($p['lat'] >= min($p1['lat'], $p3['lat']) && $p['lat'] <= max($p1['lat'], $p3['lat'])) { //p.lat lies between p1.lat & p3.lat
                        ++$intersectCount;
                    } else {
                        $intersectCount += 2;
                    }
                }
            }
            $p1 = $p2;//next ray left point
        }

        if ($intersectCount % 2 == 0) {//偶数在多边形外
            return false;
        } else { //奇数在多边形内
            return true;
        }
    }

    /**
     * 获取网格
     * @author lijie
     * @date_time 2020/12/31
     * @param $where
     * @param bool $field
     * @param string $order
     * @param $polygon_center_code
     * @return int
     */
    public function getGridRangeType($where,$field=true,$order='b.zoom DESC',$polygon_center_code)
    {
        $data = $this->modelRange->getList($where,$field,$order)->toArray();
        $center_code['lng'] = $polygon_center_code[0];
        $center_code['lat'] = $polygon_center_code[1];
        $type = 0;
        $bind_id=0;
        if($data){
            foreach ($data as $v){
                $arr = explode('|',$v['manage_range_polygon']);
                foreach ($arr as &$v1){
                    $v1 = explode(',',$v1);
                    $v1['lng'] = $v1[0];
                    $v1['lat'] = $v1[1];
                    unset($v1[0]);
                    unset($v1[1]);
                }
                $res = $this->is_point_in_polygon($center_code,$arr);
                if($res){
                    $type = $v['type'];
                    $bind_id = $v['bind_id'];
                    break;
                }
            }
        }
        $rtn['type'] = $type;
        $rtn['bind_id'] = $bind_id;
        return $rtn;
    }

    /**
     * 获取楼栋网格员信息
     * @author lijie
     * @date_time 2020/01/12
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getSingleGridMemberInfo($where,$field=true)
    {
        $db_house_village_grid_range = new HouseVillageGridRange();
        $data = $db_house_village_grid_range->getGridMember($where,$field);
        return $data;
    }

    /**
     * 更新网格信息
     * @author lijie
     * @date_time 2020/01/26
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveRange($where,$data)
    {
        $res = $this->modelRange->saveOne($where,$data);
        return $res;
    }


    /**
     * 更新网格信息
     * @author lijie
     * @date_time 2020/01/26
     * @param $where
     * @param $data
     * @return mixed
     */
    public function saveRange1()
    {
        $where[]=['polygon_name','<>',' '];
        $res = $this->modelRange->getLists(['polygon_name'=>' ']);
        $res1=[];
        if (!empty($res)){
            $res=$res->toArray();
        }
        if (!empty($res)){
            foreach ($res as $v){
                $res1[] = $this->modelRange->saveOne(['id'=>$v['id']],['polygon_name'=>'网络'.$v['id']]);
            }
        }

        return $res1;
    }

    /**
     * 查询网格员数据
     * @author: liukezhu
     * @date : 2022/5/13
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getGridMemberList($where,$field=true,$page=1,$limit=15,$order='g.id DESC')
    {
        $data = $this->model->getLists($where,$field,$order,$page,$limit);
        $count = $this->model->getCounts($where);
        $res['list'] = $data;
        $res['count'] = $count;
        return $res;
    }


    public function getGridRangeList($data,$is_manage_api=false){
        $where_member = array();
        if($data['area_type'] == 1){
            $type = 2;
        }else{
            $type = 1;
        }
        $where_member['area_id']=$data['area_id'];
        if(!$is_manage_api){
            $where_member['business_type']=$type;
        }
        $where_member['workers_id']=$data['wid'];
        $db_house_village_grid_member=new HouseVillageGridMember();
        $grid_member_info=$db_house_village_grid_member->getOne($where_member,'*','business_type asc');
        $grid_member_id=0;
        $community_id=0;
        if (!empty($grid_member_info) && !$grid_member_info->isEmpty()){
            $grid_member_id=$grid_member_info['id'];
            if($is_manage_api && $grid_member_info['business_type']==2 && $grid_member_info['community_id']>0){
                $type=2; //社区网格员
                $community_id=$grid_member_info['community_id'];
            }
        }
        $where = array();
        if($grid_member_id){
            $where['grid_member_id'] = $grid_member_id;
            if($type==1){
                $where['f_street_id'] = $data['area_id'];
            } else{
                if($community_id>0){
                    $where['f_street_id'] = $data['area_id'];
                    $where['f_area_id'] = $community_id;
                }else{
                    $where['f_area_id'] = $data['area_id'];
                }

            }
        }else{
            $where['bind_id'] = $data['area_id'];
            $where['type'] = $type;
        }
        $data1 = $this->getGridRangeLists($where);
        if($data1){
            foreach ($data1 as &$v){
                $v['manage_range_polygon'] = explode('|',$v['manage_range_polygon']);
                $info = $this->getGridCenter(['bind_id'=>$data['area_id'],'type'=>$type],'zoom');
                $v['zoom'] = $info['zoom'];
            }
        }
        $res['data'] = $data1;
        $res['type'] = $type;
        return $res;
    }
    //获取绑定类型数据
    public function getBindTypeData($area_type=0){
        $dataTmp=array();
        if($area_type==0){
            $dataTmp[]=array('title'=>'街道区域','xkey'=>'street_area');
        }
        $dataTmp[]=array('title'=>'社区区域','xkey'=>'community_area');
        $dataTmp[]=array('title'=>'小区区域','xkey'=>'village_area');
        $dataTmp[]=array('title'=>'楼栋区域','xkey'=>'single_area');
        return $dataTmp;
    }
}