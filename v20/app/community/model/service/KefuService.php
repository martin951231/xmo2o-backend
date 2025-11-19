<?php


namespace app\community\model\service;

use app\community\model\db\MerchantStoreKefu;
use app\community\model\db\HouseServicesHousekeeper;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseWorker;

class KefuService
{
    public function getKefu($where=[],$field=true)
    {
        $db_merchant_store_kefu = new MerchantStoreKefu();
        $db_services_housekeeper = new HouseServicesHousekeeper();
        $db_house_village_single = new HouseVillageSingle();
        $data = $db_merchant_store_kefu->getOne($where,$field);
        $info = [];
        if($data){
            $keeper_list = $db_services_housekeeper->getList([['village_id','=',$data['store_id'],['single_id','<>',0]]]);
            $single_id = [];
            if($keeper_list){
                foreach ($keeper_list as $v){
                    $work_arr = explode(',',$v['work_arr']);
                    if(in_array($data['bind_uid'],$work_arr)){
                        $single_id[] = $v['single_id'];
                    }
                }
            }
            if($single_id){
                $single_info = $db_house_village_single->getList([['id','in',$single_id]],'single_name');
                $info['single_name'] = '';
                foreach ($single_info as $v){
                    $info['single_name'] .= isset($v['single_name'])?$v['single_name'].';':'';
                }
                $info['single_name'] = rtrim($info['single_name'],';');
            }else{
                $info['single_name'] = '无';
            }
            $db_house_worker = new HouseWorker();
            $worker_info = $db_house_worker->get_one(['wid'=>$data['bind_uid']],'name,phone,avatar,job_number');
            $info['name'] = isset($worker_info['name'])?$worker_info['name']:'';
            $info['phone'] = isset($worker_info['phone'])?$worker_info['phone']:'';
            $info['avatar'] = isset($worker_info['avatar'])?$worker_info['avatar']:'';
            $info['job_number'] = isset($worker_info['job_number'])?$worker_info['job_number']:'';
        }
        return $info;
    }

    function isLastCharChinese($str) {
        $lastChar = mb_substr($str, -1, 1, 'UTF-8');
        return preg_match('/[\x{4e00}-\x{9fa5}]/u', $lastChar);
    }

