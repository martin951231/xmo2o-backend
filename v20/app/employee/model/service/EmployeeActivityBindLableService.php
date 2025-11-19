<?php


namespace app\employee\model\service;
use app\employee\model\db\EmployeeActivityBindLable;
use think\facade\Db;

class EmployeeActivityBindLableService
{

    public $employeeActivityBindLable = null;
    public function __construct()
    {
        $this->employeeActivityBindLable = new EmployeeActivityBindLable();
       
    }

    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0)
    {
        $res = $this->employeeActivityBindLable->getSome($where, $field, $order, $page, $limit);
        if(empty($res)){
            return [];
        }
       return $res->toArray();
    }

    public function addAll($data)
    {
        $res = $this->employeeActivityBindLable->addAll($data);
        
        return $res;
    }

    

    public function del($where)
    {
        if(empty($where)){
            return false;
        }
        $res = $this->employeeActivityBindLable->where($where)->delete();
        
        return $res;
    }


}