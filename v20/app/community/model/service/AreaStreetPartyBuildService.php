<?php


namespace app\community\model\service;

use app\community\model\db\AreaStreetPartyBuildCategory;
use app\community\model\db\AreaStreetPartyBuild;
use app\community\model\db\AreaStreetPartyBuildReply;
use app\community\model\db\AreaStreetConfig;

class AreaStreetPartyBuildService
{
    private $db_area_street_party_build_category = '';
    private $db_area_street_party_build = '';
    private $db_area_street_party_build_reply = '';
    private $db_area_street_config = '';

    /**
     * 数据初始化
     * @author lijie
     * @date_time 2020/09/16
     * AreaStreetPartyBuildService constructor.
     */
    public function __construct()
    {
        $this->db_area_street_party_build_category = new AreaStreetPartyBuildCategory();
        $this->db_area_street_party_build = new AreaStreetPartyBuild();
        $this->db_area_street_party_build_reply = new AreaStreetPartyBuildReply();
        $this->db_area_street_config = new AreaStreetConfig();
    }

    /**
     * 获取党建资讯列表
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param string $from
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildLists($where,$field=true,$page=1,$limit=20,$order='is_hot,build_id DESC',$from='front')
    {
        $data = $this->db_area_street_party_build->getLists($where,$field,$page,$limit,$order);
        if($data){
            $site_url = cfg('site_url');
            $static_resources = static_resources(true);
            $db_area_street_party_build = new AreaStreetPartyBuildCategory();
            foreach ($data as $k=>$v){
                $data[$k]['add_time'] = date('Y-m-d',$v['add_time']);
                if($v['is_notice'] == 0) {
                    $data[$k]['is_notice_txt'] = '否';
                } else {
                    $data[$k]['is_notice_txt'] = '是';
                }
                if (isset($v['title_img']) && $v['title_img']) {
                    if (strpos($v['title_img'],'/v20/') !== false) {
                        $data[$k]['title_img'] = cfg('site_url') . $v['title_img'];
                    } else {
                        $data[$k]['title_img'] = replace_file_domain($v['title_img']);
                    }
                } else {
                    $data[$k]['title_img'] =  $site_url . $static_resources . 'images/meeting.png';
                }
                if (isset($v['cat_id'])) {
                    $where_cat = [];
                    $where_cat[] = ['cat_id','=',$v['cat_id']];
                    $cat_info = $db_area_street_party_build->getOne($where_cat,'cat_name');
                    if ($cat_info && $cat_info['cat_name']) {
                        $data[$k]['cat_name'] = $cat_info['cat_name'];
                    } else {
                        $data[$k]['cat_name'] = '无';
                    }
                }

                $this->db_area_street_party_build->incReadNum(['build_id'=>$v['build_id']]);
            }
        }
        if($from == 'end'){
            $count = $this->db_area_street_party_build->getCount($where);
            $res['list'] = $data;
            $res['count'] = $count;
            return $res;
        }
        return $data;
    }

    /**
     * 编辑党内资讯
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param $data
     * @return bool
     */
    public function savePartyBuild($where,$data)
    {
        if(isset($data['system_type'])){
            unset($data['system_type']);
        }
        $res = $this->db_area_street_party_build->saveOne($where,$data);
        return $res;
    }

    /**
     * 添加党内资讯
     * @author lijie
     * @date_time 2020/10/15
     * @param $data
     * @return int|string
     */
    public function addPartyBuild($data)
    {
        if(isset($data['system_type'])){
            unset($data['system_type']);
        }
        $res = $this->db_area_street_party_build->addOne($data);
        return $res;
    }

    /**
     * 硬删除党内资讯
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delPartyBuild($where)
    {
        $res = $this->db_area_street_party_build->delOne($where);
        return $res;
    }

    /**
     * 获取党建资讯分类列表
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param bool $field
     * @param string $order
     * @param string $from
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildCategoryLists($where,$field=true,$order='cat_sort DESC',$from='front')
    {
        $data = $this->db_area_street_party_build_category->getLists($where,$field,$order);
        if($from == 'end'){
            $count = $this->db_area_street_party_build_category->getCount($where);
            $res['list'] = $data;
            $res['count'] = $count;
            return $res;
        }
        return $data;
    }

    /**
     * 党内资讯详情
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function PartyBuildCategoryDetail($where,$field=true)
    {
        $data = $this->db_area_street_party_build_category->getOne($where,$field);
        return $data;
    }

    /**
     * 编辑党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @param $where
     * @param $data
     * @return bool
     */
    public function savePartyBuildCategory($where,$data)
    {
        if(isset($data['system_type'])){
            unset($data['system_type']);
        }
        $res = $this->db_area_street_party_build_category->saveOne($where,$data);
        return $res;
    }

    /**
     * 添加党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @param $data
     * @return int|string
     */
    public function addPartyBuildCategory($data)
    {
        if(isset($data['system_type'])){
            unset($data['system_type']);
        }
        $res = $this->db_area_street_party_build_category->addOne($data);
        return $res;
    }

    /**
     * 删除党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delPartyBuildCategory($where)
    {
        $res = $this->db_area_street_party_build_category->saveOne($where,['cat_status'=>2]);
        return $res;
    }

    /**
     * 获取党建资讯评论列表
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param string $from
     * @param string $street_id
     * @return mixed
     */
    public function getPartyBuildReplyLists($where,$field=true,$page=1,$limit=20,$order='r.pigcms_id DESC',$from='front',$street_id='')
    {
        $data = $this->db_area_street_party_build_reply->getLists($where,$field,$page,$limit,$order);
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['add_time'] = date('Y-m-d',$v['add_time']);
            }
        }
        if($from == 'end'){
            $con['build_id'] = $where[1][0];
            $count = $this->db_area_street_party_build_reply->getCount($con);
            $switch = $this->db_area_street_config->getFind(['area_id'=>$street_id],'party_build_switch');
            $res['list'] = $data;
            $res['count'] = $count;
            $res['party_build_switch'] = $switch['party_build_switch'];
            return $res;
        }
        return $data;
    }

    /**
     * 删除党内资讯评论
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function delPartyBuildReply($where,$data)
    {
        $res = $this->db_area_street_party_build_reply->delOne($where,$data);
        return $res;
    }

    /**
     * 修改党内资讯评论状态
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param $data
     * @return mixed
     */
    public function savePartyBuildReply($where,$data)
    {
        $res = $this->db_area_street_party_build_reply->changOne($where,$data);
        return $res;
    }

    /**
     * 党建资讯详情
     * @author lijie
     * @date_time 2020/09/16
     * @param $where
     * @param $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildDetail($where,$field=true)
    {
        $data = $this->db_area_street_party_build->getOne($where,$field);
        if($data){
            $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
        }
        return $data;
    }

    /**
     * 添加评论
     * @author lijie
     * @date_time 2020/09/16
     * @param $data
     * @return int|string
     */
    public function addReply($data)
    {
        if(isset($data['system_type'])){
            unset($data['system_type']);
        }
        $res = $this->db_area_street_party_build_reply->saveOne($data);
        return $res;
    }

    /**
     * 评论是否需要审核
     * @author lijie
     * @date_time 2020/10/15
     * @param $where
     * @param $data
     * @return AreaStreetConfig
     */
    public function changeAreaStreetConfig($where,$data)
    {
        if(isset($data['system_type'])){
            unset($data['system_type']);
        }
        $res = $this->db_area_street_config->saveData($where,$data);
        return $res;
    }

    public function getAreaConfig($where,$field=true)
    {
        $res = $this->db_area_street_config->getOne($where,$field);
        return $res;
    }
}