<?php
/**
 * 在线报修相关业务
 * @author weili
 * @datetime 2020/07/14
**/

namespace app\community\model\service;

use app\community\model\db\HouseVillageRepairList;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseVillageRepairFollow;
use app\community\model\db\HouseVillageRepairCate;
use app\community\model\service\HouseVillageRepairLogService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageSingleService;
class HouseVillageRepairListService
{
    /**
     * 获取在线报修相关数据
     * @param array $where
     * @param integer $page $limit
     * @param int $limit
     * @param string $order
     * @param string $field $order
     * @return array
     * @author weili
     * @datetime 2020/07/14 11:02
     */
    public function getListSelect($where,$page=0,$limit=10,$order='r.pigcms_id desc',$field=true)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $dbHouseWorker = new HouseWorker();
        $dbHouseVillageRepairCate = new HouseVillageRepairCate();
        $serviceHouseVillage = new HouseVillageService();
        $data = $dbHouseVillageRepairList->getListLimit($where,$field,$page,$limit,$order);
        $dataArr = [];
        foreach ($data as $key=>&$val)
        {
            if(empty($val['user_name']))
            {
                $val['user_name'] = $val['name'];
                unset($val['name']);
            }else{
                unset($val['name']);
            }
            if(empty($val['phone']))
            {
                $val['phone'] = $val['uphone'];
                unset($val['uphone']);
            }else{
                unset($val['uphone']);
            }
            $val['time'] = date('Y-m-d H:i:s',$val['time']);
            if($val['reply_time'])
            {
                $val['reply_time'] = date('Y-m-d H:i:s',$val['reply_time']);
            }else{
                $val['reply_time'] = '暂无';
            }
            //获取分类
            if($val['cate_id']){//二级分类的情况
                $whereCate['a.id'] = $val['cate_id'];
                $field = 'a.cate_name,b.cate_name as cate_f_name';
                $dataCate = $dbHouseVillageRepairCate->getRelevance($whereCate,$field);
                if($dataCate){
                    $val['cate_name'] = $dataCate['cate_f_name'].'-'.$dataCate['cate_name'];
                }else{
                    $val['cate_name'] = '暂无';
                }
            }elseif ($val['cate_fid']){//只有一级分类的情况
                $whereCateF['id'] = $val['cate_fid'];
                $dataCate = $dbHouseVillageRepairCate->getFind($whereCateF,'cate_name');
                $val['cate_name'] = $dataCate['cate_name'];
            }else{
                $val['cate_name'] = '暂无';
            }
            //已解决报修，获取相关的接单跟踪信息
            if($val['status']>=2)
            {
                $map[] = ['wid','=',$val['wid']];
                $workerField = 'name,phone,type';
                $workerInfo = $dbHouseWorker->getOne($map,$workerField);
                $val['w_name'] = $workerInfo['name'];
                $val['w_phone'] = $workerInfo['phone'];
                $val['w_type'] = $workerInfo['type'];
            }
            //根据楼栋id 单元id 楼层id 门牌号id 社区id 获取地址
            $single_id = $val['single_id'] ? $val['single_id'] : $val['u_single_id'];
            $floor_id = $val['floor_id'] ? $val['floor_id'] : $val['u_floor_id'];
            $layer_id = $val['layer_id'] ? $val['layer_id'] : $val['u_layer_id'];
            $vacancy_id = $val['vacancy_id'] ? $val['vacancy_id'] : $val['u_vacancy_id'];
            $val['address'] = $serviceHouseVillage->getSingleFloorRoom($single_id, $floor_id, $layer_id, $vacancy_id,$val['village_id']);
//            //根据楼栋id 单元id 楼层id 门牌号id 社区id 获取地址
//            $val['address'] = $serviceHouseVillage->getSingleFloorRoom($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
            //工单 状态
            $statusData = $this->getStatus($val['status'],$val['type']);
            $dataArr[$key]['pigcms_id'] = $val['pigcms_id'];
            $dataArr[$key]['time'] = $val['time'];
            $dataArr[$key]['status_msg'] = $statusData['status_msg'];
            $dataArr[$key]['status'] = $val['status'];
            $dataArr[$key]['status_color'] = $statusData['status_color'];
            $dataArr[$key]['list']=[
                ['title'=>'报修人员','type'=>1,
                'content'=>$val['user_name']],
                ['title'=>'报修内容','type'=>1,
                    'content'=>$val['content']],
                ['title'=>'报修地址','type'=>1,
                    'content'=>$val['address']],
            ];
            if($val['status']>=2) {
                $dataArr[$key]['list'][] = [
                    'title' => '处理人员', 'type' => 1,
                    'content' => $val['w_name'] . ' ' . $val['w_phone'],
                ];
            }
            if($val['status']>=3) {
                $dataArr[$key]['list'][] = [
                    'title' => '处理意见', 'type' => 1,
                    'content' => $val['reply_content'] ? $val['reply_content'] : '暂无',
                ];
                $dataArr[$key]['list'][] = [
                    'title' => '处理时间', 'type' => 1,
                    'content' => $val['reply_time'],
                ];
            }
            if($val['status']>=4) {
                $dataArr[$key]['list'][] = [
                    'title' => '评价内容','type'=>1,
                    'content' => $val['comment']?$val['comment']:'暂无',
                ];
                $dataArr[$key]['list'][] = [
                    'title' => '评价时间','type'=>1,
                    'content' => $val['comment_time']?$val['comment_time']:'暂无',
                ];
            }
        }
        return $dataArr;
    }

    public function getRepairList($where, $page = 0, $limit = 10, $field = true, $order = 'r.pigcms_id desc')
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $data = $dbHouseVillageRepairList->getListLimit($where, $field, $page, $limit, $order);
        $retdata = array('list' => array(), 'total_limit' => $limit, 'count' => 0);
        if ($data && !$data->isEmpty()) {
            $data=$data->toArray();
            $service_house_village_single = new HouseVillageSingleService();
            $waste_type = array(
                1 => '玻璃制品',
                2 => '纸制品',
                3 => '金属制品',
                4 => '塑料制品',
                5 => '其他'
            );
            $retdata['count'] = $dbHouseVillageRepairList->getListCount($where, $field);
            foreach ($data as $key => &$v) {
                $v['time_str'] = '';
                if ($v['time'] > 0) {
                    $v['time_str'] = date('Y-m-d H:i:s', $v['time']);
                }
                if ($v['pic']) {
                    $pic = explode('|', $v['pic']);
                    $picArray = array();
                    foreach ($pic as $picinfo) {
                        if (substr($picinfo, 1, 6) == 'upload') {
                            $picArray[] = file_domain() . $picinfo;
                        } elseif (substr($picinfo, 3, 7) == '000/000') {
                            $picArray[] = file_domain() . "/upload/activity/" . $picinfo;
                        } else {
                            $picArray[] = file_domain() . "/upload/house/" . $picinfo;
                        }
                    }
                    $v['pic'] = $picArray;
                } else {
                    $v['pic'] = array();
                }
                if ($v['reply_pic']) {
                    $pic = explode('|', $v['reply_pic']);
                    $picArray = array();
                    foreach ($pic as $picinfo) {
                        if (substr($picinfo, 1, 6) == 'upload') {
                            $picArray[] = file_domain() . $picinfo;
                        } else {
                            $picArray[] = file_domain() . "/upload/worker/" . $picinfo;
                        }
                    }
                    $v['reply_pic'] = $picArray;
                } else {
                    $v['reply_pic'] = array();
                }
                if ($v['comment_pic']) {
                    $pic = explode('|', $v['comment_pic']);
                    $picArray = array();
                    foreach ($pic as $picinfo) {
                        if (substr($picinfo, 1, 6) == 'upload') {
                            $picArray[] = file_domain() . $picinfo;
                        } elseif (substr($picinfo, 3, 7) == '000/000') {
                            $picArray[] = file_domain() . "/upload/house/" . $picinfo;
                        } else {
                            $picArray[] = file_domain() . "/upload/house/" . $picinfo;
                        }
                    }
                    $v['comment_pic'] = $picArray;
                } else {
                    $v['comment_pic'] = array();
                }
                $v['waste_type_name'] = '';
                if ($v['waste_type'] > 0 && isset($waste_type[$v['waste_type']])) {
                    $v['waste_type_name'] = $waste_type[$v['waste_type']];
                }
                $room_diy_name = '';
                $v['single_name'] = '';
                if ($v['single_id'] > 0) {
                    $house_single = $service_house_village_single->getSingleInfo(['id' => $v['single_id']], 'single_name');
                    if (!empty($house_single)) {
                        $v['single_name'] = $house_single['single_name'];
                        if (is_numeric($v['single_name'])) {
                            $v['single_name'] = $v['single_name'] . (cfg('single_name')?:'栋');
                        }
                        $room_diy_name = $v['single_name'];
                    }
                }
                $v['floor_name'] = '';
                if ($v['floor_id'] > 0) {
                    $house_single = $service_house_village_single->getFloorInfo(['floor_id' => $v['floor_id']], 'floor_name');
                    if (!empty($house_single)) {
                        $v['floor_name'] = $house_single['floor_name'];
                        if (is_numeric($v['floor_name'])) {
                            $v['floor_name'] = $v['floor_name'] . '单元';
                        }
                        $room_diy_name .= $v['floor_name'];
                    }
                }
                $v['layer_name'] = '';
                if($v['layer_num']>0){
                    $v['layer_name'] = $v['layer_num'] . '层';
                    $room_diy_name .= $v['layer_name'];
                }else if ($v['layer_id'] > 0) {
                    $house_single = $service_house_village_single->getLayerInfo(['id' => $v['layer_id']], 'layer_name');
                    if (!empty($house_single)) {
                        $v['layer_name'] = $house_single['layer_name'];
                        if (is_numeric($v['layer_name'])) {
                            $v['layer_name'] = $v['layer_name'] . '层';
                        }
                        $room_diy_name .= $v['layer_name'];
                    }
                }

                if (!empty($v['room_addrss'])) {
                    $room_diy_name .= $v['room_addrss'];
                }
                if ($room_diy_name) {
                    $v['address'] = $room_diy_name;
                }
                $v['reply_time_str']='';
                if($v['reply_time']>0){
                    $v['reply_time_str']=date('Y-m-d H:i:s',$v['reply_time']);
                }

                $v['comment_time_str']='';
                if($v['comment_time']>0){
                    $v['comment_time_str']=date('Y-m-d H:i:s',$v['comment_time']);
                }

            }
            $retdata['list'] = $data;
        }
        return $retdata;
    }
    /**
     * Notes: 根据type类型  获取对应的状态和颜色
     * @param $status
     * @param $type
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/24 17:59
     */
    public function getStatus($status,$type)
    {
        //在线报修 0未受理1已指派2已受理3已处理4业主已评价
        //水电煤上报/投诉建议 0未受理1物业已受理2客服专员已受理3客服专员已处理4业主已评价
        switch ($status){
            case '0':
                $val['status_msg'] = '未受理';
                $val['status_color'] = '#ff2929';
                return $val;
                break;
            case '1':
                if($type == 1) {
                    $val['status_msg'] = '已指派';
                }else{
                    $val['status_msg'] = '物业已受理';
                }
                $val['status_color'] = '#a685fe';
                return $val;
                break;
            case '2':
                if($type == 1) {
                    $val['status_msg'] = '已受理';
                }else{
                    $val['status_msg'] = '客服专员已受理';
                }
                $val['status_color'] = '#7ca6f7';
                return $val;
                break;
            case '3':
                if($type == 1) {
                    $val['status_msg'] = '已处理';
                }else{
                    $val['status_msg'] = '客服专员已处理';
                }
                $val['status_color'] = '#1ed19f';
                return $val;
                break;
            case '4':
                $val['status_msg'] = '业主已评价';
                $val['status_color'] = '#ffa801';
                return $val;
                break;
        }
    }
    /**
     * 获取工单详情
     * @param array $where
     * @param string $field
     * @return array
     * @author weili
     * @datetime 2020/07/14 11:02
     */
    public function getRepairFind($where,$field)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $dbHouseVillageUserBind   = new HouseVillageUserBind();
        $dbHouseWorker            = new HouseWorker();
        $dbHouseVillageRepairFollow = new HouseVillageRepairFollow();
        $dbHouseVillageRepairCate = new HouseVillageRepairCate();
        $serviceHouseVillage = new HouseVillageService();
        $info = $dbHouseVillageRepairList->getFind($where,$field);

        $dataArr = [];
        if($info) {
            $map[] = ['village_id', '=', $info['village_id']];
            $map[] = ['uid', '=', $info['uid']];
            $map[] = ['pigcms_id', '=', $info['bind_id']];
            $userBindField = 'usernum,bind_number,name,phone,address';
            $userBindInfo = $dbHouseVillageUserBind->getOne($map, $userBindField);
            if ($userBindInfo) {
                if ($userBindInfo['bind_number']) {
                    $info['usernum'] = $userBindInfo['bind_number'];
                } elseif ($userBindInfo['usernum']) {
                    $info['usernum'] = $userBindInfo['usernum'];
                } else {
                    $info['usernum'] = '';
                }
                $info['address'] = $userBindInfo['address']?$userBindInfo['address']:'';
                if (!$info['user_name']) {
                    $info['user_name'] = $userBindInfo['name']?$userBindInfo['name']:'';
                }
                if (!$info['phone']) {
                    $info['phone'] = $userBindInfo['phone']?$userBindInfo['phone']:'';
                }
            } else {
                if (isset($info['bind_number']) && $info['bind_number']) {
                    $info['usernum'] = $info['bind_number'];
                }
                //$info['usernum'] = '';
                $info['address'] = '';
            }
            //报修类别 (1公共维修 2个人维修)
            if($info['type']==1 && $info['repair_type']){
                $info['repair_types'] = $info['repair_type'];
                $arr = array(
                    '1'=>'公共维修',
                    '2'=>'个人维修'
                );
                $info['repair_type'] = $arr[$info['repair_type']];
            }
            //获取分类
            if($info['cate_id']){//二级分类的情况
                $whereCate['a.id'] = $info['cate_id'];
                $field = 'a.cate_name,b.cate_name as cate_f_name';
                $dataCate = $dbHouseVillageRepairCate->getRelevance($whereCate,$field);
                if($dataCate){
                    if($dataCate['cate_f_name'] && $dataCate['cate_name']) {
                        $info['cate_name'] = $dataCate['cate_f_name'] . '-' . $dataCate['cate_name'];
                    }elseif (!$dataCate['cate_f_name'] && $dataCate['cate_name']){
                        $info['cate_name'] =$dataCate['cate_name'];
                    }elseif ($dataCate['cate_f_name'] && !$dataCate['cate_name']){
                        $info['cate_name'] =$dataCate['cate_f_name'];
                    }else{
                        $info['cate_name'] = '暂无';
                    }
                }else{
                    $info['cate_name'] = '暂无';
                }
            }elseif ($info['cate_fid']){//只有一级分类的情况
                $whereCateF['id'] = $info['cate_fid'];
                $dataCate = $dbHouseVillageRepairCate->getFind($whereCateF,'cate_name');
                $info['cate_name'] = $dataCate['cate_name'];
            }else{
                $info['cate_name'] = '暂无';
            }
            //添加时间
            if ($info['time']) {
                $info['time'] = date('Y-m-d H:i:s', $info['time']);
            }else{
                $info['time'] = '暂无';
            }
            //上报图例
            $picArray = [];
            if ($info['pic']) {
                $pic = explode('|', $info['pic']);
                foreach ($pic as $val) {
                    if (substr($val, 1, 6) == 'upload') {
                        $picArray[] = replace_file_domain($val);
                    } elseif (substr($val, 3, 7) == '000/000') {
                        $picArray[] = file_domain() . "/upload/activity/" . $val;
                    } else {
                        $picArray[] = file_domain() . "/upload/house/" . $val;
                    }
                }
            }

            $info['pic'] = $picArray;

            $statusData = $this->getStatus($info['status'],$info['type']);
            //处理时间
            if ($info['repair_time']) {
                $info['repair_time'] = date('Y-m-d H:i:s', $info['repair_time']);
            }else{
                $info['repair_time'] = '暂无';
            }
            //回复时间
            if ($info['reply_time']) {
                $info['reply_time'] = date('Y-m-d H:i:s', $info['reply_time']);
            }else{
                $info['reply_time'] = '暂无';
            }
            //未处理状态下不需要处理相应参数
            if ($info['status'] <> 0) {
                //处理图例
                if ($info['reply_pic']) {
                    $replyPic = explode('|', $info['reply_pic']);
                    $replyPicArr = [];
                    foreach ($replyPic as $val) {
                        $replyPicArr[] = file_domain() . "/upload/worker/" . $val;
                    }
                    $info['reply_pic'] = $replyPicArr;
                }else{
                    $info['reply_pic'] = '暂无';
                }
                $info['follow']= [];
                //查询跟进内容
                if ($info['status'] >= 2) {
                    $followWhere[] = ['repair_id', '=', $info['pigcms_id']];
                    $followField = 'time,content';
                    $followInfo = $dbHouseVillageRepairFollow->getAll($followWhere, $followField);
                    if ($followInfo) {
                        foreach ($followInfo as &$value) {
                            $value['time'] = date('Y-m-d H:i:s', $value['time']);
                        }
                    }
                    $info['follow'] = $followInfo;

                }
                //评论图例
                if ($info['comment_pic']) {
                    $commentPic = explode('|', $info['comment_pic']);
                    $commentPicArr = [];
                    foreach ($commentPic as $val) {
                        $commentPicArr[] = file_domain() . "/upload/house/" . $val;
                    }
                    $v['comment_pic'] = $commentPicArr;
                    $info['comment_pic'] = $commentPicArr;
                }
                //评论时间
                if ($info['comment_time']) {
                    $info['comment_time'] = date('Y-m-d H:i:s', $info['comment_time']);
                }else{
                    $info['comment_time'] = '暂无';
                }
                //处理人员
                $w_map[] = ['village_id', '=', $info['village_id']];
                $w_map[] = ['wid', '=', $info['wid']];
                $field = 'name,phone';
                $workerInfo = $dbHouseWorker->getOne($w_map, $field);
                if ($workerInfo) {
                    $info['worker_name'] = $workerInfo['name']?$workerInfo['name']:'';
                    $info['worker_phone'] = $workerInfo['phone']?$workerInfo['phone']:'';
                } else {
                    $info['worker_name'] = '暂无';
                    $info['worker_phone'] = '暂无';
                }
            }
            $worker = $this->getWorkerAll($info['village_id']);
            //根据楼栋id 单元id 楼层id 门牌号id 社区id 获取地址
            $single_id = $info['single_id'] ? $info['single_id'] : $info['u_single_id'];
            $floor_id = $info['floor_id'] ? $info['floor_id'] : $info['u_floor_id'];
            $layer_id = $info['layer_id'] ? $info['layer_id'] : $info['u_layer_id'];
            $vacancy_id = $info['vacancy_id'] ? $info['vacancy_id'] : $info['u_vacancy_id'];
            $info['address'] = $serviceHouseVillage->getSingleFloorRoom($single_id, $floor_id, $layer_id, $vacancy_id,$info['village_id']);
            //$info['address'] = $serviceHouseVillage->getSingleFloorRoom($info['single_id'],$info['floor_id'],$info['layer_id'],$info['vacancy_id'],$info['village_id']);

            $dataArr['pigcms_id'] = $info['pigcms_id'];
            if($info['type'] == 1 ){
                $dataArr['work_type'] = 1;
            }else{
                $dataArr['work_type'] = 0;
            }
            $dataArr['list']=[
                ['title'=>'上报地址', 'content'=>$info['address'],'type'=>1],
                ['title'=>'业主姓名', 'content'=>$info['user_name'],'type'=>1],
                ['title'=>'业主编号', 'content'=>$info['usernum'],'type'=>1],
                ['title'=>'上报时间', 'content'=>$info['time'],'type'=>1],
                ['title'=>'联系方式', 'content'=>$info['phone'],'type'=>1,'is_phone'=>1],
            ];
            if(in_array($info['type'],[1,3])) {
                if($info['type'] == 1) {
                    $dataArr['list'][] = ['title' => '报修类别', 'content' => $info['repair_type'], 'type' => 1];
                    $dataArr['list'][] = ['title' => '报修分类', 'content' => $info['cate_name'], 'type' => 1];
                }else{
                    $dataArr['list'][] = ['title' => '投诉建议分类', 'content' => $info['cate_name'], 'type' => 1];
                }
            }
            $dataArr['list'][]=['title'=>'上报内容', 'content'=>$info['content'],'type'=>1];
            if($info['repair_types'] == 2 && $info['type'] == 1)//只有在线报修的个人报修才有上门时间
            {
                $dataArr['list'][]=['title'=>'上门时间', 'content'=>$info['repair_time'],'type'=>1];
            }
            $dataArr['list'][]=['title'=>'上报图片', 'content'=>!empty($info['pic'])?$info['pic']:array(),'type'=>2];
            $dataArr['list'][]=['title'=>'状态', 'content'=>$statusData['status_msg'],'color'=>$statusData['status_color'],'type'=>1];
            if($info['status'] == 0){
                //$dataArr['list'][] = ['title'=>'分配给工作人员', 'content'=>$worker,'type'=>1];
            }
//            if($info['status'] <> 0) {
//                $dataArr['list'][] = ['title'=>'处理人员','content'=>$info['worker_name'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'接单留言','content'=>$info['msg'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'接单跟进','content'=>$info['follow'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'处理时间','content'=>$info['reply_time'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'处理意见','content'=>$info['reply_content'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'上报图例','content'=>$info['reply_pic'],'type'=>2];
//                $dataArr['list'][] = ['title'=>'评论时间','content'=>$info['comment_time'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'评分','content'=>$info['score'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'评论内容','content'=>$info['comment'],'type'=>1];
//                $dataArr['list'][] = ['title'=>'评论图例','content'=>$info['comment_pic'],'type'=>2];
//            }
            //已接单
            if($info['status'] >= 2){
                $dataArr['list'][] = ['title'=>'处理人员','content'=>$info['worker_name'],'type'=>1];
                $dataArr['list'][] = ['title'=>'接单留言','content'=>$info['msg'],'type'=>1];
                $dataArr['list'][] = ['title'=>'接单跟进','content'=>$info['follow'],'type'=>3];//接单跟进 3 时间线方式
            }
            //已处理
            if($info['status'] >= 3) {
                $dataArr['list'][] = ['title'=>'处理时间','content'=>$info['reply_time'],'type'=>1];
                $dataArr['list'][] = ['title'=>'处理意见','content'=>$info['reply_content'],'type'=>1];
                $dataArr['list'][] = ['title'=>'上报图例','content'=>!empty($info['reply_pic'])?$info['reply_pic']:array(),'type'=>2];
            }
            //已评价
            if($info['status'] >= 4){
                $dataArr['list'][] = ['title'=>'评论时间','content'=>$info['comment_time'],'type'=>1];
                $dataArr['list'][] = ['title'=>'评分','content'=>$info['score'],'type'=>1];
                $dataArr['list'][] = ['title'=>'评论内容','content'=>$info['comment'],'type'=>1];
                $dataArr['list'][] = ['title'=>'评论图例','content'=>!empty($info['comment_pic'])?$info['comment_pic']:array(),'type'=>2];
            }
        }
        return $dataArr;
    }

    /**
     * Notes: 获取工作人员  （暂弃用  如果后边需要直接使用即可）
     * @param $villageId
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: weili
     * @datetime: 2020/8/24 17:58
     */
    public function getWorkerAll($villageId)
    {

        $serviceHouseWorker = new HouseWorkerService();
        $where[] = ['village_id','=',$villageId];
        $where[] = ['type','=',1];
        $where[] = ['status','=',1];
        $where[] = ['is_del','=',0];
        $data = $serviceHouseWorker->getWorker($where);
        return $data;
    }
    /**
     * 指派工作人员
     * @param array $where
     * @param array $data
     * @param $type
     * @return
     * @author weili
     * @datetime 2020/07/14 17:13
     */
     public function appointWorker($where,$data,$type,$pigcmsId)
     {
         $dbHouseVillageRepairList = new HouseVillageRepairList();
         $dbHouseWorker = new HouseWorker();
         $serviceHouseVillageRepairLog = new HouseVillageRepairLogService();
         $map[] = ['wid','=',$data['wid']];
         $map[] = ['type','=',$type];
         $map[] = ['status','=',1];
         $map[] = ['is_del','=',0];
         $info = $dbHouseWorker->getOne($map);
         if(!$info)
         {
             throw new \think\Exception("工作人员不存在！");
         }
         $res = $dbHouseVillageRepairList->updateInfo($where, $data);
         if($res){
             $serviceHouseVillageRepairLog->addLog(array('status' => 1, 'repair_id' => $pigcmsId, 'phone' => $info['phone'], 'name' => $info['name']));
         }
         return $res;
     }

    /**
     * 获取相应条件下的工单数量
     * @author lijie
     * @date_time 2020/08/03 14:39
     * @param $where
     * @param $group
     * @return int|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
     public function getRepairCount($where,$group='')
     {
         $dbHouseVillageRepairList = new HouseVillageRepairList();
         $count = $dbHouseVillageRepairList->get_repair_list_num($where,$group);
         return $count;
     }

    /**
     * 查询对应条件下线上报修数量
     * @author  lijie
     * @date_time: 2020/12/04
     * @param $where
     * @param $area_id
     * @return mixed
     */
    public function getRepairCountGroupByAreaId($where,$area_id)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $count = $dbHouseVillageRepairList->getRepairCountGroupByAreaId($where,$area_id);
        return $count;
    }

    /**
     * 获取各个区域报修数量
     * @author lijie
     * @date_time 2020/12/05
     * @param $where
     * @return mixed
     */
    public function getAreaRepairCount($where)
    {
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $count = $dbHouseVillageRepairList->getAreaRepairCount($where);
        return $count;
    }
}
