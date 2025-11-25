<?php
namespace app\community\model\service;
use app\community\model\db\VillageQywxEngineGroup;
use app\community\model\db\VillageQywxEngineContent;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseServiceCategory;
use app\community\model\db\HouseVillageNewsCategory;
use app\community\model\db\HouseVillageActivity;
use app\community\model\db\MerchantStore;
use app\community\model\db\Group;
use app\community\model\db\Appoint;
use app\community\model\db\HouseServiceInfo;
use app\community\model\db\VillageQywxChatColumnSet;
use app\community\model\db\WeixinAppBind;
use app\community\model\db\VillageQywxAgent;
use app\community\model\db\HouseProperty;

use error_msg\GetErrorMsg;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\writer\xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\style\Alignment;
use PhpOffice\PhpSpreadsheet\style\Border;
use app\community\model\service\HouseNewPorpertyService;
class ContentEngineService
{
    /**
     * Notes:获取分组
     * @param $type
     * @param integer $property_id 物业id
     * @param integer $village_id 小区id
     * @return mixed
     */
    public function getGroupMenu($type,$property_id,$village_id,$gid=0)
    {
        //小区
        if($type == 1){
            if ($property_id) {
                $where = [];
                $where_or = "status=0 AND ((type=1 AND village_id={$village_id}) OR (type=2 AND property_id={$property_id}))";
            } else {
                $where_or='';
                $where[] = ['type','=',$type];
                $where[] = ['village_id','=',$village_id];
            }
        }else{
            $dbHouseVillage = new HouseVillage();
            $map[] = ['property_id','=',$property_id];
            $map[] = ['status','in',[0,1]];
            $village_id_arr = $dbHouseVillage->getColumn($map,'village_id');
            if($village_id_arr && count($village_id_arr)>0)
            {
                $where = [];
                $village_id_a = implode(',',$village_id_arr);
                $where_or ="status=0 AND ((type=2 AND property_id=$property_id) or (type=1 AND village_id in ($village_id_a)))";
            }else{
                $where[] = ['type','=',$type];
                $where[] = ['property_id','=',$property_id];
                $where_or='';
            }
        }
        if (!empty($where)) {
            $where[] = ['status','=',0];
        }
        $find_where = $where;

        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $total_sum_number = $dbVillageQywxEngine->getSum($where,'number',$where_or);
        $arr_data[0]=[
            'id'=>0,
            'title'=>'所有 ('.$total_sum_number.')',
            'key'=>'9-0',
            'scopedSlots'=> ['title'=> "add_out"],
            'is_add' => 1,
        ];
        $choose_data = '9-0';
        $c_arr = [
            'id'=>-1,
            'pid'=>0,
            'title'=>'渠道码 ('.$total_sum_number.')',
            'key'=>'0-9',
            'scopedSlots'=> ['title'=> "fixed_out"],
        ];
        $find_where[] = ['status','=',0];
        $find_where[] = ['pid','=',0];
        $info =$dbVillageQywxEngine->getFind($find_where,true,'id desc',$where_or);
        if(!$info){
            $arr = [
                'pid'=>0,
                'name'=>'未分组',
                'type'=>$type,
                'add_time'=>time(),
                'is_default'=>1,
            ];
            if($type == 1) {
                $arr['number'] = 4;
                $arr['village_id'] = $village_id;
            }else{
                $arr['number'] = 0;
                $arr['property_id'] = $property_id;
            }
            $dbVillageQywxEngine->addFind($arr);
        }

        $list = $dbVillageQywxEngine->getList($where,true,'id desc',0,10,$where_or);
        $new_list = [];
//        $new_list[0] = $c_arr;
        if($list) {
            $list = $list->toArray();
            foreach ($list as $key => $val) {
                if ($val['pid'] == 0) {
                    $map = [];
                    $map[] = ['pid', '=', $val['id']];
                    $map[] = ['status', '=', 0];
                    $sum_number = $dbVillageQywxEngine->getSum($map, 'number');
                    $sum_number = $sum_number + $val['number'];
                } else {
                    $sum_number = $val['number'];
                }
                if ($val['type']!=$type) {
                    $add = 0;
                    $edit = 0;
                    $del = 0;
                } else {
                    $add = 1;
                    $edit = 1;
                    $del = 1;
                }
                $new_list[$key] = [
                    'id' => $val['id'],
                    'pid' => $val['pid'],
                    'title' => $val['name'] . ' (' . $sum_number . ')',
                    'key' =>'0-0-'.$val['id'],
                    'scopedSlots' => ['title' => "edit_out"],
                    'is_add' => $add,
                    'is_edit' => $edit,
                    'is_del' => $del,
                ];
                if ($gid && $gid==$val['id']) {
                    $choose_data = '0-0-'.$val['id'];
                }
                if($val['is_default'] == 1){
                    $new_list[$key]['scopedSlots']['title'] = 'fixed_not_out';
                }
            }
        }
//        dump($new_list);die;
        if($new_list){
            $key = array_column($new_list,'key');
        }else{
            $key = [];
        }
        $new_arr = $this->getTree($new_list);
        $arr_data[0]['children'] = $new_arr;
        $data['menu_list'] = $arr_data;
        $data['key'] = $key;
        $data['key_one'] = $key[0];
        $data['village_id'] = $village_id;
        $data['property_id'] = $property_id;
        $data['total_sum_number'] = "所有 ($total_sum_number)";
        // 默认选中的一级分类
        $data['choose_data'] = $choose_data;
        return $data;

    }
    public function getTree($list,$fid ="pid")
    {
        $map  = [];
        $tree = [];
        foreach ($list as &$val) {
            $map[$val['id']] = &$val;
        }
        foreach ($list as &$val) {
            $parent = &$map[$val[$fid]];
            if($parent) {
                $parent['children'][] = &$val;
            }else{
                $tree[] = &$val;
            }
        }
        return $tree;
    }

    /**
     * Notes: 添加编辑分组
     * @datetime: 2021/3/11 18:05
     * @param $id
     * @param $data
     * @param $param_id
     * @return VillageQywxEngineGroup|int|string
     */
    public function subGroup($id,$data,$param_id)
    {
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        if($id){
            if($data['type'] == 1)
            {
                $data['village_id'] = $param_id;
            }else{
                $data['property_id'] = $param_id;
            }
            $where[] = ['id','=',$id];
            $engine_info = $dbVillageQywxEngine->getFind($where);
            if (empty($engine_info) || empty($engine_info) ) {
                throw new \Exception('当前编辑对象不存在或者已经被删除');
            }
            if($data['type'] == 1) {
                if ($param_id != $engine_info['village_id']) {
                    return api_output_error(1001, '您没有权限编辑当前对象');
                }
            } elseif ($data['type'] == 2) {
                if ($param_id != $engine_info['property_id']) {
                    return api_output_error(1001, '您没有权限编辑当前对象');
                }
            }
            $res = $dbVillageQywxEngine->editFind($where,$data);
        }else{
            if($data['type'] == 1)
            {
                $data['village_id'] = $param_id;
                $map[] = ['village_id','=',$param_id];
            }else{
                $data['property_id'] = $param_id;
                $map[] = ['property_id','=',$param_id];
            }
            $map[] = ['name','=',$data['name']];
            $map[] = ['type','=',$data['type']];
            $map[] = ['pid','=',$data['pid']];
            $data['add_time'] = time();
            $engine_info = $dbVillageQywxEngine->getFind($map);
            if($engine_info){
                throw new \Exception('分组已存在');
            }
            $res = $dbVillageQywxEngine->addFind($data);
        }
        return $res;
    }

