<?php
/**
 * 打印机service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/12 11:45
 */

namespace app\merchant\model\service\print_order;
use app\foodshop\model\service\order_print\DiningPrintRulePrintService;
use app\merchant\model\db\Orderprinter as OrderprinterModel;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use think\facade\Cache;
use net\Http;
class OrderprintService {
    public $orderprinterModel = null;
    public $printType = null;
    public function __construct()
    {
        $this->orderprinterModel = new OrderprinterModel();
        $this->printType = [
            '1' => L_('易联云打印机'),
            '2' => L_('易联云打印机'),
            '1' => L_('易联云打印机'),
        ];

    }

    /**
     * 店员app自动添加打印机
     * @param $print array 打印机数据
     * @return string
     */
    public function addPrintAutoByStaff($param) {
        $mkey = $param['mkey'] ?? '';
        $mcode = $param['mcode'] ?? '';
        $appVersion = $param['app_version'] ?? '';
        $staffUser = $param['staff'] ?? [];
        if(empty($staffUser)){
            throw new \think\Exception(L_('请先登录'), 1002);
        }

        $condition['mkey'] 	= $mkey;
        $condition['store_id']	= $staffUser['store_id'];
        $print = $this->getOne($condition);
        if($print){// 存在打印机更新版本号
            // 更新版本号
            if ($print['version'] != $appVersion) {
                $data = [
                    'version' => $appVersion
                ];
                $this->updateThis($condition,$data);
            }
            return true;
        }else{
            if($mkey && $mcode){// 添加此店铺的打印机
                $where = [
                    'mcode' => $mcode,
                    'mkey' => $mkey
                ];
                $this->orderprinterModel->where($where)->delete();

                $dataPrint['mer_id'] = $staffUser['token'];
                $dataPrint['store_id'] = $staffUser['store_id'];
                $dataPrint['version'] = $appVersion;
                $dataPrint['mcode'] = $mcode;
                $dataPrint['mkey'] = $mkey;
                $dataPrint['count'] = 1;
                $dataPrint['paid'] = "1,5,2,3";
                $dataPrint['store_print'] = "1";
                $dataPrint['name'] = L_('手持设备');
                $dataPrint['is_main'] = 1;
                $dataPrint['print_type'] = 6;
                $dataPrint['print_way'] = 2;
                $dataPrint['add_time'] = time();
                if($this->add($dataPrint)){
                    return true;
                }else{
                    fdump_sql(M('Orderprinter'),'add_printer_error');
                }
            }
            throw new \think\Exception(L_('打印机不存在'), 1003);
        }
        return true;
    }

    /**
     * 店员app打印轮询打印
     * @param $print array 打印机数据
     * @return string
     */
    public function ownPrintWork($param) {
        $mkey = $param['mkey'] ?? '';
        $appVersion = $param['app_version'] ?? '';
        $appVersionName = $param['app_version_name'] ?? '';
        $staffUser = $param['staff'] ?? [];
        if(empty($staffUser)){
            throw new \think\Exception(L_('请先登录'), 1002);
        }

        $return = [];

        $mkeyLastTime = Cache::get('own_print_work' . $mkey);
        if($mkeyLastTime > time() - 2){
            $return['info'] = '';
            return $return;
        }
        Cache::set('own_print_work'.$mkey,time());

        if($staffUser){
            // 保存最后打印时间
            $saveData = ['last_print_time'=>time()];
            $where = [
                'id' => $staffUser['id']
            ];
            (new MerchantStoreStaffService())->updateThis($where , $saveData);
        }

        if(empty($mkey)){
            throw new \think\Exception(L_('请携带密钥值'), 1003);
        }

        fdump($appVersionName, 'api/log/' . date('Ymd') . '/own_print_work/' . $mkey, true);

        $return['info'] = $this->getOwnPrinter($mkey, $appVersion);
        return $return;
    }

