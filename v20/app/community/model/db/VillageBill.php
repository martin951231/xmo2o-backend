<?php

namespace app\community\model\db;

use think\Model;

class VillageBill extends Model
{
    // 定义表名（可选，ThinkPHP会自动推断，但显式定义更清晰）
    protected $name = 'village_bill';

    /**
     * 获取列表（分页/全部）
     * @param array $where 查询条件
     * @param mixed $field 查询字段（true=全部，字符串=指定字段）
     * @param int $page 页码（0=不分页）
     * @param int $limit 每页条数
     * @param string $order 排序规则
     * @return \think\Collection
     */
    public function getList($where = [], $field = true, $page = 1, $limit = 10, $order = 'id DESC')
    {
        $query = $this->where($where)->field($field)->order($order);
        // 分页逻辑
        if ($page > 0) {
            $data = $query->page($page, $limit)->select();
        } else {
            $data = $query->select();
        }
        return $data;
    }

    /**
     * 根据分类获取列表（分页/全部）
     * @param array $where 查询条件
     * @param mixed $field 查询字段（true=全部，字符串=指定字段）
     * @param int $page 页码（0=不分页）
     * @param int $limit 每页条数
     * @param string $order 排序规则
     * @return \think\Collection
     */
    public function getListAll($where = [], $field = true, $page = 1, $limit = 10, $order = 'vb.id DESC')
    {
        $query = $this->alias('vb')
            ->leftJoin('budget_category bc', 'bc.id=vb.category_id')
            ->where($where)
            ->field($field)
            ->order($order);
        // 分页逻辑
        if ($page > 0) {
            $data = $query->page($page, $limit)->select();
        } else {
            $data = $query->select();
        }
        return $data;
    }

    /**
     * 获取总数
     * @param array $where 查询条件
     * @return int
     */
    public function getCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 获取单条数据
     * @param array $where 查询条件
     * @param mixed $field 查询字段
     * @return $this|Model|null
     */
    public function getOne($where = [], $field = true)
    {
        return $this->where($where)->field($field)->find();
    }

    /**
     * 修改单条数据（对应update操作）
     * @author: zhubaodi
     * @date_time: 2021/8/13 9:52
     * @param array $where 修改条件（如 ['id' => 1, 'property_id' => 2]）
     * @param array $save 修改的字段及值（如 ['name' => '新分类名', 'status' => 1]）
     * @return int|bool 受影响行数（成功返回>0，失败返回false/0）
     */
    public function saveOne($where, $save)
    {
        // 过滤空值（可选，根据业务需求调整）
        $save = array_filter($save, function ($val) {
            return $val !== '' && $val !== null;
        });
        if (empty($save)) {
            return false;
        }
        return $this->where($where)->save($save);
    }

    /**
     * 新增数据（对应insertGetId操作）
     * @author: zhubaodi
     * @date_time: 2021/8/13 9:52
     * @param array $data 新增数据（如 ['name' => '物业费', 'unit' => '元', 'status' => 1]）
     * @return int|bool 新增数据的自增ID（成功返回ID，失败返回false）
     */
    public function addOne($data)
    {
        // 过滤无效字段（仅保留表中存在的字段，防止注入）
        $allowFields = $this->getTableFields(); // 获取表的所有字段
        $data = array_intersect_key($data, array_flip($allowFields));

        if (empty($data)) {
            return false;
        }
        // 自动补充时间字段（如果表中有create_time/update_time）
        if (!isset($data['addtime']) && in_array('addtime', $allowFields)) {
            $data['addtime'] = date('Y-m-d H:i:s');
        }
        return $this->insertGetId($data);
    }

    /**
     * 获取指定字段的列数据
     * @param array $where 查询条件
     * @param mixed $field 查询字段
     * @param string $key 索引键
     * @return array
     */
    public function getColumn($where, $field, string $key = '')
    {
        return $this->where($where)->column($field, $key);
    }

    // ---------------- 以下是兼容ThinkPHP原生方法的封装（可选，便于理解） ----------------
    /**
     * 原生update操作（和saveOne等价，语义更直观）
     * @param array $where 修改条件
     * @param array $data 修改数据
     * @return int|bool
     */
    public function updateOne($where, $data)
    {
        return $this->saveOne($where, $data);
    }

    /**
     * 原生insertGetId操作（和addOne等价，语义更直观）
     * @param array $data 新增数据
     * @return int|bool
     */
    public function insertGetIdOne($data)
    {
        return $this->addOne($data);
    }
}