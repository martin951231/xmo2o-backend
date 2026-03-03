<?php


namespace app\community\controller\property_api;


use app\community\controller\CommunityBaseController;
use app\community\model\db\BudgetCategory;
use app\community\model\db\HouseVillage;
use app\community\model\db\VillageBudget;
use app\community\model\service\BudgetManagementService;
use app\community\model\service\HousePropertyService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\Property\HouseVillageNewOrderService;
use app\community\model\service\HouseNewParkingService;
use app\common\model\service\export\ExportService;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BudgetManagementController extends CommunityBaseController
{
    public function getBudgetCategoryList()
    {
        // 获取登录信息
        $property_id = $this->adminUser['property_id'];
        $village_id = $this->adminUser['village_id'];
        if (empty($property_id)){
            return api_output(1002, [], '请先登录到物业后台！');
        }

        // 1. 接收分页参数，默认每页15条（和前端保持一致）
        $page          = $this->request->post('page', 1);
        $limit         = $this->request->post('limit', 15); // 改为默认15条，和前端匹配
        $village_id    = $this->request->post('village_id', $village_id); // 无传参则用登录用户的小区ID

        // 2. 构造查询条件（过滤空值，避免village_id为空导致查询异常）
        $where = [
            'property_id'  => $property_id,
        ];
        if (!empty($village_id)) {
            $where['village_id'] = $village_id;
        }

        $data = [
            'list' => [],
            'total' => 0, // 新增：总条数
            'page' => $page,
            'limit' => $limit
        ];

        try{
            $serviseBudgetManagement = new BudgetManagementService();
            // 3. 分别获取列表数据和总条数
            $list = $serviseBudgetManagement->getBudgetCategoryList($where,'*',false,$page,$limit);
            $total = $serviseBudgetManagement->getBudgetCategoryCount($where); // 新增：获取总条数

            $data['list'] = $list;
            $data['total'] = $total; // 返回总条数给前端
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        // 4. 返回完整的分页数据（前端需要total计算总页数）
        return api_output(0, $data);
    }

    /**
     * 新增/修改预算分类（新增无id，修改传id）
     * @return \think\Response
     */
    public function saveBudgetCategoryList()
    {
        // 1. 获取登录信息（复用原有登录校验逻辑）
        $property_id = $this->adminUser['property_id'];
        $village_id = $this->adminUser['village_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }

        // 2. 获取前端提交参数（新增：无id；修改：传id）
        $params = $this->request->post();
        // 必传参数校验
        if (empty($params['category_name'])) {
            return api_output(1003, [], '分类名称不能为空！');
        }
        // 补充关联字段（物业ID/小区ID，前端无需传，后台自动赋值）
        $params['category_name'] = $params['category_name'];
        $params['property_id'] = $property_id;
        $params['village_id'] = $params['village_id'] ?? null; // 优先取前端传的，无则用登录的
        // 状态默认值（前端未传则设为1-正常）
        $params['status'] = isset($params['status']) ? intval($params['status']) : 1;
        // 单位字段处理（空则存空字符串）
        $params['unit'] = $params['unit'] ?? '';

        try {
            // 3. 调用Service处理核心逻辑
            $serviseBudgetManagement = new BudgetManagementService();
            // 新增：无id；修改：有id
            if (empty($params['id'])) {
                // 新增逻辑：添加创建时间
                $params['add_time'] = date('Y-m-d H:i:s'); // 时间戳格式，符合MySQL TIMESTAMP存储
                $result = $serviseBudgetManagement->addBudgetCategory($params);
                if ($result) {
                    return api_output(0, ['code'=>200], '新增预算分类成功！');
                } else {
                    return api_output(1004, [], '新增预算分类失败！');
                }
            } else {
                // 修改逻辑：仅更新指定字段，不覆盖创建时间
                $updateData = [
                    'category_name'     => $params['category_name'],
                    'unit'     => $params['unit'],
                    'status'   => $params['status'],
                    'village_id' => $params['village_id'] // 支持修改小区关联
                ];
                $where = [
                    'id' => intval($params['id']),
                    'property_id' => $property_id // 仅允许修改当前物业的分类，防止越权
                ];
                $result = $serviseBudgetManagement->editBudgetCategory($where, $updateData);
                if ($result) {
                    return api_output(0, ['code'=>200], '修改预算分类成功！');
                } else {
                    return api_output(1005, [], '修改预算分类失败（分类不存在或无权限）！');
                }
            }
        } catch (\Exception $e) {
            // 异常捕获，返回错误信息
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 删除预算分类
     * @return \think\Response
     */
    public function deleteBudgetCategory()
    {
        // 1. 登录校验（复用原有逻辑）
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }

        // 2. 获取删除参数并校验
        $id = $this->request->post('id');
        if (empty($id)) {
            return api_output(1006, [], '请选择要删除的预算分类！');
        }

        try {
            // 3. 调用Service执行删除
            $serviseBudgetManagement = new BudgetManagementService();
            $result = $serviseBudgetManagement->deleteBudgetCategory($id, $property_id);

            if ($result) {
                return api_output(0, ['code'=>200], '删除预算分类成功！');
            } else {
                return api_output(1007, [], '删除失败：分类不存在或无权限！');
            }
        } catch (\Exception $e) {
            // 异常捕获
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 保存预算（新增/编辑）
     * 新增：校验category_id+month唯一性；编辑：仅允许修改当前物业的预算
     */
    public function saveVillageBudget()
    {
        // 1. 登录信息校验（复用原有逻辑）
        $property_id = $this->adminUser['property_id'];
        $admin_id = $this->adminUser['id'] ?? 0; // 操作人员ID（登录用户ID）
        $admin_name = $this->adminUser['user_name'] ?? '未知用户'; // 操作人员名称
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }
        if (empty($admin_id)) {
            return api_output(1006, [], '操作人员信息缺失，请重新登录！');
        }

        // 2. 获取并校验前端提交参数
        $params = $this->request->post();

        // 必传参数校验（核心字段）
        $requiredFields = [
            'category_id' => '分类ID',
            'village_id'  => '小区ID',
            'amount'      => '金额',
            'month'       => '月份'
        ];
        foreach ($requiredFields as $field => $desc) {
            if (empty($params[$field])) {
                return api_output(1003, [], $desc . '不能为空！');
            }
        }

        // 字段格式/类型处理
        $params['property_id']  = intval($property_id);       // 物业ID（强制转为整型）
        $params['village_id']   = intval($params['village_id']); // 小区ID（强制转为整型）
        $params['category_id']  = intval($params['category_id']); // 分类ID（强制转为整型）
        $params['amount']       = floatval($params['amount']); // 金额（强制转为浮点型，支持小数）
        $params['month']        = trim($params['month']);     // 月份（格式：YYYY-MM）
        $params['status']       = isset($params['status']) ? intval($params['status']) : 1; // 状态默认正常

        // 月份格式校验（必须为YYYY-MM）
        $monthReg = '/^\d{4}-\d{2}$/';
        if (!preg_match($monthReg, $params['month'])) {
            return api_output(1007, [], '月份格式错误，请输入YYYY-MM格式（如2025-02）！');
        }

        try {
            // 3. 实例化Service处理核心逻辑
            $budgetService = new BudgetManagementService();

            // 新增逻辑（无id）
            if (empty($params['id'])) {
                // 校验：同一分类+同一月份 不能重复添加（按物业ID隔离）
                $uniqueWhere = [
                    'category_id'  => $params['category_id'],
                    'month'        => $params['month']
                ];
                $existBudget = $budgetService->getVillageBudgetFind($uniqueWhere);
                if (!empty($existBudget)) {
                    return api_output(1008, [], '该分类在[' . $params['month'] . ']月份的预算已存在，请勿重复添加！');
                }

                // 新增字段补充
                $addData = [
                    'property_id'   => $params['property_id'],
                    'village_id'    => $params['village_id'],
                    'category_id'   => $params['category_id'],
                    'amount'        => $params['amount'],
                    'month'         => $params['month'],
                    'status'        => $params['status'],
                    'add_time'      => date('Y-m-d H:i:s'), // 创建时间
                    'operator' => $admin_name          // 操作人员名称
                ];

                // 执行新增
                $result = $budgetService->addBudget($addData);
                if ($result) {
                    return api_output(1000, ['code'=>200], '新增预算成功！');
                } else {
                    return api_output(1009, [], '新增预算失败，请重试！');
                }
            }
            // 编辑逻辑（有id）
            else {
                // 编辑字段（仅更新允许修改的字段，不覆盖创建时间/操作人员）
                $updateData = [
                    'village_id'    => $params['village_id'],
                    'category_id'   => $params['category_id'],
                    'amount'        => $params['amount'],
                    'month'         => $params['month'],
                    'status'        => $params['status']
                ];

                // 编辑条件：仅允许修改当前物业的预算（防止越权）
                $where = [
                    'id'            => intval($params['id']),
                    'property_id'   => $params['property_id']
                ];

                // 执行编辑
                $result = $budgetService->editBudget($where, $updateData);
                if ($result) {
                    return api_output(1000, [], '修改预算成功！');
                } else {
                    return api_output(1011, [], '修改预算失败（预算不存在或无权限）！');
                }
            }
        } catch (\Exception $e) {
            // 全局异常捕获，返回详细错误信息
            return api_output_error(-1, '保存预算异常：' . $e->getMessage());
        }
    }

    public function getBudgetList()
    {
        // 获取登录信息
        $property_id = $this->adminUser['property_id'];
        $village_id = $this->adminUser['village_id'];
        if (empty($property_id)){
            return api_output(1002, [], '请先登录到物业后台！');
        }

        // 1. 接收分页参数，默认每页15条（和前端保持一致）
        $page          = $this->request->post('page', 1);
        $limit         = $this->request->post('limit', 15); // 改为默认15条，和前端匹配
        $village_id    = $this->request->post('village_id', $village_id); // 无传参则用登录用户的小区ID

        // 2. 构造查询条件（过滤空值，避免village_id为空导致查询异常）
        $where = [
            'vb.property_id'  => $property_id,
        ];
        if (!empty($village_id)) {
            $where['village_id'] = $village_id;
        }

        $data = [
            'list' => [],
            'total' => 0, // 新增：总条数
            'page' => $page,
            'limit' => $limit
        ];

        try{
            $serviseBudgetManagement = new BudgetManagementService();
            $serviseHouseVillage = new HouseVillage();
            // 3. 分别获取列表数据和总条数
            $list = $serviseBudgetManagement->getBudgetList($where,'vb.*,bc.category_name',false,$page,$limit);
            $total = $serviseBudgetManagement->getBudgetCount($where); // 新增：获取总条数

            $categoryWhere['property_id'] = $property_id;
            $categoryList = $serviseBudgetManagement->getBudgetCategoryList($categoryWhere,true,'',1,99999);

            $villageWhere['property_id'] = $property_id;
            $villageList = $serviseHouseVillage->getList($villageWhere);

            $data['list'] = $list;
            $data['total'] = $total; // 返回总条数给前端
            $data['categoryList'] = $categoryList;
            $data['villageList'] = $villageList;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

        // 4. 返回完整的分页数据（前端需要total计算总页数）
        return api_output(0, $data);
    }

    public function exportBudget()
    {
        // 获取登录信息
        $property_id = $this->adminUser['property_id'];
        $village_id = $this->adminUser['village_id'];
        $property_name = $this->adminUser['property_name'];
        $village_name = $this->adminUser['village_name'] ?? ''; // 社区名，兼容无值情况

        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }

        // 获取前端传参的小区ID，无传参则用登录用户的小区ID
        $village_id = $this->request->post('village_id', $village_id);

        // 构造查询条件（过滤空值，避免village_id为空导致查询异常）
        $where = [
            'property_id'  => $property_id,
        ];
        if (!empty($village_id)) {
            $where['village_id'] = $village_id;
        }

        $serviseBudgetManagement = new BudgetManagementService();
        // 获取预算分类列表数据
        $list = $serviseBudgetManagement->getBudgetCategoryList($where,'*',false,1,99999);

        // 彻底转换为纯数组，避免对象导致的[object]问题
        $data = [];
        if (is_array($list)) {
            foreach ($list as $item) {
                $data[] = is_object($item) ? (array)$item : $item;
            }
        } elseif (is_object($list) && method_exists($list, 'toArray')) {
            $data = $list->toArray();
        }

        // 定义Excel模板路径（确保模板存在且表头正确）
        $filePath = root_path() . '../static/file/export_budget_template.xlsx';
        try {
            // 禁用PhpSpreadsheet计算缓存，提升性能
            \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance()->setCalculationCacheEnabled(false);

            // 必须加载已有的模板文件（表头已初始化）
            if (!file_exists($filePath)) {
                return api_output(1, [], '预算模板文件不存在，请先创建模板！');
            }
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // ========== 核心调整：按行填充（每行一个分类） ==========
            $startRow = 2; // 数据起始行（第1行是表头）
            $currentRow = $startRow;

            // 循环分类列表，每行填充一个分类
            foreach ($data as $index => $value) {
                // 确保value是纯数组
                $value = is_object($value) ? (array)$value : $value;

                // 【关键】按表头列对应填充（先确认你的表头列顺序，以下是常规表头映射）
                // 假设你的表头列顺序：A=序号, B=物业名, C=物业ID, D=社区名, E=分类名, F=分类ID, G=金额, H=月份, I=操作人
                $cellMap = [
                    // 序号列（A列）
                    'A' . $currentRow => (string)($index + 1),
                    // 物业名列（B列）
                    'B' . $currentRow => (string)$property_name,
                    // 物业ID列（C列）
                    'C' . $currentRow => (string)$property_id,
                    // 社区名列（D列）
                    'D' . $currentRow => (string)$village_name,
                    // 分类名列（E列）- 精准填入分类名
                    'E' . $currentRow => (string)($value['category_name'] ?? ''),
                    // 分类ID列（F列）
                    'F' . $currentRow => (string)($value['id'] ?? ''),
                    // 金额列（G列）- 留空
                    'G' . $currentRow => '',
                    // 月份列（H列）- 留空
                    'H' . $currentRow => '',
                    // 操作人列（I列）- 留空
                    'I' . $currentRow => ''
                ];

                // 循环赋值，强制文本格式避免类型错误
                foreach ($cellMap as $cellCoordinate => $cellValue) {
                    $worksheet->getCell($cellCoordinate)->setValueExplicit(
                        $cellValue,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }

                $currentRow++; // 下一个分类填充到下一行
            }

            // 如果没有数据，至少保留一行空数据（保证模板结构）
            if (empty($data)) {
                $emptyCellMap = [
                    'A2' => '1',
                    'B2' => (string)$property_name,
                    'C2' => (string)$property_id,
                    'D2' => (string)$village_name,
                    'E2' => '',
                    'F2' => '',
                    'G2' => '',
                    'H2' => '',
                    'I2' => ''
                ];
                foreach ($emptyCellMap as $cellCoordinate => $cellValue) {
                    $worksheet->getCell($cellCoordinate)->setValueExplicit(
                        $cellValue,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }
            }

            // 生成导出文件名
            $filename = '预算模板-' . date('YmdHis') . '.xlsx';

            // 调用导出服务生成文件
            $exportService = new ExportService();
            $exportService->phpSpreadsheet($filename, $spreadsheet);

            // 获取下载链接
            $arr = (new HouseNewParkingService())->downloadExportFile($filename);
            return api_output(0, $arr, '预算模板导出成功');

        } catch (Exception $e) {
            // 捕获异常并返回错误信息
            return api_output(1, [], '预算模板导出失败：' . $e->getMessage());
        }
    }

    /**
     * 预算导入接口（接收前端上传的Excel文件）
     */
    public function importBudget()
    {
        // ========== 1. 接收前端上传的文件 ==========
        $upload_file = $_FILES['file'] ?? []; // 对应前端formData.append('file', file)
        if (empty($upload_file) || $upload_file['error'] !== 0) {
            return api_output(400, [], '请上传有效的Excel文件！');
        }

        // 校验文件类型
        $file_name = $upload_file['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($file_ext, ['xls', 'xlsx'])) {
            return api_output(400, [], '仅支持上传.xls/.xlsx格式的Excel文件！');
        }

        // 临时文件路径
        $file_tmp = $upload_file['tmp_name'];
        if (!file_exists($file_tmp)) {
            return api_output(500, [], '上传文件临时路径不存在！');
        }

        try {
            // ========== 2. 解析Excel文件内容 ==========
            require_once $_SERVER['DOCUMENT_ROOT'].'/cms/Lib/ORG/phpexcel/PHPExcel.php';
            require_once $_SERVER['DOCUMENT_ROOT'].'/cms/Lib/ORG/phpexcel/PHPExcel/IOFactory.php';

            // 根据文件后缀选择解析器
            if ($file_ext === 'xlsx') {
                $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            } else {
                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            }

            // 加载Excel文件（禁用只读模式，避免解析异常）
            $objPHPExcel = $objReader->load($file_tmp);
            $sheet = $objPHPExcel->getActiveSheet();
            $highestRow = $sheet->getHighestRow(); // 获取总行数
            $highestColumn = $sheet->getHighestColumn(); // 获取总列数

            // 解析Excel为数组（格式：行号 => [A=>值, B=>值, ...]）
            $result = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $row_data = [];
                // 遍历每一列（A到H列，对应物业名到月份）
                for ($col = 'A'; $col <= 'H'; $col++) {
                    $cell_value = $sheet->getCell($col . $row)->getValue();
                    // 处理单元格值（兼容PHPExcel的特殊格式）
                    if ($cell_value instanceof PHPExcel_RichText) {
                        $cell_value = $cell_value->__toString();
                    }
                    $row_data[$col] = $cell_value ?? '';
                }
                $result[$row] = $row_data;
            }

            // ========== 3. 调用核心导入逻辑处理数据 ==========
            $this->doImportBudget($result);

        } catch (Exception $e) {
            // 捕获解析/导入异常
            return api_output(500, [], 'Excel解析或导入失败：' . $e->getMessage());
        }
    }

    /**
     * 核心导入逻辑（原importBudget方法，调整返回格式适配接口）
     * @param array $result Excel解析后的数组
     */
    private function doImportBudget($result)
    {
        // 基础校验：数据是否有效
        if (empty($result) || !is_array($result)) {
            return api_output(400, [], '导入数据为空或格式错误！');
        }

        // 移除表头行（第1行是表头，无需处理）
        unset($result[1]);

        // 初始化变量
        $error_arr = [];          // 失败数据数组
        $admin_user = $this->adminUser ?? []; // 登录用户信息

        $serviseHouseProperty = new HousePropertyService();
        $serviseBudgetManagement = new BudgetManagementService();
        $serviseHouseVillage = new HouseVillageService();

        // 循环处理每一行数据
        foreach ($result as $row_key => $row_data) {
            // 初始化当前行错误信息
            $error_msg = '';
            // 清理空格（避免全角/半角空格导致的空值误判）
            $row_data = array_map(function($v) {
                return is_string($v) ? trim($v) : $v;
            }, $row_data);

            // ========== 1. 必填项校验（B-H列） ==========
            $required_check = [
                'B' => ['val' => $row_data['B'] ?? '', 'msg' => '物业名不能为空'],
                'C' => ['val' => $row_data['C'] ?? '', 'msg' => '物业ID不能为空'],
                'D' => ['val' => $row_data['D'] ?? '', 'msg' => '社区名不能为空'],
                'E' => ['val' => $row_data['E'] ?? '', 'msg' => '分类名不能为空'],
                'F' => ['val' => $row_data['F'] ?? '', 'msg' => '分类ID不能为空'],
                'G' => ['val' => $row_data['G'] ?? '', 'msg' => '金额不能为空'],
                'H' => ['val' => $row_data['H'] ?? '', 'msg' => '月份不能为空'],
            ];
            foreach ($required_check as $col => $check) {
                if (empty($check['val'])) {
                    $error_msg = $check['msg'];
                    break;
                }
            }
            // ========== 2. 格式校验 ==========
            if (empty($error_msg)) {
                // 金额校验：数字/两位小数（正则）
                $amount = $row_data['G'];
                if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
                    $error_msg = '金额格式错误，仅支持整数或两位小数（如：100 或 100.00）';
                }

                // 月份校验：YYYY-MM格式（正则）
                $month = $row_data['H'];
                if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                    $error_msg = '月份格式错误，必须为YYYY-MM（如：2025-02）';
                }

                // 校验月份有效性（如2025-13是无效月份）
                if (empty($error_msg)) {
                    $month_parts = explode('-', $month);
                    $year = (int)$month_parts[0];
                    $month_num = (int)$month_parts[1];
                    if ($month_num < 1 || $month_num > 12 || $year < 2000 || $year > 2100) {
                        $error_msg = '月份无效，年份需在2000-2100之间，月份需为1-12';
                    }
                }
            }

            // ========== 3. 数据存在性校验 ==========
            if (empty($error_msg)) {
                // 校验物业是否存在
                $property_where = [
                    'id' => $row_data['C'],
                    'property_name' => $row_data['B'],
                    'status' => 1 // 仅校验启用的物业
                ];
                $property_exists = $serviseHouseProperty->getFind($property_where);

                if (empty($property_exists)) {
                    $error_msg = "物业不存在（ID：{$row_data['C']}，名称：{$row_data['B']}）";
                }

                // 校验预算分类是否存在
                $category_where = [
                    'id' => $row_data['F'],
                    'category_name' => $row_data['E'],
                    'property_id' => $row_data['C'],
                    'status' => 1 // 仅校验启用的分类
                ];
                $category_exists = $serviseBudgetManagement->getBudgetCategoryFind($category_where);
                if (empty($category_exists)) {
                    $error_msg = "预算分类不存在（ID：{$row_data['F']}，名称：{$row_data['E']}）";
                }
            }

            if (empty($error_msg)) {
                // 校验小区是否存在
                $village_where[] = [
                    'village_name' ,'=', $row_data['D'],
                ];
                $village_exists = $serviseHouseVillage->getFind($village_where);

                if (empty($village_exists)) {
                    $error_msg = "小区不存在（名称：{$row_data['D']}）";
                }
            }

            // ========== 4. 唯一性校验（月份+分类） ==========
            if (empty($error_msg)) {
                $unique_where = [
                    'category_id' => $row_data['F'],
                    'month' => $row_data['H'],
                    'village_id' => $village_exists['village_id'],
                    'status' => 1 // 仅校验未删除的数据
                ];
                $unique_exists = $serviseBudgetManagement->getVillageBudgetFind($unique_where);
                if (!empty($unique_exists)) {
                    $error_msg = "该分类（ID：{$row_data['F']}）在{$row_data['H']}月份已有预算数据，不允许重复添加";
                }
            }

            // ========== 5. 处理校验结果 ==========
            if (!empty($error_msg)) {
                // 记录失败数据（I列存储失败原因）
                $row_data['I'] = $error_msg;
                $error_arr[] = $row_data;
                continue;
            }

            // ========== 6. 数据入库（全部校验通过） ==========
            $insert_data = [
                'property_id' => $row_data['C'],
                'village_id' => $village_exists['village_id'],
                'category_id' => $row_data['F'],
                'amount' => $row_data['G'],
                'month' => $row_data['H'],
                'operator' => $admin_user['user_name'] ?? '未知用户', // 操作人
                'add_time' => date('Y-m-d H:i:s'),
                'status' => 1
            ];

            // 插入数据（失败则记录错误）
            $insert_result = $serviseBudgetManagement->addBudget($insert_data);
            if (empty($insert_result)) {
                $row_data['I'] = '数据入库失败，请联系管理员';
                $error_arr[] = $row_data;
            }
        }

        // ========== 7. 最终结果处理 ==========
        if (empty($error_arr)) {
            // 全部成功：返回JSON响应
            return $this->api_output(1000, ['code'=>200,'data' => []], '预算数据导入成功！');
        } else {
            // 存在失败数据：直接导出失败表格（前端会自动下载）
            $this->exportBudgetFailData($error_arr);
        }
    }

    /**
     * 导出预算导入失败数据表格（适配文件下载的响应格式）
     * @param array $error_arr 失败数据数组
     */
    private function exportBudgetFailData($error_arr)
    {
        try {
            // ========== 1. 基础配置（和exportBudget保持一致） ==========
            error_reporting(E_ALL);
            date_default_timezone_set('Asia/Shanghai');

            // 禁用PhpSpreadsheet计算缓存，提升性能（和exportBudget统一）
            \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance()->setCalculationCacheEnabled(false);

            // ========== 2. 加载Excel模板（复用预算导出模板，保证表头一致） ==========
            // 模板路径和exportBudget保持一致
            $filePath = root_path() . '../static/file/export_budget_template.xlsx';
            if (!file_exists($filePath)) {
                return $this->api_output(1, [], '预算模板文件不存在，无法生成失败列表！');
            }

            // 加载模板文件（PhpSpreadsheet方式）
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $title = '预算导入失败列表';
            $worksheet->setTitle($title);

            // ========== 3. 填充失败数据（按模板表头格式） ==========
            $startRow = 2; // 数据起始行（第1行是表头）
            $currentRow = $startRow;

            // 循环填充失败数据
            foreach ($error_arr as $index => $value) {
                // 确保value是纯数组
                $value = is_object($value) ? (array)$value : $value;

                // 按模板表头列映射填充（和exportBudget列顺序完全一致）
                $cellMap = [
                    // 序号列（A列）
                    'A' . $currentRow => (string)($index + 1),
                    // 物业名列（B列）
                    'B' . $currentRow => (string)($value['B'] ?? ''),
                    // 物业ID列（C列）
                    'C' . $currentRow => (string)($value['C'] ?? ''),
                    // 社区名列（D列）
                    'D' . $currentRow => (string)($value['D'] ?? ''),
                    // 分类名列（E列）
                    'E' . $currentRow => (string)($value['E'] ?? ''),
                    // 分类ID列（F列）
                    'F' . $currentRow => (string)($value['F'] ?? ''),
                    // 金额列（G列）
                    'G' . $currentRow => (string)($value['G'] ?? ''),
                    // 月份列（H列）
                    'H' . $currentRow => (string)($value['H'] ?? ''),
                    // 失败原因列（I列，复用原操作人列位置）
                    'I' . $currentRow => (string)($value['I'] ?? '')
                ];

                // 循环赋值，强制文本格式避免类型错误（和exportBudget统一）
                foreach ($cellMap as $cellCoordinate => $cellValue) {
                    $worksheet->getCell($cellCoordinate)->setValueExplicit(
                        $cellValue,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }

                $currentRow++; // 下一行填充下一条失败数据
            }

            // 如果没有失败数据（理论上不会走到这里，做兜底）
            if (empty($error_arr)) {
                $emptyCellMap = [
                    'A2' => '1',
                    'B2' => '',
                    'C2' => '',
                    'D2' => '',
                    'E2' => '',
                    'F2' => '',
                    'G2' => '',
                    'H2' => '',
                    'I2' => '无失败数据'
                ];
                foreach ($emptyCellMap as $cellCoordinate => $cellValue) {
                    $worksheet->getCell($cellCoordinate)->setValueExplicit(
                        $cellValue,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }
            }

            // ========== 4. 生成文件并返回下载链接（和exportBudget完全一致） ==========
            // 生成导出文件名（区分失败列表）
            $filename = '预算导入失败列表-' . date('YmdHis') . '.xlsx';
            // 调用导出服务生成文件（复用ExportService）
            $exportService = new ExportService();
            $exportService->phpSpreadsheet($filename, $spreadsheet);

            // 获取下载链接（复用已有服务）
            $arr = (new HouseNewParkingService())->downloadExportFile($filename);

            // 返回JSON结果
            return $this->api_output(1000, ['code'=>201,'data' => $arr], '预算导入失败列表已生成，可下载查看！');

        } catch (Exception $e) {
            // 捕获异常并返回错误信息
            return $this->api_output(0, [], '预算导入失败列表导出失败：' . $e->getMessage());
        }
    }

    /**
     * 删除预算
     * @return \think\Response
     */
    public function deleteVillageBudget()
    {
        // 1. 登录校验（复用原有逻辑）
        $property_id = $this->adminUser['property_id'];
        if (empty($property_id)) {
            return api_output(1002, [], '请先登录到物业后台！');
        }

        // 2. 获取删除参数并校验
        $id = $this->request->post('id');
        if (empty($id)) {
            return api_output(1006, [], '请选择要删除的预算！');
        }

        try {
            // 3. 调用Service执行删除
            $serviseBudgetManagement = new BudgetManagementService();
            $result = $serviseBudgetManagement->deleteVillageBudget($id, $property_id);

            if ($result) {
                return api_output(0, ['code'=>200], '删除预算分类成功！');
            } else {
                return api_output(1007, [], '删除失败：分类不存在或无权限！');
            }
        } catch (\Exception $e) {
            // 异常捕获
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 通用API响应格式（适配前端res.code判断）
     * @param int $code 状态码（200成功，400参数错误，500服务器错误）
     * @param array $data 数据
     * @param string $msg 提示信息
     * @return void
     */
    private function api_output($code, $data = [], $msg = '')
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $code,
            'data' => $data,
            'msg' => $msg
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}