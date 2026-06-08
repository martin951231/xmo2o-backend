<?php
/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/12/04
 * Time: 20.59
 */
//物业后台
class BillVillageAction extends BaseAction{



    protected $village_id;
    protected $village;

    public function _initialize(){
        parent::_initialize();

        $this->village_id = $this->house_session['village_id'];
        $this->village = D('House_village')->where(array('village_id'=>$this->village_id))->find();
        if(empty($this->village)){
            $this->error('该'.C('config.house_name').'不存在！');
        }
        if($this->village['status'] == 0){
            $this->assign('jumpUrl',U('Index/config'));
            $this->error('您需要先完善信息才能继续操作');
        }
    }
    public function index_list()
    {
        if (!in_array(404, $this->house_session['menus']) && !in_array(458, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $where['vb.village_id'] = $this->village_id;
        $add_time_start = $_GET['add_time_start'];
        $add_time_end = $_GET['add_time_end'];
        $this->assign('add_time_start', $add_time_start);
        $this->assign('add_time_end', $add_time_end);

        if($add_time_start && $add_time_end){

            $start_time = strtotime($add_time_start);
            $end_time = strtotime($add_time_end.'23:59:59');

            $where['vb.payment_time'] = array('between',array($start_time,$end_time));
        }else if($add_time_start){
            $add_time_start = strtotime($add_time_start);
            $where['vb.payment_time'] = array('egt',$add_time_start);
        }else if($add_time_end){
            $add_time_end = strtotime($add_time_end.'23:59:59');
            $where['vb.payment_time'] = array('lt',$add_time_end);
        }
        if(!empty($_GET['title'])){
            $title = $_GET['title'];
            $this->assign('title', $title);
            if($title){
                $where['vb.title'] = $title;
            }
        }


        $bill_type = $_GET['bill_type'];


        $this->assign('bill_type', $bill_type);
        if($bill_type == 2){
            $where['vb.bill_type'] = 2;
        }elseif($bill_type == 1){
            $where['vb.bill_type'] = 1;

        }

        $count_user = M('vb.Village_bill')
            ->join('pigcms_budget_category bc ON bc.id = vb.category_id')
            ->where($where)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Village_bill vb')
            ->join('pigcms_budget_category bc ON bc.id = vb.category_id')
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
//            ->buildSql();
            ->select();
        $this->assign('pagebar',$p->show());
        $bill_type0 = M('Village_bill vb')
            ->join('pigcms_budget_category bc ON bc.id = vb.category_id')
            ->where($where)
            ->where(array('vb.bill_type'=>2))
            ->sum('vb.price');
        $bill_type1 = M('Village_bill vb')
            ->join('pigcms_budget_category bc ON bc.id = vb.category_id')
            ->where($where)
            ->where(array('vb.bill_type'=>1))
            ->sum('vb.price');
        $bill_type = M('Village_bill vb')
            ->join('pigcms_budget_category bc ON bc.id = vb.category_id')
            ->where($where)
            ->sum('vb.price');
        $this->assign('dataList',$dataList);
        $this->assign('bill_type0',$bill_type0);
        $this->assign('bill_type1',$bill_type1);
        $this->assign('bill_type',$bill_type);
        $this->assign('dataList',$dataList);
        $this->display();
    }

    public function bill_add()
    {
        if (!in_array(404, $this->house_session['menus']) && !in_array(458, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_POST){

            if(empty($_POST['title'])){
                $this->error('费用名称不能为空！');
            }

            if(empty($_POST['price'])){
                $this->error('金额不能为空！');
            }
            if(empty($_POST['payment_time'])){
                $this->error('付款时间不能为空！');
            }
            $data['title'] = $_POST['title'];
            $data['bill_type'] = $_POST['bill_type'];
            $data['unit_price'] = $_POST['unit_price'];
            $data['num'] = $_POST['num'];
            $data['price'] = $_POST['price'];
            $data['payee_name'] = $_POST['payee_name'];
            $data['payer_name'] = $_POST['payer_name'];
            $data['desc'] = $_POST['desc'];
            $data['operation_name'] = $this->house_session['account'];
            $data['village_id'] = $this->village_id;
            $data['payment_time'] = strtotime($_POST['payment_time']);
            $data['category_id'] = $_POST['budgetCategoryId'];
            if($_POST['pic']){
                $data['image'] = implode(';',$_POST['pic']);
            }
            $id = M('Village_bill')->data($data)->add();
            if($id){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！');
            }
        }else{
            $cate_where['status'] = 1;
            $categoryList = M('budget_category')->where($cate_where)->select();
            $this->assign('categoryList', $categoryList );
            $this->display();
        }

    }



    public function bill_edit()
    {

        if (!in_array(404, $this->house_session['menus']) && !in_array(458, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if(IS_POST){
            if(empty($_POST['title'])){
                $this->error('费用名称不能为空！');
            }

            if(empty($_POST['price'])){
                $this->error('金额不能为空！');
            }
            if(empty($_POST['payment_time'])){
                $this->error('付款时间不能为空！');
            }
            $data['title'] = $_POST['title'];
            $data['bill_type'] = $_POST['bill_type'];
            $data['unit_price'] = $_POST['unit_price'];
            $data['num'] = $_POST['num'];
            $data['price'] = $_POST['price'];
            $data['payee_name'] = $_POST['payee_name'];
            $data['payer_name'] = $_POST['payer_name'];
            $data['desc'] = $_POST['desc'];
            $data['operation_name'] = $this->house_session['account'];
            $data['payment_time'] = strtotime($_POST['payment_time']);
            if($_POST['pic']){
                $data['image'] = implode(';',$_POST['pic']);
            }
            $id = M('Village_bill')->where(array('id'=>$_POST['id']))->save($data);
            if($id){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！');
            }
        }else{

            $bill = M('Village_bill')->where(array('id'=>$_GET['id']))->find();
            if($bill['image']){
                $bill['image'] = explode(';',$bill['image']);
            }
            $cate_where['status'] = 1;
            $categoryList = M('budget_category')->where($cate_where)->select();
            $this->assign('categoryList', $categoryList );
            $this->assign('bill',$bill);
           $this->display();
        }

    }

    public function bill_del()
    {
        if (!in_array(404, $this->house_session['menus']) && !in_array(458, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if($_GET['id']){
            $result = M('Village_bill')->where(array('id'=>$_GET['id']))->delete();
            if($result){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }else{
            $this->error('数据不存在');
        }
    }

    public function bill_info()
    {
        $bill = M('Village_bill')->where(array('id'=>$_GET['id']))->find();
        if($bill['image']){
            $bill['image'] = explode(';',$bill['image']);
        }

        $this->assign('bill',$bill);
        $this->display();
    }

    public function bill_import_template()
    {
        if (!in_array(404, $this->house_session['menus']) && !in_array(458, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $objExcel = new PHPExcel();
        $objExcel->getProperties()->setTitle('bill_import_template');
        $objActSheet = $objExcel->getActiveSheet();
        $objActSheet->setTitle('import');

        $headers = array(
            'A' => '费用名称(必填)',
            'B' => '类型(支出/收入或1/2)',
            'C' => '所属分类名称(必填)',
            'D' => '单价',
            'E' => '数量',
            'F' => '金额(必填)',
            'G' => '收款方',
            'H' => '付款方',
            'I' => '付款时间(必填)',
            'J' => '备注',
        );

        foreach ($headers as $column => $title) {
            $objActSheet->setCellValue($column . '1', $title);
            $objActSheet->getColumnDimension($column)->setWidth(22);
        }
        $objActSheet->setCellValue('A2', '物业维修');
        $objActSheet->setCellValue('B2', '支出');
        $objActSheet->setCellValue('C2', '维修费');
        $objActSheet->setCellValue('D2', '100');
        $objActSheet->setCellValue('E2', '1');
        $objActSheet->setCellValue('F2', '100');
        $objActSheet->setCellValue('G2', '收款方名称');
        $objActSheet->setCellValue('H2', '付款方名称');
        $objActSheet->setCellValue('I2', date('Y-m-d H:i:s'));
        $objActSheet->setCellValue('J2', '备注');

        $objActSheet->getStyle('A1:J1')->getFont()->setBold(true);
        $objActSheet->getStyle('A:J')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="bill_import_template.xls"');
        header('Content-Transfer-Encoding: binary');
        $objWriter->save('php://output');
        exit();
    }

    public function bill_import()
    {
        if (!in_array(404, $this->house_session['menus']) && !in_array(458, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if ($_FILES['import_file']['error'] == 4) {
            $this->error('请选择导入文件');
        }

        $upload_dir = './upload/excel/village_bill/' . date('Ymd') . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        import('ORG.Net.UploadFile');
        $upload = new UploadFile();
        $upload->maxSize = 10 * 1024 * 1024;
        $upload->allowExts = array('xls', 'xlsx');
        $upload->savePath = $upload_dir;
        $upload->saveRule = 'uniqid';
        if (!$upload->upload()) {
            $this->error($upload->getErrorMsg());
        }

        $uploadList = $upload->getUploadFileInfo();
        $path = $uploadList[0]['savepath'] . $uploadList[0]['savename'];

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
        $fileType = PHPExcel_IOFactory::identify($path);
        $objReader = PHPExcel_IOFactory::createReader($fileType);
        $excelObj = $objReader->load($path);
        $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
        unlink($path);

        $categoryList = M('budget_category')->where(array('status' => 1))->select();
        $categoryMap = array();
        foreach ($categoryList as $category) {
            $categoryMap[trim($category['category_name'])] = $category['id'];
        }

        $insertData = array();
        $errors = array();
        foreach ($result as $rowNum => $row) {
            if ($rowNum == 1) {
                continue;
            }
            $title = trim($row['A']);
            $billTypeText = trim($row['B']);
            $categoryName = trim($row['C']);
            $unitPrice = trim($row['D']);
            $num = trim($row['E']);
            $price = trim($row['F']);
            $payeeName = trim($row['G']);
            $payerName = trim($row['H']);
            $paymentTimeText = trim($row['I']);
            $desc = trim($row['J']);

            if ($title === '' && $billTypeText === '' && $categoryName === '' && $unitPrice === '' && $num === '' && $price === '' && $payeeName === '' && $payerName === '' && $paymentTimeText === '' && $desc === '') {
                continue;
            }

            if ($title === '') {
                $errors[] = '第' . $rowNum . '行：费用名称不能为空';
            }
            if ($price === '') {
                $errors[] = '第' . $rowNum . '行：金额不能为空';
            } elseif (!is_numeric($price)) {
                $errors[] = '第' . $rowNum . '行：金额必须为数字';
            }
            if ($unitPrice !== '' && !is_numeric($unitPrice)) {
                $errors[] = '第' . $rowNum . '行：单价必须为数字';
            }
            if ($num !== '' && !is_numeric($num)) {
                $errors[] = '第' . $rowNum . '行：数量必须为数字';
            }
            if ($categoryName === '' || !isset($categoryMap[$categoryName])) {
                $errors[] = '第' . $rowNum . '行：所属分类名称不存在或未启用';
            }

            if ($billTypeText === '' || $billTypeText === '支出' || $billTypeText === '1') {
                $billType = 1;
            } elseif ($billTypeText === '收入' || $billTypeText === '2') {
                $billType = 2;
            } else {
                $billType = 0;
                $errors[] = '第' . $rowNum . '行：类型只能填写支出、收入、1或2';
            }

            $paymentTime = $this->parseBillImportTime($paymentTimeText);
            if (!$paymentTime) {
                $errors[] = '第' . $rowNum . '行：付款时间不能为空或格式错误';
            }

            $insertData[] = array(
                'title' => $title,
                'bill_type' => $billType,
                'category_id' => isset($categoryMap[$categoryName]) ? $categoryMap[$categoryName] : 0,
                'unit_price' => $unitPrice,
                'num' => $num,
                'price' => $price,
                'payee_name' => $payeeName,
                'payer_name' => $payerName,
                'desc' => $desc,
                'operation_name' => $this->house_session['account'],
                'village_id' => $this->village_id,
                'payment_time' => $paymentTime,
            );
        }

        if (empty($insertData)) {
            $this->error('导入文件没有可导入的数据');
        }
        if (!empty($errors)) {
            $this->error(implode('<br/>', array_slice($errors, 0, 20)));
        }

        $billModel = M('Village_bill');
        $successNum = 0;
        foreach ($insertData as $data) {
            $id = $billModel->data($data)->add();
            if ($id) {
                $successNum++;
            }
        }

        if ($successNum > 0) {
            $this->success('导入成功，共导入' . $successNum . '条记录', U('BillVillage/index_list'));
        } else {
            $this->error('导入失败');
        }
    }

    private function parseBillImportTime($value)
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        if (is_numeric($value) && $value > 25569) {
            return intval(($value - 25569) * 86400);
        }
        return strtotime($value);
    }


    public function ajax_upload_pic(){
        if ($_FILES['imgFile']['error'] != 4) {
//            $img_mer_id = sprintf("%09d", $this->house_session['village_id']);
//            $rand_num = mt_rand(10, 99) . '/' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
            $rand_num = mt_rand(10, 99);

            $upload_dir = './upload/activity/' . $rand_num . '/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = $this->config['group_pic_size'] * 1024 * 1024;
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif');
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();
                $title = $rand_num . ',' . $uploadList[0]['savename'];
                $slide_image_class = new slide_image();
                $url = $slide_image_class->get_image_by_path($title , 5);
                $title = $rand_num . '/' . $uploadList[0]['savename'];
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => '没有选择图片')));
        }
    }

    public function ajax_del_pic(){
        $group_image_class = new slide_image();
        $group_image_class->del_image_by_path($_POST['path'],1);
    }
}
