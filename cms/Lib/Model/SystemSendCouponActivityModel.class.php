<?php

/**
 * 系统后台推广营销-》发券功能
 * @author 张涛
 * @date 2020/08/03
 */
class SystemSendCouponActivityModel extends Model
{
    public function isActivity($sendType,$except = [])
    {
        $where['is_del'] = 0;
        $where['status'] = 1;
        $where['end_date'] = ['egt', date('Y-m-d')];
        $where['send_type'] = $sendType;
        if ($except) {
            $where['id'] = ['not in', $except];
        }
        $rs = $this->where($where)->find();
        return $rs ? true : false;
    }

    public function isTimeCross($sendType, $beginTime, $endTime,$except = [])
    {
        $where = [
            'is_del' => 0,
            'status' => 1,
            'send_type' => $sendType
        ];
        if ($except) {
            $where['id'] = ['not in', $except];
        }
        $lists = $this->where($where)->select();
        $isCross = false;
        foreach ($lists as $v) {
            if (isTimeCross($beginTime, $endTime, strtotime($v['start_date']), strtotime($v['end_date']) + 86399)) {
                $isCross = true;
                break;
            }
        }
        return $isCross;
    }

    public function addActivity($post)
    {
        $rs = ['error_no' => 0, 'error_msg' => ''];
        $this->startTrans;
        try {
            if($_POST['is_city']==1){
                if(!$post['province_idss']||!$post['city_idss']){
                    throw new Exception('请选择省市');
                }
                $post['province_id'] = $post['province_idss'];
                $post['city_id'] = $post['city_idss'];
            }else{
                $post['is_city'] = 0;
                $post['province_id'] = 0;
                $post['city_id'] = 0;
            }
            
            if (!empty($_FILES['background_pic']['name'])) {
                $param['size'] = 2;
                $param['imageClassPath'] = 'ORG.Util.Image';
                $param['thumbRemoveOrigin'] = false;

                $image = D('Image')->handle(0, 'send_coupon_activity', 0, $param);
                if ($image['error']) {
                    throw new Exception($image['message']);
                }
                $backgroundPic = $image['url']['background_pic'];
            } else {
                $backgroundPic = '';
            }

            $data = [
                'activity_name' => $post['activity_name'] ?? '',
                'start_date' => $post['start_date'] ?? '',
                'end_date' => $post['end_date'] ?? '',
                'send_type' => $post['send_type'] ?? 1,
                'get_way' => $post['get_way'] ?? 1,
                'title' => $post['title'] ?? '',
                'color' => $post['color'] ?? '#FFFFFF',
                'background_color' => $post['background_color'] ?? '#E13924',
                'background_pic' => $backgroundPic,
                'province_id' => $post['province_id'],
                'city_id' => $post['city_id'],
                'is_city' => $post['is_city'],
            ];

            if(empty($data['activity_name'])){
                throw new Exception('活动名称不能为空');
            }
            if(empty($data['start_date']) || empty($data['end_date'])){
                throw new Exception('活动时间不能为空');
            }

            $showPage = 0;
            foreach ($post['show_page'] as $v) {
                if ($showPage == 0) {
                    $showPage = intval($v);
                } else {
                    $showPage |= intval($v);
                }
            }
            if ($showPage == 0) {
                throw new Exception('请选择展示页面');
            }

            $beginTime = strtotime($post['start_date']);
            $endTime = strtotime($post['end_date']) + 86399;
            if ($endTime < $beginTime) {
                throw new Exception('活动结束时间必须大于开始时间');
            }

            // if ($this->isTimeCross($data['send_type'], $beginTime, $endTime)) {
            //     throw new Exception('当前活动时间存在重复');
            // }
            $couponIds = $post['coupon_ids'] ?? [];
            $couponTypes = $post['coupon_type'] ?? [];
            if (empty($couponIds)) {
                throw new Exception('请选择优惠券');
            }

            $data['show_page'] = $showPage;
            $aid =  $this->add($data);
            if ($aid < 1) {
                throw new Exception('活动保存失败');
            }
            $setting = [];

            foreach ($couponIds as $k => $v) {
                if (empty($v) || empty($couponTypes[$k])) {
                    continue;
                }
                $setting[] = [
                    'aid' => $aid,
                    'coupon_id' => $v,
                    'coupon_type' => $couponTypes[$k]
                ];
            }
            M('System_send_coupon_activity_setting')->addAll($setting);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback;
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }
        return $rs;
    }

