<?php
namespace app\common\model\service\diypage;

use app\common\model\db\DiypageSearchHot;
use think\Exception;

class DiypageSearchHotService
{
    public $diypageSearchHot = null;
    public $merchantCategory = null;
    public function __construct()
    {
        $this->diypageSearchHot=new DiypageSearchHot();
    }

    /**
     * @param $where
     * @return mixed
     * 获取店铺分类页分类导航列表
     */
     public function getHotWordsList($params, $sort = ['sort' => 'desc'])
     {
        if (empty($params['source_id']) || empty($params['source'])) {
            throw new Exception('缺少参数');
        }
        $where = array(
            'source_id' => $params['source_id'],
            'source' => $params['source'],
        );
        $total = $this->diypageSearchHot->where($where)->count();
        if ($total > 0) {
            $lists = $this->diypageSearchHot->where($where)->page($params['page'], $params['pageSize'])->order($sort)->select()->toArray();
        } else {
            $lists = [];
        }
        return ['list' => $lists, 'total' => $total];
     }

     /**
     * 获取一条记录
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function getOneHotWordsId($id)
    {
        return $this->diypageSearchHot->where('pigcms_id', $id)->findOrEmpty()->toArray();
    }

     /**
     * 保存
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function getHotWordsEdit($param)
    {
        if (empty($param['source_id']) || empty($param['source'])) {
            throw new Exception('缺少参数');
        }
        if (!isset($param['name']) || empty($param['name'])) {
            throw new Exception('关键词不能为空');
        }
        $data = [
            'source_id' => $param['source_id'],
            'source' => $param['source'],
            'name' => $param['name'],
            'sort' => $param['sort'] ?? 0,
        ];
        $id = $param['id'] ?? 0;
        if ($id > 0) {
            //编辑
            $this->diypageSearchHot->where('pigcms_id', $id)->update($data);
        } else {
            //新增
            $this->diypageSearchHot->insert($data);
        }
        return true;
    }

     /**
     * 保存排序
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function getHotWordsEditSort($id, $sort)
    {
        if (empty($id)) {
            throw new Exception('请选择一条记录');
        }
        $this->diypageSearchHot->where('pigcms_id', $id)->update(['sort' => $sort]);
        return true;
    }

    /**
     * 删除
     * @param array $ids
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function delWords($ids = [])
    {
        $ids = array_filter($ids);
        if (!is_array($ids) || empty($ids)) {
            throw new Exception('请选择删除记录');
        }
        $this->diypageSearchHot->whereIn('pigcms_id', $ids)->delete();
        return true;
    }
}