<?php


namespace app\community\model\service;

use app\community\model\db\AreaStreet;
use app\community\model\db\AreaStreetPartyBranch;
use app\community\model\db\HouseVillage;
use app\community\model\db\streetPartyBindCommunity;
class PartyBranchService
{
    public $PartyBranchType=[
        ['key'=>1,'value'=>'党工委','src'=>'/static/images/house/community_committee/dgw.png'],
        ['key'=>2,'value'=>'党委','src'=>'/static/images/house/community_committee/dw.png'],
        ['key'=>3,'value'=>'党总支','src'=>'/static/images/house/community_committee/dzz.png'],
        ['key'=>4,'value'=>'党支部','src'=>'/static/images/house/community_committee/dzb.png'],
        ['key'=>5,'value'=>'机关党支部','src'=>'/static/images/house/community_committee/jgdzb.png']
    ];

    public $PartyBranchTypeSing=[
        1=>'党工委',
        2=>'党委',
        3=>'党总支',
        4=>'党支部',
        5=>'机关党支部'
    ];

    /**
     * Notes: 获取党支部列表
     * @param $street_id
     * @param $keyword
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/14 9:34
     */
    public function getList($street_id,$keyword,$page,$limit)
    {
        $dbAreaStreetPartyBranch = new AreaStreetPartyBranch();
        if($keyword)
        {
            $where[] = ['name','like','%'.$keyword.'%'];
        }
        $where[] = ['street_id','=',$street_id];
        $count = $dbAreaStreetPartyBranch->getCount($where);
        $list = $dbAreaStreetPartyBranch->getSome($where,true,'sort desc,id desc',$page,$limit);
        foreach ($list as $key=>&$val){
            $val['type']=!empty($val['type']) ? $this->PartyBranchTypeSing[$val['type']] : '--';
            $val['adress']=!empty($val['adress']) ? $val['adress'] : '--';
            $val['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
        }
        $data['list'] = $list;
        $data['count'] = $count;
        return $data;
    }

    /**
     * Notes:
     * @param $street_id
     * @param $party_id
     * @param $area_type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: weili
     * @datetime: 2020/9/14 18:01
     */
    public function getCommunity($street_id,$party_id,$area_type=0)
    {
        $db_area_street = new AreaStreet();
        $dbHouseVillage = new HouseVillage();
        $dbStreetPartyBindCommunity = new streetPartyBindCommunity();
        $where[] = ['area_pid','=',$street_id];
        $where[] = ['area_type','=',1];//社区
        $field = 'area_id,area_name,area_type';
        $data = $db_area_street->getLists($where,$field,0);
        $data = $data->toArray();
        $area_id = array_column($data,'area_id');
        $village_field = 'village_id,village_name,community_id,street_id';
        $map[] = ['street_id','=',$street_id];
        $map[] = ['community_id','in',$area_id];
        $map[] = ['status','=',1];
        $village_list = $dbHouseVillage->getList($map,$village_field,'','','village_id asc');
        $maps[] = ['street_id','=',$street_id];
        $field='street_id,community_id,plot_id';
        $plot_arr = $dbStreetPartyBindCommunity->getSome($maps,$field,'id desc');
        $party_plot_id = [];
        if($party_id) {
            $wheres[] = ['party_id','=',$party_id];
            $wheres[] = ['street_id','=',$street_id];
            $partyBind = $dbStreetPartyBindCommunity->getOne($wheres,'plot_id');
            $party_plot_id = explode(',',$partyBind['plot_id']);
        }
        $new_plot = [];
        $community_id=[];
        $plot_id = '';
        foreach ($plot_arr as $key=>$val){
            $community_id[] = $val['community_id'];
            $plot_id .= ','.$val['plot_id'];
            $new_plot = $plot_id;
        }
        $community_id = array_unique($community_id);
        if ($new_plot) {
            $new_plot = array_unique(explode(',',ltrim($new_plot,',')));
            $new_plot = array_diff($new_plot,$party_plot_id);
        }
        $new_arr = [];
        foreach ($data as $key=>&$val){
            $val['community'] = [];
            $new_arr[$key]['key'] =$val['area_id'];
            $new_arr[$key]['name'] =$val['area_name'];
            if (1==$area_type) {
                $new_arr[$key]['disabled'] = true;
            } elseif( (isset($new_plot) && count($new_plot)>0) && in_array($val['area_id'],$community_id)){
                $new_arr[$key]['disabled'] = true;
            }

            $new_arr[$key]['child'] =[];
            foreach ($village_list as $k=>$v)
            {
                if($val['area_id'] == $v['community_id']){
                    $val['community'][] = $v;
                    $new_arr[$key]['child'][$k] =[
                        'name'=>$v['village_name'],
                        'key'=>$val['area_id'].'-'.$v['village_id'],
                    ];
                    if (1==$area_type) {
                        $new_arr[$key]['child'][$k]['disableCheckbox'] = true;
                    } elseif(isset($new_plot) && in_array($v['village_id'],$new_plot)){
                        $new_arr[$key]['child'][$k]['disableCheckbox'] = true;
                    }
                }
            }
            $new_arr[$key]['child'] = array_values($new_arr[$key]['child']);
        }

        return $new_arr;
    }

    /**
     * Notes: 添加党建支部
     * @param $data
     * @param $street_id
     * @param $id
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/14 18:01
     */
    public function addPartyBranch($data,$street_id,$id)
    {
        $dbAreaStreetPartyBranch = new AreaStreetPartyBranch();
        $dbstreetPartyBindCommunity = new streetPartyBindCommunity();
        if(array_key_exists('community',$data))
        {
            $community = $data['community'];
            unset($data['community']);
        }else{
            $community = [];
        }
        $new_arr = [];
        $community_arr = [];//初始化社区id数组;
        if(is_array($community) && count($community) >0){
            foreach ($community as $k=>$v)
            {
                if(strpos($v,'-') !== false){
                    $new_arr[] = explode('-',$v);
                }else{
                    $community_arr[] = $v;
                }
            }
        }
        $data['street_id'] = $street_id;
        if(!$id)
        {
            $data['create_time'] = time();
            $party_id = $dbAreaStreetPartyBranch->addFind($data);
        }else{
            $where[] = ['id','=',$id];
            $res = $dbAreaStreetPartyBranch->updateThis($where,$data);
            $party_id = $id;
        }
        $insert_arr = [];
        $arr =[];
        if($new_arr && count($new_arr)>0){
            foreach ($new_arr as $key=>$val)
            {
                $arr[$val[0]][] = $val[1];
            }
        }
        $keys = [];
        if(count($arr)>0){
            foreach ($arr as $key=>$val){
                $plot_id = implode(',',$val);
                $insert_arr[] = [
                    'party_id'=>$party_id,
                    'street_id'=>$street_id,
                    'community_id'=>$key,
                    'plot_id'=>$plot_id,
                ];
                $keys[] = $key;
            }
        }
        if(!$id)
        {
            $dbstreetPartyBindCommunity->addAll($insert_arr);
        }else{
            foreach ($insert_arr as $key=>$val)
            {
                $map['party_id'] = $val['party_id'];
                $map['street_id'] = $val['street_id'];
                $map['community_id'] = $val['community_id'];
                $info = $dbstreetPartyBindCommunity->getOne($map);
                if($info){
                    $dbstreetPartyBindCommunity->updateThis($map,['plot_id'=>$val['plot_id']]);
                }else{
                    $dbstreetPartyBindCommunity->add($val);
                }
            }
            $del_where[]=['party_id','=',$party_id];
            $del_where[]=['street_id','=',$street_id];
            $del_where[]=['community_id','not in',$keys];
            $dbstreetPartyBindCommunity->del($del_where);
        }
        $list['new_arr'] = $new_arr;
        $list['data'] = $data;
        $list['insert_arr'] = $insert_arr;
        return $list;
    }

    /**
     * Notes: 获取党建支部详情
     * @param $id
     * @return array
     * @author: weili
     * @datetime: 2020/9/14 18:02
     */
    public function getPartyInfo($id)
    {
        if(!$id)
        {
            return [];
        }
        $dbAreaStreetPartyBranch = new AreaStreetPartyBranch();
        $dbstreetPartyBindCommunity = new streetPartyBindCommunity();
        $where[] = ['id','=',$id];
        $info = $dbAreaStreetPartyBranch->getOne($where);
        if($info){
            $map[] = ['party_id','=',$info['id']];
            $map[] = ['street_id','=',$info['street_id']];
            $community =$dbstreetPartyBindCommunity->getSome($map);
            $new_plot = [];
            foreach ($community as $key=>$val){
                $plot_id = explode(',',$val['plot_id']);
                foreach ($plot_id as $k=>$v)
                {
                    $new_plot[] = $val['community_id'].'-'.$v;
                }
            }
            $info['community'] = $new_plot;
        }
        $data['info'] = $info;
        return $data;
    }

    /**
     * 删除党支部
     * @author: liukezhu
     * @date : 2022/4/23
     * @param $street_id
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function delPartyBranch($street_id,$id){
        $dbAreaStreetPartyBranch = new AreaStreetPartyBranch();
        $dbstreetPartyBindCommunity = new streetPartyBindCommunity();
        $party_id = $dbAreaStreetPartyBranch->del([
            ['street_id','=',$street_id],
            ['id','=',$id]
        ]);
        if($party_id){
            $dbstreetPartyBindCommunity->del([
                ['party_id','=',$id],
                ['street_id','=',$street_id]
            ]);
        }
        return true;
    }

    /**
     * 查询党支部集合
     * @author: liukezhu
     * @date : 2022/10/29
     * @param $street_id
     * @return mixed
     */
    public function getPartyBranchAll($street_id){
        $where[] = ['street_id','=',$street_id];
        $where[] = ['status','=',0];
        $field = 'id,name';
        $list = (new AreaStreetPartyBranch())->getList($where,$field,'sort desc,id desc');
        return $list;
    }
}