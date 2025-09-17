<?php


namespace app\merchant\model\service;


use app\merchant\model\db\ShopSlider;

class ShopSliderService
{
    /**
     * 获取外卖导航列表
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function getSlider($params){
        $page = $params['page']?:0;
        $pageSize = $params['pageSize']?:10;
        $store_id = $params['store_id']?:0;
        if(!$store_id){
            throw new \think\Exception(L_('店铺参数错误！'));
        }
        $where[] = ['store_id','=',$store_id];
        $where[] = ['is_del','=',0];
        $list = (new ShopSlider())->getList($where,$page,$pageSize);
        foreach($list as $val){
            $val['last_time'] = $val['last_time']?date('Y-m-d H:i:s'):'';
            $val['pic'] = $val['pic'] ? replace_file_domain($val['pic']):'';
            $val['status_txt'] = $val['status'] ? '开启':'关闭';
        }
        return $list;
    }

    /**
     * 外卖导航-添加/编辑
     * @return \think\response\Json
     */
    public function saveSlider($params){
        $name = $params['name'];
        $pic = $params['pic'];
        $url = $params['url'];
        $store_id = $params['store_id'];
        $sort = $params['sort'];
        $status = $params['status'];
        $id = $params['id']??0;
        $mer_id = $params['mer_id'];
        if(!$name){
            throw new \think\Exception(L_('导航名称不能为空！'));
        }
        if(!$pic){
            throw new \think\Exception(L_('导航图片不能为空！'));
        }
        if(!$url){
            throw new \think\Exception(L_('导航地址不能为空！'));
        }
        if(!$store_id){
            throw new \think\Exception(L_('店铺不能为空！'));
        }
        if(!is_numeric($sort)){
            throw new \think\Exception(L_('排序值错误！'));
        }
        if(!in_array($status,[0,1])){
            throw new \think\Exception(L_('无效的状态！'));
        }
        $saveData = [
            'name' => $name,
            'pic' => $pic,
            'url' => $url,
            'store_id' => $store_id,
            'sort' => $sort,
            'status' => $status,
            'last_time' => time()
        ];
        try {
            if($id){
                //查询信息是否存在
                $info = (new ShopSlider())->where(['id'=>$id,'store_id'=>$store_id,'is_del'=>0])->find();
                if(empty($info)||!$info){
                    throw new \think\Exception(L_('修改信息不存在/已删除！'));
                }
                (new ShopSlider())->where(['id'=>$id])->save($saveData);
            }else{
                $saveData['create_time'] = time();
                $saveData['mer_id'] = $mer_id;
                (new ShopSlider())->save($saveData);
            }
            return true;
        }catch(\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
    }

    /**
     * 外卖导航-删除
     * @return \think\response\Json
     */
    public function delSlider($params){
        $store_id = $params['store_id'];
        $id = $params['id']??0;
        $info = (new ShopSlider())->where(['id'=>$id,'store_id'=>$store_id,'is_del'=>0])->find();
        if(empty($info)||!$info){
            throw new \think\Exception(L_('信息已删除/不存在！'));
        }
        $res = (new ShopSlider())->where(['id'=>$id,'store_id'=>$store_id,'is_del'=>0])->save(['is_del'=>1]);
        if($res){
            return true;
        }else{
            throw new \think\Exception(L_('删除失败！')); 
        }
    }

    /**
     * 外卖导航-详情
     * @param $params
     * @return array|\think\Model|null
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function showSlider($params){
        $store_id = $params['store_id'];
        $id = $params['id']??0;
        $info = (new ShopSlider())->field('id,name,url,pic,sort,status')->where(['id'=>$id,'store_id'=>$store_id,'is_del'=>0])->find();
        if(empty($info)||!$info){
            throw new \think\Exception(L_('信息已删除/不存在！'));
        }
        $info['pic'] = $info['pic']?replace_file_domain($info['pic']):'';
        return $info;
    }
}