<?php

class PercentAction extends BaseAction
{
    /**
     * @return 抽成设置
     */
    public function percent()
    {
        if (IS_POST) {
            $plat_percent = $_POST['platform_get_merchant_percent'];
            $newlandpos_percent = $_POST['platform_get_newlandpos_percent'];
            $money_start_arr = $_POST['money_start'];
            $money_end_arr = $_POST['money_end'];
            $percent_arr = $_POST['money_percent'];
            $tmp = 0;
            $data_arr  = array();
            foreach ($_POST as $_key => $_value) {
                if(strpos($_key, 'old_age_pension') !== false){
                    $temp = substr($_key, 16);
                    $temp = explode('_', $temp);
                    M('Config')->where(['name' => 'oap_'.$temp[0].'_'.$temp[1]])->save(['value' => $_value]);
                }
            }
            if(empty($_POST['type'])) {
//                if(empty($money_start_arr)){
//                    $this->error('数据设置有误，请检查');
//                }elseif(empty($money_end_arr)){
//                    $this->error('数据设置有误，请检查');
//                }elseif(empty($percent_arr)){
//                    $this->error('数据设置有误，请检查');
//                }
                M('Config')->where(['name' => 'platform_get_newlandpos_percent'])->save(['value' => $newlandpos_percent]);
                $config_where['name'] = 'platform_get_merchant_percent';

                $result_config = M('Config')->where($config_where)->save( array('value'=>$plat_percent));
                foreach ($money_start_arr as $key => $v) {
					if(empty($v) && empty($money_end_arr[$key]) && empty($percent_arr[$key])){
						continue;
					}
					
                    if (!is_numeric($v)||!is_numeric($money_end_arr[$key])||!is_numeric($percent_arr[$key])||$v < 0 || empty($money_end_arr[$key]) || $money_end_arr[$key] < 0 || $percent_arr[$key]==='' || $percent_arr[$key] < 0) {
                        $this->error('数据设置有误，请检查');
                    } else if ($v >= $money_end_arr[$key]) {
                        $this->error('金额范围设置有误，金额范围结束不能比金额范围起始小');
                    }
                    if ($tmp != 0 && $money_start_arr[$key] < $tmp) {
                        $this->error('金额范围设置有误，金额范围起始不能比上一级的金额范围结束小');
                    }
                    $data['money_start'] = $v;
                    $data['money_end'] = $money_end_arr[$key];
                    $data['percent'] = $percent_arr[$key];
                    $data_arr[] = $data;
                    $tmp = $money_end_arr[$key];
                }
                //2020/9/29 start  修改
                $count = M('Percent_detail')->count();
                if($count)
                {
                    M('')->execute('DELETE FROM '.C('DB_PREFIX').'percent_detail');
                }
                D("System_log")->add_log("编辑了平台抽成设置",'平台配置项',0);
                $res = M('Percent_detail')->addAll($data_arr);
                
                // 清除v20缓存
                $requestUrl = C('config.site_url').'/v20/public/index.php/common/common.index/deleteCache?name=config';
                import('ORG.Net.Http');
                $http = new Http();
                $return = Http::curlGet($requestUrl);

                import('ORG.Util.Dir');
                Dir::delDirnotself('./runtime/Temp');
                if($res || $result_config)
                {
                    $this->success('保存成功');
                }else{
                    $this->error('保存失败,请检查是否没有修改任何数据！');
                }
//                if(M('')->execute('DELETE FROM '.C('DB_PREFIX').'percent_detail')){
//                    D("System_log")->add_log("编辑了平台抽成设置",'平台配置项',0);
//                    M('Percent_detail')->addAll($data_arr);
//                    import('ORG.Util.Dir');
//                    Dir::delDirnotself('./runtime/Temp');
//                    $this->success('保存成功');
//                }else{
//                    $this->error('保存失败');
//                }
                //2020/9/29 end  修改
            }else{
                $where['fid'] = 0;

                //快店平台配送抽成
                if (isset($_POST['plat_deliver'])) {
                    $shop_plat_deliver_percent = $_POST['plat_deliver'] == 1 ? $_POST['shop_plat_deliver_percent'] : -1;
                    $result_config1 = M('Config')->where(['name' => 'shop_plat_deliver_percent'])->save(['value' => $shop_plat_deliver_percent]);
                }
                //快店商家配送抽成
                if (isset($_POST['merchant_deliver'])) {
                    $shop_merchant_deliver_percent = $_POST['merchant_deliver'] == 1 ? $_POST['shop_merchant_deliver_percent'] : -1;
                    $result_config2 = M('Config')->where(['name' => 'shop_merchant_deliver_percent'])->save(['value' => $shop_merchant_deliver_percent]);
                }
                //快店快递配送抽成
                if (isset($_POST['express_deliver'])) {
                    $shop_express_deliver_percent = $_POST['express_deliver'] == 1 ? $_POST['shop_express_deliver_percent'] : -1;
                    $result_config3 = M('Config')->where(['name' => 'shop_express_deliver_percent'])->save(['value' => $shop_express_deliver_percent]);
                }
                //快店自提抽成
                if (isset($_POST['self_pick'])) {
                    $shop_self_pick_percent = $_POST['self_pick'] == 1 ? $_POST['shop_self_pick_percent'] : -1;
                    $result_config4 = M('Config')->where(['name' => 'shop_self_pick_percent'])->save(['value' => $shop_self_pick_percent]);
                }
                $shop_deliver_result = $result_config1||$result_config2||$result_config3||$result_config4;

                if(isset($_POST['meal_scan_percent'])){
                    $config_where_['name'] = 'meal_scan_percent';
                    $result_config_ = M('Config')->where($config_where_)->save( array('value'=>$_POST['meal_scan_percent']));
                }
                $config_where['name'] = $_POST['type'].'_percent';

                $result_config = M('Config')->where($config_where)->save( array('value'=>$_POST[$_POST['type'].'_percent']));
                $data[$_POST['type'].'_percent_detail'] = implode(',',$percent_arr);
                if(M('Percent_detail_by_type')->where($where)->find()){
                    $result = M('Percent_detail_by_type')->where($where)->save($data);
                }else{
                    $data['fid'] = 0;
                    $result = M('Percent_detail_by_type')->add($data);
                }
                if($result||$result_config||$result_config_||$shop_deliver_result){
                    D("System_log")->add_log("编辑了抽成设置",'平台配置项',0);

                    // 清除v20缓存
                    $requestUrl = C('config.site_url').'/v20/public/index.php/common/common.index/deleteCache?name=config';
                    import('ORG.Net.Http');
                    $http = new Http();
                    $return = Http::curlGet($requestUrl);

                    import('ORG.Util.Dir');
                    Dir::delDirnotself('./runtime/Temp');

                    $this->success('保存成功');
                }else{
                    $this->error('保存失败,请检查是否没有修改任何数据！');
                }
            }
        } else {
            if(!empty($_GET['type'])){
                $result = M('Percent_detail_by_type')->where(array('fid'=>0))->find();
                $detail = explode(',',$result[$_GET['type'].'_percent_detail']);
                $this->assign('detail', $detail);
            }
            $percent_detail = M('Percent_detail')->select();

            $this->assign('percent_detail', $percent_detail);
            $this->display();
        }

    }

