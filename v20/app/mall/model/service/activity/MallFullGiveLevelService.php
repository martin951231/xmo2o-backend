<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallFullGiveLevel;

class MallFullGiveLevelService{
    protected $MallFullGiveLevel = null;

    public function __construct()
    {
        $this->MallFullGiveLevel = (new MallFullGiveLevel());
    }

    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addGiveLevel($data) {
        return $this->MallFullGiveLevel->addOne($data);
    }

    /** 更新数据
     * Date: 2020-10-16 15:42:29
     * @param array $data
     * @param array|mixed $where
     * @return boolean
     */
    public function updateGiveLevel($data,$where) {
        return $this->MallFullGiveLevel->updateOne($data,$where);
    }

    /** 删除数据
     * Date: 2020-10-16 15:42:29
     * @param array|mixed $where
     * @return boolean
     */
    public function delGiveLevel($where) {
        return $this->MallFullGiveLevel->delOne($where);
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return array
     * 查找层级数据
     */
    public function getActLevel($where,$field,$order){
        return $this->MallFullGiveLevel->getInfoList($where,$field,$order);
    }
}