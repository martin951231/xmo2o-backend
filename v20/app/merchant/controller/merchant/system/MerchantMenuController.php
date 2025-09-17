<?php
/**
 * 商家后台菜单
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\merchant\controller\merchant\system;

use app\common\model\service\MerchantService;
use app\http\exceptions\ParametersException;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\MerchantMenuService;
use app\merchant\validate\MerchantUserAccount;
use think\Exception;
use think\helper\Str;

class MerchantMenuController extends AuthBaseController
{
    //region 参数验证
    const STATUS_OK = 1000;//状态码 正常

    /**
     * 参数验证
     * @param $scenario
     * @param array $param
     * @param string $method
     * @return array|mixed
     */
    private function validateParameter($params,$scene): array
    {
        $validate = validate(MerchantUserAccount::class);
        if (!$validate->scene($scene)->check($params)) {
            throw new ParametersException(L_($validate->getError()));
        }

        return $params;
    }
    /**
     * desc: 商家后台菜单
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/5/7 09:23
     */

    /**
     * desc: 返回网站菜单信息,用于菜单展示
     * return :array
     */
    public function menuList()
    {
        // 菜单
        try{
            $merchantMenuService = new MerchantMenuService();

            $menu = $merchantMenuService->getShowMenuList($this->merchantUser);
            $menu = $merchantMenuService->formartMenuList($menu, $this->merchantUser);
    
            if($this->subAccountId){
                $menu = $merchantMenuService->filterBySubAccountStation($menu,$this->subAccountUser['station_id']);
            }
    
            $returnArr['systemMenu'] = $menu;
            
            return api_output(0, $returnArr);
        }catch(\Exception $e){
            return api_output(1003,[], $e->getMessage());
        }
        
    }

    /**
     * 商家多账户登录账号添加或修改
     * @return \think\response\Json
     */
    public function userAccountAddOrEdit()
    {
        try {
            $post = $this->request->param();
            $params = $this->validateParameter($this->request->param(), (isset($post['id']) && $post['id']) ? 'edit' : 'add');
            $params['mer_id'] = $this->merId;
            $data = app(MerchantService::class)->userAccountAddOrEdit($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output(1003,[], $e->getMessage());
        }
    }


    /**
     * 商家多账户登录账号列表
     * @return \think\response\Json
     */
    public function userAccountList()
    {
        $params['mer_id'] = $this->merId;
        $data = app(MerchantService::class)->userAccountList($params);
        return api_output(0, $data);
    }
    
    /**
     * 商家多账户登录账号删除
     * @return \think\response\Json
     */
    public function userAccountDelete()
    {
        $params = $this->request->param();
        $params['mer_id'] = $this->merId;

        $data = app(MerchantService::class)->userAccountDelete($params);
        return api_output(0, $data);
    }
    
    public function merchantMenu()
    {
        $menu = (new MerchantMenuService())->getShowMenuList($this->merchantUser);
        
        $data = (new MerchantMenuService())->formatUserAccountMenuList($menu);
        
        return api_output(0, $data);
    }


    /**
     * 岗位管理
     * @author: zt
     * @date: 2023/04/12
     */
    public function stations()
    {
        $merId = $this->request->log_uid;
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 15, 'intval');
        $data = (new MerchantService())->stations($merId, $page, $pageSize);
        return api_output(0, $data);
    }

    /**
     * 新增、编辑岗位
     * @author: zt
     * @date: 2023/04/12
     */
    public function saveStation()
    {
        $params['mer_id'] = $this->request->log_uid;
        $params['station_name'] = $this->request->param('station_name', '销售经理', 'trim');
        $params['station_desc'] = $this->request->param('station_desc', '');
        $params['menus'] = $this->request->param('menus', []);
        $params['status'] = $this->request->param('status', 0, 'intval');
        $params['id'] = $this->request->param('id', 0, 'intval');
        try {
            (new MerchantService())->saveStation($params);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 删除岗位
     * @author: zt
     * @date: 2023/04/12
     */
    public function delStation()
    {
        $id = $this->request->param('id', 0, 'intval');
        $merId = $this->request->log_uid;
        try {
            (new MerchantService())->delStation($merId, $id);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 批量导入
     *
     * @return void
     * @author: zt
     * @date: 2023/04/13
     */
    public function importAccount()
    {
        $file = $this->request->file('file');
        try {
            if (!$file) {
                return api_output(1003, [], L_('上传文件不存在'));
            }
            $result = (new MerchantService())->importAccount($file, $this->request->log_uid);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }
    
}