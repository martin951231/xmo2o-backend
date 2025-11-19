<?php


namespace app\community\controller\manage_api\v1;


use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageNewsService;

class NewsController extends CommunityBaseController
{
    /**
     * 获取新闻分类
     * @author lijie
     * @date_time 2020/08/17 18:07
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewsCategoryLists()
    {
        $village_id = $this->request->post('village_id',0);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $field = 'cat_id,cat_name';
        $house_village_news = new HouseVillageNewsService();
        $where['village_id'] = $village_id;
        $where['cat_status'] = 1;
        $order = 'cat_sort DESC';
        $data = $house_village_news->getNewsCategoryLists($where,$field,$page,$limit,$order);
        return api_output(0,$data,'获取成功');
    }

    /**
     * 新闻列表
     * @author lijie
     * @date_time 2020/08/18 9:50
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewsLists()
    {
        $village_id = $this->request->post('village_id',0);
        $cat_id = $this->request->post('cat_id',0);
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if($cat_id<1){
            return api_output(0,[],'获取成功');
        }
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $field = 'news_id,title,add_time';
        $house_village_news = new HouseVillageNewsService();
        $where['village_id'] = $village_id;
        $where['status'] = 1;
        $where['cat_id'] = $cat_id;
        $order = 'news_id DESC';
        $data = $house_village_news->getNewsLists($where,$field,$page,$limit,$order);
        return api_output(0,$data,'获取成功');
    }

    /**
     * 新闻详情
     * @author lijie
     * @date_time 2020/08/18 10:02
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function newsDetail()
    {
        $news_id = $this->request->post('news_id',0);
        if(!$news_id)
            return api_output_error(1001,'必传参数缺失');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $field = 'r.add_time,r.content,u.nickname,r.pigcms_id,u.avatar,r.status';
        $where['r.news_id'] = $news_id;
        $order = 'r.pigcms_id DESC';
        $house_village_news = new HouseVillageNewsService();
        $reply_lists = $house_village_news->getNewsReplyLists($where,$field,$page,$limit,$order);
        $news_detail = $house_village_news->getNewsDetail(['news_id'=>$news_id],'title,content,add_time');
        $data['reply_lists'] = $reply_lists;
        $data['news_detail'] = $news_detail;
        return api_output(0,$data,'获取成功');
    }

    /**
     *
     * @return \json
     */
    public function isShow()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $status = $this->request->post('status',0);
        if(!$pigcms_id || !$status)
            return api_output_error(1001,'必传参数缺失');
        $where['pigcms_id'] = $pigcms_id;
        $data['status'] = $status;
        $house_village_news = new HouseVillageNewsService();
        $res = $house_village_news->isShowReply($where,$data);
        if($res)
            return api_output(0,'','操作成功');
        else
            return api_output_error(1001,'服务异常');
    }

    /**
     * 删除新闻评论
     * @author lijie
     * @date_time 2020/08/18 10:09
     * @return \json
     * @throws \Exception
     */
    public function delReply()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$pigcms_id)
            return api_output_error(1001,'必传参数缺失');
        $where['pigcms_id'] = $pigcms_id;
        $house_village_news = new HouseVillageNewsService();
        $res = $house_village_news->delReply($where);
        if($res)
            return api_output(0,'','删除成功');
        else
            return api_output_error(1001,'服务异常');
    }
}