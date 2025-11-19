<?php
namespace app\real_estate\model\service;

use app\real_estate\model\db\RealEstateProcess;

class ProcessService
{

    public $process;

    public function __construct(){
        $this->process = new RealEstateProcess();
    }

    /**
     * 购买流程-列表
     * @param $param
     * @return array|\think\Collection|\think\Paginator
     */
    public function getList($param){
        $where = [];
        $list = $this->process->getList($where,$param['page'],$param['page_size']);
        $arr_type = array(1 => '贷款', 2 => '全款', 3 => '其它');
        foreach ($list as $key=>$item){
            $list[$key]['update_time'] = date('Y.m.d H:s',$item['update_time']);
            $type_info = explode(',',$item['pay_type']);
            foreach ($type_info as $k=>$v){
                $type_info[$k] = $arr_type[$v]??'';
            }
            $list[$key]['pay_type'] = $type_info;
        }
        return $list;

    }

    /**
     * 购买流程-添加
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
            'sort' => $param['sort'],
            'pay_type' => $param['pay_type'],
            'font_color' => $param['font_color']
        );
        if(!$where['name']){
            throw new \think\Exception('名称不能为空！');
        }
        if(!$where['sort']){
            throw new \think\Exception('步骤值不能为空！');
        }
        if(!$where['pay_type']){
            throw new \think\Exception('请选择付款类型！');
        }
        if(!$where['font_color']){
            throw new \think\Exception('状态颜色不能为空！');
        }
        if(is_array($where['pay_type'])){
            $where['pay_type'] = implode(',',$where['pay_type']);
        }
        //查询是否重复
        $check_name = $this->process->where(['name'=>$where['name']])->find();
        if($check_name){
            throw new \think\Exception('名称已存在！');
        }

        $where['add_time'] = time();
        $where['update_time'] = time();
        try {
            $this->process->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 购买流程-编辑
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
            'sort' => $param['sort'],
            'pay_type' => $param['pay_type'],
            'font_color' => $param['font_color'],
        );
        if(!$param['id']){
            throw new \think\Exception('修改信息不存在！');
        }
        if(!$where['name']){
            throw new \think\Exception('名称不能为空！');
        }
        if(!$where['sort']){
            throw new \think\Exception('步骤值不能为空！');
        }
        if(!$where['pay_type']){
            throw new \think\Exception('请选择付款类型！');
        }
        if(!$where['font_color']){
            throw new \think\Exception('状态颜色不能为空！');
        }

        if(is_array($where['pay_type'])){
            $where['pay_type'] = implode(',',$where['pay_type']);
        }
        //查询是否重复
        $check_name = $this->process->where(['name'=>$where['name']])->find();
        if($check_name&&($check_name['id']!=$param['id'])){
            throw new \think\Exception('名称已存在！');
        }

        //查询信息是否存在
        $wifi_info = $this->process->find($param['id']);
        if(!$wifi_info){
            throw new \think\Exception('修改信息不存在！');
        }
        $where['update_time'] = time();
        try {
            $this->process->where(['id'=>$param['id']])->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        return true;

    }

    /**
     * 购买流程-付款类型
     * @return array
     */
    public function payNameList(){
        $data['pay_name_list'] = [
            [
                'id'=>1,
                'value'=>'贷款'
            ],
            [
                'id'=>2,
                'value'=>'全款'
            ],
            [
                'id'=>3,
                'value'=>'其它'
            ]
        ];
        return $data;
    }

    /**
     * 购买流程-单个信息详情
     * @param $param
     * @return array|\think\Model|null
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
        $info = $this->process->find($id);
        if(!$info){
            throw new \think\Exception('信息不存在');
        }
        $type_info = explode(',',$info['pay_type']);
        foreach ($type_info as $k=>$v){
            $type_info[$k] = intval($v);
        }
        $info['pay_type'] = $type_info;
        return $info;
    }

    /**
     * 购买流程-单个/批量删除
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
            $this->process->where([['id','in',$id]])->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 购买流程-修改步骤值
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeSort($param){
        $id = $param['id'];
        $sort = $param['sort'];
        if(!$id){
            throw new \think\Exception('信息不存在');
        }
        $info = $this->process->find($id);
        if(!$info){
            throw new \think\Exception('信息不存在');
        }
        try {
            $this->process->where([['id','=',$id]])->save(['sort'=>$sort,'update_time'=>time()]);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }
}