    public function editActivity($post)
    {
        $rs = ['error_no' => 0, 'error_msg' => ''];
        $this->startTrans;
        try {
            $id = intval($post['id']);
            $activity = $this->where(['id' => $id, 'is_del' => 0])->find();
            if (empty($activity)) {
                throw new Exception('活动不存在');
            }

            if($_POST['is_city']==1){
                if(!$post['province_idss']||!$post['city_idss']){
                    throw new Exception('请选择省市');
                }
                $post['province_id'] = $post['province_idss'];
                $post['city_id'] = $post['city_idss'];
            }else{
                $post['is_city'] = 0;
                $post['province_id'] = 0;
                $post['city_id'] = 0;
            }

            if (!empty($_FILES['background_pic']['name'])) {
                $param['size'] = 2;
                $param['imageClassPath'] = 'ORG.Util.Image';
                $param['thumbRemoveOrigin'] = false;

                $image = D('Image')->handle(0, 'send_coupon_activity', 0, $param);
                if ($image['error']) {
                    throw new Exception($image['message']);
                }
                $backgroundPic = $image['url']['background_pic'];
            } else {
                $backgroundPic = $post['background_pic'];
            }


            $data = [
                'activity_name' => $post['activity_name'] ?? '',
                'start_date' => $post['start_date'] ?? $activity['start_date'],
                'end_date' => $post['end_date'] ?? '',
                'send_type' => $post['send_type'] ?? $activity['send_type'],
                'get_way' => $post['get_way'] ?? $activity['get_way'],
                'title' => $post['title'] ?? $activity['title'],
                'color' => $post['color'] ?: $activity['color'],
                'background_color' => $post['background_color'] ?: $activity['background_color'],
                'background_pic' => $backgroundPic,
                'province_id' => $post['province_id'],
                'city_id' => $post['city_id'],
                'is_city' => $post['is_city'],
            ];
            $showPage = 0;
            foreach ($post['show_page'] as $v) {
                if ($showPage == 0) {
                    $showPage = intval($v);
                } else {
                    $showPage |= intval($v);
                }
            }

            if (empty($data['activity_name'])) {
                throw new Exception('活动名称不能为空');
            }
            if (empty($data['end_date'])) {
                throw new Exception('活动时间不能为空');
            }
            if ($showPage == 0) {
                throw new Exception('请选择展示页面');
            }

            $beginTime = strtotime($activity['start_date']);
            $endTime = strtotime($data['end_date']) + 86399;
            if ($endTime < $beginTime) {
                throw new Exception('活动结束时间必须大于开始时间');
            }

            // if ($this->isTimeCross($activity['send_type'], $beginTime, $endTime,[$id])) {
            //     throw new Exception('当前活动时间存在重复');
            // }

            $couponIds = $post['coupon_ids'] ?? [];
            $couponTypes = $post['coupon_type'] ?? [];
            if (empty($couponIds)) {
                throw new Exception('请选择优惠券');
            }

            $data['show_page'] = $showPage;
            $saveRs =  $this->where(['id' => $id])->save($data);
            if ($saveRs === false) {
                throw new Exception('活动保存失败');
            }
            $setting = [];
            foreach ($couponIds as $k => $v) {
                if (empty($v) || empty($couponTypes[$k])) {
                    continue;
                }
                $setting[] = [
                    'aid' => $id,
                    'coupon_id' => $v,
                    'coupon_type' => $couponTypes[$k]
                ];
            }
            M('System_send_coupon_activity_setting')->where(['aid' => $id])->delete();
            M('System_send_coupon_activity_setting')->addAll($setting);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback;
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }
        return $rs;
    }

