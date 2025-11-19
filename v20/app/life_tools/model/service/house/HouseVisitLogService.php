<?php


namespace app\life_tools\model\service\house;


use app\life_tools\model\db\LifeToolsHouseVisitLog;

class HouseVisitLogService
{
    /**
     * 保存浏览记录
     * @param $uid
     * @param $visit_type
     * @param $house_id
     * @return bool
     */
    public function saveLog($uid,$visit_type='normal',$house_id=0){
        //当前用户是否已存在浏览记录
        $log_where = [
            'uid' => $uid,
        ];
        $visit_log = (new LifeToolsHouseVisitLog())->where($log_where)->findOrEmpty()->toArray();
        $log_data = [
            'last_time'=>time(),
            'visit_type' => $visit_type,
        ];
        if(!$house_id){
            $log_data['title'] = '首页';
        }else{
            $log_data['house_id'] = $house_id;
            $log_data['title'] = '详情页';
        }
        if($visit_log){
            $log_data['views'] = ($visit_log['views']+1);
            (new LifeToolsHouseVisitLog())->where($log_where)->save($log_data);
        }else{
            $log_data = [
                'uid' => $uid,
                'add_time' => time(),
                'views' => 1,
            ];
            (new LifeToolsHouseVisitLog())->save($log_data);
        }
        return true;
    }

    /**
     * 浏览记录
     * @param $params
     * @return array
     */
    public function getList($params){
        $visit_type = $params['visit_type'];
        $search_type = $params['search_type'];
        $keywords = $params['keywords'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $end_time = $params['end_time']?strtotime($params['end_time']):0;
        $start_time = $params['start_time']?strtotime($params['start_time']):0;
        $where[] = ['h.is_del','=',0];
        if($keywords){
            if($search_type==1){
                $where[] = ['u.nickname','like','%'.$keywords.'%'];
            }elseif($search_type==2){
                $where[] = ['u.phone','=',$keywords];
            }elseif($search_type==3){
                $where[] = ['v.views','>=',$keywords];
            }
        }
        if($start_time&&$end_time){
            $where[] = ['v.last_time','between',[$start_time,$end_time]];
        }
        if(in_array($visit_type,['normal','scan','wechat'])){
            $where[] = ['v.visit_type','=',$visit_type];
        }
        $field = 'v.*,u.nickname,u.phone,h.title as h_title';
        $list = (new LifeToolsHouseVisitLog())->getList($where,$field,$page,$pageSize);
        $visit_type_txt = ['normal'=>'普通进入','scan'=>'扫码进入','wechat'=>'微信分享'];
        foreach ($list['data'] as $key=>$item){
            $list['data'][$key]['last_time'] = $item['last_time']?date('Y-m-d H:i:s',$item['last_time']):'';
            $list['data'][$key]['visit_type'] = $visit_type_txt[$item['visit_type']]??'';
            $list['data'][$key]['title'] = $item['h_title']?:$item['title'];
        }
        return $list;
    }
}