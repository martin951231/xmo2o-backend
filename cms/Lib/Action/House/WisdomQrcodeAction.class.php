<?php

use app\community\model\service\RepairCateService;

/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/12/18
 * Time: 10:16
 */
class WisdomQrcodeAction extends BaseAction
{
    protected $village_id;
    protected $village;

    public function _initialize()
    {
        parent::_initialize();

        $this->village_id = $this->house_session['village_id'];
        $this->village = M('House_village')->where(array('village_id' => $this->village_id))->find();
        if (empty($this->village)) {
            $this->error('该' . C('config.house_name') . '不存在！');
        }
    }

    public function qrcode_download()
    {
        $name = $_GET['import_name'];
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
//        $xtitle = '导入巡检分类模板';
        $xtitle = $name;

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($xtitle);
        $objProps->setTitle($xtitle);
        $objProps->setSubject($xtitle);
        $objProps->setDescription($xtitle);

        $length = 1;

        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex($length);

        $objExcel->getActiveSheet()->setTitle($name);
        $objActSheet = $objExcel->getActiveSheet();
        if( $name == '导入巡检分类模板' ){
            $cell_list = range('A', 'F');
            foreach ($cell_list as $cell) {
                $objActSheet->getColumnDimension($cell)->setWidth(30);
            }
            $objActSheet->setCellValue('A1', '分类名(必填)');
            $objActSheet->setCellValue('B1', '记录字段名(必填)');
            $objActSheet->setCellValue('C1', '记录字段内容名称(必填)');
            $objActSheet->setCellValue('D1', '记录字段内容类型(文本或者单选)(必填)');
            $objActSheet->setCellValue('E1', '记录字段内容(如果内容类型为单选则必须填写该字段,多个条件用逗号分隔)');
            $objActSheet->setCellValue('F1', '记录字段内容是否必填(填是或者否)');
        }elseif( $name == '导入二维码模板') {
            $cell_list = range('A', 'D');
            foreach ($cell_list as $cell) {
                $objActSheet->getColumnDimension($cell)->setWidth(30);
            }
            $objActSheet->setCellValue('A1', '二维码名称(必填)');
            $objActSheet->setCellValue('B1', '位置(经度)(必填)');
            $objActSheet->setCellValue('C1', '位置(纬度)(必填)');
            $objActSheet->setCellValue('D1', '所属分类名(必填)');
        }

        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $xtitle . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();

    }

    /*
     * 导出分类
     * */
    public function export_qrcode_category()
    {
        $final_list = [];
        $category_where = [
            'village_id' => $this->village_id,
            'status' => 1
        ];
        $category_list = M('wisdom_qrcode_category')->where($category_where)->select();
        if( empty( $category_list ) ){
            $this->error('没有可导出的分类！');
        }
        foreach( $category_list as $category ){
            $field_where = [
                'category_id' => $category['id'],
            ];
            $field_list = M('wisdom_qrcode_record_cate')->where($field_where)->select();
            if( empty( $field_list ) ){
                $final_list[] = [
                    'category_name' => $category['name'],
                    'category_addtime' => $category['addtime'],
                    'record_name' => '',
                    'record_add_time' => '',
                    'field_config_name' => '',
                    'field_config_type' => '',
                    'field_config_add_time' => '',
                    'field_config_use_field' => '',
                    'field_config_use_is_must' => '',
                ];
                continue;
            }
            foreach( $field_list as $field ){
                $field_config_where = [
                    'record_cate_id' => $field['id'],
                ];
                $field_config_list = M('wisdom_qrcode_record_cate_field_config')->where($field_config_where)->select();
                if( empty( $field_config_list ) ){
                    $final_list[] = [
                        'category_name' => $category['name'],
                        'category_addtime' => $category['addtime'],
                        'record_name' => $field['record_name'],
                        'record_add_time' => $field['add_time'],
                        'field_config_name' => '',
                        'field_config_type' => '',
                        'field_config_add_time' => '',
                        'field_config_use_field' => '',
                        'field_config_use_is_must' => '',
                    ];
                    continue;
                }
                foreach( $field_config_list as $field_config ){
                    $type = '';
                    switch( $field_config['type'] ){
                        case 1:$type = '文本';break;
                        case 2:$type = '图片';break;
                        case 3:$type = '单选';break;
                        default :$type = '文本';break;
                    }
                    $final_list[] = [
                        'category_name' => $category['name'],
                        'category_addtime' => $category['addtime'],
                        'record_name' => $field['record_name'],
                        'record_add_time' => $field['add_time'],
                        'field_config_name' => $field_config['title'],
                        'field_config_type' => $type,
                        'field_config_add_time' => $field_config['add_time'],
                        'field_config_use_field' => $field_config['use_field'],
                        'field_config_use_is_must' => $field_config['is_must'] == 1?'是':'否',
                    ];
                }
            }
        }
        if( empty( $final_list ) ){
            $this->error('没有可导出的分类！');
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        error_reporting(E_ALL);
        date_default_timezone_set('Europe/London');
        $objExcel = new PHPExcel();

        $title = '分类列表';
        // $title = $this->village['village_name'] . '社区-导入失败列表';
        /*以下是一些设置 ，什么作者  标题之类的*/
        $objExcel->getProperties()->setCreator($title)
            ->setLastModifiedBy($title)
            ->setTitle($title)
            ->setSubject("分类导出")
            ->setDescription("备份数据")
            ->setKeywords("excel")
            ->setCategory("result file");

        $i = 0;
        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex($i);
        $objExcel->getActiveSheet()->setTitle($title);
        $objActSheet = $objExcel->getActiveSheet();
        $objActSheet->setCellValue('A1', '分类名');
        $objActSheet->setCellValue('B1', '分类添加时间');
        $objActSheet->setCellValue('C1', '记录字段名');
        $objActSheet->setCellValue('D1', '记录添加时间');
        $objActSheet->setCellValue('E1', '记录字段内容名称');
        $objActSheet->setCellValue('F1', '记录字段内容添加时间');
        $objActSheet->setCellValue('G1', '记录字段内容类型');
        $objActSheet->setCellValue('H1', '记录字段内容');
        $objActSheet->setCellValue('I1', '记录字段内容是否必填');

        $columns = range('A', 'I'); // 生成A到F的列标数组，如需更多列可改为'G'、'H'等
        foreach ($columns as $col) {
            $objActSheet->getColumnDimension($col)->setWidth(30);
        }

        $index = 2;
        foreach ($final_list as $value) {
            $objActSheet->setCellValueExplicit('A' . $index, $value['category_name']);
            $objActSheet->setCellValueExplicit('B' . $index, $value['category_addtime']);
            $objActSheet->setCellValueExplicit('C' . $index, $value['record_name']);
            $objActSheet->setCellValueExplicit('D' . $index, date( 'Y-m-d H:i:s', $value['record_add_time']));
            $objActSheet->setCellValueExplicit('E' . $index, $value['field_config_name']);
            $objActSheet->setCellValueExplicit('F' . $index, date( 'Y-m-d H:i:s', $value['field_config_add_time']));
            $objActSheet->setCellValueExplicit('G' . $index, $value['field_config_type']);
            $objActSheet->setCellValueExplicit('H' . $index, $value['field_config_use_field']);
            $objActSheet->setCellValueExplicit('I' . $index, $value['field_config_use_is_must']);
            $index++;
        }

        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    /*
     * 导出二维码
     * */
    /**
     * 生成多个带底部名称的二维码，按一页6张排版嵌入Word并自动下载
     * 依赖：PHP GD库（需开启，默认PHP环境已开启）
     * @param array $qrConList 二维码内容数组（每个元素是二维码跳转地址）
     * @param string $wordName 导出Word文件名（无需.doc后缀）
     * @param int $perPageCount 每页显示二维码数量（默认6张）
     * @return void
     */
    function generateMultiQrCodeToWord($qrConList,$wordName = "批量二维码导出文档",$perPageCount = 6) {
        // ================= 基础配置 =================
        $qrModuleSize = 6;
        $qrPureSize   = 200;
        $categoryFontSize = 16;   // ⬅ 分类名称字体大小
        $nameFontSize = 10;        // ⬅ 下方名称字体大小


        $fontPath     = APP_PATH . 'Lib/ORG/fonts/Simhei.ttf';
        $qrWordWidthMm = 90;

        // 根据字体大小动态算区域高度（防裁剪）
        $categoryHeight = $categoryFontSize + 12;
        $nameHeight     = $nameFontSize + 10;

        require_once APP_PATH . 'Lib/ORG/phpqrcode/phpqrcode.php';

        if (!file_exists($fontPath)) {
            die("错误：字体文件不存在");
        }

        // ================= 生成二维码图片 =================
        $qrImageBase64List = [];

        foreach ($qrConList as $i => $qrCon) {

            parse_str(parse_url($qrCon, PHP_URL_QUERY), $params);

            $qrName = mb_substr($params['name'] ?? ('二维码-' . ($i + 1)), 0, 10, 'UTF-8');
            $categoryName = mb_substr($params['category_name'] ?? '', 0, 16, 'UTF-8');

            // === 原始二维码 ===
            ob_start();
            QRcode::png($qrCon, false, QR_ECLEVEL_M, $qrModuleSize, 1);
            $qrPngData = ob_get_clean();

            $qrRawImg = imagecreatefromstring($qrPngData);
            if (!$qrRawImg) continue;

            // === 等比缩放 ===
            $qrScaled = imagecreatetruecolor($qrPureSize, $qrPureSize);
            $white = imagecolorallocate($qrScaled, 255, 255, 255);
            imagefill($qrScaled, 0, 0, $white);

            imagecopyresampled(
                $qrScaled,
                $qrRawImg,
                0, 0, 0, 0,
                $qrPureSize, $qrPureSize,
                imagesx($qrRawImg),
                imagesy($qrRawImg)
            );
            imagedestroy($qrRawImg);

            // === 画布（分类名 + 二维码 + 底部名称）===
            $canvasHeight = $categoryHeight + $qrPureSize + $nameHeight;
            $canvas = imagecreatetruecolor($qrPureSize, $canvasHeight);
            imagefill($canvas, 0, 0, $white);

            $black = imagecolorallocate($canvas, 0, 0, 0);

            // ===== 分类名称（上）=====
            if ($categoryName !== '') {
                $bbox = imagettfbbox($categoryFontSize, 0, $fontPath, $categoryName);
                $textWidth = $bbox[2] - $bbox[0];
                $textX = ($qrPureSize - $textWidth) / 2;
                $textY = ($categoryHeight + $categoryFontSize) / 2;

                imagettftext(
                    $canvas,
                    $categoryFontSize,
                    0,
                    (int)$textX,
                    (int)$textY,
                    $black,
                    $fontPath,
                    $categoryName
                );
            }

            // ===== 二维码 =====
            imagecopy(
                $canvas,
                $qrScaled,
                0,
                $categoryHeight,
                0,
                0,
                $qrPureSize,
                $qrPureSize
            );
            imagedestroy($qrScaled);

            // ===== 底部名称 =====
            $bbox = imagettfbbox($nameFontSize, 0, $fontPath, $qrName);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];

            $textX = ($qrPureSize - $textWidth) / 2;
            $textY = $categoryHeight + $qrPureSize + ($nameHeight + $textHeight) / 2;

            imagettftext(
                $canvas,
                $nameFontSize,
                0,
                (int)$textX,
                (int)$textY,
                $black,
                $fontPath,
                $qrName
            );

            // === 转 base64 ===
            ob_start();
            imagepng($canvas, null, 9);
            $imgData = ob_get_clean();
            imagedestroy($canvas);

            $qrImageBase64List[] = [
                'base64' => 'data:image/png;base64,' . base64_encode($imgData)
            ];
        }

        // ================= Word HTML =================
        $totalPages = ceil(count($qrImageBase64List) / $perPageCount);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { width:210mm; font-family:SimSun; }
table { width:100%; border-collapse:collapse; }
td { width:50%; text-align:center; padding:6mm 0; }
img { width:{$qrWordWidthMm}mm; }
.page-break { page-break-after:always; }
</style>
</head>
<body>
HTML;

        $idx = 0;

        for ($p = 1; $p <= $totalPages; $p++) {

            $html .= "<table>";

            for ($r = 0; $r < 3; $r++) {
                $html .= "<tr>";
                for ($c = 0; $c < 2; $c++) {
                    if (isset($qrImageBase64List[$idx])) {
                        $img = $qrImageBase64List[$idx]['base64'];
                        $html .= "<td><img src='{$img}'></td>";
                    } else {
                        $html .= "<td></td>";
                    }
                    $idx++;
                }
                $html .= "</tr>";
            }

            $html .= "</table>";

            if ($p < $totalPages) {
                $html .= "<div class='page-break'></div>";
            }
        }

        $html .= "</body></html>";

        // ================= 输出 Word =================
        header("Content-Type: application/msword; charset=utf-8");
        header(
            "Content-Disposition: attachment; filename=\"" .
            iconv('UTF-8', 'GB2312//IGNORE', $wordName) . ".doc\""
        );
        echo $html;
        exit;
    }


    public function export_qrcode_detail()
    {
        $qrcode_where = [
            'q.village_id' => $this->village_id
        ];

        if( isset($_GET['categoryId']) && !empty($_GET['categoryId']) && $_GET['categoryId'] != 0 ){
            $qrcode_where['q.category_id'] = $_GET['categoryId'];
        }

        $data_list =  M('wisdom_qrcode as q')
            ->join('pigcms_wisdom_qrcode_category qc on q.category_id = qc.id')
            ->field('q.id,q.name,qc.name as category_name')
            ->where($qrcode_where)
            ->select();

        if( empty($data_list) ){
            $this->error('暂无二维码可导出!');
        }
        $qrConList = [];
        $base_url = $this->config['site_url'] . '/packapp/village/pages/village/smartQRcode/elevatorManagement?';
        foreach( $data_list as $key => $value ){
            $str = $base_url.'qrcode_id='.$value['id'].'&spread=1&village_id='.$this->village_id.'&name='.$value['name'].'&wis_code=1&map_type=0&category_name='.$value['category_name'];
            $qrConList[] = $str;
        }

        // 2. 调用方法，批量生成并下载Word（一页6张）
        $this->generateMultiQrCodeToWord($qrConList, "批量导出二维码", 6);
    }
    /**
     * 解析 URL 中的查询参数
     * @param string $url 完整的 URL 地址
     * @return array 解析后的参数关联数组（自动处理中文解码）
     */
    public function parseUrlParams($url) {
        // 1. 分离 URL 中的查询字符串部分（? 后面的内容）
        $queryString = parse_url($url, PHP_URL_QUERY);

        if (empty($queryString)) {
            return []; // 无查询参数时返回空数组
        }

        // 2. 解析查询字符串为数组（自动处理 & 分隔）
        parse_str($queryString, $params);

        // 3. 对参数值进行 URL 解码（处理中文或特殊字符）
        foreach ($params as $key => $value) {
            $params[$key] = urldecode($value);
        }

        return $params;
    }
    public function export_qrcode_detail2()
    {
        $final_list = [];
        $qrcode_detail_where = [
            'village_id' => $this->village_id
        ];
        $qrcode_detail_list = M('wisdom_qrcode')->where($qrcode_detail_where)->select();
        if( empty( $qrcode_detail_list ) ){
            $this->error('没有可导出的二维码！');
        }
        foreach( $qrcode_detail_list as $qrcode_detail ){
            $category_where = [
                'id' => $qrcode_detail['category_id']
            ];
            $category_info = M('wisdom_qrcode_category')->where($category_where)->find();
            $final_list[] = [
                'num_id' => $qrcode_detail['num_id'],
                'name' => $qrcode_detail['name'],
                'category_name' => $category_info['name'],
                'image_path' => $qrcode_detail['image_path'] != ''? $this->config['site_url'].$qrcode_detail['image_path']:'',
                'add_time' => date( 'Y-m-d H:i:s', $qrcode_detail['add_time'] ),
                'long' => $qrcode_detail['long'],
                'lat' => $qrcode_detail['lat'],
            ];
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        error_reporting(E_ALL);
        date_default_timezone_set('Europe/London');
        $objExcel = new PHPExcel();

        $title = '分类列表';
        // $title = $this->village['village_name'] . '社区-导入失败列表';
        /*以下是一些设置 ，什么作者  标题之类的*/
        $objExcel->getProperties()->setCreator($title)
            ->setLastModifiedBy($title)
            ->setTitle($title)
            ->setSubject("分类导出")
            ->setDescription("备份数据")
            ->setKeywords("excel")
            ->setCategory("result file");

        $i = 0;
        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex($i);
        $objExcel->getActiveSheet()->setTitle($title);
        $objActSheet = $objExcel->getActiveSheet();
        $objActSheet->setCellValue('A1', '序号');
        $objActSheet->setCellValue('B1', '二维码名称');
        $objActSheet->setCellValue('C1', '分类名称');
        $objActSheet->setCellValue('D1', '标题图');
        $objActSheet->setCellValue('E1', '二维码添加时间');
        $objActSheet->setCellValue('F1', '经度');
        $objActSheet->setCellValue('G1', '纬度');

        $columns = range('A', 'I'); // 生成A到F的列标数组，如需更多列可改为'G'、'H'等
        foreach ($columns as $col) {
            $objActSheet->getColumnDimension($col)->setWidth(30);
        }

        $index = 2;
        foreach ($final_list as $value) {
            $objActSheet->setCellValueExplicit('A' . $index, $value['num_id']);
            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
            $objActSheet->setCellValueExplicit('C' . $index, $value['category_name']);
            $objActSheet->setCellValueExplicit('D' . $index, $value['image_path']);
            $objActSheet->setCellValueExplicit('E' . $index, $value['add_time']);
            $objActSheet->setCellValueExplicit('F' . $index, $value['long']);
            $objActSheet->setCellValueExplicit('G' . $index, $value['lat']);
            $index++;
        }

        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function qrcode_import()
    {
        $import_name = $_GET['import_name'];
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);
                ini_set('memory_limit', '2048M');

                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/inspection');

                $upload_dir = './upload/excel/inspection/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 10 * 1024 * 1024;
                $upload->allowExts = array('xls', 'xlsx');
                $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
                $upload->savePath = $upload_dir;
                $upload->thumb = false;
                $upload->thumbType = 0;
                $upload->imageClassPath = '';
                $upload->thumbPrefix = '';
                $upload->saveRule = 'uniqid';

                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);
                    //验证是否为空数据
                    if( $this->isOnlyTitle($result, 1) ){
                        $this->error('导入数据为空,请重新上传文件！');
                    }
                    if( $import_name == 'import_category' ){//导入分类
                        $this->import_category($result);
                    }elseif( $import_name == 'import_qrcode_detail' ){//导入二维码
                        $this->import_qrcode_detail($result);
                    }else{

                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }else{
                $this->error('文件上传失败');
                exit;
            }
        } else {
            if( $import_name == 'import_category' ){
                $this->display('qrcode_category_import');
            }elseif( $import_name == 'import_qrcode_detail' ){
                $this->display('qrcode_detail_import');
            }else{
                $this->display('qrcode_index');
            }
        }
    }

