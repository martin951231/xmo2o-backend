<?php

namespace app\common\controller\api;

use app\common\model\service\user\SelfPickPersionService;
use Exception;

class SelfPickPersonController extends ApiBaseController
{
    /**
     * 保存自提人（新增/编辑）
     *
     * @return void
     * @author: zt
     * @date: 2024/04/26
     */
    public function save()
    {
        $this->checkLogin();
        $param = [
            'name' => $this->request->post('name', '', 'trim'),
            'phone' => $this->request->post('phone', '', 'trim'),
            'is_default' => $this->request->post('is_default', 0, 'intval'),
            'tag' => $this->request->post('tag', '', 'trim'),
            'id' => $this->request->post('id', 0, 'intval'),
        ];
        try {
            (new SelfPickPersionService())->save($this->request->log_uid, $param);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除
     *
     * @return void
     * @author: zt
     * @date: 2024/04/26
     */
    public function delete()
    {
        $this->checkLogin();
        $uid = $this->request->log_uid;
        $id = $this->request->post('id', 0, 'intval');
        try {
            (new SelfPickPersionService())->deleteById($uid, $id);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 自提人列表
     *
     * @return void
     * @author: zt
     * @date: 2024/04/26
     */
    public function getLists()
    {
        try {
            $this->checkLogin();
            $persons = (new SelfPickPersionService())->getLists($this->request->log_uid);
            return api_output(0, $persons, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 自提人详情
     *
     * @return void
     * @author: zt
     * @date: 2024/04/26
     */
    public function detail()
    {
        $this->checkLogin();
        $uid = $this->request->log_uid;
        $id = $this->request->post('id', 0, 'intval');
        $person = (new SelfPickPersionService())->detail($id, $uid);
        return api_output(0, $person, 'success');
    }
}
