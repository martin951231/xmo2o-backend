<?php


namespace app\common\controller\platform;

use app\common\controller\CommonBaseController;
use app\common\model\service\diypage\DiypageSearchHotService;

class HotWordsController extends CommonBaseController
{
    /**
     * @return \json
     * 获取热门搜索词列表
     */
    public function getHotWordsList(){
        // $rs = (new DiypageSearchHotService)->getHotWordsList(0, 'name,sort');
        // return api_output(0, $rs);

        try {
            $param['page'] = $this->request->param('page', 1, 'intval');
            $param['pageSize'] = $this->request->param('pageSize', 20, 'intval');
            $param['source_id'] = $this->request->param('source_id', 0, 'intval');
            $param['source'] = $this->request->param('source', '', 'trim');
            $rs = (new DiypageSearchHotService())->getHotWordsList($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
   }

   /**
     * 保存
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function getHotWordsEdit()
    {
        try {
            
            $param['id'] = $this->request->param('id', 0, 'intval');
            $param['name'] = $this->request->param('name', '', 'trim');
            $param['sort'] = $this->request->param('sort', 0, 'intval');
            $param['source_id'] = $this->request->param('source_id', 0, 'intval');
            $param['source'] = $this->request->param('source', '', 'trim');
            $rs = (new DiypageSearchHotService())->getHotWordsEdit($param);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 获取一条记录
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function getWordDetail()
    {
        try {
            $id = $this->request->param('id', 0, 'intval');
            $rs = (new DiypageSearchHotService())->getOneHotWordsId($id);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 保存排序
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function getHotWordsEditSort()
    {
        try {
            $id = $this->request->param('id', 0, 'intval');
            $sort = $this->request->param('sort', 0, 'intval');
            $rs = (new DiypageSearchHotService())->getHotWordsEditSort($id, $sort);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 删除关键词
     * @author: 汪晨
     * @date: 2021/04/28
     */
    public function delWords()
    {
        try {
            $ids = $this->request->param('ids', []);
            $rs = (new DiypageSearchHotService())->delWords($ids);
            return api_output(0, $rs);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}