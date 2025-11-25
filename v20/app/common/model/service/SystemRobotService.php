<?php
/**
 * 机器人 操作
 * Author: 衡婷妹
 * Date Time: 2020/12/23
 */

namespace app\common\model\service;

use app\common\model\db\SystemRobot;
use tools;

class SystemRobotService
{
    public $systemRobotModel = null;
    public function __construct()
    {
        $this->systemRobotModel = new SystemRobot();
    }

    /**
     * 添加编辑机器人
     * @param $param
     * @return array
     */
    public function editRobot($param) {
        $id = isset($param['id']) ? $param['id'] : '0';
        $saveData['name'] = $param['name'] ?? '';
        $saveData['avatar'] = $param['avatar'] ?? '';
        if($id){
            //编辑
            $where = [
                'id' =>$id
            ];
            $res = $this->updateThis($where, $saveData);
        }else{
            // 新增
            $res = $this->add($saveData);
        }

        if($res===false){
            throw new \think\Exception("操作失败请重试");

        }
        return true;
    }

    /**
     * 删除机器人
     * @param $param
     * @return array
     */
    public function delRobot($param) {
        $id = isset($param['id']) ? $param['id'] : '0';
        if(!$id){
            throw new \think\Exception("参数错误");
        }

        $where = [
            'id' => $id
        ];
        $detail= $this->getOne($where);
        if(!$detail){
            throw new \think\Exception("机器人已删除或不存在");
        }

        $res = $this->systemRobotModel->where($where)->delete();
        if(!$res){
            throw new \think\Exception("删除失败请重试");
        }
        return true;
    }

    /**
     * 获得详情
     * @param $where
     * @return array
     */
    public function getRobotDetail($param) {
        $id = isset($param['id']) ? $param['id'] : '0';
        if(!$id){
            throw new \think\Exception("参数错误");
        }

        $where = [
            'id' => $id
        ];
        $returnArr = $this->getOne($where);

        return $returnArr;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getRobotList($param = [])
    {
        $page = $param['page'] ?? 0;
        $pageSize = $param['pageSize'] ?? 10;
        $start = ($page-1)*$pageSize;
        $order = [
            'id' => 'DESC'
        ];
        $where = [
            ['id' ,'>', 0]
        ];
        $list = $this->getSome($where, true, $order, $start, $pageSize);
        $count = $this->getCount($where);
        foreach ($list as &$_robot){
            $_robot['avatar'] = replace_file_domain($_robot['avatar'] );
            $_robot['create_time'] = date('Y-m-d H:i:s', $_robot['create_time']);
        }

        $returnArr['list'] = $list;
        $returnArr['total'] = $count;
        return $returnArr;
    }

    /**
     * 获取随机姓名
     * @param $where array
     * @return array
     */
    public function getRandName()
    {
        $randName = new tools\RandName();
        $name = $randName->getName();
        return $name;
    }

        /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where,$field = true ){
        if(empty($where)){
            return false;
        }

        $result = $this->systemRobotModel->getOne($where,$field);
        if(empty($result)){
            return [];
        }
        return $result;
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where){
        $result = $this->systemRobotModel->getCount($where);
        if(empty($result)){
            return 0;
        }
        return $result;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->systemRobotModel->getSome($where,$field,$order,$page,$limit);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();

        $id = $this->systemRobotModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->systemRobotModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }


}
