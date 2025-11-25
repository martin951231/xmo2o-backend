<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物料信息
 */

namespace app\community\model\service;

use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseWorker;
use app\community\model\db\MaterielCheckout;
use app\community\model\db\MaterielCheckoutList;
use app\community\model\db\MaterielTypeMsg;

class MaterielTypeMsgService
{
    public function getList($where, $field = true, $order = true, $page = 0, $listRows = 0)
    {
        return (new MaterielTypeMsg())->getList($where, $field, $order, $page, $listRows);
    }

    public function materielCheckoutsAdd($params)
    {
        $receive_id = $params['receive_id'] ?? 0;
        if (!$receive_id) {
            throw new \think\Exception("请选择领用人！");
        }
        $receive_name = $params['receive_name'] ?? '';
        $receive_phone = $params['receive_phone'] ?? '';
        $receive_time = $params['receive_time'] ?? '';
        if (!$receive_time) {
            throw new \think\Exception("请选择领取时间！");
        }
        $type = $params['type'] ?? [];
        if (empty($type)) {
            throw new \think\Exception("最少填写一个物料类别领取数量！");
        }
        $village_id = $params['village_id'] ?? 0;
        $db_materiel_type_msg = new MaterielTypeMsg();
        $db_materiel_checkout = new MaterielCheckout();
        $db_materiel_checkout_list = new MaterielCheckoutList();
        $db_house_worker = new HouseWorker();
        $typeArray = [];
        foreach ($type as $item) {
            $type_id = isset($item['type_id']) ? intval($item['type_id']) : 0;
            if ($type_id <= 0) {
                continue;
            }
            $receive_num = isset($item['receive_num']) ? intval($item['receive_num']) : 0;
            if ($receive_num <= 0) {
                $typeInfo = $db_materiel_type_msg->getOne(['type_id', 'type_name'],'type_name');
                $type_name = isset($typeInfo['type_name']) ? $typeInfo['type_name'] : '';
                throw new \think\Exception("物料类别【{$type_name}】请填写大于0的领取数量或者删除此项！");
                break;
            }
            $remark = isset($item['remark']) ? $item['remark'] : '';
            $typeArray[] = [
                'type_id' => $type_id, 'receive_num' => $receive_num, 'remark' => $remark,
            ];
        }
        if (empty($typeArray)) {
            throw new \think\Exception("最少填写一个正确物料类别领取数量信息！");
        }
        if ($receive_time) {
            $receive_time = strtotime($receive_time);
        }
        if (!$receive_name) {
            $house_worker = $db_house_worker->getOne(['wid' => $receive_id],'wid,name,nickname,phone');
            if (isset($house_worker['name']) && $house_worker['name']) {
                $receive_name = $house_worker['name'];
            } elseif (isset($house_worker['nickname']) && $house_worker['nickname']) {
                $receive_name = $house_worker['nickname'];
            }
            if (!$receive_phone && isset($house_worker['phone']) && $house_worker['phone']) {
                $receive_phone = $house_worker['phone'];
            }
        }
        $nowTime = time();
        foreach ($typeArray as $typeItem) {
            $type_id = isset($typeItem['type_id']) ? intval($typeItem['type_id']) : 0;
            $receive_num = isset($typeItem['receive_num']) ? intval($typeItem['receive_num']) : 0;
            $remark = isset($typeItem['remark']) ? $typeItem['remark'] : '';
            $where = [];
            $where['type_id'] = $type_id;
            $type_msg = $db_materiel_type_msg->getOne($where,'type_id,type_name,from,from_id,stock_num, checkout_num, stock_num-checkout_num as surplus_num');
            if ($type_msg && !is_array($type_msg)) {
                $type_msg = $type_msg->toArray();
            }
            if (empty($type_msg)) {
                throw new \think\Exception("领取物料不存在或者已经被删除！");
                break;
            }
            if(isset($type_msg['surplus_num']) && intval($type_msg['surplus_num']) < $receive_num) {
                $type_name = isset($type_msg['type_name']) ? $type_msg['type_name'] : '';
                throw new \think\Exception("物料类别【{$type_name}】当前不能领取{$receive_num}份，剩余库存为{$type_msg['surplus_num']}份！");
                break;
            }
            $data = array(
                'type_id' => $type_id,
                'receive_id' => $receive_id,
                'receive_num' => $receive_num,
                'receive_phone' => $receive_phone,
                'receive_name' => $receive_name,
                'receive_time' => $receive_time,
                'remark' => $remark,
                'from' => isset($type_msg['from']) && $type_msg['from'] ? $type_msg['from'] : 0,
                'from_id' => isset($type_msg['from_id']) && $type_msg['from_id'] ? $type_msg['from_id'] : $village_id,
            );
            $data['add_time'] = $nowTime;
            $add_id = $db_materiel_checkout->add($data);
            if ($add_id) {
                $checkoutTotal = $db_materiel_checkout->getSum(['type_id' => $type_id], 'receive_num');
                $db_materiel_type_msg->updateThis(['type_id' => $type_id], ['checkout_num' => $checkoutTotal]);
                $stock_num = $type_msg['stock_num'];
                $surplus_num = $stock_num - $checkoutTotal;
                if ($surplus_num < 0) {
                    $surplus_num = 0;
                }
                $checkoutData = ['stock_num' => $stock_num, 'surplus_num' => $surplus_num];
                $db_materiel_checkout->updateThis(['id' => $add_id], $checkoutData);
                // 出库成功 同步生成等数量的 物料出库详细
                $detail = array(
                    'checkout_id' => $add_id, 'type_id' => $type_id, 'status' => 0, 'add_time' => $nowTime
                );
                $add_all = array();
                for ($i=0;$i<$receive_num;$i++) {
                    $add_all[]=$detail;
                }
                $add_all_id = $db_materiel_checkout_list->addAll($add_all);
                if (!$add_all_id) {
                    fdump_api(['$add_all' => $add_all, '$data' => $data], 'checkout_add_err_log', 1);
                }
                $worker = $db_house_worker->getOne(['wid' => $receive_id]);
                $first = '领用物料通知';
                $type_name = '';
                if ($type_msg) {
                    $keyword1 = '领取物料【' . $type_msg['type_name'].'】';
                    $type_name = '【' . $type_msg['type_name'].'】';
                } else {
                    $keyword1 = '领取物料';
                }
                $href = get_base_url('pages/village/commonly_used/pickUpMaterials?receive_id='.$receive_id);
                $keyword2 = '待领取者确认';
                if (isset($worker['openid']) && $worker['openid']) {
                    $templateNewsService = new TemplateNewsService();
                    $dataMsg = array(
                        'tempKey' => 'OPENTM400166399',
                        'dataArr' => [
                            'href' => $href,
                            'wecha_id' => $worker['openid'],
                            'first' => $first,
                            'keyword1' => $keyword1,
                            'keyword2' => $keyword2,
                            'keyword3' => date("Y年m月d日 H:i"),
                            'remark' => '点击查看并确认',
                            'new_info' => [//新版本发送需要的信息
                                'tempKey'=>'61396',//新模板号
                                'thing1'=>$first.$type_name,//服务项目
                                'const2'=>$keyword2,//订单类型
                                'time3'=>date("Y年m月d日 H:i"),//服务时间
                            ],
                        ],
                    );
                    $templateNewsService->sendTempMsg($dataMsg['tempKey'], $dataMsg['dataArr']);
                }
            } else {
                throw new \think\Exception("添加失败！");
                break;
            }
        }
        return $typeArray;
    }
}