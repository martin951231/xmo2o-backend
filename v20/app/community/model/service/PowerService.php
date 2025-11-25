<?php
/**
 * @author : liukezhu
 * @date : 2022/2/25
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseVillage;
use app\community\model\db\PropertyAdmin;
use app\community\model\service\PropertyFrameworkService;
use app\community\model\db\PropertyAdminAuth;
use app\community\model\db\HouseWorker;
use customization\customization;

class PowerService
{
    use customization;
    protected $PropertyAdmin;
    protected $PropertyFrameworkService;

    public function __construct()
    {
        $this->PropertyAdmin = new PropertyAdmin();
        $this->PropertyFrameworkService=new PropertyFrameworkService();
    }

    /**
     * 权限用户列表
     * @author: liukezhu
     * @date : 2022/2/25
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getRoleList($where,$field='*',$page=0,$limit=10,$order='id DESC'){
        $list = $this->PropertyAdmin->getList($where,$field,$order,$page,$limit);
        $houseWorkerDb=new HouseWorker();
        if($list){
            $statusArr=array('禁用','正常','已禁用','','已注销');
            foreach ($list as $kk=>$vv){
                $list[$kk]['set_pwd']=0;
                $list[$kk]['is_property_worker']='否';
                $list[$kk]['hw_id']=0;
                if($vv['wid']>0){
                    $houseWorker=$houseWorkerDb->get_one(['wid'=>$vv['wid']],'*');
                    if($houseWorker && !$houseWorker->isEmpty() && isset($houseWorker['hw_id'])){
                        $list[$kk]['hw_id']=$houseWorker['hw_id'];
                        if($houseWorker['hw_id']>0){
                            $list[$kk]['is_property_worker']='是';
                        }
                    }
                }
                if(!empty($vv['pwd'])){
                    $list[$kk]['set_pwd']=1;
                }
                $list[$kk]['status_str']=$statusArr[$vv['status']];
                unset($list[$kk]['pwd']);
                $list[$kk]['list_phone']='';
                if(isset($vv['phone']) && !empty($vv['phone'])){
                    $list[$kk]['list_phone']=phone_desensitization($vv['phone']);
                }
            }

        }
        $count = $this->PropertyAdmin->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }
    public function getOneData($where,$field=true)
    {
        // 初始化 数据层
        $propertyAdminDb = new PropertyAdmin();
        $info = $propertyAdminDb->get_one($where,$field);
        if ($info && !$info->isEmpty()) {
            $info=$info->toArray();
        }else{
            $info = [];
        }
        return $info;
    }

    //更新数据
    public function updatePropertyAdmin($where = array(), $updateArr = array(), $now_admin = array())
    {
        if (empty($where) || empty($updateArr)) {
            return false;
        }
        $propertyAdminDb = new PropertyAdmin();
        $ret = $propertyAdminDb->save_one($where, $updateArr);
        if(!empty($now_admin) && $now_admin['wid']>0 && isset($updateArr['phone'])){
            $whereArr=array();
            $whereArr['wid']=$now_admin['wid'];
            $db_HouseWorker = new HouseWorker();
            $updateData=array();
            $updateData['phone']=$updateArr['phone'];
            if(isset($updateArr['account']) && !empty($updateArr['account'])){
                $updateData['account']=$updateArr['account'];
            }
            if(isset($updateArr['pwd']) && !empty($updateArr['pwd'])){
                $updateData['password']=$updateArr['pwd'];
            }
            if(isset($updateArr['realname']) && !empty($updateArr['realname'])){
                $updateData['name']=$updateArr['realname'];
            }
            if(isset($updateArr['remarks'])){
                $updateData['remarks']=$updateArr['remarks'];
            }
            $ret = $db_HouseWorker->editData($whereArr, $updateData);
        }
        return $ret;
    }

    /**
     *删除角色
     * @author: liukezhu
     * @date : 2022/2/28
     * @param $property_id
     * @param $id
     * @return array
     * @throws \think\Exception
     */
    public function roleDel($property_id,$id){
        $admin=$this->PropertyAdmin->get_one([['property_id','=',$property_id], ['id','=',$id]]);
        if(!$admin || $admin->isEmpty()){
            throw new \think\Exception("数据不存在");
        }
        if($admin['wid'] > 0){
            $where=[
                ['wid','=',$admin['wid']]
            ];
            return $this->PropertyFrameworkService->businessWorkerDel($property_id,$where);
        }
        else{
           $this->PropertyAdmin->delPropertyAdmin(['id'=>$admin['id'],'property_id'=>$property_id]);
           return ['error'=>true,'msg'=>'删除成功'];
        }

    }

    public function getPropertyRolePermissionData()
    {
        $menus_arr1 = array(
            ['id' => 'property_2', 'fid' => 0, 'name' => '控制台'],
            ['id' => 'property_20', 'fid' => 'property_2', 'name' => '物业服务套餐'],
            ['id' => 'property_21', 'fid' => 'property_20', 'name' => '订购功能套餐'],
            ['id' => 'property_21_1', 'fid' => 'property_20', 'name' => '订购房间套餐'],
            ['id' => 'property_25', 'fid' => 'property_20', 'name' => '我订购的功能套餐'],
            ['id' => 'property_26', 'fid' => 'property_20', 'name' => '我订购的房间套餐'],
            ['id' => 'property_2_2', 'fid' => 'property_2', 'name' => '组织架构'],
            ['id' => 'property_2_3', 'fid' => 'property_2', 'name' => '权限管理'],
            ['id' => 'property_2_4', 'fid' => 'property_2', 'name' => '人员管理'],
            ['id' => 'property_2_4_1', 'fid' => 'property_2_4', 'name' => '添加人员'],
            ['id' => 'property_2_4_2', 'fid' => 'property_2_4', 'name' => '编辑'],
            ['id' => 'property_2_4_3', 'fid' => 'property_2_4', 'name' => '禁用账号'],
            ['id' => 'property_2_4_4', 'fid' => 'property_2_4', 'name' => '删除'],
            ['id' => 'property_3', 'fid' => 0, 'name' => '数据中心'],
            //['id' => 'property_3_1', 'fid' => 'property_3', 'name' => '小区列表'],
            ['id' => 'property_3_2', 'fid' => 'property_3', 'name' => '业主档案设置'],
        );
        $configCustomizationService= new ConfigCustomizationService();
        $jz_wsxdsj_net_customized_open = $configCustomizationService->getWSXCustomizedOpen();
        if ($jz_wsxdsj_net_customized_open) {
            $menus_arr1[] = ['id'=>'property_3_4_1','fid'=>'property_3','name'=>'质保明细'];
        }
        $isAihorseCustomized = $configCustomizationService->getAihorseCustomizedJudge();
        $menus_arr2 = array(
            ['id' => 'property_3_4', 'fid' => 'property_3', 'name' => '广告设置'],
            ['id' => 'property_4', 'fid' => 0, 'name' => '缴费管理'],
            ['id' => 'property_4_2', 'fid' => 'property_4', 'name' => '收费科目管理'],
            ['id' => 'property_4_3', 'fid' => 'property_4', 'name' => '线下支付方式管理'],
            ['id' => 'property_4_4', 'fid' => 'property_4', 'name' => '费用统计分析'],
        );

        $menus_arr2[]= ['id' => 'property_4_5', 'fid' => 'property_4', 'name' => '票据编号管理'];
        if($isAihorseCustomized){
            $menus_arr2['4']['name']='收费率';
            $menus_arr2[]= ['id' => 'property_41_1', 'fid' => 'property_4', 'name' => '小区账单报表'];
            $menus_arr2[]= ['id' => 'property_41_2', 'fid' => 'property_4', 'name' => '费用费率统计'];
        }
        $menus_arr3 =array(
            /*
            ['id' => 'property_3_3', 'fid' => 'property_4', 'name' => '账单服务'],
            ['id' => 'property_33_1', 'fid' => 'property_3_3', 'name' => '收支流水'],
            ['id' => 'property_33_2', 'fid' => 'property_3_3', 'name' => '物业流水'],
            */
            ['id' => 'property_10', 'fid' => 0, 'name' => '企业微信SCRM'],
            ['id' => 'property_8', 'fid' => 0, 'name' => '微信公众号'],
            ['id' => 'property_81', 'fid' => 'property_8', 'name' => '公众号绑定'],
            ['id' => 'property_82', 'fid' => 'property_8', 'name' => '模板消息'],
            ['id' => 'property_83', 'fid' => 'property_8', 'name' => '自动回复'],
            ['id' => 'property_83', 'fid' => 'property_8', 'name' => '自定义菜单'],
            ['id' => 'property_84', 'fid' => 'property_8', 'name' => '关键词回复'],
            ['id' => 'property_84', 'fid' => 'property_8', 'name' => '图文素材'],
            ['id' => 'property_9', 'fid' => 0, 'name' => '微信小程序'],
            ['id' => 'property_10_2', 'fid' => 'property_10', 'name' => '渠道活码'],
            ['id' => 'property_10_3', 'fid' => 'property_10', 'name' => '添加渠道码'],
            ['id' => 'property_10_4', 'fid' => 'property_10', 'name' => '内容引擎'],
            ['id' => 'property_10_5', 'fid' => 'property_10', 'name' => '群发消息'],
            ['id' => 'property_10_8', 'fid' => 'property_10', 'name' => '数据运营中心'],
            ['id' => 'property_10_9', 'fid' => 'property_10', 'name' => '会话存档'],
            ['id' => 'property_7', 'fid' => 0, 'name' => '商家管理'],
            ['id' => 'property_12', 'fid' => 0, 'name' => '功能应用库'],

        );
        $menus_arr2 = array_merge($menus_arr2, $menus_arr3);
        $menus_arr = array_merge($menus_arr1, $menus_arr2);
        return $menus_arr;
    }

    public function getTrees($array,$application_id=[],$role_menus=[],$removeIds=array(),$village_id=0){
        //第一步 构造数据
        $houseName = (new HouseVillageService())->getHouseAlias('house_name',$village_id) ?: '小区';
        $singleName = (new HouseVillageService())->getHouseAlias('single_name',$village_id) ?: '楼栋';
        $floorName = (new HouseVillageService())->getHouseAlias('floor_name',$village_id) ?: '单元';
        $roomName = (new HouseVillageService())->getHouseAlias('room_name',$village_id) ?: '房间';
        $roomNames = (new HouseVillageService())->getHouseAlias('room_name',$village_id) ?: '房屋';
        $items = array();
        $removeArr=array_merge($removeIds,array(9,11,270,271,430)) ;
        foreach($array as $value){
            if($value['name'] == '单元楼层'){
                $value['name'] = $floorName ? ($floorName.'管理') : '单元楼层';
            }else{
                $value['name'] = str_replace(['小区','楼栋','单元','房间','房屋'],['{小区}','{楼栋}','{单元}','{房间}','{房屋}'],$value['name']);//防止重复替换
                $value['name'] = str_replace(['{小区}','{楼栋}','{单元}','{房间}','{房屋}'],[$houseName,$singleName,$floorName,$roomName,$roomNames],$value['name']);
            }
            if (in_array($value['id'],$removeArr)) {
                continue;
            } elseif($value['id']==194) {
                $value['name'] = '我的应用';
            }elseif ($value['id']==10){
                $value['name'] = '老版管理员数据';
            }elseif ($value['id']==296){
                $value['name'] = '组织架构(人员)-删除 ';
            }
            $items[$value['id']] = $value;
        }
        //第二部 遍历数据 生成树状结构
        $tree = array();
        foreach($items as $key => $value){
            if(isset($items[$value['fid']])){
                $items[$value['fid']]['child'][] = &$items[$key];
                if (isset($items[$value['fid']]['count'])) {
                    $items[$value['fid']]['count']++;
                }else{
                    $items[$value['fid']]['count'] = 1;
                }
            } elseif($application_id===false){
                $tree[] = &$items[$key];
            }
            //处理套餐购买功能应用 start
            if($application_id!==false && $value['fid'] == 0)
            {
                if($application_id && !in_array($value['application_id'],$application_id))
                {
                    unset($items[$value['fid']]);
                    unset($items[$key]);
                }else{
                    $tree[] = &$items[$key];
                }
            }
            //处理套餐购买功能应用 end
        }
        $ckeyArr=array();
        foreach ($tree as $kk => $vv) {
            $tree[$kk]['ckey'] = 'key_0_' . $vv['id'];
            $tmpCv=false;
            if(in_array($vv['id'],$role_menus)){
                $tmpCv=true;
            }
            $ckeyArr[]=array('ckey'=>'key_0_' . $vv['id'],'cv'=>$tmpCv,'name'=>$vv['name'],'id'=>$vv['id']);
            if (isset($vv['child']) && !empty($vv['child'])) {
                foreach ($vv['child'] as $kk1 => $vv1) {
                    $tree[$kk]['child'][$kk1]['ckey'] = 'key_1_' . $vv1['id'];
                    $tmpCv=false;
                    if(in_array($vv1['id'],$role_menus)){
                        $tmpCv=true;
                    }
                    $ckeyArr[]=array('ckey'=>'key_1_' . $vv1['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'name'=>$vv1['name'],'id'=>$vv1['id']);
                    if (isset($vv1['child']) && !empty($vv1['child'])) {
                        foreach ($vv1['child'] as $kk2 => $vv2) {
                            $tree[$kk]['child'][$kk1]['child'][$kk2]['ckey'] = 'key_2_' . $vv2['id'];
                            $tmpCv=false;
                            if(in_array($vv2['id'],$role_menus)){
                                $tmpCv=true;
                            }
                            $ckeyArr[]=array('ckey'=>'key_2_' . $vv2['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'ckey1'=>'key_1_' . $vv1['id'],'name'=>$vv2['name'],'id'=>$vv2['id']);
                            if (isset($vv2['child']) && !empty($vv2['child'])) {
                                foreach ($vv2['child'] as $kk3 => $vv3) {
                                    $tree[$kk]['child'][$kk1]['child'][$kk2]['child'][$kk3]['ckey'] = 'key_3_' . $vv3['id'];
                                    $tmpCv=false;
                                    if(in_array($vv3['id'],$role_menus)){
                                        $tmpCv=true;
                                    }
                                    $ckeyArr[]=array('ckey'=>'key_3_' . $vv3['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'ckey1'=>'key_1_' . $vv1['id'],'ckey2'=>'key_2_' . $vv2['id'],'name'=>$vv3['name'],'id'=>$vv3['id']);
                                    if (isset($vv3['child']) && !empty($vv3['child'])) {
                                        foreach ($vv3['child'] as $kk4 => $vv4) {
                                            $tree[$kk]['child'][$kk1]['child'][$kk2]['child'][$kk3]['child'][$kk4]['ckey'] = 'key_4_' . $vv4['id'];
                                            $tmpCv=false;
                                            if(in_array($vv4['id'],$role_menus)){
                                                $tmpCv=true;
                                            }
                                            $ckeyArr[]=array('ckey'=>'key_4_' . $vv4['id'],'cv'=>$tmpCv,'ckey0'=>'key_0_' . $vv['id'],'ckey1'=>'key_1_' . $vv1['id'],'ckey2'=>'key_2_' . $vv2['id'],'ckey3'=>'key_3_' . $vv3['id'],'name'=>$vv4['name'],'id'=>$vv4['id']);
                                        }
                                    }

                                }

                            }

                        }

                    }
                }
            }
        }
        return array('tree'=>$tree,'ckeyArr'=>$ckeyArr);
    }

    //获取授权信息
    public function propertyAdminAuth($whereArr=array(),$field='*'){
        if(empty($whereArr)){
            return false;
        }
        $propertyAdminAuth=new PropertyAdminAuth();
        $tmpAdminAuth=$propertyAdminAuth->getOne($whereArr,$field);
        if($tmpAdminAuth && !$tmpAdminAuth->isEmpty()){
            $tmpAdminAuth=$tmpAdminAuth->toArray();
        }else{
            $tmpAdminAuth=array();
        }
        return $tmpAdminAuth;
    }
    //保存
    public function savePropertyAdminAuth($whereArr=array(),$saveData=array()){
        if(empty($whereArr) || empty($saveData)){
            return false;
        }
        $propertyAdminAuth=new PropertyAdminAuth();
        $tmpAdminAuth=$propertyAdminAuth->getOne($whereArr);
        if($tmpAdminAuth && !$tmpAdminAuth->isEmpty()){
            $saveData['update_time']=time();
            $ret=$propertyAdminAuth->editData($whereArr,$saveData);
            return $ret;
        }else{
            $saveData=array_merge($whereArr,$saveData);
            $saveData['add_time']=time();
            $ret=$propertyAdminAuth->addData($saveData);
            return $ret;
        }
    }
}