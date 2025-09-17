<?php
/**
 * App打包配置服务类.
 * @author: 衡婷妹
 * @date: 2020/12/17
 */

namespace app\common\model\service;

use app\common\model\db\AppapiPackConfig;

class AppapiPackConfigService
{

    public $appapiPackConfigModel = null;
    public function __construct()
    {
        $this->appapiPackConfigModel = new AppapiPackConfig();
    }


    /**
     * 根据条件获取其数量
     * @param $where array $where
     * @return array
     */
    public function getCount($where) {
        if(empty($where)){
            return false;
        }

        $count = $this->appapiPackConfigModel->getCount($where);
        if(!$count) {
            return 0;
        }

        return $count;
    }

    /**
     * 更新数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->appapiPackConfigModel->updateThis($where,$data);
        return $result;
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->appapiPackConfigModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->appapiPackConfigModel->getSome($where, $field, $order, $page, $limit);
        if(!$result) {
            return [];
        }
        return $result->toArray();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->appapiPackConfigModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->appapiPackConfigModel->id;
    }

}