    /**
     * 判断Excel数据是否只有标题（无有效数据）
     * 适配：行号为键、列名[A-F]为子键的关联数组格式
     * @param array $excelArray Excel读取后的二维关联数组
     * @param int $titleRowNum 标题行号（默认第1行）
     * @return bool true=只有标题，false=存在有效数据
     */
    function isOnlyTitle($excelArray, $titleRowNum = 1) {
        // 1. 安全判断：数组为空或无标题行，直接判定为无有效数据
        if (!is_array($excelArray) || empty($excelArray)) {
            return true;
        }

        // 2. 分离标题行和数据行
        $dataRows = [];
        foreach ($excelArray as $rowNum => $row) {
            // 行号大于标题行号的，视为数据行
            if ($rowNum > $titleRowNum) {
                $dataRows[] = $row;
            }
        }

        // 3. 数据行为空，判定为只有标题
        if (empty($dataRows)) {
            return true;
        }

        // 4. 遍历数据行，判断是否存在有效内容
        foreach ($dataRows as $row) {
            // 安全判断：当前行不是数组，跳过
            if (!is_array($row)) {
                continue;
            }

            $hasValidData = false;
            // 遍历当前行的每个单元格（A-F列）
            foreach ($row as $cellValue) {
                // 处理单元格内容：去除空白字符，兼容NULL/数字/字符串
                $trimmedValue = is_scalar($cellValue) ? trim((string)$cellValue) : '';
                // 有效数据判定：非空字符串、非0数字（避免数字0被误判为空）
                if (($trimmedValue !== '' && $trimmedValue !== null) || (is_numeric($cellValue) && $cellValue == 0)) {
                    $hasValidData = true;
                    break; // 找到有效数据，跳出单元格循环
                }
            }

            // 存在有效数据行，直接返回false
            if ($hasValidData) {
                return false;
            }
        }

        // 5. 所有数据行均无有效数据，返回true
        return true;
    }

