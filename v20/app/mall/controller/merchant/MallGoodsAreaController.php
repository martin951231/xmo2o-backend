<?php


namespace app\mall\controller\merchant;


use app\common\model\service\AreaService;
use app\mall\model\service\MallGoodsAreaService;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\db\MerchantStoreStaff;
use think\Exception;
use token\Token;

class MallGoodsAreaController extends AuthBaseController
{
    public function initialize()
    {
        if(!empty(input('staff_ticket'))){//员工角色请求数据
            $token = Token::checkToken(input('staff_ticket'));
            //员工信息
            $storeStaff = MerchantStoreStaff::where('id', $token['memberId'])
                ->field('id staff_id, token mer_id, store_id, name')
                ->find();
            if(empty($storeStaff['mer_id'])){
                throw_exception("未登录",Exception::class, 1002);
            }
            request()->staff_info = $storeStaff;
            request()->log_uid = $storeStaff['mer_id'];
        }
        parent::initialize();
    }
    /**
     *区域地址列表
     */
    public function getAreaList()
    {
        $area = (new MallGoodsAreaService())->getAreaList();
        return api_output(0, $area, 'success');
    }
    /**
     * @return \json
     * 找到区域
     */
    public function findAreaList()
    {
        $param['goods_id'] = $this->request->param("goods_id", "0", "intval");
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $area = (new MallGoodsAreaService())->findAreaList($param);
        $assign['list']=(new MallGoodsAreaService())->getAreaList();
        $assign['check_list']=$area;
        return api_output(0, $assign, 'success');
    }
    /**
     * @return \think\response\Json
     * 商品区域添加
     */
    public function addMallGoodsArea(){
        $param['goods_id'] = $this->request->param("goods_ids", "", "trim");
        $param['area_id'] = $this->request->param("area_id", "", "trim");
        $param['level'] = $this->request->param("level", "0", "intval");
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['set_type'] = $this->request->param("set_type", "-1", "intval");
        if($param['set_type']<0){
            return api_output_error(1003, "缺少设置类型");
        }

        if(empty($param['goods_id'])){
            return api_output_error(1003, "缺少商品信息");
        }

        if(empty($param['area_id'])){
            return api_output_error(1003, "缺少区域信息");
        }

        if(empty($param['store_id'])){
            return api_output_error(1003, "缺少店铺id");
        }

        $ret=(new MallGoodsAreaService())->addMallGoodsArea($param);
        return api_output(0, $ret, 'success');
    }

    /**
     * 单个商品区域添加
     */
    public function addOneMallGoodsArea()
    {
        $param['goods_id'] = $this->request->param("goods_ids");
        $param['area_id'] = $this->request->param("area_id", "", "trim");
        $param['store_id'] = $this->request->param("store_id", "0", "intval");
        $param['set_type'] = 0;
        if (empty($param['goods_id'])) {
            return api_output_error(1003, "缺少商品信息");
        }

        if (empty($param['area_id'])) {
            return api_output_error(1003, "缺少区域信息");
        }

        if (empty($param['store_id'])) {
            return api_output_error(1003, "缺少店铺id");
        }

        $ret = (new MallGoodsAreaService())->addOneMallGoodsArea($param);
        return api_output(0, $ret, 'success');
    }

    /**
     * 批量导入商品城市区域
     * @return \think\response\Json
     */
    public function importGoodsArea(){
        $param['file_url'] = $this->request->param('file_url','','trim,string');
        $param['goods_id'] = $this->request->param('goods_ids');
        $param['mer_id'] = $this->merId;
        $param['store_id'] = $this->request->param("store_id", 0, "trim,intval");
        $param['set_type'] = 0;
        try {
            $res = (new MallGoodsAreaService())->importGoodsArea($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 商家中心导出商品城市区域
     *
     * @return void
     * @author: zt
     * @date: 2024/04/08
     */
    public function exportViewArea()
    {
        $goodsId = $this->request->param('goods_id', 0, 'intval');
        $goodsName = $this->request->param('goods_name', '', 'trim');
        try {
            $res = (new MallGoodsAreaService())->exportViewArea($goodsId, $goodsName);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}