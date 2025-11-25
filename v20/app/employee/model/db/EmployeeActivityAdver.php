<?php 
namespace app\employee\model\db;
 
use think\Model;

class EmployeeActivityAdver extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取标签列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = 0, $field = '*', $order = 'id desc') {
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
     * @param $field
     * @param $where
     * @return array
     * 通过id获取一条记录
     */
    public function getById($field, $where)
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 通过条件获取列表
     * @param $field
     * @param $where
     * @param $order
     * @return array
     */
    public function getByCondition($field, $where, $order)
    {
        $arr = $this->field($field)->where($where)->order($order)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 根据条件获取广告列表
     * @param $where
     * @param $order 排序
     * @param $limit 查询记录条数限制
     * @return array|bool|Model|null
     */
    public function getAdverListByCondition($where, $order = '', $limit = 0)
    {
        $arr = $this->where($where)->order($order)->limit($limit)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 获取活动商品列表
     * @param $where array
     * @return array
     */
    public function getListJoin($where = [], $order = 'a.sort desc, a.id desc', $limit=0) {
        $prefix = config('database.connections.mysql.prefix');
       
        $data = $this->alias('a')
            ->field('a.*')
            ->leftjoin($prefix . 'employee_activity_adver_bind_lable b', 'b.adver_id = a.id')
            ->where($where)
            ->order($order)
            ->group('a.id')
            ->limit($limit)
            ->select();
        if (!empty($data)) {
            return $data->toArray();
        } else {
            return [];
        }
        return $data;
    }

}