    //导入分类
    public function import_category($result)
    {
        if (!empty($result) && is_array($result)) {
            unset($result[1]);
            $last_category_id = 0;
            $err_msg = '';
            $error_arr = [];
            $tmpdata = [];
            foreach ($result as $kk => $vv) {

                if (empty($vv['A'])) {
                    $vv['G'] = '请填写分类名！';
                    $error_arr[] = $vv;
                    continue;
                }
                if (empty($vv['B'])) {
                    $vv['G'] = '请填写记录字段名！';
                    $error_arr[] = $vv;
                    continue;
                }

                if (empty($vv['C'])) {
                    $vv['G'] = '请填写记录字段内容名称！';
                    $error_arr[] = $vv;
                    continue;
                }

                if (empty($vv['D'])) {
                    $vv['G'] = '记录字段内容类型！';
                    $error_arr[] = $vv;
                    continue;
                }

                if ($vv['D'] == '单选' && empty($vv['E'])) {
                    $vv['G'] = '请填写记录字段内容！';
                    $error_arr[] = $vv;
                    continue;
                }

                $tmpdata[] = [
                    'village_id' => $this->village_id,
                    'category_name' => htmlspecialchars(trim($vv['A']), ENT_QUOTES),//分类名
                    'record_name' => htmlspecialchars(trim($vv['B']), ENT_QUOTES),//记录字段名
                    'record_field_name' => htmlspecialchars(trim($vv['C']), ENT_QUOTES),//记录内容名
                    'record_field_type' => htmlspecialchars(trim($vv['D']), ENT_QUOTES),//记录内容类型
                    'record_field_content' => htmlspecialchars(trim($vv['E']), ENT_QUOTES),//记录内容内容
                    'is_must' => htmlspecialchars(trim($vv['F']), ENT_QUOTES),//记录内容是否必填
                ];
            }
            $data = $this->formatArrayByDoubleGroupForCategory($tmpdata);
            foreach ($data as $key => $value) {
                $insert_category_data = [
                    'village_id' => $this->village_id,
                    'name' => $value['category_name'],
                    'addtime' => date('Y-m-d H:i:s', time()),
                    'status' => 1
                ];

                $category_where = [
                    'village_id' => $this->village_id,
                    'name' => $value['category_name'],
                    'status' => 1
                ];
                $category_exists = M('wisdom_qrcode_category')->where($category_where)->find();

                if( !empty( $category_exists ) ){// 修改
                    $last_category_info = M('wisdom_qrcode_category')->where(['id'=>$category_exists['id']])->data($insert_category_data)->save();
                    $last_category_id = $category_exists['id'];
                }else{// 保存
                    $last_category_id = M('wisdom_qrcode_category')->data($insert_category_data)->add();
                }
                if ($last_category_id) {
                    foreach ($value['data'] as $key_field => $val_field) {
                        if (empty($val_field)) {
                            continue;
                        }
                        $insert_field_data = [
                            'record_name' => $val_field['record_name'],
                            'add_time' => time(),
                            'status' => 1,
                            'category_id' => $last_category_id
                        ];

                        $category_field_where = [
                            'record_name' => $val_field['record_name'],
                            'category_id' => $last_category_id,
                            'status' => 1
                        ];
                        $category_field_exists = M('wisdom_qrcode_record_cate')->where($category_field_where)->find();

                        if( !empty( $category_field_exists ) ){// 修改
                            $last_category_field_info = M('wisdom_qrcode_record_cate')->where(['id'=>$category_field_exists['id']])->data($insert_field_data)->save();
                            $last_field_id = $category_field_exists['id'];
                        }else{// 保存
                            $last_field_id = M('wisdom_qrcode_record_cate')->data($insert_field_data)->add();
                        }

                        if ($last_field_id) {
                            $insert_content_data_list = [];
                            foreach ($val_field['data'] as $key_content => $val_content) {
                                if (empty($val_content)) {
                                    continue;
                                }
                                $type = 0;
                                switch ($val_content['record_field_type']) {
                                    case '文本':
                                        $type = 1;
                                        break;
                                    case '单选':
                                        $type = 3;
                                        break;
                                    default:
                                        $type = 0;
                                        break;
                                }
                                $uni_key = $this->getPinyinInitials($this->village['village_name'], true) . '_' . $this->getPinyinInitials($val_content['record_field_name'], true);
                                $insert_content_data = [
                                    'title' => $val_content['record_field_name'],
                                    'key' => $uni_key,
                                    'type' => $type,
                                    'add_time' => time(),
                                    'record_cate_id' => $last_field_id,
                                    'use_field' => $val_content['record_field_content'],
                                    'is_must' => $val_content['is_must'] == '是' ? 1: 0
                                ];

                                $category_content_where = [
                                    'title' => $val_content['record_field_name'],
                                    'key' => $uni_key,
                                ];
                                $category_content_exists = M('wisdom_qrcode_record_cate_field_config')->where($category_content_where)->find();

                                if( !empty( $category_content_exists ) ){// 修改
                                    $last_category_content_info = M('wisdom_qrcode_record_cate_field_config')->where(['acid'=>$category_content_exists['acid']])->data($insert_content_data)->save();
                                    $last_content_id = $category_content_exists['acid'];
                                }else{// 保存
                                    $last_content_id = M('wisdom_qrcode_record_cate_field_config')->data($insert_content_data)->add();
                                }
                            }
                        }
                    }
                }
            }
            if (empty($error_arr)) {
                $this->success('导入成功');
                exit;
            }else {
                $num = count($error_arr);
                // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                //导出失败信息
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                error_reporting(E_ALL);
                date_default_timezone_set('Europe/London');
                $objExcel = new PHPExcel();

                $title = '导入失败列表';
                // $title = $this->village['village_name'] . '社区-导入失败列表';
                /*以下是一些设置 ，什么作者  标题之类的*/
                $objExcel->getProperties()->setCreator($title)
                    ->setLastModifiedBy($title)
                    ->setTitle($title)
                    ->setSubject("失败信息导出")
                    ->setDescription("备份数据")
                    ->setKeywords("excel")
                    ->setCategory("result file");

                $i = 0;
                $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->setTitle($title);
                $objActSheet = $objExcel->getActiveSheet();
                $objActSheet->setCellValue('A1', '分类名');
                $objActSheet->setCellValue('B1', '记录字段名');
                $objActSheet->setCellValue('C1', '记录字段内容名称');
                $objActSheet->setCellValue('D1', '记录字段内容类型(文本或者单选)');
                $objActSheet->setCellValue('E1', '记录字段内容(如果内容类型为单选则填写该字段,多个条件用逗号分隔)');
                $objActSheet->setCellValue('F1', '记录字段内容是否必填(填是或者否)');
                $objActSheet->setCellValue('G1', '导入失败原因');
                $index = 2;
                foreach ($error_arr as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                    $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                    $index++;
                }

                //输出
                $objWriter = new PHPExcel_Writer_Excel5($objExcel);
                ob_end_clean();
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                header("Content-Type:application/force-download");
                header("Content-Type:application/vnd.ms-execl");
                header("Content-Type:application/octet-stream");
                header("Content-Type:application/download");
                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
        }
    }

    //导入分类
    public function import_qrcode_detail($result)
    {
        if (!empty($result) && is_array($result)) {
            unset($result[1]);
            $last_category_id = 0;
            $err_msg = '';
            $error_arr = [];
            $tmpdata = [];
            foreach ($result as $kk => $vv) {

                if (empty($vv['A'])) {
                    $vv['E'] = '请填写二维码名称！';
                    $error_arr[] = $vv;
                    continue;
                }
                if (empty($vv['B'])) {
                    $vv['E'] = '请填写经度！';
                    $error_arr[] = $vv;
                    continue;
                }
                if (empty($vv['C'])) {
                    $vv['E'] = '请填写纬度！';
                    $error_arr[] = $vv;
                    continue;
                }

                if (empty($vv['D'])) {
                    $vv['E'] = '请填写所属分类！';
                    $error_arr[] = $vv;
                    continue;
                }

                $category_info = M('wisdom_qrcode_category')->where(['name'=>$vv['D'],'village_id'=>$this->village_id])->find();
                if( empty( $category_info ) ){
                    $vv['E'] = '该分类不存在！';
                    $error_arr[] = $vv;
                    continue;
                }

                $tmpdata[] = [
                    'village_id' => $this->village_id,
                    'category_id' => $category_info['id'],
                    'name' => htmlspecialchars(trim($vv['A']), ENT_QUOTES),
                    'long' => htmlspecialchars(trim($vv['B']), ENT_QUOTES),
                    'lat' => htmlspecialchars(trim($vv['C']), ENT_QUOTES),
                ];
            }

            foreach ($tmpdata as $key => $value) {
                $insert_qrcode_data = [
                    'village_id' => $this->village_id,
                    'category_id' => $value['category_id'],
                    'name' => $value['name'],
                    'add_time' => time(),
                    'long' => $value['long'],
                    'lat' => $value['lat']
                ];

                $qrcode_where = [
                    'village_id' => $this->village_id,
                    'name' => $value['qrcode_name'],
                ];

                $qrcode_exists = M('wisdom_qrcode')->where($qrcode_where)->find();

                if( !empty( $qrcode_exists ) ){// 修改
                    $last_qrcode_info = M('wisdom_qrcode')->where(['id'=>$qrcode_exists['id']])->data($insert_qrcode_data)->save();
                    $last_qrcode_id = $qrcode_exists['id'];
                }else{// 保存
                    $last_qrcode_id = M('wisdom_qrcode')->data($insert_qrcode_data)->add();
                    M('wisdom_qrcode')->where(['id'=>$last_qrcode_id])->data(['num_id'=>$last_qrcode_id])->save();
                }
            }
            if (empty($error_arr)) {
                $this->success('导入成功');
                exit;
            }else {
                $num = count($error_arr);
                // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                //导出失败信息
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                error_reporting(E_ALL);
                date_default_timezone_set('Europe/London');
                $objExcel = new PHPExcel();

                $title = '导入失败列表';
                // $title = $this->village['village_name'] . '社区-导入失败列表';
                /*以下是一些设置 ，什么作者  标题之类的*/
                $objExcel->getProperties()->setCreator($title)
                    ->setLastModifiedBy($title)
                    ->setTitle($title)
                    ->setSubject("失败信息导出")
                    ->setDescription("备份数据")
                    ->setKeywords("excel")
                    ->setCategory("result file");

                $i = 0;
                $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);
                $objExcel->getActiveSheet()->setTitle($title);
                $objActSheet = $objExcel->getActiveSheet();
                $objActSheet->setCellValue('A1', '二维码名称(必填)');
                $objActSheet->setCellValue('B1', '位置(经度)(必填)');
                $objActSheet->setCellValue('C1', '位置(纬度)(必填)');
                $objActSheet->setCellValue('D1', '所属分类(必填)');
                $objActSheet->setCellValue('E1', '导入失败原因');
                $index = 2;
                foreach ($error_arr as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                    $index++;
                }

                //输出
                $objWriter = new PHPExcel_Writer_Excel5($objExcel);
                ob_end_clean();
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                header("Content-Type:application/force-download");
                header("Content-Type:application/vnd.ms-execl");
                header("Content-Type:application/octet-stream");
                header("Content-Type:application/download");
                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
        }
    }

    /**
     * 对数组进行双层分组格式化
     * 第一层：按 category_name 分组
     * 第二层：在 category_name 组内，按 record_name 分组
     * @param array $sourceArray 原始待格式化数组
     * @return array 格式化后的双层分组数组
     */
    public function formatArrayByDoubleGroupForCategory($sourceArray) {
        // 最终格式化结果
        $finalResult = [];

        // 安全判断：原始数组非数组或为空，直接返回空数组
        if (!is_array($sourceArray) || empty($sourceArray)) {
            return $finalResult;
        }

        // 第一层分组：按 category_name 分组
        foreach ($sourceArray as $item) {
            // 安全判断：当前元素非数组，跳过
            if (!is_array($item)) {
                continue;
            }
            // 获取 category_name（作为第一层分组键），默认空字符串避免报错
            $categoryName = $item['category_name'] ?? '';
            if ($categoryName === '') {
                continue;
            }

            // 提取第一层公共字段（village_id、category_name）
            $categoryPublicData = [
                'village_id' => $item['village_id'] ?? '',
                'category_name' => $categoryName
            ];

            // 提取第一层需要放入 data 的字段（排除 village_id、category_name）
            $categoryItemData = [];
            foreach ($item as $key => $value) {
                if (!in_array($key, ['village_id', 'category_name'])) {
                    $categoryItemData[$key] = $value;
                }
            }

            // 初始化：若该 category_name 分组不存在，先创建结构
            if (!isset($finalResult[$categoryName])) {
                $finalResult[$categoryName] = array_merge(
                    $categoryPublicData,
                    ['data' => []] // data 字段存储第二层分组数据
                );
            }

            // 第二层分组：在 category_name 组内，按 record_name 分组
            $recordName = $categoryItemData['record_name'] ?? '';
            if ($recordName === '') {
                // 无 record_name，直接放入 data（可选：也可跳过）
                $finalResult[$categoryName]['data'][] = ['data' => $categoryItemData];
                continue;
            }

            // 提取第二层公共字段（仅 record_name）
            $recordPublicData = ['record_name' => $recordName];
            // 提取第二层需要放入 data 的字段（排除 record_name）
            $recordItemData = [];
            foreach ($categoryItemData as $key => $value) {
                if ($key !== 'record_name') {
                    $recordItemData[$key] = $value;
                }
            }

            // 初始化：若该 record_name 分组不存在，先创建结构
            if (!isset($finalResult[$categoryName]['data'][$recordName])) {
                $finalResult[$categoryName]['data'][$recordName] = array_merge(
                    $recordPublicData,
                    ['data' => []] // 内层 data 存储最终业务数据
                );
            }

            // 将第二层 item 数据放入对应 record_name 组的 data 中
            $finalResult[$categoryName]['data'][$recordName]['data'][] = $recordItemData;
        }

        // 可选：将关联数组转为索引数组（更符合常规返回格式，如需保留键名可删除此步骤）
        $finalResult = array_values($finalResult);
        foreach ($finalResult as &$categoryItem) {
            if (isset($categoryItem['data']) && is_array($categoryItem['data'])) {
                $categoryItem['data'] = array_values($categoryItem['data']);
            }
        }
        unset($categoryItem); // 释放引用

        return $finalResult;
    }

    /**
     * 工单类目-类别列表
     * @author wanzy
     * @date_time 2021/08/13 17:07
     */
    public function getSubject()
    {
        $village_id = $this->village_id;
        $where['village_id'] = $village_id;
        $where['status'] = 1;
        $where['parent_id'] = 0;
        $field = 'cate_name as subject_name,id as workorder_id,color';
        try{
            $data = D('house_new_repair_cate')->where($where)->field($field)->select();
            return $data;
        }catch (\Exception $e){
            return [];
        }
    }


    public function cate_index()
    {
        if (!in_array(426, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['village_id'] = $this->village_id;
        $count_user = D('Wisdom_qrcode_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = D('Wisdom_qrcode_cate')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
        if (!empty($dataList)) {
            $third_robot_deviceDb = D('Third_robot_device');
            foreach ($dataList as $lkk => $lvv) {
                $dataList[$lkk]['robot_name'] = '';
                if ($lvv['from_type'] && $lvv['from_type'] == 1 && !empty($lvv['robot_code'])) {
                    $robot_device = $third_robot_deviceDb->where(array('robot_code' => $lvv['robot_code']))->field('village_id,name,robot_code')->find();
                    if ($robot_device) {
                        $dataList[$lkk]['robot_name'] = $robot_device['name'];
                    }
                }
            }
        }
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());

        $this->display();
    }

    public function cate_add()
    {
        if (!in_array(541, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $wisdomQrcodeDB = D('Wisdom_qrcode');
        if (IS_POST) {
            if (!$_POST['cate_name']) {
                $this->error('名称不能为空!');
            }
            $data['distance'] = $_POST['distance'] ? $_POST['distance'] : 0;
            $data['cate_name'] = $_POST['cate_name'];
            $data['add_time'] = time();
            $data['village_id'] = $this->village_id;
            $data['type'] = $_POST['type'] ?: 1;
            $data['record_type'] = $_POST['record_type'];
            $data['workorder_category_id'] = $_POST['workorderCategoryId'];
            // 初始化结果数组
            $day_times = [];

            // 获取开始时间和结束时间的数组
            $startTimes = $_POST['day_start_time'] ?? [];
            $endTimes = $_POST['day_end_time'] ?? [];

            // 遍历数组（按索引对应拼接）
            $maxIndex = max(count($startTimes), count($endTimes));
            for ($i = 0; $i < $maxIndex; $i++) {
                // 处理索引不存在的情况（避免报错）
                $start = isset($startTimes[$i]) ? $startTimes[$i] : '';
                $end = isset($endTimes[$i]) ? $endTimes[$i] : '';
                // 拼接格式：start-end（仅当两个值都存在时拼接，否则为空）
                $day_times[] = $start && $end ? "{$start}-{$end}" : '';
            }
            if ($_POST['record_type'] == 1) { //每日
//                $data['record_time'] = '';
                $data['record_time'] = implode(',', $day_times);
            } elseif ($_POST['record_type'] == 2) { //每周
//                $datas['record_time'] = '';
//                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
                $data['record_time'] = implode(',', $_POST['record_time']);
            } elseif ($_POST['record_type'] == 3) { //每月
//                $datas['record_time'] = '';
//                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
                $data['record_time'] = implode(',', $_POST['record_time']);
            }
            if ($_POST['record_type'] != 1) {
                $data['start_time'] = $_POST['start_time'];
                $data['end_time'] = $_POST['end_time'];
                $data['remind_time'] = $_POST['remind_time'];
            }
            $data['bind_qrcode'] = $_POST['qrcodeCheckList'];

            $inset_id = D('Wisdom_qrcode_cate')->data($data)->add();
            if ($inset_id) {
                $qrcodeList = array_column( json_decode(htmlspecialchars_decode($_POST['qrcodeCheckList'] ?? '[]'), true) ,'id');
                $qrcodeWhere['id'] = [' in ',$qrcodeList];
                $wisdomQrcodeDB->where($qrcodeWhere)->data(['cate_id'=> $inset_id])->save();
                $this->success('添加成功！', U('cate_index'));
            } else {
                $this->error('添加失败');
            }
        } else {
            //二维码列表
            $qrcodeList = M('Wisdom_qrcode as wq')
                ->join('pigcms_wisdom_qrcode_category as wqc ON wq.category_id = wqc.id')
                ->field('wq.*,wqc.name as category_name')
                ->where(array('wq.village_id' => $this->village_id))
                ->select();

            $checkboxList = [];
            if (!empty($qrcodeList)) {
                foreach ($qrcodeList as $k => $v) {
                    $checkboxList[$v['category_id']]['category_id'] = $v['category_id'];
                    $checkboxList[$v['category_id']]['category_name'] = $v['category_name'];
                    $checkboxList[$v['category_id']]['data'][] = [
                        'id' => $v['id'],
                        'label' => $v['name'],
                        'isChecked' => $v['cate_id'] != 0? true : false,
                    ];
                }
            }
            sort($checkboxList);
            $workOrderList = $this->getSubject();
            $this->assign('workorderCategoryList', $workOrderList);
            $this->assign('qrcodeList', json_encode($checkboxList, true));
            $this->display();
        }
    }

    public function cate_edit()
    {
        if (!in_array(542, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $wisdomQrcodeDB = D('Wisdom_qrcode');
        $wisdomQrcodeCateMapDB = D('Wisdom_qrcode_cate_map');
        $wisdomQrcodeCateDB = D('Wisdom_qrcode_cate');
        if (IS_POST) {
            if( empty($_POST['qrcodeCheckList']) ){
                $this->error('请选择二维码');
            }
            $id = $_POST['id'];
            $data['distance'] = $_POST['distance'] ? $_POST['distance'] : 0;
            $data['cate_name'] = $_POST['cate_name'];
            $data['record_type'] = $_POST['record_type'];
            $data['workorder_category_id'] = $_POST['workorderCategoryId'];
            // 初始化结果数组
            $day_times = [];
            // 获取开始时间和结束时间的数组
            $startTimes = $_POST['day_start_time'] ?? [];
            $endTimes = $_POST['day_end_time'] ?? [];

            // 遍历数组（按索引对应拼接）
            $maxIndex = max(count($startTimes), count($endTimes));
            for ($i = 0; $i < $maxIndex; $i++) {
                // 处理索引不存在的情况（避免报错）
                $start = isset($startTimes[$i]) ? $startTimes[$i] : '';
                $end = isset($endTimes[$i]) ? $endTimes[$i] : '';
                // 拼接格式：start-end（仅当两个值都存在时拼接，否则为空）
                $day_times[] = $start && $end ? "{$start}-{$end}" : '';
            }

            if ($_POST['record_type'] == 1) { //每日
//                $data['record_time'] = '';
                $data['record_time'] = implode(',', $day_times);
            } elseif ($_POST['record_type'] == 2) { //每周
//                $datas['record_time'] = '';
//                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
                $data['record_time'] = implode(',', $_POST['record_time2']);
            } elseif ($_POST['record_type'] == 3) { //每月
//                $datas['record_time'] = '';
//                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
                $data['record_time'] = implode(',', $_POST['record_time3']);
            }

            $data['start_time'] = $_POST['start_time'];
            $data['end_time'] = $_POST['end_time'];
            $data['remind_time'] = $_POST['remind_time'];
            $data['type'] = $_POST['type'];
            $data['robot_code'] = '1';
            $data['task_instance_id'] = '1';
            $data['task_type'] = '1';
//            $data['bind_qrcode'] = $_POST['qrcodeCheckList'];

            $result = $wisdomQrcodeCateDB->where(array('id' => $id))->data($data)->save();
            if ($result !== false) {
                //先把当前cate_id原有的qrcode都清空
                $wisdomQrcodeDB->where(['cate_id'=> $id])->data(['cate_id'=>null])->save();
                $qrcodeList = array_column( json_decode(htmlspecialchars_decode($_POST['qrcodeCheckList'] ?? '[]'), true) ,'id');
                $qrcodeWhere['id'] = [' in ',$qrcodeList];
                $wisdomQrcodeDB->where($qrcodeWhere)->data(['cate_id'=> $id])->save();
                $this->success('修改成功！', U('cate_index'));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = $wisdomQrcodeCateDB->where(array('id' => $id))->find();
            $currentCateQrcode = D('Wisdom_qrcode as wq')
                ->join('pigcms_wisdom_qrcode_category as wqc ON wq.category_id = wqc.id')
                ->where(array('wq.cate_id' => $id))
                ->field('wq.id as id,wq.name as label,wqc.id as category_id,wqc.name as category_name,wq.cate_id ')
                ->select();
            $cate['bind_qrcode'] = json_encode($currentCateQrcode, true);
            $cate['record_time'] = explode(',', $cate['record_time']);
//            if( $cate['record_type'] == 1 && !empty($cate['record_time']) ){
//                $result = ['day_start_time' => [], 'day_end_time' => []];
//                foreach ($cate['record_time'] as $timeStr) {
//                    [$start, $end] = explode('-', $timeStr);
//                    $result['day_start_time'][] = $start;
//                    $result['day_end_time'][] = $end;
//                }
//                $cate['record_time'] = $result;
//            }
            //二维码列表
            $qrcodeList = M('Wisdom_qrcode as wq')
                ->join('pigcms_wisdom_qrcode_category as wqc ON wq.category_id = wqc.id')
                ->field('wq.*,wqc.name as category_name')
                ->where(array('wq.village_id' => $this->village_id))
                ->select();

            $checkboxList = [];
            if (!empty($qrcodeList)) {
                foreach ($qrcodeList as $k => $v) {
                    $checkboxList[$v['category_id']]['category_id'] = $v['category_id'];
                    $checkboxList[$v['category_id']]['category_name'] = $v['category_name'];
                    $checkboxList[$v['category_id']]['data'][] = [
                        'id' => $v['id'],
                        'label' => $v['name'],
                        'isChecked' => $v['cate_id'] != 0 && $v['cate_id'] != $id ? true : false,
                    ];
                }
            }
            sort($checkboxList);
            $workOrderList = $this->getSubject();
            $this->assign('workorderCategoryList', $workOrderList);
            $this->assign('qrcodeList', json_encode($checkboxList, true));
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function cate_del()
    {
        if (!in_array(543, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        //介绍字段
//        $qrcode_field_cate = M('Wisdom_qrcode_field_cate')->where(array('cate_id' => $_GET['id']))->find();
//        if ($qrcode_field_cate) {
//
//            //介绍字段配置项
//            $qrcode_cate_field_cate_config = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $qrcode_field_cate['id']))->find();
//            if ($qrcode_cate_field_cate_config) {
//                $this->error('请先删除当前设备介绍字段管理下面的配置项列表');
//            } else {
//                $this->error('请先删除当前设备介绍字段管理列表');
//            }
//        }

        //二维码
//        $qrcode = M('Wisdom_qrcode')->where(array('cate_id' => $_GET['id']))->find();
//        if ($qrcode) {
//            $this->error('请删除当前类别下的所有二维码!');
//        }

        //记录内容管理
//        $record_cate = M('Wisdom_qrcode_record_cate')->where(array('cate_id' => $_GET['id']))->find();
//        if ($record_cate) {
//            $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('record_cate_id' => $record_cate['id']))->find();
//            if ($qrcode_record_cate_field_config) {
//                $this->error('请先删除当前记录记录内容管理列表下面的配置项列表');
//            } else {
//                $this->error('请先删除当前记录记录内容管理列表');
//            }
//        }

        //负责人
        $qrcode_person = M('Wisdom_qrcode_person')->where(array('cate_id' => $_GET['id']))->find();
        if ($qrcode_person) {
            $this->error('删除当前类别下的所有的负责人!');
        }

        $id = D('Wisdom_qrcode_cate')->where(array('id' => $_GET['id']))->delete();

        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function cate_field_index()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['cate_id'] = $_GET['cate_id'];
        $count_user = D('Wisdom_qrcode_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode_field_cate')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }


    public function cate_field_add()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (!$_POST['field_name']) {
                $this->error('名称不能为空!');
            }
            $data['cate_id'] = $_POST['cate_id'];
            $data['field_name'] = $_POST['field_name'];
            $data['sort'] = $_POST['sort'];
            $data['add_time'] = time();
            $data['village_id'] = $this->village_id;
            $inset_id = M('Wisdom_qrcode_field_cate')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！', U('cate_field_index', array('cate_id' => $_GET['cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    public function cate_field_edit()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            $data['field_name'] = $_POST['field_name'];
            $data['sort'] = $_POST['sort'];
            $result = M('Wisdom_qrcode_field_cate')->where(array('id' => $id))->data($data)->save();
            if ($result !== false) {
                $this->success('修改成功！', U('cate_field_index', array('cate_id' => $_GET['cate_id'])));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_field_cate')->where(array('id' => $id))->find();
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function cate_field_del()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $qrcode_cate_field_cate_config = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $_GET['id']))->find();
        if ($qrcode_cate_field_cate_config) {
            $this->error('请先删除当前设备介绍字段管理下面的配置项列表');
        }
        $id = M('Wisdom_qrcode_field_cate')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function cate_field_cate_config_index()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $field_cate_id = $_GET['field_cate_id'];
        $dataList = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $field_cate_id))->select();
        $this->assign('dataList', $dataList);
        $this->display();
    }

    public function cate_field_cate_config_add()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (empty($_POST['title'])) {
                $this->error('名称不能为空!');
            }
            if (!isset($_POST['key']) || empty($_POST['key'])) {
                $_POST['key'] = uniqid() . generate_password(8);
            }
            if (empty($_POST['key'])) {
                $this->error('唯一标识不能为空!');
            }
            $cate = D('Wisdom_qrcode_cate_field_cate_config')->where(array('key' => $_POST['key']))->find();
            if ($cate) {
                $this->error('标识必须唯一!');
            }
            $data['title'] = $_POST['title'];
            $data['field_cate_id'] = $_POST['field_cate_id'];
            $data['field_sort'] = $_POST['field_sort'];
            $data['type'] = $_POST['type'];
            $data['key'] = $_POST['key'];
            $data['add_time'] = time();
            $inset_id = M('Wisdom_qrcode_cate_field_cate_config')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！');
                //$this->success('添加成功！',U('cate_field_cate_config_index',array('field_cate_id'=>$_POST['field_cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    public function cate_field_cate_config_edit()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $where['acid'] = $_POST['id'];
            $cate = D('Wisdom_qrcode_cate_field_cate_config')->where($where)->find();
            if (!$cate) {
                $this->error('数据不存在!');
            }
            $id = $_POST['id'];
            $data['title'] = $_POST['title'];
            $data['field_sort'] = $_POST['field_sort'];
            $data['type'] = $_POST['type'];
            if (empty($cate['key'])) {
                $data['key'] = uniqid() . generate_password(8);
            }
            $result = M('Wisdom_qrcode_cate_field_cate_config')->where(array('acid' => $id))->data($data)->save();
            if ($result !== false) {
                $this->success('修改成功！');
                //                $this->success('修改成功！',U('cate_field_cate_config_index',array('field_cate_id'=>$_POST['field_cate_id'])));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_cate_field_cate_config')->where(array('acid' => $id))->find();

            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function cate_field_cate_config_del()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = M('Wisdom_qrcode_cate_field_cate_config')->where(array('acid' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    //二维码管理
    public function qrcode_index()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

//        $where['cate_id'] = $_GET['cate_id'];
        $where['wq.village_id'] = $this->village_id;

        if( isset($_GET['categoryId']) && !empty($_GET['categoryId']) && $_GET['categoryId'] != 0 ){
            $where['wq.category_id'] = $_GET['categoryId'];
            $this->assign('searchCategoryId', $_GET['categoryId']);
        }else{
            $this->assign('searchCategoryId', 0);
        }

        $count_user = M('Wisdom_qrcode as wq')
            ->join('pigcms_wisdom_qrcode_category as wqc ON wq.category_id = wqc.id')
            ->where($where)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode as wq')
            ->join('pigcms_wisdom_qrcode_category as wqc ON wq.category_id = wqc.id')
            ->field('wq.*,wqc.name as category_name')
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());

        $base_url = D('House_village')->base_url;

        //分类
        $category_list = M('wisdom_qrcode_category')->where(array('village_id' => $this->village_id))->select();
        $this->assign('categoryList', $category_list);

        //二维码
        $qrcode_url = $this->config['site_url'] . $base_url . 'pages/village/smartQRcode/elevatorManagement';
        $this->assign('qrcode_url', $qrcode_url);
        $this->assign('village_id', $this->village_id);
        $this->display();
    }

    //分类管理
    public function category_index()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['village_id'] = $this->village_id;
        $count_user = M('wisdom_qrcode_category')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('wisdom_qrcode_category')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->assign('village_id', $this->village_id);
        $this->display();
    }

    //异常管理
    public function qrcode_abnormality_index()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $where['qrl.village_id'] = $this->village_id;
        $where['qrl.is_abnormal'] = 1;
        if( isset($_GET['categoryId']) && !empty($_GET['categoryId']) && $_GET['categoryId'] != 0 ){
            $where['wq.category_id'] = $_GET['categoryId'];
            $this->assign('searchCategoryId', $_GET['categoryId']);
        }else{
            $this->assign('searchCategoryId', 0);
        }
        import('@.ORG.merchant_page');
        $field = 'qrl.*,wqc.cate_name as plan_name,whw.name as worker_name,whw.phone as phone,pg.name as department_name,wq.name as qrcode_name,wqcg.name as qrcode_category_name,hnrwo.event_status';
        $qrcode_record_log_num = M('wisdom_qrcode_record_log as qrl')
            ->join('pigcms_wisdom_qrcode_cate as wqc ON qrl.cate_id = wqc.id')
            ->join( 'pigcms_house_worker as whw ON qrl.uid = whw.wid' )
            ->join( 'pigcms_property_group as pg ON pg.id = whw.department_id' )
            ->join( 'pigcms_wisdom_qrcode as wq ON wq.id = qrl.qrcode_id' )
            ->join( 'pigcms_wisdom_qrcode_category as wqcg ON wq.category_id = wqcg.id' )
            ->join( 'pigcms_house_new_repair_works_order as hnrwo ON qrl.work_order_id = hnrwo.order_id' )
            ->where($where)
            ->field($field)
            ->count();
        $p = new Page($qrcode_record_log_num, 20, 'page');
        $qrcode_record_log = M('wisdom_qrcode_record_log as qrl')
            ->join('pigcms_wisdom_qrcode_cate as wqc ON qrl.cate_id = wqc.id')
            ->join( 'pigcms_house_worker as whw ON qrl.uid = whw.wid' )
            ->join( 'pigcms_property_group as pg ON pg.id = whw.department_id' )
            ->join( 'pigcms_wisdom_qrcode as wq ON wq.id = qrl.qrcode_id' )
            ->join( 'pigcms_wisdom_qrcode_category as wqcg ON wq.category_id = wqcg.id' )
            ->join( 'pigcms_house_new_repair_works_order as hnrwo ON qrl.work_order_id = hnrwo.order_id' )
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->field($field)
            ->select();
        $abnormality_list = array();
        foreach( $qrcode_record_log as &$log ){
            if( empty($log['entication_field']) ){
                continue;
            }
            $entication_field = unserialize( $log['entication_field']);
            $log['entication_field_arr'] = $entication_field;
            if( empty($entication_field['test']) ){
                continue;
            }
            switch($log['event_status'])
            {
                case 10: $log['event_status_str'] = '未指派';break;
                case 20: $log['event_status_str'] = '已指派';break;
                case 30: $log['event_status_str'] = '处理中';break;
                case 40: $log['event_status_str'] = '已办结';break;
                case 50: $log['event_status_str'] = '已撤回';break;
                case 60: $log['event_status_str'] = '已关闭';break;
                case 70: $log['event_status_str'] = '已评价';break;
                default: $log['event_status_str'] = '未指派';break;
            }

            $works_order_str = implode( ',',array_slice( array_column($log['entication_field_arr']['test'], 'record_name') ,0,3) );
            $log['works_order_str'] = $works_order_str;
            $abnormality_list[] = $log;
        }
        $category_list = M('wisdom_qrcode_category')->where(array('village_id' => $this->village_id))->select();
        $this->assign('categoryList', $category_list);

        $this->assign('pagebar', $p->show());
        $this->assign('dataList', $abnormality_list);

        $this->assign('village_id', $this->village_id);
        $this->display();
    }

    function checkHasFixedPointAbnormality(array $arr): bool
    {
        // 遍历一维索引数组中的每个关联数组元素
        foreach ($arr as $item) {
            // 1. 判断当前元素是否包含指定key 2. 判断对应value是否为"是"（严格匹配）
            if (isset($item['key']) && $item['key'] === 'FIXED_POINT_IS_ABNORMALITY'
                && isset($item['value']) && $item['value'] === '是') {
                return true; // 找到符合条件的元素，直接返回true
            }
        }

        // 遍历结束未找到符合条件的元素，返回false
        return false;
    }

    public function category_add()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error('分类名称必须填!');
            }
            $data['name'] = $_POST['name'];
            $data['village_id'] = $this->village_id;
            $data['addtime'] = date('Y-m-d H:i:s', time());
            $data['status'] = 1;
            $insert_id = M('wisdom_qrcode_category')->data($data)->add();
            if ($insert_id) {
                $this->success('添加成功！', U('category_index'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    public function category_edit()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            if (empty($_POST['name'])) {
                $this->error('分类名称必须填!');
            }

            $data['name'] = $_POST['name'];
            $data['status'] = $_POST['status'];

            $insert_id = M('wisdom_qrcode_category')->where(array('id' => $id))->data($data)->save();
            if ($insert_id) {
                $this->success('修改成功！', U('category_index'));
            } else {
                $this->error('修改失败');
            }
        } else {
            $id = $_GET['id'];
            $category = D('wisdom_qrcode_category')->where(array('id' => $id))->find();
            $this->assign('category', $category);
            $this->display();
        }
    }

    public function category_del()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = M('wisdom_qrcode_category')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function qrcode_add()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
//            $cate_id = $_POST['cate_id'];
            if (empty($_POST['name'])) {
                $this->error('二维码名称必须填!');
            }
//            if (empty($_POST['image_path'])) {
//                $this->error('设备logo必须填!');
//            }
            if (empty($_POST['long_lat'])) {
                $this->error('经纬度必须填!');
            }
            $data['name'] = $_POST['name'];
            $data['village_id'] = $this->village_id;
            $long_lat = explode(',', $_POST['long_lat']);
            $data['long'] = $long_lat[0];
            $data['lat'] = $long_lat[1];
            $data['image_path'] = $_POST['image_path'];
            $data['add_time'] = time();
            $data['cate_id'] = $_POST['cate_id'];
//            $data['num_id'] = $_POST['num_id'];
            $data['category_id'] = $_POST['categoryId'];
            unset($_POST['name']);
            unset($_POST['image_path']);
            unset($_POST['long_lat']);
            unset($_POST['cate_id']);
            //            var_dump(json_encode($_POST));die;
            $data['entication_field'] = serialize($_POST);
            //var_dump($data);die;
            $insert_id = M('Wisdom_qrcode')->data($data)->add();
            if ($insert_id) {
                M('Wisdom_qrcode')->where(array('id' => $insert_id))->data(['num_id' => $insert_id])->save();
                $this->success('添加成功！', U('qrcode_index'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $category_list = M('wisdom_qrcode_category')->where(array('village_id' => $this->village_id))->select();
            $this->assign('categoryList', $category_list);
            $this->display();
        }
    }

    public function qrcode_edit()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
//            $cate_id = $_POST['cate_id'];
            $id = $_POST['id'];
            if (empty($_POST['name'])) {
                $this->error('二维码名称必须填!');
            }
//            if (empty($_POST['image_path'])) {
//                $this->error('设备logo必须填!');
//            }
            if (empty($_POST['long_lat'])) {
                $this->error('经纬度必须填!');
            }

            $data['name'] = $_POST['name'];
            $long_lat = explode(',', $_POST['long_lat']);
            $data['long'] = $long_lat[0];
            $data['lat'] = $long_lat[1];
            $data['image_path'] = $_POST['image_path'];
            $data['add_time'] = time();
            $data['cate_id'] = $_POST['cate_id'];
//            $data['num_id'] = $_POST['num_id'];
            $data['category_id'] = $_POST['categoryId'];
            unset($_POST['name']);
            unset($_POST['image_path']);
            unset($_POST['long_lat']);

            unset($_POST['cate_id']);
            unset($_POST['id']);
//            unset($_POST['num_id']);
            $data['entication_field'] = serialize($_POST);

            $insert_id = M('Wisdom_qrcode')->where(array('id' => $id))->data($data)->save();
            if ($insert_id) {
                $this->success('修改成功！', U('qrcode_index'));
            } else {
                $this->error('修改失败');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode')->where(array('id' => $id))->find();

            $cate['entication_field'] = unserialize($cate['entication_field']);
            //分类列表
            $category_list = M('wisdom_qrcode_category')->where(array('village_id' => $this->village_id))->select();
            $this->assign('categoryList', $category_list);
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function qrcode_del()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = M('Wisdom_qrcode')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function record_cate_index()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['category_id'] = intval($_GET['category_id']);
        $count_user = D('Wisdom_qrcode_record_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode_record_cate')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function record_cate_add()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $data['record_name'] = $_POST['record_name'];
            $data['sort'] = $_POST['sort'];
            $data['add_time'] = time();
            $data['status'] = $_POST['status'];
            $data['category_id'] = $_POST['category_id'];
            $insert_id = M('Wisdom_qrcode_record_cate')->data($data)->add();
            if ($insert_id) {
                $this->success('添加成功！', U('record_cate_index', array('category_id' => $_POST['category_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    public function record_cate_edit()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            $data['record_name'] = $_POST['record_name'];
            $data['sort'] = $_POST['sort'];
            $data['status'] = $_POST['status'];
            //            var_dump($data);die;
            $result = M('Wisdom_qrcode_record_cate')->where(array('id' => $id))->data($data)->save();
            $category = D('Wisdom_qrcode_record_cate')->where(array('id' => $id))->find();
            if ($result !== false) {
                $this->success('修改成功！', U('record_cate_index', array('category_id' => $category['category_id'])));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_record_cate')->where(array('id' => $id))->find();
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function record_cate_del()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('record_cate_id' => $_GET['id']))->find();
        if ($qrcode_record_cate_field_config) {
            $this->error('请先删除当前记录记录内容管理列表下面的配置项列表');
        }
        $id = M('Wisdom_qrcode_record_cate')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function record_cate_field_config_index()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $record_cate_id = $_GET['record_cate_id'];
        $dataList = M('Wisdom_qrcode_record_cate_field_config')->where(array('record_cate_id' => $record_cate_id))->order('sort desc,acid desc')->select();
        $this->assign('dataList', $dataList);
        $this->display();
    }

    public function record_cate_field_config_add()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if (empty($_POST['title'])) {
                $this->error('名称不能为空!');
            }
            // if (empty($_POST['key'])) {
            //     $this->error('唯一标识不能为空!');
            // }

            $uni_key = $this->getPinyinInitials($this->village['village_name'], true) . '_' . $this->getPinyinInitials($_POST['title'], true);
            if ($this->isOnlyUnderscore($uni_key)) {
                $this->error('唯一标识不能为空!');
            }
            $record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('key' => $uni_key))->find();
            if ($record_cate_field_config) {
                $this->error('唯一标识已存在!');
            }
            if ($_POST['type'] == 3) {
                if (empty($_POST['title'])) {
                    $this->error('单选内容不能为空!');
                }
            }
            $data['title'] = $_POST['title'];
            $data['sort'] = $_POST['sort'];
            $data['type'] = $_POST['type'];
            $data['key'] = $uni_key;
            $data['is_must'] = $_POST['is_must'];
            $data['add_time'] = time();
            $data['record_cate_id'] = $_POST['record_cate_id'];
            if ($_POST['use_field']) {
                $data['use_field'] = implode(',', explode(PHP_EOL, $_POST['use_field']));
            }
            $inset_id = M('Wisdom_qrcode_record_cate_field_config')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！', U('record_cate_field_config_index', array('record_cate_id' => $_POST['record_cate_id'], 'cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    /**
     * 获取中文字符串的拼音首字母
     * * @param string $str 输入的字符串 (UTF-8)
     * @return string 返回拼音首字母字符串 (大写)
     */
    public function getPinyinInitials($str)
    {
        if (empty($str)) {
            return '';
        }

        // 1. 将 UTF-8 转为 GBK，无法转换的字符忽略
        // GBK 编码中，汉字是按拼音排序的，这是此方法的核心
        $str = iconv("UTF-8", "GBK//IGNORE", $str);
        $result = '';
        $length = strlen($str);

        for ($i = 0; $i < $length; $i++) {
            $ord = ord($str[$i]);

            // 2. 如果是 ASCII 字符 (0-127)，直接保留 (如数字、字母)
            if ($ord < 128) {
                $result .= strtoupper($str[$i]);
                continue;
            }

            // 3. 如果是双字节字符 (汉字)，获取其十进制值
            // GBK 汉字由两个字节组成，第一个字节 > 128
            if ($i + 1 < $length) {
                $asc = $ord * 256 + ord($str[$i + 1]) - 65536;

                // 4. 根据 GBK 编码范围判断首字母
                if ($asc >= -20319 && $asc <= -20284) $result .= 'A';
                elseif ($asc >= -20283 && $asc <= -19776) $result .= 'B';
                elseif ($asc >= -19775 && $asc <= -19219) $result .= 'C';
                elseif ($asc >= -19218 && $asc <= -18711) $result .= 'D';
                elseif ($asc >= -18710 && $asc <= -18527) $result .= 'E';
                elseif ($asc >= -18526 && $asc <= -18240) $result .= 'F';
                elseif ($asc >= -18239 && $asc <= -17923) $result .= 'G';
                elseif ($asc >= -17922 && $asc <= -17418) $result .= 'H';
                elseif ($asc >= -17417 && $asc <= -16475) $result .= 'J';
                elseif ($asc >= -16474 && $asc <= -16213) $result .= 'K';
                elseif ($asc >= -16212 && $asc <= -15641) $result .= 'L';
                elseif ($asc >= -15640 && $asc <= -15166) $result .= 'M';
                elseif ($asc >= -15165 && $asc <= -14923) $result .= 'N';
                elseif ($asc >= -14922 && $asc <= -14915) $result .= 'O';
                elseif ($asc >= -14914 && $asc <= -14631) $result .= 'P';
                elseif ($asc >= -14630 && $asc <= -14150) $result .= 'Q';
                elseif ($asc >= -14149 && $asc <= -14091) $result .= 'R';
                elseif ($asc >= -14090 && $asc <= -13319) $result .= 'S';
                elseif ($asc >= -13318 && $asc <= -12839) $result .= 'T';
                elseif ($asc >= -12838 && $asc <= -12557) $result .= 'W';
                elseif ($asc >= -12556 && $asc <= -11848) $result .= 'X';
                elseif ($asc >= -11847 && $asc <= -11056) $result .= 'Y';
                elseif ($asc >= -11055 && $asc <= -10247) $result .= 'Z';

                // 跳过下一个字节，因为汉字占两个字节
                $i++;
            }
        }

        return $result;
    }

    public function isOnlyUnderscore($str)
    {
        // 1. 字符串不能为空
        // 2. 剔除 '_' 后必须为空字符串
        return $str !== '' && trim($str, '_') === '';
    }

    public function record_cate_field_config_edit()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            if (empty($_POST['title'])) {
                $this->error('名称不能为空!');
            }

            $uni_key = $this->getPinyinInitials($this->village['village_name'], true) . '_' . $this->getPinyinInitials($_POST['title'], true);
            if ($this->isOnlyUnderscore($uni_key)) {
                $this->error('唯一标识不能为空!');
            }
            $where['acid'] = array('neq', $_POST['id']);
            $record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('key' => $uni_key))->find();

            if ($record_cate_field_config) {
                $this->error('唯一标识已存在!');
            }
            if ($_POST['type'] == 3) {
                if (empty($_POST['title'])) {
                    $this->error('单选内容不能为空!');
                }
            }
            $data['title'] = $_POST['title'];
            $data['sort'] = $_POST['sort'];
            $data['type'] = $_POST['type'];
            $data['key'] = $uni_key;
            $data['is_must'] = $_POST['is_must'];
            $data['record_cate_id'] = $_POST['record_cate_id'];
            if ($_POST['type'] == 3) {
                if ($_POST['use_field']) {
                    $data['use_field'] = implode(',', explode(PHP_EOL, $_POST['use_field']));
                }
            } else {
                $data['use_field'] = '';
            }

            $inset_id = M('Wisdom_qrcode_record_cate_field_config')->where(array('acid' => $id))->data($data)->save();
            if ($inset_id) {
                $this->success('修改成功！', U('record_cate_field_config_index', array('record_cate_id' => $_POST['record_cate_id'], 'cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('修改失败');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_record_cate_field_config')->where(array('acid' => $id))->find();
            if ($cate['use_field']) {
                $cate['use_field'] = implode(PHP_EOL, explode(',', $cate['use_field']));
            }

            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function record_cate_field_config_del()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = M('Wisdom_qrcode_record_cate_field_config')->where(array('acid' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }


    //负责人
    public function perpon_index()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['cate_id'] = $_GET['cate_id'];
        $count_user = D('Wisdom_qrcode_person')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode_person')->where($where)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($dataList as $k => &$v) {
            $house_worker = D('House_worker')->where(array('wid' => $v['uid'], 'village_id' => $this->village_id, 'del' => 0))->find();
            if ($house_worker) {
                $v['name'] = $house_worker['name'];
            }
        }

        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function perpon_add()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $data['uid'] = $_POST['uid'];
            $data['user_phone'] = $_POST['user_phone'];
//            $data['record_type'] = $_POST['record_type'];
//            if ($_POST['record_type'] == 1) { //每日
//                $data['record_time'] = '';
//            } elseif ($_POST['record_type'] == 2) { //每周
//
//                $data['record_time'] = implode(',', $_POST['record_time']);
//            } elseif ($_POST['record_type'] == 3) { //每月
//
//                $data['record_time'] = implode(',', $_POST['record_time']);
//            }

//            $data['start_time'] = $_POST['start_time'];
//            $data['end_time'] = $_POST['end_time'];
//            $data['remind_time'] = $_POST['remind_time'];
            $data['status'] = $_POST['status'];
            $data['cate_id'] = $_POST['cate_id'];
            $data['add_time'] = time();
            $inset_id = M('Wisdom_qrcode_person')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！', U('perpon_index', array('cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $house_worker = D('House_worker')->where(array('village_id' => $this->village_id, 'is_del' => 0, 'status' => 1))->select();
            foreach ($house_worker as $k => &$v) {
                //                $user = D('User')->where(array('phone'=>$v['phone']))->find();
                //                if($user){
                //                   $v['uid']=$user['uid'];
                //                }
            }
            $this->assign('house_worker', $house_worker);
            $this->display();
        }
    }

    public function perpon_edit()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if ($_POST['start_time'] && $_POST['end_time']) {
                $start_time = (int)str_replace(":", "", $_POST['start_time']);
                $end_time = (int)str_replace(":", "", $_POST['end_time']);
                if ($start_time > $end_time) {
                    $this->error('起止开始时间不可大于起止结束时间（仅限当天范围）');
                }
            }
            $data['uid'] = $_POST['uid'];
            $data['user_phone'] = $_POST['user_phone'];
//            $data['record_type'] = $_POST['record_type'];
//            if ($_POST['record_type'] == 1) { //每日
//                $data['record_time'] = '';
//            } elseif ($_POST['record_type'] == 2) { //每周
//                $datas['record_time'] = '';
//                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
//                $data['record_time'] = implode(',', $_POST['record_time2']);
//            } elseif ($_POST['record_type'] == 3) { //每月
//                $datas['record_time'] = '';
//                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
//                $data['record_time'] = implode(',', $_POST['record_time3']);
//            }

//            $data['start_time'] = $_POST['start_time'];
//            $data['end_time'] = $_POST['end_time'];
//            $data['remind_time'] = $_POST['remind_time'];
            $data['status'] = $_POST['status'];
            $data['cate_id'] = $_POST['cate_id'];

            $inset_id = M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($data)->save();
            if ($inset_id) {
                $this->success('修改成功！', U('perpon_index', array('cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('修改失败');
            }
        } else {
            $qrcode_person = M('Wisdom_qrcode_person')->where(array('id' => $_GET['id']))->find();
            $qrcode_person['record_time'] = explode(',', $qrcode_person['record_time']);
            $this->assign('qrcode_person', $qrcode_person);
            $house_worker = D('House_worker')->where(array('village_id' => $this->village_id, 'is_del' => 0, 'status' => 1))->select();
            //            foreach($house_worker as $k=>&$v){
            ////                $user = D('User')->where(array('phone'=>$v['phone']))->find();
            ////                if($user){
            ////                    $v['uid']=$user['uid'];
            ////                }
            //            }
            $this->assign('house_worker', $house_worker);
            $this->display();
        }
    }

    public function perpon_del()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = M('Wisdom_qrcode_person')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function qrcode_record_ai()
    {
        $where = array();
        $where['village_id'] = $this->village_id;
        $add_time_start = $_GET['begin_time'];
        $add_time_end = $_GET['end_time'];
        $this->assign('begin_time', $add_time_start);
        $this->assign('end_time', $add_time_end);

        if ($add_time_start && $add_time_end) {

            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');

            $where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('lt', $add_time_end);
        }
        $visionLogTyppeInfo = array(
            'doorStatus' => '大门检测',
            'vehicle' => '车辆乱停放',
            'garbageCan' => '垃圾桶满溢',
            'trash' => '绿化带垃圾',
            'climb' => '翻越围栏',
            'crowd' => '人员聚集',
            'countVehicle' => '车辆堵塞',
            'carPlate' => '车位识别',
            'lawnTrample' => '践踏草坪',
            'cvMeter' => '巡检仪表识别',
            'countCan' => '垃圾桶数量识别',
            'trashbin' => '垃圾桶盖',
            'bottle' => '地面垃圾',
            'occupyEA' => '应急通道占用',
        );
        if ($find_type = $_GET['find_type'] + 0) {
            switch ($find_type) {
                case 1: //二维码名称
                    //$where['usernum'] = $_GET['find_value'];
                    $qrcode['name'] = $_GET['find_value'];
                    $qrcode_ids = [];
                    $qrcode_list = M('wisdom_qrcode')->where($qrcode)->select();
                    foreach ($qrcode_list as $k => $v) {
                        $qrcode_ids[] = $v['id'];
                    }
                    $where['qrcode_id'] = array('in', $qrcode_ids);
                    break;
                case 2: //二维码类别
                    //$where['phone'] = $_GET['find_value'];
                    $qrcode['cate_name'] = $_GET['find_value'];
                    $cate_ids = [];
                    $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->select();
                    foreach ($qrcode_cate as $k => $v) {
                        $cate_ids[] = $v['id'];
                    }
                    $where['cate_id'] = array('in', $cate_ids);
                    break;
                default:
                    break;
            }
        }
        $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
        $patrol_type = 3; //1 系统巡检 2智能巡检  3AI识别巡检 
        switch ($view_type) {
            case 1:
                $where['view_time'] = array('gt', 1);
                break;
            case 2:
                $where['view_time'] = array('lt', 1);
                break;
            case 3:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('gt', 0);
                break;
            case 4:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('lt', 1);
                break;
            default:
                break;
        }
        if ($patrol_type == 1) {
            $where['from_type'] = 0;
        } else if ($patrol_type == 2) {
            $where['from_type'] = 1;
            $where['rtype'] = 1;
        } else if ($patrol_type == 3) {
            $where['from_type'] = 1;
            $where['rtype'] = 2;
        }
        $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
        if (!empty($recognition_type)) {
            $where['robot_recognition_type'] = $recognition_type;
        }
        $count_user = D('Wisdom_qrcode_record_log')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 15, 'page');
        $dataList = M('Wisdom_qrcode_record_log')->where($where)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $third_robot_deviceDb = D('Third_robot_device');
        foreach ($dataList as $k => &$v) {
            $cate = D('Wisdom_qrcode_cate')->where(array('id' => $v['cate_id']))->find();
            if ($cate) {
                $v['cate_name'] = $cate['cate_name'];
            } else {
                $v['cate_name'] = '';
            }
            $v['patrol_type_str'] = '系统巡检';
            if ($v['from_type'] == 1 && $v['rtype'] == 1) {
                $v['patrol_type_str'] = '智能巡检';
            } else if ($v['from_type'] == 1 && $v['rtype'] == 2) {
                $v['patrol_type_str'] = 'AI识别巡检';
            }
            $v['recognition_type_str'] = $v['robot_recognition_type'] ? $v['robot_recognition_type'] : '';
            if ($v['robot_recognition_type'] && isset($visionLogTyppeInfo[$v['robot_recognition_type']])) {
                $v['recognition_type_str'] = $visionLogTyppeInfo[$v['robot_recognition_type']];
            }
            $qrcode = D('Wisdom_qrcode')->where(array('id' => $v['qrcode_id']))->find();
            if ($qrcode) {
                $v['qrcode_name'] = $qrcode['name'];
            } else {
                $v['qrcode_name'] = '';
            }
            $v['name'] = '';
            $v['phone'] = '';
            if ($v['from_type'] == 1 && !empty($v['robot_code'])) {
                $robot_device = $third_robot_deviceDb->where(array('robot_code' => $v['robot_code']))->field('village_id,name,robot_code')->find();
                if ($robot_device) {
                    $v['name'] = $robot_device['name'];
                }
            } elseif ($v['uid'] > 0) {
                $worker = D('House_worker')->where(array('wid' => $v['uid']))->find();
                if ($worker) {
                    $v['name'] = $worker['name'];
                    $v['phone'] = $worker['phone'];
                }
            }
        }
        $visionLogTyppeArr = array(
            ['type_key' => 'doorStatus', 'type_name' => '大门检测'],
            ['type_key' => 'vehicle', 'type_name' => '车辆乱停放'],
            ['type_key' => 'garbageCan', 'type_name' => '垃圾桶满溢'],
            ['type_key' => 'trash', 'type_name' => '绿化带垃圾'],
            ['type_key' => 'climb', 'type_name' => '翻越围栏'],
            ['type_key' => 'crowd', 'type_name' => '人员聚集'],
            ['type_key' => 'countVehicle', 'type_name' => '车辆堵塞'],
            ['type_key' => 'carPlate', 'type_name' => '车位识别'],
            ['type_key' => 'lawnTrample', 'type_name' => '践踏草坪'],
            ['type_key' => 'cvMeter', 'type_name' => '巡检仪表识别'],
            ['type_key' => 'countCan', 'type_name' => '垃圾桶数量识别'],
            ['type_key' => 'trashbin', 'type_name' => '垃圾桶盖'],
            ['type_key' => 'bottle', 'type_name' => '地面垃圾'],
            ['type_key' => 'occupyEA', 'type_name' => '应急通道占用'],
        );
        $this->assign('visionLogTyppeArr', $visionLogTyppeArr);
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function qrcode_record_index()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $is_property_robot_jsdhwy = D('Config_customization')->getPropertyRobotJsdhwy();
        $sqAeondeLightJiangsuCustomized_judge = D('Config_customization')->getSqAeondeLightJiangsuCustomized();
        if ($_GET['type'] == 1) {
            $property_endtime_start = $_GET['start_time'];
            $property_endtime_end = $_GET['end_time'];
            $property_endtime_start = strtotime($property_endtime_start);
            $property_endtime_end = strtotime($property_endtime_end);
            $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
            $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 
            $where = array();
            switch ($view_type) {
                case 1:
                    $where['view_time'] = array('gt', 1);
                    break;
                case 2:
                    $where['view_time'] = array('lt', 1);
                    break;
                case 3:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('gt', 0);
                    break;
                case 4:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('lt', 1);
                    break;
                default:
                    break;
            }
            if ($patrol_type == 1) {
                $where['from_type'] = 0;
            } else if ($patrol_type == 2) {
                $where['from_type'] = 1;
                $where['rtype'] = 1;
            } else if ($patrol_type == 3) {
                $where['from_type'] = 1;
                $where['rtype'] = 2;
            }
            $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
            if (!empty($recognition_type)) {
                $where['robot_recognition_type'] = $recognition_type;
            }
            $where['cate_id'] = $_GET['cate_id'];
            $where['village_id'] = $this->village_id;
            $where['_string'] .= " add_time between '" . $property_endtime_start . "' and '" . $property_endtime_end . "'";
            $user_list = M('Wisdom_qrcode_record_log')->where($where)->select();

            if (count($user_list) <= 0) {
                $this->error('无数据导出！');
            }

            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

            $title = '123456';
            $test = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $objExcel = new PHPExcel();
            $objProps = $objExcel->getProperties();
            // 设置文档基本属性
            $objProps->setCreator($title);
            $objProps->setTitle($title);
            $objProps->setSubject($title);
            $objProps->setDescription($title);

            $length = ceil(count($user_list) / 1000);

            for ($i = 0; $i < intval($length); $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);

                $objExcel->getActiveSheet()->setTitle('智慧二维码记录');
                $objActSheet = $objExcel->getActiveSheet();

                $objActSheet->setCellValue('A1', '二维码类别');
                $objActSheet->setCellValue('B1', '二维码名称');
                $objActSheet->setCellValue('C1', '负责人姓名');
                $objActSheet->setCellValue('D1', '负责人联系方式');
                $objActSheet->setCellValue('E1', '记录时间');

                //                if (!empty($user_list)) {
                //                    $index = 2;
                //
                //
                //                    foreach ($user_list as $value) {
                //                        $objActSheet->setCellValueExplicit('A' . $index, $value['cate_id']);
                //                        $objActSheet->setCellValueExplicit('B' . $index, $value['qrcode_id']);
                //                        $objActSheet->setCellValueExplicit('C' . $index, $value['uid']);
                //                        $index++;
                //                    }
                //                }
                sleep(2);
                //输出
                $objWriter = new PHPExcel_Writer_Excel5($objExcel);
                ob_end_clean();
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                header("Content-Type:application/force-download");
                header("Content-Type:application/vnd.ms-execl");
                header("Content-Type:application/octet-stream");
                header("Content-Type:application/download");
                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
        } else {
            $where['village_id'] = $this->village_id;
            $add_time_start = $_GET['begin_time'];
            $add_time_end = $_GET['end_time'];
            $this->assign('begin_time', $add_time_start);
            $this->assign('end_time', $add_time_end);

            if ($add_time_start && $add_time_end) {

                $start_time = strtotime($add_time_start . '00:00:00');
                $end_time = strtotime($add_time_end . '23:59:59');

                $where['add_time'] = array('between', array($start_time, $end_time));
            } else if ($add_time_start) {
                $add_time_start = strtotime($add_time_start);
                $where['add_time'] = array('egt', $add_time_start);
            } else if ($add_time_end) {
                $add_time_end = strtotime($add_time_end . '23:59:59');
                $where['add_time'] = array('lt', $add_time_end);
            }

            if ($find_type = $_GET['find_type'] + 0) {
                switch ($find_type) {
                    case 1: //二维码名称
                        //$where['usernum'] = $_GET['find_value'];
                        $qrcode['name'] = $_GET['find_value'];
                        $qrcode_ids = [];
                        $qrcode_list = M('wisdom_qrcode')->where($qrcode)->select();
                        foreach ($qrcode_list as $k => $v) {
                            $qrcode_ids[] = $v['id'];
                        }
                        $where['qrcode_id'] = array('in', $qrcode_ids);
                        break;
                    case 2: //二维码类别
                        //$where['phone'] = $_GET['find_value'];
                        $qrcode['cate_name'] = $_GET['find_value'];
                        $cate_ids = [];
                        $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->select();
                        foreach ($qrcode_cate as $k => $v) {
                            $cate_ids[] = $v['id'];
                        }
                        $where['cate_id'] = array('in', $cate_ids);
                        break;
                    case 3: //负责人姓名
                        //$where['address'] = $_GET['find_value'];
                        $worker['name'] = $_GET['find_value'];
                        $worker['village_id'] = $this->village_id;
                        $worker = D('House_worker')->where($worker)->select();
                        foreach ($worker as $k => $v) {
                            $worker_ids[] = $v['wid'];
                        }
                        $where['uid'] = array('in', $worker_ids);
                        break;
                    case 4: //负责人联系方式
                        //$where['address'] = $_GET['find_value'];
                        $worker['phone'] = $_GET['find_value'];
                        $worker['village_id'] = $this->village_id;
                        $worker = D('House_worker')->where($worker)->select();

                        foreach ($worker as $k => $v) {
                            $worker_ids[] = $v['wid'];
                        }
                        $where['uid'] = array('in', $worker_ids);
                        break;

                    default:
                        break;
                }
            }
            $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
            $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 
            switch ($view_type) {
                case 1:
                    $where['view_time'] = array('gt', 1);
                    break;
                case 2:
                    $where['view_time'] = array('lt', 1);
                    break;
                case 3:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('gt', 0);
                    break;
                case 4:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('lt', 1);
                    break;
                default:
                    break;
            }
            if ($patrol_type == 1) {
                $where['from_type'] = 0;
            } else if ($patrol_type == 2) {
                $where['from_type'] = 1;
                $where['rtype'] = 1;
            } else if ($patrol_type == 3) {
                $where['from_type'] = 1;
                $where['rtype'] = 2;
            }
            $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
            if (!empty($recognition_type)) {
                $where['robot_recognition_type'] = $recognition_type;
            }
            $count_user = D('Wisdom_qrcode_record_log')->where($where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, 15, 'page');
            $dataList = M('Wisdom_qrcode_record_log')->where($where)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
            $third_robot_deviceDb = D('Third_robot_device');
            foreach ($dataList as $k => &$v) {
                $cate = D('Wisdom_qrcode_cate')->where(array('id' => $v['cate_id']))->find();
                if ($cate) {
                    $v['cate_name'] = $cate['cate_name'];
                } else {
                    $v['cate_name'] = '';
                }
                $v['patrol_type_str'] = '系统巡检';
                if ($v['from_type'] == 1 && $v['rtype'] == 1) {
                    $v['patrol_type_str'] = '智能巡检';
                } else if ($v['from_type'] == 1 && $v['rtype'] == 2) {
                    $v['patrol_type_str'] = 'AI识别巡检';
                }
                $qrcode = D('Wisdom_qrcode')->where(array('id' => $v['qrcode_id']))->find();
                if ($qrcode) {
                    $v['qrcode_name'] = $qrcode['name'];
                } else {
                    $v['qrcode_name'] = '';
                }
                $v['name'] = '';
                $v['phone'] = '';
                if ($v['from_type'] == 1 && !empty($v['robot_code'])) {
                    $robot_device = $third_robot_deviceDb->where(array('robot_code' => $v['robot_code']))->field('village_id,name,robot_code')->find();
                    if ($robot_device) {
                        $v['name'] = $robot_device['name'];
                    }
                } elseif ($v['uid'] > 0) {
                    $worker = D('House_worker')->where(array('wid' => $v['uid']))->find();
                    if ($worker) {
                        $v['name'] = $worker['name'];
                        $v['phone'] = $worker['phone'];
                    }
                }
            }
            $qrcodeRecordExportDetails = D('Config_customization')->getQrcodeRecordExportDetails();
            if ($sqAeondeLightJiangsuCustomized_judge) {
                $qrcodeRecordExportDetails = $sqAeondeLightJiangsuCustomized_judge;
            }
            $this->assign('qrcodeRecordExportDetails', $qrcodeRecordExportDetails);
            $this->assign('is_property_robot_jsdhwy', $is_property_robot_jsdhwy);
            $this->assign('dataList', $dataList);
            $this->assign('pagebar', $p->show());
            $this->display();
        }
    }

    public function qrcode_record_info()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = $_GET['id'];
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $id, 'village_id' => $this->village_id))->find();
        if ($record_log['entication_field']) {
            $entication_field = unserialize($record_log['entication_field']);
            $record_log['entication_field'] = $entication_field['test'];
            foreach ($record_log['entication_field'] as $k1 => &$v1) {

                if ($v1['record_cate_field_config']) {
                    foreach ($v1['record_cate_field_config'] as $kk => &$vv) {
                        if ($vv['type'] == 2) {
                            foreach ($vv['value'] as $k3 => $v3) {
                                if (!empty($v3) && strpos($v3, 'http:') === false && strpos($v3, 'https:') === false) {
                                    $vv['image_value'][] = replace_file_domain($v3);
                                } elseif (!empty($v3)) {
                                    $vv['image_value'][] = $v3;
                                }
                            }
                            $vv['value'] = '';
                        }
                    }
                }
            }
        } else {
            $record_log['entication_field'] = [];
        }
        $robot_record = array();
        if (isset($record_log['robot_record']) && !empty($record_log['robot_record'])) {
            $robot_record = json_decode($record_log['robot_record'], 1);
            if ($robot_record) {
                $robot_record['start_time_str'] = $robot_record['start_time'] > 100 ? date('Y-m-d H:i:s', $robot_record['start_time']) : '';
                $robot_record['end_time_str'] = $robot_record['end_time'] > 100 ? date('Y-m-d H:i:s', $robot_record['end_time']) : '';
            }
        }
        M('Wisdom_qrcode_record_log')->where(array('id' => $id))->data(['view_time' => time()])->save();
        $this->assign('robot_record', $robot_record);
        $this->assign('record_log', $record_log);

        $this->display('qrcode_record_info_test');
    }

    public function handle_record_log_pic($log_id = 0, $surl = '', $img_key = '', $opt = 'add')
    {
        if (empty($img_key) || empty($surl)) {
            return false;
        }
        $surl_path_arr = explode('upload/', $surl);
        $surl_path = $surl_path_arr['1'];
        $img_key_arr = explode('_', $img_key);
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $log_id, 'village_id' => $this->village_id))->find();
        if ($record_log && $record_log['entication_field']) {
            $entication_field = unserialize($record_log['entication_field']);
            $record_log['entication_field'] = $entication_field['test'];
            $is_del_handle = false;
            $is_add_handle = false;
            foreach ($record_log['entication_field'] as $k1 => $v1) {
                if ($is_del_handle || $is_add_handle) {
                    break;
                }
                if ($k1 != $img_key_arr['0']) {
                    continue;
                }
                if (empty($v1['record_cate_field_config'])) {
                    continue;
                }
                foreach ($v1['record_cate_field_config'] as $kk => $vv) {
                    if ($is_del_handle || $is_add_handle) {
                        break;
                    }
                    if ($kk != $img_key_arr['1'] || $vv['type'] != 2) {
                        continue;
                    }
                    if ($opt == 'add') {
                        if (!isset($entication_field['test'][$k1]['record_cate_field_config'][$kk]['value']) || !is_array($entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'])) {
                            $entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'] = array();
                        }
                        $entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'][] = $surl;
                        $is_add_handle = true;
                    } elseif ($opt == 'del') {
                        foreach ($vv['value'] as $k3 => $v3) {
                            if (strpos($v3, $surl_path)) {
                                unset($vv['value'][$k3]);
                                unset($entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'][$k3]);
                                $is_del_handle = true;
                                break;
                            }
                        }
                    }
                }
            }
            if ($is_del_handle && $opt == 'del') {
                $saveArr = array('entication_field' => serialize($entication_field));
                M('Wisdom_qrcode_record_log')->where(array('id' => $log_id, 'village_id' => $this->village_id))->data($saveArr)->save();
                return true;
            }
            if ($is_add_handle && $opt == 'add') {
                $saveArr = array('entication_field' => serialize($entication_field));
                M('Wisdom_qrcode_record_log')->where(array('id' => $log_id, 'village_id' => $this->village_id))->data($saveArr)->save();
                return true;
            }
        }
        return false;
    }

    public function ajax_add_record_pic()
    {
        $record_log_id = $_POST['record_log_id'];
        $record_log_id = $record_log_id ? intval($record_log_id) : 0;
        $surl = $_POST['surl'];
        $surl = $surl ? trim($surl) : '';
        $surl = $surl ? urldecode($surl) : '';
        $img_key = $_POST['img_key'];
        $img_key = $img_key ? trim($img_key) : '';
        $ret = $this->handle_record_log_pic($record_log_id, $surl, $img_key, 'add');
        if ($ret && !empty($surl)) {
            echo json_encode(array('err_code' => 0, 'msg' => '图片添加成功！'), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('err_code' => 1, 'msg' => '图片添加失败了！'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function ajax_del_record_pic()
    {
        $record_log_id = $_POST['record_log_id'];
        $record_log_id = $record_log_id ? intval($record_log_id) : 0;
        $surl = $_POST['surl'];
        $surl = $surl ? trim($surl) : '';
        $surl = $surl ? urldecode($surl) : '';
        $img_key = $_POST['img_key'];
        $img_key = $img_key ? trim($img_key) : '';
        $ret = $this->handle_record_log_pic($record_log_id, $surl, $img_key, 'del');
        if ($ret && !empty($surl)) {
            echo json_encode(array('err_code' => 0, 'msg' => '图片删除成功！'), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('err_code' => 1, 'msg' => '图片删除失败了！'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function qrecord_to_work_order()
    {
        $log_id = $_GET['log_id'];
        $log_id = intval($log_id);
        if ($log_id < 1) {
            $this->error('记录日志不存在！');
        }
        $where = array();
        $where['village_id'] = $this->village_id;
        $where['status'] = 1;
        $where['parent_id'] = 0;
        $field = 'cate_name,id';
        $workcates = D('House_new_repair_cate')->where($where)->field($field)->order('id desc')->select();
        $this->assign('workcates', $workcates);
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $log_id))->find();
        $robot_record = array();
        if (isset($record_log['robot_record']) && !empty($record_log['robot_record'])) {
            $robot_record = json_decode($record_log['robot_record'], 1);
            if ($robot_record) {
                $robot_record['start_time_str'] = $robot_record['start_time'] > 100 ? date('Y-m-d H:i:s') : '';
                $robot_record['end_time_str'] = $robot_record['end_time'] > 100 ? date('Y-m-d H:i:s') : '';
            }
        }
        $this->assign('robot_record', $robot_record);
        $this->assign('record_log', $record_log);
        $this->display();
    }

    public function send_ai_message_set()
    {
        $where = array('village_id' => $this->village_id, 'status' => 1, 'is_del' => 0);
        $house_worker = M('House_worker')->where($where)->field('wid,name,village_id,nickname,phone,openid')->select();
        $this->assign('house_worker', $house_worker);
        $this->display();
    }

    public function get_work_order_cate()
    {
        $f_cate_id = $_GET['f_cate_id'];
        $f_cate_id = intval($f_cate_id);
        $where = array();
        $where['village_id'] = $this->village_id;
        $where['status'] = 1;
        $where['parent_id'] = $f_cate_id;
        $field = 'cate_name,id';
        $workcates = D('House_new_repair_cate')->where($where)->field($field)->order('id desc')->select();
        echo json_encode(array('workcates' => $workcates), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public function add_work_order()
    {
        $f_cate_id = $_POST['f_cate_id'];
        $f_cate_id = intval($f_cate_id);
        $cate_id = $_POST['cate_id'];
        $cate_id = intval($cate_id);
        $log_id = $_POST['log_id'];
        $log_id = intval($log_id);
        if ($f_cate_id < 1) {
            echo json_encode(array('err_code' => 1, 'msg' => '请选择工单类目！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        if ($cate_id < 1) {
            echo json_encode(array('err_code' => 1, 'msg' => '请选择工单分类！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        if ($log_id < 1) {
            echo json_encode(array('err_code' => 1, 'msg' => '二维记录ID错误！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $log_id))->find();
        if ($record_log && isset($record_log['robot_record']) && !empty($record_log['robot_record'])) {
            $params = array();
            $params['f_cate_id'] = $f_cate_id;
            $params['cate_id'] = $cate_id;
            $params['log_id'] = $log_id;
            $params['village_id'] = $this->village_id;
            $postData = array('params' => $params);
            $returnres = invoke_v20_service('\app\community\model\service\RepairCateService/addWisdomQrcodeToWorksOrder', $postData);
            fdump_api(['postData' => $postData, 'res' => $returnres], '00addWisdomQrcodeToWorksOrder', 1);
            if ($returnres && isset($returnres['data']) && isset($returnres['data']['retval'])) {
                $tmpret = $returnres['data']['retval'];
                if ($tmpret['err_code'] == 1) {
                    $msg = $tmpret['msg'] ? $tmpret['msg'] : '生成工单失败了！';
                    echo json_encode(array('err_code' => 1, 'msg' => $msg), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit();
                } else {
                    echo json_encode(array('err_code' => 0, 'msg' => '生成工单成功！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit();
                }
            } else {
                echo json_encode(array('err_code' => 1, 'msg' => '生成工单失败了！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit();
            }
        } else {
            echo json_encode(array('err_code' => 1, 'msg' => '二维记录不存在！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
    }

    public function ajax_user_info()
    {
        $uid = $_POST['uid'];
        if (!$uid) {
            echo json_encode(array('code' => 1, 'msg' => '用户不存在!'));
            die;
        }
        $user = D('House_worker')->where(array('wid' => $uid))->find();
        if ($user) {
            echo json_encode(array('code' => 2, 'msg' => $user['phone']));
            die;
        } else {
            echo json_encode(array('code' => 1, 'msg' => '用户不存在!'));
            die;
        }
    }


    public function download_record()
    {
        if (IS_POST) {
        } else {
            $wisdom_qrcode_cate = M('Wisdom_qrcode_cate')->where(array('village_id' => $this->village_id))->select();
            $this->assign('wisdom_qrcode_cate', $wisdom_qrcode_cate);
            $this->display();
        }
    }

    public function download_person_ten_record()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $where = array('village_id' => $this->village_id, 'uid' => array('gt', 0));
        $add_time_start = isset($_GET['begin_time']) && $_GET['begin_time'] ? trim($_GET['begin_time']) : '';
        $add_time_end = isset($_GET['end_time']) && $_GET['end_time'] ? trim($_GET['end_time']) : '';
        if ($add_time_start && $add_time_end) {
            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('lt', $add_time_end);
        }
        $find_type = isset($_GET['find_type']) && $_GET['find_type'] > 0 ? $_GET['find_type'] : 0;
        $find_value = isset($_GET['find_value']) && $_GET['find_value'] ? trim($_GET['find_value']) : '';
        if ($find_type > 0 && !empty($find_value)) {
            switch ($find_type) {
                case 1: //二维码名称
                    $qrcode = array();
                    $qrcode['name'] = $find_value;
                    $qrcode_ids = [];
                    $qrcode_list = M('wisdom_qrcode')->where($qrcode)->field('id')->select();
                    foreach ($qrcode_list as $k => $v) {
                        $qrcode_ids[] = $v['id'];
                    }
                    unset($qrcode_list);
                    $where['qrcode_id'] = array('in', $qrcode_ids);
                    break;
                case 2: //二维码类别
                    $qrcode = array();
                    $qrcode['cate_name'] = $find_value;
                    $cate_ids = [];
                    $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->field('id')->select();
                    foreach ($qrcode_cate as $k => $v) {
                        $cate_ids[] = $v['id'];
                    }
                    unset($qrcode_cate);
                    $where['cate_id'] = array('in', $cate_ids);
                    break;
                case 3: //负责人姓名
                    $workerwhere = array();
                    $workerwhere['name'] = $find_value;
                    $workerwhere['village_id'] = $this->village_id;
                    $worker = D('House_worker')->where($workerwhere)->field('wid')->select();
                    foreach ($worker as $k => $v) {
                        $worker_ids[] = $v['wid'];
                    }
                    unset($worker);
                    $where['uid'] = array('in', $worker_ids);
                    break;
                case 4: //负责人联系方式
                    $workerwhere = array();
                    $workerwhere['phone'] = $find_value;
                    $workerwhere['village_id'] = $this->village_id;
                    $worker = D('House_worker')->where($workerwhere)->field('wid')->select();
                    foreach ($worker as $k => $v) {
                        $worker_ids[] = $v['wid'];
                    }
                    unset($worker);
                    $where['uid'] = array('in', $worker_ids);
                    break;

                default:
                    break;
            }
        }
        $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
        $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 
        switch ($view_type) {
            case 1:
                $where['view_time'] = array('gt', 1);
                break;
            case 2:
                $where['view_time'] = array('lt', 1);
                break;
            case 3:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('gt', 0);
                break;
            case 4:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('lt', 1);
                break;
            default:
                break;
        }
        if ($patrol_type == 1) {
            $where['from_type'] = 0;
        } else if ($patrol_type == 2) {
            $where['from_type'] = 1;
            $where['rtype'] = 1;
        } else if ($patrol_type == 3) {
            $where['from_type'] = 1;
            $where['rtype'] = 2;
        }

        $user_list = M('Wisdom_qrcode_record_log')->where($where)->field('id,cate_id,qrcode_id,uid,village_id,add_time,entication_field')->order('id desc')->select();
        if (empty($user_list)) {
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = $this->village['village_name'] . '导出巡检明细表';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setTitle('智慧二维码记录');
        $objActSheet = $objExcel->getActiveSheet();

        $objActSheet->setCellValue('A1', '二维码类别');
        $objActSheet->setCellValue('B1', '二维码名称');
        $objActSheet->setCellValue('C1', '负责人姓名');
        $objActSheet->setCellValue('D1', '负责人联系方式');
        $objActSheet->setCellValue('E1', '记录时间');
        $objActSheet->setCellValue('F1', '检查项目1');
        $objActSheet->setCellValue('G1', '检查项目2');
        $objActSheet->setCellValue('H1', '检查项目3');
        $objActSheet->setCellValue('I1', '检查项目4');
        $objActSheet->setCellValue('J1', '检查项目5');
        $objActSheet->setCellValue('K1', '检查项目6');
        $objActSheet->setCellValue('L1', '检查项目7');
        $objActSheet->setCellValue('M1', '检查项目8');
        $objActSheet->setCellValue('N1', '检查项目9');
        $objActSheet->setCellValue('O1', '检查项目10');
        $objActSheet->setCellValue('P1', '检查项目11');
        $objActSheet->setCellValue('Q1', '检查项目12');
        $objActSheet->setCellValue('R1', '检查项目13');
        $objActSheet->setCellValue('S1', '检查项目14');
        $objActSheet->setCellValue('T1', '检查项目15');
        $objActSheet->setCellValue('U1', '检查项目16');
        $objActSheet->setCellValue('V1', '检查项目17');
        $objActSheet->setCellValue('W1', '检查项目18');
        $cell_list = range('A', 'E');
        foreach ($cell_list as $cell) {
            $objActSheet->getColumnDimension($cell)->setWidth(30);
        }
        $cell_list = range('F', 'X');
        foreach ($cell_list as $cell) {
            $objActSheet->getColumnDimension($cell)->setWidth(80);
        }
        $objActSheet->getRowDimension(1)->setRowHeight(25);
        $objActSheet->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objActSheet->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $wisdom_qrcode_cateDb = M('Wisdom_qrcode_cate');
        $wisdom_qrcode_record_cateDb = M('Wisdom_qrcode_record_cate');
        $index = 2;
        $otherField = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X');
        if ($user_list && is_array($user_list)) {
            foreach ($user_list as $value) {
                $objActSheet->getRowDimension($index)->setRowHeight(100);
                $qrcode_cate = $wisdom_qrcode_cateDb->where(array('id' => $value['cate_id'], 'village_id' => $value['village_id']))->field('id,cate_name')->find();
                if ($qrcode_cate) {
                    $objActSheet->setCellValueExplicit('A' . $index, $qrcode_cate['cate_name']);
                } else {
                    $objActSheet->setCellValueExplicit('A' . $index, '');
                }
                $qrcode = M('Wisdom_qrcode')->where(array('id' => $value['qrcode_id']))->field('id,name')->find();
                if ($qrcode) {
                    $objActSheet->setCellValueExplicit('B' . $index, $qrcode['name']);
                } else {
                    $objActSheet->setCellValueExplicit('B' . $index, '');
                }
                $house_worker = D('House_worker')->where(array('wid' => $value['uid']))->field('wid,name,phone')->find();
                if ($house_worker) {
                    $objActSheet->setCellValueExplicit('C' . $index, $house_worker['name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $house_worker['phone']);
                } else {
                    $objActSheet->setCellValueExplicit('C' . $index, '');
                    $objActSheet->setCellValueExplicit('D' . $index, '');
                }
                //$tenwhere=array('qrcode_id'=>$value['qrcode_id'],'cate_id'=>$value['cate_id'],'village_id' => $this->village_id,'uid' => $value['uid']);
                //$record_ten_list = M('Wisdom_qrcode_record_log')->where($tenwhere)->field('id,cate_id,qrcode_id,uid,village_id,add_time,entication_field')->order('id desc')->limit('0,30')->select();
                $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['add_time']));


                if ($value['entication_field']) {
                    $entication_field = unserialize($value['entication_field']);
                    if ($entication_field) {
                        foreach ($entication_field as $k3 => $fdv) {
                            if ($fdv) {
                                $mindex = 0;
                                foreach ($fdv as $k4 => $v4) {
                                    foreach ($v4['record_cate_field_config'] as $k5 => $v5) {
                                        $msg_test = '';
                                        $msg_test = $v4['record_name'] . "：\n";
                                        $mc = $k5 + 1;
                                        $msg_test .= $v5['title'] . '：';
                                        if ($v5['type'] == 2 && is_array($v5['value'])) {
                                            $msg_test .= "\n";
                                            foreach ($v5['value'] as $img) {
                                                $logo = replace_file_domain($img);
                                                $msg_test .= $logo . "\n";
                                            }
                                        } else {
                                            $msg_test .= $v5['value'] . "\n";
                                        }
                                        $objActSheet->getStyle($otherField[$mindex] . $index)->getAlignment()->setWrapText(true);
                                        $objActSheet->setCellValueExplicit($otherField[$mindex] . $index, $msg_test);
                                        $mindex++;
                                        if ($mindex >= 10) {
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    /*
                        if ($msg_test) {
                            $objActSheet->getStyle($otherField[$mindex] . $index)->getAlignment()->setWrapText(true);
                            $objActSheet->setCellValueExplicit($otherField[$mindex] . $index, $msg_test);
                            $mindex++;
                        }
                        if ($mindex >= 10) {
                            break;
                        }
                        */
                }

                $index++;
            }
            sleep(2);
        }

        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename=' . urlencode($title) . '_' . date("Y-m-d h:i:sa", time()) . '.xls');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function download_record_list()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where = array();

//        if ($_GET['find_type'] != 2) {
//            $this->error('请选择二维码类别进行导出条件！');
//        }

        //        if(empty($_GET['begin_time']) || empty($_GET['end_time'])){
        //            $this->error('请选择请选择开始时间和结束时间！');
        //        }
        $where['village_id'] = $this->village_id;
        if (!empty($_GET['find_value'])) {
            $where['cate_name'] = trim($_GET['find_value']);
        }
        $qrcode_cate = M('Wisdom_qrcode_cate')->where($where)->select();

        if (empty($qrcode_cate)) {
            $this->error('无数据导出！');
        }
        $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
        $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 

        switch ($view_type) {
            case 1:
                $where['view_time'] = array('gt', 1);
                break;
            case 2:
                $where['view_time'] = array('lt', 1);
                break;
            case 3:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('gt', 0);
                break;
            case 4:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('lt', 1);
                break;
            default:
                break;
        }
        if ($patrol_type == 1) {
            $where['from_type'] = 0;
        } else if ($patrol_type == 2) {
            $where['from_type'] = 1;
            $where['rtype'] = 1;
        } else if ($patrol_type == 3) {
            $where['from_type'] = 1;
            $where['rtype'] = 2;
        }
        $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
        if (!empty($recognition_type)) {
            $where['robot_recognition_type'] = $recognition_type;
        }
        $qrcode_cate_list = array_column($qrcode_cate, 'id');
        $where['cate_id'] = ['in', $qrcode_cate_list];
        $where['village_id'] = $this->village_id;
        $add_time_start = $_GET['begin_time'];
        $add_time_end = $_GET['end_time'];
        if ($add_time_start && $add_time_end) {

            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');

            $where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('lt', $add_time_end);
        }
        $user_list = M('Wisdom_qrcode_record_log')->where($where)->order('id desc')->select();
        if (count($user_list) <= 0) {
            $this->error('无数据导出！');
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = $this->village['village_name'] . '智慧二维码记录';
        $test = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($user_list) / 1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('智慧二维码记录');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '二维码类别');
            $objActSheet->setCellValue('B1', '二维码名称');
            $objActSheet->setCellValue('C1', '负责人姓名');
            $objActSheet->setCellValue('D1', '负责人联系方式');
            $objActSheet->setCellValue('E1', '记录时间');
            foreach ($user_list as $value1) {
                if ($value1['entication_field']) {
                    $entication_field = unserialize($value1['entication_field']);
                    foreach ($entication_field as $k3 => $v3) {
                        if ($v3) {
                            foreach ($v3 as $k4 => $v4) {
                                foreach ($v4['record_cate_field_config'] as $k5 => $v5) {
                                    $qrcode_record_cate = M('Wisdom_qrcode_record_cate')->where(array('cate_id' => $qrcode_cate['id']))->select();
                                    if ($qrcode_record_cate) {
                                        $qrcode_record_cate_data = array();
                                        $qrcode_record_cate_name_arr = array();
                                        foreach ($qrcode_record_cate as $kk => $vv) {
                                            $qrcode_record_cate_data[] = $vv['id'];
                                            $qrcode_record_cate_name_arr[$vv['id']] = $vv['record_name'];
                                        }
                                        $field_cate_data['record_cate_id'] = array('in', $qrcode_record_cate_data);
                                        $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where($field_cate_data)->select();
                                        foreach ($qrcode_record_cate_field_config as $k8 => $v8) {
                                            if (($v8['key'] == $v5['key']) && ($v8['title'] == $v5['title'])) {
                                                $xtitle = $v8['title'];
                                                if (isset($qrcode_record_cate_name_arr[$v8['record_cate_id']])) {
                                                    $xtitle = $qrcode_record_cate_name_arr[$v8['record_cate_id']] . "\r\n" . $v8['title'];
                                                }
                                                $objActSheet->setCellValue($test[$k8] . '1', $xtitle);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // 获取A1单元格的样式
            $styleArray = array(
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap' => true  // 启用自动换行
                ),
            );
            // 应用样式到A1单元格
            $objExcel->getActiveSheet()->getStyle('A1:AZ1')->applyFromArray($styleArray);
            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A', 'Z');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }
                foreach ($test as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }
                $qrcodeImageArr = [];
                foreach ($user_list as $value) {
                    $qrcode_cate = M('Wisdom_qrcode_cate')->where(array('id' => $value['cate_id'], 'village_id' => $value['village_id']))->find();
                    if ($qrcode_cate) {
                        $objActSheet->setCellValueExplicit('A' . $index, $qrcode_cate['cate_name']);
                    } else {
                        $objActSheet->setCellValueExplicit('A' . $index, '');
                    }
                    $qrcode = M('Wisdom_qrcode')->where(array('id' => $value['qrcode_id']))->find();
                    if ($qrcode) {
                        $objActSheet->setCellValueExplicit('B' . $index, $qrcode['name']);
                    } else {
                        $objActSheet->setCellValueExplicit('B' . $index, '');
                    }
                    $house_worker = D('House_worker')->where(array('wid' => $value['uid']))->find();
                    if ($house_worker) {
                        $objActSheet->setCellValueExplicit('C' . $index, $house_worker['name']);
                        $objActSheet->setCellValueExplicit('D' . $index, $house_worker['phone']);
                    } else {
                        $objActSheet->setCellValueExplicit('C' . $index, '');
                        $objActSheet->setCellValueExplicit('D' . $index, '');
                    }
                    $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['add_time']));
                    if ($value['entication_field']) {
                        $entication_field = unserialize($value['entication_field']);
                        foreach ($entication_field as $k3 => $v3) {
                            if ($v3) {
                                foreach ($v3 as $k4 => $v4) {
                                    foreach ($v4['record_cate_field_config'] as $k5 => $v5) {
                                        $qrcode_record_cate = M('Wisdom_qrcode_record_cate')->where(array('cate_id' => $qrcode_cate['id']))->select();
                                        if ($qrcode_record_cate) {
                                            $qrcode_record_cate_data = array();
                                            foreach ($qrcode_record_cate as $kk => $vv) {
                                                $qrcode_record_cate_data[] = $vv['id'];
                                            }
                                            $field_cate_data['record_cate_id'] = array('in', $qrcode_record_cate_data);
                                            $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where($field_cate_data)->select();
                                            $countConfig = count($qrcode_record_cate_field_config) - 1;

                                            foreach ($qrcode_record_cate_field_config as $k8 => $v8) {
                                                $configJudge = false;
                                                if (($v8['key'] == $v5['key']) && ($v8['title'] == $v5['title'])) {
                                                    $configJudge = true;
                                                }
                                                if (!$configJudge && $countConfig == $k8 && $v8['key'] == $v5['key']) {
                                                    // 如果走到了最后一个分类都没有匹配上 直接匹配关键值 key
                                                    $configJudge = true;
                                                }
                                                if ($configJudge) {
                                                    $objExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(80);
                                                    if ($v5['type'] == 2) {
                                                        if (isset($qrcodeImageArr[$test[$k8] . $index]) && intval($qrcodeImageArr[$test[$k8] . $index]) == 1) {
                                                            continue;
                                                        }
                                                        $objDrawing[$test[$k8]] = new \PHPExcel_Worksheet_Drawing();
                                                        $imgArr = array();
                                                        foreach ($v5['value'] as $ivv) {
                                                            if (!empty($ivv)) {
                                                                $imgArr[] = $ivv;
                                                            }
                                                        }
                                                        if ($imgArr[0]) {
                                                            $logo = replace_file_domain($imgArr[0]);
                                                            //将oss上的图片先下来
                                                            $file_handle = new file_handle();
                                                            if ($file_handle->check_open_oss()) {
                                                                $file_handle->download($logo);
                                                                $logo = $file_handle->get_path($logo);
                                                                $logo_file = './' . ltrim($logo, '/');
                                                            } else {
                                                                $logo = $_SERVER['DOCUMENT_ROOT'] . str_replace($this->config['site_url'], '', $imgArr[0]);
                                                                $logo_file = $logo;
                                                            }
                                                            if (!file_exists($logo_file)) {
                                                                continue;
                                                            }
                                                            $objDrawing[$test[$k8]]->setPath($logo_file);
                                                            // 设置宽度高度
                                                            $objDrawing[$test[$k8]]->setWidth(120); //照片宽度
                                                            $objDrawing[$test[$k8]]->setHeight(100); //照片高度
                                                            /*设置图片要插入的单元格*/
                                                            $objDrawing[$test[$k8]]->setCoordinates($test[$k8] . $index);
                                                            // 图片偏移距离
                                                            $objDrawing[$test[$k8]]->setOffsetX(12);
                                                            $objDrawing[$test[$k8]]->setOffsetY(12);
                                                            $objDrawing[$test[$k8]]->setWorksheet($objExcel->getActiveSheet());
                                                            $qrcodeImageArr[$test[$k8] . $index] = 1;
                                                            //                                                            $objActSheet->setCellValueExplicit($test[$k8].$index, file_domain().$imgArr[0]);
                                                        } else {
                                                            $objActSheet->setCellValueExplicit($test[$k8] . $index, '');
                                                        }
                                                    } else {
                                                        if ($v5['value']) {
                                                            $v5['value'] = trim($v5['value']);
                                                            $v5['value'] = removeEmojiChar($v5['value']);
                                                        }
                                                        $objActSheet->setCellValueExplicit($test[$k8] . $index, $v5['value']);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $index++;
                }
            }

            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');

            exit();
        }
    }

    //查看巡检记录
    public function record_list_detail()
    {
        $cate_id = intval($_GET['cate_id']);
        $name = trim($_GET['name']);
        $begin_time = trim($_GET['begin_time']);
        $end_time = trim($_GET['end_time']);
        $where = [
            'a.village_id' => $this->village_id,
            'a.cate_id' => $cate_id,
            'a.del_time' => 0
        ];
        if ($begin_time && $end_time) {
            $start_time = strtotime($begin_time . ' 00:00:00');
            $end_time = strtotime($end_time . ' 23:59:59');
            if ($start_time > $end_time) {
                $this->error('请重新选择，【开始时间不可大于结束时间】');
            }
            $where['a.add_time'] = array('between', array($start_time, $end_time));

        }
    }

    //巡检定位记录
    public function position_record_list()
    {
        $cate_id = intval($_GET['cate_id']);
        $name = trim($_GET['name']);
        $begin_time = trim($_GET['begin_time']);
        $end_time = trim($_GET['end_time']);
        $where = [
            'a.village_id' => $this->village_id,
            'a.cate_id' => $cate_id,
            'a.del_time' => 0
        ];
        if ($begin_time && $end_time) {
            $start_time = strtotime($begin_time . ' 00:00:00');
            $end_time = strtotime($end_time . ' 23:59:59');
            if ($start_time > $end_time) {
                $this->error('请重新选择，【开始时间不可大于结束时间】');
            }
            $where['a.add_time'] = array('between', array($start_time, $end_time));
        }
        $from_type = 0;
        $whereArr = array('id' => $cate_id, 'village_id' => $this->village_id);
        $qrcode_cate = D('Wisdom_qrcode_cate')->where($whereArr)->field('*')->find();
        if ($qrcode_cate && isset($qrcode_cate['from_type']) && ($qrcode_cate['from_type'] == 1) && !empty($qrcode_cate['task_instance_id'])) {
            $robot_where = [
                'village_id' => $this->village_id,
                'cate_id' => $cate_id,
                'del_time' => 0,
                'from_type' => 1,
                'task_instance_id' => $qrcode_cate['task_instance_id'],
            ];
            if (isset($where['a.add_time'])) {
                $robot_where['add_time'] = $where['a.add_time'];
            }
            $list = D('Wisdom_qrcode_cate')->get_robot_position_record_list($robot_where);
            $third_robot_deviceDb = D('Third_robot_device');
            $robot_device = $third_robot_deviceDb->where(array('robot_code' => $qrcode_cate['robot_code']))->field('village_id,name,robot_code')->find();
            $from_type = 1;
            $this->assign('robot_device', $robot_device);
        } else {
            if (!empty($name)) {
                $where['b.name'] = ['like', '%' . $name . '%'];
            }
            $list = D('Wisdom_qrcode_cate')->get_position_record_list($where);
        }
        $no_line_path = 0;
        $isAiHorse = D('Config_customization')->getIsAihorseCustomized();
        if ($this->village_id == 119 && $isAiHorse) {
            $no_line_path = 1;
        }
        $this->assign('no_line_path', $no_line_path);
        $this->assign('from_type', $from_type);
        $this->assign('dataList', $list['dataList']);
        $this->assign('pagebar', $list['pagebar']);
        $this->display();
    }

    public function inspection_record_detail()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['village_id'] = $this->village_id;
        $count_user = D('Wisdom_qrcode_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList = D('Wisdom_qrcode_cate')
            ->where($where)
            // ->limit($p->firstRow . ',' . $p->listRows)
            ->order('id desc')
            ->select();
        if (!empty($dataList)) {
            $third_robot_deviceDb = D('Third_robot_device');
            foreach ($dataList as $lkk => $lvv) {
                $dataList[$lkk]['robot_name'] = '';
                if ($lvv['from_type'] && $lvv['from_type'] == 1 && !empty($lvv['robot_code'])) {
                    $robot_device = $third_robot_deviceDb->where(array('robot_code' => $lvv['robot_code']))->field('village_id,name,robot_code')->find();
                    if ($robot_device) {
                        $dataList[$lkk]['robot_name'] = $robot_device['name'];
                    }
                }
            }
        }


        //项目id
        $detail_where['village_id'] = $this->village_id;

        // $this->assign('begin_time', $add_time_start);
        // $this->assign('end_time', $add_time_end);

        // $this->assign('qrcode_name', $qrcode_name);
        // $this->assign('qrcode_type', $qrcode_type);


        //开始结束时间
        $add_time_start = $_GET['begin_time'];
        $add_time_end = $_GET['end_time'];
        if ($add_time_start && $add_time_end) {
            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');
            $detail_where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $detail_where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $detail_where['add_time'] = array('lt', $add_time_end);
        }

        //二维码名称
        $qrcode_name = $_GET['qrcode_name'];
        if ($qrcode_name) {
            $qrcode['name'] = $_GET['find_value'];
            $qrcode_ids = [];
            $qrcode_list = M('wisdom_qrcode')->where($qrcode)->select();
            foreach ($qrcode_list as $k => $v) {
                $qrcode_ids[] = $v['id'];
            }
            $detail_where['qrcode_id'] = array('in', $qrcode_ids);
        }

        //二维码类别
        $qrcode_type = $_GET['qrcode_type'];
        if ($qrcode_type) {
            $qrcode['cate_name'] = $_GET['find_value'];
            $cate_ids = [];
            $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->select();
            foreach ($qrcode_cate as $k => $v) {
                $cate_ids[] = $v['id'];
            }
            $detail_where['cate_id'] = array('in', $cate_ids);
        }

        $count_user = D('Wisdom_qrcode_record_log')->where($detail_where)->count();

        import('@.ORG.merchant_page');
        // $p = new Page($count_user, 15, 'page');
        $detailDataList = M('Wisdom_qrcode_record_log')
            ->where($detail_where)
            ->order('add_time desc')
            // ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $third_robot_deviceDb = D('Third_robot_device');

        foreach ($detailDataList as $k => &$v) {
            $cate = D('Wisdom_qrcode_cate')->where(array('id' => $v['cate_id']))->find();
            if ($cate) {
                $v['cate_name'] = $cate['cate_name'];
            } else {
                $v['cate_name'] = '';
            }
            $v['patrol_type_str'] = '系统巡检';
            if ($v['from_type'] == 1 && $v['rtype'] == 1) {
                $v['patrol_type_str'] = '智能巡检';
            } else if ($v['from_type'] == 1 && $v['rtype'] == 2) {
                $v['patrol_type_str'] = 'AI识别巡检';
            }
            $qrcode = D('Wisdom_qrcode')->where(array('id' => $v['qrcode_id']))->find();
            if ($qrcode) {
                $v['qrcode_name'] = $qrcode['name'];
            } else {
                $v['qrcode_name'] = '';
            }
            $v['name'] = '';
            $v['phone'] = '';
            if ($v['from_type'] == 1 && !empty($v['robot_code'])) {
                $robot_device = $third_robot_deviceDb->where(array('robot_code' => $v['robot_code']))->field('village_id,name,robot_code')->find();
                if ($robot_device) {
                    $v['name'] = $robot_device['name'];
                }
            } elseif ($v['uid'] > 0) {
                $worker = D('House_worker')->where(array('wid' => $v['uid']))->find();
                if ($worker) {
                    $v['name'] = $worker['name'];
                    $v['phone'] = $worker['phone'];
                }
            }
        }
        unset($v);

        // $this->assign('detailDataList', $detailDataList);
        // $this->assign('pagebar', $p->show());

        foreach ($dataList as $k_ => &$v_) {
            $v_['detailDataList'] = filterArrayByField($detailDataList, 'cate_name', $v_['cate_name']);
        }
        unset($v_);

        $this->assign('dataList', $dataList);
        // $this->assign('pagebar', $p->show());
        $this->display();


        // echo "<pre>";
        // var_dump( $dataList, $detailDataList );die;
    }

    //地图轨迹
    public function position_map_trail()
    {
        $cate_id = intval($_GET['cate_id']);
        $index_id = intval($_GET['index_id']);
        if (!$cate_id || !$index_id) {
            $this->error('缺少必要参数');
        }
        $list = D('Wisdom_qrcode_cate')->get_position_record($this->village_id, $cate_id, $index_id);
        $this->assign('res', $list);
        $this->display();
    }

    //同步记录内容
    public function qrcode_synchronize_recorded()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作!');
        }

        echo "<pre>";
        $id = $_GET['id'];
        $cate_id = $_POST['cate_id'];
        $cate_name = $_POST['cate_name'];
        if (IS_POST) {
            if (!isset($_POST['cate_id']) || !isset($_POST['cate_name']) || !isset($_POST['village_id'])) {
                $this->error('缺少参数, 请重试!');
            }
            $origin_cate_info = D('Wisdom_qrcode_cate')->where(array('cate_name' => $cate_name, 'village_id' => $_POST['village_id']))->find();
            if (!isset($origin_cate_info['id'])) {
                $this->error('该分类不存在, 请重试!');
            }
            $cate_list = D('Wisdom_qrcode_record_cate')->where(array('cate_id' => $origin_cate_info['id']))->select();
            $cate_record_ids = implode(',', array_column($cate_list, 'id'));
            foreach ($cate_list as &$list) {
                unset($list['id']);
                unset($list['add_time']);
                $list['cate_id'] = $cate_id;
                $list['add_time'] = time();
            }
            unset($list);
            $res_key2 = 0;
            foreach ($cate_list as $k => $v) {
                $res = D('Wisdom_qrcode_record_cate')->where(['cate_id' => $v['cate_id'], 'record_name' => $v['record_name']])->find();
                if (count($res) <= 0) {
                    $result = D('Wisdom_qrcode_record_cate')->data($v)->add();
                    $res_key2++;
                }
            }

            $record_list = D('Wisdom_qrcode_record_cate_field_config')
                ->where('record_cate_id in (' . $cate_record_ids . ')')
                ->select();

            $res_key = 0;
            foreach ($record_list as &$list2) {
                unset($list2['acid']);
                unset($list2['add_time']);
                unset($list2['key']);
                $list2['add_time'] = time();
                $list2['key'] = $this->getPinyinInitials($this->village['village_name'], true) . '_' . $this->getPinyinInitials($list2['title'], true);
                $record_cate_name = D('Wisdom_qrcode_record_cate')
                    ->where(['id' => $list2['record_cate_id']])
                    ->find();
                $record_cate_id = D('Wisdom_qrcode_record_cate')
                    ->where(['record_name' => $record_cate_name['record_name'], 'cate_id' => $cate_id])
                    ->find();
                unset($list2['record_cate_id']);
                if (isset($record_cate_id['id'])) {
                    $list2['record_cate_id'] = $record_cate_id['id'];
                }

                $res = D('Wisdom_qrcode_record_cate_field_config')->where(['key' => $list2['key']])->find();
                if (count($res) <= 0) {
                    $result = D('Wisdom_qrcode_record_cate_field_config')->data($list2)->add();
                    if ($result) {
                        $res_key++;
                    }
                }
            }
            if ($res_key > 0 || $res_key2 > 0) {
                $this->success('同步成功！', U('cate_index'));
            } else {
                $this->error('同步失败！请重试。');
            }
        } else {
            $cate_info = D('Wisdom_qrcode_cate')->where(array('id' => $id))->find();
            if (!$cate_info) {
                $this->error('参数错误');
            }

            $village_list = D('house_village')
                ->where(['status' => 1])
                ->field('village_id,village_name')
                ->select();
            $mapped_list = array_filter(array_map(function ($item) use ($cate_info) {
                $is_contain = D('Wisdom_qrcode_cate')
                    ->where([
                        'village_id' => $item['village_id'],
                        'cate_name' => $cate_info['cate_name']
                    ])
                    ->select();
                if ($item['village_id'] != $this->village_id && count($is_contain) > 0) {
                    return $item;
                } else {
                    return null;
                }
            }, $village_list));
            sort($mapped_list);
            $this->assign('cate_info', $cate_info);
            $this->assign('village_list', $mapped_list);
            $this->display();
        }
    }
    public function get_inspection_detail()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $cate_id = $_POST['cateId'] ? $_POST['cateId'] : '';
        $fulldate = $_POST['fulldate'] ? $_POST['fulldate'] : '';
        $type = $_POST['type'] ? $_POST['type'] : 'day';
        if( empty($cate_id) || empty($fulldate) ){
            $this->error('缺少查询参数, 请重试');
        }
        $where['wqc.village_id'] = $this->village_id;
        $where['wqc.id'] = $cate_id;
        $cateInfo = D('Wisdom_qrcode_cate as wqc')
            ->join( 'pigcms_wisdom_qrcode as wq ON wqc.id = wq.cate_id' )
            ->field("wqc.*,GROUP_CONCAT(wq.id SEPARATOR ',') as qrcode_ids,GROUP_CONCAT(wq.name SEPARATOR ',') as qrcode_names")
            ->where($where)
            ->group('wqc.id')
//            ->buildSql();
            ->select();

        foreach ($cateInfo as $item) {
            $item['qrcode_list'] = array();
            // 拆分聚合的id和name
            $ids = explode(',', $item['qrcode_ids']);
            $names = explode(',', $item['qrcode_names']);
            foreach ($ids as $k => $id) {
                $item['qrcode_list'][] = array(
                    'qrcode_id' => $id,
                    'qrcode_name' => $names[$k] ?? ''
                );
            }
            unset($item['qrcode_ids'], $item['qrcode_names']);
            $finalData = $item;
        }

        $qrcodeRecordLogDB = D('Wisdom_qrcode_record_log as wqr');

        $startTime = null;
        $endTime = null;
        if( $type == 'day' ){
            $startTime = strtotime($fulldate . ' 00:00:00');
            $endTime = strtotime($fulldate . ' 23:59:59');
        }else if( $type == 'week' ){
            $startTime = strtotime($fulldate[0] . ' 00:00:00');
            $endTime = strtotime($fulldate[1] . ' 23:59:59');
        }else if( $type == 'month' ){
            $startTime = strtotime($fulldate.'-01' . ' 00:00:00');
            $endTime = strtotime($this->getLastDayOfMonth($fulldate) . ' 23:59:59');
        }

        $logWhere['wqr.cate_id'] = $finalData['id'];
        $logWhere['wqr.add_time'] = ['between', [$startTime, $endTime]];
        foreach( $finalData['qrcode_list'] as $k => &$val ){
            $logWhere['wqr.qrcode_id'] = $val['qrcode_id'];
            $logInfo = $qrcodeRecordLogDB
                ->join('pigcms_house_worker as hw ON wqr.uid = hw.wid')
                ->field('wqr.*,hw.name,hw.phone')
                ->where($logWhere)
                ->select();
            $parsedData = array_map(function($item) {
                // 1. 反序列化entication_field（处理空值/异常）
                $enticationField = '';
                if (!empty($item['entication_field']) && is_string($item['entication_field'])) {
                    // 修复序列化字符串可能的换行/空格问题（避免反序列化失败）
                    $enticationField = unserialize($item['entication_field']); // @抑制非致命错误
                }

                // 2. 将解析后的数据添加到原数组，保留原字段（也可unset($item['entication_field'])删除原字段）
                $item['entication_field_list'] = $enticationField ?: [];
                return $item;
            }, $logInfo);
            $val['log_list'] = $parsedData;
        }
        unset($val);
        $arr = [
            'code'=> 0,
            'data'=> $finalData,
        ];
        exit(json_encode($arr));
    }
    /**
     * 修复序列化字符串的长度错误，解决unserialize返回false的问题
     * @param string $str 原始序列化字符串
     * @return array|false 解析后的数组
     */
    function safeUnserialize($str) {
        // 步骤1：清理换行/回车/不可见字符
        $str = str_replace(["\r", "\n", "\t"], '', $str);
        // 步骤2：正则匹配所有 s:长度:"值" 格式，重新计算长度
        $str = preg_replace_callback(
            '/s:(\d+):"(.*?)";/s', // s:长度:"值"; 正则（s修饰符匹配换行）
            function($matches) {
                $realLen = strlen($matches[2]); // 计算值的实际字节长度
                return "s:{$realLen}:\"{$matches[2]}\";"; // 替换为正确长度
            },
            $str
        );
        // 步骤3：反序列化（抑制警告，增加容错）
        $result = @unserialize($str);
        return $result;
    }
    public function inspection_statistics_index()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $count_user = D('Wisdom_qrcode_cate')->where(['village_id'=>$this->village_id])->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 15, 'page');

        $type = $_GET['type'] ? $_GET['type'] : 'day' ;
        $startDate = $_GET['startDate'] ? $_GET['startDate'] : '';
        $endDate = $_GET['endDate'] ? $_GET['endDate'] : '';

        $currentHeaderList = $this->getHeaderList($startDate, $endDate, $type);
        $where['village_id'] = $this->village_id;
        //区分任务类型
        switch( $type )
        {
            case 'day': $where['record_type'] = 1;break;
            case 'week': $where['record_type'] = 2;break;
            case 'month': $where['record_type'] = 3;break;
            default : $where['record_type'] = 1;break;
        }

        $qrcodeDb = D('Wisdom_qrcode');
        $qrcodeCateDb = D('Wisdom_qrcode_cate');
        $qrcodeRecordLogDb = D('Wisdom_qrcode_record_log');
        $cateList = $qrcodeCateDb->where($where)->limit($p->firstRow . ','. $p->listRows)->select();
        $cateList = is_array($cateList) ? array_map(function($cateItem) use ($currentHeaderList) {
            // 给每个元素添加date_list键
            $cateItem['date_list'] = $currentHeaderList;
            return $cateItem;
        }, $cateList) : [];
        foreach($cateList as $k => &$v){
            $v['complete_rate'] = 0;
            $currentCateQrcodeList = $qrcodeDb->where(['cate_id' => $v['id']])->select();
            if( empty($currentCateQrcodeList) ){
                continue;
            }
            $qrcodeIds = array_column( $currentCateQrcodeList,'id' );
            //循环查询当前日期是否完成
            $where['qrcode_id'] = ['in',$qrcodeIds];
            $where['cate_id'] = ['in',$v['id']];

            foreach( $v['date_list'] as &$vv ){
                if( $type == 'day' ){
                    //查看当前天是否区分了时间段,如果是,则查看每个时间段是否巡检,所有时间段都有巡检记录才为巡检完成, 如果没有设置时间段, 那只要当前天有记录就算完成
                    if( empty( $v['record_time'] ) ){
                        $startTime = strtotime($vv['fullDate'] . ' 00:00:00');
                        $endTime = strtotime($vv['fullDate'] . ' 23:59:59');
                        $where['add_time'] = ['between', [$startTime, $endTime]];
                        $num = $qrcodeRecordLogDb->where($where)->count();
                        $vv['isInspection'] = $num > 0 ? 1 : 2;//1为完成, 2为未完成
                    }else{
                        $day_range = explode( ',',$v['record_time']);
                        //根据数组创建sql, 查看是否每个时间段都有巡检记录
                        $vv['isInspection'] = 1;
                        foreach($day_range as $dayV){
                            $dayV = explode('-', $dayV);
                            $startTime = strtotime($vv['fullDate'] .' '. $dayV[0]);
                            $endTime = strtotime($vv['fullDate'] .' '. $dayV[1]);
                            $where['add_time'] = ['between', [$startTime, $endTime]];
                            $list__ = $qrcodeRecordLogDb->where($where)->select();
                            $num_list = $this->groupByQrcodeId($list__,$qrcodeIds);
                            $isExists = $this->checkEachQrcodeHasTimeData($num_list, $startTime, $endTime);
                            if( $isExists == 'false' ){
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                }else if( $type == 'week' ){
                    //周任务查看是哪天需要巡检, 每天的那个时间段需要巡检,必须全部完成才算完成, 否则当前任务未完成
                    //如果数据为空, 那默认为本周有一次巡检就算完成
                    if( empty( $v['record_time'] ) ){
                        $startTime = strtotime($vv['weekRangeArr'][0] . ' 00:00:00');
                        $endTime = strtotime($vv['weekRangeArr'][1] . ' 23:59:59');
                        $where['add_time'] = ['between', [$startTime, $endTime]];
                        $num = $qrcodeRecordLogDb->where($where)->count();
                        $vv['isInspection'] = $num > 0 ? 1 : 2;//1为完成, 2为未完成
                    }else{
                        $day_range = explode( ',',$v['record_time']);
                        $vv['isInspection'] = 1;
                        foreach($day_range as $rangeItem){
                            $cur_day = $this->findDateByWeekday($vv['weekRangeArr'],$rangeItem);
                            $day_start_time = strtotime($cur_day . ' ' . $v['start_time']);
                            $day_end_time = strtotime($cur_day . ' ' . $v['end_time']);
                            $where['add_time'] = ['between', [$day_start_time, $day_end_time]];
                            $num = $qrcodeRecordLogDb->where($where)->count();
                            if( $num <= 0 ){
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                }else if( $type == 'month' ){
                    //月任务查看是哪天需要巡检, 每天的那个时间段需要巡检,必须全部完成才算完成, 否则当前任务未完成
                    //如果数据为空, 那默认为本月有一次巡检就算完成
                    if( empty( $v['record_time'] ) ){
                        $fullData = $this->getLastDayOfMonth($vv['fullMonth']);
                        $startTime = strtotime($vv['fullMonth'].'-01' . ' 00:00:00');
                        $endTime = strtotime($fullData . ' 23:59:59');
                        $where['add_time'] = ['between', [$startTime, $endTime]];
                        $num = $qrcodeRecordLogDb->where($where)->count();
                        $vv['isInspection'] = $num > 0 ? 1 : 2;//1为完成, 2为未完成
                    }else{
                        $day_range = explode( ',',$v['record_time']);
                        $vv['isInspection'] = 1;
                        foreach($day_range as $rangeItem){
                            $day_start_time = strtotime($vv['fullMonth'] .'-'.sprintf('%02d', $rangeItem). ' ' . $v['start_time']);
                            $day_end_time = strtotime($vv['fullMonth'] .'-'.sprintf('%02d', $rangeItem). ' ' . $v['end_time']);
                            $where['add_time'] = ['between', [$day_start_time, $day_end_time]];
                            $num = $qrcodeRecordLogDb->where($where)->count();
                            if( $num <= 0 ){
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                }
            }

            //添加完成率
            $total_num = count($v['date_list']);
            $inspectionArr = array_column($v['date_list'], 'isInspection', null);
            // 补全未定义的字段值为0（避免sum时漏算）
            $inspectionArr = array_map(function($val) {
                return ($val === 1 || $val === '1') ? 1 : 0;
            }, $inspectionArr);
            // 求和（1+0+1=2）
            $inspectin_num = array_sum($inspectionArr);
            $v['inspectin_num'] = $inspectin_num;
            $v['total_num'] = $total_num;
            $v['inspectin_rate'] = round( ($inspectin_num/$total_num) * 100,2 );
            unset($vv);
        }

        unset($v);
        $this->assign('static_path',$this->static_path);
        $this->assign('pagebar', $p->show());
        $this->assign('currentActive', $type);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);
        $this->assign('currentActive', $type);
        $this->assign('dataList', $cateList);
        $this->assign('dataListNum', count($cateList));
        $this->assign('currentHeaderList', $currentHeaderList);
        $this->display();

    }
    /**
     * 判断分组数组中每个qrcode_id下是否都有指定时间段内的数据
     * @param array $groupedData 按qrcode_id分组的数组（如你提供的格式）
     * @param int $startTime 起始时间戳（如1766200000）
     * @param int $endTime 结束时间戳（如1766300000）
     */
    function checkEachQrcodeHasTimeData(array $groupedData, int $startTime, int $endTime)
    {
        // 遍历每个qrcode_id的分组
        foreach ($groupedData as $qrcodeId => $items) {
            // 标记当前qrcode_id是否有符合时间段的数据
            $hasValidData = false;
            // 遍历当前qrcode_id下的所有数据项
            foreach ($items as $item) {
                // 校验add_time字段是否存在且为有效数字
                if (empty($item['add_time']) || !is_numeric($item['add_time'])) {
                    continue;
                }

                // 转换为整型时间戳，判断是否在指定时间段内
                $addTime = (int)$item['add_time'];

                if ($addTime >= $startTime && $addTime <= $endTime) {
                    $hasValidData = true;
                    break; // 找到一条即停止遍历当前qrcode_id的其他数据
                }
            }

            // 若当前qrcode_id无符合条件的数据，直接返回「否」
            if (!$hasValidData) {
                return 'false';
            }
        }
        // 所有qrcode_id都有符合条件的数据，返回「是」
        return 'true';
    }
    /**
     * 根据qrcode_id对多维数组分组，保留所有重复项，并补全指定qrcode_id列表中缺失的项（值为空数组）
     * @param array $data 原始多维数组（如你提供的格式）
     * @param array $qrcodeIdList 需校验补全的qrcode_id列表（如['61','62','63']）
     * @param string $key 分组字段名（默认qrcode_id）
     * @return array 分组后的数组，key为qrcode_id（包含列表中所有项），value为对应元素数组（缺失则为空数组）
     */
    function groupByQrcodeId(array $data, array $qrcodeIdList = [], string $key = 'qrcode_id'): array
    {
        // 初始化分组结果数组
        $groupedData = [];

        // 第一步：遍历原始数据，按qrcode_id分组（保留原有核心逻辑）
        foreach ($data as $item) {
            // 校验当前元素是否包含目标分组字段，避免报错
            if (!isset($item[$key])) {
                continue;
            }

            // 获取qrcode_id值（确保是字符串类型，统一格式）
            $qrcodeId = (string)$item[$key];

            // 若该qrcode_id分组不存在，先初始化空数组
            if (!isset($groupedData[$qrcodeId])) {
                $groupedData[$qrcodeId] = [];
            }

            // 将当前元素添加到对应分组中（保留重复项核心逻辑）
            $groupedData[$qrcodeId][] = $item;
        }

        // 第二步：补全qrcode_id列表中缺失的项（核心新增逻辑）
        if (!empty($qrcodeIdList)) {
            foreach ($qrcodeIdList as $targetQrcodeId) {
                // 统一转为字符串，避免数字/字符串类型不匹配导致的漏补
                $targetQrcodeId = (string)$targetQrcodeId;
                // 若分组结果中无该qrcode_id，补充key并赋值为空数组
                if (!isset($groupedData[$targetQrcodeId])) {
                    $groupedData[$targetQrcodeId] = [];
                }
            }

            // 可选：按qrcode_id列表的顺序重新排序（保持结果顺序和传入列表一致）
            $sortedGroupedData = [];
            foreach ($qrcodeIdList as $targetQrcodeId) {
                $targetQrcodeId = (string)$targetQrcodeId;
                $sortedGroupedData[$targetQrcodeId] = $groupedData[$targetQrcodeId];
            }
            $groupedData = $sortedGroupedData;
        }

        return $groupedData;
    }
    /**
     * 从「年-月」格式字符串获取当月最后一天日期
     * @param string $yearMonth 格式：Y-m（如2025-02）
     * @return string 最后一天日期，格式：Y-m-d（如2025-02-28）
     */
    function getLastDayOfMonth($yearMonth) {
        // 1. 拼接成「年-月-01」格式，确保DateTime能解析
        $dateStr = $yearMonth . '-01';
        // 2. 创建DateTime对象
        $date = DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$date) {
            return ''; // 格式错误返回空
        }
        // 3. 调整为当月最后一天
        $date->modify('last day of this month');
        // 4. 返回格式化后的日期
        return $date->format('Y-m-d');
    }

    /**
     * 从时间区间中找出指定星期几的日期
     * @param array $weekRangeArr 时间区间数组 [起始日期, 结束日期]，格式：Y-m-d
     * @param int $weekNum 目标星期数（0=周日，1=周一，2=周二...6=周六）
     * @return array 符合条件的日期列表（格式：Y-m-d）
     */
    function findDateByWeekday($weekRangeArr, $weekNum) {
        // 1. 校验入参（避免报错）
        if (count($weekRangeArr) != 2) {
            return []; // 区间数组必须是2个元素
        }
        $startDateStr = $weekRangeArr[0];
        $endDateStr = $weekRangeArr[1];

        // 2. 转换为DateTime对象（方便日期遍历）
        $startDate = DateTime::createFromFormat('Y-m-d', $startDateStr);
        $endDate = DateTime::createFromFormat('Y-m-d', $endDateStr);
        if (!$startDate || !$endDate) {
            return []; // 日期格式错误
        }

        // 3. 遍历区间内的所有日期，筛选目标星期
        $targetDates = null;
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            // 获取当前日期的星期数（0-6）
            $currentWeekNum = (int)$currentDate->format('w');

            // 匹配目标星期数，加入结果数组
            if ($currentWeekNum == $weekNum) {
                $targetDates = $currentDate->format('Y-m-d');
            }

            // 日期+1天，继续遍历
            $currentDate->modify('+1 day');
        }

        return $targetDates;
    }
    function getHeaderList($startDateStr = null, $endDateStr = null, $type)
    {
        // 基准当前日期（所有推算的结束日期都不能超过当天）
        $today = new DateTime();
        $today->setTime(0, 0, 0); // 重置时间为当天0点，避免时分秒干扰

        // 步骤1：根据传入的参数，计算最终的开始/结束日期
        try {
            $startDate = null;
            $endDate = null;

            // 情况1：同时传了开始和结束时间
            if ($startDateStr && $endDateStr) {
                $startDate = new DateTime($startDateStr);
                $endDate = new DateTime($endDateStr);
                // 确保结束时间不超过当天
                if ($endDate > $today) {
                    $endDate = clone $today;
                }
                // 确保开始时间 ≤ 结束时间
                if ($startDate > $endDate) {
                    throw new Exception('开始时间不能晚于结束时间（且结束时间不超过当天）');
                }
            }
            // 情况2：只传了开始时间，没传结束时间
            elseif ($startDateStr && !$endDateStr) {
                $startDate = new DateTime($startDateStr);
                // 根据类型推算结束时间，且不超过当天
                switch ($type) {
                    case 'day':
                        $endDate = clone $startDate;
                        $endDate->modify('+1 month'); // 日：往后推1个月
                        break;
                    case 'week':
                        $endDate = clone $startDate;
                        $endDate->modify('+2 months'); // 周：往后推2个月
                        break;
                    case 'month':
                        $endDate = clone $startDate;
                        $endDate->modify('+1 year'); // 月：往后推1年
                        break;
                    default:
                        $endDate = clone $today;
                }
                // 最终结束时间不能超过当天
                if ($endDate > $today) {
                    $endDate = clone $today;
                }
            }
            // 情况3：只传了结束时间，没传开始时间
            elseif (!$startDateStr && $endDateStr) {
                $endDate = new DateTime($endDateStr);
                // 确保结束时间不超过当天
                if ($endDate > $today) {
                    $endDate = clone $today;
                }
                // 根据类型推算开始时间
                switch ($type) {
                    case 'day':
                        $startDate = clone $endDate;
                        $startDate->modify('-1 month'); // 日：往前推1个月
                        break;
                    case 'week':
                        $startDate = clone $endDate;
                        $startDate->modify('-2 weeks'); // 周：往前推2周
                        break;
                    case 'month':
                        $startDate = clone $endDate;
                        $startDate->modify('-1 year'); // 月：往前推1年
                        break;
                    default:
                        $startDate = clone $endDate;
                        $startDate->modify('-1 month');
                }
                // 确保开始时间不早于结束时间（极端情况：结束时间是当天，推算后开始时间可能更早，无需额外限制）
            }
            // 情况4：都没传（按补充需求调整兜底逻辑）
            else {
                $endDate = clone $today; // 结束时间固定为当天
                switch ($type) {
                    case 'day':
                        $startDate = clone $today;
                        $startDate->modify('-1 month'); // 日：当前时间往前推1个月
                        break;
                    case 'week':
                        $startDate = clone $today;
                        $startDate->modify('-2 months'); // 周：当前时间往前推2个月
                        break;
                    case 'month':
                        $startDate = clone $today;
                        $startDate->modify('-1 year'); // 月：当前月往前推1年
                        break;
                    default:
                        $startDate = clone $today;
                        $startDate->modify('-1 month');
                }
            }

            // 最终校验：确保开始时间有效且不晚于结束时间
            if (!$startDate || !$endDate || $startDate > $endDate) {
                throw new Exception('时间范围计算异常，请检查传入参数');
            }

        } catch (Exception $e) {
            // 时间格式错误或逻辑错误时返回空数组
            return [];
        }

        // 步骤2：根据类型生成对应的时间列表（基于最终的startDate和endDate）
        if ($type == 'day') {
            $dayHeaderList = [];
            $loopDate = clone $startDate;
            while ($loopDate <= $endDate) {
                $fullDate = $loopDate->format('Y-m-d');
                $dayNum = (int)$loopDate->format('d');
                $dayHeaderList[] = [
                    'fullDate' => $fullDate,
                    'dayNum' => $dayNum,
                    'isInspection' => 2
                ];
                $loopDate->modify('+1 day');
            }
            return $dayHeaderList;

        } elseif ($type == 'week') {
            $weekHeaderList = [];
            $weekNum = 1;
            // 调整起始日期为开始时间所在周的周一
            $currentWeekStart = clone $startDate;
            $currentWeekStart->modify('this Monday');
            if ($currentWeekStart > $startDate) {
                $currentWeekStart->modify('-1 week');
            }

            while ($currentWeekStart <= $endDate) {
                $currentWeekEnd = clone $currentWeekStart;
                $currentWeekEnd->modify('this Sunday');
                if ($currentWeekEnd > $endDate) {
                    $currentWeekEnd = clone $endDate;
                }

                $weekRange = $currentWeekStart->format('Y-m-d') . ' 至 ' . $currentWeekEnd->format('Y-m-d');
                $weekRangeArr = [
                    $currentWeekStart->format('Y-m-d'),
                    $currentWeekEnd->format('Y-m-d')
                ];
                $weekHeaderList[] = [
                    'weekKey' => "week{$weekNum}",
                    'weekRange' => $weekRange,
                    'weekRangeArr' => $weekRangeArr,
                    'weekNum' => $weekNum,
                    'isInspection' => 2
                ];
                $weekNum++;
                $currentWeekStart->modify('next Monday');
            }
            return $weekHeaderList;

        } elseif ($type == 'month') {
            $monthHeaderList = [];
            // 从开始时间的当月第一天开始遍历
            $loopDate = clone $startDate;
            $loopDate->modify('first day of this month');

            while ($loopDate <= $endDate) {
                $year = (int)$loopDate->format('Y');
                $month = (int)$loopDate->format('m');

                $monthHeaderList[] = [
                    'monthNum' => $month,
                    'monthName' => $year . '年' . $month . '月',
                    'fullMonth' => $year . '-' . sprintf('%02d', $month),
                    'isInspection' => 2
                ];

                $loopDate->modify('first day of next month');
            }
            return $monthHeaderList;
        }

        // 非指定类型返回空数组
        return [];
    }
    public function Inspection_statistics_index2()
    {
//        echo "<pre>";
        $count_user = D('Wisdom_qrcode_cate')->where(['village_id'=>$this->village_id])->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $data_list = $this->get_inspection_statistics($p);
//        var_dump($data_list);die;
//        $data_list = [];


        //当月日期表头数据
        $dayHeaderList = [];
        $year = date('Y');
        $month = date('m');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $fullDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dayHeaderList[] = [
                'fullDate' => $fullDate,
                'dayNum' => $day
            ];
        }
        // 生成当月所有周的表头列表（示例：2026-01-01到2026-01-07 为第1周）
        $weekHeaderList = [];
        $weekNum = 1;
        $startDate = new DateTime("$year-$month-01");
        $endDate = new DateTime("$year-$month-$daysInMonth");
        $currentWeekStart = clone $startDate;
        while ($currentWeekStart <= $endDate) {
            // 计算本周最后一天（周日）
            $currentWeekEnd = clone $currentWeekStart;
            $currentWeekEnd->modify('this Sunday');
            if ($currentWeekEnd > $endDate) $currentWeekEnd = clone $endDate;

            $weekRange = $currentWeekStart->format('Y-m-d') . ' 至 ' . $currentWeekEnd->format('Y-m-d');
            $weekHeaderList[] = [
                'weekKey' => "week{$weekNum}",
                'weekRange' => $weekRange,
                'weekNum' => $weekNum
            ];

            // 下一周开始
            $currentWeekStart->modify('next Monday');
            $weekNum++;
        }

        // 生成1-12月表头列表
        $monthHeaderList = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthHeaderList[] = [
                'monthNum' => $m,
                'monthName' => $m . '月'
            ];
        }

        $day_list = $week_list = $month_list = [];

        // 遍历拆分
        foreach ($data_list as $item) {
            $record_type = isset($item['record_type']) ? $item['record_type'] : 0;
            if ($record_type == 1) {
                $day_list[] = $item;
            } elseif ($record_type == 2) {
                $week_list[] = $item;
            } elseif ($record_type == 3) {
                $month_list[] = $item;
            }
        }

        $day_completed_list = $this->addCompletedDatesToDataList($dayHeaderList, $day_list );
        echo "<pre>";
        var_dump($day_completed_list,$dayHeaderList);die;
        $this->assign('pagebar', $p->show());
        $this->assign('monthHeaderList', $monthHeaderList);
        $this->assign('weekHeaderList', $weekHeaderList);
        $this->assign('dayHeaderList', $dayHeaderList);
        $this->assign('day_list', $day_completed_list);
        $this->assign('week_list', $week_list);
        $this->assign('month_list', $month_list);
        $this->assign('countDateNum', count($data_list));
        $this->assign('countWeekNum', count($week_list));
        $this->assign('countMonthNum', count($month_list));
        $this->display();
    }

    public function get_inspection_statistics($p)
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['village_id'] = $this->village_id;
        $dataList = D('Wisdom_qrcode_cate')
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('id desc')
            ->select();

        //项目id
        $detail_where['village_id'] = $this->village_id;

        //开始结束时间
        $add_time_start = $_GET['begin_time'];
        $add_time_end = $_GET['end_time'];
        if ($add_time_start && $add_time_end) {
            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');
            $detail_where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $detail_where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $detail_where['add_time'] = array('lt', $add_time_end);
        }

        //二维码名称
        $qrcode_name = $_GET['qrcode_name'];
        if ($qrcode_name) {
            $qrcode['name'] = $_GET['find_value'];
            $qrcode['village_id'] = $this->village_id;
            $qrcode_ids = [];
            $qrcode_list = M('wisdom_qrcode')->where($qrcode)->select();
            foreach ($qrcode_list as $k => $v) {
                $qrcode_ids[] = $v['id'];
            }
            $detail_where['qrcode_id'] = array('in', $qrcode_ids);
        }

        //二维码类别
        $qrcode_type = $_GET['qrcode_type'];
        if ($qrcode_type) {
            $qrcode['cate_name'] = $_GET['find_value'];
            $cate_ids = [];
            $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->select();
            foreach ($qrcode_cate as $k => $v) {
                $cate_ids[] = $v['id'];
            }
            $detail_where['cate_id'] = array('in', $cate_ids);
        }

        $count_user = D('Wisdom_qrcode_record_log')->where($detail_where)->count();

        import('@.ORG.merchant_page');
        // $p = new Page($count_user, 15, 'page');
        $detailDataList = M('Wisdom_qrcode_record_log')
            ->where($detail_where)
            ->order('add_time desc')
            // ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $third_robot_deviceDb = D('Third_robot_device');

        foreach ($detailDataList as $k => &$v) {
            $cate = D('Wisdom_qrcode_cate')->where(array('id' => $v['cate_id']))->find();
            if ($cate) {
                $v['cate_name'] = $cate['cate_name'];
            } else {
                $v['cate_name'] = '';
            }
            $v['patrol_type_str'] = '系统巡检';
            if ($v['from_type'] == 1 && $v['rtype'] == 1) {
                $v['patrol_type_str'] = '智能巡检';
            } else if ($v['from_type'] == 1 && $v['rtype'] == 2) {
                $v['patrol_type_str'] = 'AI识别巡检';
            }
            $qrcode = D('Wisdom_qrcode')->where(array('id' => $v['qrcode_id']))->find();
            if ($qrcode) {
                $v['qrcode_name'] = $qrcode['name'];
            } else {
                $v['qrcode_name'] = '';
            }
            $v['name'] = '';
            $v['phone'] = '';
            if ($v['from_type'] == 1 && !empty($v['robot_code'])) {
                $robot_device = $third_robot_deviceDb->where(array('robot_code' => $v['robot_code']))->field('village_id,name,robot_code')->find();
                if ($robot_device) {
                    $v['name'] = $robot_device['name'];
                }
            } elseif ($v['uid'] > 0) {
                $worker = D('House_worker')->where(array('wid' => $v['uid']))->find();
                if ($worker) {
                    $v['name'] = $worker['name'];
                    $v['phone'] = $worker['phone'];
                }
            }
        }
        unset($v);

        foreach ($dataList as $k_ => &$v_) {
            $v_['detailDataList'] = filterArrayByField($detailDataList, 'cate_name', $v_['cate_name']);
        }
        unset($v_);

        if( !empty($dataList) ){
            return $dataList;
        }else{
            return [];
        }
    }

    /**
     * 核心功能：为$dataList中每个巡检项目新增completed_dates字段，存储完成全点位巡检的日期列表
     * @param array $dateList 日期数组（含fullDate/dayNum）
     * @param array $dataList 原始巡检数据数组
     * @return array 新增completed_dates字段后的$dataList
     */
    function addCompletedDatesToDataList($dateList, $dataList) {
        // 遍历每个巡检项目
        foreach ($dataList as &$projectItem) { // 注意&引用，直接修改原数组元素
            $completedDates = []; // 当前项目的完成日期列表（默认空）

            // 1. 解析当前项目的必检点位ID（bind_qrcode）
            $bindQrcode = json_decode(htmlspecialchars_decode($projectItem['bind_qrcode'] ?? '[]'), true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($bindQrcode)) {
                $projectItem['completed_dates'] = $completedDates;
                continue; // bind_qrcode解析失败/无点位，完成日期为空
            }
            $allQrcodeIds = array_column($bindQrcode, 'id'); // 提取所有点位ID
            $allQrcodeIds = array_unique($allQrcodeIds); // 去重
            if (empty($allQrcodeIds)) {
                $projectItem['completed_dates'] = $completedDates;
                continue; // 无有效点位，完成日期为空
            }

            // 2. 遍历每个日期，校验是否完成全点位巡检
            foreach ($dateList as $dateItem) {
                $currentDate = $dateItem['fullDate']; // 当前校验日期（YYYY-MM-DD）
                $isAllCompleted = true; // 标记：当日所有点位是否完成

                // 3. 遍历所有点位，逐个校验当日是否有记录
                foreach ($allQrcodeIds as $qrcodeId) {
                    $hasRecord = false; // 该点位当日是否有记录

                    // 4. 遍历detailDataList，匹配点位ID+日期
                    if (!empty($projectItem['detailDataList'])) {
                        foreach ($projectItem['detailDataList'] as $detail) {


                            // 跳过无效记录（无add_time/qrcode_id）
                            if (empty($detail['add_time']) || empty($detail['qrcode_id'])) {
                                continue;
                            }
                            // 时间戳转日期（匹配fullDate格式）
                            $recordDate = date('Y-m-d', $detail['add_time']);

                            // 匹配条件：点位ID一致 + 记录日期=当前校验日期
                            if ($detail['qrcode_id'] == $qrcodeId && $recordDate == $currentDate) {
                                $hasRecord = true;
                                break; // 该点位有记录，无需继续遍历
                            }
                        }
                    }

                    // 有一个点位无记录，当日未完成
                    if (!$hasRecord) {
                        $isAllCompleted = false;
                        break;
                    }
                }

                // 当日全点位完成，加入列表
                if ($isAllCompleted) {
                    $completedDates[] = $currentDate;
                }
            }

            // 5. 为当前项目新增completed_dates字段
            $projectItem['completed_dates'] = $completedDates;
        }
        unset($projectItem); // 解除引用，避免后续数组操作异常

        return $dataList;
    }

}
