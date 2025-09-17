<?php
/**
 * 结婚controller
 * @author mrdeng
 * Date Time: 2021/05/31
 */

namespace app\marriage_helper\controller\api;

use app\BaseController;
use app\marriage_helper\model\service\MarriageToolService;

class MarriageToolController extends ApiBaseController
{
    /**
     *  更新浏览量
     */
    public function updateViews()
    {
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        if (empty($param['cat_id'])) {
            return api_output_error(1003, L_('参数缺失'));
        }

        $ret = (new MarriageToolService())->setInc($param,$field="views_num");
        if ($ret) {
            return api_output(0, 0,"更新成功");
        } else {
            return api_output_error(1003, L_('更新浏览量失败'));
        }
    }

    /**
     *结婚攻略前端页面列表
     */
    public function getToolList(){
        $category_list=(new MarriageToolService())->getToolList();
        return api_output(0, $category_list);
    }

    /**
     * @return \json
     * 结婚攻略前端页面有用没用
     */
    public function isToolUse(){
        try{
        $user = request()->user;
        $param['uid'] = $user['uid'] ?? 0;
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        $param['is_use'] = $this->request->param("is_use", "0", "intval");
        $param['add_time'] =time();
        if(!$param['cat_id']){
            return api_output_error(1003, L_('攻略id获取失败'));
        }
        if($param['uid']){
            $ret=(new MarriageToolService())->isToolUse($param);
            if(!empty($ret)){
                return api_output(0, $ret,"更新成功");
            }else{
                return api_output_error(1003,"更新失败");
            }
        }else{
            return api_output_error(1002, L_('获取用户id是失败'));
        }
        }catch (\Exception $e){
                dd($e);
        }

    }

    /**
     * @return \json
     * 获取攻略信息
     */
    public function getToolMsg(){
        $param['cat_id'] = $this->request->param("cat_id", "0", "intval");
        if(!$param['cat_id']){
            return api_output_error(1003, L_('攻略id获取失败'));
        }
        $ret=(new MarriageToolService())->editCategory($param);
        if($ret){
            return api_output(0, $ret);
        }else{
            return api_output_error(1001, [],"获取失败");
        }
    }
    /**
     * 前端结婚计划设置婚期
     */
    public function setMarriageDate(){
        $user = request()->user;
        $param['uid'] = $user['uid'] ?? 0;
        $param['marry_date'] = $this->request->param("marry_date", "", "trim");
        if($param['uid'] && !empty($param['marry_date'])) {
            $ret=(new MarriageToolService())->setMarriageDate($param);
            if($ret!==false){
                return api_output(0, [], L_("设置成功"));
            }else{
                return api_output_error(1003, L_('设置失败'));
            }
        }else{
            return api_output_error(1002, L_('参数缺失'));
        }
    }

    /**
     * 前端结婚计划获取婚期
     */
    public function getMarriageDate()
    {
        $user = request()->user;
        $param['uid'] = $user['uid'] ?? 0;
        if($param['uid']) {
            $ret=(new MarriageToolService())->getMarriageDate($param);
            if($ret['status']){
                return api_output(0, $ret['data']);
            }else{
                return api_output_error(1002, L_('获取用户信息失败'));
            }
        }else{
            return api_output_error(1002, L_('获取用户信息失败'));
        }
    }

    /**
     * 婚姻登记处
     */
   public function getMarriageAddr(){
       $user = request()->user;
       $param['uid'] = $user['uid'] ?? 0;
       $param['user_lng'] = $this->request->param("user_lng", "", "trim");
       $param['user_lat'] = $this->request->param("user_lat", "", "trim");
       $param['area_name'] = $this->request->param("area_name", "", "trim");
       if($param['uid']) {
           $ret=(new MarriageToolService())->getMarriageAddr($param);
           if($ret['status']==0){
               return api_output_error(1003, $ret['msg']);
           }else{
               return api_output(0, $ret['list']);
           }
       }else{
           return api_output_error(1002, L_('获取用户信息失败'));
       }
   }

}