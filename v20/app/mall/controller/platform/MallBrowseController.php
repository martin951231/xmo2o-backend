<?php
/**
 * MallOrderController.php
 * 平台后台-订单管理
 * Create on 2020/9/16 15:32
 * Created by zhumengqun
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\MallBrowseNewService;
use think\App;

class MallBrowseController extends AuthBaseController
{
    /**
     * 获取商城首页或者商品的浏览量信息
     * @return \json
     */
    public function getMallBrowse()
    {
        $param['search_type'] = $this->request->param('search_type', 0, 'intval');//查询类类型（0：商城首页浏览量，1：商城商品浏览量）
        $param['time_type'] = $this->request->param('time_type', 1, 'intval');//查询类类型（0：今日，1：本周，2：本月，3：全年，4：自定义时间）
        $param['start_time'] = $this->request->param('start_time', '', 'trim');//查询开始时间
        $param['end_time'] = $this->request->param('end_time', '', 'trim');//查询结束时间
        try {
            $arr = (new MallBrowseNewService())->getMallBrowse($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 浏览量信息导出
     * @author Nd
     * @date 2022/5/17
     */
    public function export()
    {
        $param['start_time'] = $this->request->param('start_time', '', 'trim');//查询开始时间
        $param['end_time'] = $this->request->param('end_time', '', 'trim');//查询结束时间
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['merList'] = $this->request->param('merList', '');
        $param['storeList'] = $this->request->param('storeList', '');
        $param['cat_id'] = $this->request->param('cat_id', '');
        $param['browse'] = $this->request->param('browse', 0);//根据浏览量排序（1-倒序，2-正序）
        $param['browseToday'] = $this->request->param('browse_today', 0);//根据今日浏览量排序（1-倒序，2-正序）
        try {
            $arr = (new MallBrowseNewService())->exportMallBrowse($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 浏览量信息汇总导出
     * @author Nd
     * @date 2022/5/17
     */
    public function exportBrowseTotal()
    {
        $param['search_type'] = $this->request->param('search_type', 0, 'intval');//查询类类型（0：商城首页浏览量，1：商城商品浏览量）
        $param['time_type'] = $this->request->param('time_type', 1, 'intval');//查询类类型（0：今日，1：本周，2：本月，3：全年，4：自定义时间，5：全部）
        $param['start_time'] = $this->request->param('start_time', '', 'trim');//查询开始时间
        $param['end_time'] = $this->request->param('end_time', '', 'trim');//查询结束时间
        $param['export_type'] = $this->request->param('export_type', 0, 'trim');//按照年月日导出,0-日，1-月，2-年
        try {
            $arr = (new MallBrowseNewService())->exportMallBrowseTotal($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}