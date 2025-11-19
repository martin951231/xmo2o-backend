<?php


namespace app\appoint\model\service;


use app\appoint\model\db\AppointRewardMoney;

class AppointRewardMoneyService
{
    /**
     * 获取列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(){
        $list = (new AppointRewardMoney())->select()->toArray();
        foreach ($list as $key=>$item){
            $list[$key]['pic'] = replace_file_domain($item['pic']);
        }
        return $list;
    }

    /**
     * 添加/编辑
     */
    public function saveData($params){
        $id = $params['id']??0;
        $from_type = $params['from_type'];
        $savedata = [
            'name' => $params['name'],
            'pic' => $params['pic'],
            'sort' => $params['sort'],
            'money' => $params['money'],
            'status' => $params['status'],
        ];
        if(!$savedata['name']){
            throw new \think\Exception(L_('名称不能为空！'));
        }
        if(!$savedata['pic']){
            throw new \think\Exception(L_('请上传图片！'));
        }
        if($savedata['sort']<0||!is_numeric($savedata['sort'])){
            throw new \think\Exception(L_('排序值必须是大于0的整数！'));
        }
        if($savedata['status']<0||!in_array($savedata['status'],[0,1])){
            throw new \think\Exception(L_('状态有误！'));
        }
        try {
            if($from_type==2){
                $info = (new AppointRewardMoney())->where(['id'=>$id])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('信息不存在！'));
                }
                (new AppointRewardMoney())->where(['id'=>$id])->save($savedata);
            }else{
                $savedata['add_time'] = time();
                (new AppointRewardMoney())->save($savedata);
            }
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 删除
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function del($params){
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('请选择要删除信息！'));
        }
        $info = (new AppointRewardMoney())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        try {
            (new AppointRewardMoney())->where(['id'=>$id])->delete();
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 修改状态/排序值
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function changeInfo($params){
        $id = $params['id'];
        $status = $params['status']??0;
        $sort = $params['sort']??0;
        $from_type = $params['from_type'];
        if(!$id){
            throw new \think\Exception(L_('请选择要修改信息！'));
        }
        $saveData = [];
        if($from_type==1){
            if(!in_array($status,[0,1])){
                throw new \think\Exception(L_('状态有误！'));
            }
            $saveData['status'] = $status;
        }else{
            if($sort<0||!is_numeric($sort)){
                throw new \think\Exception(L_('排序值必须是大于0的整数！'));
            }
            $saveData['sort'] = $sort;
        }
        $info = (new AppointRewardMoney())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        try {
            (new AppointRewardMoney())->where(['id'=>$id])->save($saveData);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }
}