    public function getHousekeepersOfSingle($where = [], $field = 'housekeeper_id,work_arr,property_id,village_id,single_id')
    {
        $db_services_housekeeper = new HouseServicesHousekeeper();
        $db_house_village_single = new HouseVillageSingle();
        $db_house_worker = new HouseWorker();
        $keeper_list = $db_services_housekeeper->getList($where, $field);
        $keeperSingle = array();
        if ($keeper_list && !$keeper_list->isEmpty()) {
            $keeper_list = $keeper_list->toArray();
            foreach ($keeper_list as $v) {
                $single_name = '';
                if ($v['single_id'] > 0) {
                    $single_info = $db_house_village_single->getOne(array(['id', '=', $v['single_id']], ['status', '<', 4]), 'single_name');
                    if ($single_info && isset($single_info['single_name'])) {
                        $single_name = $single_info['single_name'];
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
                if ($single_name && !$this->isLastCharChinese($single_name)) {
                    $single_name = $single_name . '栋楼';
                }
                $work_arr = explode(',', $v['work_arr']);
                if ($v['work_arr'] && $work_arr) {
                    $whereTempArr = array(['village_id', '=', $v['village_id']], ['wid', 'in', $work_arr]);
                    $workerObj = $db_house_worker->getAll($whereTempArr, 'wid,name,phone,nickname');
                    if ($workerObj && !$workerObj->isEmpty()) {
                        $workers = $workerObj->toArray();
                        foreach ($workers as $wvv) {
                            $title_name = $single_name . '：' . $wvv['name'];
                            $key_id = $wvv['wid'] . '_' . $v['single_id'];
                            $keeperSingle[] = array('wid' => $wvv['wid'], 'village_id' => $v['village_id'], 'single_name' => $single_name, 'key_id' => $key_id, 'single_id' => $v['single_id'], 'name' => $wvv['name'], 'phone' => $wvv['phone'], 'title_name' => $title_name);
                        }
                    }
                }
            }
        }
        return ['list' => $keeperSingle];
    }

    public function getHousekeepersList($where = [], $field = 'housekeeper_id,work_arr,property_id,village_id,single_id')
    {
        $db_services_housekeeper = new HouseServicesHousekeeper();
        $db_house_village_single = new HouseVillageSingle();
        $db_house_worker = new HouseWorker();
        $keeper_list = $db_services_housekeeper->getList($where, $field);
        $keeperSingle = array();
        if ($keeper_list && !$keeper_list->isEmpty()) {
            $keeper_list = $keeper_list->toArray();
            foreach ($keeper_list as $v) {
                $work_arr = explode(',', $v['work_arr']);
                $whereSingle = array(['id', '=', $v['single_id']], ['status', '<', 4]);
                $singleObj = $db_house_village_single->getOne($whereSingle, '*');
                if(empty($singleObj) || $singleObj->isEmpty()){
                    continue;
                }
                if ($v['work_arr'] && $work_arr) {
                    $whereTempArr = array(['village_id', '=', $v['village_id']], ['wid', 'in', $work_arr],['status','<',4]);
                    $workerObj = $db_house_worker->getAll($whereTempArr, 'wid,name,phone,nickname');
                    if ($workerObj && !$workerObj->isEmpty()) {
                        $workers = $workerObj->toArray();
                        foreach ($workers as $wvv) {
                            $title_name = $wvv['name'];
                            if (!isset($keeperSingle[$wvv['wid']])) {
                                $keeperSingle[$wvv['wid']] = array('wid' => $wvv['wid'], 'village_id' => $v['village_id'], 'phone' => $wvv['phone'], 'title_name' => $title_name);
                            }
                        }
                    }
                }
            }
            $keeperSingle = array_values($keeperSingle);
        }
        return ['list' => $keeperSingle];
    }

    public function getVillageSingleListByWid($where, $wids)
    {
        $singleList = array('list' => array(),'single_ids'=>array());
        if (empty($wids)) {
            return $singleList;
        }
        $db_services_housekeeper = new HouseServicesHousekeeper();
        $single_id_arr = array();
        foreach ($wids as $wid) {
            $whereStr = ' FIND_IN_SET(' . $wid . ',`work_arr`)';
            $keeper_list = $db_services_housekeeper->getListAndRaw($where, 'work_arr,property_id,village_id,single_id', $whereStr);
            if ($keeper_list && !$keeper_list->isEmpty()) {
                $keeper_list = $keeper_list->toArray();
                foreach ($keeper_list as $v) {
                    if ($v['single_id'] > 0) {
                        $single_id_arr[] = $v['single_id'];
                    }
                }

            }
        }
        if (empty($single_id_arr)) {
            return $singleList;
        }
        $single_ids=array();
        $single_id_arr = array_unique($single_id_arr);
        $db_house_village_single = new HouseVillageSingle();
        $whereSingle = array(['id', 'in', $single_id_arr], ['status', '<', 4]);
        $singleObj = $db_house_village_single->getList($whereSingle, 'id as single_id,single_name,single_number', 'id desc');
        if ($singleObj && !$singleObj->isEmpty()) {
            $singleArr = $singleObj->toArray();
            foreach ($singleArr as $kk => $svv) {
                $single_ids[]=$svv['single_id'];
                $svv['single_name'] = strval($svv['single_name']);
            }
            $singleList['single_ids']= $single_ids;
            $singleList['list'] = $singleArr;
        }
        return $singleList;
    }
    
	/**
	 * 获取楼栋管家信息
	 * @param      $where
	 * @param bool $field
	 *
	 * @return array|\think\Model|null
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function getBuildingButlerInfo($where , $field=true)
	{
		return (new HouseServicesHousekeeper())->where($where)->field($field)->find();
	}

	public function saveBuildingButlerInfo($where , $data)
	{
		return (new HouseServicesHousekeeper())->where($where)->data($data)->save();
	}

	public function addBuildingButlerInfo($data)
	{
		return (new HouseServicesHousekeeper())->save($data);
	}

}