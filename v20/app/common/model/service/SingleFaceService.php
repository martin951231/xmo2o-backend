<?php
/**
 * 电子面单service
 * Create on 2020年9月29日09:29:57
 * Created by 钱大双
 */

namespace app\common\model\service;

use app\mall\model\service\MallOrderService;
use app\mall\model\service\MerchantStoreService;
use app\mall\model\service\MerchantStoreMallService;
use app\common\model\service\ElectronicSheetPrintService;
use express\Kd100;

class SingleFaceService
{
    /**
     * 获取快递100电子面单打印
     * @param int $order_id 订单ID
     * @param array $single_face_info 电子面单信息
     * @param int $periodic_order_id 周期购子订单ID
     * @return array
     */
    public function getSingleFace($order_id, $single_face_info, $periodic_order_id = 0)
    {
        try {
            $electronicSheetPrintService = new ElectronicSheetPrintService();
            $where = [];
            $where['order_id'] = $order_id;
            $where['order_type'] = 1;
            $where['sub_order_id'] = $periodic_order_id;
            $print_info = $electronicSheetPrintService->getInfo($where);
            $kd100 = new Kd100();
            if (!empty($print_info)) {//复打
                $result = $kd100->repeat_task($print_info['taskId']);
                if ($result['status'] == 1) {
                    $return = ['code' => 1, 'msg' => $result['msg']];
                } else {
                    if ($result['result']) {
                        $where = [];
                        $where['id'] = $print_info['id'];
                        $update = [];
                        $update['number'] = $print_info['number'] + 1;
                        $update['update_time'] = time();
                        $electronicSheetPrintService->updatePrintData($update, $where);
                        $data = [];
                        $data['order_id'] = $order_id;
                        $data['periodic_order_id'] = $periodic_order_id;
                        $data['express_num'] = $print_info['express_num'];
                        $data['express_code'] = $print_info['express_code'];
                        $return = ['code' => 0, 'data' => $data, 'msg' => '电子面单生成成功'];
                    } else {
                        $return = ['code' => 1, 'msg' => '电子面单生成失败'];
                    }
                }
            } else {
                $mallOrderService = new MallOrderService();
                $order = $mallOrderService->getOne($order_id);
                if (empty($order)) {
                    throw new \Exception('订单不存在');
                }

                //获取店铺信息
                $merchantStoreService = new MerchantStoreService();
                $store = $merchantStoreService->getOne($order['store_id']);
                $store['address'] = $store['adress'];

                $merchantStoreMallService = new MerchantStoreMallService();
                $store_mall = $merchantStoreMallService->getStoremallInfo($order['store_id'], $field = 'device_id');
                if (empty($store_mall) || empty($store_mall['device_id'])) {
                    throw new \Exception('打印机设备码为空');
                }

                if (empty($single_face_info['partner_id'])) {
                    throw new \Exception('快递公司网点账号为空');
                }

                if (empty($single_face_info['tempid'])) {
                    throw new \Exception('物流面单模板编码为空');
                }

                if (empty($single_face_info['code'])) {
                    throw new \Exception('快递公司编码为空');
                }

                //寄件人地址信息
                $sendAddress = $this->getAddress($store, 1);
                if ($sendAddress['code'] != 0) {
                    return ['code' => 1, 'msg' => $sendAddress['msg']];
                }
                $sendManPrintAddr = $sendAddress['address'];

                //收件人地址信息
                $recAddress = $this->getAddress($order, 2);
                if ($recAddress['code'] != 0) {
                    return ['code' => 1, 'msg' => $recAddress['msg']];
                }
                $recManPrintAddr = $recAddress['address'];

                //订单详情信息
                $order_detail = $mallOrderService->getOrderDetails($order_id);
                $goods = isset($order_detail['children']) ? $order_detail['children'] : [];
                $count = 0;
                $goods_name = [];
                foreach ($goods as $value) {
                    $count += $value['num'];
                    $goods_name[] = $value['goods_name'];
                }
                $cargo = implode(",", $goods_name);

                $order_data = [];
                $order_data['order_id'] = $order_id;
                $order_data['partner_id'] = $single_face_info['partner_id'];
                $order_data['kuaidicom'] = $single_face_info['code'];
                $order_data['rec_name'] = $order['username'];
                $order_data['rec_mobile'] = $order['phone'];
                $order_data['rec_address'] = $recManPrintAddr;
                $order_data['send_name'] = $store['name'];
                $order_data['send_mobile'] = $store['phone'];
                $order_data['send_address'] = $sendManPrintAddr;
                $order_data['cargo'] = $cargo;
                $order_data['count'] = $count;
                $order_data['remark'] = $order['remark'];
                $order_data['tempid'] = $single_face_info['tempid'];
                $order_data['siid'] = $store_mall['device_id'];
                $order_data['periodic_order_id'] = $periodic_order_id;
                $result = $kd100->print_task($order_data);
                if ($result['status'] == 1) {
                    $return = ['code' => 1, 'msg' => $result['msg']];
                } else {
                    if (isset($result['data']) && !empty($result['data'])) {
                        $insert = [];
                        $insert['order_id'] = $order_id;
                        $insert['sub_order_id'] = $periodic_order_id;
                        $insert['partner_id'] = $single_face_info['partner_id'];
                        $insert['tempid'] = $single_face_info['tempid'];
                        $insert['express_num'] = $result['data']['kuaidinum'];
                        $insert['taskId'] = $result['data']['taskId'];
                        $insert['express_code'] = $result['data']['kuaidicom'];
                        $insert['status'] = 10;
                        $insert['create_time'] = time();
                        $electronicSheetPrintService->addPrintData($insert);
                        $data = [];
                        $data['order_id'] = $order_id;
                        $data['periodic_order_id'] = $periodic_order_id;
                        $data['express_num'] = $result['data']['kuaidinum'];
                        $data['express_code'] = $result['data']['kuaidicom'];
                        $return = ['code' => 0, 'data' => $data, 'msg' => '电子面单生成成功'];
                    } else {
                        $return = ['code' => 1, 'msg' => $result['message']];
                    }
                }
            }
        } catch (\Exception $e) {
            $return = ['code' => 1, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 获取快递100实时快递查询
     * @param int $order_id 订单ID
     * @return array
     */
    /**获取快递100实时快递查询
     * @param $express_num     快递单号
     * @param $express_name    快递公司编码
     * @param $phone           收、寄件人的电话号码（手机和固定电话均可，只能填写一个，顺丰单号必填，其他快递公司选填。）
     * @return array
     */
    public function getSynQuery($express_num, $express_name, $phone)
    {
        try {
            if (empty($express_num)) {
                return ['code' => 1, 'msg' => '快递单号不能为空'];
            }

            if (empty($express_name)) {
                return ['code' => 1, 'msg' => '快递公司编码不能为空'];
            }

            if ($express_name == 'shunfeng' && empty($phone)) {
                return ['code' => 1, 'msg' => '收、寄件人的电话号码不能为空'];
            }

            $express = (new \app\mall\model\db\Express())->where('code', $express_name)->find();
            if ($express && $express->is_fake == 1) {
                return ['code' => 1, 'msg' => '虚拟快递不支持查询物流信息'];
            }

            $query_data = [];
            $query_data['express_num'] = $express_num;//快递单号
            $query_data['express_name'] = $express_name;//快递公司编码
            $query_data['phone'] = $phone;//收、寄件人的电话号码（手机和固定电话均可，只能填写一个，顺丰单号必填，其他快递公司选填。）

            $kd100 = new Kd100();
            $result = $kd100->logistics_query($query_data);
            if ($result['status'] == 1) {
                $return = ['code' => 1, 'msg' => $result['msg']];
            } else {
                $return = ['code' => 0, 'data' => $result['data']];
            }
        } catch (\Exception $e) {
            $return = ['code' => 1, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 获取邮寄地址信息
     * @param $data
     * @param int $type 1 寄件 2 收件
     * @return array
     */
    private function getAddress($data, $type = 1)
    {
        $return = [
            'code' => 0,
            'msg' => '',
            'address' => '',
        ];

        if ($type == 1 && empty($data['name'])) {
            $return['code'] = 1;
            $return['msg'] = '寄件人店铺名称为空';
        }

        if ($type == 2 && empty($data['username'])) {
            $return['code'] = 1;
            $return['msg'] = '收件人姓名为空';
        }

        if (empty($data['phone'])) {
            $return['code'] = 1;
            $return['msg'] = $type = 1 ? '寄件人手机号为空' : '收件人手机号为空';
            return $return;
        }

        if (empty($data['province_id'])) {
            $return['code'] = 1;
            $return['msg'] = $type = 1 ? '寄件人地址省份为空' : '收件人地址省份为空';
        }

        if (empty($data['city_id'])) {
            $return['code'] = 1;
            $return['msg'] = $type = 1 ? '寄件人地址城市为空' : '收件人地址城市为空';
        }

        if (empty($data['area_id'])) {
            $return['code'] = 1;
            $return['msg'] = $type = 1 ? '寄件人地址区域为空' : '收件人地址区域为空';
        }

        if (empty($data['address'])) {
            $return['code'] = 1;
            $return['msg'] = $type = 1 ? '寄件人地址为空' : '收件人地址为空';
        }

        if ($return['code'] == 1) {
            return $return;
        } else {
            $areaService = new AreaService();
            $areaId = [$data['province_id'], $data['city_id'], $data['area_id']];
            $where = [];
            $where[] = ['area_id', 'in', $areaId];
            $area = array_column($areaService->getAreaListByCondition($where), NULL, 'area_type');
            $return['address'] = $area[1]['area_name'] . $area[2]['area_name'] . $area[3]['area_name'] . $data['address'];
            return $return;
        }
    }
}