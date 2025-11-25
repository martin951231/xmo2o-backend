<?php
/**
 * 景区团体票门票信息
 */

namespace app\life_tools\model\service\group;

use app\common\model\service\AreaService;
use app\life_tools\model\db\LifeToolsGroupTicket;
class LifeToolsGroupTicketService
{
    public $lifeToolsGroupTicketModel = null;

    public function __construct()
    {
        $this->lifeToolsGroupTicketModel = new LifeToolsGroupTicket();
    }

    /**
     * 获得已选中的门票列表
     */
    public function getTicketList($param){
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?: 10;

        // 排序
        $order = [
            't.sort' => 'desc',
            'l.sort' => 'desc',
            'g.id' => 'desc'
        ];

        // 查询字段
        $field = 'g.*,l.title as name, t.title, t.price';

        // 搜索条件
        $where = [];
        $where[] = ['g.is_del', '=', 0];

        // 商家id
        if(isset($param['mer_id']) && $param['mer_id']){
            $where[] = ['g.mer_id', '=', $param['mer_id']];
        }

        // 关键词搜索
        if(isset($param['keyword']) && $param['keyword']){
            $where[] = ['l.title|t.title', 'like', '%'.$param['keyword'].'%'];
        }

        // 关键词搜索
        if(isset($param['search_type']) && $param['search_type'] && isset($param['search_keyword']) && $param['search_keyword']){
            switch($param['search_type']){
                case 'title':// 景区名称
                    $where[] = ['l.title', 'like', '%'.$param['search_keyword'].'%'];
                    break;
                case 'ticket_title':// 门票名称
                    $where[] = ['t.title', 'like', '%'.$param['search_keyword'].'%'];
                    break;
            }
        }
        $list = $this->lifeToolsGroupTicketModel->getTicketList($where, $field ,$order ,$page ,$pageSize);
        return $list;
    }

