<?php


namespace app\life_tools\model\service\house;


use app\life_tools\model\db\LifeToolsHouseSpreadUser;

class HouseSpreadService
{
    /**
     * 获取用户的分销人员
     * @param $params
     */
    public function getSpreadUserList($params){
        $uid = $params['uid'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where[] = ['s.p_uid','=',$uid];
        $field = 's.id,s.add_time,u.uid,u.nickname,u.avatar';
        $list = (new LifeToolsHouseSpreadUser())->getList($where,$field,$page,$pageSize);
        foreach ($list['data'] as $key=>$item){
            $list['data'][$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
            $list['data'][$key]['avatar'] = $item['avatar']?replace_file_domain($item['avatar']):'';
        }
        return $list;
    }
}