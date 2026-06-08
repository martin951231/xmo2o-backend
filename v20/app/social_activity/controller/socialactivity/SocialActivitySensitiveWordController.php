<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivitySensitiveWordService;
use app\social_activity\validate\SocialActivitySensitiveWordValidate;

class SocialActivitySensitiveWordController extends BaseController
{
    protected $validateClass = SocialActivitySensitiveWordValidate::class;

    /**
     * 获取敏感词列表
     */
    public function getList()
    {
        $param['page'] = $this->request->param('page', 1, 'int');
        $param['pageSize'] = $this->request->param('pageSize', 20, 'int');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['status'] = $this->request->param('status', 0, 'int');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');

        $sensitiveWordService = new SocialActivitySensitiveWordService();
        try {
            $data = $sensitiveWordService->getList($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
		return json([
			'status' => 1000,
			'msg' 	 => "success",
			'data' 	 => $data
		]);
    }

    /**
     * 保存敏感词（新增/编辑）
     */
    public function save()
    {
        $param['id'] = $this->request->param('id', 0, 'int');
        $param['word'] = $this->request->param('word', '', 'trim');
        $param['status'] = $this->request->param('status', 1, 'int');
        $scene = $param['id'] > 0 ? 'edit' : 'add';
        $param = $this->validateParameter($param, $scene);
        $sensitiveWordService = new SocialActivitySensitiveWordService();
        try {
            $result = $sensitiveWordService->save($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        if ($param['id'] > 0) {
            $msg = "编辑成功";
        } else {
            $msg = isset($result['msg']) ? $result['msg'] : "新增成功";
        }
        return api_output(0, $result['data'] ?? [], $msg);
    }

    /**
     * 删除敏感词
     */
    public function delete()
    {
        $param = $this->validateParameter([], 'delete');
        $sensitiveWordService = new SocialActivitySensitiveWordService();
        try {
            $sensitiveWordService->delete($param);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, [], "删除成功");
    }
}
