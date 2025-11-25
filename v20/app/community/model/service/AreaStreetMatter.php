<?php


namespace app\community\model\service;

use app\community\model\db\AreaStreetMatter as AreaStreetMatterModel;
use app\community\model\db\AreaStreetMatterCategory;

class AreaStreetMatter
{
    private $db_area_street_matter = '';
    private $db_area_street_matter_category = '';

    /**
     * 初始化数据
     * AreaStreetMeetingLessonService constructor.
     */
    public function __construct()
    {
        $this->db_area_street_matter = new AreaStreetMatterModel();
        $this->db_area_street_matter_category = new AreaStreetMatterCategory();
    }

    /**
     * 事项列表
     * @author lijie
     * @date_time 2020/09/21
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
    public function getMatterLists($where,$field=true,$page=1,$limit=20,$order='is_hot,matter_id DESC')
    {
        $data = $this->db_area_street_matter->getLists($where,$field,$page,$limit,$order);
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['add_time'] = date('Y-m-d',$v['add_time']);
                $this->db_area_street_matter->incReadNum(['matter_id'=>$v['matter_id']]);
            }
        }
        return $data;
    }

    /**
     * 事项分类列表
     * @author lijie
     * @date_time 2020/09/21
     * @param $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMatterCategoryLists($where,$field=true,$order='cat_sort DESC')
    {
        $data = $this->db_area_street_matter_category->getLists($where,$field,$order);
        return $data;
    }

    /**
     * 事项详情
     * @author lijie
     * @date_time 2020/09/21
     * @param $where
     * @param $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMatterDetail($where,$field=true)
    {
        $data = $this->db_area_street_matter->getOne($where,$field);
        if($data){
            $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
            $data['content'] = replace_file_domain_content(htmlspecialchars_decode($data['content']));
        }
        return $data;
    }
}