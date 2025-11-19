<?php


namespace app\complaint\model\service;


use app\complaint\model\db\ComplaintGroupType;

class ComplaintGroupTypeService
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
        $gid = $params['gid'];
        if(!$gid){
            throw new \think\Exception(L_('请选择场景类型！'));
        }
        $where[] = ['gid', '=', $gid];
        $list = (new ComplaintGroupType())->getList($where,$page,$pageSize);
        
        return $list;
    }

    /**
     * 添加/编辑
     */
    public function saveData($params){
        $id = $params['id']??0;
        $name = $params['name']?:'';
        $sort = $params['sort']?:'';
        $gid = $params['gid']?:'';
        if(!$name){
            throw new \think\Exception(L_('举报类型名称不能为空！'));
        }
        if($sort>9998){
            throw new \think\Exception(L_('排序值不能超过9998！'));
        }
        $saveData = [
            'name' => $name,
            'sort' => $sort,
        ];
        if($id){
            $info = (new ComplaintGroupType())->where(['id'=>$id])->findOrEmpty()->toArray();
            if(!$info){
                throw new \think\Exception(L_('修改信息不存在！'));
            }
            $check_name = (new ComplaintGroupType())->where([['name','=',$name],['id','<>',$id],['gid','=',$info['gid']]])->findOrEmpty()->toArray();
            if($check_name){
                throw new \think\Exception(L_('举报类型名称已存在！'));
            }
            $res = (new ComplaintGroupType())->where(['id'=>$id])->save($saveData);
        }else{
            if(!$gid){
                throw new \think\Exception(L_('请选择场景类型！'));
            }
            $saveData['gid'] = $gid;
            $check_name = (new ComplaintGroupType())->where(['name'=>$name,'gid'=>$gid])->findOrEmpty()->toArray();
            if($check_name){
                throw new \think\Exception(L_('举报类型名称已存在！'));
            }
            $saveData['is_system'] = 0;
            $saveData['create_time'] = time();
            $res = (new ComplaintGroupType())->save($saveData);
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
        $info = (new ComplaintGroupType())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息已删除！'));
        }
        $res = (new ComplaintGroupType())->where(['id'=>$id])->delete();
        if(!$res){
            throw new \think\Exception(L_('删除失败！'));
        }
        return true;
    }
}