    public function getLists($get)
    {
        $where = ['is_del' => 0];
        if (!empty($get['activity_name'])) {
            $where['activity_name'] = ['LIKE', '%' . trim($get['activity_name']) . '%'];
        }
        if (!empty($get['start_date'])) {
            $where['start_date'] = ['EGT', $get['start_date']];
        }
        if (!empty($get['end_date'])) {
            $where['end_date'] = ['ELT', $get['end_date']];
        }
        if (!empty($get['send_type'])) {
            $where['send_type'] = $get['send_type'];
        }
        if (!empty($get['show_page'])) {
            $where['_string'] = 'show_page&' . $get['show_page'];
        }
        $count = $this->where($where)->count();
        $tm = time();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $lists = $this->field(true)->where($where)->order('id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($lists as &$v){
            $v['progress_status'] = 0;
            if($v['status'] == 0){
                $v['status_txt'] = '已失效';
            }else if($tm > strtotime($v['start_date']) && $tm < strtotime($v['end_date'])+86400){
                $v['status_txt'] = '进行中';
                $v['progress_status'] = 1;
            }else if($tm < strtotime($v['start_date'])){
                $v['status_txt'] = '未开始';
            }else if($tm > strtotime($v['end_date'])+86400){
                $v['status_txt'] = '已结束';
            }
            $hadpull = M('System_send_coupon_activity_setting')->where(['aid' => $v['id']])->sum('had_pull');
            $v['had_pull'] = intval($hadpull);

            //统计使用优惠券数量
            $useCount = M('System_send_coupon_activity_setting')->alias('setting')
                ->join('INNER JOIN ' . C('DB_PREFIX') . 'system_send_coupon_activity_log AS log ON log.coupon_id=setting.coupon_id AND log.coupon_type = setting.coupon_type')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'card_new_coupon_hadpull AS mer_pull ON mer_pull.coupon_id = log.coupon_id AND log.hadpull_id = mer_pull.id AND log.coupon_type="mer"')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'system_coupon_hadpull AS plat_pull ON plat_pull.coupon_id = log.coupon_id AND log.hadpull_id = plat_pull.id AND log.coupon_type="plat"')
                ->where('(mer_pull.is_use = 1 OR plat_pull.is_use = 1) AND setting.aid = ' . $v['id'])
                ->count();
            $v['use_num'] = $useCount;
        }
        return ['lists' => $lists, 'pagebar' => $p->show()];
    }


    public function getDetailById($id)
    {
        $baseInfo = $this->where(['id' => $id])->find();

        $fields = "s.*";
        $fields .= ",plat.is_discount AS plat_is_discount,plat.coupon_id as plat_coupon_id,plat.name AS plat_name,plat.order_money AS plat_order_money,plat.discount_value AS plat_discount_value,plat.discount AS plat_discount,plat.start_time AS plat_start_time,plat.end_time AS plat_end_time,plat.platform AS plat_platform,plat.cate_name AS plat_cate_name,plat.num AS plat_num,plat.had_pull AS plat_had_pull";
        $fields .= ",mer.is_discount AS mer_is_discount,mer.coupon_id as mer_coupon_id,mer.name AS mer_name,mer.order_money AS mer_order_money,mer.discount_value AS mer_discount_value,mer.discount AS mer_discount,mer.start_time AS mer_start_time,mer.end_time AS mer_end_time,mer.platform AS mer_platform,mer.cate_name AS mer_cate_name,mer.num AS mer_num,mer.had_pull AS mer_had_pull";

        $setting = M('System_send_coupon_activity_setting')->alias('s')
            ->join(C('DB_PREFIX') . 'system_coupon AS plat ON s.coupon_id = plat.coupon_id AND s.coupon_type="plat"')
            ->join(C('DB_PREFIX') . 'card_new_coupon AS mer ON s.coupon_id = mer.coupon_id AND s.coupon_type="mer"')
            ->field($fields)
            ->where(['s.aid' => $id])
            ->select();

        $return = D('System_coupon')->cate_platform();
        $category = $return['category'];
        $platform = $return['platform'];

        $coupon = [];
        foreach ($setting as $v) {

            if ($v[$v['coupon_type'] . '_is_discount'] == 1) {
                $discountTxt = sprintf('满%s打%s折', getFormatNumber($v[$v['coupon_type'] . '_order_money']), getFormatNumber($v[$v['coupon_type'] . '_discount_value']));
            } else {
                $discountTxt =  sprintf('满%s减%s', getFormatNumber($v[$v['coupon_type'] . '_order_money']), getFormatNumber($v[$v['coupon_type'] . '_discount']));
            }

            $platformDescArr = array_map(function ($item) use ($platform) {
                return $platform[$item] ?? '--';
            }, unserialize($v[$v['coupon_type'] . '_platform']));
            $platformTxt = implode(' ', $platformDescArr);

            if ($v[$v['coupon_type'] . '_cate_name'] == 'all') {
                $cateNameTxt = '全部类别';
            } else {
                $cateNameTxt = $category[$v[$v['coupon_type'] . '_cate_name']] ?? '-';
            }

            //剩余数量
            $v['left_num'] = $v[$v['coupon_type'] . '_num'] - $v[$v['coupon_type'] . '_had_pull'];

            $coupon[] = [
                'coupon_id' => $v['coupon_id'],
                'name' => $v[$v['coupon_type'] . '_name'],
                'discount_txt' => $discountTxt,
                'time' => date('Y-m-d', $v[$v['coupon_type'] . '_start_time']) . '至' . date('Y-m-d', $v[$v['coupon_type'] . '_end_time']),
                'platform_txt' => $platformTxt,
                'cate_name_txt' => $cateNameTxt,
                'type' => $v['coupon_type'],
                'left_num'=>$v['left_num']
            ];
        }
        empty($baseInfo['color']) && $baseInfo['color'] = '#FFFFFF';
        empty($baseInfo['background_color']) && $baseInfo['background_color'] = '#E13924';
        empty($baseInfo['background_pic']) && $baseInfo['background_pic'] = C('config.site_url') . '/static/default_background_send_coupon.png';
        $baseInfo['show_background_pic'] = replace_file_domain($baseInfo['background_pic']);
        $baseInfo['coupons'] = $coupon;
        return $baseInfo;
    }


    public function getNowActivity($uid, $page = '', $city_id = 0, $status = -1)
    {
        if ($uid > 0) {
            $paidOrderCount = D('User')->check_new($uid, 'all');
            $isNew = $paidOrderCount ? 1 : 2;
        } else {
            $isNew = 1;
        }

        $today = date('Y-m-d');
        $where = [
            'send_type' => $isNew,
            'is_del' => 0,
            'status' => 1,
            'start_date' => ['elt', $today],
            'end_date' => ['egt', $today],
        ];
        $where['_string'] = '';
        if ($page == 'plat_index') {
            $where['_string'] = 'show_page&1 = 1';
        } else if ($page == 'shop_index') {
            $where['_string'] = 'show_page&2 = 2';
        }else if ($page == 'mall_index') {
            //商城券
            $where['_string'] = 'show_page&4 = 4';
        }else if ($page == 'appoint_index') {
            //预约首页
            $where['_string'] = 'show_page&8 = 8';
        } else if ($page == 'all') {
            //$where['_string'] = 'show_page&3 = 3';
        } else {
            return [];
        }
        if ($city_id){
            $p_area = D('Area')->where(['area_id'=>$city_id,'status'=>1])->getField('area_pid');
            $where_or = '(is_city = 0 or (is_city = 1 and city_id in ('.$city_id.','.$p_area.')))';
            $where['_string'] .= $where['_string']?' and '.$where_or:$where_or;
        }
        
        $activity = $this->where($where)->order('id DESC')->find();
        if (empty($activity)) {
            return [];
        }
        $fields = "s.*";
        $fields .= ",plat.img AS plat_img,plat.is_discount AS plat_is_discount,plat.coupon_id as plat_coupon_id,plat.name AS plat_name,plat.order_money AS plat_order_money,plat.discount_value AS plat_discount_value,plat.discount AS plat_discount,plat.start_time AS plat_start_time,plat.end_time AS plat_end_time,plat.platform AS plat_platform,plat.cate_name AS plat_cate_name,plat.is_privileged AS plat_is_privileged,plat.is_hide AS plat_is_hide,plat.allow_sign AS plat_allow_sign,plat.allow_gift AS plat_allow_gift,plat.allow_im_get AS plat_allow_im_get,plat.allow_new AS plat_allow_new,plat.is_vip_level AS plat_is_vip_level,plat.status AS plat_status,plat.num AS plt_num,plat.`limit` AS plat_limit,plat.use_type AS plat_use_type,plat.coupon_url AS plat_coupon_url";
        $fields .= ",mer.mer_id,mer.store_id,mer.img AS mer_img,mer.is_discount AS mer_is_discount,mer.coupon_id as mer_coupon_id,mer.name AS mer_name,mer.order_money AS mer_order_money,mer.discount_value AS mer_discount_value,mer.discount AS mer_discount,mer.start_time AS mer_start_time,mer.end_time AS mer_end_time,mer.platform AS mer_platform,mer.cate_name AS mer_cate_name,mer.allow_new AS mer_allow_new,mer.allow_im_get AS mer_allow_im_get,mer.is_live AS mer_is_live,mer.status AS mer_status,mer.num AS mer_num,mer.`limit` AS mer_limit,mer.coupon_url AS mer_coupon_url";

        $sys_coupon_join = C('DB_PREFIX') . 'system_coupon AS plat ON s.coupon_id = plat.coupon_id AND s.coupon_type="plat"';
        $mer_coupon_join = C('DB_PREFIX') . 'card_new_coupon AS mer ON s.coupon_id = mer.coupon_id AND s.coupon_type="mer"';
        if($status > -1){
            $sys_coupon_join .= 'AND plat.status = '. $status;
            $mer_coupon_join .= 'AND mer.status = '. $status;
        }
        $setting = M('System_send_coupon_activity_setting')->alias('s')
            ->join($sys_coupon_join)
            ->join($mer_coupon_join)
            ->field($fields)
            ->where(['s.aid' => $activity['id']])
            ->select();
        $coupon = [];
        $logMod = M('System_send_coupon_activity_log');
        $getCouponIndex = [];
        if ($uid > 0) {
            $logs = $logMod->where(['uid' => $uid, 'aid' => $activity['id']])->field('concat(`coupon_type`,"_",`coupon_id`) AS couponIndex')->select();
            $getCouponIndex = array_column($logs, 'couponIndex');
        }

        $isAlert = false;
        foreach ($setting as $v) {
            //判断用户领取是否达到上线，是否还有剩余
            if ($v[$v['coupon_type'] . '_status'] == 3) {
                continue;
            }

            $canGetNum = $v[$v['coupon_type'] . '_limit'];
            if ($uid > 0) {
                $maxNum = $v[$v['coupon_type'] . '_limit'];
                if ($v['coupon_type'] == 'mer') {
                    $count = M('Card_new_coupon_hadpull')->where(['coupon_id' => $v['coupon_id'], 'uid' => $uid])->count();
                } else {
                    $count = M('System_coupon_hadpull')->where(['coupon_id' => $v['coupon_id'], 'uid' => $uid])->count();
                }
                if ($count >= $maxNum) {
                    continue;
                }else{
                    $canGetNum = $maxNum - $count;
                }
            }

            $data = [
                'id' => $v['id'],
                'type' => $v['coupon_type'],
                'mer_id' => intval($v['mer_id']),
                'coupon_id' => $v['coupon_id'],
                'coupon_name' => $v[$v['coupon_type'] . '_name'],
                'sub_title' => sprintf('满%s可用', getFormatNumber($v[$v['coupon_type'] . '_order_money'])),
                'coupon_type' => $v[$v['coupon_type'] . '_is_discount'] == 1 ? 2 : 1,
                'discount' => $v[$v['coupon_type'] . '_is_discount'] == 1 ? getFormatNumber($v[$v['coupon_type'] . '_discount_value']) : getFormatNumber($v[$v['coupon_type'] . '_discount']),
                'store_ids' => is_null($v['store_id']) ? '' : $v['store_id'],
                'pic' => replace_file_domain($v[$v['coupon_type'] . '_img']),
            ];
            $storeIds = explode(',', $data['store_ids']);
            if (count($storeIds) > 1) {
                $data['is_multi_store'] = true;
            } else {
                $data['is_multi_store'] = false;
            }

            if ($uid == 0) {
                //未登陆
                $data['is_get'] = false;
                $data['btn_txt'] = '登录领取';
                $isAlert = true;
            } else {
                if (in_array($v['coupon_type'] . '_' . $v['coupon_id'], $getCouponIndex)) {
                    $data['btn_txt'] = '去使用';
                    $data['is_get'] = true;
                    continue;
                } else {
                    $data['btn_txt'] = '领取';
                    $isAlert = true;
                    $data['is_get'] = false;
                }
            }

            if ($v['coupon_type'] == 'mer') {
                if ($v['mer_coupon_url']) {
                    $data['goto'] = $v['mer_coupon_url'];
                } else {
                    $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/storeList?coupon_id=' . $v['coupon_id'];
                }
            } else {
                if($v['plat_cate_name'] == 'village_group') { //跳转 社区团购首页
                    $data['goto'] = C('config.site_url') . '/wap.php?g=Wap&c=Village_group&a=index';
                } else {
                    if ($v['plat_coupon_url']) {
                        $data['goto'] = $v['plat_coupon_url'];
                    } else {
                        if ($v['plat_use_type'] == 1) {
                            $store_ids = M('System_coupon_store')->field('store_id')->where(['coupon_id' => $v['coupon_id']])->select();
                            if (count($store_ids) == 1) { //单店铺
                                $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/homePage?store_id=' . $store_ids[0]['store_id'];
                            } else { //多店铺
                                $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/storeList?coupon_id=' . $v['coupon_id'] . '&coupon_type=system';
                            }
                        } else {
                            $data['goto'] = C('config.site_url') . '/packapp/plat/pages/store/storeList?coupon_id=' . $v['coupon_id'] . '&coupon_type=system';
                        }
                    }
                }
            }
//            for ($i = 0; $i < $canGetNum; $i++) {
            $canGetNum > 0 && $coupon[] = $data;
//            }

        }

        empty($activity['color']) && $activity['color'] = '#FFFFFF';
        empty($activity['background_color']) && $activity['background_color'] = '#E13924';
        empty($activity['background_pic']) && $activity['background_pic'] = C('config.site_url') . '/static/default_background_send_coupon.png';

        $rs = [
            'is_alert'=>$isAlert,
            'activity_id' => $activity['id'],
            'get_way' => $activity['get_way'],
            'title' => $activity['title'],
            'color' => stripos($activity['color'], 'rgb') !== false ? RGBToHex($activity['color']) : $activity['color'],
            'background_pic' => replace_file_domain($activity['background_pic']),
            'background_color' => stripos($activity['background_color'], 'rgb') !== false ? RGBToHex($activity['background_color']) : $activity['background_color'],
            'coupon_lists' => $coupon
        ];
        return $rs;
    }
}
