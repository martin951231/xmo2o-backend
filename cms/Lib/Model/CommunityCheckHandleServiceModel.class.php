<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      小区审核处理（目前用于定制）
 */

class CommunityCheckHandleServiceModel extends Model
{
    /**
     * 小区广告检查是否需要审核盒子.
     */
    public function tryCheckHouseAdvertBox($params) {
        try{
            return $this->checkHouseAdvert($params);
        }catch (\Exception $e){
            fdump_api(['err' => '异常捕捉盒子报错-小区广告检查是否需要审核', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryCheckHouseAdvertBoxModelLog', 1);
            return false;
        }
    }

    /**
     * 小区广告检查是否需要审核.
     */
    public function checkHouseAdvert($params) {
        $advert_id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        if (!$advert_id) {
            return false;
        }
        $admin_id = isset($params['admin_id']) ? intval($params['admin_id']) : 0;
        $xtype = isset($params['xtype']) ? trim($params['xtype']) : 'house_advert_check';
        $db_house_village_checkauth_set = M('House_village_checkauth_set');
        $where = [];
        $where['xtype'] = $xtype;
        $where['village_id'] = $village_id;
        $checkAuthSet = $db_house_village_checkauth_set->where($where)->find();
        if (isset($checkAuthSet['set_datas']) && $checkAuthSet['set_datas']) {
            $checkAuthSet['set_datas'] = json_decode($checkAuthSet['set_datas'], 1);
            $checkLevel = array();
            if (!empty($checkAuthSet['set_datas'])) {
                foreach ($checkAuthSet['set_datas'] as $vv) {
                    $checkLevel[$vv['level_v']] = $vv;
                }
                ksort($checkLevel);
            }
            $checkAuthSet['check_level'] = $checkLevel;
        }
        if (isset($checkAuthSet['is_open']) && intval($checkAuthSet['is_open']) > 0 && isset($checkAuthSet['check_level']) && !empty($checkAuthSet['check_level'])) {
            $is_check = 1;
        } else {
            $is_check = 0;
        }
        fdump_api($checkAuthSet, '$checkAuthSet');
        fdump_api($is_check, '$is_check');
        if ($is_check == 0) {
            return true;
        }
        $nowTime = time();
        $apply_reason = isset($params['apply_reason']) ? trim($params['apply_reason']) : '广告需要进行审核';
        $name = isset($params['name']) ? trim($params['name']) : '';
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        $orderRefundCheckArr = array('property_id' => $property_id, 'village_id' => $village_id);
        $orderRefundCheckArr['xtype'] = 'house_advert';
        $orderRefundCheckArr['order_id'] = $advert_id;
        $orderRefundCheckArr['other_relation_id'] = -1;
        $orderRefundCheckArr['money'] = 0;
        $orderRefundCheckArr['status'] = 0;  //0未审核 1审核中 2审核通过
        $orderRefundCheckArr['apply_login_role'] = 0;
        $orderRefundCheckArr['apply_name'] = $name;
        $orderRefundCheckArr['apply_phone'] = $phone;
        $orderRefundCheckArr['apply_uid'] = $admin_id;
        $orderRefundCheckArr['apply_reason'] = $apply_reason;
        $extra_data = array(
            'order_id' => $advert_id, 'village_id' => $village_id, 'advert_id' => $advert_id,
            'apply_time' => $nowTime, 'apply_reason' => $apply_reason,
            'title' => '广告申请审核', 'admin_id' => $admin_id
        );
        $orderRefundCheckArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
        $extra['checkauth_set'] = $checkAuthSet;
        $admin_id && $extra['wid'] = $admin_id;
        $extra['from_user'] = $from_user;
        $apply_id = $this->addApply($orderRefundCheckArr, $extra);
        return $apply_id;
    }

    /**
     * 添加审核
     */
    public function addApply($orderRefundCheckArr = array(), $extra = array())
    {
        if (empty($orderRefundCheckArr)) {
            return false;
        }
        $nowTime = time();
        fdump_api(['msg' => '添加审核申请入参','parameter' => func_get_args(), 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
        $village_id = $orderRefundCheckArr['village_id'];
        $property_id = $orderRefundCheckArr['property_id'];
        $advert_id = $orderRefundCheckArr['order_id'];
        $dbVillageCheckauthApply = M('House_village_checkauth_apply');
        $orderRefundCheckArr['add_time'] = time();
        $xtypeSet = 'house_advert_check';
        $database_adver = D('House_adver');
        $db_house_community_check_about = M('House_community_check_about');
        if ($orderRefundCheckArr['xtype'] == 'house_advert') {
            // 工单转单至其他小区
            $xtypeSet = 'house_advert_check';
            $where_advert = [];
            $where_advert['id'] = $advert_id;
            $advert_info = $database_adver->where($where_advert)->find();
            if (empty($advert_info)) {
                fdump_api(['msg' => '无对应广告拦截','advert_info' => $advert_info, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                return false;
            } elseif (!in_array($advert_info['status'], [1, 3])) {
                fdump_api(['msg' => '状态异常','advert_info' => $advert_info, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                return false;
            }
            $whereCheckAbout = [];
            $whereCheckAbout['check_type'] = 'house_adver';
            $whereCheckAbout['check_id'] = $advert_id;
            $checkInfo = $db_house_community_check_about->where($whereCheckAbout)->order('id DESC')->find();
            fdump_api(['msg' => '审核记录', 'checkInfo' => $checkInfo, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
            if (isset($checkInfo['check_status']) && !in_array($checkInfo['check_status'], [0, 2, 3])) {
                // 有审核记录 为 无审核+审核通过+审核失败 外的存在待审核 不允许执行
                return false;
            }
        }
        $orderRefundCheckArr['add_time'] = $nowTime;
        $apply_id = $dbVillageCheckauthApply->add($orderRefundCheckArr);
        fdump_api(['msg' => '添加审核记录', 'apply_id' => $apply_id, 'orderRefundCheckArr' => $orderRefundCheckArr, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
        if ($apply_id > 0) {
            $from_user = isset($extra['from_user']) ? $extra['from_user'] : '';
            if ($from_user && is_array($from_user)) {
                $from_user = json_encode($from_user,JSON_UNESCAPED_UNICODE);
            }
            $db_house_worker = M('House_worker');
            $db_house_village_checkauth_detail = M('House_village_checkauth_detail');
            switch ($orderRefundCheckArr['xtype']) {
                case 'house_advert':
                    $xtypeSet = 'house_advert_check';
                    break;
            }
            //添加一条审核记录
            if (isset($extra['checkauth_set'])) {
                $checkauthSet = $extra['checkauth_set'];
            } else {
                $db_house_village_checkauth_set = M('House_village_checkauth_set');
                $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => $xtypeSet);
                $checkauthSet = $db_house_village_checkauth_set->where($orderRefundCheckWhere)->find();
                if (isset($checkauthSet['set_datas']) && $checkauthSet['set_datas']) {
                    $checkauthSet['set_datas'] = json_decode($checkauthSet['set_datas'], 1);
                    $checkLevel = array();
                    if (!empty($checkauthSet['set_datas'])) {
                        foreach ($checkauthSet['set_datas'] as $vv) {
                            $checkLevel[$vv['level_v']] = $vv;
                        }
                        ksort($checkLevel);
                    }
                    $checkauthSet['check_level'] = $checkLevel;
                }
            }
            fdump_api(['msg' => '审核配置', 'checkauthSet' => $checkauthSet, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
            if (!empty($checkauthSet['check_level'])) {
                $level = -1;
                if (isset($extra['wid']) && ($extra['wid'] > 0)) {
                    foreach ($checkauthSet['check_level'] as $vv) {
                        if ($vv['level_wid'] == $extra['wid']) {
                            $level = $vv['level_v'];
                        }
                    }
                }
                $level_tmp = $level + 1;
                fdump_api(['msg' => '判断是否进行跳过', 'level_tmp' => $level_tmp, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                if ($level_tmp == 0) {
                    //取第一条数据，没有自动过审数据
                    $level_wid = $checkauthSet['check_level']['0']['level_wid'];
                    $whereArr = array('wid' => $level_wid, 'village_id' => $village_id);
                    $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                    $worker = $db_house_worker->where($whereArr)->field($field)->find();
                    $detailArr = array('apply_id' => $apply_id, 'property_id' => $property_id);
                    $detailArr['order_id'] = $advert_id;
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $level_wid;
                    $detailArr['level'] = 0;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                    $detailArr['add_time'] = $nowTime;
                    $detail_id = $db_house_village_checkauth_detail->add($detailArr);
                    fdump_api(['msg' => '没有自动过审数据', 'detailArr' => $detailArr, 'detail_id' => $detail_id, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
              
                    if ($orderRefundCheckArr['xtype'] == 'house_advert') {
                        $orderUpdateArr = array('status' => 2);
                        $database_adver->where(['id' => $advert_id])->save($orderUpdateArr);
                        $aboutData = [
                            'check_type' => 'house_adver', 'check_id' => $advert_id, 'apply_type' => 'house_village_checkauth_apply',
                            'apply_id' => $apply_id, 'apply_time' => $nowTime, 'check_status' => 1, 'from_user' => $from_user, 'add_time' => time(),
                        ];
                        $db_house_community_check_about->add($aboutData);
                        fdump_api(['msg' => '变更状态', 'advert_id' => $advert_id, 'orderUpdateArr' => $orderUpdateArr,  'aboutData' => $aboutData, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                    }
                    
                    if (!empty($worker) && !empty($worker['openid'])) {
                        $xtypeStr = '广告申请审核';
                        if ($orderRefundCheckArr['xtype'] == 'house_advert') {
                            $xtypeStr = '广告申请审核';
                        }
                        $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                        $apply_name = $orderRefundCheckArr['apply_name'];
                        $address = '无';
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        fdump_api(['msg' => '模板消息推送', 'tempKey' => $datamsg['tempKey'], 'dataArr' => $datamsg['dataArr'], 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                        D('Tempmsg')->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                }
                else {
                    //有自动过审数据
                    $auto_level = $level_tmp - 1;
                    fdump_api(['msg' => '有自动过审数据', 'level_tmp' => $level_tmp, 'auto_level' => $auto_level, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                    foreach ($checkauthSet['check_level'] as $cvv) {
                        if ($cvv['level_v'] <= $auto_level) {
                            $whereArr = array('wid' => $cvv['level_wid'], 'village_id' => $village_id);
                            $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                            $worker = $db_house_worker->where($whereArr)->field($field)->find();
                            $detailArr = array('apply_id' => $apply_id, 'property_id' => $property_id);
                            $detailArr['order_id'] = $advert_id;
                            $detailArr['village_id'] = $village_id;
                            $detailArr['wid'] = $cvv['level_wid'];
                            $detailArr['level'] = $cvv['level_v'];
                            $detailArr['status'] = 1;
                            $detailArr['apply_time'] = $nowTime;
                            $detailArr['bak'] = '自动过审';
                            $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                            $detailArr['add_time'] = $nowTime;
                            $detail_id = $db_house_village_checkauth_detail->add($detailArr);
                            fdump_api(['msg' => '自动过审', 'detailArr' => $detailArr, 'detail_id' => $detail_id, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                        }
                    }
                    //需要审核的 处理
                    if (isset($checkauthSet['check_level'][$level_tmp])) {
                        $level_wid = $checkauthSet['check_level'][$level_tmp]['level_wid'];
                        $whereArr = array('wid' => $level_wid, 'village_id' => $village_id);
                        $field = 'wid,village_id,phone,name,openid,status,type,property_id,people_type,department_id,account';
                        $worker = $db_house_worker->where($whereArr)->field($field)->find();
                        $detailArr = array('apply_id' => $apply_id, 'property_id' => $property_id);
                        $detailArr['order_id'] = $advert_id;
                        $detailArr['village_id'] = $village_id;
                        $detailArr['wid'] = $level_wid;
                        $detailArr['level'] = $level_tmp;
                        $detailArr['status'] = 0;
                        $detailArr['extra_data'] = !empty($worker) ? json_encode($worker, JSON_UNESCAPED_UNICODE) : '';
                        $detailArr['add_time'] = $nowTime;
                        $detail_id = $db_house_village_checkauth_detail->add($detailArr);
                        fdump_api(['msg' => '需要审核的的', 'detailArr' => $detailArr, 'detail_id' => $detail_id, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                        
                        if ($orderRefundCheckArr['xtype'] == 'house_advert') {
                            $orderUpdateArr = array('status' => 2);
                            $database_adver->where(['id' => $advert_id])->save($orderUpdateArr);
                            $aboutData = [
                                'check_type' => 'house_adver', 'check_id' => $advert_id, 'apply_type' => 'house_village_checkauth_apply',
                                'apply_id' => $apply_id, 'apply_time' => $nowTime, 'check_status' => 1, 'from_user' => $from_user, 'add_time' => time(),
                            ];
                            $db_house_community_check_about->add($aboutData);
                            fdump_api(['msg' => '变更状态', 'advert_id' => $advert_id, 'orderUpdateArr' => $orderUpdateArr,  'aboutData' => $aboutData, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                        }
                        if (!empty($worker) && !empty($worker['openid'])) {
                            $xtypeStr = '广告申请审核';
                            if ($orderRefundCheckArr['xtype'] == 'house_advert') {
                                $xtypeStr = '广告申请审核';
                            }
                            $href = cfg('site_url') . '/packapp/community/pages/Community/reviewRefund/myApproval';
                            $apply_name = $orderRefundCheckArr['apply_name'];
                            $address = '无';
                            $datamsg = [
                                'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                                'dataArr' => [
                                    'href' => $href,
                                    'wecha_id' => $worker['openid'],
                                    'first' => '审核提醒',
                                    'keyword1' => $xtypeStr,
                                    'keyword2' => $apply_name,
                                    'keyword3' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),
                                    'remark' => '您有一个新的申请消息需要审核！',
                                    'new_info' => [//新版本发送需要的信息
                                        'tempKey' => '43258',//新模板号
                                        'thing2' => $xtypeStr,//流程名称
                                        'thing9' => $apply_name,//发起人
                                        'time10' => date('Y-m-d H:i:s', $orderRefundCheckArr['add_time']),//发起时间
                                        'thing7' => $address,//房源名称
                                    ],
                                ]
                            ];
                            fdump_api(['msg' => '模板消息推送', 'tempKey' => $datamsg['tempKey'], 'dataArr' => $datamsg['dataArr'], 'line' => __LINE__], 'CommunityCheckHandleServiceModel/addApplyLog', 1);
                            D('Tempmsg')->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                        }
                    } else {
                        //全部自动过审了
                        $updateArr = array('status' => 2, 'apply_time' => time());
                        $dbVillageCheckauthApply->where(['id' => $apply_id])->save($updateArr);
                        if ($orderRefundCheckArr['xtype'] == 'house_advert') {
                            $orderUpdateArr = array('status' => 1);
                            $database_adver->where(['id' => $advert_id])->save($orderUpdateArr);
                            $aboutData = [
                                'check_type' => 'house_adver', 'check_id' => $advert_id, 'apply_type' => 'house_village_checkauth_apply',
                                'apply_id' => $apply_id, 'apply_time' => $nowTime, 'check_status' => 2, 'check_reason' => '全部自动过审', 'from_user' => $from_user, 'add_time' => time(),
                            ];
                            $db_house_community_check_about->add($aboutData);
                        }
                    }
                }
            }
        }
        return $apply_id;
    }
    
    /**
     * 小区广告审核盒子.
     */
    public function tryHouseAdvertCheckBox($params) {
        try{
            return $this->houseAdvertCheck($params);
        }catch (\Exception $e){
            fdump_api(['err' => '异常捕捉盒子报错-小区广告审核操作', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryHouseAdvertCheckBoxModelLog', 1);
            return false;
        }
    }
    
    /**
     * 小区广告审核.
     */
    public function houseAdvertCheck($params)
    {
        fdump_api(['msg' => '添加审核申请入参','parameter' => func_get_args(), 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
        $advert_id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $check_status = isset($params['check_status']) ? intval($params['check_status']) : 0;
        $check_reason = isset($params['check_reason']) ? trim($params['check_reason']) : '';
        $from_user = isset($params['from_user']) ? $params['from_user'] : '';
        $admin_id = isset($params['admin_id']) ? intval($params['admin_id']) : 0;
        if (!$advert_id || !$admin_id) {
            fdump_api(['msg' => '广告id缺少|操作管理员id缺少', 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
            return false;
        }
        if (!$admin_id) {
            return true;
        }
        $xtypeSet = isset($params['xtype']) ? trim($params['xtype']) : 'house_advert_check';
        $db_house_village_checkauth_set = M('House_village_checkauth_set');
        $where = [];
        $where['xtype'] = $xtypeSet;
        $where['village_id'] = $village_id;
        $checkAuthSet = $db_house_village_checkauth_set->where($where)->find();
        if (isset($checkAuthSet['set_datas']) && $checkAuthSet['set_datas']) {
            $checkAuthSet['set_datas'] = json_decode($checkAuthSet['set_datas'], 1);
            $checkLevel = array();
            if (!empty($checkAuthSet['set_datas'])) {
                foreach ($checkAuthSet['set_datas'] as $vv) {
                    $checkLevel[$vv['level_v']] = $vv;
                }
                ksort($checkLevel);
            }
            $checkAuthSet['check_level'] = $checkLevel;
        }
        fdump_api(['msg' => '审核设置信息', 'checkAuthSet' => $checkAuthSet, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
        $dbHouseVillageCheckauthDetail = M('House_village_checkauth_detail');
        $dbVillageCheckauthApply = M('House_village_checkauth_apply');
        $db_house_adver = M('House_adver');
        $whereCheckAuthDetail = ['order_id' => $advert_id, 'property_id' => 0, 'village_id' => 0, 'wid' => $admin_id, 'status' => 0];
        $detail = $dbHouseVillageCheckauthDetail->where($whereCheckAuthDetail)->find();
        if (empty($detail)) {
            fdump_api(['msg' => '审核信息不存在', 'checkAwhereCheckAuthDetailuthSet' => $whereCheckAuthDetail, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
            return ['err' => 1, 'msg' => "审核信息不存在！"];
        }
        switch ($xtypeSet) {
            case 'house_advert_check':
                $xtype = 'house_advert';
                break;
        }
        $applyId = isset($detail['apply_id']) ? intval($detail['apply_id']) : 0;
        $tmpApply = $dbVillageCheckauthApply->where(['id' => $applyId, 'xtype' => $xtype])->find();
        if (empty($tmpApply)) {
            fdump_api(['msg' => '审核信息不存在', 'applyId' => $applyId, 'xtype' => $xtype, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
            return ['err' => 1, 'msg' => "审核信息不存在！"];
        }
        fdump_api(['msg' => '审核信息', 'detail' => $detail, 'tmpApply' => $tmpApply, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
        $nowTime = time();
        $status = $check_status == 2 ? 1 : 2;
        $bak = $check_reason;
        $verifyData = array('bak' => $bak, 'status' => $status);
        $verifyData['apply_time'] = $nowTime;

        $current_level = $detail['level'];

        fdump_api(['msg' => '更新审核信息', 'verifyData' => $verifyData, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
        $ret = $dbHouseVillageCheckauthDetail->where(array('id' => $detail['id'], 'apply_id' => $tmpApply['id']))->save($verifyData);
        if ($ret) {
            $db_house_community_check_about = M('House_community_check_about');
            if ($verifyData['status'] == 2) {
                // 审核拒绝
                $updateArr = array('status' => 3, 'apply_time' => $nowTime);
                fdump_api(['msg' => '审核拒绝', 'updateArr' => $updateArr,'id' => $tmpApply['id'], 'xtype' => $xtype, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                $dbVillageCheckauthApply->where(array('id' => $tmpApply['id']))->save($updateArr);
                if ($xtype == 'house_advert') {
                    $orderUpdateArr = array('status' => 3);
                    $db_house_adver->where(['id' => $advert_id])->save($orderUpdateArr);
                    // 	审核状态 0-无审核 1-待审核 2-审核成功 3-审核失败 4-删除了
                    $whereAbout = [
                        'check_type' => 'house_adver', 'check_id' => $advert_id, 'apply_type' => 'house_village_checkauth_apply',
                        'apply_id' => $applyId, 'check_status' => ['in', [0, 1]]
                    ];
                    $info = $db_house_community_check_about->where($whereAbout)->field('id,from_user')->find();
                    fdump_api(['msg' => '审核记录信息', 'advert_id' => $advert_id, 'orderUpdateArr' => $orderUpdateArr,'whereAbout' => $whereAbout,'info' => $info, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                    if (!empty($from_user)) {
                        if ($from_user && !is_array($from_user)) {
                            $from_user = json_decode($from_user, 1);
                        }
                        $info_from_user = isset($info['from_user']) && $info['from_user'] ? json_decode($info['from_user'], 1) : [];
                        $info_from_user['check'] = $from_user;
                    } else {
                        $info_from_user = [];
                    }
                    $updateData = [
                        'check_time' => $nowTime, 'check_status' => 3, 'check_reason' => $check_reason, 'from_user' => $from_user, 'update_time' => time(),
                    ];
                    if (!empty($info_from_user)) {
                        $updateData['from_user'] = json_encode($info_from_user, JSON_UNESCAPED_UNICODE);
                    } elseif (!empty($from_user)) {
                        if ($from_user && is_array($from_user)) {
                            $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
                        }
                        $from_user && $updateData['from_user'] = $from_user;
                    }
                    fdump_api(['msg' => '更新记录', 'advert_id' => $advert_id, 'updateData' => $updateData, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                    if (isset($info['id']) && intval($info['id']) > 0) {
                        $db_house_community_check_about->where(['id' => $info['id']])->add($updateData);
                    }
                }
                return array('check_status' => 4, 'check_apply_id' => $applyId, 'check_user_id' => $admin_id);
            }

            $dbAdmin = M('Admin');
            $adminField = 'id, account, realname, phone, login_count, status, level, openid, nickname';
            if ($verifyData['status'] == 1) {
                // 审核通过
                $next_level = $current_level + 1;
                if (isset($checkAuthSet['check_level'][$next_level])) {
                    //有下一等级的
                    $next_level_wid = $checkAuthSet['check_level'][$next_level]['level_wid'];
                    if ($xtype == 'house_advert') {
                        $whereArr = array('id' => $next_level_wid);
                        $adminInfo = $dbAdmin->where($whereArr)->field($adminField)->find();
                    } else {
                        $adminInfo = [];
                    }
                    fdump_api(['msg' => '审核通过且有下一级的', 'next_level' => $next_level, 'check_level' => $checkAuthSet['check_level'], 'adminInfo' => $adminInfo, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                    $detailArr = array('apply_id' => $tmpApply['id'], 'property_id' => $tmpApply['property_id']);
                    $detailArr['order_id'] = $tmpApply['order_id'];
                    $detailArr['village_id'] = $village_id;
                    $detailArr['wid'] = $next_level_wid;
                    $detailArr['level'] = $next_level;
                    $detailArr['status'] = 0;
                    $detailArr['extra_data'] = !empty($adminInfo) ? json_encode($adminInfo, JSON_UNESCAPED_UNICODE) : '';
                    $dbHouseVillageCheckauthDetail->add($detailArr);
                    fdump_api(['msg' => '记录下一级待审核信息', 'detailArr' => $detailArr, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                    if (!empty($adminInfo) && !empty($adminInfo['openid'])) {
                        $xtypeStr = '广告申请审核';
                        $href = '';
                        $apply_name = $tmpApply['apply_name'];
                        $address = '无';
                        $datamsg = [
                            'tempKey' => 'OPENTM417740354',//todo 类目模板OPENTM417740354
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $adminInfo['openid'],
                                'first' => '审核提醒',
                                'keyword1' => $xtypeStr,
                                'keyword2' => $apply_name,
                                'keyword3' => date('Y-m-d H:i:s', $tmpApply['add_time']),
                                'remark' => '您有一个新的申请消息需要审核！',
                                'new_info' => [//新版本发送需要的信息
                                    'tempKey' => '43258',//新模板号
                                    'thing2' => $xtypeStr,//流程名称
                                    'thing9' => $apply_name,//发起人
                                    'time10' => date('Y-m-d H:i:s', $tmpApply['add_time']),//发起时间
                                    'thing7' => $address,//房源名称
                                ],
                            ]
                        ];
                        fdump_api(['msg' => '模板消息推送', 'tempKey' => $datamsg['tempKey'], 'dataArr' => $datamsg['dataArr'], 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                        D('Tempmsg')->sendTempMsg($datamsg['tempKey'], $datamsg['dataArr']);
                    }
                    $updateArr = array('status' => 1, 'apply_time' => time());
                    $dbVillageCheckauthApply->where(['id' => $tmpApply['id']])->save($updateArr);
                    fdump_api(['msg' => '更新申请为审核中', 'updateArr' => $updateArr, 'id' => $tmpApply['id'], 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                    return array('check_status' => 2, 'check_apply_id' => $applyId, 'check_user_id' => $admin_id);
                } else {
                    // 没有下一个审核的 就是完全审核通过了
                    //全部自动过审了
                    $updateArr = array('status' => 2, 'apply_time' => time());
                    fdump_api(['msg' => '没有下一个审核的就是完全审核通过了', 'updateArr' => $updateArr, 'id' => $tmpApply['id'], 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                    $dbVillageCheckauthApply->where(['id' => $tmpApply['id']])->save($updateArr);
                    if ($xtype == 'house_advert') {
                        $orderUpdateArr = array('status' => 1);
                        $db_house_adver->where(['id' => $advert_id])->save($orderUpdateArr);
                        // 	审核状态 0-无审核 1-待审核 2-审核成功 3-审核失败 4-删除了
                        $whereAbout = [
                            'check_type' => 'house_adver', 'check_id' => $advert_id, 'apply_type' => 'house_village_checkauth_apply',
                            'apply_id' => $applyId, 'check_status' => ['in', [0, 1]]
                        ];
                        $info = $db_house_community_check_about->where($whereAbout)->field('id,from_user')->find();
                        fdump_api(['msg' => '审核记录', 'whereAbout' => $whereAbout, 'info' => $info, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                        if (!empty($from_user)) {
                            if ($from_user && !is_array($from_user)) {
                                $from_user = json_decode($from_user, 1);
                            }
                            $info_from_user = isset($info['from_user']) && $info['from_user'] ? json_decode($info['from_user'], 1) : [];
                            $info_from_user['check'] = $from_user;
                        } else {
                            $info_from_user = [];
                        }
                        $updateData = [
                            'check_time' => $nowTime, 'check_status' => 2, 'check_reason' => $check_reason, 'from_user' => $from_user, 'update_time' => time(),
                        ];
                        if (!empty($info_from_user)) {
                            $updateData['from_user'] = json_encode($info_from_user, JSON_UNESCAPED_UNICODE);
                        } elseif (!empty($from_user)) {
                            if ($from_user && is_array($from_user)) {
                                $from_user = json_encode($from_user, JSON_UNESCAPED_UNICODE);
                            }
                            $from_user && $updateData['from_user'] = $from_user;
                        }
                        fdump_api(['msg' => '更新审核记录', 'updateData' => $updateData, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/houseAdvertCheckLog', 1);
                        if (isset($info['id']) && intval($info['id']) > 0) {
                            $db_house_community_check_about->where(['id' => $info['id']])->add($updateData);
                        }
                    }
                    return array('check_status' => 3, 'check_apply_id' => $applyId, 'check_user_id' => $admin_id);
                }
            }
        } else {
            return ['err' => 1, 'msg' => "审核操作失败！"];
        }
    }

    /**
     * 获取审核日志盒子.
     */
    public function tryGetCheckLogBox($params) {
        try{
            return $this->getCheckLog($params);
        }catch (\Exception $e){
            fdump_api(['err' => '异常捕捉盒子报错-获取审核日志', 'params' => $params, 'line' => $e->getLine(), 'file' => $e->getLine(), 'code' => $e->getCode(), 'message' => $e->getMessage()], 'jzOpen/tryGetCheckLogBoxLog', 1);
            return false;
        }
    }
    
    /**
     * 获取审核日志.
     */
    public function getCheckLog($params) {
        $advert_id = isset($params['id']) ? intval($params['id']) : 0;
        $property_id = isset($params['property_id']) ? intval($params['property_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $xtypeSet = isset($params['xtype']) ? trim($params['xtype']) : 'house_advert_check';
        if (!$advert_id) {
            fdump_api(['msg' => '广告id缺少', 'line' => __LINE__], 'CommunityCheckHandleServiceModel/getCheckLog', 1);
            return [];
        }
        $dbHouseVillageCheckauthDetail = M('House_village_checkauth_detail');
        $dbVillageCheckauthApply = M('House_village_checkauth_apply');
        switch ($xtypeSet) {
            case 'house_advert_check':
                $xtype = 'house_advert';
                break;
        }
        $tmpApply = $dbVillageCheckauthApply->where(['order_id' => $advert_id, 'xtype' => $xtype])->find();
        if (empty($tmpApply)) {
            fdump_api(['msg' => '审核信息不存在', 'advert_id' => $advert_id, 'xtype' => $xtype, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/getCheckLog', 1);
            return [];
        }
        $detailList = $dbHouseVillageCheckauthDetail->where(['apply_id' => $tmpApply['id']])->order('id ASC')->select();
        if (empty($detailList)) {
            fdump_api(['msg' => '审核信息不存在', 'tmpApply' => $tmpApply, 'xtype' => $xtype, 'line' => __LINE__], 'CommunityCheckHandleServiceModel/getCheckLog', 1);
            return [];
        }
        $adminIdArr = array_unique(array_column($detailList,'wid'));
        $dbAdmin = M('Admin');
        $adminField = 'id, account, realname, phone, login_count, status, level, openid, nickname, avatar';
        $adminList = $dbAdmin->where(['id' => ['in', $adminIdArr]])->field($adminField)->select();
        $adminArr = [];
        foreach ($adminList as $admin) {
            $label = isset($admin['realname']) ? trim($admin['realname']) : '';
            !$label && $label = "账号:{$admin['account']}";
            $admin['label'] = $label;
            $adminArr[$admin['id']] = $admin;
        }
        $checkLog = [];
        // 0未审核1审核通过2未通过
        $statusArr = [0 => '未审核', 1 => '审核通过', 2 => '审核拒绝'];
        $statusColorArr = [0 => '#26dbf3', 1 => 'green', 2 => 'red'];
        foreach ($detailList as $key => $detail) {
            $apply_time = isset($detail['apply_time']) && $detail['apply_time'] ? date('Y-m-d H:i:s', $detail['apply_time']) : '';
            $wid = isset($detail['wid']) ? intval($detail['wid']) : 0;
            $adminInfo = isset($adminArr[$wid]) ? $adminArr[$wid] : [];
            $status = intval($detail['status']);
            $statusText = isset($statusArr[$status]) ? $statusArr[$status] : '';
            $statusColor = isset($statusColorArr[$status]) ? $statusColorArr[$status] : '';
            $num = $key + 1;
            $checkLog[] = [
                'title' => "第{$num}位审批人",
                'apply_id' => $detail['apply_id'],
                'apply_time' => $apply_time,
                'bak' => $detail['bak'],
                'avatar' => isset($adminInfo['avatar']) ? trim($adminInfo['avatar']) : '',
                'status' => $status,
                'statusText' => $statusText,
                'statusColor' => $statusColor,
                'name' => isset($adminInfo['label']) ? trim($adminInfo['label']) : '',
            ];
        }
        return $checkLog;
    }
}