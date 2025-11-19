<?php


namespace app\community\model\service\Device;


use app\community\model\db\Device\GarbageCategory;
use app\community\model\db\Device\GarbageCollectionPutList;
use app\community\model\db\HouseVillage;
use app\community\model\db\User;
use garbage_collection\GarbageCollection;
use think\facade\Db;

class GarbageCollectionPutService
{
    /**
     * 投放记录列表
     */
    public function putList($param)
    {
        $where = [];
        $field= 'p.id,p.device_no,p.name,p.clnt_type,u.nickname,p.ic_card,p.uid,p.weight,p.score,p.put_time,p.content';
        $order = 'p.put_time desc';
        $where[] = ['p.status','=',2];
        if($param['device_no']){
            $where[] = ['p.device_no','=',$param['device_no']];
        }
        if($param['name']){
            $where[] = ['p.name','like','%'.$param['name'].'%'];
        }
        if($param['clnt_type']){
            $where[] = ['p.clnt_type','=',$param['clnt_type']];
        }
        if($param['nickname']){
            $where[] = ['u.nickname','like','%'.$param['nickname'].'%'];
        }
        if($param['village_id']){
            $where[] = ['p.village_id','=',$param['village_id']];
        }
        if(isset($param['start_put_time']) && $param['start_put_time']){
            $start_put_time=strtotime($param['start_put_time']);
            $where[] = ['p.put_time','>=',$start_put_time];
        }
        if(isset($param['end_put_time']) && $param['end_put_time']){
            $end_put_time=strtotime($param['end_put_time'])+86399;
            $where[] = ['p.put_time','<=',$end_put_time];
        }
        $list = (new GarbageCollectionPutList())->getList($where,$field,$order,$param['pageSize']);
        foreach ($list['data'] as &$item){
            $item['put_time'] = date('Y-m-d H:i:s',$item['put_time']);
            $item['clnt_type_str'] = (new GarbageCollectionPutList())->getClntTypeText($item['clnt_type']);
            $item['score'] = $item['score'] > 0 ? '+'.$item['score'] : '';
            $content = json_decode($item['content'],true);
            $item['type'] = '';
            $item['children'] = [];
            $content['garbage'] = is_array($content['garbage']) ? $content['garbage'] : json_decode($content['garbage'],true);
            if(is_array($content['garbage'])){
                foreach ($content['garbage'] as $k=>$v){
                    if(!isset($v['garbageCategoryMsg'])){
                        $garbageCategoryMsg = $this->getGarbageType($v['type']??0,$v['garbageCategory']??0,false);
                    }else{
                        $garbageCategoryMsg = $v['garbageCategoryMsg']?:'';
                    }
                    $chiScore = bcdiv(strval($v['points']??0),10,2);
                    $item['children'][] = [
                        'id'=>$item['id'].'-'.($k+1),
                        'name'=>$item['name'],
                        'clnt_type'=>$item['clnt_type'],
                        'clnt_type_str'=>$item['clnt_type_str'],
                        'nickname'=>$item['nickname'],
                        'ic_card'=>$item['ic_card'],
                        'uid'=>$item['uid'],
                        'weight'=>$v['weight']??0,
                        'score'=>$chiScore > 0 ? '+'.$chiScore : '',
                        'put_time'=>$item['put_time'],
                        'device_no'=>$item['device_no'],
                        'type'=>$garbageCategoryMsg
                    ];
                }
            }
        }
        return $list;
    }
    /**
     * 获取垃圾类型
     */
    public function getGarbageType($type,$garbageCategory,$pull=true)
    {
        $typeMsg = (new GarbageCollectionPutList())->getGarbageType($type);
        if(!$garbageCategory){
            return $typeMsg;
        }
        $garbageCategoryInfo = (new GarbageCategory())->where('category_id',$garbageCategory)->field('name')->find();
        if($garbageCategoryInfo){
            return $typeMsg.'-'.$garbageCategoryInfo['name'];
        }
        
        if(!$pull){
            return $typeMsg;
        }

        //查询最新垃圾类型
        $garbageCollection = new GarbageCollection();
        $newList = $garbageCollection->pullGarbageCategory();
        $newList = $newList ?? [];
        $newAry = array_column($newList,'name','id');
        $garbageCategoryMsg = $newAry[$garbageCategory]??'';
        
        $oldList = (new GarbageCategory())->field('category_id,name')->select()->toArray();
        $oldAry = array_column($oldList,'name','category_id');
        $oldIdAry = array_column($oldList,'category_id');
        
        $addData = [];
        $updateData = [];
        foreach ($newList as $v){
            if(!in_array($v['id'],$oldIdAry)){//需要新增
                $addData[] = [
                    'category_id'=>$v['id'],
                    'name'=>$v['name'],
                    'standard_type'=>$v['standardType'],
                    'date_created'=>strtotime($v['dateCreated']),
                    'add_time'=>time(),
                ];
                continue;
            }
            if($v['name'] != $oldAry[$v['id']]){
                $updateData[] = [
                    'category_id'=>$v['id'],
                    'name'=>$v['name']
                ];
            }
        }

        Db::startTrans();
        try {
            if($updateData){
                foreach ($updateData as $data){
                    (new GarbageCategory())->where('category_id',$data['category_id'])
                        ->update(['name'=>$data['category_id']]);
                }
            }
            if($addData){
                (new GarbageCategory())->insertAll($addData);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw_exception($e->getMessage());
        }

        $garbageCategoryMsg = $garbageCategoryMsg ? $typeMsg.'-'.$garbageCategoryMsg : $typeMsg;
        return $garbageCategoryMsg;
    }
    /**
     * 投放详情
     */
    public function putDetail($param)
    {
        if(!$param['id']){
            throw_exception(L_('查看对象不能为空'));
        }
        $info = (new GarbageCollectionPutList())->getDetail(['id'=>$param['id']],'id,device_no,name,village_id,clnt_type,uid,ic_card,weight,score,description,put_time,add_time'); 
        if(!$info){
            throw_exception(L_('未查询到投放信息'));
        }
        $info['village_name'] = $info['village_id'] ? (new HouseVillage())->where('village_id',$info['village_id'])->value('village_name') : '';
        $info['clnt_type_str'] = (new GarbageCollectionPutList())->getClntTypeText($info['clnt_type']);
        $info['nickname'] = (new User())->where('uid',$info['uid'])->value('nickname');
        $info['put_time'] = date('Y-m-d H:i:s',$info['put_time']);
        $info['add_time'] = date('Y-m-d H:i:s',$info['add_time']);
        return $info;
    }
}