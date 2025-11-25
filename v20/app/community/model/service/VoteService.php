<?php
/**
 * 投票活动
 */

namespace app\community\model\service;


use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\PluginVote;
use app\community\model\db\PluginVoteBlock;
use app\community\model\db\PluginVoteJoin;
use app\community\model\db\PluginVoteOption;
use app\community\model\db\User;

class VoteService
{
    /**
     * 获取小区投票列表
     */
    public function getVoteList($param)
    {
        $where = [
            ['v.from','=','house'],
            ['v.status','<',4],
        ];
        if($param['village_name']){
            $where[] = ['hv.village_name','like','%'.htmlentities($param['village_name']).'%'];
        }
        if($param['vote_title']){
            $where[] = ['v.vote_title','like','%'.htmlentities($param['vote_title']).'%'];
        }
        if($param['start_time'] && $param['end_time']){//活动开始结束时间必须都在筛选的时间范围内
            $param['start_time'] = strtotime($param['start_time']);
            $param['end_time'] = strtotime($param['end_time'].' 23:59:59');
            $where[] = ['v.start_time','>=',$param['start_time']];
            $where[] = ['v.start_time','<=',$param['end_time']];
            $where[] = ['v.end_time','>=',$param['start_time']];
            $where[] = ['v.end_time','<=',$param['end_time']];
        }
        $order = 'v.vote_id desc';
        if($param['sorter']){
            $order = 'v.'.$param['sorter'];
        }
        $field = 'v.vote_id,hv.village_name,v.vote_title,v.start_time,v.end_time,v.vote_number,v.status';
        $list = (new PluginVote())->getList($where,$field,$order,$param['pageSize']);
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['start_time'] = $v['start_time'] ? date('Y-m-d H:i:s',$v['start_time']) : '';
            $list['data'][$k]['end_time'] = $v['end_time'] ? date('Y-m-d H:i:s',$v['end_time']) : '';
            $list['data'][$k]['status_msg'] = $v['status'] ? (new PluginVote())->getStatusStr($v['status'] ) : '';
        }
        return $list;
    }
    
    /**
     * 查看投票活动详情
     */
    public function getVoteDetail($param)
    {
        $return = [
            'base_data' => [],
            'vote_block' => [],
        ];
        if (!$param['vote_id']) {
            throw_exception('查看对象不能为空！');
        }
        $voteInfo = (new PluginVote())->getOne([
            ['vote_id','=',$param['vote_id']],
            ['status','<',4],
            ['from','=','house'],
            ],'vote_id,vote_title,vote_des,vote_des_style,vote_image,vote_number,from,from_id,limit_type'
        );
        $voteInfo = $voteInfo ? $voteInfo->toArray() : [];
        if(!$voteInfo){
            throw_exception('查看对象不存在或已被删除，请刷新页面！');
        }
        $voteInfo['vote_image'] = $voteInfo['vote_image'] ? unserialize($voteInfo['vote_image']) : '';
        // 存在图片，处理图片路径
        if ($voteInfo['vote_image']) {
            foreach ($voteInfo['vote_image'] as &$val) {
                $val['file_url'] =  replace_file_domain($val['url']);
            }
        }
        $return['base_data'] = $voteInfo;

        $voteBlock = (new PluginVoteBlock())->getList(['vb.vote_id'=>$param['vote_id']],'vb.block_id, vb.vote_id, vb.block_title, vb.block_type, vb.is_write, vb.block_sort, vb.block_number, vb.block_last_time,vo.option_name,vo.option_type,vo.option_img,vo.option_id,vo.option_number,vo.option_id','vb.block_sort ASC, vb.block_id ASC');

        $blockAry = [];
        foreach ($voteBlock as $k=>$v){
            $blockAry[$v['block_id']] = $blockAry[$v['block_id']] ?? [];
            if(!$blockAry[$v['block_id']]){
                $blockAry[$v['block_id']] = [
                    'block_id'=>$v['block_id'],
                    'block_title'=>$v['block_title'],
                    'block_type'=>$v['block_type'],
                    'block_sort'=>$v['block_sort'],
                    'block_number'=>$v['block_number'],
                    'option'=>[]
                ];
            }
            if($v['option_id']){
                $blockAry[$v['block_id']]['option'][] = [
                    'option_id' => $v['option_id'],
                    'option_name' => $v['option_name'],
                    'option_type' => $v['option_type'],
                    'option_img' => $v['option_img'],
                    'option_number' => $v['option_number']
                ];
            }
        }
        $blockAry = array_values($blockAry);
        $return['vote_block'] = $blockAry;
        return $return;
    }

    /**
     * 获取投票结果
     */
    public function getVoteResult($param)
    {
        $return = $this->getVoteDetail($param);
        if(!$return){
            return [];
        }
        $villageId = $return['base_data']['from_id'] ?? 0;

        // 初始化
        // 默认业主总人数
        $ownerTotal = 0;
        // 默认业主总面积
        $ownerHouseSizeTotal = 0;
        
        //获取用户投票详情
        $voteBlockJoinArr = [];
        $joinBindArr = [];
        if($return['base_data']['limit_type'] == 1){
            $voteJoin = (new PluginVoteJoin())->getList(['vote_id'=>$return['base_data']['vote_id']],'block_id,bind_id,option_id');
            foreach ($voteJoin as $val) {
                if (!isset($voteBlockJoinArr[$val['block_id']]) || !$voteBlockJoinArr[$val['block_id']]) {
                    $voteBlockJoinArr[$val['block_id']] = array();
                }
                $voteBlockJoinArr[$val['block_id']][] = $val['bind_id'];

                if ($val['option_id'] && (!isset($joinBindArr[$val['option_id']]) || !$joinBindArr[$val['option_id']])) {
                    $joinBindArr[$val['option_id']] = array();
                }
                if ($val['option_id']) {
                    $joinBindArr[$val['option_id']][] = $val['bind_id'];
                }
            }
            // 获取业主总人数
            // 获取业主总面积
            $bindMsg = (new HouseVillageUserBind())->getOne([['village_id' ,'=', $villageId],['type' ,'in', [0,3]],['status' ,'=', 1]],'COUNT(pigcms_id) as owner_total, SUM(housesize) as owner_house_size_total');
            if ($bindMsg && $bindMsg['owner_total']) {
                $ownerTotal = $bindMsg['owner_total'];
            }
            if ($bindMsg && $bindMsg['owner_house_size_total']) {
                $ownerHouseSizeTotal = $bindMsg['owner_house_size_total'];
            }  
        }
        $return['base_data']['owner_total'] = $ownerTotal;
        $return['base_data']['owner_house_size_total'] = $ownerHouseSizeTotal;
        
        foreach ($return['vote_block'] as $k=>&$v){
            $return['vote_block'][$k]['block_house_size_total'] = 0;//参与本题的业主总面积
            $block_number = intval($v['block_number']);
            if(in_array($v['block_type'],[3,4]) && $v['option']){//单选多选
                //处理面积
                if($return['base_data']['limit_type'] == 1 && $voteBlockJoinArr && isset($voteBlockJoinArr[$v['block_id']]) && $voteBlockJoinArr[$v['block_id']]){
                    $blockWhere = [
                        ['village_id' ,'=', $villageId],
                        ['type' ,'in', [0,3]],
                        ['status' ,'=', 1],
                        ['pigcms_id' ,'in', $voteBlockJoinArr[$v['block_id']]]
                    ];
                    $bindBlockMsg = (new HouseVillageUserBind())->getOne($blockWhere,'SUM(housesize) as block_house_size_total');
                    if ($bindBlockMsg && $bindBlockMsg['block_house_size_total']) {
                        $return['vote_block'][$k]['block_house_size_total'] = getFormatNumber($bindBlockMsg['block_house_size_total'],2);
                    }
                }
                $return['vote_block'][$k]['owner_total'] = $ownerTotal;
                $return['vote_block'][$k]['owner_house_size_total'] = getFormatNumber($ownerHouseSizeTotal,2);

                foreach ($v['option'] as &$vv){
                    // 计算每个选择项占比
                    if($block_number <= 0){
                        $vv['proportion'] = 0;
                    }elseif(intval($vv['option_number'])>0){
                        // 按照百分比算 计算出来是0-100的值
                        $vv['proportion'] = (intval($vv['option_number'])/$block_number) * 100;
                        $vv['proportion'] = floatval(getFormatNumber($vv['proportion'],2));
                    }else{
                        $vv['proportion'] = 0;
                    }
                    
                    if (intval($vv['option_number'])>0 && $ownerTotal>0) {
                        // 计算与总人数比
                        $vv['proportion_total'] = (intval($vv['option_number'])/$ownerTotal) * 100;
                        $vv['proportion_total'] = floatval(getFormatNumber($vv['proportion_total'],2));
                    } else {
                        $vv['proportion_total'] = 0;
                    }

                    if ($return['base_data']['limit_type'] == 1){
                        $vv['option_house_size_total'] = 0;
                        $vv['option_house_size_proportion'] = 0;
                        $vv['option_house_size_proportion_total'] = 0;
                        if($joinBindArr && isset($joinBindArr[$vv['option_id']]) && $joinBindArr[$vv['option_id']]){
                            $optionWhere = [
                                ['village_id' ,'=', $villageId],
                                ['type' ,'in', [0,3]],
                                ['status' ,'=', 1],
                                ['pigcms_id' ,'in', $joinBindArr[$vv['option_id']]]
                            ];
                            $bindOptionMsg = (new HouseVillageUserBind())->getOne($optionWhere,'SUM(housesize) as option_house_size_total');
                            if ($bindOptionMsg && $bindOptionMsg['option_house_size_total']) {
                                $vv['option_house_size_total'] = getFormatNumber($bindOptionMsg['option_house_size_total'],2);
                            }

                            // 该选项专有面积 对比 整个题目 专有面积比
                            if (floatval($vv['option_house_size_total'])>0 && floatval($return['vote_block'][$k]['block_house_size_total']) > 0) {
                                $vv['option_house_size_proportion'] = (floatval($vv['option_house_size_total'])/floatval($return['vote_block'][$k]['block_house_size_total'])) * 100;
                                $vv['option_house_size_proportion'] = floatval(getFormatNumber($vv['option_house_size_proportion'],2));
                            }
                            // 该选项专有面积 对比 整个小区业主 专有面积比
                            if (floatval($vv['option_house_size_total'])>0 && floatval($ownerHouseSizeTotal) > 0) {
                                $vv['option_house_size_proportion_total'] = (floatval($vv['option_house_size_total'])/floatval($ownerHouseSizeTotal)) * 100;
                                $vv['option_house_size_proportion_total'] = floatval(getFormatNumber($vv['option_house_size_proportion_total'],2));
                            }
                        }
                    }
                }
            }elseif($v['block_type'] == 5){
                $blockJoin = (new PluginVoteJoin())->getOne(['block_id'=>$v['block_id']],'SUM(option_num) as option_num_total');
                if ($blockJoin && $blockJoin['option_num_total']>0) {
                    $option_num_total = $blockJoin['option_num_total'];
                    $return['vote_block'][$k]['option_num_total'] = $option_num_total;
                    $return['vote_block'][$k]['average'] = $option_num_total / $block_number;
                    $return['vote_block'][$k]['average'] = floatval(getFormatNumber($return['vote_block'][$k]['average'],2));
                } else {
                    $return['vote_block'][$k]['option_num_total'] = 0;
                    $return['vote_block'][$k]['average'] = 0;
                }
            }
        }
        return $return;
    }
    
    /**
     * 获取投票活动结果详情
     */
    public function getVoteResultOne($param)
    {
        if (!$param['block_id']) {
            throw_exception('查看对象不能为空！');
        }
        $where = ['block_id'=>$param['block_id']];
        $voteBlockInfo = (new PluginVoteBlock())->getOne($where,
            'vote_id,block_type,block_title'
        );
        $voteBlockInfo = $voteBlockInfo ? $voteBlockInfo->toArray() : [];
        if(!$voteBlockInfo){
            throw_exception('查看对象不存在，请刷新页面！');
        }
        $voteInfo = (new PluginVote())->getOne([
            ['vote_id','=',$voteBlockInfo['vote_id']],
            ['status','<',4],
            ['from','=','house'],
        ],'vote_id,limit_type,from_id'
        );
        if(!$voteInfo){
            throw_exception('查看活动不存在或已被删除，请刷新页面！');
        }

        $voteOption = [];
        if(in_array($voteBlockInfo['block_type'],[3,4])){
            $voteOption = (new PluginVoteOption())->getList($where,'*');
        }
        if($voteOption){
            foreach ($voteOption as $kk=>$vv){
                unset($voteOption[$kk]);
                $voteOption[$vv['option_id']] = $vv;
            }
        }
        
        $list = (new PluginVoteJoin())->getList($where,'*','last_time DESC,add_time DESC',$param['pageSize']);
        
        // 默认业主总面积
        $ownerHouseSizeTotal = 0;
        $voteBlockJoinArr = [];//参与本题目的业主
        $voteBlockInfo['block_house_size_total'] = 0;
        if($voteInfo['limit_type'] == 1){
            $whereJoin = array(
                'vote_id' => $voteBlockInfo['vote_id'],
                'block_id' => $param['block_id'],
            );
            $voteJoin = (new PluginVoteJoin())->getList($whereJoin);
            if ($voteJoin) {
                foreach ($voteJoin as $val) {
                    $voteBlockJoinArr[] = $val['bind_id'];
                }
            }
            // 获取业主总人数
            // 获取业主总面积
            $village_id = $voteInfo['from_id'];
            $bindMsg = (new HouseVillageUserBind())->getOne([
                ['village_id','=',$village_id],
                ['type','in',[0,3]],
                ['status','=',1]
            ],'SUM(housesize) as owner_house_size_total');
            if ($bindMsg && $bindMsg['owner_house_size_total']) {
                $ownerHouseSizeTotal = $bindMsg['owner_house_size_total'];
            }
            // 获取区块题目总面积
            $blockWhere = [
               ['village_id' ,'=', $voteInfo['from_id']],
               ['type' ,'in', [0,3]],
               ['status' ,'=', 1],
               ['pigcms_id' ,'in', $voteBlockJoinArr],
            ];
            $bindBlockMsg = (new HouseVillageUserBind())->getOne($blockWhere,'SUM(housesize) as block_house_size_total');
            if ($bindBlockMsg && $bindBlockMsg['block_house_size_total']) {
                $voteBlockInfo['block_house_size_total'] = getFormatNumber($bindBlockMsg['block_house_size_total'],2);
            }
        }
        $voteInfo['owner_house_size_total'] = getFormatNumber($ownerHouseSizeTotal,2);

        // 计算统计总评分
        $optionNumTotal = 0;
        foreach ($list['data'] as $k=>&$v){
            $list['data'][$k]['house_size_proportion'] = 0;
            $v['housesize'] = 0;//业主占地面积
            if (in_array($v['from'],['house','community','street']) && $v['bind_id']>0) {
                $bindInfo = (new HouseVillageUserBind())->getOne(['pigcms_id' => $v['bind_id']]); 
                if ($bindInfo && $bindInfo['housesize']>0) {
                    $housesize = $bindInfo['housesize'];
                    $v['housesize'] = getFormatNumber($housesize,2);
                    // 该选项专有面积 对比 整个题目 专有面积比
                    if (floatval($housesize)>0 && floatval($voteBlockInfo['block_house_size_total']) > 0) {
                        $list['data'][$k]['house_size_proportion'] = (floatval($housesize)/floatval($voteBlockInfo['block_house_size_total'])) * 100;
                        $list['data'][$k]['house_size_proportion'] = getFormatNumber($v['house_size_proportion'],2);
                    }
                    // 该选项专有面积 对比 整个小区业主 专有面积比
                    if (floatval($housesize)>0 && floatval($ownerHouseSizeTotal) > 0) {
                        $list['data'][$k]['house_size_proportion_total'] = (floatval($housesize)/floatval($ownerHouseSizeTotal)) * 100;
                        $list['data'][$k]['house_size_proportion_total'] = getFormatNumber($v['house_size_proportion_total'],2);
                    }
                }
                // 处理投票者的基本信息
                if ($bindInfo) {
                    $v['name'] = $bindInfo['name'];
                    $v['phone'] = $bindInfo['phone'];
                    $v['address'] = (new HouseVillageService())->getSingleFloorRoom($bindInfo['single_id'],$bindInfo['floor_id'],$bindInfo['layer_id'],$bindInfo['pigcms_id'],$bindInfo['village_id']);
                    $house_type = (new HouseVillageUserVacancy())->where(['pigcms_id'=>$bindInfo['vacancy_id']])->value('house_type');
                    switch ($bindInfo['type']){
                        case 0:
                            $v['relative'] = '业主';
                            break;
                        case 1:
                            $v['relative'] = '家属';
                            break;
                        case 2:
                            if($house_type == 1){
                                $v['relative'] = '租客';
                            }else{
                                $v['relative'] = '租客/员工';
                            }
                            break;
                        default:
                            $v['relative'] = '业主';
                    }
                }
            }
            if (empty($v['name']) && $v['uid']>0) {
                $user_info = (new User())->where(array('uid'=>$v['uid']))->field('nickname, phone')->find();
                // 处理投票者的基本信息
                if ($user_info) {
                    $v['name'] = $user_info['nickname'];
                    $v['phone'] = $user_info['phone'];
                }
            }
            // 如果是选项获取选中选项信息
            if ($voteOption && $v['option_id']) {
                $option_id = explode(',', $v['option_id']);
                $choose_info = array();
                if ($option_id) {
                    foreach ($option_id as $val) {
                        if ($voteOption[$val]) {
                            $vote_option_info = $voteOption[$val];
                        } else {
                            $vote_option_info = (new PluginVoteOption())->where(array('option_id' => $val))->find();
                        }
                        if (1==$vote_option_info['option_type']) {
                            $choose_info[] = '【其他】：'. $v['option_txt'];
                        } else {
                            $choose_info[] = $vote_option_info['option_name'];
                        }
                    }
                }
                if ($choose_info) {
                    $v['vote_option_info'] = implode(',', $choose_info);
                }
            } elseif ($v['option_txt']) {
                $v['vote_option_info'] = $v['option_txt'];
            }
            // 如果是评分记录总分数
            if (5==$voteBlockInfo['block_type'] && $v['option_num']>0) {
                $optionNumTotal += $v['option_num'];
            }
            if ($v['last_time']) {
                $v['vote_time'] = date('Y-m-d H:i',$v['last_time']);
            } elseif ($v['add_time']) {
                $v['vote_time'] = date('Y-m-d H:i',$v['add_time']);
            }
        }
        $list['option_num_total'] = $optionNumTotal;
        $list['house_size_total'] =$voteBlockInfo['block_house_size_total'];
        $list['vote_info'] = $voteInfo;
        $list['vote_block'] = $voteBlockInfo;
        return $list;
    }
}