    /**
     * 获得店员app打印的内容
     * @param $print array 打印机数据
     * @return string
     */
    function getOwnPrinter($mkey, $appVersion = 0)
    {
        if(cfg('print_server_own')){
            $condition = [
                ['mkey','=',$mkey],
                ['print_time','=',0],
                ['add_time','>=',time() - 600],
            ];
            $order = [
                'print_id' => 'ASC'
            ];
            $print = (new OrderprintListService())->getOne($condition,true, $order);

            if($print){
                $saveData = ['print_time'=>time()];
                $where = [
                    'print_id' => $print['print_id']
                ];
                (new OrderprintListService())->updateThis($where, $saveData);

                $printArr = explode(PHP_EOL,$print['content']);
                if(strstr(PHP_OS, 'WIN')){
                    $printArr = explode("\n",$print['content']);
                }else{
                    $printArr = explode(PHP_EOL,$print['content']);
                }

                if(isset($printArr[0]) && empty($printArr[0])){
                    unset($printArr[0]);
                }

                $printArr = array_values($printArr);
                if($printArr){
                    if(empty($appVersion) || $appVersion < 1400){
                        if($printArr[0] == '【尺寸80mm】' || $printArr[0] == '【尺寸58mm】'){
                            unset($printArr[0]);
                            $printArr = array_values($printArr);
                        }

                        if($printArr[0] == '【小号字】' || $printArr[0] == '【中号字】' || $printArr[0] == '【大号字】'){
                            unset($printArr[0]);
                            $printArr = array_values($printArr);
                        }
                    }
                }
                //$printArr[] = L_('打印时间') . '：' . date('m-d H:i');
                $return = implode('<br/>',$printArr);
            }else{
                $return = '';
            }
            return $return;
        }else{
            $url = 'http://up.pigcms.cn/server.php?m=server&c=orderPrint&domain=pigcms.com&a=getcableprint&utf8=1&mkey='.$mkey;

            $return = Http::curlGet($url);
            if($return == '-1'){
                $return = '';
            }else if(strpos($return,'<html>') !== false || strpos($return,'</head>') !== false || strpos($return,'502 Bad Gateway') !== false){
                $return = '';
            }else{
                $return_arr = explode('||&&||',$return);
                foreach($return_arr as &$value){
                    $value = trim($value);
                }
                $return = implode('<br/>',$return_arr);
            }
            return $return;
        }
    }

    /**
     * 根据条件获取打印机类型
     * @param $print array 打印机数据
     * @return string
     */
    public function getPrintType($print) {
        if($print['print_type'] == 1 || ($print['print_type'] == 0 && strlen($print['mcode']) != 6)){
            return L_("易联云打印机");
        }elseif($print['print_type'] == 2 || $print['mcode'] == '888888'){
            return L_("有线打印机");
        }elseif($print['print_type'] == 3 || (strlen($print['mcode']) == 6 && $print['mcode'] >= 600000)){
            return L_("蓝牙打印机");
        }elseif($print['print_type'] == 4){
            return L_("飞鹅打印机");
        }elseif($print['print_type'] == 5){
            return L_("云打印机");
        }elseif($print['print_type'] == 6){
            return L_("手持POS终端");
        }elseif($print['print_type'] == 7){
            return L_("大趋云打印机");
        }
        return L_("未知打印机类型");
    }

    /**
     * 根据条件获取数据
     * @param $where array
     * @return array
     */
    public function getPrintList($param) {
        $storeId = $param['store_id'] ?? 0;

        // 是否是获得打印机规则绑定的打印机列表
        $isBindRule = $param['is_bind_rule'] ?? 0;
        $id = $param['id'] ?? 0;

        $where = [];
        if($storeId){
            $where[] = ['store_id', '=', $storeId];
        }

        if($isBindRule){
            // 去掉已经绑定的打印机
            $wherePrint = [
                ['r.store_id', '=', $storeId],
                ['r.reciept_type', '=', 1]
            ];
            $id && $wherePrint[] = ['b.rule_id','<>',$id];
            $bindPrintList = (new DiningPrintRulePrintService())->getBindPrintList($wherePrint, 'b.*');
            if($bindPrintList){
                $bindPrintId = array_column($bindPrintList,'print_id');
                $where[] = ['pigcms_id', 'not in', implode(',',$bindPrintId)];
            }
        }
        // 查询列表
        $printList = $this->getList($where);
        foreach ($printList as &$print ){
            // 打印机类型
            $print['print_type_txt'] = $this->getPrintType($print);
            // 纸张类型
            $print['paper_txt'] = $print['paper'] ? L_('80mm') : L_('58mm');
            $print['key'] = $print['pigcms_id'];
        }

        $returnArr['list'] = $printList;
        return $returnArr;
    }

    /**
     * 根据条件一条数据
     * @param $where array 
     * @return array
     */
    public function getOne($where, $field=true, $order = []) {
        if(empty($where)){
           return [];
        }

        $print = $this->orderprinterModel->getOne($where, $field, $order);
//        var_dump($print);
        if(!$print) {
            return [];
        }
        
        return $print->toArray(); 
    }

    /**
     * 根据条件获取数据
     * @param $where array 
     * @return array
     */
    public function getList($where, $order=[]) {
        if(empty($where)){
           return [];
        }

        $printList = $this->orderprinterModel->getList($where, $order);
//        var_dump($this->orderprinterModel->getLastSql());
        if(!$printList) {
            return [];
        }
        
        return $printList->toArray(); 
    }


    /**
     *插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $id = $this->orderprinterModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }


    /**
     * 更新店铺数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || !$data){
            return false;
        }

        $result = $this->orderprinterModel->updateThis($where, $data);
        if($result === false){
            return false;
        }

        return $result;
    }

}