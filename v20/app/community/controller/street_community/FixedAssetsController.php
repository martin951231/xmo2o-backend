<?php
/**
 * 固定资产管理
 * @author weili
 * @date 2020/11/19
 */

namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetService;
use app\community\model\service\FixedAssetsService;
class FixedAssetsController extends CommunityBaseController
{
    /**
     * Notes: 获取分类
     * @return \json
     * @author: weili
     * @datetime: 2020/11/20 11:04
     */
    public function getClassifyNav()
    {
        $info = $this->adminUser;
        $street_id = $this->adminUser['area_id'];
        $FixedAssetsService = new FixedAssetsService();
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $list = $FixedAssetsService->getClassifyNav($street_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    //获取一级分类导航详情
    public function getClassifyNavInfo()
    {
        $street_id = $this->adminUser['area_id'];
        $FixedAssetsService = new FixedAssetsService();
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $cat_id = $this->request->param('cat_id','','intval');
        if(!$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $list = $FixedAssetsService->getClassifyInfo($cat_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 删除一级分类
     * @return \json
     * @author: weili
     * @datetime: 2020/11/23 15:39
     */
    public function delClassifyNav()
    {
        $street_id = $this->adminUser['area_id'];
        $FixedAssetsService = new FixedAssetsService();
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $cat_id = $this->request->param('cat_id','','intval');
        if(!$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $list = $FixedAssetsService->delClassify($cat_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * Notes: 添加编辑分类
     * @return \json
     * @author: weili
     * @datetime: 2020/11/20 14:22
     */
    public function operateClassifyNav()
    {
        $info = $this->adminUser;
        $street_id = $this->adminUser['area_id'];
        $cat_name = $this->request->param('cat_name','','trim');
        $cat_id = $this->request->param('cat_id',0,'intval');
        if(!$cat_name || !$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $FixedAssetsService = new FixedAssetsService();
        $data = [
            'cat_name'=>$cat_name,
            'street_id'=>$street_id,
        ];
        try{
            $service_area_street = new AreaStreetService();
            $where_street = [];
            $where_street[] = ['area_id', '=', $street_id];
            $street_info = $service_area_street->getAreaStreet($where_street);
            if ($street_info && $street_info['area_type']==0) {
                $data['community_id'] = 0;
            }else{
                $data['community_id'] = $street_id;
                $data['street_id'] = 0;
            }
            $res = $FixedAssetsService->operateClassify($data,$cat_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if(!$res){
            return api_output_error(1001,'添加失败');
        }else{
            return api_output(0,$res);
        }
    }

    /**
     * Notes: 获取一级分类列表
     * @return \json
     * @author: weili
     * @datetime: 2020/11/20 14:22
     */
    public function getClassifyList()
    {
        $street_id = $this->adminUser['area_id'];
        $FixedAssetsService = new FixedAssetsService();
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $service_area_street = new AreaStreetService();
            $whereArr=array();
            $whereArr['street_id'] = $street_id;
            $where_street = [];
            $where_street[] = ['area_id', '=', $street_id];
            $street_info = $service_area_street->getAreaStreet($where_street);
            if ($street_info && $street_info['area_type']==0) {
                $whereArr['community_id'] = 0;
            }else{
                $whereArr['community_id'] = $street_id;
                $whereArr['street_id'] = 0;
            }
            $whereArr['cat_status'] = 1;
            $list = $FixedAssetsService->getClassify($street_id,$whereArr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * Notes:添加、编辑资产
     * @return \json
     * @author: weili
     * @datetime: 2020/11/20 14:53
     */
    public function subAssets()
    {
        $street_id = $this->adminUser['area_id'];
        $FixedAssetsService = new FixedAssetsService();
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $assets_id = $this->request->param('assets_id','','intval');
        $assets_name = $this->request->param('assets_name','','trim');
        $cat_id = $this->request->param('cat_id','','intval');
        $num = $this->request->param('num','','intval');
        $price = $this->request->param('price','','trim');
        $interval_time = $this->request->param('interval_time','','trim');
        $supplier_name = $this->request->param('supplier_name','','trim');
        $supplier_phone = $this->request->param('supplier_phone','','trim');
        if(!$assets_name || !$cat_id || !$num || !$price){
            return api_output_error(1001,'必传参数缺失');
        }
        if($num>1000){
            return api_output_error(1001,'数量不能大于1000');
        }
        $data = [
            'assets_name'=>$assets_name,
            'cat_id'=>$cat_id,
            'num'=>$num,
            'price'=>$price,
            'interval_time'=>$interval_time,
            'supplier_name'=>$supplier_name,
            'supplier_phone'=>$supplier_phone,
            'street_id'=>$street_id,
        ];
        try{
            $list = $FixedAssetsService->subAssets($data,$assets_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 获取资产详情
     * @return \json
     * @author: weili
     * @datetime: 2020/11/20 16:25
     */
    public function getAssetsInfo()
    {
        $FixedAssetsService = new FixedAssetsService();
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $assets_name = $this->request->param('assets_name','','trim');
        $assets_id = $this->request->param('assets_id','','trim');
        if(!$assets_name && !$assets_id){
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $data = $FixedAssetsService->getAssetsInfo($assets_name,$assets_id,$street_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($assets_name){
            $data['type'] = 1;
        }elseif($assets_id){
            $data['type'] = 2;
        }else{
            $data['type'] = 0;
        }
        return api_output(0,$data);
    }

    /**
     * Notes:删除资产（二级分类）
     * @author: weili
     * @datetime: 2020/11/23 15:40
     */
    public function delAssets()
    {
        $street_id = $this->adminUser['area_id'];
        $FixedAssetsService = new FixedAssetsService();
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $assets_id = $this->request->param('assets_id','','intval');
        if(!$assets_id){
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $list = $FixedAssetsService->delAssets($assets_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    //获取资产信息列表
    public function getAssetsList()
    {
        $FixedAssetsService = new FixedAssetsService();
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $assets_id = $this->request->param('assets_id',0,'intval');
        $status = $this->request->param('status',0,'intval');
        $page = $this->request->param('page','','intval');
        $num = $this->request->param('num','','trim');
        $time = $this->request->param('time');
        $limit = 10;
        if(!$assets_id){
            return api_output_error(1001,'清先检查是否没有添加资产，优先添加资产');
        }
        $where = [];
        if($num){
            $where[] = ['num','=',$num];
        }
        if($time && count($time)>0){
            $where[]=['add_time','>=',strtotime($time[0])];
            $where[]=['add_time','<=',strtotime($time[1].' 23:59:59')];
        }
        try{
            $data = $FixedAssetsService->getAssetsList($assets_id,$page,$limit,$status,$where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    //提交领取租借
    public function subLedRent()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $type = $this->request->param('type','','intval');
        $name = $this->request->param('name','','trim');
        $tel = $this->request->param('tel','','trim');
        $assets = $this->request->param('assets','','trim');
        $time = $this->request->param('time','','trim');
        $trent_end_time = $this->request->param('rent_end_time','','trim');
        if(!$name){
            return api_output_error(1001,'请输入领用人名称');
        }
        if(!$tel){
            return api_output_error(1001,'请输入联系方式');
        }
        if(!$assets){
            return api_output_error(1001,'请选择领用资产');
        }
        if(!$time){
            return api_output_error(1001,'请选择领用时间');
        }
        if(!$name || !$tel || !$assets || !$type || !$time)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        if($type == 2)
        {
            if(strtotime($trent_end_time)<=strtotime($time))
            {
                return api_output_error(1001,'租赁到期时间不得小于租赁时间');
            }
            if(!$trent_end_time) {
                return api_output_error(1001, '必传参数缺失');
            }
        }
        $data = [
            'type'=>$type,
            'name'=>$name,
            'tel'=>$tel,
            'assets'=>$assets,
            'time'=>$time,
        ];
        if($trent_end_time){
            $data['rent_end_time'] = $trent_end_time;
        }
        $FixedAssetsService = new FixedAssetsService();
        try{
            $res = $FixedAssetsService->subLedRent($data,$street_id);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($type == 1){
            $msg = '领取';
        }else{
            $msg = '租借';
        }
        if($res){
            return api_output(0,$res,$msg.'成功');
        }else{
            return api_output_error(1001,$msg.'失败');
        }
    }
    //资产收回
    public function subTakeBack()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $assets = $this->request->param('assets','','trim');
        $status = $this->request->param('status','','trim');
        $record = $this->request->param('record','','trim');
        $take_back_time = $this->request->param('take_back_time','','trim');
        if(!$assets){
            return api_output_error(1001, '必传参数缺失');
        }
        if(!in_array($status,[4,5])){
            return api_output_error(1001, '必传参数异常');
        }
        if($status == 4 && !$take_back_time){
            return api_output_error(1001, '必传参数缺失');
        }
        if($status == 5 && !$record){
            return api_output_error(1001, '必传参数缺失');
        }
        $data = [
            'assets'=>$assets,
            'take_back_time'=>$take_back_time,
            'status'=>$status,
            'record'=>$record,
        ];
        $FixedAssetsService = new FixedAssetsService();
        try{
            $res = $FixedAssetsService->subTakeBack($data,$street_id);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($res){
            return api_output(0,$res,'成功');
        }else{
            return api_output_error(1001,'失败');
        }
    }
    //租借记录
    public function getRecordList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id','','intval');
        $page = $this->request->param('page','','intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $limit = 10;
        $FixedAssetsService = new FixedAssetsService();
        try{
            $res = $FixedAssetsService->getRecordList($id,$street_id,$page,$limit);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * Notes: 维修记录
     * @return \json
     * @author: weili
     * @datetime: 2020/11/21 18:59
     */
    public function getMaintainList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $assets_num_id = $this->request->param('assets_num_id','','intval');
        $page = $this->request->param('page','','intval');
        if(!$assets_num_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $limit = 10;
        $FixedAssetsService = new FixedAssetsService();
        try{
            $res = $FixedAssetsService->getMaintainList($assets_num_id,$street_id,$page,$limit);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * Notes: 获取维修记录详情
     * @return \json
     * @author: weili
     * @datetime: 2020/11/23 9:39
     */
    public function getMaintainInfo()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id','','intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $FixedAssetsService = new FixedAssetsService();
        try{
            $res = $FixedAssetsService->getMaintainInfo($id,$street_id);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    public function subMaintain()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id','','intval');
        $assets_num_id = $this->request->param('assets_num_id','','intval');
        $name = $this->request->param('name','','trim');
        $phone = $this->request->param('phone','','trim');
        $price = $this->request->param('price','','trim');
        $time = $this->request->param('time','','trim');
        $remark = $this->request->param('remark','','trim');
        $img_path = $this->request->param('img_path');
        if(!$assets_num_id || !$name || !$phone || !$price || !$time){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!isset($img_path)){
            return api_output_error(1001,'请上传图片');
        }
        $data = [
            'name'=>$name,
            'phone'=>$phone,
            'price'=>$price,
            'time'=>$time,
            'remark'=>$remark,
            'img_path'=>$img_path,
            'assets_num_id'=>$assets_num_id,
        ];
        $FixedAssetsService = new FixedAssetsService();
        try{
            $res = $FixedAssetsService->subMaintain($id,$street_id,$data);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * Notes: 图片上传
     * @return \json
     * @author: weili
     * @datetime: 2020/11/23 10:32
     */
    public function uploadStreet(){
        $file = $this->request->file('img');
        if(!$file){
            return api_output_error(1001,'必传参数缺失');
        }
        $FixedAssetsService = new FixedAssetsService();
        try {
            $img_url = $FixedAssetsService->uploads($file);
            $arr = [];
            $arr['img_url'] = $img_url;
            return api_output(0,$arr);
//            return json($img_url);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }
}