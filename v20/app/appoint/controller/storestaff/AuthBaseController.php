<?php

namespace app\appoint\controller\storestaff;

use app\appoint\model\service\MerchantWorkService;
use app\common\controller\CommonBaseController;
use app\http\exceptions\ParametersException;
use think\helper\Str;

class AuthBaseController extends CommonBaseController
{
    public $workUser;

    public $noCheckAction = [];

    protected $validateClass;

    public function initialize()
    {
        parent::initialize();
        // 验证登录
        if (in_array(request()->action(), $this->noCheckAction)) {
           return;
        }
        $this->checkLogin();
        
        $where = [
            'work_id' => intval($this->request->log_uid)
        ];
        $workUser = (new MerchantWorkService())->getWorkInfo($where);
        
        $this->workUser = $workUser;
    }
    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    protected function validateParameter(array $params = [], string $scenario = '', $method = 'post'): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate($this->validateClass);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw_exception(L_($validate->getError()), ParametersException::class);
        }

        return $params;
    }
    //endregion

    /**
     * 验证登录
     * @var int
     */
    private function checkLogin()
    {
        $log_uid = request()->log_uid ?? 0;
        if (empty($log_uid)) {
            throw new \think\Exception("未登录", 1002);
        }
        if(request()->log_extends != 11){
            throw_exception('账号登录异常', ParametersException::class);
        }
    }
}