<?php
/**
 * 活动商品推荐
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/11/11 16:38
 */

namespace app\mall\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\mall\model\service\MallActivityRecommendService;
use app\mall\model\service\MallGoodsActivityBannerService;
use think\App;

class MallActivityRecommendController extends AuthBaseController
{

    /**
     * 获取 限时秒杀 活动推荐列表
     * User: chenxiang
     * Date: 2020/11/11 16:42
     */
    public function getLimitedRecommendList() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $result = $mallActivityRecommendService->getLimitedRecommendList($param,'sk.sort desc,a.id desc');
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 修改推荐至首页
     * User: chenxiang
     * Date: 2020/11/16 16:26
     * @return \json
     */
    public function editLimitedRecommend() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->editLimitedRecommend($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }


    /**
     * 秒杀活动 - 设置置顶
     * User: chenxiang
     * Date: 2020/11/16 16:55
     * @return \json
     */
    public function setFirstLimited() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->setFirstLimited($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 秒杀活动 - 设置排序
     * User: chenxiang
     * Date: 2020/11/16 16:56
     * @return \json
     */
    public function setSortLimited() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->setSortLimited($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 获取 拼团 活动推荐列表
     * User: chenxiang
     * Date: 2020/11/16 9:55
     */
    public function getGroupRecommendList() {

        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $result = $mallActivityRecommendService->getGroupRecommendList($param,'gr.sort desc,a.id desc');
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 拼团-修改推荐至首页
     * User: chenxiang
     * Date: 2020/11/16 16:26
     * @return \json
     */
    public function editGroupRecommend() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->editGroupRecommend($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }


    /**
     * 拼团活动 - 设置置顶
     * User: chenxiang
     * Date: 2020/11/16 16:55
     * @return \json
     */
    public function setFirstGroup() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->setFirstGroup($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 拼团活动 - 设置排序
     * User: chenxiang
     * Date: 2020/11/16 16:56
     * @return \json
     */
    public function setSortGroup() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->setSortGroup($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }



    /**
     * 获取 砍价 活动推荐列表
     * User: chenxiang
     * Date: 2020/11/16 9:54
     */
    public function getBargainRecommendList() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $result = $mallActivityRecommendService->getBargainRecommendList($param,'b.sort desc,a.id desc');
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 砍价-修改推荐至首页
     * User: chenxiang
     * Date: 2020/11/16 16:26
     * @return \json
     */
    public function editBargainRecommend() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->editBargainRecommend($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }


    /**
     * 砍价活动 - 设置置顶
     * User: chenxiang
     * Date: 2020/11/16 16:55
     * @return \json
     */
    public function setFirstBargain() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->setFirstBargain($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }

    /**
     * 砍价活动 - 设置排序
     * User: chenxiang
     * Date: 2020/11/16 16:56
     * @return \json
     */
    public function setSortBargain() {
        $param = $this->request->param();
        $mallActivityRecommendService = new MallActivityRecommendService();
        try {
            $mallActivityRecommendService->setSortBargain($param);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
    }



    /**
     * 活动轮播图列表
     * User: chenxiang
     * Date: 2020/11/16 17:24
     * @param $cat_id
     * @param $type
     * @return mixed
     */
    public function bannerList($act_type)
    {
        if (empty($act_type)) {
            return api_output(1001, [], '店铺ID不存在');
        }

        try{
            $mallGoodsActivityBannerService = new MallGoodsActivityBannerService();
            $result = $mallGoodsActivityBannerService->getBannerList($act_type);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 添加 / 编辑轮播图
     * User: chenxiang
     * Date: 2020/11/16 17:46
     * @return bool
     */
    public function addOrEditBanner()
    {
        $param = $this->request->param();

        if (empty($param['act_type'])) {
            return api_output(1001, [], 'act_type参数缺失');
        }
        if (empty($param['image'])) {
            return api_output(1001, [], 'iamge参数缺失');
        }
		
		$saveParams = [
			'id' => $param['id'],
			'act_type' => $param['act_type'],
			'image' => $param['image'],
			'url' => $param['url'],
			'sort' => $param['sort'],
		];
        try{
            $mallGoodsActivityBannerService = new MallGoodsActivityBannerService();
            $mallGoodsActivityBannerService->addOrEditBanner($saveParams);

            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }

    /**
     * 删除轮播图
     * User: chenxiang
     * Date: 2020/11/16 17:48
     * @param $id
     * @return bool
     */
    public function delBanner()
    {
        $id = $this->request->param('id', '');
        $act_type = $this->request->param('act_type', '');
        if (empty($id)) {
            return api_output(1001, [], 'id参数缺失');
        }
        if (empty($act_type)) {
            return api_output(1001, [], 'act_type参数缺失');
        }
        $where = [];
        $where['id'] = $id;
        $where['act_type'] = $act_type;

        try {
            $mallGoodsActivityBannerService = new MallGoodsActivityBannerService();
            $mallGoodsActivityBannerService->delBanner($where);
            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }

}