    /**
     * Notes:获取分组信息
     * @datetime: 2021/3/11 18:05
     * @param $id
     * @return mixed
     */
    public function getGroupInfo($id)
    {
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $where[] = ['id','=',$id];
        $info = $dbVillageQywxEngine->getFind($where);
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 软删除
     * @datetime: 2021/3/11 18:04
     * @param $id
     * @return VillageQywxEngineGroup
     */
    public function delGroup($id)
    {
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $map[] = ['pid','=',$id];
        $map[] = ['status','<>',-1];
        $info = $dbVillageQywxEngine->getFind($map);
        if($info){
            throw new \Exception('该分组下有子分组，请先删除子分组');
        }
        $where[] = ['id','=',$id];
        $data['status'] = -1;
        $data['del_time'] = time();
        $res = $dbVillageQywxEngine->editFind($where,$data);
        //需要删除分组下的成员
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $map = [];
        $map[] = ['c.gid','=',$id];
        $content_list = $dbVillageQywxEngineContent->getList($map);
//        dump($content_list);die;
        foreach ($content_list as $value)
        {
            $map = [];
            $map[] = ['id','=',$value['id']];
            $data['status'] = -1;
            $data['del_time'] = time();
            $dbVillageQywxEngineContent->editFind($map,$data);
        }

        return $res;
    }
    public function getGroupSelect($type,$property_id,$village_id)
    {
        //小区
        if($type == 1){
            if ($property_id) {
                $where = [];
                $where_or = "status=0 AND ((type=1 AND village_id={$village_id}) OR (type=2 AND property_id={$property_id}))";
            } else {
                $where_or='';
                $where[] = ['type','=',$type];
                $where[] = ['village_id','=',$village_id];
            }
        }else{
            $dbHouseVillage = new HouseVillage();
            $map[] = ['property_id','=',$property_id];
            $map[] = ['status','in',[0,1]];
            $village_id_arr = $dbHouseVillage->getColumn($map,'village_id');

            if($village_id_arr && count($village_id_arr)>0)
            {
                $where = [];
                $village_id_a = implode(',',$village_id_arr);
                $where_or ="status=0 AND ((type=2 AND property_id=$property_id) or (type=1 AND village_id in ($village_id_a)))";
            }else{
                $where[] = ['type','=',$type];
                $where[] = ['property_id','=',$property_id];
                $where_or='';
            }
        }
        if (!empty($where)) {
            $where[] = ['status','=',0];
        }
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $list = $dbVillageQywxEngine->getList($where,true,'id desc',0,10,$where_or)->toArray();
        $new_list = [];
        foreach ($list as $key=>$val){
            $new_list[$key] = [
                'id'=>$val['id'],
                'pid'=>$val['pid'],
                'title'=> $val['name'],
                'key'=> '0-'.$type.'-'.$val['id'],
                'value'=>$val['id'],
                'scopedSlots'=> ['title'=> "edit_out"],
            ];
        }
        $new_arr = $this->getTree($new_list);
        $data['menu_list'] = $new_arr;
        return $data;

    }
    //-------------------------------------内容引擎内容相关---------------------------------------------------//

    /**
     * Notes:内容引擎列表
     * @param $param
     * @return mixed
     */
    public function getContentList($param,$village_id,$property_id)
    {
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $dbHouseVillage = new HouseVillage();
        $dbHouseProperty = new HouseProperty();
        $limit = 15;
        $page = isset($param['page']) && $param['page'] ? $param['page'] : 0;
        if($param['from_type'] == 1) {//小区
            $this->defaultApplication($param['uid'], $param['from_type'], $param['from_id']);
            $where = [];
            $where_or = "c.status=0 AND ((from_type=1 AND from_id=$village_id) OR (from_type=2 AND from_id=$property_id))";
        }else{
            $from_id = $param['from_id'];
            $map[] = ['property_id','=',$from_id];
            $map[] = ['status','in',[0,1]];
            $village_id_arr = $dbHouseVillage->getColumn($map,'village_id');
            if($village_id_arr && count($village_id_arr)>0)
            {
                $where = [];
                $village_id_a = implode(',',$village_id_arr);
                $where_or ="c.status=0 AND ((from_id=$from_id and from_type=2) or (from_id in ($village_id_a) and from_type=1))";
            }else{
                $where = [];
                $where[] = ['from_type','=',$param['from_type']];
                $where[] = ['from_id','=',$from_id];
                $where_or='';
            }
        }
        if (!empty($where)) {
            $where[] = ['c.status','=',0];
            if($param['type']){
                $where[] = ['c.type','=',$param['type']];
            }
            if($param['title']){
                $where[] = ['c.title','like','%'.$param['title'].'%'];
            }
        } elseif ($where_or) {
            if($param['type']){
                $where_or .= " AND c.type={$param['type']}";
            }
            if($param['title']){
                $where_or .= " AND c.title like '%{$param['title']}%'";
            }
        }
        $title_number ='';
        if($param['gid']){
            $map[] = ['pid','=',$param['gid']];
            $map[] = ['status','<>',-1];
            $group = $dbVillageQywxEngine->getColumn($map,'id');
            $gid = $param['gid'];
            $s_w[] = ["pid|id","=",$gid];
            $number = $dbVillageQywxEngine->getSum($s_w,'number');
            $f_where[] = ['id','=',$gid];
            $title = $dbVillageQywxEngine->getFind($f_where,'name');
            $title_number = $title['name'].'('.$number.')';
            if(!empty($group)) {
                $group = array_merge($group,[$param['gid']]);
                if(count($group) > 1){
                    if (!empty($where)) {
                        $where[] = ['c.gid', 'in', $group];
                    } elseif ($where_or) {
                        $group_str = implode(',',$group);
                        $where_or .= " AND c.gid in ({$group_str})";
                    }
                }else{
                    if (!empty($where)) {
                        $where[] = ['c.gid','=',$param['gid']];
                    } elseif ($where_or) {
                        $where_or .= " AND c.gid={$param['gid']}";
                    }
                }
            }else{
                if (!empty($where)) {
                    $where[] = ['c.gid','=',$param['gid']];
                } elseif ($where_or) {
                    $where_or .= " AND c.gid={$param['gid']}";
                }
            }
        }
        $list = $dbVillageQywxEngineContent->getList($where,'c.*,g.name','c.id desc',$page,$limit,$where_or);
        $service_house_village = new HouseVillageService();
        $app_version = isset($_POST['app_version'])&&$_POST['app_version']?intval($_POST['app_version']):0;
        $deviceId = isset($_POST['Device-Id'])&&$_POST['Device-Id']?trim($_POST['Device-Id']):0;
        foreach ($list as &$value)
        {
            if($value['add_time']){
                $value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            }
            $value['key'] = $value['id'];
            if($value['type'] == 2){
                $value['content'] = dispose_url($value['content']);
            }
            if($value['type'] ==3){
                $value['content'] = $this->fileIco($value['file_type']);
            }
            if(isset($value['share_img']) && $value['share_img']){
                $value['share_img_txt'] = replace_file_domain($value['share_img']);
            }
            if($value['from_type'] == 1){//小区
                $village_info = $dbHouseVillage->getOne($value['from_id'],'village_name,account');
                $value['user'] = $village_info['village_name']?$village_info['village_name']:$village_info['account'];
            }else{
                $property_info = $dbHouseProperty->get_one(['id'=>$value['from_id']],'account,property_name');
                $value['user'] =$property_info['property_name']?$property_info['property_name']:$property_info['account'];
            }
            switch ($value['type']){
                case '1':
                    $value['type'] = '文本';
                    break;
                case '2':
                    $value['type'] = '图片';
                    break;
                case '3':
                    $value['type'] = '文件';
                    break;
                case '4':
                    $value['type'] = '功能库';
                    if (isset($value['content']) && $value['content'] && strstr($value['content'], 'pages/village/my/')) {
                        $param = [
                            'pagePath' => $value['content'],
                        ];
                        $value['content'] = $service_house_village->villagePagePath($app_version,$deviceId,$param);
                    }
                    break;
            }
        }
        $count = $dbVillageQywxEngineContent->getListCount($where,$where_or);
        $data['total_limit'] = $limit;
        $data['count'] = $count ? $count : 0;
        $data['list'] = $list;
        $data['title_number'] = $title_number;
        return $data;

    }
    public function fileIco($type)
    {
        switch ($type){
            case 'txt':
                $ico = dispose_url('/v20/public/static/community/qywx/txt.png');
                return $ico;
                break;
            case 'docx':
            case 'doc':
                $ico = dispose_url('/v20/public/static/community/qywx/word.png');
                return $ico;
                break;
            case 'xls'://
                $ico = dispose_url('/v20/public/static/community/qywx/xls.png');
                return $ico;
                break;
            case 'xlsx'://
                $ico = dispose_url('/v20/public/static/community/qywx/XLSX.png');
                return $ico;
                break;
            case 'ppt'://
                $ico = dispose_url('/v20/public/static/community/qywx/PDF.png');
                return $ico;
                break;
            case 'pptx'://
                $ico = dispose_url('/v20/public/static/community/qywx/ppt.png');
                return $ico;
                break;
            case 'pdf'://
                $ico = dispose_url('/v20/public/static/community/qywx/pptx.png');
                return $ico;
                break;
            case 'xmind'://
                $ico = dispose_url('/v20/public/static/community/qywx/xmind_file.png');
                return $ico;
                break;
        }
    }

    /**
     * Notes:添加编辑内容
     * @param $id
     * @param $data
     * @return VillageQywxEngineContent|int|string
     * @throws \think\Exception
     */
    public function subContent($id,$data)
    {
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        if($data['title']){
            $map[] = ['title','=',$data['title']];
        }else{
            $name_arr = array_column($data['content'],'name');
            $map[] = ['title','in',$name_arr];
        }
        $map[] = ['type','=',$data['type']];
        $map[] = ['gid','=',$data['gid']];
        if($id){
            $map[] = ['id','<>',$id];
        }
        $map[] = ['status','=',0];
        $info = $dbVillageQywxEngineContent->getFind($map);

        if($info){
            throw new \think\Exception('名称重复');
        }
        if($id){
            $where[] = ['id','=',$id];
            $res = $dbVillageQywxEngineContent->editFind($where,$data);
            $dbVillageQywxEngine->updateNum($data['gid']);
        }else{
            if($data['type'] == 2 || $data['type'] == 3){
                $content = $data['content'];
                foreach ($content as $v){
                    $data['title'] = $v['name'];
                    $data['content'] = $v['url'];
                    if($data['type'] == 3){
                        $data['file_type'] = $v['file_type'];
                    }
                    $data['add_time'] = time();
                    $res = $dbVillageQywxEngineContent->addFind($data);
                }
            }else{
                $data['add_time'] = time();
                $res = $dbVillageQywxEngineContent->addFind($data);
            }
            $dbVillageQywxEngine->updateNum($data['gid']);
        }
        return $res;
    }

    /**
     * Notes:获取内容引擎详情
     * @param $id
     * @return mixed
     */
    public function getContent($id)
    {
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $where[] = ['id','=',$id];
        $info = $dbVillageQywxEngineContent->getFind($where);
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 软删除 （单 /多）
     * @datetime: 2021/3/15 10:07
     * @param $id
     * @return VillageQywxEngineContent|\think\Collection
     */
    public function delContent($id)
    {
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        if(is_array($id))
        {
            //批量删除
            $where[] = ['id','in',$id];
            $data['status'] = -1;
            $data['del_time'] = time();
            $res = $dbVillageQywxEngineContent->editFind($where,$data);
            foreach ($id as $val){
                $c_where =[];
                $c_where[] =['id','=',$val];
                $info = $dbVillageQywxEngineContent->getFind($c_where,'gid');
                $dbVillageQywxEngine->updateNum($info['gid']);
            }
        }else{
            //单个删除
            $where[] = ['id','=',$id];
            $data['status'] = -1;
            $data['del_time'] = time();
            $info = $dbVillageQywxEngineContent->getFind($where,'gid');
            $res = $dbVillageQywxEngineContent->editFind($where,$data);

            $dbVillageQywxEngine->updateNum($info['gid']);
        }
        return $res;
    }
    //--------------------------------------功能库相关------------------------------------
    /**
     * Notes:功能库
     * @datetime: 2021/3/23 11:17
     * @param $village_id
     * @return mixed
     */
    public function functionLibrary($village_id,$from_type)
    {
        $modules = $this->modulesList($village_id,$from_type);
        $data['list'] = $modules;
        return $data;
    }

    /**
     * Notes:功能库相关
     * @param $from_id
     * @param $from_type
     * @return array
     */
    private function modulesList($from_id,$from_type)
    {
        $sHouse_village = new HouseVillageService();
        $base_url = $sHouse_village->base_url;
        $street_url = $sHouse_village->street_url;

        $menu_data = $this->modulesa();
        if($from_type == 1){//小区
            $house_ride_url = cfg('site_url').'/wap.php?g=Wap&c=Ride&a=ride_list&village_id='.$from_id;
            $housephoneIndexUrl = cfg('site_url') . $base_url . 'pages/village/commonly_used/house_phone?village_id='.$from_id;
            $HousevillageNewsCatelist = cfg('site_url') .$base_url . 'pages/village/index/notice?village_id=' . $from_id;
            $HouseserviceserviceCategory = cfg('site_url') . $base_url . 'pages/village_menu/convenient?village_id='.$from_id;
            $HousevillageActivity = cfg('site_url').'/wap.php?g=Wap&c=House&a=village_activitylist&village_id='.$from_id;
            $HousevillageManager = cfg('site_url').'/wap.php?g=Wap&c=House&a=village_manager_list&village_id='.$from_id;
        }else{//物业
            $house_ride_url = cfg('site_url').'/wap.php?g=Wap&c=Ride&a=ride_list';
            $housephoneIndexUrl = cfg('site_url') . $base_url . 'pages/village/commonly_used/house_phone';
            $HousevillageNewsCatelist = cfg('site_url') .$base_url . 'pages/village/index/notice';
            $HouseserviceserviceCategory = cfg('site_url') . $base_url . 'pages/village_menu/convenient';
            $HousevillageActivity = cfg('site_url').'/wap.php?g=Wap&c=House&a=village_activitylist';
            $HousevillageManager = cfg('site_url').'/wap.php?g=Wap&c=House&a=village_manager_list';
        }
        $service_house_village = new HouseVillageService();
        $app_version = isset($_POST['app_version'])&&$_POST['app_version']?intval($_POST['app_version']):0;
        $deviceId = isset($_POST['Device-Id'])&&$_POST['Device-Id']?trim($_POST['Device-Id']):0;
        $param = [
            'pagePath' => 'pages/village/my/',
            'isAll' => true,
        ];
        $pagesMyUrl = $service_house_village->villagePagePath($app_version,$deviceId,$param);

        $t = [
            ['module' => 'Housevillage',
                'linkcode' => cfg('site_url') . $base_url . 'pages/village_menu/index',
                'name'=>$menu_data['Housevillage'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'askeyword'=>1
            ],

            ['module' => 'HousevillageList',
                'linkcode' => $pagesMyUrl . 'villagelist',
                'name'=>$menu_data['HousevillageList'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'askeyword'=>1
            ],

            ['module' => 'HouseRide',
                'linkcode' => $house_ride_url,
                'name'=>$menu_data['HouseRide'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'keyword'=>'','askeyword'=>1],
            ['module' => 'HousephoneIndex',
                'linkcode' => $housephoneIndexUrl,
                'name'=>$menu_data['HousephoneIndex'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1],

            ['module' => 'HousevillageNewsCatelist',
                'linkcode' => $HousevillageNewsCatelist,
                'name'=>$menu_data['HousevillageNewsCatelist'],'sub'=>1,'canselected'=>1,'linkurl'=>'','askeyword'=>1],

            ['module' => 'Member',
//                'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/My/index', '', true, false, true)),
                'linkcode' =>cfg('site_url') .'/wap.php?g=Wap&c=My&a=index',
                'name'=>$menu_data['Member'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1],


            ['module' => 'HousevillageMy', 'linkcode' => cfg('site_url') .$base_url . 'pages/village_menu/my',
                'name'=>$menu_data['HousevillageMy'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1],

            ['module' => 'HousevillageMyBindFamilyAdd', 'linkcode' => $pagesMyUrl . 'bindFamily',
                'name'=>$menu_data['HousevillageMyBindFamilyAdd'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1],
            ['module' => 'HousevillageMyBindFamilyList',
                'linkcode' => $pagesMyUrl . 'myVillage',
                'name'=>$menu_data['HousevillageMyBindFamilyList'],
                'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1],

            ['module' => 'HouseserviceserviceCategory',
                'linkcode' => $HouseserviceserviceCategory,
                'name'=>$menu_data['HouseserviceserviceCategory'],'sub'=>1,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1],

            ['module' => 'HousevillageActivity',
                'linkcode' =>$HousevillageActivity,
                'name'=>$menu_data['HousevillageActivity'],
                'sub'=>1,'canselected'=>1,'linkurl'=>'','askeyword'=>1],
            ['module' => 'HousevillageManager',
                //'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/House/village_manager_list',array('village_id'=>$village_id) , true, false, true)),
                'linkcode' =>$HousevillageManager,
                'name'=>$menu_data['HousevillageManager'],
                'sub'=>0,'canselected'=>1,
                'linkurl'=>'',
                'askeyword'=>1
            ],

            ['module' => 'MyVillage',
                'linkcode' => $pagesMyUrl .'myVillage',
                'name'=>$menu_data['MyVillage'],
                'sub'=>0,
                'canselected'=>1,
                'linkurl'=>'',
                'askeyword'=>1
            ],
        ];
        $serviceApplication_list = new ApplicationService();
        $where_bind =[];
        $where_bind[] = ['from','=',0];
        if($from_type == 1){
            $where_bind[] = ['use_id','=',$from_id];
        }else{
            $where_bind[] = ['other_id','=',$from_id];
        }
        $where_bind[] = ['status','=',0];
        $application_arr = $serviceApplication_list->get_application_id_arr($where_bind);

        //收费管理 start 2020/9/27
        if(in_array(33,$application_arr)){
            //小区缴费
            if($from_type == 1){
                $HousePay = $pagesMyUrl .'payMentlist?village_id='.$from_id;
                $villageArr=$sHouse_village->getHouseVillage($from_id,'property_id');
                $serviceHouseNewPorperty = new HouseNewPorpertyService();
                $is_new_effect_time = $serviceHouseNewPorperty->getTakeEffectTimeJudge($villageArr['property_id']);
                if($is_new_effect_time){
                    $HousePay = cfg('site_url') . $base_url.'pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$from_id;
                }
            }else{
                $HousePay = $pagesMyUrl .'payMentlist';
            }

            $t[] = ['module' => 'HousePay',
                'linkcode' => str_replace('shequ.php', 'wap.php', $HousePay),
                'name'=>$menu_data['HousePay'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
            //自主缴费
            if($from_type == 1) {
                $custom = $pagesMyUrl . 'independentPayement?village_id='.$from_id;
            }else{
                $custom = $pagesMyUrl . 'independentPayement';
            }
            $t[] = ['module' => 'custom',
                'linkcode' => str_replace('shequ.php', 'wap.php', $custom),
                'name'=>$menu_data['custom'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
            //预存物业费
            if($from_type == 1) {
                $pre_storage_property = $pagesMyUrl . 'lifepayment?village_id=' . $from_id;
            }else{
                $pre_storage_property = $pagesMyUrl . 'lifepayment';
            }
            $t[] = ['module' => 'pre_storage_property',
                'linkcode' => str_replace('shequ.php', 'wap.php', $pre_storage_property),
                'name'=>$menu_data['pre_storage_property'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        //收费管理 end

        if (in_array(16,$application_arr)) {
            if($from_type == 1) {
                $HousevillageMyRepairlists = $pagesMyUrl . 'waterReportlist?type=1&title=' . urlencode('物业报修') . '&village_id=' . $from_id;
            }else{
                $HousevillageMyRepairlists = $pagesMyUrl . 'waterReportlist?type=1&title=' . urlencode('物业报修');
            }
            $t[] = ['module' => 'HousevillageMyRepairlists',
                'linkcode' => str_replace('shequ.php', 'wap.php', $HousevillageMyRepairlists),
                'name'=>$menu_data['HousevillageMyRepairlists'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if (in_array(18,$application_arr)) {
            if($from_type == 1) {
                $HousevillageMyUtilitieslists = $pagesMyUrl . 'waterReportlist?type=2&title=' . urlencode('水电煤上报') . '&village_id=' . $from_id;
            }else{
                $HousevillageMyUtilitieslists = $pagesMyUrl . 'waterReportlist?type=2&title=' . urlencode('水电煤上报');
            }
            $t[] = ['module' => 'HousevillageMyUtilitieslists',
                'linkcode' => str_replace('shequ.php', 'wap.php', $HousevillageMyUtilitieslists),
                'name'=>$menu_data['HousevillageMyUtilitieslists'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if (in_array(19,$application_arr)) {
            if($from_type == 1) {
                $HousevillageMySuggestist = $pagesMyUrl . 'waterReportlist?type=3&title=' . urlencode('投诉建议') . '&village_id=' . $from_id;
            }else{
                $HousevillageMySuggestist = $pagesMyUrl . 'waterReportlist?type=3&title=' . urlencode('投诉建议');
            }
            $t[] = ['module' => 'HousevillageMySuggestist',
                'linkcode' => str_replace('shequ.php', 'wap.php', $HousevillageMySuggestist),
                'name'=>$menu_data['HousevillageMySuggestist'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }

        if (in_array(24,$application_arr)) {
            if($from_type == 1) {
                $ActiveGroupList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=village_grouplist&village_id=' . $from_id;
            }else{
                $ActiveGroupList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=village_grouplist';
            }
            $t[] = ['module' => 'ActiveGroupList',
                //'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/House/village_grouplist', array('village_id'=>$village_id), true, false, true)),
                'linkcode' =>$ActiveGroupList,
                'name'=>$menu_data['ActiveGroupList'],'sub'=>1,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if (in_array(14,$application_arr)) {
            if($from_type == 1) {
                $HousevillageExpress = $pagesMyUrl . 'expressManagement?village_id=' . $from_id;
                $HousevillageExpressSend = $pagesMyUrl . 'expressManagement?current=1&village_id='.$from_id;
            }else{
                $HousevillageExpress = $pagesMyUrl . 'expressManagement';
                $HousevillageExpressSend = $pagesMyUrl . 'expressManagement?current=1';
            }
            $t[] = ['module' => 'HousevillageExpress',
                'linkcode' => $HousevillageExpress,
                'name'=>$menu_data['HousevillageExpress'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1];

            $t[] =  ['module' => 'HousevillageExpressSend',
                'linkcode' => $HousevillageExpressSend,
                'name'=>$menu_data['HousevillageExpressSend'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }

        if (in_array(25,$application_arr)) {
            if($from_type == 1) {
                $ActiveMealList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=village_meallist&village_id=' . $from_id;
            }else{
                $ActiveMealList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=village_meallist';
            }
            $t[] = ['module' => 'ActiveMealList',
                //'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/House/village_meallist', array('village_id'=>$village_id), true, false, true)),
                'linkcode' => $ActiveMealList,
                'name'=>$menu_data['ActiveMealList'],'sub'=>1,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if (in_array(26,$application_arr)) {
            if($from_type == 1) {
                $ActiveAppointList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=village_appointlist&village_id=' . $from_id;
            }else{
                $ActiveAppointList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=village_appointlist';
            }
            $t[] = ['module' => 'ActiveAppointList',
//                'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/House/village_meallist', array('village_id'=>$village_id), true, false, true)),
                'linkcode' => $ActiveAppointList,
                'name'=>$menu_data['ActiveAppointList'],'sub'=>1,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if (in_array(27,$application_arr)) {
            if($from_type == 1) {
                $ActiveStoreList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=shop&village_id=' . $from_id;
            }else{
                $ActiveStoreList = cfg('site_url') . '/wap.php?g=Wap&c=House&a=shop';
            }
            $t[] = ['module' => 'ActiveStoreList',
//                'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/House/shop', array('village_id'=>$village_id.'#cat-all'), true, false, true)),
                'linkcode' => $ActiveStoreList,
                'name'=>$menu_data['ActiveStoreList'],'sub'=>1,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if (in_array(28,$application_arr)) {
            if($from_type == 1) {
                $VisitorRegistration = $pagesMyUrl . 'visitorRegistration?village_id=' . $from_id;
            }else{
                $VisitorRegistration = $pagesMyUrl . 'visitorRegistration';
            }
            $t[] = ['module' => 'VisitorRegistration',
                'linkcode' => $VisitorRegistration,
                'name'=>$menu_data['VisitorRegistration'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }

        if(cfg('crowdsourcing_is')){
            if($from_type == 1) {
                $Crowdsourcing = cfg('site_url') . '/wap.php?g=wap&c=Crowdsourcing&a=index&village_id=' . $from_id;
            }else{
                $Crowdsourcing = cfg('site_url') . '/wap.php?g=wap&c=Crowdsourcing&a=index';
            }
            $t[] = ['module' => 'Crowdsourcing',
//                'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/Crowdsourcing/index', array('village_id'=>$_SESSION['house']['village_id']), true, false, true)),
                'linkcode' =>$Crowdsourcing,
                'name'=>$menu_data['Crowdsourcing'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        $dbHouseVillage = new HouseVillage();
        // 智能访客开门
        if (cfg('house_village_temporary_visitor') == 1 && $from_type == 1) {
            $now_village = $dbHouseVillage->getOne($from_id);
            if ($now_village['temporary_visitor_switch'] == 1) {
                $t[] = ['module' => 'TemporaryVisitorOpen',
                    'linkcode' => cfg('site_url') . $base_url.'pages/village/index/visitorPass',
                    'name'=>$menu_data['TemporaryVisitorOpen'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                    'askeyword'=>1];
            }
        }
        if (in_array(2,$application_arr)){
            $url = cfg('site_url') . $base_url . 'pages/vote/index';
            $t[] = ['module' => 'Vote',
                'linkcode' => $url,
                'name'=>$menu_data['Vote'],'sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if($from_type == 1) {
            $where = [];
            $where[] = ['village_id', '=', $from_id];
            $touch_alarm_phone = $dbHouseVillage->getColumn($where, 'touch_alarm_phone');
            if ($touch_alarm_phone) {
                $url = cfg('site_url') . $base_url . 'pages/village/index/oneClickAlarm';
                $t[] = ['module' => 'AlarmPhone', 'linkcode' => $url,
                    'name' => '一键报警', 'sub' => 0, 'canselected' => 1, 'linkurl' => '',
                    'askeyword' => 1];
            }
        }
        $url = cfg('site_url') . $base_url . 'pages/village_menu/resident';
        $t[] = ['module' => 'AlarmPhone',
            'linkcode' => $url,'name'=>'邻里','sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1];
        $url = $pagesMyUrl . 'faceAccessControl';
        $t[] = ['module' => 'AlarmPhone',
            'linkcode' => $url,'name'=>'人脸识别门禁','sub'=>0,'canselected'=>1,'linkurl'=>'',
            'askeyword'=>1];

        if (empty($now_village) && $from_type == 1) {
            $now_village = $dbHouseVillage->getOne($from_id,'street_id, community_id');
        }
        if (!empty($now_village) && $now_village['street_id'] && $from_type == 1) {
            // 街道首页
            $url = cfg('site_url') . $street_url . "pages/street_menu/index?area_street_id={$now_village['street_id']}";
            $t[] = ['module' => 'Street', 'linkcode' => $url,
                'name'=>'街道首页','sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        if (!empty($now_village) && $now_village['community_id'] && $from_type == 1) {
            // 社区首页
            $url = cfg('site_url') . $street_url. "pages/street_menu/index?area_street_id={$now_village['community_id']}";
            $t[] = ['module' => 'Community', 'linkcode' => $url,
                'name'=>'社区首页','sub'=>0,'canselected'=>1,'linkurl'=>'',
                'askeyword'=>1];
        }
        return $t;
    }

    /**
     * Notes:功能库子集
     * @param $village_id
     * @param $type
     * @param $id
     * @return mixed
     */
    public function childLibrary($village_id,$type,$id)
    {
        if($type == 'HouseserviceserviceCategory') {
            $list = $this->houseServiceCategory($village_id,$id);
        }else if($type=='HousevillageNewsCatelist'){
            $list = $this->houseVillageNewsCateList($village_id);
        }else if($type=='HousevillageActivity') {
            $list = $this->housevillageActivity($village_id);
        }else if($type == 'ActiveMealList'){
            $list = $this->activeMealList($village_id);
        }else if($type == 'ActiveGroupList'){
            $list = $this->activeGroupList($village_id);
        }else if($type == 'ActiveAppointList'){
            $list = $this->activeAppointList($village_id);
        }else{
            $list = [];
        }

        $data['list'] = $list;
        return $data;
    }

    /**
     * Notes:便民服务
     * @param $village_id
     * @param int $id
     * @return array
     */
    public function houseServiceCategory($village_id,$id=0){
        if($id){
            $moduleName = '便民服务子分类';
            $where[] = ['parent_id', '=', $id];
        }else {
            $moduleName = '便民服务分类';
            $where[] = ['parent_id', '=', 0];
        }
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['status', '=', 1];
        $dbHouseServiceCategory = new HouseServiceCategory();
        $list = $dbHouseServiceCategory->getList($where);
        $items = array();
        if($list){
            foreach ($list as $item){
                if (!$item['parent_id']) {
                    array_push($items, ['id' => $item['id'], 'sub' => 1,
                        'name' => $item['cat_name'],
                        'sublink' => url('Link/HouseserviceserviceCategory', array('id' => $item['id']), true, false, true),'keyword' => $item['cat_name']]);
                }else{
                    if(!$item['cat_url']){
                        array_push($items, ['id' => $item['id'], 'sub' => 1, 'name' => $item['cat_name'],
//                            'linkcode'=> str_replace('shequ.php', 'wap.php', url('Wap/Houseservice/cat_list', array('village_id' => $village_id ,'id'=>$item['id']), true, false, true)),
                            //'sublink' => url('Link/HouseserviceserviceCategory', array('id' => $item['id']), true, false, true),
                            'linkcode'=>cfg('site_url') .'/wap.php?g=wap&c=Houseservice&a=cat_list&village_id='.$village_id.'&id='.$item['id'],
                            'sublink' =>cfg('site_url') .'shequ.php?g=House&c=Link&a=HouseserviceserviceCategory&id='.$item['id'],
                            'keyword' => $item['cat_name']]);
                    }else{
                        array_push($items, ['id' => $item['id'], 'sub' => 0, 'name' => $item['cat_name'],
                            //'linkcode'=> str_replace('shequ.php', 'wap.php', url('Wap/Houseservice/cat_list', array('village_id' => $village_id ,'id'=>$item['id']), true, false, true)),
                            //'sublink' => url('Link/HouseserviceserviceCategory', array('id' => $item['id']), true, false, true),
                            'linkcode'=>cfg('site_url') .'/wap.php?g=wap&c=Houseservice&a=cat_list&village_id='.$village_id.'&id='.$item['id'],
                            'sublink' =>cfg('site_url') .'shequ.php?g=House&c=Link&a=HouseserviceserviceCategory&id='.$item['id'],
                            'keyword' => $item['cat_name']]);
                    }
                }

            }
        }else{
            $moduleName = '便民服务便民详情';
            $dbHouseServiceInfo =new HouseServiceInfo();
            $where = [];
            $where[] = ['status','=',1];
            $where[] = ['village_id','=',$village_id];
            $where[] = ['cat_id','=',$id];
            $list = $dbHouseServiceInfo->getList($where);

            foreach ($list as $item){
                $tmpLbs = wapLbsTranform($item['url'],array('title'=>$item['title'],'pic'=>$item['img_path'],'phone'=>$item['phone']),true);
                if($tmpLbs){
                    array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> $tmpLbs['url'],'sublink' => $tmpLbs['url'],'keyword' => $item['title']));
                }

            }
        }
        return $items;
    }

    /**
     * Notes:小区新闻
     * @param $village_id
     * @return array
     */
    public function houseVillageNewsCateList($village_id){
        $sHouse_village = new HouseVillageService();
        $dbHouseVillageNewsCategory = new HouseVillageNewsCategory();
        $menu_data = $this->modulesa();
        $base_url = $sHouse_village->base_url;
        $street_url = $sHouse_village->street_url;
        $moduleName = $menu_data['HousevillageNewsCatelist'];
        $where[] = ['c.village_id','=',$village_id];
        $where[] = ['c.cat_status','=',1];
        $list = $dbHouseVillageNewsCategory->getList($where);

        $items = array();
        foreach ($list as $item){
            $items[] = array('module' => 'HousevillageNewslist',
                'linkcode' => cfg('site_url') .$base_url . 'pages/village/index/notice?village_id=' . $village_id.'&cat_id='.$item['cat_id'],
                'name'=>$item['cat_name'],'sub'=>0,'canselected'=>1,'linkurl'=>'','keyword'=>'','askeyword'=>1);

            // array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['cat_name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', U('Wap/House/village_newslist', array('village_id' => $item['village_id'],'cat_id'=>$item['cat_id']), true, false, true)),'sublink' => U('Wap/House/village_newslist', array('village_id' => $item['village_id'],'cat_id'=>$item['cat_id']), true, false, true),'keyword' => $item['cat_name']));
        }

        return $items;
    }

    /**
     * Notes:小区活动
     * @param $village_id
     * @return array
     */
    public function housevillageActivity($village_id){
        $dbHouseVillageActivity =new HouseVillageActivity();
        $menu_data = $this->modulesa();
        $moduleName = $menu_data['HousevillageActivity'];
        $where = [];
        $where[] = ['village_id', '=',$village_id];
        $where[] = ['status','=',1];
        $list = $dbHouseVillageActivity->getList($where);
        $items = array();
        foreach ($list as $item){
            array_push($items, array('id' => $item['id'], 'sub' => 0, 'name' => $item['title'], 'linkcode'=> str_replace('shequ.php', 'wap.php', url('Wap/House/village_activity', array('village_id' => $item['village_id'],'id'=>$item['id']), true, false, true)),'sublink' => url('Wap/House/village_activity', array('village_id' => $item['id'],'news_id'=>$item['id']), true, false, true),'keyword' => $item['title']));
        }

        return $items;
    }

    /**
     * Notes:推荐餐饮
     * @param $village_id
     * @return array
     */
    public function activeMealList($village_id){
        $dbMerchantStore =new MerchantStore();
        $menu_data = $this->modulesa();
        $moduleName = $menu_data['ActiveMealList'];
        $condition_field = "ms.*,msm.*,m.name as merchant_name,ms.name as store_name,hvm.url";
        $where[] = ['ms.have_meal','=',1];
        $where[] = ['ms.status','=',1];
        $where[] = ['hvm.village_id','=',$village_id];
        $list = $dbMerchantStore->getList($where,$condition_field);

        $items = array();
        foreach ($list as $item){
            if($item!=''){
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',$item['url']),'sublink' => $item['url'],'keyword' => $item['name']));
            }else{
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php', url('Wap/Food/shop', array('store_id' => $item['store_id'],'mer_id'=>$item['mer_id']), true, false, true)),'sublink' => url('Wap/Food/shop', array('store_id' => $item['store_id'],'mer_id'=>$item['mer_id']), true, false, true),'keyword' => $item['name']));
            }
        }

        return $items;
    }

    /**
     * Notes:推荐团购
     * @param $village_id
     * @return array
     */
    public function activeGroupList($village_id){
        $dbGroup = new Group();
        $menu_data = $this->modulesa();
        $moduleName = $menu_data['ActiveGroupList'];
        $condition_field = "`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`,`g`.*,`m`.*,`hvg`.`sort`,`hvg`.`url`";
        $where = [];
        $where[] = ['g.status','=',1];
        $where[] = ['m.status','=',1];
        $where[] = ['g.type','=',1];
        $where[] = ['hvg.village_id','=',$village_id];
        $where[] = ['g.begin_time','<',time()];
        $where[] = ['g.end_time','>',time()];
        $list = $dbGroup->getList($where,$condition_field);

        $items = array();
        foreach ($list as $item){
            if($item!=''){
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',$item['url']),'sublink' => $item['url'],'keyword' => $item['name']));
            }else {
                array_push($items, array('id' => $item['group_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode' => str_replace('shequ.php', 'wap.php', url('Wap/Group/detail', array('group_id' => $item['group_id']), true, false, true)), 'sublink' => U('Wap/Group/detail', array('group_id' => $item['group_id']), true, false, true), 'keyword' => $item['name']));
            }
        }
        return $items;
    }

    /**
     * Notes:推荐预约
     * @param $village_id
     * @return array
     */
    public function activeAppointList($village_id){
        $dbAppoint = new Appoint();
        $menu_data = $this->modulesa();
        $moduleName = $menu_data['ActiveGroupList'];
        $where = [];
        $where[] = ['a.check_status','=',1];
        $where[] = ['a.appoint_status','=',0];
        $where[] = ['m.status','=',1];
        $where[] = ['hva.village_id','=',$village_id];
        $where[] = ['a.start_time','<',time()];
        $where[] = ['a.end_time','>',time()];
        $condition_field = "m.name as merchant_name,a.*,m.*,hva.*";
        $list = $dbAppoint->getList($where,$condition_field);

        $items = array();
        foreach ($list as $item){
            if($item!=''){
                array_push($items, array('id' => $item['store_id'], 'sub' => 0, 'name' => $item['name'], 'linkcode'=> str_replace('shequ.php', 'wap.php',$item['url']),'sublink' => $item['url'],'keyword' => $item['name']));
            }else {
                array_push($items, array('id' => $item['appoint_id'], 'sub' => 0, 'name' => $item['appoint_name'], 'linkcode' => str_replace('shequ.php', 'wap.php', U('Wap/Appoint/detail', array('appoint_id' => $item['appoint_id']), true, false, true)), 'sublink' => U('Wap/Appoint/detail', array('appoint_id' => $item['appoint_id']), true, false, true), 'keyword' => $item['appoint_name']));
            }
        }

        return $items;
    }

    /**
     * Notes:功能库title
     * @return array
     */
    public function modulesa()
    {
        $modules = [
            'Member' => '会员中心',
            'HousevillageList' => cfg('house_name') . '列表',
            'Housevillage' => cfg('house_name') . '首页',
            'HousevillageNewsCatelist' => cfg('house_name') . '新闻分类',
            'HousevillageNewslist' => cfg('house_name') . '新闻',
            'ActiveGroupList' => '推荐' . cfg('group_alias_name'),
            'ActiveMealList' => "推荐" . cfg('meal_alias_name'),
            'ActiveAppointList' => '推荐' . cfg('appoint_alias_name'),
            'HousevillageMy' => "个人中心",
            'HouseserviceserviceCategory' => '便民服务',
            'HousevillagePayProperty' => '缴物业费',
            'HousevillagePayWater' => '缴水费',
            'HousevillagePayElectric' => '缴电费',
            'HousevillagePayGas' => '缴燃气费',
            'HousevillagePayPark' => '缴停车费',
            'HousevillagePayCustom' => '自定义缴费',
            //'HousevillageMyPaylists'=>'缴费订单列表',
            'HousevillageMyRepairlists' => '在线报修',
            'HousevillageMyUtilitieslists' => '水电煤上报',
            'HousevillageMyBindFamilyAdd' => '绑定' . (cfg('open_house_user_name') ? '亲友' : '家属'),
            'HousevillageMyBindFamilyList' => '绑定' .(cfg('open_house_user_name') ? '亲友' : '家属') . '列表',
            'HousephoneIndex' => '常用电话列表',
            'HouseRide' => cfg('house_name') . '顺风车',
            'PlatformPayWater' => '平台缴水费',
            'PlatformPayGas' => '平台缴燃气费',
            'PlatformPayElectric' => '平台缴电费',
            'HousevillageMySuggestist' => '投诉建议',
            'HousevillageActivity' => '小区活动',
            'HousePay' => '小区缴费',
            'Crowdsourcing' => '众包',
            'HousevillageManager' => cfg('house_name') . '管家',
            'HousevillageExpress' => '快递代收',
            'HousevillageExpressSend' => '快递代发',
            'MyVillage' => '我的' . cfg('house_name'),
            'VisitorRegistration' => '访客登记',
            'ActiveStoreList' => L_('推荐X1', ['X1' => cfg('shop_alias_name')]),
            'TemporaryVisitorOpen' => '智能访客开门',
            'Vote' => '投票',
            'pre_storage_property' => '预存物业费',
            'custom' => '自主缴费',
            'Street' => '街道首页',
            'Community' => '社区首页',
        ];
        return $modules;
    }
    //--------------------------渠道码---------------------

    /**
     * Notes:渠道活码
     * @param $param
     * @return mixed
     */
    public function getChannelCode($param)
    {
        $data['list'] = [];
        return $data;
    }

    /**
     * Notes:删除渠道码（未完成未使用）
     * @param $id
     * @return int
     */
    public function delChannelCode($id)
    {
        $res = 1;
        return $res;
    }
    //---------------------------聊天侧边栏--------

    /**
     * Notes:聊天侧边栏上传文件记录
     * @param $txt_file
     * @param $from_id
     * @param $type
     * @return VillageQywxChatColumnSet|int|string
     */
    public function chatColumn($txt_file,$from_id,$type)
    {
        if($type == 1){
            $where[] = ['village_id','=',$from_id];
        }else{
            $where[] = ['property_id','=',$from_id];
        }
        $where[] = ['type','=',$type];
        $dbVillageQywxChatColumnSet = new VillageQywxChatColumnSet();
        $info = $dbVillageQywxChatColumnSet->getFind($where);
        if($info){
//            $url = cfg('site_url').$info['txt_file'];
//            rmdir($url);
//            $urls = '.'.$info['txt_file'];
//            unlink($urls);

            $res = $dbVillageQywxChatColumnSet->editFind($where,['txt_file'=>$txt_file]);
        }else{
            $arr = [
                'txt_file'=>$txt_file,
                'type'=>$type,
                'add_time'=>time(),
            ];
            if($type == 1){
                $arr['village_id'] = $from_id;
            }else{
                $arr['property_id'] = $from_id;
            }
            $res = $dbVillageQywxChatColumnSet->addFind($arr);
        }
        return $res;
    }

    /**
     * Notes:聊天侧边栏链接
     * @param $from_id
     * @param $type
     * @return mixed
     */
    public function setColumn($from_id,$type)
    {
        if($type == 1){
            $param_url = 'from_type=1&from_id='.$from_id;
        }else{
            $param_url = 'from_type=2&from_id='.$from_id;
        }
        $user_url = cfg('site_url') . '/packapp/qyweixin/#/pages/index/index?' .$param_url;
        $content_url = cfg('site_url') . '/packapp/workweixin/DevelopmentStation.html?' .$param_url;
        $ico = 'http://wework.qpic.cn/bizmail/VeW5ZhyQFlwpg1Izf41eHtvxM0oSeRDiaiaICrlp8ib7iaKQKFYHVADvuw/0';
        $qyWeChat = 'https://work.weixin.qq.com/wework_admin/loginpage_wx';
        $site_url = str_replace(['https://', 'http://'], '', cfg('site_url'));
        $domain_name_img = cfg('site_url') . '/static/images/qiyeweixin/chatSidebar.png';
        $data['info'] = [
            'site_url'=>$site_url,
            'content_url'=>$content_url,
            'user_url'=>$user_url,
            'ico_url'=>$ico,
            'qyWeChat'=>$qyWeChat,
            'domain_name_img'=>$domain_name_img,
        ];
        // 获取对应物业后台当前应用
        $where_qywx_agent = [];
        if($type == 1){
            $where_qywx_agent[] = ['village_id', '=', $from_id];
        }else{
            $where_qywx_agent[] = ['property_id', '=', $from_id];
            $serviceQywx = new QywxService();
            // 获取下应用信息
//            $serviceQywx->getAgentList($from_id);
        }
        $where_qywx_agent[] = ['type', '=', 1];
        $where_qywx_agent[] = ['is_close', '=', 0];
        $dbVillageQywxAgent = new VillageQywxAgent();
        $agent_info = $dbVillageQywxAgent->getOne($where_qywx_agent,'id,agentid,name,square_logo_url,description,secret');
        if (!empty($agent_info)) {
            $agent_info = $agent_info->toArray();
            !empty($agent_info) && $agent_info['is_secret'] = true;
        } else {
            $agent_info = [];
        }
        $data['agent_info'] = $agent_info;

        return $data;
    }

    /**
     * Notes:获取组及组内容
     * @datetime: 2021/3/20 15:34
     * @param $from_id
     * @param $type
     * @param $page
     * @param $limit
     */
    public function getGroupContent($data,$page,$limit)
    {
        $from_id = $data['from_id'];
        if($data['from_type'] == 1){
//            $where[] = ['c.from_type','=',$from_type];
//            $where[] = ['c.from_id','=',$from_id];
            $where = [];
            $sHouseVillage = new HouseVillageService();
            $where_v[] = ['village_id','=',$from_id];
            $village_info = $sHouseVillage->getHouseVillageInfo($where_v,'property_id');
            $property_id = $village_info['property_id'];
            $where_or="((c.from_type=2 and c.from_id=$property_id) or (c.from_type=1 and c.from_id = $from_id))";
        }else{
            $dbHouseVillage = new HouseVillage();
            $map[] = ['property_id','=',$from_id];
            $map[] = ['status','in',[0,1]];
            $village_id_arr = $dbHouseVillage->getColumn($map,'village_id');
            if($village_id_arr && count($village_id_arr)>0)
            {
                $village_id_a = implode(',',$village_id_arr);
                $where_or = "((c.from_type=2 and c.from_id=$from_id) or (c.from_type=1 and c.from_id in ($village_id_a)))";
            }else{
                $where[] = ['c.from_type','=',$data['from_type']];
                $where[] = ['c.from_id','=',$from_id];
                $where_or='';
            }
            $property_id = $from_id;
        }

//        $appWx_info = $this->turnPointTo($property_id);
//        if($appWx_info){
//            $app_id = $appWx_info['appid'];
//        }else{
//            $app_id = '';
//        }
        $app_id = '';
        if (!empty($where)) {
            $where[] = ['c.status','=',0];
            $where[] = ['g.status', '=', 0];
            if($data['title']){
                $where[] = ['c.title','like','%'.$data['title'].'%'];
            }
            if($data['type']){
                $where[] =['c.type','=',$data['type']];
            }
            if($data['gid']){
                $where[] =['c.gid','=',$data['gid']];
            }
        } elseif ($where_or) {
            $where_or .= ' AND c.status=0';
            $where_or .= ' AND g.status=0';
            if($data['title']){
                $where_or .= " AND c.title like '%{$data['title']}%'";
            }
            if($data['type']){
                $where_or .= " AND c.type = {$data['type']}";
            }
            if($data['gid']){
                $where_or .= " AND c.gid = {$data['gid']}";
            }
        }
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $field = 'c.*,g.name';
        $list = $dbVillageQywxEngineContent->getList($where,$field,'c.id desc',$page,$limit,$where_or);
        if (!empty($list)) {
            $list = $list->toArray();
        } else {
            $list = [];
        }

        $new_arr = [];
        foreach ($list as $key=>$val){
            $title = '【'.$val['name'].'】'.$val['title'];
            if($val['type'] == 4 && $val['content'] && $app_id){//功能库

                if($app_id)
                {
//                    $parma_urls = '&qywx_user_id='.$qywx_user_id;
                    $val['content'] ='/pages/village_menu/index?redirect=webview&webview_url='.urlencode($val['content']);
                }
//                else{
//                    $contain = strstr($val['content'], '?');
//                    if($contain)
//                    {
//                        $param_urls = '&qywx_user_id='.$qywx_user_id;
//                    }else{
//                        $param_urls = '?qywx_user_id='.$qywx_user_id;
//                    }
//                    $val['content'] =$val['content'].$param_urls;
//                }
            }
            $new_arr[$key] = [
                'id'=>$val['id'],
                'title'=>$title,
                'content'=>$val['content'],
                'type'=>$val['type'],
            ];
            if($val['type'] == 2){
                $new_arr[$key]['content'] = dispose_url($val['content']);
            }
            if($val['type'] ==3){
                $new_arr[$key]['content'] = dispose_url($val['content']);
                $new_arr[$key]['ico_url'] = $this->fileIco($val['file_type']);
            }
            if($val['type'] <> 1) {
                if ($val['created_at'] - 1800 < time()) {
                    $new_arr[$key]['media_id'] = '';
                } else {
                    $new_arr[$key]['media_id'] = $val['media_id'];
                }
            }
            if($val['type'] == 4){
//                unset($new_arr[$key]['content']);
                unset($new_arr[$key]['ico_url']);
                $default_img = dispose_url('/v20/public/static/community/qywx/share.png');
                $new_arr[$key]['share_title']=$val['share_title'];
                $new_arr[$key]['share_dsc']=$val['share_dsc']?$val['share_dsc']:'';
                $new_arr[$key]['share_img']=dispose_url($val['share_img'])?dispose_url($val['share_img']):$default_img;
                $new_arr[$key]['appid'] = $app_id;

            }
        }
        return $new_arr;
    }

    /**
     * Notes:调转指向 是调转物业小程序还是平台小程序  还是H5
     * @datetime: 2021/3/23 11:10
     * @param $property_id
     * @return array|bool|string|\think\Model|null
     */
    public function turnPointTo($property_id)
    {
        $qywx_open_applet_set = cfg('qywx_open_applet_set');
        if($qywx_open_applet_set){//物业小程序
            $weixin_app_bind = new WeixinAppBind();
            $where[] = ['other_id','=',$property_id];
            $weixin_app = $weixin_app_bind->getFind($where,'appid,appsecret');
            $platform_wx_appid = cfg('pay_wxapp_appid');//平台小程序id
            if($weixin_app){//先返回客户物业小程序id
                return $weixin_app;
            }elseif($platform_wx_appid){//平台小程序id
                $platform_wx_app['appid'] = $platform_wx_appid;
                return $platform_wx_app;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * Notes:分组信息
     * @datetime: 2021/3/23 11:10
     * @param $type
     * @param $param_id
     * @return array
     */
    public function getGroupList($type,$param_id)
    {
        if($type == 1){
            $where[] = ['type','=',$type];
            $where[] = ['village_id','=',$param_id];
            $where_or='';
        }else{
            $dbHouseVillage = new HouseVillage();
            $map[] = ['property_id','=',$param_id];
            $map[] = ['status','in',[0,1]];
            $village_id_arr = $dbHouseVillage->getColumn($map,'village_id');
            if($village_id_arr && count($village_id_arr)>0)
            {
                $where = [];
                $village_id_a = implode(',',$village_id_arr);
                $where_or ="status=0 AND ((type=2 AND property_id=$param_id) or (type=1 AND village_id in ($village_id_a)))";
            }else{
                $where[] = ['type','=',$type];
                $where[] = ['property_id','=',$param_id];
                $where_or='';
            }
        }
        if (!empty($where)) {
            $where[] = ['status','=',0];
        }
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $total_sum_number = $dbVillageQywxEngine->getSum($where,'number',$where_or);
        $arr_data=[
            'id'=>0,
            'title'=>'所有 ('.$total_sum_number.')',
        ];
        $c_arr = [
            'id'=>-1,
            'title'=>'渠道码 ('.$total_sum_number.')',
        ];
        $list = $dbVillageQywxEngine->getList($where,true,'id desc',0,10,$where_or);
        $new_list = [];
        $new_list[] = $arr_data;
//        $new_list[] = $c_arr;
        if($list) {
            $list = $list->toArray();
            foreach ($list as $key => $val) {
                if ($val['pid'] == 0) {
                    $map = [];
                    $map[] = ['pid', '=', $val['id']];
                    $map[] = ['status', '=', 0];
                    $sum_number = $dbVillageQywxEngine->getSum($map, 'number');
                    $sum_number = $sum_number + $val['number'];
                } else {
                    $sum_number = $val['number'];
                }
                $new_list[$key+1] = [
                    'id' => $val['id'],
                    'title' => $val['name'] . ' (' . $sum_number . ')',
                ];
            }
        }
        return $new_list;
    }

    /**
     * Notes: type信息
     * @datetime: 2021/3/23 11:09
     * @return mixed
     */
    public function getHeadType()
    {
        $data = [
            ['title'=>'文本','type'=>1],
            ['title'=>'图片','type'=>2],
            ['title'=>'文件','type'=>3],
            ['title'=>'功能库','type'=>4],
//            ['title'=>'渠道码','type'=>5],
        ];
        $list['data'] =$data;
        return $list;
    }

    /**
     * Notes: 上传素材
     * @datetime: 2021/3/23 11:09
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function uploadMatter($id)
    {
        $village_qywx_engine_content = new VillageQywxEngineContent();

        $where_c[] = ['id','=',$id];
        $engine_content_info = $village_qywx_engine_content->getFind($where_c);
        if(!in_array($engine_content_info['type'],[2,3])){
            return false;
        }
        if($engine_content_info['from_type'] == 1){
            $sHouseVillage = new HouseVillageService();
            $where[] = ['village_id','=',$engine_content_info['from_id']];
            $village_info = $sHouseVillage->getHouseVillageInfo($where,'property_id');
            $property_id = $village_info['property_id'];
        }else{
            $property_id = $engine_content_info['from_id'];
        }
        if(($engine_content_info['created_at']+86400*3)<time() || !$engine_content_info['media_id'])
        {
//            $file_url = dispose_url($engine_content_info['content']);
//            $file_data =getimagesize($file_url);
//            $header_array = get_headers($file_url, true);
//            $size = $header_array['Content-Length'];
//            $file =(object)[
//                'name'=>$engine_content_info['title'],
//                'filename'=>$engine_content_info['title'],
//                'size'=>$size,
//                'type'=>$file_data['mime'],
//            ];
            if($engine_content_info['type'] == 2){
                $type = 'image';
            }else{
                $type = 'file';
            }
            $sEnterpriseWeChatService = new EnterpriseWeChatService();
//            $wxUpload = $sEnterpriseWeChatService->qyWxUpload($file,$property_id,$type);
            $title = isset($engine_content_info['title'])&&$engine_content_info['title']?$engine_content_info['title']:'';
            $wxUpload = $sEnterpriseWeChatService->uploadImg($engine_content_info['content'],$property_id,$type,$title);
            if($wxUpload){
                $data['media_id'] = $wxUpload['media_id'];
                $data['created_at'] = $wxUpload['created_at'];
                $village_qywx_engine_content->editFind($where_c,$data);
            }else{
                $data['media_id'] = '';
                $data['created_at'] = '';
            }
            return $data;
        }else{
            $data['media_id'] = $engine_content_info['media_id'];
            $data['created_at'] = $engine_content_info['created_at'];
            return $data;
        }
    }

    /**
     * Notes:导入表格
     * @param $path
     * @param $from_id
     * @param $type
     * @param $userId
     * @return int|string
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function importExcel($path,$from_id,$type,$userId,$gids){

        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $where =[];
        if($type ==1){
            $where[] = ['village_id','=',$from_id];
        }else{
            $where[] = ['property_id','=',$from_id];
        }
        $where[] = ['type','=',$type];
        $first = $dbVillageQywxEngine->getFind($where,'id');
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet =$reader->load("$path");

        //载入excel表格
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        //总行数
        for ($row =3; $row <=$highestRow; $row++) {
            $group_name = $worksheet->getCellByColumnAndRow(1, $row)->getValue();

            $title =$worksheet->getCellByColumnAndRow(2, $row)->getValue();
            if(utf8_strlen($title)>50){
                continue;
            }
            $content = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            if(utf8_strlen($content)>1000){
                continue;
            }
            $where =[];
            if($type ==1){
                $where[] = ['village_id','=',$from_id];
            }else{
                $where[] = ['property_id','=',$from_id];
            }
            $where[] = ['name','=',$group_name];
            $where[] = ['type','=',$type];
            $info = $dbVillageQywxEngine->getFind($where,'id');
            if($info){
                $gid = $info['id'];
            }elseif($gids){
                $gid = $gids;
            }else{
                $gid = $first['id'];
            }
            $number = $dbVillageQywxEngine->getFind(['id'=>$gid],'number');
            $new_number = $number['number']+1;
            $dbVillageQywxEngine->editFind(['id'=>$gid],['number'=>$new_number]);
            if(!$title){
                $title =mb_substr($content,0,50,'utf-8');
            }
            $data = [
                'gid'=>$gid,
                'from_type'=>$type,
                'from_id'=>$from_id,
                'type'=>1,
                'title'=>$title,
                'content'=>$content,
                'add_uid'=>$userId,
                'add_time'=>time()
            ];
            $res = $dbVillageQywxEngineContent->addFind($data);
        }
        return $res;
    }

    /**
     * Notes:下载表格
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function downloadTab()
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActivesheet();
        //表格名称
        $worksheet->setTitle('内容引擎表');
        ////表头设置单元格内容
        $msg = "导入模板（请按照以下格式填写) ".PHP_EOL." (目前仅支持文字内容;没有分组的自动导入至默认分组中;没有标题的自动截取话术内容前50个字)";
        $worksheet->setCellValueByColumnAndRow(1, 1, $msg);
        $worksheet->setCellValueByColumnAndRow(1, 2, '分组');
        $worksheet->setCellValueByColumnAndRow(2, 2,'标题（50个字以内）');
        $worksheet->setCellValueByColumnAndRow(3, 2, '话术内容');
        //合并单元格
        $worksheet->mergeCells('A1:C1');
        $styleArray=[
            'alignment' => [
                 'horizontal' =>Alignment::HORIZONTAL_CENTER,
            ]
        ];
        $len = 0;
        //设置单元格样式
        $worksheet->getStyle('A1')->applyFromArray($styleArray)->getFont()->getColor()->setRGB('FF0000');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(30);
        $worksheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(50);
        $styleArrayBody =[
            'borders' => [
                'allBorders' => [
                    'borderstyle' => Border::BORDER_THIN,//边框
                    'color' => ['argb' => '666666'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,//居中
                ],
            ]
        ];
        //设置列充
        $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(30);
        $total_rows =$len +2;//添加所有边框并字体设置居中
        $worksheet->getStyle('A1:C'.$total_rows)->applyFromArray($styleArrayBody);
        $filename ='内容引擎表.xlsx';
        header('content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header( 'content-Disposition: attachment;filename=" '.$filename. '"');
        header('Cache-control:max-age-0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    /**
     * Notes: 创建默认套餐
     * @datetime: 2021/3/23 18:38
     * @param $uid
     * @param $from_type
     * @param $from_id
     * @return
     */
    public function defaultApplication($uid,$from_type,$from_id)
    {
        $dbVillageQywxEngine = new VillageQywxEngineGroup();
        $sHouse_village = new HouseVillageService();
        $dbVillageQywxEngineContent = new VillageQywxEngineContent();
        if($from_type == 1){
            $find_where[] = ['village_id','=',$from_id];
        }else{
            return false;
        }
        $find_where[] = ['type','=',$from_type];
        $find_where[] = ['status','=',0];
        $find_where[] = ['pid','=',0];
        $find_where[] = ['is_default','=',1];
        $info =$dbVillageQywxEngine->getFind($find_where);
        if(!$info){
            return false;
        }
        $gid = $info['id'];
        $service_house_village = new HouseVillageService();
        $app_version = isset($_POST['app_version'])&&$_POST['app_version']?intval($_POST['app_version']):0;
        $deviceId = isset($_POST['Device-Id'])&&$_POST['Device-Id']?trim($_POST['Device-Id']):0;
        $param = [
            'pagePath' => 'pages/village/my/',
            'isAll' => true,
        ];
        $pagesMyUrl = $service_house_village->villagePagePath($app_version,$deviceId,$param);
        if($from_type ==1) {
            $param_url = '?village_id=' . $from_id;
            $param_urls = '&village_id=' . $from_id;

            $base_url = $sHouse_village->base_url;
            $data = [
                [
                    'gid' => $gid,
                    'from_type' => $from_type,
                    'from_id' => $from_id,
                    'type' => 4,
                    'title' => '入住房屋',
                    'content' => $pagesMyUrl . 'myVillage',
                    'add_time' => time(),
                    'share_title' => '入住房屋',
                    'share_dsc' => '点击入住xx小区',
                    'share_img' => '',
                    'add_uid' => $uid,
                    'is_default_title'=>'house',
                    'is_default'=>1,
                ],
                [
                    'gid' => $gid,
                    'from_type' => $from_type,
                    'from_id' => $from_id,
                    'type' => 4,
                    'title' => '在线缴费',
                    'content' => $pagesMyUrl . 'payMentlist' . $param_url,
                    'add_time' => time(),
                    'share_title' => '在线缴费',
                    'share_dsc' => '点击查询缴纳您的物业费用',
                    'share_img' => '',
                    'add_uid' => $uid,
                    'is_default_title'=>'online_pay',
                    'is_default'=>1,
                ],
                [
                    'gid' => $gid,
                    'from_type' => $from_type,
                    'from_id' => $from_id,
                    'type' => 4,
                    'title' => '在线报修',
                    'content' => $pagesMyUrl . 'waterReportlist?type=1&title=' . urlencode('物业报修') . $param_urls,
                    'add_time' => time(),
                    'share_title' => '在线报修',
                    'share_dsc' => '点击提交报修内容',
                    'share_img' => '',
                    'add_uid' => $uid,
                    'is_default_title'=>'online_service',
                    'is_default'=>1,
                ],
                [
                    'gid' => $gid,
                    'from_type' => $from_type,
                    'from_id' => $from_id,
                    'type' => 4,
                    'title' => '访客登记',
                    'content' => $pagesMyUrl . 'visitorRegistration' . $param_url,
                    'add_time' => time(),
                    'share_title' => '访客登记',
                    'share_dsc' => '线上邀请您的访客，方便快捷',
                    'share_img' => '',
                    'add_uid' => $uid,
                    'is_default_title'=>'visitor_registration',
                    'is_default'=>1,
                ],
            ];
            foreach ($data as $val){
                $map = [];
                $map[] = ['from_type','=',$from_type];
                $map[] = ['from_id','=',$from_id];
                $map[] = ['type','=',4];
                $map[] = ['is_default','=',1];
                $map[] = ['is_default_title','=',$val['is_default_title']];
                $info = $dbVillageQywxEngineContent->getFind($map);
                if(!$info){
                    $dbVillageQywxEngineContent->addFind($val);
                }
            }

        }
        return true;
    }

    public function addAgent($from_id,$type,$data) {
        $dbVillageQywxAgent = new VillageQywxAgent();
        $reset_save = [];
        $reset_where = [];
        if (isset($data['id']) && $data['id']) {
            $where_qywx_agent = [];
            $where_qywx_agent[] = ['id', '=', $data['id']];
            $where_qywx_agent[] = ['type', '=', 1];
            $agent_info = $dbVillageQywxAgent->getOne($where_qywx_agent);
            if (empty($agent_info)) {
                throw new \think\Exception('编辑对象不存在或者已经被删除');
            }
            if ((!isset($data['secret']) || !$data['secret']) && isset($agent_info['secret']) && $agent_info['secret']) {
                $data['secret'] = $agent_info['secret'];
            }
            $agent_info = $agent_info->toArray();
            if($type == 1 && $agent_info['village_id'] && $agent_info['village_id']!=$from_id){
                throw new \think\Exception('当前对象您不能编辑');
            }elseif ($type == 2 && $agent_info['property_id'] && $agent_info['property_id']!=$from_id){
                throw new \think\Exception('当前对象您不能编辑');
            }
            $id = $data['id'];
            $where_qywx_agent = [];
            if($type == 1){
                $where_qywx_agent[] = ['village_id', '=', $from_id];
            }else{
                $where_qywx_agent[] = ['property_id', '=', $from_id];
            }
            $where_qywx_agent[] = ['type', '=', 1];
            $now_agent_info = $dbVillageQywxAgent->getOne($where_qywx_agent);
            // 获取对应身份当前选中的应用
            if ($now_agent_info['id']!=$id) {
                if($type == 1){
                    $reset_save['village_id'] = 0;
                }else{
                    $reset_save['property_id'] = 0;
                }
                $reset_save['last_time'] = time();
                $reset_where[] = ['id','=',$now_agent_info['id']];
            }
        } else {
            $where_qywx_agent = [];
            if($type == 1){
                $where_qywx_agent[] = ['village_id', '=', $from_id];
            }else{
                $where_qywx_agent[] = ['property_id', '=', $from_id];
            }
            $where_qywx_agent[] = ['type', '=', 1];
            $where_qywx_agent[] = ['is_close', '=', 0];
            $agent_info = $dbVillageQywxAgent->getOne($where_qywx_agent);
            if (!empty($agent_info)) {
                $agent_info = $agent_info->toArray();
                $id = $agent_info['id'];
                if ((!isset($data['secret']) || !$data['secret']) && isset($agent_info['secret']) && $agent_info['secret']) {
                    $data['secret'] = $agent_info['secret'];
                }
            }
//            else {
//                throw new \think\Exception('请登录企业微信官方后台，在应用管理-应用-自建应用');
//            }
        }


        $set_data = [];
        if (isset($data['agentid']) && $data['agentid']) {
            $set_data['agentid'] = $data['agentid'];
            $serviceQywx = new QywxService();
            $agent_get_data = $serviceQywx->getAgentDetail($from_id, $data['agentid'], $data['secret']);
            fdump_api(['获取企业微信应用》》'.__LINE__,$from_id,$data,$agent_get_data],'qyweixin/get_qy_agent_log',true);
            if (!empty($agent_get_data['errcode'])) {
                $errmsg = GetErrorMsg::qiyeErrorCode($agent_get_data['errcode']);
                if (!$errmsg) {
                    $errmsg = isset($agent_get_data['errmsg']) ? $agent_get_data['errmsg'] :'';
                }
                throw new \think\Exception($errmsg);
            } else {
                // 处理下数据
                if (isset($agent_get_data['name']) && $agent_get_data['name']) {
                    $set_data['name'] = $agent_get_data['name'];
                }
                if (isset($agent_get_data['square_logo_url']) && $agent_get_data['square_logo_url']) {
                    $set_data['square_logo_url'] = $agent_get_data['square_logo_url'];
                }
                if (isset($agent_get_data['description']) && $agent_get_data['description']) {
                    $set_data['description'] = $agent_get_data['description'];
                }
                if (isset($agent_get_data['groupid']) && $agent_get_data['groupid']) {
                    $set_data['groupid'] = $agent_get_data['groupid'];
                }
                if (isset($agent_get_data['is_close']) && $agent_get_data['is_close']) {
                    $set_data['is_close'] = $agent_get_data['is_close'];
                }
                if (isset($agent_get_data['suiteid']) && $agent_get_data['suiteid']) {
                    $set_data['suiteid'] = $agent_get_data['suiteid'];
                }
                if (isset($agent_get_data['suiteid']) && $agent_get_data['suiteid']) {
                    $set_data['suiteid'] = $agent_get_data['suiteid'];
                }
                if (isset($agent_get_data['order'])) {
                    $set_data['order'] = $agent_get_data['order'];
                }
            }
        }
        if (isset($data['secret']) && $data['secret']) {
            $set_data['secret'] = $data['secret'];
        }
        if($type == 1){
            $set_data['village_id'] = $from_id;
        }else{
            $set_data['property_id'] = $from_id;
        }
        if ($id) {
            $where_save = [];
            $where_save[] = ['id', '=', $id];
            $set_data['last_time'] = time();
            if (!isset($agent_info['bind_time']) || !$agent_info['bind_time']) {
                $set_data['bind_time'] = time();
            }
            $set = $dbVillageQywxAgent->updateThis($where_save,$set_data);
        } else {
            $set_data['bind_time'] = time();
            $set = $dbVillageQywxAgent->add($set_data);
        }
        if ($set!==false) {
            // 将原来的选中应用重置
            if (!empty($reset_save)) {
                $dbVillageQywxAgent->updateThis($reset_where, $reset_save);
            }
            return $id;
        } else {
            throw new \think\Exception('操作失败');
        }
    }
}
