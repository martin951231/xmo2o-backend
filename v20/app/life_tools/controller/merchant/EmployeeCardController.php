<?php


namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\EmployeeCardService;
use app\merchant\controller\merchant\AuthBaseController;

class EmployeeCardController extends AuthBaseController
{
    /**
     * 员工卡列表
     */
   public function getCardList(){
       $params['name'] = $this->request->post('name', '', 'trim');
       $params['mer_id'] =$this->merId;
       try {
           $list=(new EmployeeCardService())->getCardList($params);
       } catch (\Exception $e) {
           return api_output_error(1001, $e->getMessage());
       }
       return api_output(0, $list);
   }

    /**
     * 员工卡编辑
     */
    public function editCard(){
        $params['card_id'] = $this->request->post('card_id',0, 'intval');
        try {
            if(empty($params['card_id'])){
                return api_output_error(1001, "缺少必要参数");
            }else{
                $list=(new EmployeeCardService())->editCard($params);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 员工卡保存
     */
    public function saveCard(){
        $params['mer_id'] =$this->merId;
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        $params['name'] = $this->request->post('name', '', 'trim');
        $params['description'] = $this->request->post('description', '', 'trim');
        $params['bg_image'] = $this->request->post('bg_image', '', 'trim');
        $params['bg_color'] = $this->request->post('bg_color', '', 'trim');
        $params['status'] = $this->request->post('status', 1, 'intval');
        try {
            $ret=(new EmployeeCardService())->saveCard($params);
            if(empty($ret)){
                return api_output_error(1001, "保存失败");
            }else{
                return api_output(0, []);
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 员工卡删除
     */
    public function delCard(){
        $params['card_id'] = $this->request->post('card_id', 0, 'intval');
        try {
            if(empty($params['card_id'])){
                return api_output_error(1001, "缺少必要参数");
            }else{
                $ret=(new EmployeeCardService())->delCard($params);
                if(!empty($ret)){
                    return api_output(0, []);
                }else{
                    return api_output_error(1001, "删除失败");
                }
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }

    }
}