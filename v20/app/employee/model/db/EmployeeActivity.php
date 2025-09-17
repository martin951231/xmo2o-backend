<?php 
namespace app\employee\model\db;
 
use think\Model;

class EmployeeActivity extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取标签列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = 0, $field = '*', $order = 'pigcms_id desc') {
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
     * 获取活动商品列表
     * @param $where array
     * @return array
     */
    public function getListJoin($where = [], $limit = [], $field = 'a.*', $order = 'a.pigcms_id desc') {
        $prefix = config('database.connections.mysql.prefix');
       
        $data = $this->alias('a')
            ->field($field)
            ->leftjoin($prefix . 'employee_activity_bind_lable b', 'b.activity_id = a.pigcms_id')
            ->where($where)
            ->order($order)
            ->group('a.pigcms_id')
            ->paginate($limit)
            ->toArray();
        return $data;
    }

}