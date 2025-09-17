<?php
/**
 * 事项材料相关
 * @author weili
 * @date 2020/11/2
 */

namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\MatterService;
class MatterController extends CommunityBaseController
{
    //分类列表
    public function getCategoryList()
    {

        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMatter = new MatterService();
        $page = $this->request->param('page',1,'intval');
        $name = $this->request->param('cat_name','','trim');
        $limit = 10;
        try {
            $list = $serviceMatter->categoryList($street_id,$name,$page, $limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        return api_output(0,$list,'成功');
    }
    //分类详情
    public function getCategoryDetail()
    {
        $id = $this->request->param('cat_id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMatter = new MatterService();
        try {
            $info = $serviceMatter->details($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$info,'成功');
    }
    //添加/编辑分类
    public function handleCategory()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $cat_name = $this->request->param('cat_name','','trim');
        $cat_sort = $this->request->param('cat_sort',0,'intval');
        $cat_status = $this->request->param('cat_status',0,'intval');
        $cat_id = $this->request->param('cat_id',0,'intval');
        if(!$cat_name){
            return api_output_error(1001,'必传参数缺失');
        }
        $data =[
            'cat_name'=>$cat_name,
            'cat_sort'=>$cat_sort,
            'cat_status'=>$cat_status,
            'area_id'=>$street_id,
        ];
        $serviceMatter = new MatterService();
        try {
            $info = $serviceMatter->postCategory($data,$cat_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($info)
        {
            return api_output(0,$info,'成功');
        }else{
            return api_output_error(-1,'失败');
        }
    }
    //删除分类
    public function delCategory()
    {
        $id = $this->request->param('cat_id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMatter = new MatterService();
        try {
            $info = $serviceMatter->del($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$info,'成功');
    }
    //事项列表
    public function getMatterList()
    {
        $street_id = $this->adminUser['area_id'];
        $cat_id = $this->request->param('cat_id','','intval');
        if(!$street_id || !$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMatter = new MatterService();
        $page = $this->request->param('page',1,'intval');
        $name = $this->request->param('title','','trim');
        $limit = 10;
        try {
            $list = $serviceMatter->getMatterList($cat_id,$street_id,$name,$page, $limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        $list['cat_id'] = $cat_id;
        return api_output(0,$list,'成功');
    }
    //事项详情
    public function getMatterInfo()
    {
        $id = $this->request->param('matter_id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMatter = new MatterService();
        try {
            $info = $serviceMatter->getMatterInfo($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$info,'成功');
    }
    //添加/编辑事项
    public function subMatter()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $title = $this->request->param('title','','trim');
        $content = $this->request->param('content');
        $status = $this->request->param('status',0,'intval');
        $cat_id = $this->request->param('cat_id',0,'intval');
        $sort = $this->request->param('sort',0,'intval');
        $matter_id = $this->request->param('matter_id',0,'intval');
        if(!$title || !$content || !$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $data =[
            'title'=>$title,
            'content'=>$content,
            'status'=>$status,
            'area_id'=>$street_id,
            'cat_id'=>$cat_id,
            'sort'=>$sort,
        ];
        $serviceMatter = new MatterService();
        try {
            $info = $serviceMatter->subMatter($data,$matter_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($info)
        {
            return api_output(0,$info,'成功');
        }else{
            return api_output_error(-1,'失败');
        }
    }
    //删除事项
    public function delMatter()
    {
        $id = $this->request->param('matter_id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMatter = new MatterService();
        try {
            $info = $serviceMatter->delMatter($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$info,'成功');
    }
}