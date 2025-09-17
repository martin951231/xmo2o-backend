<?php
namespace app\real_estate\model\service;

use app\real_estate\model\db\RealEstateProject;

class ProjectService
{

    public $project;

    public function __construct(){
        $this->project = new RealEstateProject();
    }

    /**
     * 项目列表-列表
     * @param $param
     * @return array|\think\Collection|\think\Paginator
     */
    public function getList($param){
        $where = [];
        $list = $this->project->getList($where,$param['page'],$param['page_size']);
        foreach ($list as $key=>$item){
            $list[$key]['update_time'] = date('Y.m.d H:s',$item['update_time']);
        }
        return $list;

    }

    /**
     * 项目列表-添加
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
            'place' => $param['place']
        );
        if(!$where['name']){
            throw new \think\Exception('名称不能为空！');
        }
        if(!$where['place']){
            throw new \think\Exception('位置不能为空！');
        }
        //查询是否重复
        $check_name = $this->project->where(['name'=>$where['name']])->find();
        if($check_name){
            throw new \think\Exception('名称已存在！');
        }

        $where['add_time'] = time();
        $where['update_time'] = time();
        try {
            $this->project->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 项目列表-编辑
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
            'place' => $param['place']
        );
        if(!$where['name']){
            throw new \think\Exception('名称不能为空！');
        }
        if(!$where['place']){
            throw new \think\Exception('位置不能为空！');
        }

        //查询是否重复
        $check_name = $this->project->where(['name'=>$where['name']])->find();
        if($check_name&&($check_name['id']!=$param['id'])){
            throw new \think\Exception('名称已存在！');
        }

        //查询信息是否存在
        $wifi_info = $this->project->find($param['id']);
        if(!$wifi_info){
            throw new \think\Exception('修改信息不存在！');
        }
        $where['update_time'] = time();
        try {
            $this->project->where(['id'=>$param['id']])->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        return true;

    }

    /**
     * 项目列表-单个信息详情
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
        $info = $this->project->find($id);
        if(!$info){
            throw new \think\Exception('信息不存在');
        }
        return $info;
    }

    /**
     * 项目列表-单个/批量删除
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
            $this->project->where([['id','in',$id]])->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
}