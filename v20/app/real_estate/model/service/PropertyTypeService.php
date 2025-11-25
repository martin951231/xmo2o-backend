<?php
namespace app\real_estate\model\service;

use app\real_estate\model\db\RealEstateType;

class PropertyTypeService
{

    public $propertyType;

    public function __construct(){
        $this->propertyType = new RealEstateType();
    }

    /**
     * 购房类型-列表
     * @param $param
     * @return array|\think\Collection|\think\Paginator
     */
    public function getList($param){
        $where = [];
        $list = $this->propertyType->getList($where,$param['page'],$param['page_size']);
        foreach ($list as $key=>$item){
            $list[$key]['update_time'] = date('Y.m.d H:s',$item['update_time']);
        }
        return $list;

    }

    /**
     * 购房类型-添加
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add($param){
        $where = array(
            'name' => $param['name'],
        );
        if(!$where['name']){
            throw new \think\Exception('名称不能为空！');
        }
        //查询是否重复
        $check_name = $this->propertyType->where(['name'=>$where['name']])->find();
        if($check_name){
            throw new \think\Exception('名称已存在！');
        }

        $where['add_time'] = time();
        $where['update_time'] = time();
        try {
            $this->propertyType->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 购房类型-编辑
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($param){
        $where = array(
            'name' => $param['name'],
        );
        if(!$where['name']){
            throw new \think\Exception('名称不能为空！');
        }

        //查询是否重复
        $check_name = $this->propertyType->where(['name'=>$where['name']])->find();
        if($check_name&&($check_name['id']!=$param['id'])){
            throw new \think\Exception('名称已存在！');
        }

        //查询信息是否存在
        $wifi_info = $this->propertyType->find($param['id']);
        if(!$wifi_info){
            throw new \think\Exception('修改信息不存在！');
        }
        $where['update_time'] = time();
        try {
            $this->propertyType->where(['id'=>$param['id']])->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        return true;

    }

    /**
     * 购房类型-单个信息详情
     * @param $param
     * @return array|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function show($param){
        $id = $param['id'];
        if(!$id){
            throw new \think\Exception('信息不存在');
        }
        $info = $this->propertyType->find($id);
        if(!$info){
            throw new \think\Exception('信息不存在');
        }
        return $info;
    }

    /**
     * 购房类型-单个/批量删除
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function delete($param){
        $id = $param['id'];
        if(!is_array($id)){
            $id = array($id);
        }
        if(!$id){
            throw new \think\Exception('信息已删除');
        }
        try {
            $this->propertyType->where([['id','in',$id]])->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
}