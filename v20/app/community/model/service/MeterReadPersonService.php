<?php


namespace app\community\model\service;

use app\community\model\db\MeterReadCate;
use app\community\model\db\MeterReadPerson;
use app\community\model\db\MeterReadRecord;
use app\community\model\db\MeterReadProject;

class MeterReadPersonService
{
    /**
     * 获取单个数据
     * @author lijie
     * @date_time 2020/11/02
     * @param array $where
     * @param array $wheres
     * @param bool $field
     * @param string $order
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeterReadPerson($where=array(),$wheres=array(),$field=true,$order='id DESC')
    {
        $db_meter_read_person = new MeterReadPerson();
        $data = $db_meter_read_person->getOne($where,$wheres,$field,$order);
        return $data;
    }

    /**
     * 取列表
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeterReadPersonList($where,$field=true)
    {
        $db_meter_read_person = new MeterReadPerson();
        $data = $db_meter_read_person->getLists($where,$field);
        return $data;
    }

    /**
     * 抄表记录
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @param string $order
     * @return mixed
     */
    public function getMeterReadRecord($where,$field=true,$order='id DESC')
    {
        $db_meter_read_record = new MeterReadRecord();
        $data = $db_meter_read_record->getOne($where,$field,$order);
        return $data;
    }

    /**
     * 添加抄表记录
     * @author lijie
     * @date_time 2020/11/02
     * @param $data
     * @return mixed
     */
    public function addMeterReadRecord($data)
    {
        $db_meter_read_record = new MeterReadRecord();
        $res = $db_meter_read_record->addOne($data);
        return $res;
    }

    /**
     * 抄表记录列表
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getMeterReadRecordList($where,$field=true,$page=1,$limit=20)
    {
        $db_meter_read_record = new MeterReadRecord();
        $data = $db_meter_read_record->getList($where,$field,$page,$limit);
        return $data;
    }

    /**
     * 表记录数量
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @return mixed
     */
    public function getMeterReadRecordCount($where)
    {
        $db_meter_read_record = new MeterReadRecord();
        $count = $db_meter_read_record->getCount($where);
        return $count;
    }

    /**
     * 抄表项目列表
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getMeterReadProjectList($where,$field= true)
    {
        $db_meter_read_project = new MeterReadProject();
        $data = $db_meter_read_project->getList($where,$field);
        return $data;
    }

    /**
     * 抄表项目
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getMeterReadProject($where,$field=true)
    {
        $db_meter_read_project = new MeterReadProject();
        $data = $db_meter_read_project->getOne($where,$field);
        return $data;
    }
    public function getMeterReadProjectCount($where)
    {
        $db_meter_read_project = new MeterReadProject();
        $data = $db_meter_read_project->getCount($where);
        return $data;
    }

    /**
     * 抄表分类
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getMeterReadCate($where,$field=true)
    {
        $db_meter_read_cate  = new MeterReadCate();
        $data = $db_meter_read_cate->getOne($where,$field);
        return $data;
    }

    /**
     * 抄表分类
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getMeterReadCateList($where,$field=true)
    {
        $db_meter_read_cate  = new MeterReadCate();
        $data = $db_meter_read_cate->getList($where,$field);
        return $data;
    }


    /**
     * 抄表分类
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getMeterReadCateField($where,$field=true)
    {
        $db_meter_read_cate  = new MeterReadCate();
        $data = $db_meter_read_cate->getField($where,$field);
        return $data;
    }

}