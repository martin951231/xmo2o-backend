<?php
/**
 * LinkController.php
 * 功能库
 * Create on 2020/11/14 11:30
 * Created by zhumengqun
 */

namespace app\common\controller\common;

use app\common\controller\CommonBaseController;
use app\common\controller\platform\AuthBaseController;
use app\common\model\service\LinkService;

class LinkController extends CommonBaseController
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取连接类型
     */
    public function getLinkCategory()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $type = $this->request->param('type', 'h5', 'trim');
        $linkService = new LinkService();
        $res = $linkService->getLinkCategory($source, $source_id, $type);
        try {
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 根据连接类型获取连接主体（样式 内容等信息）
     */
    public function getLinkContent()
    {
        $label = $this->request->param('label', 'commonPages', 'trim');
        $systemUser = [];
        $linkService = new LinkService();
        try {
            $res = $linkService->getLinkContent($label, $systemUser);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function getList()
    {
        $source = $this->request->param('source', 'platform', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');
        $label = $this->request->param('label', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('pageSize', 10, 'intval');
        $store_id = $this->request->param('store_id', 0, 'intval');
        $systemUser = [];
        $linkService = new LinkService();

        if ($source == 'merchant' && empty($source_id) && empty($store_id)) {
            $source_id = $this->request->log_uid;
        }

        try {
            $res = $linkService->getList($label, $systemUser, $keyword, $page, $pageSize, $source, $source_id, $store_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}