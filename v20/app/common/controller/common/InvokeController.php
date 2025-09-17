<?php
/**
 * 供外部调用service
 * Author: 衡婷妹
 * Date Time: 2020/11/05
 */

namespace app\common\controller\common;
use app\common\controller\CommonBaseController;

class InvokeController extends CommonBaseController
{
    /**
     * Author: hengtingmei
     * @param 传参
     * $func=>方法名
     * $params=[
     * 参数1=》参数值 ,
     * 参数2=》参数值
     * ]
     * @return array
     */
    public function index()
    {
        $rs = ['error_no' => 0, 'error_msg' => '', 'retval' => []];
        try {
            $raw = file_get_contents('php://input');
            $post = json_decode($raw, true);
            $token = $_SERVER['HTTP_INVOKE_AUTH_TOKEN'] ?? '';
            $func = $post['func'] ?? '';
            $params = $post['params'] ?? [];
            $tm = $post['tm'] ?: 0;

            $thisToken = md5(cfg('site_url') . $tm);
            if (abs($tm - time()) > 30) {
                throw new \Exception('鉴权失败,错误码:1001');
            }

            if ($thisToken != $token) {
                fdump_sql([$post, $thisToken], 'auth_fail');
                throw new \Exception('鉴权失败,错误码:1002');
            }
            if (empty($func)) {
                throw new \Exception('方法不能为空');
            }

            list($model, $action) = explode('/', $func);
            if (empty($model)) {
                throw new \Exception('模型参数不能为空');
            }
            if (empty($action)) {
                throw new \Exception('方法参数不能为空');
            }
            $retval = call_user_func_array([new $model, $action], $params);
            $rs['retval'] = $retval;
        } catch (\Exception $e) {
            $rs = ['error_no' => 1, 'error_msg' => $e->getMessage()];
        }
        return api_output(0,$rs);
    }
}
