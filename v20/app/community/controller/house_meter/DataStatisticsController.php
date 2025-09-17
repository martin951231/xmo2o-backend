<?php


namespace app\community\controller\house_meter;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseMeterService;

class DataStatisticsController extends CommunityBaseController
{
    /**
     * 大屏头部数量统计
     * @author lijie
     * @date_time 2021/05/20
     * @return \json
     */
    public function getTongjiCount()
    {
        $service_house_meter = new HouseMeterService();
        $city_count = $service_house_meter->getCountByField([],'city_id');
        $village_count = $service_house_meter->getCountByField([],'village_id');
        $ele_count = $service_house_meter->getEleCount([]);
        $ele_warn_count = $service_house_meter->getWarnCount([]);
        $room_count = $service_house_meter->getCountByField([],'vacancy_id');
        $data = [
            ['title'=>'入驻城市','value'=>$city_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_6.png'],
            ['title'=>'入驻小区','value'=>$village_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_7.png'],
            ['title'=>'房屋总数','value'=>$room_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_8.png'],
            ['title'=>'设备总数','value'=>$ele_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_9.png'],
            ['title'=>'设备告警总数','value'=>$ele_warn_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_10.png'],
        ];
        return api_output(0,$data);
    }

    public function getTongjiCountByCity()
    {
        $service_house_meter = new HouseMeterService();
        $city = $this->request->post('city','');
        $where = [];
        if($city){
            $city_info = $service_house_meter->getCity(['area_name'=>$city],'area_id');
            $where['city_id'] = $city_info['area_id'];
        }
        $village_count = $service_house_meter->getCountByField($where,'village_id');
        $ele_count = $service_house_meter->getEleCount($where);
        $ele_warn_count = $service_house_meter->getWarnCount($where);
        $room_count = $service_house_meter->getCountByField($where,'vacancy_id');
        /*$info = [
            ['title'=>'入驻小区','value'=>$village_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_7.png'],
            ['title'=>'房屋总数','value'=>$room_count.'m²','logo'=>cfg('site_url').'/v20/public/static/community/images/top_8.png'],
            ['title'=>'设备总数','value'=>$ele_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_9.png'],
            ['title'=>'设备告警总数','value'=>$ele_warn_count,'logo'=>cfg('site_url').'/v20/public/static/community/images/top_10.png'],
        ];*/
        $info = ['village_count'=>$village_count,'room_count'=>$room_count,'ele_count'=>$ele_count,'ele_warn_count'=>$ele_warn_count];
        $position = ['name'=>'河南省郑州市','lng'=>'113.667061','lat'=>'34.754626'];
        $data['info'] = $info;
        $data['position'] = $position;
        return api_output(0,$data);
    }

    /**
     * 设备故障列表
     * @author lijie
     * @date_time 2021/05/20
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getEleWarnList()
    {
        $service_house_meter = new HouseMeterService();
        $page = $this->request->get('page',1);
        $limit = $this->request->get('limit',15);
        $data = $service_house_meter->getWarnList([],true,$page,$limit,'id DESC');
        return api_output(0,$data);
    }

    /**
     * 设备耗电分析
     * @author lijie
     * @date_time 2021/05/20
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function powerConsumptionAnalysis()
    {
        $service_house_meter = new HouseMeterService();
        //总耗电量
        $sum_power = $service_house_meter->getSumPower('end_num - begin_num');
        $sum_power = $sum_power[0]['sum'];
        //各个小区的耗电量
        $village_power = $service_house_meter->getSumPowerGroupByVillage('r.end_num - r.begin_num','r.village_id','sum DESC',$sum_power);
        $top_village_power = $village_power['data'];
        $data['sum_power'] = $sum_power;
        $data['village_power'] = $village_power['list_arr'];
        $data['top_village_power'] = $top_village_power;
        $data['color'] = ['#ff9f00', '#00d7ed', '#31abe3', '#005fd6', '#36d400', '#ff5916'];
        return api_output(0,$data);
    }

    /**
     * 设备管理
     * @author lijie
     * @date_time 2021/05/21
     * @return \json
     */
    public function deviceManage()
    {
        $service_house_meter = new HouseMeterService();
        $ele_count = $service_house_meter->getEleCount([]); //设备总数
        $online_ele = $service_house_meter->getEleStatus(['g.status1'=>0]);
        $offline_ele = $service_house_meter->getEleStatus(['g.status1'=>1]);
        $online_rate = sprintf("%.2f", $online_ele/$ele_count*100).'%'; // 在线率
        $offline_rate = sprintf("%.2f", $offline_ele/$ele_count*100).'%'; // 离线率
        $fault_rate = '0%';  //故障率
        $village_list = $service_house_meter->getEleStatus([],'e.village_id');
        $list_arr = [];
        if($village_list){
            $arr = [
                ['title'=>'小区名称 '],
                ['title'=>'设备总数'],
                ['title'=>'在线'],
                ['title'=>'故障'],
                ['title'=>'离线']
            ];
            foreach ($village_list as $k=>$v){
                $online_ele = $service_house_meter->getEleStatus(['g.status1'=>0,'e.village_id'=>$v['village_id']]);
                $offline_ele = $service_house_meter->getEleStatus(['g.status1'=>1,'e.village_id'=>$v['village_id']]);
                $village_list[$k]['online_ele'] = $online_ele;
                $village_list[$k]['offline_ele'] = $offline_ele;
                $village_list[$k]['fault'] = $v['all_count'] - $online_ele - $offline_ele;
                $list_arr[0] = $arr;
                $list_arr[$k+1] = [
                    ['title'=>$v['village_name']],
                    ['title'=>$v['all_count']],
                    ['title'=>$online_ele],
                    ['title'=>$v['all_count'] - $online_ele - $offline_ele],
                    ['title'=>$offline_ele],
                ];
            }
        }
        $data['ele_count'] = $ele_count;
        $data['online_rate'] = $online_rate;
        $data['offline_rate'] = $offline_rate;
        $data['fault_rate'] = $fault_rate;
        $data['list'] = $list_arr;
        return api_output(0,$data);
    }

    /**
     * 设备耗电费用分析
     * @author lijie
     * @date_time 2021/05/22
     * @return \json
     */
    public function powerConsumptionFeeAnalysis()
    {
        $service_house_meter = new HouseMeterService();
        $info = $service_house_meter->getRealtime([],'village_id,electric_id,sum(end_num - begin_num) as sum_num','electric_id');
        $top_village_power = array_slice($info['village_list'],0,6);
        $data['sum_power'] = $info['all_fee'];
        $data['village_power'] = $info['list_arr'];
        $data['top_village_power'] = $top_village_power;
        $data['color'] = ['#ff9f00', '#00d7ed', '#31abe3', '#005fd6', '#36d400', '#ff5916'];
        return api_output(0,$data);
    }
}