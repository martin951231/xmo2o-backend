<?php


namespace app\complaint\model\service;


use app\complaint\model\db\ComplaintGroup;

class ComplaintGroupService
{
    /**
     * 获取投诉列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($params){
        $page = $params['page']?:1;
        $pageSize = $params['pageSize']?:10;
        $where[] = ['is_del', '=', 0];
        $list = (new ComplaintGroup())->getList($where,$page,$pageSize);
        
        foreach ($list['data'] as $key=>$value){
            $list['data'][$key]['link_url'] = cfg('site_url').'/packapp/plat/pages/my/complaint/submit?type='.$value['type'];
        }
        return $list;
    }

    /**
     * 添加/编辑
     */
    public function saveData($params){
        $id = $params['id']??0;
        $name = $params['name']?:'';
        $type = $params['type']?:'';
        $sort = $params['sort']?:'';
        if(!$name){
            throw new \think\Exception(L_('场景名称不能为空！'));
        }
        if(!$type){
            throw new \think\Exception(L_('场景类型不能为空！'));
        }
        if($sort>9998){
            throw new \think\Exception(L_('排序值不能超过9998！'));
        }
        $saveData = [
            'name' => $name,
            'type' => $type,
            'sort' => $sort,
        ];
        if($id){
            $check_name = (new ComplaintGroup())->where([['name','=',$name],['id','<>',$id],['is_del','=',0]])->findOrEmpty()->toArray();
            if($check_name){
                throw new \think\Exception(L_('场景名称已存在！'));
            }
            $check_type = (new ComplaintGroup())->where([['type','=',$type],['id','<>',$id],['is_del','=',0]])->findOrEmpty()->toArray();
            if($check_type){
                throw new \think\Exception(L_('场景类型已存在！'));
            }
            $info = (new ComplaintGroup())->where(['id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
            if(!$info){
                throw new \think\Exception(L_('修改信息不存在！'));
            }
            if($info['is_system']==1){
                throw new \think\Exception(L_('系统场景无法修改！'));
            }
            $res = (new ComplaintGroup())->where(['id'=>$id])->save($saveData);
        }else{
            $check_name = (new ComplaintGroup())->where(['name'=>$name,'is_del'=>0])->findOrEmpty()->toArray();
            if($check_name){
                throw new \think\Exception(L_('场景名称已存在！'));
            }
            $check_type = (new ComplaintGroup())->where(['type'=>$type,'is_del'=>0])->findOrEmpty()->toArray();
            if($check_type){
                throw new \think\Exception(L_('场景类型已存在！'));
            }
            $saveData['is_system'] = 0;
            $saveData['create_time'] = time();
            $res = (new ComplaintGroup())->save($saveData);
        }
        if($res===false){
            throw new \think\Exception(L_('操作失败！'));
        }
        return true;
    }

    /**
     * 删除
     * @param $params
     */
    public function del($params){
        $id = $params['id'];
        $info = (new ComplaintGroup())->where(['id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息已删除！'));
        }
        if($info['is_system']==1){
            throw new \think\Exception(L_('系统场景无法删除！'));
        }
        $res = (new ComplaintGroup())->where(['id'=>$id,'is_del'=>0])->save(['is_del'=>1]);
        if(!$res){
            throw new \think\Exception(L_('删除失败！'));
        }
        return true;
    }
}