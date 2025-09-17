<?php
/**
 * 后台微信小程序订阅消息模板管理
 * Author: 衡婷妹
 * Date Time: 2020/10/26
 *
 */
namespace app\common\controller\platform\weixin;
use app\common\controller\platform\AuthBaseController;
use app\common\model\service\weixin\WxappTemplateService;

class WxappTemplateController extends AuthBaseController{

   
    /**
     * desc: 获得微信小程序订阅消息模板列表
     * return :array
     */
    public function getWxappTemplateList(){
        $param['page'] = $this->request->param('page','0','intval');
    	$returnArr = (new WxappTemplateService())->getWxappTemplateList($param);
        return api_output(0, $returnArr);
    }


    /**
     * desc: 修改
     * return :array
     */
    public function editWxappTemplate(){
        $param['id'] = $this->request->param('id','0','intval');
        $param['status'] = $this->request->param('status','0','intval');
        $returnArr = (new WxappTemplateService())->editWxappTemplate($param);
        return api_output(0, $returnArr);
    }

    /**
     * desc: 获取模板id
     * return :array
     */
    public function addTemplate(){
        $param['id'] = $this->request->param('id','0','intval');
//        try {
            $returnArr = (new WxappTemplateService())->addTemplate($param);
//        }catch (\Exception $e){
//            return api_output_error(1003, $e->getMessage());
//
//        }
        return api_output(0, $returnArr);
    }




}