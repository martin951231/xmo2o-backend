<?php
/**
 * 热词
 **/

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseHotWordManageService;
use app\community\model\service\HouseHotWordMaterialCategoryService;
use app\community\model\service\HouseHotWordMaterialContentService;
class HouseHotWordManageController extends CommunityBaseController
{
    /**
     * 获取热词列表
     */
    public function getHotWordList()
    {
        $village_id = $this->adminUser['village_id'];
        $houseHotWordManageService = new HouseHotWordManageService();
        $page = $this->request->param('page', '1', 'int');
        $limit = 20;
        $keyword = $this->request->post('keyword', 0, 'trim');
        $dateArr = $this->request->post('date');
        $where = array(['village_id', '=', $village_id]);
        $where[] = ['del_time', '<', 1];
        if ($keyword) {
            $keyword = htmlspecialchars($keyword, ENT_QUOTES);
            $where[] = ['wordname', 'like', '%' . $keyword . '%'];
        }
        if ($dateArr && is_array($dateArr) && !empty($dateArr['0'])) {
            $starttime = strtotime($dateArr['0']);
            if ($starttime > 0) {
                $where[] = ['update_time', '>=', $starttime];
            }
            $endtime = strtotime($dateArr['1']);
            if ($endtime > 0) {
                $where[] = ['update_time', '<=', $endtime];
            }
        }
        try {
            $fieldStr = '*';
            $list = $houseHotWordManageService->getHotwordList($where, $fieldStr, $page, $limit);
            $list['role_addword']=$this->checkPermissionMenu(112067);  //新建关键词
            $list['role_editword']=$this->checkPermissionMenu(112069);  //编辑关键词
            $list['role_delword']=$this->checkPermissionMenu(112070);  //删除关键词
            $list['role_copyword']=$this->checkPermissionMenu(112068);  //从其他小区复制
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }


    //检查是否有相关权限
    function checkPermissionMenu($pid = 0)
    {
        $logomenus = array();
        if (isset($this->adminUser['menus']) && !empty($this->adminUser['menus'])) {
            if (is_array($this->adminUser['menus'])) {
                $logomenus = $this->adminUser['menus'];
            } else {
                $logomenus = explode(',', $this->adminUser['menus']);
            }
        }
        if (in_array($this->login_role, array(3, 7, 105, 303))) {
            return 1;
        }
        if (empty($logomenus)) {
            return 1;
        } else if (!empty($logomenus) && in_array($pid, $logomenus)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     *获取一条数据
     ***/
    public function getOneHouseHotWord()
    {
        $village_id = $this->adminUser['village_id'];
        $word_id = $this->request->post('word_id', 0, 'int');
        if ($word_id < 1) {
            return api_output_error(1001, '关键词ID参数错误！');
        }
        try {
            $houseHotWordManageService = new HouseHotWordManageService();
            $whereArr = array('id' => $word_id, 'village_id' => $village_id);
            $ret = $houseHotWordManageService->getOneHotword($whereArr);
            $wordurllist = '';
            if ($ret && isset($ret['urllist'])) {
                $wordurllist = $ret['urllist'];
                unset($ret['urllist']);
            }
            $returnArr = array('hotword' => $ret, 'wordurllist' => $wordurllist);
            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //保存编辑数据
    public function saveHotWordData()
    {
        $village_id = $this->adminUser['village_id'];
        $word_id = $this->request->post('word_id', 0, 'int');
        $wordname = $this->request->post('wordname', '', 'trim');
        $wordname = htmlspecialchars($wordname, ENT_QUOTES);
        $wordurllist = $this->request->post('wordurllist');

        $xtype= $this->request->post('xtype', 0, 'int');    //0链接1文字2音频3图片
        $word_imgs = $this->request->post('word_imgs','');  //图片
        $xcontent = $this->request->post('xcontent','');  //文本回复
        $xname = $this->request->post('xname','','trim');  //标题
        $audio_url = $this->request->post('audio_url','','trim');  //音频
        $comfrom = $this->request->post('comfrom', 0, 'int');  //来源 1素材库
        $material_id = $this->request->post('material_id',0,'int');  //素材库id
        $cate_id = $this->request->post('cate_id',0,'int');  //素材库分类id
        
        $material_info = $this->request->post('material_info','');  //素材库数据
        if (empty($wordname)) {
            return api_output_error(1001, '关键词名称不能为空！');
        }
        $urllist = array();
        $saveArr = array('wordname' => $wordname, 'showtitle' =>'','jumpurl'=>'','comfrom'=>$comfrom);
        $saveArr['material_id']=0;
        $saveArr['cate_id']=0;
        $saveArr['xtype']=$xtype;
        if($xtype==0){
            if (empty($wordurllist) || !is_array($wordurllist)) {
                return api_output_error(1001, '链接数据不能为空！');
            }
            foreach ($wordurllist as $kkk => $vv) {
                $tmpArr = array();
                $tmpArr['village_id'] = $village_id;
                $tmpArr['wordname'] = $wordname;
                $vv['showtitle']=trim($vv['showtitle']);
                $tmpArr['showtitle'] = htmlspecialchars($vv['showtitle'], ENT_QUOTES);
                $tmpArr['jumpurl'] = trim($vv['jumpurl'], ENT_QUOTES);
                $tmpArr['xsort'] = $kkk;
                $urllist[] = $tmpArr;
            }
            $saveArr['showtitle']=$wordurllist['0']['showtitle'];
            $saveArr['jumpurl'] = $wordurllist['0']['jumpurl'];
        }else if($xtype==1){
            //文本
            if($comfrom==1){
                //从素材库
                if($cate_id<1 && $material_id<1){
                    return api_output_error(1001, '请从素材库中选择文字回复数据！');
                }
                $saveArr['material_id']=$material_id;
                $saveArr['cate_id']=$cate_id;
                $saveArr['xcontent']='';
            }else{
                if(empty($xcontent)){
                    return api_output_error(1001, '回复内容不能为空！');
                }
                $saveArr['xcontent']=htmlspecialchars($xcontent, ENT_QUOTES);
            }
            
        }else if($xtype==2){
            //音频
            if($comfrom==1){
                //从素材库
                if($cate_id<1 && $material_id<1){
                    return api_output_error(1001, '请从素材库中选择音频回复数据！');
                }
                $saveArr['material_id']=$material_id;
                $saveArr['cate_id']=$cate_id;
                $saveArr['xcontent']='';
            }else{
                if(empty($audio_url)){
                    return api_output_error(1001, '请上传回复音频文件！');
                }
                
                $saveArr['showtitle']=$xname;
                $saveArr['xcontent']=$audio_url;
            } 
        }else if($xtype==3){
            //图片
            if($comfrom==1){
                //从素材库
                if($cate_id<1 && $material_id<1){
                    return api_output_error(1001, '请从素材库中选择图片回复数据！');
                }
                $saveArr['material_id']=$material_id;
                $saveArr['cate_id']=$cate_id;
                $saveArr['xcontent']='';
            }else{
                if(empty($word_imgs)){
                    return api_output_error(1001, '请上传回复图片文件！');
                }
                $saveArr['xcontent']=implode(',',$word_imgs);
            }
        }

        try {
            $houseHotWordManageService = new HouseHotWordManageService();
            $whereTmp=array();
            $whereTmp[]=array('village_id','=',$village_id);
            $whereTmp[]=array('wordname','=',$wordname);
            $whereTmp[]=array('xtype','=',$xtype);
            $whereTmp[]=array('del_time','<',1);
            
            if($word_id>0){
                $whereTmp[]=array('id','<>',$word_id);
            }
            $oneData=$houseHotWordManageService->getOneHotwordManage($whereTmp);
            if($oneData && isset($oneData['id'])){
                return api_output_error(1001, '关键词【'.$wordname.'】在当前关键词类型下已经存在了。');
            }
            if ($word_id > 0) {
                $saveArr['village_id'] = $village_id;
                $whereArr = array('id' => $word_id, 'village_id' => $village_id);
                $houseHotWordManageService->updateHotwordAndUrl($whereArr, $saveArr, $urllist);
            } else {
                $saveArr['village_id'] = $village_id;
                $word_id = $houseHotWordManageService->addHotword($saveArr, $urllist);
            }
            return api_output(0, $word_id);

        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /***
     * 只更新hotword_manage表
     **/
    public function setHouseHotWordStatus()
    {
        $word_id = $this->request->post('word_id', 0, 'int');
        $status = $this->request->post('status', 0, 'int');  //0禁用 1启用
        $village_id = $this->adminUser['village_id'];
        if ($word_id < 1) {
            return api_output_error(1001, '关键词ID参数错误！');
        }
        try {
            $houseHotWordManageService = new HouseHotWordManageService();
            $ret = $houseHotWordManageService->setHotWordDataStatus($village_id, $word_id, $status);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function deleteHotWord()
    {
        $word_id = $this->request->post('word_id', 0, 'int');
        $village_id = $this->adminUser['village_id'];
        if ($word_id < 1) {
            return api_output_error(1001, '关键词ID参数错误！');
        }
        try {
            $houseHotWordManageService = new HouseHotWordManageService();
            $ret = $houseHotWordManageService->deleteHotWord($village_id, $word_id);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //功能库
    public function getFuncApplication()
    {
        $village_id = $this->adminUser['village_id'];
        $houseHotWordManageService = new HouseHotWordManageService();
        try {
        $ret=$houseHotWordManageService->getFuncApplication($village_id,$this->adminUser);
        return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getFuncApplicationDetail(){

        $village_id = $this->adminUser['village_id'];
        $xtype = $this->request->post('xtype', '', 'trim'); //功能类型
        $page = $this->request->post('page', 0, 'int'); //分页
        $houseHotWordManageService = new HouseHotWordManageService();
        try {
            $ret=$houseHotWordManageService->getFuncApplicationDetail($village_id,$xtype,$page);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        
    }

    /**
     * 获取街道社区数据
     */
    public function  getAreaStreetCommunity(){

        $village_id = $this->adminUser['village_id'];
        $xtype = $this->request->post('xtype', 'street', 'trim'); //street 街道数据  community 社区数据
        $pid = $this->request->post('pid', 0, 'intval');
        $houseHotWordManageService = new HouseHotWordManageService();
        try {
            $ret=$houseHotWordManageService->getAreaStreetCommunity($xtype,$pid);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    //获取物业小区
    public function getAllVillages()
    {

        $page = $this->request->param('page', 0, 'int');
        $property_id = $this->adminUser['property_id'];
        $village_id = $this->adminUser['village_id'];
        $village_name = $this->request->param('keyword', '', 'trim');
        $village_name = htmlspecialchars($village_name, ENT_QUOTES);
        $province_id = $this->request->param('province_id', 0, 'int');
        $city_id = $this->request->param('city_id', 0, 'int');
        $area_id = $this->request->param('area_id', 0, 'int');
        $community_id = $this->request->param('community_id', 0, 'int');
        $street_id = $this->request->param('street_id', 0, 'int');
        $condition_where = array();
        $condition_where[] = array('village_id', '<>', $village_id);
        if (!empty($village_name)) {
            $condition_where[] = array('village_name', 'like', '%' . $village_name . '%');
        }
        if (!empty($province_id)) {
            $condition_where[] = array('province_id', '=', $province_id);
        }
        if (!empty($city_id)) {
            $condition_where[] = array('city_id', '=', $city_id);
        }
        if (!empty($area_id)) {
            $condition_where[] = array('area_id', '=', $area_id);
        }
        if (!empty($community_id)) {
            $condition_where[] = array('community_id', '=', $community_id);
        }
        if (!empty($street_id)) {
            $condition_where[] = array('street_id', '=', $street_id);
        }
        $condition_where[] = array('property_id', '>', 0);
        $condition_where[] = array('status', '<>', 5);
        try {
            $houseHotWordManageService = new HouseHotWordManageService();
            $fieldStr='village_id,property_id,account,area_id,city_id,community_id,property_address,property_name,property_phone,village_address,village_name,village_logo';
            $limit = 10;
            $page = $page > 0 ? $page : 1;
            $rets=$houseHotWordManageService->getAllVillages($condition_where,$fieldStr,$page, $limit, 'village_id DESC');
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function copyAvillageKeyword(){
        $village_id = $this->adminUser['village_id'];
        $other_village_id = $this->request->param('other_village_id', 0, 'int');
        if($other_village_id<1){
            return api_output_error(1001, '请先选择一个小区！');
        }
        if($village_id==$other_village_id){
            return api_output(0, []);
        }
        try {
            $houseHotWordManageService = new HouseHotWordManageService();
            $rets=$houseHotWordManageService->copyAvillageKeywords($village_id,$other_village_id);
            return api_output(0, $rets);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        
    }
    
    //保存编辑素材分类数据
    public function saveMaterialCategoryData()
    {
        $village_id = $this->adminUser['village_id'];
        $cate_id = $this->request->post('cate_id', 0, 'int');
        $categoryname = $this->request->post('categoryname', '', 'trim');
        $categoryname = htmlspecialchars($categoryname, ENT_QUOTES);
        $xtype= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类

        if (empty($categoryname)) {
            return api_output_error(1001, '分类名称不能为空！');
        }
        if(!in_array($xtype,array(1,2,3))){
            return api_output_error(1001, '素材类型错误！');
        }
        $saveArr = array('categoryname' => $categoryname, 'xtype' =>$xtype);
        
        try {
            $houseHotWordMaterialCategoryService = new HouseHotWordMaterialCategoryService();
            $whereArrTmp=array();
            $whereArrTmp[]=array('village_id','=',$village_id);
            $whereArrTmp[]=array('xtype','=',$xtype);
            $whereArrTmp[]=array('categoryname','=',$categoryname);
            $whereArrTmp[]=array('del_time','<',1);
            if($cate_id > 0){
                $whereArrTmp[]=array('cate_id','<>',$cate_id);
            }
            $existCategory=$houseHotWordMaterialCategoryService->getOneMaterialCategory($whereArrTmp);
            if(!empty($existCategory)){
                $errmsg='此分类名称【'.$categoryname.'】已经存在了，请修改分类名称！';
                return api_output(0,array('errmsg'=>$errmsg,'is_have_err'=>1));
            }
            if ($cate_id > 0) {
                $saveArr['village_id'] = $village_id;
                $whereArr = array('cate_id' => $cate_id, 'village_id' => $village_id);
                $houseHotWordMaterialCategoryService->updateMaterialCategory($whereArr, $saveArr);
            } else {
                $saveArr['village_id'] = $village_id;
                $cate_id = $houseHotWordMaterialCategoryService->addMaterialCategory($saveArr);
            }
            return api_output(0, $cate_id);

        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 获取热词列表
     */
    public function getMaterialCategoryList()
    {
        $village_id = $this->adminUser['village_id'];
        $houseHotWordMaterialCategoryService = new HouseHotWordMaterialCategoryService();
        $page = $this->request->param('page', '1', 'int');
        $limit = 20;
        $keyword = $this->request->post('keyword', 0, 'trim');
        $dateArr = $this->request->post('date');
        $xtype= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        $where = array(['village_id', '=', $village_id]);
        $where[] = ['del_time', '<', 1];
        $where[] = ['xtype', '=', $xtype];
        if ($keyword) {
            $keyword = htmlspecialchars($keyword, ENT_QUOTES);
            $where[] = ['categoryname', 'like', '%' . $keyword . '%'];
        }
        if ($dateArr && is_array($dateArr) && !empty($dateArr['0'])) {
            $starttime = strtotime($dateArr['0']);
            if ($starttime > 0) {
                $where[] = ['update_time', '>=', $starttime];
            }
            $endtime = strtotime($dateArr['1']);
            if ($endtime > 0) {
                $where[] = ['update_time', '<=', $endtime];
            }
        }
        try {
            $fieldStr = '*';
            $list = $houseHotWordMaterialCategoryService->getMaterialCategoryList($where, $fieldStr, $page, $limit);
            $list['role_addcategory']=$this->checkPermissionMenu(112074);  //新建关键词
            $list['role_editcategory']=$this->checkPermissionMenu(112075);  //编辑关键词
            $list['role_delcategory']=$this->checkPermissionMenu(112076);  //删除关键词
            $list['role_managecategory']=$this->checkPermissionMenu(112077);  //管理
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //删除数据
    public function delMaterialCategoryData(){
        $xtype= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        $cate_ids= $this->request->post('cate_ids','', 'trim');
        $village_id = $this->adminUser['village_id'];
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('xtype','=',$xtype);
        if($cate_ids && is_numeric($cate_ids)){
            $whereArr[]=array('cate_id','=',$cate_ids);
        }else if($cate_ids && is_string($cate_ids) && strpos($cate_ids,',')){
            $cate_ids=explode(',',$cate_ids);
            $whereArr[]=array('cate_id','in',$cate_ids);
        }else{
            return api_output_error(1001, '参数ID错误！');
        }

        try {
            $houseHotWordMaterialCategoryService = new HouseHotWordMaterialCategoryService();
            $ret = $houseHotWordMaterialCategoryService->delMaterialCategoryData($whereArr);
            //删除分类下内容数据
            $houseHotWordMaterialContentService = new HouseHotWordMaterialContentService();
            $retContent = $houseHotWordMaterialContentService->delMaterialContentData($whereArr);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /***
    **保存素材数据
     ***/
    public function saveMaterialSetData(){
        $village_id = $this->adminUser['village_id'];
        $material_id = $this->request->post('material_id', 0, 'int');  //素材id
        $cate_id = $this->request->post('cate_id', 0, 'int');   //分类id
        $xtype= $this->request->post('xtype', 0, 'int');    //1文字2音频3图片
        $word_imgs = $this->request->post('word_imgs','');  //图片
        $xname = $this->request->post('xname','','trim');  //标题
        $xcontent = $this->request->post('xcontent','');  //文本回复
        $audio_url_arr = $this->request->post('audio_url');  //音频
        if ($cate_id<0) {
            return api_output_error(1001, '分类参数ID错误！');
        }
        $xname=htmlspecialchars($xname, ENT_QUOTES);
        $saveArr=array();
        if($xtype==1){
            //文本
                if(empty($xcontent)){
                    return api_output_error(1001, '回复内容不能为空！');
                }
                $saveArr['xcontent']=htmlspecialchars($xcontent, ENT_QUOTES);
            

        }else if($xtype==2){
            //音频
                $saveArr['xname']=$xname;
                if($material_id>0){
                    if(empty($audio_url_arr) ){
                        return api_output_error(1001, '请上传回复音频文件！');
                    }
                    if(is_array($audio_url_arr)){
                        $saveArr['xcontent']=$audio_url_arr['0']['url'];
                        /*
                        if($audio_url_arr['0']['filename']){
                            $saveArr['xname']=$audio_url_arr['0']['filename'];
                        }
                        */
                    }else{
                        $saveArr['xcontent']=$audio_url_arr;
                    }
                }else{
                    if(empty($audio_url_arr) || !is_array($audio_url_arr)){
                        return api_output_error(1001, '请上传回复音频文件！');
                    }
                }
            
        }else if($xtype==3){
            //图片
                if(empty($word_imgs)){
                    return api_output_error(1001, '请上传回复图片文件！');
                }
                $saveArr['xcontent']=implode(',',$word_imgs);
            
        }else{
            return api_output_error(1001, '素材类型错误！');
        }
        try {
            $houseHotWordMaterialContentService = new HouseHotWordMaterialContentService();
            if($material_id>0){
                $whereArr=array();
                $whereArr[]=array('material_id','=',$material_id);
                $whereArr[]=array('cate_id','=',$cate_id);
                $whereArr[]=array('village_id','=',$village_id);
                $whereArr[]=array('xtype','=',$xtype);
                $ret = $houseHotWordMaterialContentService->updateMaterialContent($whereArr,$saveArr);
            }else{
                $saveArr['village_id']=$village_id;
                $saveArr['cate_id']=$cate_id;
                $saveArr['xtype']=$xtype;
                $ret=0;
                $num=0;
                if($xtype==2){
                    $saveArr['xname']=$xname;
                    foreach ($audio_url_arr as $vcc){
                        if(!empty($vcc['url'])){
                            $saveArr['xcontent']=$vcc['url'];
                            /*
                            if(!empty($vcc['filename'])){
                                $saveArr['xname']=$vcc['filename'];
                            }
                            */
                            $ret = $houseHotWordMaterialContentService->addMaterialContent($saveArr);
                            $num++;
                        }
                    }
                }else{
                    $ret = $houseHotWordMaterialContentService->addMaterialContent($saveArr);
                    $num=1;
                }
                $houseHotWordMaterialCategoryService = new HouseHotWordMaterialCategoryService();
                $whereArr=array();
                $whereArr[]=array('cate_id','=',$cate_id);
                $whereArr[]=array('village_id','=',$village_id);
                $houseHotWordMaterialCategoryService->updateFieldPlusNum($whereArr,$num);
            }
            
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    
    /***
     * 删除素材内容 
     **/
    public function delHouseHotWordMaterialContent(){

        $xtype= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        $material_ids= $this->request->post('material_ids','','trim');
        $village_id = $this->adminUser['village_id'];
        $cate_id= $this->request->post('cate_id',0,'int');
        if($cate_id<1){
            return api_output_error(1001, '分类参数ID错误！');
        }
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('cate_id','=',$cate_id);
        $whereArr[]=array('xtype','=',$xtype);
        $whereTmpArr=$whereArr;
        if($material_ids && is_numeric($material_ids)){
            $whereArr[]=array('material_id','=',$material_ids);
        }else if($material_ids && is_string($material_ids) && strpos($material_ids,',')){
            $material_ids=explode(',',$material_ids);
            $whereArr[]=array('material_id','in',$material_ids);
        }else{
            return api_output_error(1001, '参数ID错误！');
        }
        try {
            $houseHotWordMaterialContentService = new HouseHotWordMaterialContentService();
            $ret = $houseHotWordMaterialContentService->delMaterialContentData($whereArr);
            $whereTmpArr[]=array('del_time','<',1);
            $count=$houseHotWordMaterialContentService->getMaterialContentCount($whereTmpArr);
            $houseHotWordMaterialCategoryService = new HouseHotWordMaterialCategoryService();
            $whereCategory=array();
            $whereCategory[]=array('cate_id','=',$cate_id);
            $whereCategory[]=array('village_id','=',$village_id);
            $houseHotWordMaterialCategoryService->updateMaterialCategorySubCount($whereCategory,$count);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /**
     * 获取素材
    */
    public function getMaterialList(){
        $village_id = $this->adminUser['village_id'];
        $houseHotWordMaterialContentService = new HouseHotWordMaterialContentService();
        $page = $this->request->param('page', '1', 'int');
        $limit = 20;
        $dateArr = $this->request->post('date');
        $xtype= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        $cate_id= $this->request->post('cate_id', 0, 'int');
        if($cate_id<1){
            return api_output_error(1001, '分类参数ID错误！');
        }
        $where = array(['village_id', '=', $village_id]);
        $where[] = ['cate_id', '=', $cate_id];
        $where[] = ['del_time', '<', 1];
        $where[] = ['xtype', '=', $xtype];
        if ($dateArr && is_array($dateArr) && !empty($dateArr['0'])) {
            $starttime = strtotime($dateArr['0']);
            if ($starttime > 0) {
                $where[] = ['update_time', '>=', $starttime];
            }
            $endtime = strtotime($dateArr['1']);
            if ($endtime > 0) {
                $where[] = ['update_time', '<=', $endtime];
            }
        }
        try {
            $fieldStr = '*';
            $list = $houseHotWordMaterialContentService->getMaterialContentList($where, $fieldStr, $page, $limit);
            $list['role_addmaterial']=$this->checkPermissionMenu(112078);  //添加回复内容
            $list['role_editmaterial']=$this->checkPermissionMenu(112079);  //编辑回复内容
            $list['role_delmaterial']=$this->checkPermissionMenu(112080);  //删除回复内容
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /****
    * 素材库 分类
     **/
    public function getHotWordMaterialLibrary(){
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->param('page', 0, 'int');
        $limit = 500;
        $xtype= $this->request->post('xtype', 0, 'int'); //1文字分类2音频分类3图片分类
        $where=array();
        $where[]=array('village_id','=',$village_id);
        $where[]=array('xtype','=',$xtype);
        $where[]=array('del_time','<',1);
        $where[]=array('subcount','>',0);
        
        try {
            $houseHotWordMaterialCategoryService = new HouseHotWordMaterialCategoryService();
            $fieldStr = 'cate_id,categoryname,xtype,village_id';
            $list = $houseHotWordMaterialCategoryService->getHotWordMaterialLibrary($where, $fieldStr);
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /****
     * 素材库 分类详细数据
     **/
    public function getHotWordMaterialLibraryDetails(){
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->param('page', '1', 'int');
        $limit = 20;
        $xtype= $this->request->post('xtype', 0, 'int'); //1文字分类2音频分类3图片分类
        $cate_id= $this->request->post('cate_id', 0, 'int');
        if($cate_id<1){
            return api_output_error(1001, '分类参数ID错误！');
        }
        $where=array();
        $where[]=array('village_id','=',$village_id);
        $where[]=array('cate_id','=',$cate_id);
        $where[]=array('del_time','<',1);
        if($xtype>0 && in_array($xtype,array(1,2,3))){
            $where[]=array('xtype','=',$xtype);
        }
        try {
            $houseHotWordMaterialContentService = new HouseHotWordMaterialContentService();
            $fieldStr = 'material_id,cate_id,xtype,village_id,xcontent,xname';
            $list = $houseHotWordMaterialContentService->getHotWordMaterialLibraryDetails($where, $fieldStr,$page,$limit);
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /***
    *素材导入 
     ***/
    public function exportHotWordMaterial(){
        set_time_limit(0);
        $village_id = $this->adminUser['village_id'];
        $houseHotWordManageService = new HouseHotWordManageService();
        $file = $this->request->post('file','','trim');
        $xtype = $this->request->post('xtype',0,'int'); //1文字分类2音频分类3图片分类
        if(empty($xtype) || $xtype!=1){
            return api_output_error(1001,'参数出错，请确认导入操作！');
        }
        try {
            $savenum = $houseHotWordManageService->uploadMaterial($file,$village_id,$xtype);
            return api_output(0, $savenum);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
