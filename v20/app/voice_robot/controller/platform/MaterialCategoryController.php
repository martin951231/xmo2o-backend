<?php


namespace app\voice_robot\controller\platform;



use app\voice_robot\model\service\MaterialCategoryService;

class MaterialCategoryController extends AuthBaseController
{
    /**
     * 素材库分类列表
     */
    public function materialCategoryList()
    {
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['keyword'] = $this->request->post('keyword', 0, 'trim');
        $param['dateArr'] = $this->request->post('date');
        $param['xtype'] = $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        try {
            $arr= (new MaterialCategoryService())->getList($param);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 素材库分类编辑
     */
    public function editMaterialCategory()
    {
        $param['cate_id'] = $this->request->post('cate_id', 0, 'int');
        $param['categoryname'] = $this->request->post('categoryname', '', 'trim');
        $param['xtype']= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        try {
            $result= (new MaterialCategoryService())->editMaterialCategory($param);
            return api_output(0, $result, "success");
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 素材库分类删除
     */
    public function delMaterialCategory()
    {
        $param['cate_ids'] = $this->request->post('cate_ids','', 'trim');//编辑的关键词id
        $param['xtype']= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        try {
            $result= (new MaterialCategoryService())->delMaterialCategory($param);
            return api_output(0, $result, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 素材内容列表
     */
    public function contentList()
    {
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['dateArr'] = $this->request->post('date');
        $param['xtype']= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        $param['cate_id']= $this->request->post('cate_id', 0, 'int');
        try {
            $result= (new MaterialCategoryService())->contentList($param);
            return api_output(0, $result, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 素材库内容编辑
     */
    public function saveContent()
    {
        $param['material_id'] = $this->request->post('material_id', 0, 'int');  //素材id
        $param['cate_id'] = $this->request->post('cate_id', 0, 'int');   //分类id
        $param['xtype']= $this->request->post('xtype', 0, 'int');    //1文字2音频3图片
        $param['word_imgs'] = $this->request->post('word_imgs','');  //图片
        $param['xname'] = $this->request->post('xname','','trim');  //标题
        $param['xcontent'] = $this->request->post('xcontent','');  //文本回复
        $param['audio_url'] = $this->request->post('audio_url');  //音频
        try {
            $result= (new MaterialCategoryService())->saveContent($param);
            return api_output(0, $result, "success");
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 素材内容删除
     */
    public function delContent()
    {
        $param['material_ids'] = $this->request->post('material_ids','');
        $param['xtype']= $this->request->post('xtype', 0, 'int');    //1文字分类2音频分类3图片分类
        $param['cate_id']= $this->request->post('cate_id', 0, 'int');
        try {
            $result= (new MaterialCategoryService())->delContent($param);
            return api_output(0, $result, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取素材库
     */
    public function getHotWordMaterialLibrary()
    {
        $param['xtype']= $this->request->post('xtype', 0, 'int'); //1文字分类2音频分类3图片分类
        try {
            $arr= (new MaterialCategoryService())->getHotWordMaterialLibrary($param);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取素材详情
     */
    public function getHotWordMaterialLibraryDetail()
    {
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['xtype']= $this->request->post('xtype', 0, 'int'); //1文字分类2音频分类3图片分类
        $param['cate_id']= $this->request->post('cate_id', 0, 'int');
        try {
            $arr= (new MaterialCategoryService())->getHotWordMaterialLibraryDetail($param);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 文本导入
     */
    public function exportMaterialCategory()
    {
        $param['file'] = $this->request->post('file','','trim');
        $param['xtype'] = $this->request->post('xtype',0,'int'); //1文字分类2音频分类3图片分类
        try {
            $arr= (new MaterialCategoryService())->exportMaterialCategory($param);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}