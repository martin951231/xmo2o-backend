<?php


namespace app\life_tools\model\db;

use think\Model;

class LifeToolsCard extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public $term_type = [ //有效期类型:1=天,2=月,3=年
        '1' => '天',
        '2' => '月',
        '3' => '年',
    ];

    public function getTermType($term_type = 1) {
        return $this->term_type[$term_type] ?? '未知类型';
    }

    public function getTermNum($term_type = 1, $term_num = 1) {
        switch ($term_type) {
            case 2:
                $time = $term_num * 86400 * 30;
                break;
            case 3:
                $time = $term_num * 86400 * 365;
                break;
            default:
                $time = $term_num * 86400;
                break;
        }
        return $time;
    }

    /**
     *获取列表
     * @param $where array
     * @return array
     */
    public function getList($where = [], $limit = 20, $field = '*', $order = 'add_time desc') {
        if (is_array($limit)) {
            $arr = $this
                ->field($field)
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit == 0) {
            $arr = $this
                ->field($field)
                ->where($where)
                ->order($order)
                ->select();
            if (!empty($arr)) {
                $arr = $arr->toArray();
            } else {
                $arr = [];
            }
        } else {
            $arr = $this
                ->field($field)
                ->where($where)
                ->limit($limit)
                ->order($order)
                ->select();
            if (!empty($arr)) {
                $arr = $arr->toArray();
            } else {
                $arr = [];
            }
        }
        return $arr;
    }

}