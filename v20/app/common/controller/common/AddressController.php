<?php
/**
 * 地址改版controller
 * Author: zhumengqun
 * Date Time: 2020/08/27
 */

namespace app\common\controller\common;

use app\common\controller\CommonBaseController;
use app\common\model\service\address\AddressService;
use app\common\model\service\address\AutoAddressService;
class AddressController extends CommonBaseController
{
    /**
     * 展示地址列表
     * @param
     * @return array
     */
    public function getAddrList()
    {
        $log_uid = request()->log_uid ? request()->log_uid : 0;
        $goods_id = $this->request->param('goods_id');
        $is_get_mall_area = $this->request->param('is_get_mall_area',0,'trim,intval');
        $addrService = new AddressService();
        /*try {*/
            $result = $addrService->getAllList($log_uid,$goods_id,$is_get_mall_area);
        /*} catch (\Exception $e) {
            return api_output_error(1002, $e->getMessage());
        }*/
        return api_output(0, $result);
    }

    /**
     * 编辑地址
     * @param $address_id
     * @return array
     */
    public function getEditAddr()
    {
        $log_uid = request()->log_uid ? request()->log_uid : 0;
        $address_id = $this->request->param('address_id');
        $addrService = new AddressService();
        try {
            $result = $addrService->getEditAddr($log_uid, $address_id);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取手机区号
     * @param
     * @return array
     */
    public function getAreaCode()
    {
        $addrService = new AddressService();
        try {
            $phoneCode = $addrService->getAreaCode();
            return api_output(0, $phoneCode);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取搜索地址
     * @param uid
     * @return array
     */
    /* public function getMapAddr()
    {

    }*/

    /**
     * 自动识别手机 号码等信息
     * @param $info
     * @return array
     */
    public function formatInfo()
    {
        $info = $this->request->param("info", "", "trim");
        try {
            $results = (new AutoAddressService())->formatInfo($info);
            return api_output(0, $results);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 自动识别手机 号码等信息（新）
     * @param $info
     * @return array
     */
    public function formatInfoNew()
    { 
        $info = $this->request->param("info", "", "trim");
        try {
            $results = (new AutoAddressService())->formatInfoNew($info);
            return api_output(0, $results);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑保存或新增保存地址
     * @param
     * @return array
     */
    public function addOrEditAddr()
    {
        $arr['uid'] = request()->log_uid ? request()->log_uid : 0;
        $arr['adress_id'] = $this->request->param("address_id", 0, "trim");
        $arr['name'] = $this->request->param("name", "", "trim");
        $arr['adress'] = $this->request->param("address", "", "trim");
        $arr['detail'] = $this->request->param("detail", "", "trim");
        $arr['default'] = $this->request->param("defaults", 0, "trim");
        $arr['tag'] = $this->request->param("tag", "", "trim");
        $arr['latitude'] = $this->request->param("lat", "", "trim");
        $arr['longitude'] = $this->request->param("lng", "", "trim");
        $arr['phone'] = $this->request->param("phone", "", "trim");
        $arr['phone_country_type'] = $this->request->param("phone_country_type", "", "trim");
        $arr['sex'] = $this->request->param("sex", 1, "trim");
        
        try {
            $results = (new AddressService())->addOrEditAddr($arr);
            return api_output(0, $results,'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除地址
     * @param $address_id
     * @return array
     */
    public function delAddr()
    {
        $log_uid = request()->log_uid ? request()->log_uid : 0;
        $address_id = $this->request->param('address_id');
        $addrService = new AddressService();
        try {
            $result = $addrService->delAddr($log_uid, $address_id);
            return api_output(0, $result,'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}

/*getAddrList()  //展示地址列表
getEditAddr()   //编辑地址
getAreaCode() //获取手机区号
getMapAddr() //获取搜索地址
formatInfo()  //自动识别手机 号码等信息
addOrEditAddr() //编辑保存或新增保存地址
delAddr()      //删除地址*/