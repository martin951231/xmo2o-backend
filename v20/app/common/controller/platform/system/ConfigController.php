<?php

/**
 * 后台站点配置
 * Author: chenxiang
 * Date Time: 2020/5/18 10:30
 */
namespace app\common\controller\platform\system;


use app\common\controller\CommonBaseController;
use app\common\model\db\MerchantStore;
use app\common\model\db\MerchantStoreShop;
use app\common\model\service\CacheSqlService;
use app\common\model\service\ConfigService;
use app\common\model\service\plan\PlanService;
use app\common\model\service\UploadFileService;
use app\common\model\service\UserLevelService;
use app\group\model\db\Group;
use app\mall\model\db\MerchantStoreMall;

class ConfigController extends CommonBaseController
{

    /**
     * 系统设置 - 站点设置
     * @author: chenxiang
     * @date: 2020/5/19 13:26
     * @return \json
     */
    public function index() {
        $gid = input('get.gid');

        if($gid) {
            $condition_config_group[] = ['gid', '=', $gid];
        } else {
            $condition_config_group[] = ['status', '=', 1];
        }

        $configService = new ConfigService();
        $group_list = $configService->getConfigGroupList(true, $condition_config_group,'gsort DESC,gid ASC', $this->config);

        if(empty($gid)) $gid = $group_list[0]['gid'];

        if(cfg('oss_first_loaded') && cfg('oss_first_loaded') == '1'){
            $data_first_loaded = array(
                'type'=>'type=show',
                'value'=>'同步已经完成',
                'info'=>'阿里云OSS同步情况',
                'gid'=>'61',
                'sort'=>'100',
                'tab_id'=>'oss_info',
                'tab_name'=>'阿里云OSS',
            );
            $where = ['name' => 'oss_first_loaded'];
            $configService->saveConfig($where,$data_first_loaded);
        }

        $condition_config['gid'] = $gid;
        $condition_config['status'] = 1;
        $sort = 'sort DESC';

        $config_list = $configService->getTmpConfigList($condition_config,$sort,$this->config);
        $mobile_head_affect = isset($config_list[0]['mobile_head_affect']) ? $config_list[0]['mobile_head_affect'] : 0;
        $colorHeadInfo = (isset($config_list[0]['colorHeadInfo']) && !empty($config_list[0]['colorHeadInfo'])) ? $config_list[0]['colorHeadInfo'] : '#FFFFFF';
        $colorFloorInfo = (isset($config_list[0]['colorFloorInfo']) && !empty($config_list[0]['colorFloorInfo'])) ? $config_list[0]['colorFloorInfo'] : '#FFFFFF';
        // 营山域名限制
        $huizhisq = !empty(customization('huizhisq')) ? true : false;
        $wap_index_jsdhwy_ui=customization('wap_index_jsdhwy_ui');
        if($wap_index_jsdhwy_ui>0){
            $huizhisq=true;
        }
        $returnArr = [
            'gid' => $gid,
            'galias' => input('get.galias'),
            'group_list' => $group_list,
            'header_list' => $header_file??'',
            'config_list' => $config_list,
            'mobile_head_affect' => $mobile_head_affect,
            'huizhisq' => $huizhisq,
            'colorHeadInfo' => $colorHeadInfo,
            'colorFloorInfo' => $colorFloorInfo,
            'domain' => cfg('site_url')
        ];

        return api_output(0, $returnArr);
    }

