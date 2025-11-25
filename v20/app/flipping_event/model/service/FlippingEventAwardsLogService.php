<?php


namespace app\flipping_event\model\service;


use app\common\model\db\User;
use app\flipping_event\model\db\FlippingEventAwards;
use app\flipping_event\model\db\FlippingEventAwardsLog;

class FlippingEventAwardsLogService
{
    /**
     * 中奖列表
     * @param $params
     * @return array|mixed
     */
    public function getAwardLogList($params){
        $search_type = $params['search_type'];
        $keywords = $params['keywords'];
        $start_time = $params['start_time'];
        $end_time = $params['end_time'];
        $page = $params['page']??0;
        $pageSize = $params['pageSize']??10;
        $id = $params['id']??0;
        $where = [];
        if($keywords){
            if($search_type=='username'){
                $where[] = ['u.nickname','like','%'.$keywords.'%'];
            }elseif($search_type=='phone'){
                $where[] = ['u.phone','=',$keywords];
            }elseif($search_type=='awardsname'){
                $where[] = ['l.awards_title','like','%'.$keywords.'%'];
            }
        }
        if($start_time&&$end_time){
            $where[] = ['l.create_time','between',[strtotime($start_time),strtotime($end_time)]];
        }
        if($id){
            $where[] = ['l.event_id','=',$id];
        }
        $where[] = ['l.awards_type','<>',2];
        $field = 'l.id,l.awards_title,l.awards_type,l.create_time,u.phone,u.nickname';
        $list = (new FlippingEventAwardsLog())->getList($where,$field,$page,$pageSize);
        $data = $page>0?$list['data']:$list;
        foreach ($data as $key=>$val){
            $data[$key]['awards_txt'] = $val['awards_type']?'免单':'普通卡牌';
            $data[$key]['create_time'] = $val['create_time']?date('Y-m-d H:i:s',$val['create_time']):'';
        }
        if($page>0){
            $list['data'] = $data;
        }else{
            $list = $data;
        }
        return $list;
    }

    /**
     * 导出
     * @param $params
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\Exception
     */
    public function exportAwardLogList($params){
        $data = $this->getAwardLogList($params);
        $title = '中奖列表';
        $xlsCell = [
            ['id', 'ID'],
            ['create_time', '中奖日期'],
            ['nickname', '昵称'],
            ['phone', '手机号'],
            ['awards_title', '奖品'],
            ['awards_txt', '奖品类型'],
        ];
        $fileName = date("Y-m-d", time()) . '-' . $params['rand_number'];
        $exportResult = export_excel($title, $xlsCell, $data, $fileName);
        return ['file_url' => $exportResult['url']];
    }

    /**
     * 获取用户已获取卡牌列表
     */
    public function getUserAwards($params){
        $uid = $params['uid'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $id = $params['id'];
        $status = $params['status'];
        if(!$id){
            throw new \think\Exception(L_('活动不存在！'));
        }
        $where = [
            ['l.uid','=',$uid],
            ['l.event_id','=',$id],
            ['l.awards_type','=',0]
        ];
        if($status>-1){
            $where[] = ['l.status','=',$status];
        }else{
            $where[] = ['l.status','<>',2];
        }
        $field = 'l.id,l.awards_title,u.phone,u.nickname,a.img,l.status,a.can_give,count(l.awards_id) as total';
        $list = (new FlippingEventAwardsLog())->getList($where,$field,$page,$pageSize,'l.awards_id,l.status');
        foreach ($list['data'] as $key=>$val){
            $list['data'][$key]['img'] = $val['img']?replace_file_domain($val['img']):'';
        }
        return $list;
    }

    /**
     * 接收赠送卡牌
     */
    public function acceptingCards($params){
        $give_uid = $params['give_uid'];
        $uid = $params['uid'];
        $id = $params['id'];
        if(!$give_uid||!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $give_user = (new User())->where(['uid'=>$uid])->findOrEmpty()->toArray();
        if(!$give_user){
            throw new \think\Exception(L_('赠送人不存在！'));
        }
        $where = ['uid'=>$give_uid,'id'=>$id];
        $awards_log = (new FlippingEventAwardsLog())->where($where)->findOrEmpty()->toArray();
        if(!$awards_log){
            throw new \think\Exception(L_('卡牌不存在！'));
        }
        if($awards_log['status']!=1){
            throw new \think\Exception(L_('卡牌未解封，无法获取！'));
        }
        $awards = (new FlippingEventAwards())->where(['id'=>$awards_log['awards_id']])->findOrEmpty()->toArray();
        if(!$awards||$awards['can_give']!=1){
            throw new \think\Exception(L_('卡牌不支持赠送！'));
        }
        try {
            (new FlippingEventAwardsLog())->where($where)->save(['uid'=>$uid,'give_uid'=>$give_uid]);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }
}