<?php
/**
 * 汪晨
 * 2021/08/17
 * 技术主管
 */
namespace app\new_marketing\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\new_marketing\model\service\MarketingArtisanService;
use think\App;
use think\Exception;

class MarketingDirectorController extends AuthBaseController
{
    // 技术主管列表
    public function getMarketingDirectorList(){
        $page = $this->request->param('page',1,'trim');
        $pageSize = $this->request->param('pageSize',10,'trim');

        $name = $this->request->param('name','','trim');
        $begin_time = $this->request->param('begin_time','','trim');
        $end_time = $this->request->param('end_time','','trim');

        // 筛选条件
        $where = [['g.status','=',0],['g.is_director','=',1]];
        // 技术主管
        if($name != ''){
            $where[] = ['g.name', 'like', '%'.$name.'%'];
        }
        // 时间
        if($begin_time != '' && $end_time != ''){
            $arr = [['g.add_time', '>=', strtotime($begin_time.' 00:00:00')], ['g.add_time', '<=', strtotime($end_time.' 23:59:59')]];
            $where = array_merge($where, $arr);
        }

        $field = 'g.*';
        $order = 'g.id DESC';
        try {
            $arr = (new MarketingArtisanService())->getMarketingArtisanList($where, $field, $order, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    // 技术主管操作
    public function getMarketingDirectorCreate(){
        $param['id'] = $this->request->param("id", "0", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['phone'] = $this->request->param("uid", 0, "trim");
        $param['team_percent'] = $this->request->param("team_percent", "0", "intval");
        // 获得列表
        try{
            $list = (new MarketingArtisanService())->getMarketingDirectorCreate($param);
            return api_output(0, $list);
        }catch (Exception $e){
            return api_output_error(1003, $e->getMessage());
        }
    }

    // 技术主管根据条件获取一条数据
    public function getMarketingDirectorInfo(){
        $id = $this->request->param('id', '0', 'intval');
        $result = (new MarketingArtisanService())->getMarketingArtisanInfo($id);
        return api_output(1000, $result);
    }

    // 技术主管移除
    public function getMarketingDirectorDel(){
        $id = $this->request->param('id', '0', 'intval');
        $result = (new MarketingArtisanService())->getMarketingDirectorDel($id);
        return api_output(1000, $result);
    }

    // 技术主管/成员管理列表
    public function getMarketingDirectorRemove(){
        $page = $this->request->param('page',1,'trim');
        $pageSize = $this->request->param('pageSize',10,'trim');
        $id = $this->request->param('id',0,'trim');

        // 筛选条件
        $where = [['g.status','=',0],['g.is_director','=',0],['g.director_id','=',$id]];

        $field = 'g.*';
        $order = 'g.id DESC';
        try {
            $arr = (new MarketingArtisanService())->getMarketingArtisanList($where, $field, $order, $page, $pageSize);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    // 技术主管移出技术人员
    public function getMarketingDirectorArtisan(){
        $id = $this->request->param('id', '0', 'intval');
        $result = (new MarketingArtisanService())->getMarketingDirectorArtisan($id);
        return api_output(1000, $result);

    }
}