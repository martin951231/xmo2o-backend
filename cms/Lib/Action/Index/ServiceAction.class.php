<?php

/**
 * 服务接口类
 * @author: 张涛
 * @date: 2020/9/11
 */
class ServiceAction extends BaseAction
{
    /**
     * 调用Model方法
     * @author: 张涛
     * @date: 2020/9/11
     */
    public function invoke()
    {
        $rs = ['error_no' => 0, 'error_msg' => '', 'retval' => []];
        try {
            $raw = file_get_contents('php://input');
            $post = json_decode($raw, true);
            $token = $_SERVER['HTTP_INVOKE_AUTH_TOKEN'] ?? '';
            $func = $post['func'] ?? '';
            $params = ($post['params'] && is_array($post['params'])) ? $post['params'] : unserialize(urldecode($post['params']));
            $tm = $post['tm'] ?: 0;

            $thisToken = md5(C('config.site_url') . $tm);
            if (abs($tm - time()) > 30) {
                throw new \Exception('鉴权失败,错误码:1001');
            }

            if ($thisToken != $token) {
                throw new \Exception('鉴权失败,错误码:1002');
            }
            if (empty($func)) {
                throw new \Exception('方法不能为空');
            }

            list($model, $action) = explode('/', $func);
            if (empty($model) || empty($action)) {
                throw new \Exception('调用方法不存在');
            }
            $retval = call_user_func_array([D($model), $action], $params);
            $rs['retval'] = $retval;
        } catch (\Exception $e) {
            $rs = ['error_no' => 1, 'error_msg' => $e->getMessage()];
        }
        $this->ajaxReturn($rs);
    }
}