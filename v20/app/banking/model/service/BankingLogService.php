<?php
/**
 * 金融产品修改记录service
 * Author: hengtingmei
 * Date Time: 2022/01/06
 */

namespace app\banking\model\service;

use app\banking\model\db\BankingLog;

class BankingLogService {
    public $bankingLogModel = null;
    public function __construct()
    {
        $this->bankingLogModel = new BankingLog();
    }

    /**
     * 获得产品列表
     * @param $where
     * @return array
     */
    public function getList($where = [])
    {
        $pageSize = isset($where['pageSize']) ? $where['pageSize'] : 0;//每页显示数量
        $page = request()->param('page', '1', 'intval');//页码

        // 构造查询条件
        $condition = [];
        
        // 排序
        $order = [
            'id' => 'DESC',
        ];

        // 产品id
        if(isset($where['banking_id']) && $where['banking_id']){
            $condition[] = ['banking_id', '=', $where['banking_id']];
        }       
        
        // 修改人
        if(isset($where['keywords']) && $where['keywords']){
            $condition[] = ['edit_people', 'like', '%'.$where['keywords'].'%'];
        }

        // 开始时间
        if(isset($where['start_time']) && $where['start_time']){
            $condition[] = ['add_time', '>=', strtotime($where['start_time'])];
        }
        // 结束时间
        if(isset($where['end_time']) && $where['end_time']){
            $condition[] = ['add_time', '<=', strtotime($where['end_time'])+86399];
        }

        // 列表
        $list = $this->getSomeAndPage($condition, true, $order, $page, $pageSize);
        
        foreach($list['data'] as &$_banking){
            $_banking['add_time_txt'] =  date('Y-m-d H:i:s', $_banking['add_time']);

            if($_banking['type'] == 'images' || $_banking['type'] == 'cover_image'){
                $_banking['pre_content'] = replace_file_domain($_banking['pre_content']);
                $_banking['content'] = replace_file_domain($_banking['content']);
            }
        }

        return $list;
    }

    /**
    *获取多条条数据
    * @param array $where 
    * @return array
    */
    public function getSomeAndPage($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->bankingLogModel->getSomeAndPage($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    } 

    /**
     * 添加多条记录
     * @param $data array 数据
     * @return array
     */
    public function addAll($data){
        $id = $this->bankingLogModel->addAll($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
}