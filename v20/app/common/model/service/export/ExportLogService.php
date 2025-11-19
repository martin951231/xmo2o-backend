<?php
/**
 * 店铺service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/27 11:58
 */

namespace app\common\model\service\export;
use app\common\model\db\ExportLog as ExportLogModel;
class ExportLogService {
    public $exportLogModel = null;
    public function __construct()
    {
        $this->exportLogModel = new ExportLogModel();
       
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function add($data) {
        if(!$data){
           return false;
        }
        
        $result = $this->exportLogModel->save($data);
        if($result === false){
            return false;
        }
        
        return $this->exportLogModel->id; 
    }

    /**
     * 更新数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || !$data){
           return false;
        }
        
        $result = $this->exportLogModel->updateThis($where, $data);
        if($result === false){
            return false;
        }
        
        return $result; 
    }

    /**
     * 获得一条数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
           return[];
        }
        
        $result = $this->exportLogModel->getOne($where);
        if(!$result){
            return [];
        }
        
        return $result->toArray(); 
    }
}