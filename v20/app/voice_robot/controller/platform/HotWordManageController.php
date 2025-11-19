<?php


namespace app\voice_robot\controller\platform;


use app\voice_robot\model\service\HotWordManageService;

class HotWordManageController extends AuthBaseController
{
    /**
     * 关键词列表
     */
    public function hotWordList()
    {
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['keyword'] = $this->request->post('keyword', 0, 'trim');
        $param['dateArr'] = $this->request->post('date');
        try {
            $arr= (new HotWordManageService())->hotWordList($param);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 关键词新增编辑
     */
    public function editHotWord()
    {
        $param['word_id'] = $this->request->post('word_id', 0, 'int');//编辑的关键词id
        $param['wordname'] = $this->request->param('wordname', '', 'trim');//关键词名称
        $param['xtype'] = $this->request->param('xtype', 0, 'int');//关键词类型 0链接1文字2音频3图片
        $param['wordurllist'] = $this->request->param('wordurllist', []);//功能链接数组
        
        $param['xcontent'] = $this->request->param('xcontent', '', 'trim');//回复内容
        $param['comfrom'] = $this->request->param('comfrom', 0, 'int');//是否从功能库获取的素材
        $param['cate_id'] = $this->request->param('cate_id', 0, 'int');//素材类型id
        $param['material_id'] = $this->request->param('material_id', 0, 'int');//素材id
        
        $param['xname'] = $this->request->param('xname', '', 'trim');//音频名称
        $param['audio_url'] = $this->request->param('audio_url', '', 'trim');//音频文件
        
        $param['word_imgs'] = $this->request->param('word_imgs', []);//图片文件
        try {
            $result= (new HotWordManageService())->editHotWord($param);
            return api_output(0, $result, "success");
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 关键词删除
     */
    public function delHotWord()
    {
        $param['word_id'] = $this->request->post('word_id', 0, 'int');//编辑的关键词id
        try {
            $result= (new HotWordManageService())->delHotWord($param);
            return api_output(0, $result, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 关键词状态编辑
     */
    public function editHotWordStatus()
    {
        $param['word_id'] = $this->request->post('word_id', 0, 'int');//编辑的关键词id
        $param['status'] = $this->request->post('status', 0, 'int');  //0禁用 1启用
        try {
            $result= (new HotWordManageService())->editHotWordStatus($param);
            return api_output(0, $result, "success");
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    
    /**
     * 关键词详情
     */
    public function hotWordDetail()
    {
        $param['word_id'] = $this->request->post('word_id', 0, 'int');//编辑的关键词id
        try {
            $arr= (new HotWordManageService())->hotWordDetail($param);
            return api_output(0, $arr, "success");

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}