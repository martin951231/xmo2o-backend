<?php


namespace app\common\controller\platform;


use app\common\model\service\HomeSearchRankingService;

class HomeSearchRankingController extends AuthBaseController
{
    /**
     * 获取排行榜配置列表
     * @return \think\response\Json
     */
    public function getList(){
        $params = [
            'page' => $this->request->param('page',1,'trim,intval'),
            'pageSize' => $this->request->param('pageSize',10,'trim,intval'),
            'show_position' => $this->request->param('show_position','','trim,string'),
        ];
        try {
            $res = (new HomeSearchRankingService())->getList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1005,$e->getMessage());
        }
    }

    /**
     * 添加/编辑排行榜配置
     * @return \think\response\Json
     */
    public function saveData(){
        $params = [
            'name' => $this->request->param('name','','trim,string'),
            'pic' => $this->request->param('pic','','trim,string'),
            'bg_color' => $this->request->param('bg_color','','trim,string'),
            'font_color' => $this->request->param('font_color','','trim,string'),
            'business_type' => $this->request->param('business_type','','trim,string'),
            'type' => $this->request->param('type','','trim,string'),
            'sort_type' => $this->request->param('sort_type','','trim,string'),
            'virtual_sales' => $this->request->param('virtual_sales',0,'trim,intval'),
            'start_time' => $this->request->param('start_time','','trim,string'),
            'end_time' => $this->request->param('end_time','','trim,string'),
            'sort' => $this->request->param('sort',0,'trim,intval'),
            'status' => $this->request->param('status',0,'trim,intval'),
            'custom_value' => $this->request->param('custom_value',[['name'=>'测试1','virtual_sales'=>0,'link_url'=>'https://www.baidu.com']]),
            'show_position' => $this->request->param('show_position','','trim,string'),
            'id' => $this->request->param('id',0,'trim,intval'),
        ];
        try {
            $res = (new HomeSearchRankingService())->saveData($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1005,$e->getMessage());
        }
    }

    /**
     * 修改排行榜配置排序值
     * @return \think\response\Json
     */
    public function changeInfo(){
        $params = [
            'id' => $this->request->param('id',0,'trim,intval'),
            'type' => $this->request->param('type','status','trim,string'),
            'value' => $this->request->param('value',0,'trim,intval'),
        ];
        try {
            $res = (new HomeSearchRankingService())->changeInfo($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1005,$e->getMessage());
        }
    }

    /**
     * 删除排行榜配置
     * @return \think\response\Json
     */
    public function delete(){
        $params = [
            'id' => $this->request->param('id',0,'trim,intval'),
        ];
        try {
            $res = (new HomeSearchRankingService())->delete($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1005,$e->getMessage());
        }
    }
}