    public function rate(){
        // 获得商家推广分佣的区间配置
        $percent_detail = M('Percent_spread_merchant_detail')->order('id ASC')->select();
        $this->assign('percent_detail', $percent_detail);
        $this->display();
    }

    public function user_rate(){
        //客户存在景区业务就显示景区推广分佣
        $plugin = M('Plugin')->where(['plugin_label' => 'life_tools', 'status' => 1])->find();
        $this->assign('openScenicUserSpread', $plugin ? true : false);
        // 获得用户推广分佣的区间配置
        $percent_detail = M('Percent_spread_detail')->select();
        $this->assign('percent_detail', $percent_detail);
        $this->display();
    }

    // 获得用户推广分佣的区间配置
    public function ajax_get_user_rate(){
        if($_POST['type']){

            $result = M('Percent_spread_detail_by_type')->where(array('fid'=>0))->find();
            $detail = explode(',',$result[$_POST['type'].'_percent_detail']);
        }

        $percent_detail = M('Percent_spread_detail')->select();
        
        $return['percent_detail'] = $percent_detail;
        $return['detail'] = $detail ?? [];
        echo json_encode($return);
    }

    public function score(){
        $this->display();
    }

    //定制积分规则
    public function other_score(){
        if(IS_POST){
            $where['name'] ='vip_day';
            $_POST['tab']==1 && M('Config')->where( $where)->setField('value',$_POST['vip_day']);
            $where['name'] ='vipday_score_times';
            $_POST['tab']==1 &&  M('Config')->where( $where)->setField('value',$_POST['vipday_score_times']);
            $where['name'] ='vip_day_type';
            $_POST['tab']==1 && M('Config')->where( $where)->setField('value',$_POST['vip_day_type']);
            if($_POST['tab']==1){
                D("System_log")->add_log("编辑了会员日积分获得倍数设置",'平台配置项',0);
            }

            $where['name'] ='birthday_score_times';
            $_POST['tab']==2 && M('Config')->where( $where)->setField('value',$_POST['birthday_score_times']);
            if($_POST['tab']==2){
                D("System_log")->add_log("编辑了会员生日获得积分倍数设置",'平台配置项',0);
            }
//            if($_POST['id']){
//                foreach ($_POST['score_times_spday_start'] as $k=>$v) {
//                    $save_date['start'] = $_POST['score_times_spday_start'][$k];
//                    $save_date['end'] = $_POST['score_times_spday_end'][$k];
//                    $save_date['value'] = $_POST['score_times_spday_value'][$k];
//                    if($_POST['id'][$k]){
//                        M('Special_day_score_times')->where(array('id'=>$_POST['id'][$k]))->save($save_date);
//                    }else{
//                        M('Special_day_score_times')->add($save_date);
//                    }
//                }
//                echo 1;
//            }else{
            if ($_POST['tab']==3) {
                M('Special_day_score_times')->where('1=1')->delete();
                if(count($_POST['score_times_spday_start'])>0){
                    foreach ($_POST['score_times_spday_start'] as $k=>$v) {
                        $tmp['start'] = $v;
                        $tmp['end'] = $_POST['score_times_spday_end'][$k];
                        $tmp['value'] = $_POST['score_times_spday_value'][$k];
                        $add_date[] = $tmp;
                    }
                    M('Special_day_score_times')->addAll($add_date);
                }
                D("System_log")->add_log("编辑了特殊日期积分获得倍数设置",'平台配置项',0);
            }

            import('ORG.Util.Dir');
               Dir::delDirnotself('./runtime/Temp/');
                 $this->success('编辑成功！');
//            } 
        }else{
            $week = array(
                '0' => '每周日',
                '1' => '每周一',
                '2' => '每周二',
                '3' => '每周三',
                '4' => '每周四',
                '5' => '每周五',
                '6' => '每周六',
            );
            $day = array(
                '0' => '每月10号，20号，30号',
                '1' => '每月1号，11号，21号，31号',
                '2' => '每月2号，12号，22号',
                '3' => '每月3号，13号，23号',
                '4' => '每月4号，14号，24号',
                '5' => '每月5号，15号，25号',
                '6' => '每月6号，16号，26号',
                '7' => '每月7号，17号，27号',
                '8' => '每月8号，18号，28号',
                '9' => '每月9号，19号，29号',
            );
            $score_times = M('Special_day_score_times')->select();
            // fdump( D('Percent_rate')->get_user_add_score_times(100,100),'test',true);
            $this->assign('score_times',$score_times);
            $this->assign('week',json_encode($week));
            $this->assign('day',json_encode($day));
            $this->display();
        }
    }

    public function distributor_agent(){
        $levelDb = M(_view('User_level'));
        $levelarr = $levelDb->order('id ASC')->select();

        $this->assign('levelarr', $levelarr);
        $this->display();
    }


}