    /**
     * 上传图片
     * User: chenxiang
     * Date: 2020/9/27 15:31
     * @return \json
     */
    public function upload()
    {
        //$service = new UploadFileService();
        $file = $this->request->file('img');
        try {
            // = $service->uploadPictures($file, 'config');
            // 验证
            validate(['imgFile' => [
                'fileSize' => 1024 * 1024 * 10,   //10M
                'fileExt' => 'jpg,png,jpeg,gif,ico',
                'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
            ]])->check(['imgFile' => $file]);

            $upload_dir = 'config';
//            if (!is_dir($upload_dir)) {
//                mkdir($upload_dir, 0777, true);
//            }

            // 上传图片到本地服务器uniqid
            $saveName = \think\facade\Filesystem::disk('public_upload')->putFile($upload_dir, $file, 'data');
            $saveName = str_replace("\\", '/', $saveName);

            $savepath = '/upload/' . $saveName;

            return api_output(0, $savepath, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }


    /**
     * 提交表单
     * User: chenxiang
     * Date: 2020/6/8 14:47
     * @return \json
     */
    public function amend() {
        set_time_limit(0);
        if($this->request->isPost()) {
            $post_data = input('post.');
			
			//系统后台 system_type 不能触发，会导致系统紊乱
			unset($post_data['system_type']);

            $isEditSendTime = false;
            $mall_order_obligations_time = input('post.mall_order_obligations_time');
            if( isset($mall_order_obligations_time) && $mall_order_obligations_time == 0) {
                return api_output_error(-1, '待付款超时取消时间不能为0。');
            }
            if(input('post.is_open_goods_default_image') == 1 && empty(input('post.goods_default_image'))) {
                return api_output_error(-1, '开启商品默认图片，需要您同时上传一张商品默认图。');
            }

            if(input('post.open_spread_rand') == 1) {
                if(input('post.spread_rand_min')<=0  || input('post.spread_rand_max')<=0){
                    return api_output_error(-1,'开启一级推广随机奖励，每次随机金额最小值与最大值必须设置大于0的数值。');
                }

                if(input('post.spread_rand_min') >= input('post.spread_rand_max')){
                    return api_output_error(-1,'开启一级推广随机奖励，每次随机金额最小值不能大于最大值。');
                }
            }
            // 处理页面交互传错参数问题
            if (!isset($post_data['site_url'])&&(isset($post_data['mobile_head_color'])||isset($post_data['mobile_head_floor_color']))) {
                unset($post_data['mobile_head_color']);
                unset($post_data['mobile_head_floor_color']); 
            }
            if (isset($post_data['house_name']) && trim($post_data['house_name']) == '') {
                unset($post_data['house_name']);
            }

            //判断提交了国际化手机字段，则强行将系统时区设置为默认城市时区一次，如果默认城市无时区，则自动设置为 PRC，北京时间
            if(input('post.country_code')){
                $db_file = 'conf/db.php';
                //判断文件可写
                if(is_writable($db_file)){
                    $db_config = include($db_file);
                    $configService = new ConfigService();
                    $now_city_timezone = $configService->getNowCityTimezone('timezone', ['area_id'=>$this->config['now_city']]);
                    if(!$now_city_timezone){
                        $now_city_timezone = 'PRC';
                    }
                    if($db_config['DEFAULT_TIMEZONE'] != $now_city_timezone){
                        $db_config['DEFAULT_TIMEZONE'] = $now_city_timezone;
                        $db_content = '<?php '.PHP_EOL.'return '.var_export($db_config,true).';'.PHP_EOL.PHP_EOL;
                        file_put_contents($db_file,$db_content);
//                        D('Config')->clearCache(); //import('ORG.Util.Dir'); Dir::delDirnotself(WEB_PATH.'/runtime');
                    }
                }
            }
            //商城关闭骑手配送更新所有店铺的is_hourseman
            if (isset($post_data['mall_platform_delivery_open']) && $post_data['mall_platform_delivery_open'] == 0) {
                (new merchantStoreMall())->updateStoreConfig([['store_id','<>','']], ['is_houseman' => 0]);
            }
            foreach($post_data as $key=>$value){
                $data['name'] = $key;
                if(is_array($value)){
                    if($key == 'delivery_time' || $key == 'delivery_time2' || $key == 'delivery_time3'){
                        $data['value'] = implode('-',$value);
                    }
                    if($key == 'service_delivery_time' || $key == 'service_delivery_time2'){
                        $data['value'] = implode('-',$value);
                    }
                    if($key == 'service_buy_delivery_time' || $key == 'service_buy_delivery_time2'){
                        $data['value'] = implode('-',$value);
                    }
                    if (($key == 'service_buy_weight_info' || $key == 'service_give_weight_info') && $value) {
                        $info = array();
                        foreach ($value as $val) {
                            if ($val && $val['start_weight']>=0 && $val['start_weight']!='' && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                                $info[] = $val;
                            }
                        }
                        if ($info) {
                            $data['value'] = serialize($info);
                        }
                    }
                    if($key=='village_group_threshold_warning_member'){
                        $data['value'] = implode(',',$value);
                    }
                    if($key=='village_group_deliver_custom_time') {//社区团购配送时间段自定义设置
                        if(count($value)>3){
                            return api_output_error(-1, '社区团购配送费自定义配置最多只能设置3个时间段。');
                        }
                        foreach ($value as $item){
                            if($item['start_time']>=$item['end_time']){
                                return api_output_error(-1, '社区团购配送费自定义配置时间段,开始时间必须小于结束时间。');
                            }
                        }
                        $data['value'] = $value ? json_encode($value) : '';
                    }
                }else{
                    $data['value'] = trim(stripslashes(htmlspecialchars_decode($value)));
                }

                if($key=='site_url'){
                    $data['value'] = trim($value);
                    $data['value'] = rtrim($data['value'],'/');
                }

//                //关闭自动开门，则关闭用户原有的自动开门设置
//                if($key == 'house_village_auto_door' && $value == 0){
//                    M('User')->where(array('auto_door_open'=>'1'))->data(array('auto_door_open'=>'0'))->save();
//                }
//
//                //推广营销-平台活动页面开关
//                if($key == 'is_open_activity'){
//                    $data['value'] = trim($value);
//                    M('System_menu')->where(array('module'=>'Platform','action'=>'manage'))->data(array('status'=>$value['status']))->save();
//                }
//                //定制-从业者功能开关
//                if($key == 'employee_switch'){
//                    $data['value'] = trim($value);
//                    M('System_menu')->where(array('module'=>'Employee','action'=>'index'))->data(array('status'=>$value['status']))->save();
//                }
//                //定制-用户推广商家
//                if($key == 'open_c2b_spread'){
//                    M('System_menu')->where(array('module'=>'User','action'=>'user_spread_list'))->data(array('status'=>$value))->save();
//                }
//                //定制-通联支付营销
//                if($key == 'pay_allinpay_mer_open'){
//                    M('System_menu')->where(array('module'=>'Allinpay','action'=>'discount'))->data(array('status'=>$value))->save();
//                }
//                //定制-商家推广商家佣金
//                if($key == 'merchant_recommend_wholesale_switch'){
//                    M('System_menu')->where(array('module'=>'Merchant','action'=>'recommend_index'))->data(array('status'=>$value))->save();
//                    M('New_merchant_menu')->where(array('module'=>'Merchant_recommend','action'=>'index'))->data(array('status'=>$value))->save();
//                }
//                //定制-商家类型
//                if($key == 'merchant_type_switch'){
//                    M('System_menu')->where(array('module'=>'Merchant','action'=>'type_index'))->data(array('status'=>$value))->save();
//                }
//                //定制-发现
//                if($key == 'find_msg'){
//                    M('System_menu')->where(array('module'=>'Discover','action'=>'index'))->data(array('status'=>$value))->save();
//                }
                //定制-快跑者配送
                if($key == 'keloop_is_open'){
                    if($_POST['dada_is_open']==1){
                        $data['value'] = 0;
                    }
                }

//                //定制-隐藏部分前端内容
//                if($key == 'close_system_lottery'){
//                    if($value==1){
//                        $status = 0;
//                    }else{
//                        $status = 1;
//                    }
//                    M('System_menu')->where(array('module'=>'Lottery','action'=>'index'))->data(array('status'=>$status))->save();
//                    M('Config')->where(array('gid'=>'47','tab_id'=>'share_lottery'))->data(array('status'=>$status))->save();
//                }
                if($key == 'alipay_app_prikey' || $key == 'pay_alipayh5_merchant_private_key' || $key == 'pay_alipayh5_public_key'){
                    $data['value'] = str_replace(array(PHP_EOL,' '),'',$value);
                }
                if($key == 'new_pay_alipay_app_public_key' || $key == 'new_pay_alipay_app_private_key'){
                    $data['value'] = str_replace(array(PHP_EOL,' '),'',$value);
                }
                // 业务经理资格
                if($key == 'address_edit_distribution_distance'){
                    $pattern = '/^\d+(\.\d+)?$/';
                    $is_value = preg_match($pattern, $value);
                    if(!$is_value){
                        return api_output_error(-1, '请正确输入公里数！');
                    }
                }
                if($key == 'marketing_service_performance'){
                    $pattern = '/^\d+(\.\d+)?$/';
                    $is_value = preg_match($pattern, $value);
                    if(!$is_value){
                        return api_output_error(-1, '达成业绩总额只能输入数字');
                    }
                }
                // 区域代理资格
                if($key == 'marketing_agent_performance'){
                    $pattern = '/^\d+(\.\d+)?$/';
                    $is_value = preg_match($pattern, $value);
                    if(!$is_value){
                        return api_output_error(-1, '达成业绩总额只能输入数字');
                    }
                }
                
				//变更子计划任务数量
				if($key == 'sub_process_num' && $value < cfg('sub_process_num')){
					(new PlanService())->saveSubPlanRandNumber();
				}

                if(strpos($key,'score_max') && strpos($value,'%')  ){
                    $tmp_v = floatval(str_replace('%','',$value));
                    if($tmp_v>100){
                        return api_output_error(-1, '积分使用数据不能大于100%');
                    }
                }

                $configService = new ConfigService();
                $where['name'] = $key;
                $num = $configService->getDataNumByName($where);
                //分润开关，计划任务初始化到第二天凌晨
//                if($key == 'open_score_fenrun'){
//                    M('Process_plan')->where(array('file'=>'user_fenrun'))->setField('plan_time',(strtotime(date("Y-m-d",$_SERVER['REQUEST_TIME']))+86400));
//                }

                if($num>0){
                    $whereByName['name'] = $key;
                    $before = $configService->getDataOne($whereByName);
                    if($before['gid']){
                        $whereByGid['gid'] = $before['gid'];
                        $before_group = $configService->getDataOneFromConfigGroup($whereByGid);
                    }

                    $configService->saveConfigData($data);

                    if($before['value'] != $data['value']){
                        //D("System_log")->add_log("编辑了配置项:".($before['tab_name'] ?$before['tab_name'] : "").($before['info'] ? ($before['tab_name'] ? "/" : "").$before['info'] : ""),'平台配置项',$key);
                    }
                }else{
                    $configService->addConfigData($data);
                }

                if ($key == 'deliver_send_time') {
                    if ($post_data['deliver_send_time'] != $this->config['deliver_send_time']) {
                        $isEditSendTime = true;
                    }
                }
                if (in_array($data['name'], ['jg_im_appkey', 'jg_im_masterkey'])) {
                    $filterKey = str_replace('jg_', '', $data['name']);
                    $whereByFilterKey['name'] = $filterKey;
                    $old = $configService->getDataFromConfigData($whereByFilterKey);
//                    $old = D('Config_data')->where(['name' => $filterKey])->find();
                    if ($old) {
                        $save_data['value'] = $data['value'];
                        $configService->saveDataFromConfigData($whereByFilterKey,$save_data);
                    } else {
                        $add_data['name'] = $filterKey;
                        $add_data['value'] = $data['value'];
                        $configService->addDataFromConfigData($add_data);
                    }
                }
//                if($key == 'jd_price_add_percent'){
//                    C('config.jd_price_add_percent',$value);
//                }elseif ($key == 'jd_price_add_percent_gt1000'){
//                    C('config.jd_price_add_percent_gt1000',$value);
//                }elseif ($key == 'jd_price_add_percent_gt2000'){
//                    C('config.jd_price_add_percent_gt2000',$value);
//                }elseif ($key == 'jd_price_add_percent_gt3000'){
//                    C('config.jd_price_add_percent_gt3000',$value);
//                    D('Shop_goods')->jdPriceUpdate();
//                }

                if ($key == 'wechat_sourceid') {
                    $data['name'] = 'wechat_token';
                    $data['value'] = md5('pigcms_wechat_token' . $data['value']);
                    $configService->saveConfigData($data);
                }
                if($key == 'weidian_url' && $value && !file_put_contents('../../api/weidian.urls',$data['value'])){
                    return api_output_error(-1, '配置保存失败，请检查网站根目录下的api文件夹是否拥有可写权限。');
                }
                if($key == 'appoint_site_url' && $value && !file_put_contents('../../api/appoint.urls',$data['value'])){
                    return api_output_error(-1,'配置保存失败，请检查网站根目录下的api文件夹是否拥有可写权限。');
                }
                if($key == 'portal_switch' && !file_put_contents('../../api/default.urls',$data['value'])){
                    return api_output_error(-1,'配置保存失败，请检查网站根目录下的api文件夹是否拥有可写权限。');
                }
            }

//            //更新平台客服用户昵称和头像
              invoke_cms_model('Hook/exec',['site_info.after_update',['type'=>'plat']]);     
//            hook::hook_exec('site_info.after_update',array('type'=>'plat'));
//
//            //删除缓存
//            D('Config')->clearDataCacheDir('system_config');
//
//            if ($isEditSendTime) {
//                $sql = "UPDATE pigcms_merchant_store_shop SET ";
//                $sql .= "send_time={$_POST['deliver_send_time']}, s_send_time={$_POST['deliver_send_time']}, sort_time = CASE ";
//                $sql .= "WHEN send_time_type =0 THEN {$_POST['deliver_send_time']} + work_time ";
//                $sql .= "WHEN send_time_type =1 THEN {$_POST['deliver_send_time']} + work_time *60 ";
//                $sql .= "WHEN send_time_type =2 THEN {$_POST['deliver_send_time']} + work_time *1440 ";
//                $sql .= "WHEN send_time_type =3 THEN {$_POST['deliver_send_time']} + work_time *10080 ";
//                $sql .= "WHEN send_time_type =4 THEN {$_POST['deliver_send_time']} + work_time *43200 END";
//                $sql .= " WHERE (`deliver_type`=0 OR `deliver_type`=3) AND (`s_send_time`=0 OR `s_send_time`={$this->config['deliver_send_time']})";
//                D()->execute($sql);
//            }

            //同步快店、团购、快速买单中会员等级优惠比例
            $leveloff = [];
            $userLevel =  (new UserLevelService())->getSome([],true,['id'=>'ASC']);//查询当前会员等级
            foreach ($userLevel as $kk => $vv) {
                $vl['type'] = intval($vv['type']);
                $vl['vv'] = intval($vv['boon']);
                if (($vl['type'] > 0) && ($vl['vv'] > 0)) {
                    $vl['level'] = $vv['level'];
                    $vl['lname'] = $vv['lname'];
                    $vl['lid'] = $vv['id'];
                    $leveloff[$vv['level']] = $vl;
                }
            }
            $leveloff = $leveloff ? serialize($leveloff) : '';
            if(isset($post_data['discount_sync']) && $post_data['discount_sync']){
                (new MerchantStoreShop())->where([['store_id','>',0]])->update(['leveloff'=>$leveloff]);
                (new Group())->where([['group_id','>',0]])->update(['leveloff'=>$leveloff]);
                (new MerchantStore())->where([['store_id','>',0]])->update(['leveloff'=>$leveloff]);
            }

            //如果更换了腾讯地图KEY，前端打包页面也要自动替换
            $newTencentKey = isset($post_data['tencent_map_key'])?trim($post_data['tencent_map_key']):'';
            if ($newTencentKey && $newTencentKey != cfg('tencent_map_key')) {
                $dirLists = [
                    app()->getRootPath() . '../packapp/plat/static/js/',
                    app()->getRootPath() . '../packapp/worker/static/js/',
                    app()->getRootPath() . '../packapp/village/static/js/',
                    app()->getRootPath() . '../packapp/community/static/js/',
                ];
                $oldKeys = ['ZDUBZ-ND23W-SNNR2-RLVAE-7JP7Z-KVBLK'];
                cfg('tencent_map_key') && $oldKeys[] =  cfg('tencent_map_key');
                foreach ($dirLists as $dest) {
                    edit_dir_file($dest, $oldKeys, $newTencentKey);
                }
            }
            
            (new CacheSqlService())->clearCache();
            return api_output(1000, ['msg'=>'修改成功！']);
        } else {
            return api_output_error(-1, '非法提交,请重新提交~');
        }
    }

    /**
     * 获取及时聊天key
     * User: chenxiang
     * Date: 2020/6/8 16:20
     */
    public function im(){
        if (empty($this->config['site_url'])) {
//            exit(json_encode(array('error_code' => true, 'msg' => '先填写您网站的域名')));
            return api_output_error(-1, ['msg'=>'先填写您网站的域名！']);
        }
        if (empty($this->config['wechat_appid']) || empty($this->config['wechat_appsecret'])) {
//            exit(json_encode(array('error_code' => true, 'msg' => '先设置站点的微信公众号信息')));
            return api_output_error(-1, ['msg'=>'先设置站点的微信公众号信息！']);
        }
        $im = new im();
        $im->create();
    }


    /**
     * 获取配置
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function getConfig()
    {
        $str = $this->request->param('str', '', 'trim');
        $names = array_filter(explode(',', $str));
        if (empty($names)) {
            return api_output_error(-1, '没有配置项');
        }
        $config = (new ConfigService())->getConfigList([['name', 'in', $names]]);
        $config = array_combine(array_column($config,'name'),array_column($config,'value'));
        return api_output(0, $config);
    }


    /**
     * 设置配置
     * @author: 张涛
     * @date: 2020/11/25
     */
    public function saveConfig()
    {
        $data = $this->request->param('data', []);
        if (empty($data)) {
            return api_output_error(-1, '没有配置项');
        }
        $configService = new ConfigService();
        foreach ($data as $k => $v) {
            $configService->saveConfigData(['name' => $k, 'value' => $v]);
        }
        \think\facade\Cache::clear();
        return api_output(0, []);
    }
}
