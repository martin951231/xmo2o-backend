<?php


namespace app\community\model\service;

use app\community\model\db\AreaStreetNews as AreaStreetNewsModel;
use app\community\model\db\AreaStreetNewsCategory;
use app\community\model\db\AreaStreetNewsReply;

class AreaStreetNewsService
{
    private $db_area_street_news_category = '';
    private $db_area_street_news = '';
    private $db_area_street_news_reply = '';

    public function __construct()
    {
        $this->db_area_street_news = new AreaStreetNewsModel();
        $this->db_area_street_news_category = new AreaStreetNewsCategory();
        $this->db_area_street_news_reply = new AreaStreetNewsReply();
    }

    /**
     * 获取新闻列表
     * @author lijie
     * @date_time 2020/09/09 11:37
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewsLists($where,$field=true,$page=1,$limit=20,$order='is_hot DESC,news_id DESC')
    {
        $data = $this->db_area_street_news->getLists($where,$field,$page,$limit,$order);
        if($data){
            $db_area_street_news_category = new AreaStreetNewsCategory();
            foreach ($data as $k=>$v){
                $data[$k]['read_volume']=isset($v['read_sum']) && !empty($v['read_sum']) ? strval($v['read_sum']):'0';
                $data[$k]['add_time'] = date('Y-m-d',$v['add_time']);
                if (isset($v['title_img']) && $v['title_img']) {
                    $data[$k]['title_img'] = dispose_url($v['title_img']);
                }
                if (isset($v['cat_id'])) {
                    $where_cat = [];
                    $where_cat[] = ['cat_id','=',$v['cat_id']];
                    $cat_info = $db_area_street_news_category->getOne($where_cat,'cat_name');
                    if ($cat_info && $cat_info['cat_name']) {
                        $data[$k]['cat_name'] = $cat_info['cat_name'];
                    } else {
                        $data[$k]['cat_name'] = '无';
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取新闻分类
     * @author lijie
     * @date_time 2020/09/09 11:38
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewsCategoryLists($where,$field=true,$order='cat_sort DESC',$page=0,$limit=20)
    {
        $data = $this->db_area_street_news_category->getLists($where,$field,$order,$page,$limit);
        return $data;
    }

    /**
     * 获取新闻分类
     * @author lijie
     * @date_time 2020/09/09 11:39
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewsReplyLists($where,$field=true,$page=1,$limit=20,$order='r.pigcms_id DESC')
    {
        $data = $this->db_area_street_news_reply->getLists($where,$field,$page,$limit,$order);
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['add_time'] = date('Y-m-d',$v['add_time']);
            }
        }
        return $data;
    }

    /**
     * 获取街道最新要闻
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true,$order='news_id DESC')
    {
        $data = $this->db_area_street_news->getOne($where,$field,$order);
        return $data;
    }
}