    /**
     * 用户获得景区列表
     */
    public function getUserToolsList($param){
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?: 10;

        // 排序
        $order = [
            'l.sort' => 'desc',
            'l.tools_id' => 'desc'
        ];

        // 查询字段
        $field = 'g.mer_id,g.tools_id,l.title ,l.start_time,l.end_time,l.address,l.phone,l.cover_image,l.province_id,l.city_id,l.area_id, t.price,l.time_txt';

        // 搜索条件
        $where = [];
        $where[] = ['g.is_del', '=', 0];
        $where[] = ['t.is_del', '=', 0];
        $where[] = ['l.is_del', '=', 0];
        $where[] = ['l.status', '=', 1];
        $where[] = ['t.status', '=', 1];

        // 分类id
        if(isset($param['cat_id']) && $param['cat_id']){
            $where[] = ['l.cat_id', '=', $param['cat_id']];
        }

        // 关键词搜索
        if(isset($param['keyword']) && $param['keyword']){
            $where[] = ['l.title|t.title', 'like', '%'.$param['keyword'].'%'];
        }

        $list = $this->lifeToolsGroupTicketModel->getUserToolsList($where, $field ,$order ,$page ,$pageSize);

        if($list && $list['data']){
            $travelMsgList = [];
            $travelStatusList = [];
            if(isset($param['uid']) && $param['uid']){// 用户获取列表
                // 查看是否认证了
                $merIdArr = array_column($list['data'], 'mer_id');
                $where = [
                    'uid' => $param['uid'],
                    'mer_id_arr' => $merIdArr,
                    'page_size' => $pageSize
                ];
                $travelList = (new LifeToolsGroupTravelAgencyService)->getTravelList($where);
                if($travelList){
                    $travelList = $travelList->toArray();
                    $travelStatusList = array_column($travelList['data'], 'status', 'mer_id');
                    $travelMsgList = array_column($travelList['data'], 'audit_msg', 'mer_id');
                }
            }

            // 获取省市区信息
            $provinceIdArr = array_column($list['data'], 'province_id');
            $cityIdArr = array_column($list['data'], 'city_id');
            $areaIdArr = array_column($list['data'], 'area_id');
            $areaId = array_merge($provinceIdArr,$cityIdArr,$areaIdArr);
            $areaList = array_column((new AreaService())->getAreaListByCondition([['area_id','in', implode(',', $areaId)]],[],'area_id,area_name'), 'area_name', 'area_id');

            // 获取门票信息
            $toolsIdArr = array_column($list['data'], 'tools_id');
            $where = [
                ['g.tools_id', 'in', implode(',' ,$toolsIdArr)],
                ['t.status', '=', 1],
                ['g.is_del', '=', 0],
                ['t.is_del', '=', 0],
            ];
            $ticketList = $this->lifeToolsGroupTicketModel->getTicketList($where, 't.tools_id,t.title,t.label,t.price as old_price,t.ticket_id,g.group_price as price' ,['t.sort'=>'desc','g.id'=>'desc'] ,1 ,200);
            foreach($list['data'] as &$_tools){
                $_tools['cover_image'] = replace_file_domain($_tools['cover_image']);
                $_tools['show_address'] = ($areaList[$_tools['province_id']] ?? ''). ($areaList[$_tools['city_id']] ?? ''). ($areaList[$_tools['area_id']] ?? '').$_tools['address'];
                
                $_tools['have_travel_agency'] = isset($travelStatusList[$_tools['mer_id']]) ? 1 : 0;
                $_tools['travel_agency_status'] = $travelStatusList[$_tools['mer_id']] ?? -1;
                $_tools['travel_agency_error_msg'] = $travelMsgList[$_tools['mer_id']] ?? '';

                // 门票信息
                foreach($ticketList['data'] as $_ticket){
                    $_ticket['label_arr'] = $_ticket['label'] ? explode(' ' , $_ticket['label']) : [];
                    $_ticket['pay_url'] = get_base_url(). 'pages/lifeTools/order/confirmOrder?id='.$_tools['tools_id'].'&ticketId='.$_ticket['ticket_id'].'&activity_type=group';
                    if($_ticket['tools_id'] == $_tools['tools_id']){
                        $_tools['ticket_list'][] = $_ticket;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 添加团购票
     */
    public function addGroupTicket($param){
        $merId = $param['mer_id'] ?? 0;
        $ticketList = $param['seleted_ticket_list'] ?? [];

        if(!$merId || !$ticketList){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $addData = [];
        foreach($ticketList as $_ticket){
            $addData[] = [
                'mer_id' => $merId,
                'ticket_id' => $_ticket['ticket_id'],
                'tools_id' => $_ticket['tools_id'],
                'max_num' => $_ticket['max_num'],
                'group_price' => $_ticket['group_price'],
                'create_time' => time(),
            ];
        }
        $res = $this->lifeToolsGroupTicketModel->addAll($addData);

        if(!$res){
            throw new \think\Exception(L_('添加失败，请稍后重试'), 1003);
        }

        return true;
    }

    /**
     * 修改团购票
     */
    public function editGroupTicket($param)
    {
        $type = $param['type'] ?? '';
        $id = $param['id'] ?? '';

        if( empty($id) || empty($type) || !in_array($type, ['max_num','group_price'])){
            throw new \think\Exception(L_('参数错误'), 1001);
        }

        $data[$type] = $param[$type] ?? '';

        $where = [
            'id' => $id,
            'mer_id' => $param['mer_id']
        ];

        $res = $this->lifeToolsGroupTicketModel->updateThis($where, $data);
        if($res === false){
            throw new \think\Exception(L_('修改失败，请稍后重试'), 1003);
        }
        return $res;
    }

    /**
     * 删除团购票
     */
    public function delGroupTicket($where)
    {
        $res = $this->lifeToolsGroupTicketModel->updateThis($where, ['is_del'=>1]);
        if(!$res){
            throw new \think\Exception(L_('删除失败，请稍后重试'), 1003);
        }
        return $res;
    }

    /**
     * 获取一条记录
     */
    public function getOne($where)
    {
        $res = $this->lifeToolsGroupTicketModel->getOne($where);
        return $res;
    }

}