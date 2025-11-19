<?php
/**
 * 导出
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/06/30 14:12
 */

namespace app\common\model\service\export;
use app\common\model\service\plan\PlanService;
class ExportService {

    public $basePath = '';
    public function __construct()
    {
        $this->basePath = request()->server('DOCUMENT_ROOT').'/v20/runtime/';
    }

	/**
     * 添加导出计划任务
     * @param $title string 标题
     * @param $param array 数据
     * $param = [
     *         'type',//导出业务唯一标识
     * ]
     * @return array
     */
    public function addExport($title, $param,$fileFix = 'csv')
    {
        // 表格名
		$param['rand_number'] = $param['rand_number'] ? $param['rand_number'] : time();
        $fileName = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.' . $fileFix;

        // 内容
        $data = array(
            'type' => $param['type'],
            'file_name' => $fileName,
            'title' => $title,
            'status' => isset($param['status']) ? $param['status'] : 0,
            'param' => serialize($param),
            'dateline' => $param['rand_number'],
        );

        // 添加日志
        $exportId = (new ExportLogService())->add($data);
        
        if (!$exportId) {
            throw new \think\Exception("添加导出失败", 1003);
            
        }

        if(isset($param['status']) && $param['status'] == 1){
            return ['file_name' => $fileName, 'export_id' => $exportId];
        }

        // 添加计划任务
        $param['export_id'] = $exportId;
        $params = [
            'file' => 'order_export',
            'url' => '/v20/public/index.php/common/common.plan/export/export_id/'.$exportId,
            'plan_time' => time() - 86400,
            'param' => $param,
        ];
        (new PlanService())->addTask($params);
        return ['file_name' => $fileName, 'export_id' => $exportId];
    }

    /**
     * 计划任务调用生成文件
     * @param $param array
     * @return array
     */
    public function runTask($param){

        // 获得导出记录
        $where['export_id']= $param['export_id'];//导出记录id
        $exportDetail = (new ExportLogService())->getOne($where);

        $planService = new PlanService();

        //删除计划任务
        if(isset($param['plan_task_id']) && $param['plan_task_id']){
            $planService->delTask($param['plan_task_id']);
        }else{
            $wherePlan = [
                'file' => 'order_export'
            ];
            $planList = $planService->getSome($wherePlan);
            foreach($planList	 as $v){
                $tmp = unserialize($v['param']);
                if($tmp['export_id'] == $exportDetail['export_id']){
//                    $planService->delTask($v['id']);
                }
            }
        }

//        set_time_limit(0);
//        ini_set('memory_limit', '2048M');

        try {
            // 调用也业务放获得数据并创建下载文件
            $param = unserialize($exportDetail['param']);
            $servicePath = $param['service_path'];
            $serviceName = $param['service_name'];
            $exportObj = new $servicePath;
            $res = $exportObj->$serviceName($param);

            // 修改导出记录为已执行
            $res = (new ExportLogService())->updateThis($where,['status'=>1]);
        } catch (\Exception $e) {
			throw new \think\Exception($e->getMessage().$e->getFile().$e->getLine(), '1005');
        }

        return true;
    }
    
    /**
	 * 计划任务执行导出
	 * @param  string  $param 参数
	 */
    public function runExport($param)
    {
        set_time_limit(0);
		ini_set('memory_limit', '2048M');
        $param = unserialize($param['param']);
        $servicePath = $param['service_path'];
        $serviceName = $param['service_name'];
        $exportObj = new $servicePath;
        $res = $exportObj->$serviceName($param);

        return true;
    }

    /**
	 * [putCsv description]
	 * @param  string  $csvFileName [description] 文件名
	 * @param  array   $dataArr     [description] 数组，每组数据都是使用，分割的字符串
	 * @param  string  $haderText   [description] 标题（默认第一行）
	 * @param  integer $line        [description] 从第几行开始写
	 * @param  integer $offset      [description] 共计写几行
	 * @return [type]               [description]
	 */
	public function putCsv($csvFileName, $dataArr ,$haderText = '', $line = 0, $offset = 0){
        // 设置输出头部信息
        header('Content-Encoding: UTF-8');
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename={$csvFileName}");

        $csvFileName = $this->basePath.$csvFileName;
        // file_put_contents($csvFileName,'1');
//
//        var_dump(request()->server('DOCUMENT_ROOT'));die;;
//         file_put_contents('../runtime/111/test.txt','1');
        // exit;

		$handle = fopen($csvFileName,"w");//写方式打开
		if(!$handle){
			return '文件打开失败';
		}
		
        fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));
		
		//判断是否定义头标题
		if(!empty($haderText)){
			$re = fputcsv($handle,$haderText);//该函数返回写入字符串的长度。若出错，则返回 false。。
		}
		foreach ($dataArr as $key => $value) {
			$re = fputcsv($handle,$value);//该函数返回写入字符串的长度。若出错，则返回 false。。
		}
    }

    /**phpSpreadsheet生成带样式的xlsx
     * @param $filename
     * @param $spreadsheet
     * @throws \think\Exception
     */
    public function phpSpreadsheet($filename,$spreadsheet,$basePath=''){
        // 设置输出头部信息
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        try{
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename =$basePath ? $basePath.$filename : $this->basePath.$filename;
            $writer->save($filename);
        }catch(\Exception $e){
            throw new \think\Exception($e->getMessage(), '1005');
        }
    }
    
    /**
	 * 下载表格
	 */
    public function downloadExportFile($param){
        // TODO 验证身份
		// if(empty($_SESSION['staff'])&&empty($_SESSION['merchant'])&&empty($_SESSION['system'])){
		// 	exit;
        // }
        
        $returnArr = [];
        // 查询导出
		$where['export_id'] =  $param['export_id'];
        $export  = (new ExportLogService())->getOne($where);
        // var_dump($export);

		if(!file_exists($this->basePath.$export['file_name'])){
            $returnArr['error'] = 1;
            return $returnArr;
            // throw new \think\Exception("文件不存在", '1003');
            
        }

        if(request()->isAjax()){
            $returnArr['error'] = 1;
            return $returnArr;
            // throw new \think\Exception("请求发式错误", '1003');
        }

        $filename = $export['title'].'_'.$export['file_name'];

        $ua = request()->server('HTTP_USER_AGENT');
        $ua = strtolower($ua);
        if(preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
            $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
        }
        $returnArr['url'] = cfg('site_url').'/v20/runtime/'.$export['file_name'];
        $returnArr['error'] = 0;
        return $returnArr;
	}
	
	public function exportExcel($title,$cellName,$cellData,$fileName='')
    {
        empty($fileName) && $fileName = $title.'-'.date("Y-m-d", time());
        $exportResult = export_excel($title, $cellName, $cellData, $fileName);
        return ['file_url' => $exportResult['url']];
    }

}