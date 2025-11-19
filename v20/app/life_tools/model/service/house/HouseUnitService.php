<?php


namespace app\life_tools\model\service\house;


use app\life_tools\model\db\LifeToolsHouseBuilding;
use app\life_tools\model\db\LifeToolsHouseUnit;
use app\life_tools\model\db\LifeToolsHouseFloor;
use app\life_tools\model\db\LifeToolsHouseRoom;
use think\facade\Db;

class HouseUnitService
{
    /**
     * 获取列表
     */
    public function getList($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $house_id = $params['house_id'];
        $pid = $params['pid'];
        if(!$house_id){
            throw new \think\Exception(L_('请选择楼盘！'));
        }
        if(!$pid){
            throw new \think\Exception(L_('请选择楼栋！'));
        }
        $p_info = (new LifeToolsHouseBuilding())->where(['id'=>$pid,'house_id'=>$house_id])->findOrEmpty()->toArray();
        if(!$p_info){
            throw new \think\Exception(L_('楼栋不存在/已删除！'));
        }
        $where[] = ['house_id','=',$house_id];
        $where[] = ['p_number','=',$p_info['serial_number']];
        $list = (new LifeToolsHouseUnit())->where($where)->order('sort asc,serial_number asc,id asc')->paginate($pageSize);
        return $list;
    }

    /**
     * 修改状态
     * @param $params
     * @throws \think\Exception
     */
    public function changeStatus($params){
        $id = $params['id'];
        $status = $params['status'];
        if(!$id){
            throw new \think\Exception(L_('请选择要修改信息！'));
        }
        if(!in_array($status,[0,1])){
            throw new \think\Exception(L_('状态错误！'));
        }
        $info = (new LifeToolsHouseUnit())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        try {
            (new LifeToolsHouseUnit())->where(['id'=>$id])->save(['status'=>$status]);
            return true;
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 保存信息
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function saveInfo($params){
        $id = $params['id']??0;
        $house_id = $params['house_id']??0;
        $pid = $params['pid']??0;
        $saveData = [
            'name' => $params['name'],
            'sort' => $params['sort'],
            'status' => $params['status'],
            'serial_number' => $params['serial_number']
        ];
        if(!$saveData['name']){
            throw new \think\Exception(L_('名称不能为空！'));
        }
        if(!is_numeric($saveData['sort'])&&$saveData['sort']<0){
            throw new \think\Exception(L_('排序值不能小于0！'));
        }
        if(!in_array($saveData['status'],[0,1])){
            throw new \think\Exception(L_('状态错误！'));
        }
        if(!is_numeric($saveData['serial_number'])||$saveData['serial_number']<0){
            throw new \think\Exception(L_('编号大于0！'));
        }
        try {
            if($id){
                $info = (new LifeToolsHouseUnit())->where(['id'=>$id])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('信息不存在/已删除！'));
                }
                //编号是否重复
                $ready = (new LifeToolsHouseUnit())
                    ->where([['id','<>',$id],['house_id','=',$info['house_id']],['p_number','=',$info['p_number']],['serial_number','=',$saveData['serial_number']]])
                    ->findOrEmpty()->toArray();
                if($ready){
                    throw new \think\Exception(L_('编号已存在！'));
                }
                $saveData['update_time'] = time();
                (new LifeToolsHouseUnit())->where(['id'=>$id])->save($saveData);
            }else{
                if(!$house_id){
                    throw new \think\Exception(L_('楼盘信息有误！'));
                }
                if(!$pid){
                    throw new \think\Exception(L_('楼栋信息有误！'));
                }
                $info = (new LifeToolsHouseBuilding())->where(['id'=>$pid,'house_id'=>$house_id])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('楼栋不存在/已删除！'));
                }
                //编号是否重复
                $ready = (new LifeToolsHouseUnit())
                    ->where([['house_id','=',$info['house_id']],['p_number','=',$info['serial_number']],['serial_number','=',$saveData['serial_number']]])
                    ->findOrEmpty()->toArray();
                if($ready){
                    throw new \think\Exception(L_('编号已存在！'));
                }
                $saveData['house_id'] = $house_id;
                $saveData['p_number'] = $info['serial_number'];
                $saveData['create_time'] = time();
                (new LifeToolsHouseUnit())->save($saveData);
            }
            return true;
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 删除信息
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function del($params){
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('请选择要删除的信息！'));
        }
        $info = (new LifeToolsHouseUnit())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        Db::startTrans();
        try {
            $where = [
                'f.p_number'=>$info['serial_number'],
                'f.house_id'=>$info['house_id'],
                'f.b_number'=>$info['p_number']
            ];
            $field = 'f.id as f_id,r.id as r_id';
            $data = (new LifeToolsHouseFloor())->getChildren($where,$field);
            $f_ids = $r_ids = [];
            foreach ($data as $item){
                $f_ids[$item['f_id']] = $item['f_id'];
                $r_ids[$item['r_id']] = $item['r_id'];
            }
            (new LifeToolsHouseUnit())->where(['id'=>$id])->delete();
            if($f_ids){
                (new LifeToolsHouseFloor())->where([['id','in',$f_ids],['house_id','=',$info['house_id']]])->delete();
            }
            if($r_ids){
                (new LifeToolsHouseRoom())->where([['id','in',$r_ids],['house_id','=',$info['house_id']]])->delete();
            }
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
    }
}