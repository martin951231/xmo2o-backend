<?php
/**
 * 运费模板
 * Author: hengtingmei
 * Date Time: 2021/5/21 11:40
 */


namespace app\merchant\model\service\delivery;
use app\merchant\model\db\ExpressTemplate;
class ExpressTemplateService {
    public $expressTemplateModel = null;
    public function __construct()
    {
        $this->expressTemplateModel = new ExpressTemplate();
    }

    
    /**
     * 获取运费模板列表
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getExpressList($where = [], $field = 'e.*,a.*,v.*',$order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->expressTemplateModel->getExpressList($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->expressTemplateModel->getOne($where);
        if(!$result) {
            return [];
        }
        return $result;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->expressTemplateModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->expressTemplateModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     *添加一条数据
     * @param $where array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }
        $result = $this->expressTemplateModel->add($data);
        if(empty($result)) return false;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->expressTemplateModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}