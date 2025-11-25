<?php 
namespace app\douyin\model\db;
 
use think\Model;

class DouyinActivity extends Model
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

}