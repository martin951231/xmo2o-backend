<?php
/**
 * 团购标签
 * Author: 衡婷妹
 * Date Time: 2021/04/28 14:01
 */

namespace app\group\model\service;

use app\group\model\db\GroupLabel;
use think\facade\Db;

class GroupLabelService
{
    public $groupLabelModel = null;

    public function __construct()
    {
        $this->groupLabelModel = new GroupLabel();
    }


    /**
     *根据条件返回总数
     * @param $where array
     * @return array
     */
    public function addLabel($param=[])
    {
        $saveData = [];
        $saveData['mer_id'] = $param['mer_id'] ?? '0';//商家id
        $saveData['name'] = $param['name'] ?? '';//标签名
        $saveData['fid'] = $param['fid'] ?? '0';// 标签父id
        $id = $param['id'] ?? '0';//标签id

        if(empty($saveData['name'])){
            throw new \think\Exception(L_('请输入标签名称'),1001);
        }

        if($id){
            $where['label_id'] = $id;
            $res = $this->updateThis($where,$saveData);
            if (!$res) {
                throw new \think\Exception(L_('编辑失败,请重试'),1003);
            }
        }else{
            $res = $this->add($saveData);
            if (!$res) {
                throw new \think\Exception(L_('添加失败,请重试'),1003);
            }
        }
        return ['msg' => L_('操作成功')];
    }

    
    /**
     *返回树状结构
     * @param $where array
     * @return array
     */
    public function getLabelTree($merId)
    {
        // 获得所有标签
        $where = [
            'is_del' => 0,
            'mer_id' => $merId,
        ];
        $list = $this->getSome($where,true,['label_id'=>'ASC']);

        // 返回数组
        $returnArr = [];

        // 生成树状关系
        $tmpMap = array();
        foreach ($list as $label) {
            $temp = [
                'id' => $label['label_id'],
                'fid' => $label['fid'],
                'name' => $label['name'],
            ];
            $tmpMap[$label['label_id']] = $temp;
        }
        $treeList = [];
        foreach($list as $label){
            if ($label['fid'] && isset($tmpMap[$label['fid']])) {// 子标签
                $tmpMap[$label['fid']]['child'][$label['label_id']] = &$tmpMap[$label['label_id']];
            } elseif(!$label['fid']) {// 主标签
                $treeList[$label['label_id']] = &$tmpMap[$label['label_id']];
            }
        }
        
        foreach($treeList as $key => $label){
            if(isset($label['child']) && $label['child']){
                $treeList[$key]['child'] = array_values($label['child']);
            }else{
                $treeList[$key]['child'] = [];
            }
        }

        $returnArr['list'] = array_values($treeList);
        
        return $returnArr;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data)
    {
        $id = $this->groupLabelModel->insertGetId($data);
        if (!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data)
    {
        if (empty($data)) {
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupLabelModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($data) || empty($where)) {
            return false;
        }

        $result = $this->groupLabelModel->where($where)->update($data);
        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupLabelModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true, $order = true, $page = 0, $limit = 0)
    {
        $start = ($page-1)*$limit;
        $result = $this->groupLabelModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->groupLabelModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 获取树状图
     * @param $where
     * @param bool $isRaw
     * @param bool $addAll
     * @return array
     * @author: 张涛
     * @date: 2021/05/22
     */
    public function getLabelTreeV2($where, $isRaw = false, $withAll = true)
    {
        if ($isRaw) {
            $labels = $this->groupLabelModel->whereRaw(Db::raw($where))->select()->toArray();
        } else {
            $labels = $this->groupLabelModel->where($where)->select()->toArray();
        }
        if (empty($labels)) {
            return [];
        }
        $parent = array_filter($labels, function ($r) {
            return $r['fid'] == 0;
        });
        foreach ($parent as $k => $p) {
            $children = array_filter($labels, function ($r) use ($p) {
                return $r['fid'] == $p['label_id'];
            });
            $child = ["label_id" => 0, "mer_id" => $p['mer_id'], "fid" => $p['label_id'], "name" => L_('全部'), "is_del" => 0];
            $parent[$k]['children'] = array_merge([$child], array_values($children));
        }

        if ($withAll) {
            $firstLabel = $labels[0];
            $all = ["label_id" => 0, "mer_id" => $firstLabel['mer_id'], "fid" => 0, "name" => L_('全部'), "is_del" => 0, "children" => []];
            $parent = array_merge([$all], $parent);
        }
        return array_values($parent);
    }
}