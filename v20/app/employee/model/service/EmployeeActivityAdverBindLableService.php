<?php


namespace app\employee\model\service;
use app\employee\model\db\EmployeeActivityAdverBindLable;
use think\facade\Db;

class EmployeeActivityAdverBindLableService
{

    public $employeeActivityAdverBindLable = null;
    public function __construct()
    {
        $this->employeeActivityAdverBindLable = new EmployeeActivityAdverBindLable();
       
    }

    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0)
    {
        $res = $this->employeeActivityAdverBindLable->getSome($where, $field, $order, $page, $limit);
        if(empty($res)){
            return [];
        }
       return $res->toArray();
    }

    public function addAll($data)
    {
        $res = $this->employeeActivityAdverBindLable->addAll($data);
        
        return $res;
    }

    

    public function del($where)
    {
        if(empty($where)){
            return false;
        }
        $res = $this->employeeActivityAdverBindLable->where($where)->delete();
        
        return $res;
    }


}