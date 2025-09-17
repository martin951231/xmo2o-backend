<?php
/**
 * 汪晨
 * 2021/08/17
 * 技术人员
 */
namespace app\new_marketing\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\new_marketing\model\service\MarketingArtisanService;
use Exception;
use think\App;

class MarketingArtisanController extends AuthBaseController
{
    // 技术人员列表
    public function getMarketingArtisanList(){
        $page = $this->request->param('page',1,'trim');
        $pageSize = $this->request->param('pageSize',10,'trim');

        $name = $this->request->param('name','','trim');
        $director_id = $this->request->param('director_id',0,'trim');
        $begin_time = $this->request->param('begin_time','','trim');
        $end_time = $this->request->param('end_time','','trim');

        // 筛选条件
        $where = [['g.status','=',0],['g.is_director','=',0]];
        // 技术人员
        if($name != ''){
            $where[] = ['g.name', 'like', '%'.$name.'%'];
        }
        // 技术主管
        if($director_id > 0){
            $where[] = ['g.director_id', '=', $director_id];
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

    // 选择更换主管列表
    public function getDirectorList(){
        // 筛选条件
        $where = [['status','=',0],['is_director','=',1]];
        try {
            $arr = (new MarketingArtisanService())->getDirectorList($where);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    // 技术人员操作
    public function getMarketingArtisanCreate()
    {
        $param['id'] = $this->request->param("id", "0", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['phone'] = $this->request->param("uid", "", "trim");
        $param['director_id'] = $this->request->param("director_id", "0", "intval");
        // 获得列表
        try {
            $list = (new MarketingArtisanService())->getMarketingArtisanCreate($param);
            return api_output(0, $list);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    // 技术人员根据条件获取一条数据
    public function getMarketingArtisanInfo(){
        $id = $this->request->param('id', '0', 'intval');
        $result = (new MarketingArtisanService())->getMarketingArtisanInfo($id);
        if ($result['director_id'] == 0) {
            $result['director_id'] = '请选择';
        }
        return api_output(1000, $result);
    }

    // 技术人员选择、更换主管
    public function getMarketingArtisanDir(){
        $param['id'] = $this->request->param("id", "0", "intval");
        $param['director_id'] = $this->request->param("director_id", "0", "intval");
        // 获得列表
        $list = (new MarketingArtisanService())->getMarketingArtisanDir($param);
        return api_output(0, $list);
    }

    // 技术人员移除
    public function getMarketingArtisanDel(){
        $id = $this->request->param('id', '0', 'intval');
        $result = (new MarketingArtisanService())->getMarketingArtisanDel($id);
        return api_output(1000, $result);

    }
}