<?php


namespace app\flipping_event\model\service;


use app\flipping_event\model\db\FlippingEventRulesLog;

class FlippingEventRulesLogService
{
    /**
     * 中奖列表
     * @param $params
     * @return array|mixed
     */
    public function getRulesLogList($params){
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
            }elseif($search_type=='rulesname'){
                $where[] = ['l.rules_title','like','%'.$keywords.'%'];
            }
        }
        if($start_time&&$end_time){
            $where[] = ['l.create_time','between',[strtotime($start_time),strtotime($end_time)]];
        }
        if($id){
            $where[] = ['l.event_id','=',$id];
        }
        $field = 'l.id,l.rules_title,l.rules_type,l.create_time,l.rules_awards,l.status,u.phone,u.nickname';
        $list = (new FlippingEventRulesLog())->getList($where,$field,$page,$pageSize);
        $data = $page>0?$list['data']:$list;
        $rules_type_txt = [0=>'实物',1=>'余额',2=>'积分'];
        foreach ($data as $key=>$val){
            $data[$key]['rules_txt'] = $rules_type_txt[$val['rules_type']]??'';
            $data[$key]['create_time'] = $val['create_time']?date('Y-m-d H:i:s',$val['create_time']):'';
            $data[$key]['rules_awards'] = $val['rules_awards']?:'';
            $data[$key]['status_txt'] = $val['status']?'已入账':'未入账';
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
    public function exportRulesLogList($params){
        $data = $this->getRulesLogList($params);
        $title = '中奖列表';
        $xlsCell = [
            ['id', 'ID'],
            ['create_time', '中奖日期'],
            ['nickname', '昵称'],
            ['phone', '手机号'],
            ['rules_title', '奖品'],
            ['rules_txt', '奖品类型'],
            ['rules_awards', '具体数值'],
        ];
        $fileName = date("Y-m-d", time()) . '-' . $params['rand_number'];
        $exportResult = export_excel($title, $xlsCell, $data, $fileName);
        return ['file_url' => $exportResult['url']];
    }

    /**
     * 兑实物
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function changeStatus($params){
        $id = $params['id']?:0;
        if(!$id){
            throw new \think\Exception(L_('参数错误!'));
        }
        $info = (new FlippingEventRulesLog())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('操作信息不存在!'));  
        }
        if($info['rules_type']!=0){
            throw new \think\Exception(L_('奖品不是实物无法操作!'));
        }
        if($info['status']==1){
            throw new \think\Exception(L_('已兑换，无法操作!'));
        }
        $res = (new FlippingEventRulesLog())->where(['id'=>$id])->save(['status'=>1]);
        if($res){
            return true;
        }else{
            throw new \think\Exception(L_('兑换失败，请联系管理员!'));
        }
    }

    /**
     * 兑换奖品列表
     */
    public function rulesAwardsList($params){
        $uid = $params['uid'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where[] = ['l.uid','=',$uid];
        $field = 'l.id,l.rules_title,l.rules_type,l.create_time,l.rules_awards,l.status,u.phone,u.nickname,r.img';
        $list = (new FlippingEventRulesLog())->getList($where,$field,$page,$pageSize);
        foreach ($list['data'] as $key=>$val){
            $list['data'][$key]['create_time'] = $val['create_time']?date('Y-m-d H:i:s',$val['create_time']):'';
            $list['data'][$key]['rules_awards'] = $val['rules_awards']?:'';
            $list['data'][$key]['status_txt'] = $val['status']?'已入账':'未入账';
            $list['data'][$key]['img'] = $val['img']?replace_file_domain($val['img']):'';
        }
        return $list;
    }
}