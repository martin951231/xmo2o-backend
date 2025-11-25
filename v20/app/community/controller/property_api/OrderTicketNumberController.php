<?php
/**
 * @author : liukezhu
 * @date : 2022/2/25
 */

namespace app\community\controller\property_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageOrderTicketNumberService;
use app\community\model\service\HouseVillageService;


class OrderTicketNumberController extends CommunityBaseController
{

    public function getOrderTicketNumberList()
    {
        $page = $this->request->param('page', 1, 'intval');
        $ticket_number = $this->request->post('ticket_number', '', 'trim');
        $ticket_status = $this->request->post('ticket_status', '', 'intval');
        $village_id = $this->request->post('village_id', '', 'intval');
        $add_date = $this->request->post('add_date', '');
        $use_date = $this->request->post('use_date', '');
        try {
            $where = array();
            $where[] = ['property_id', '=', $this->adminUser['property_id']];
            $where[] = ['del_time', '<', 1];
            if ($village_id > 0) {
                $where[] = array('village_id', '=', $village_id);
            }
            if ($ticket_number) {
                $where[] = array('ticket_number', 'like', '%' . $ticket_number . '%');
            }
            if ($ticket_status > 0) {
                $ticket_status = $ticket_status - 1;
                $where[] = array('ticket_status', '=', $ticket_status);
            }
            if ($add_date && isset($add_date['0']) && !empty($add_date['0']) && isset($add_date['1'])) {
                $where[] = ['add_time', '>=', strtotime($add_date['0'])];
                $where[] = ['add_time', '<=', strtotime($add_date[1] . ' 23:59:59')];
            }
            if ($use_date && isset($use_date['0']) && !empty($use_date['0']) && isset($use_date['1'])) {
                $where[] = ['use_time', '>=', strtotime($use_date[0])];
                $where[] = ['use_time', '<=', strtotime($use_date[1] . ' 23:59:59')];
            }
            $field = '*';
            $houseVillageOrderTicketNumberService = new HouseVillageOrderTicketNumberService();
            $res = $houseVillageOrderTicketNumberService->getTicketNumberList($where, $field, $page, 10);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }


    public function generateTicketNumber()
    {
        $ticket_prefix = $this->request->post('ticket_prefix', '', 'trim');  //前缀
        $start_num = $this->request->post('start_num', 0, 'intval');   //开始数字
        $total_num = $this->request->post('total_num', 0, 'intval');  //要生成的数量
        $village_id = $this->request->post('village_id', 0, 'intval');  //生成的小区
        $houseVillageOrderTicketNumberService = new HouseVillageOrderTicketNumberService();
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        if ($village_id < 1) {
            return api_output_error(1001, '请选择一个绑定生成小区！');
        }
        if ($start_num < 1) {
            return api_output_error(1001, '请填写票据编号起始数字！');
        }
        if ($total_num < 1) {
            return api_output_error(1001, '请填写生成票据编号的数量！');
        }
        try {
            $generateArr = array('ticket_prefix' => $ticket_prefix, 'start_num' => $start_num, 'total_num' => $total_num, 'village_id' => $village_id);

            $generate_count = $houseVillageOrderTicketNumberService->generateTicketNumber($property_id, $generateArr);
            $returnArr = array('generate_count' => $generate_count, 'msg' => '生成成功！');
            return api_output(0, $returnArr);
                
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function delTicketNumber()
    {
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $idd = $this->request->post('idd', 0, 'intval');   //开始数字
        try {
            $houseVillageOrderTicketNumberService = new HouseVillageOrderTicketNumberService();
            $returnArr = $houseVillageOrderTicketNumberService->delTicketNumber($idd, $property_id);
            if ($returnArr['err_code'] > 0) {
                return api_output_error(1001, $returnArr['msg']);
            }
            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function uploadFiles()
    {
        $file = $this->request->file('file');
        if(!$file){
            return api_output_error(1001,'请上传文件');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 20,
                'fileExt' => 'xls,xlsx',
            ]])->check(['file' => $file]);

            $fileName = $file->getOriginalName();

            $file_arr = explode(('.'.$file->extension()),$fileName);
            if(count($file_arr)==2)
            {
                $file_type=$file->extension();
            }else{
                return api_output_error(1001,'请上传有效文件');
            }
            $upload_path=$this->request->get('upload_dir','','trim');
            if(empty($upload_path)){
                $upload_path='tmp_upload_file';
            }
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( $upload_path,$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $data['name'] = $fileName;
            $data['file_type'] = $file_type;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    public function handleImportExcel(){
        $xfile = $this->request->post('xfile');
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        try {
            $property_id = $this->adminUser['property_id'];
            $houseVillageOrderTicketNumberService = new HouseVillageOrderTicketNumberService();
            $resExcel = $houseVillageOrderTicketNumberService->handleImportExcel($xfile,$property_id);
            return api_output(0, $resExcel, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /****
    **清楚被占用的状态
     */
    public function  clearTicketNumberStatus(){
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        $idd = $this->request->post('idd', 0, 'intval');   //开始数字
        try {
            $houseVillageOrderTicketNumberService = new HouseVillageOrderTicketNumberService();
            $returnArr = $houseVillageOrderTicketNumberService->clearTicketNumberStatus($idd, $property_id);
            if ($returnArr['err_code'] > 0) {
                return api_output_error(1001, $returnArr['msg']);
            }
            return api_output(0, $returnArr);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}