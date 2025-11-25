<?php


namespace app\life_tools\model\service\house;


use app\life_tools\model\db\LifeToolsHouseBuilding;
use app\life_tools\model\db\LifeToolsHouseFloor;
use app\life_tools\model\db\LifeToolsHouseRoom;
use app\life_tools\model\db\LifeToolsHouseUnit;
use think\facade\Db;

class HouseBuildingService
{
    /**
     * 获取列表
     */
    public function getList($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $house_id = $params['house_id'];
        if(!$house_id){
            throw new \think\Exception(L_('请选择楼盘！'));  
        }
        $where[] = ['house_id','=',$house_id];
        $list = (new LifeToolsHouseBuilding())->where($where)->order('sort asc,serial_number asc,id asc')->paginate($pageSize);
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
        $info = (new LifeToolsHouseBuilding())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        try {
            (new LifeToolsHouseBuilding())->where(['id'=>$id])->save(['status'=>$status]);
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
        try {
            if($id){
                $info = (new LifeToolsHouseBuilding())->where(['id'=>$id])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('信息不存在/已删除！'));
                }
                //编号是否重复
                $ready = (new LifeToolsHouseBuilding())
                    ->where([['id','<>',$id],['house_id','=',$info['house_id']],['serial_number','=',$saveData['serial_number']]])
                    ->findOrEmpty()->toArray();
                if($ready){
                    throw new \think\Exception(L_('编号已存在！'));
                }
                $saveData['update_time'] = time();
                (new LifeToolsHouseBuilding())->where(['id'=>$id])->save($saveData);
            }else{
                if(!$house_id){
                    throw new \think\Exception(L_('楼盘信息有误！'));
                }
                //编号是否重复
                $ready = (new LifeToolsHouseBuilding())
                    ->where([['house_id','=',$house_id],['serial_number','=',$saveData['serial_number']]])
                    ->findOrEmpty()->toArray();
                if($ready){
                    throw new \think\Exception(L_('编号已存在！'));
                }
                $saveData['house_id'] = $house_id;
                $saveData['create_time'] = time();
                (new LifeToolsHouseBuilding())->save($saveData);
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
        $info = (new LifeToolsHouseBuilding())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        Db::startTrans();
        try {
            $where = [
                'u.p_number'=>$info['serial_number'],
                'u.house_id'=>$info['house_id']
            ];
            $field = 'u.id as u_id,f.id as f_id,r.id as r_id';
            $data = (new LifeToolsHouseUnit())->getChildren($where,$field);
            $u_ids = $f_ids = $r_ids = [];
            foreach ($data as $item){
                $u_ids[$item['u_id']] = $item['u_id'];
                $f_ids[$item['f_id']] = $item['f_id'];
                $r_ids[$item['r_id']] = $item['r_id'];
            }
            (new LifeToolsHouseBuilding())->where(['id'=>$id])->delete();
            if($u_ids){
                (new LifeToolsHouseUnit())->where([['id','in',$u_ids],['house_id','=',$info['house_id']]])->delete();
            }
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

    /**
     * 获取下一个编号
     * @param $params
     * @return int[]
     * @throws \think\Exception
     */
    public function getLastNumber($params){
        $pid = $params['pid'];
        $type = $params['type']; //building-楼栋，unit-单元，floor-楼层，room-房间
        $where = [];
        switch ($type){
            case 'building':
                $where[] = ['house_id','=',$pid];
                $model = new LifeToolsHouseBuilding();
                break;
            case 'unit':
                $info = (new LifeToolsHouseBuilding())->where(['id'=>$pid])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('楼栋信息不存在/已删除'));
                }
                $where = [
                    ['p_number','=',$info['serial_number']],
                    ['house_id','=',$info['house_id']],
                ];
                $model = new LifeToolsHouseUnit();
                break;
            case 'floor':
                $info = (new LifeToolsHouseUnit())->where(['id'=>$pid])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('单元信息不存在/已删除'));
                }
                $where = [
                    ['p_number','=',$info['serial_number']],
                    ['house_id','=',$info['house_id']],
                    ['b_number','=',$info['p_number']],
                ];
                $model = new LifeToolsHouseFloor();
                break;
            case 'room':
                $info = (new LifeToolsHouseFloor())->where(['id'=>$pid])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('楼栋信息不存在/已删除'));
                }
                $where = [
                    ['p_number','=',$info['serial_number']],
                    ['house_id','=',$info['house_id']],
                    ['b_number','=',$info['b_number']],
                    ['u_number','=',$info['p_number']],
                ];
                $model = new LifeToolsHouseRoom();
                break;
            default:
                throw new \think\Exception(L_('类型错误'));
        }
        $number = $model->where($where)->max('serial_number');
        $next_number = $number+1;
        return ['next_number' => $next_number];
    }
}