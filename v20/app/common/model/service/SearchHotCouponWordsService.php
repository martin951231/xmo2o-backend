<?php


namespace app\common\model\service;

use app\common\model\db\SearchHotCouponWords;
use think\Exception;

/**
 * 领券中心优惠券关键词
 * @author: 张涛
 * @date: 2020/11/23
 */
class SearchHotCouponWordsService
{
    public $hotWordsMod = null;

    public function __construct()
    {
        $this->hotWordsMod = new SearchHotCouponWords();
    }


    /**
     * 获取列表
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function getLists($params, $sort = ['sort' => 'desc'])
    {
        $total = $this->hotWordsMod->count();
        if ($total > 0) {
            $lists = $this->hotWordsMod->page($params['page'], $params['pageSize'])->order($sort)->select()->toArray();
        } else {
            $lists = [];
        }
        return ['list' => $lists, 'total' => $total];
    }

    /**
     * 获取一条记录
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function getDetailById($id)
    {
        return $this->hotWordsMod->where('id', $id)->findOrEmpty()->toArray();
    }


    /**
     * 保存
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function saveWords($param)
    {
        if (!isset($param['name']) || empty($param['name'])) {
            throw new Exception('关键词不能为空');
        }
        $data = [
            'name' => $param['name'],
            'sort' => $param['sort'] ?? 0,
            'create_time' => time()
        ];
        $id = $param['id'] ?? 0;
        if ($id > 0) {
            //编辑
            unset($data['create_time']);
            $this->hotWordsMod->where('id', $id)->update($data);
        } else {
            //新增
            $this->hotWordsMod->insert($data);
        }
        return true;
    }

    /**
     * 保存排序
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function saveWordsSort($id, $sort)
    {
        if (empty($id)) {
            throw new Exception('请选择一条记录');
        }
        $this->hotWordsMod->where('id', $id)->update(['sort' => $sort]);
        return true;
    }

    /**
     * 删除
     * @param array $ids
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function delWords($ids = [])
    {
        $ids = array_filter($ids);
        if (!is_array($ids) || empty($ids)) {
            throw new Exception('请选择删除记录');
        }
        $this->hotWordsMod->whereIn('id', $ids)->delete();
        return true;
    }
}