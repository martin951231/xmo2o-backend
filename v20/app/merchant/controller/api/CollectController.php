<?php
/**
 * 用户收藏店铺、商品
 * author by hengtingmei
 * Date Time: 2020/12/04 09:38
 */
namespace app\merchant\controller\api;

use app\merchant\model\service\MerchantUserRelationService;

class CollectController extends ApiBaseController {

    /**
     * desc: 用户收藏取消店铺、商品
     * return :array
     */
    // 收藏店铺 商品
    public function userCollect(){
        $param = $this->request->param();

        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $param['uid'] = $this->_uid;
        $result = (new MerchantUserRelationService())->userCollect($param);

        return api_output(0, $result,$result['msg']);
    }

    /**
     * desc: 获得收藏的列表
     * return :array
     */
    // 收藏店铺 商品
    public function getCollectList(){
        $param = $this->request->param();
        $type = $this->request->param('type');

        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $param['uid'] = $this->_uid;
        $result = (new MerchantUserRelationService())->getCollectList($this->_uid, $type, $param);

        return api_output(0, $result);
    }


}