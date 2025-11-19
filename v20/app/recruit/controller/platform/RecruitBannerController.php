<?php
namespace app\recruit\controller\platform;

use app\BaseController;
use app\recruit\model\service\RecruitBannerService;

class RecruitBannerController extends BaseController
{
    /**
     * 列表
     * @author deng
     */
   public function getRecruitBannerList(){
       $page = $this->request->param('page', 1, 'intval');
       $pageSize = $this->request->param('pageSize',10,'intval');
       $list=(new RecruitBannerService())->getRecruitBannerList($page, $pageSize);

       return api_output(0, $list);
   }

    /**
     * 保存
     */
    public function getRecruitBannerCreate(){
        $id = $this->request->param('id', 0, 'trim');
        $params['name'] = $this->request->param('name', '', 'trim');
        $images = $this->request->param('pic', [], 'trim');
        if(empty($images)){
            return api_output(1001,'');
		}
        $params['images'] = $images[0];
        $params['links'] = $this->request->param('links', '', 'trim');
        $result = (new RecruitBannerService())->getRecruitBannerCreate($id, $params);
        return api_output(1000, $result);
    }

    /**
     * 单条
     */
    public function getRecruitBannerInfo(){
        $id = $this->request->param('id', 0, 'trim');
        $result = (new RecruitBannerService())->getRecruitBannerInfo($id);
        return api_output(1000, $result);
    }

    /**
     * 展示
     */
    public function getRecruitBannerDis(){
        $id = $this->request->param('id', 0, 'intval');
        $type = $this->request->param('type', 0, 'intval');
        if($type == 1){
            $count = (new RecruitBannerService)->getRecruitBannerWhere(['status'=>0,'is_dis'=>1]);
            if($count > 4){
                return api_output_error(1003, L_('至多可展示五个！'));
            }
        }
        $list=(new RecruitBannerService())->getRecruitBannerDis($id, $type);
        return api_output(0, $list);
    }

    /**
     * 排序
     */
    public function getRecruitBannerSort(){
        $id = $this->request->param('id', 0, 'intval');
        $sort = $this->request->param('sort', 0, 'intval');
        $list=(new RecruitBannerService())->getRecruitBannerSort($id, $sort);
 
        return api_output(0, $list);
    }

    /**
     * 删除
     */
    public function getRecruitBannerDel(){
        $id = $this->request->param('id', 0, 'intval');
        $list=(new RecruitBannerService())->getRecruitBannerDel($id);
 
        return api_output(0, $list);
    }

 }