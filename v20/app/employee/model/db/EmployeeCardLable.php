<?php 
namespace app\employee\model\db;
 
use think\Model;

class EmployeeCardLable extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $json = ['bind_store_id'];
    protected $jsonAssoc = true;
    /**
     * 获取标签列表
     * @param $where array
     * @return array
     */
    public function getLableList($where = [], $limit = 0, $field = '*', $order = 'id desc') {
        if (is_array($limit)) {
            $arr = $this->field($field)->where($where)->order($order)->paginate($limit)->toArray();
        } else if ($limit > 0) {
            $data = $this->field($field)->where($where)->limit($limit)->order($order)->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        } else {
            $data = $this->field($field)->where($where)->order($order)->select();
            $arr = [
                'data' => []
            ];
            if (!empty($data)) {
                $arr['data'] = $data->toArray();
            }
        }
        return $arr;
    }
    
    /**
     * 获取标签列表
     * @param $where array
     * @return array
     */
    public function getLableAll($where = [], $field = 'l.*,m.name as merchant_name', $order = 'l.id desc') {
      
        $data = $this->alias('l')
                    ->leftJoin($this->dbPrefix().'merchant m','m.mer_id=l.mer_id')
                    ->field($field)
                    ->where($where)
                    ->order($order)
                    ->select();
       
        if (!empty($data)) {
            $arr= $data->toArray();
        }
        
        return $arr;
    }

}