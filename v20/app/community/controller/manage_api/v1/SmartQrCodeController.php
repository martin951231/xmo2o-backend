<?php
/**
 * 智慧二维码相关
 * @author weili
 */

namespace app\community\controller\manage_api\v1;
use app\common\model\service\image\ImageService;
use app\community\model\db\WisdomQrcodeRecordLog;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseWorkerService;
use app\community\model\service\ManageAppLoginService;
use app\community\model\service\RepairCateService;
use app\community\model\service\WisdowQrcodeService;
use app\community\model\service\HouseVillageService;
use file_handle\FileHandle;
use think\Image;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\SmartQrCodeService;
class SmartQrCodeController extends BaseController
{
    /**
     * Notes:任务列表
     * @return \json
     * @author: weili
     * @datetime: 2020/11/4 17:50
     */
    public function myTaskList()
    {
        $info = $this->getLoginInfo();
      //   print_r($info);die;
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
//        $week = array('周一','周二','周三','周四','周五','周六','周日');
        $page = $this->request->param('page',1,'intval');
        $village_id = $this->request->param('village_id', '','intval');
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
           //  $village_ids=$serviceHouseVillage->getVillageIds(['area_id'=>$info['user']['area_id'],'area_type'=>$info['user']['area_type']],'area_id','street_id');
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
          //   print_r($worker_village);die;
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
        }else{
            $phone = $info['user']['login_phone'];

        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $serviceSmartQrCode = new SmartQrCodeService();
        try{
            $dataList =  $serviceSmartQrCode->myTask($village_id,$wid,$phone,$page);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$dataList);
    }
    /**
     * Notes:任务详情列表==>地图显示点
     * @return \json
     * @author: weili
     * @datetime: 2020/11/4 17:50
     */
    public function taskQrCodeList()
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
       //  $phone = $info['user']['login_phone'];
        $cate_id = $this->request->param('cate_id','','intval');
        $page = $this->request->param('page',1,'intval');
        if(empty($cate_id)){
            return api_output_error(1001,'任务ID不存在');
        }
        //增加工作人员判断
        $village_id = $this->request->param('village_id', '','intval');
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $account= '';
            if(isset($info['user']['account'])){
                $account=$info['user']['account'];
            }
            $serviceHouseVillage = new HouseVillageService();
            //  $village_ids=$serviceHouseVillage->getVillageIds(['area_id'=>$info['user']['area_id'],'area_type'=>$info['user']['area_type']],'area_id','street_id');
            $arr=[];
            $arr['account']=$account ? $account:$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            //   print_r($worker_village);die;
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
            $wid=$worker_village['wid'];
        }else{
            $phone = $info['user']['login_phone'];

        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $serviceSmartQrCode = new SmartQrCodeService();
        try{
            $app_type = $this->request->param('app_type', '', 'trim');
            $param = ['app_type' => $app_type];
            $data = $serviceSmartQrCode->taskQrCode($village_id, $wid, $phone, $cate_id, $page, $param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    /**
     * Notes:设备详情
     * @return \json
     * @author: weili
     * @datetime: 2020/11/3 18:09
     */
    public function equipmentInfo()
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
       //  $phone = $info['user']['login_phone'];
        $map_type = $this->request->param('map_type',0,'intval');//是否从地图进

       //  $village_id = $this->request->param('village_id', '','intval');
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
            //  $village_ids=$serviceHouseVillage->getVillageIds(['area_id'=>$info['user']['area_id'],'area_type'=>$info['user']['area_type']],'area_id','street_id');
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            //   print_r($worker_village);die;
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
        }else{
            $phone = $info['user']['login_phone'];
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');

        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $qrcode_id = $this->request->param('qrcode_id','','intval');
        if(empty($qrcode_id)){
            return api_output_error(1001,'二维码ID不存在');
        }
        $lat = $this->request->param('lat','','trim');
        $lng = $this->request->param('lng','','trim');
        if(empty($lat) || empty($lng)){
            return api_output_error(1001,'定位失败');
        }
        $serviceSmartQrCode = new SmartQrCodeService();
        try{
            $data =  $serviceSmartQrCode->equipment($qrcode_id,$village_id,$wid,$phone,$lat,$lng,$map_type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    /**
     * Notes:记录详情==>页面==>数据添加
     * @return \json
     * @author: weili
     * @datetime: 2020/11/3 18:09
     */
    public function addRecordInfo()
    {
        $info = $this->getLoginInfo();
        $qrcode_id = $this->request->param('qrcode_id',0,'intval');
        if(empty($qrcode_id)){
            return api_output_error(1001,'二维码ID不存在');
        }
        $wid = $this->_uid;
        $village_id = $this->request->param('village_id',0,'intval');
        $category_id = $this->request->param('category_id',0,'intval');
        $serviceSmartQrCode = new SmartQrCodeService();
        try{
            $data =  $serviceSmartQrCode->addRecord($qrcode_id,$village_id,$wid,'',$category_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    /**
     * Notes:上传二维码图片
     * @return \json
     * @author: weili
     * @datetime: 2020/11/4 17:49
     */
    public function qrCodeImage()
    {
        $file = $this->request->file('imgFile');
        $app_type = $this->request->post('app_type','');
        if(!$file){
            return api_output_error(1001,'请上传有效图片');
        }
        try {
            // 验证
//            validate(['imgFile' => [
//                'fileSize' => 1024 * 1024 * 10,   //10M
//                'fileExt' => 'jpg,png,jpeg,gif,ico',
//                'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
//            ]])->check(['imgFile' => $file]);
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile('qrcode', $file);
            if (strpos($savename, "\\") !== false) {
                $savename = str_replace('\\', '/', $savename);
            }
            $imgurl = app()->getRootPath() . '../upload/' . $savename;

            try {
                // 压缩图片
                $arr = getimagesize($imgurl);
                $heiht = $arr[1];
                $with = $arr[0];
                if ($heiht > 10000) {
                    $heiht = floor($heiht / 20);
                    $with = floor($with / 20);
                } elseif ($heiht > 3000) {
                    $heiht = floor($heiht / 10);
                    $with = floor($with / 10);
                } elseif ($heiht > 2000) {
                    $heiht = floor($heiht / 5);
                    $with = floor($with / 5);
                } elseif ($heiht > 1000) {
                    $heiht = floor($heiht / 2);
                    $with = floor($with / 2);
                }
                $heiht = intval($heiht);
                $with = intval($with);
                $pathinfo = pathinfo($imgurl);
                $param = [
                    'imgPath'      => $imgurl,
                    'savePath'     => $imgurl,
                    'format'       => isset($pathinfo['extension']) && $pathinfo['extension'] ? $pathinfo['extension'] : 'png',  //统一默认转 jpg
                    'heiht'        => $heiht ,
                    'with'         => $with
                ];
                (new ImageService())->encodePngToJpg($param);
            }catch (Exception $e){
                return api_output_error(-1, $e->getMessage());
            }
            $http_img_url = cfg('site_url').'/upload/' . $savename;
            $fontBold = app()->getRootPath() . '/../static/fonts/PingFang Light.ttf';
            if($app_type == 'packapp'){
                $size = 90;
            }else{
                $size = 16;
            }
            if (isset($with) && $with < 400) {
                $size = $size/2;
            }
            $config = [
                [
                    'file_path' => $imgurl,
                    'main' => ['font' => $fontBold, 'size' => $size, 'color' => '#FF7B23', 'locate' => 9, 'offset' => [-30, -10]],
                ]
            ];
            $thisConfig = $config[0];
            $fileName = uniqid('', true) . '.png';
            $imgPath = date("Ymd");
            $uploadDir = app()->getRootPath() . '../upload/qrcode/'.$imgPath.'/';
            $fileUrl = cfg('site_url') . '/upload/qrcode/'.$imgPath.'/' . $fileName;
            $saveFile = $uploadDir . $fileName;
            $image = Image::open($thisConfig['file_path']);
            $image->text(date("Y-m-d H:i:s"), $thisConfig['main']['font'], $thisConfig['main']['size'], $thisConfig['main']['color'], $thisConfig['main']['locate'], $thisConfig['main']['offset'])
                ->save($saveFile);
            
            $data['url'] = thumb_img($fileUrl, 200, 200, 'fill');
            $data['imageUrl_path'] = '/upload/qrcode/'.$imgPath.'/' . $fileName;
            $data['imageUrl'] = $fileUrl;
            $params = ['savepath'=>$data['imageUrl_path']];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            fdump_api(['thisConfig' => $thisConfig, 'data' => $data],'1223',1);
            return api_output(0, $data, "成功");
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes:qrcode类别
     * @return \json
     * @author: weili
     * @datetime: 2020/11/4 17:49
     */
    public function qrCodeCate()
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
       //  $phone = $info['user']['login_phone'];
        //增加工作人员判断
        // $village_id = $this->request->param('village_id', '','intval');
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
            //  $village_ids=$serviceHouseVillage->getVillageIds(['area_id'=>$info['user']['area_id'],'area_type'=>$info['user']['area_type']],'area_id','street_id');
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            //   print_r($worker_village);die;
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
        }else{
            $phone = $info['user']['login_phone'];
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');

        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $serviceSmartQrCode = new SmartQrCodeService();
        try{
            $data =  $serviceSmartQrCode->qrCodeCate($village_id,$wid,$phone);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    /**
     * Notes:用户查看记录
     * @return \json
     * @author: weili
     * @datetime: 2020/11/4 17:49
     */
    public function getRecordInfo()
    {
        $qrcode_id = $this->request->param('qrcode_id');
        if(empty($qrcode_id)){
            return api_output_error(1001,'二维码ID不存在');
        }
        $serviceSmartQrCode = new SmartQrCodeService();
        $page = $this->request->param('page',1);
        $app_type = $this->request->param('app_type','','trim');
        $add_time = $this->request->param('add_time','','string');
        $cate_id = $this->request->param('cate_id','','intval');
        try{
            $data =  $serviceSmartQrCode->getRecord($qrcode_id,$page,$app_type,$add_time,$cate_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    public function getQrcodeRecordDetail(){
        $info = $this->getLoginInfo();
        $qrcode_id = $this->request->param('qrcode_id',0,'intval');
        $qrcode_record_id = $this->request->param('qrcode_record_id',0,'intval');
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        if (empty($qrcode_id)) {
            return api_output_error(1001,'智慧二维码Id错误！');
        }
        if (empty($qrcode_record_id)) {
            return api_output_error(1001,'二维码记录Id错误！');
        }
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
        }else{
            $phone = $info['user']['login_phone'];
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');

        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        try{
            $serviceSmartQrCode = new SmartQrCodeService();
            $whereArr=array();
            $whereArr[]=array('id','=',$qrcode_record_id);
            $whereArr[]=array('qrcode_id','=',$qrcode_id);
            $whereArr[]=array('village_id','=',$village_id);
            $data = $serviceSmartQrCode->getOneQrcodeRecord($whereArr);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        
    }
    //工作人员查看记录
    public function getRecordList()
    {
        $info = $this->getLoginInfo();
        $qrcode_id = $this->request->param('qrcode_id');
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
       /* $phone = $info['user']['login_phone'];
        $village_id = $this->request->param('village_id','','intval');*/
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
            //  $village_ids=$serviceHouseVillage->getVillageIds(['area_id'=>$info['user']['area_id'],'area_type'=>$info['user']['area_type']],'area_id','street_id');
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            //   print_r($worker_village);die;
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
        }else{
            $phone = $info['user']['login_phone'];
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');

        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $page = $this->request->param('page',1,'intval');
        $add_time = $this->request->param('add_time','','string');
        $cate_id = $this->request->param('cate_id','','intval');
//        if(empty($cate_id)){
//            return api_output_error(1001,'任务ID不存在');
//        }
        $serviceSmartQrCode = new SmartQrCodeService();
        try{
            $data = $serviceSmartQrCode->getRecordList($village_id,$wid,$phone,$page,$add_time,$cate_id,$qrcode_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    //添加记录
    public function addRecord()
    {
    
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
      //   $phone = $info['user']['login_phone'];
        $app_type = $this->request->param('app_type', '');
        $param_data = $this->request->param('param_data');
//        if($app_type == 'android'){
//            if (empty($param_data['village_id'])) {
//                return api_output_error(1001,'社区ID不能为空');
//            }
//            if(empty($param_data['cate_id'])){
//                return api_output_error(1001,'二维码类别ID不存在!');
//            }
//            if(empty($param_data['qrcode_id'])){
//                return api_output_error(1001,'二维码ID不存在!');
//            }
//            $village_id = $param_data['village_id'];
//            $cate_id = $param_data['cate_id'];
//            $qrcode_id = $param_data['qrcode_id'];
//        }else {
           //  $village_id = $this->request->param('village_id', '');
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $account='';
            if(isset($info['user']['account'])){
                $account=$info['user']['account'];
            }
            $serviceHouseVillage = new HouseVillageService();
            //  $village_ids=$serviceHouseVillage->getVillageIds(['area_id'=>$info['user']['area_id'],'area_type'=>$info['user']['area_type']],'area_id','street_id');
            $arr=[];
            $arr['account']=$account ? $account:$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            //   print_r($worker_village);die;
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
        }else{
            $phone = $info['user']['login_phone'];
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');

        }
            if (empty($village_id)) {
                return api_output_error(1001, '社区ID不能为空');
            }
            $cate_id = $this->request->param('cate_id', '', 'intval');
            if (empty($cate_id)) {
                return api_output_error(1001, '二维码类别ID不存在!');
            }
            $qrcode_id = $this->request->param('qrcode_id', '', 'intval');
            if (empty($qrcode_id)) {
                return api_output_error(1001, '二维码ID不存在!');
            }
//        }
        $post = $this->request->param();
        $serviceSmartQrCode = new SmartQrCodeService();
        $serviceWisdowQrcode = new WisdowQrcodeService();

        try{
            $insert_id = $serviceSmartQrCode->postRecord($village_id,$wid,$phone,$cate_id,$qrcode_id,$post);
            $record_cate_field_config = $post['test'][count($post['test'])-1]['record_cate_field_config'];
            $record_cate_field_config_arr = array_combine( array_column($record_cate_field_config,'key'),array_column($record_cate_field_config,'value') );
            //如果存在异常状态, 则直接提交工单
            if( isset( $record_cate_field_config_arr['FIXED_POINT_IS_ABNORMALITY'] ) && $record_cate_field_config_arr['FIXED_POINT_IS_ABNORMALITY'] == '是' ){
                $log_where = ['id'=>$insert_id];
                $log_data = ['is_abnormal'=>1];
                $serviceWisdowQrcode->updateWisdomQrcodeRecordLog($log_where,$log_data);
                $post['inspection_id'] = $insert_id;
                $this->inspectionOrderAdd($post,$info);
            }
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($insert_id){
            return api_output(0,$insert_id);
        }else{
            return api_output_error(-1, '添加记录失败,请重试!');
        }

    }

    /**
     * Notes: 提交工单
     * @author: wanzy
     * @date_time: 2021/8/17 10:27
     */
    public function inspectionOrderAdd($post,$userinfo)
    {
        $village_id = $post['village_id'];
        $db_house_new_repair_works_order_service = new WisdowQrcodeService();
        $wisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        $where['village_id'] = $village_id;
        $where['id'] = $post['cate_id'];
        $QrcodeCate = $db_house_new_repair_works_order_service->getWisdomQrcodeCateOne($where);
        $QrcodeInfo = $db_house_new_repair_works_order_service->getQrcodeOne(['id'=>$post['qrcode_id']]);

        $workorder_category_id = $QrcodeCate ? $QrcodeCate['workorder_category_id'] : 0;


        $order_content = htmlspecialchars( json_encode( $post['test'], true) );

        $repair_cate_service = new RepairCateService();
        $userinfoD = (new HouseWorkerService())->getOneWorker(['village_id'=>$village_id,'phone'=>$userinfo['user']['login_phone'],'name'=>$userinfo['user']['login_name']], 'wid,name,type,phone');
        $data = [
            'village_id' => $village_id,
            'category_id' => 0,
            'type_id' => 0,
            'cat_fid' => $workorder_category_id,
            'cat_id' => 0,
            'address_type' => 'public',
            'address_id' => $QrcodeInfo ? $QrcodeInfo['id'] : 0 ,
            'address_text_name' => $QrcodeInfo ? $QrcodeInfo['name'] : '',
            'label_txt' => '',
            'order_imgs' => '',
            'order_type' => 4,
            'order_content' => $order_content?$order_content:'',
            'worker_type' => 0,
            'worker_id' => $userinfoD?$userinfoD['wid']:0,
            'login_role' => $userinfo['login_role'],
            'phone' => $userinfo['user']['login_phone'],
            'name' => $userinfo['user']['login_name'],
            'event_status' => 10,
            'wisdomqrcode_id' => $post['qrcode_id'],
            'inspection_id' => $post['inspection_id'],
            'inspection_auto_add' => 1,
        ];
        try{
            $info = $repair_cate_service->addWorksOrder($data,true);
            if( empty($info['order_id']) ){
                return api_output_error(-1, '工单创建失败,请手动创建工单');
            }else{
                $work_order_id = $info['order_id'];
                $record_log_where['id'] = $post['inspection_id'];
                $wisdomQrcodeRecordLog->updateRecordData($record_log_where,['work_order_id'=>$work_order_id]);
            }

        }catch (\Exception $e){
            fdump_api(['file' => $e->getFile(), 'code' => $e->getCode(), 'mes' => $e->getMessage(), 'line' => $e->getLine()], '$tryCatchErrLog', 1);
            throw new \think\Exception('自动工单创建失败,请手动创建工单');
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 巡检记录轨迹
     * @author: liukezhu
     * @date : 2022/6/2
     * @return \json
     */
    public function taskPositionTrail(){
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        $cate_id = $this->request->param('cate_id','0','int');
        if(empty($cate_id)){
            return api_output_error(1001,'必传参数缺失');
        }
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
            $wid=$worker_village['wid'];
        }
        else{
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');
        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        try{
            $where=[
                ['i.village_id','=',$village_id],
                ['i.cate_id','=',$cate_id],
                ['i.wid','=',$wid]
            ];
            $data =  (new SmartQrCodeService())->getPositionRecord(1,$where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 开启定位
     * @author: liukezhu
     * @date : 2022/5/31
     * @return \json
     */
    public function taskStartPosition(){
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        $cate_id = $this->request->param('cate_id','0','int');
        $long = $this->request->param('long','','trim');
        $lat = $this->request->param('lat','','trim');
        if(empty($cate_id)){
            $positionConfig = [
                'interval'  => 5,  //间隔 秒
                'number'    => 0,    //每天次数
                'duration'  => 180, //时长 秒
                'index_id'  => 0,
                'title'     => '',
                'cate_id'   => 0,
            ];
            return api_output(0,$positionConfig);
        }
        if(empty($long) || empty($lat)){
            return api_output_error(1001,'缺少经纬度参数');
        }
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            if (empty($worker_village)){
                return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
            $wid=$worker_village['wid'];
        }
        else{
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');
        }
        if (empty($village_id)) {
            return api_output_error(1001,'社区ID不能为空');
        }
        $param=[
            'village_id'=>$village_id,
            'cate_id'=>$cate_id,
            'wid'=>$wid,
            'long'=>$long,
            'lat'=>$lat
        ];
        try{
            $data =  (new SmartQrCodeService())->positionIndexStart($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 写入、关闭定位
     * @author: liukezhu
     * @date : 2022/5/31
     * @return \json
     */
    public function taskRecordPosition(){
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $wid = $this->_uid;
        $index_id = $this->request->param('index_id',0,'int');
        $type = $this->request->param('type',1,'int');
        $cate_id = $this->request->param('cate_id','0','int');
        $long = $this->request->param('long','','trim');
        $lat = $this->request->param('lat','','trim');
        if(empty($cate_id)){
            $positionConfig = [
                'interval'        => 5,  //间隔 秒
                'number'          => 0,    //每天次数
                'duration'        => 180, //时长 秒
                'is_current_task' => 0,
                'cate_id'         => 0,
                'cate_name'       => '',
                'title'           => '',
                'index_id'        => 0,
                'task_msg'        => '',
            ];
            return api_output(0,$positionConfig);
        }
        if(empty($index_id)){
            return api_output_error(1001,'记录ID参数缺失');
        }
        if(!in_array($type,[1,2])){
            return api_output_error(1001,'类型参数不合法');
        }
        if((empty($long) || empty($lat)) && $type == 1){
            return api_output_error(1001,'缺少经纬度参数');
        }
        if (empty($wid)) {
            return api_output_error(1001,'登录失败');
        }
        $result['is_current_task']=-1;
        $result['cate_id']=0;
        $result['cate_name']='';
        $result['title']='';
        $result['index_id']=0;
        if ($info['login_role']==1){
            $phone = $info['user']['work_phone'];
            $serviceHouseVillage = new HouseVillageService();
            $arr=[];
            $arr['account']=$phone;
            $worker_village=$serviceHouseVillage->getHouseWorker($arr);
            if (empty($worker_village)){
                $result['task_msg']='未匹配到对应的小区工作人员';
                return api_output(0,$result);
               // return api_output_error(1001,'未匹配到对应的小区工作人员');
            }else{
                $village_id= $worker_village['village_id'];
            }
            $wid=$worker_village['wid'];
        }
        else{
            //增加工作人员判断
            $village_id = $this->request->param('village_id', '','intval');
        }
        if (empty($village_id)) {
            $result['task_msg']='社区ID不能为空';
            return api_output(0,$result);
//            return api_output_error(1001,'社区ID不能为空');
        }
        $param=[
            'village_id'=>$village_id,
            'index_id'=>$index_id,
            'cate_id'=>$cate_id,
            'wid'=>$wid,
            'long'=>$long,
            'lat'=>$lat
        ];
        try{
            $data =  (new SmartQrCodeService())->positionIndexRecord($type,$param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

}