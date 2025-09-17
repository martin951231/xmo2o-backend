<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillageUserLabelRecord;

class HouseVillageUserLabelRecordService
{
    /**
     * 获取跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordList($where,$field,$page,$limit,$order='id DESC')
    {
        $db_house_village_user_label_record = new HouseVillageUserLabelRecord();
        $data = $db_house_village_user_label_record->getList($where,$field,$page,$limit,$order);
        $count = $db_house_village_user_label_record->getCount($where);
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            }
        }
        $res['list'] = $data;
        $res['count'] = $count;
        return $res;
    }

    /**
     * 添加跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @param $data
     * @return int|string
     */
    public function addRecord($data)
    {
        $db_house_village_user_label_record = new HouseVillageUserLabelRecord();
        $res = $db_house_village_user_label_record->addOne($data);
        return $res;
    }

    /**
     * 修改跟踪记录
     * @author lijie
     * @date_time 2020/10/16
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveRecord($where,$data)
    {
        $db_house_village_user_label_record = new HouseVillageUserLabelRecord();
        $res = $db_house_village_user_label_record->saveOne($where,$data);
        return $res;
    }

    /**
     * 跟踪记录详情
     * @author lijie
     * @date_time 2020/11/19
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordDetail($where,$field=true)
    {
        $db_house_village_user_label_record = new HouseVillageUserLabelRecord();
        $data = $db_house_village_user_label_record->getOne($where,$field);
        return $data;
    }
}