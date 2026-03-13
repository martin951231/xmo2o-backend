<?php


namespace app\community\controller\property_api;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\village_api\Exception;
use app\community\model\db\ExpressApplication;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\UtilitiesCategory;
use app\community\model\db\UtilitiesLog;
use app\community\model\db\VillageBill;
use app\community\model\db\WisdomQrcode;
use app\community\model\db\WisdomQrcodeCate;
use app\community\model\db\WisdomQrcodeRecordLog;
use app\community\model\service\BudgetManagementService;
use app\community\model\service\CommunityStatisticsService;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\HouseVillageRepairCateService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\NewBuildingService;
use app\community\model\service\PropertyFrameworkService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageService;
use app\community\model\service\RepairCateService;
use DateTime;
use pinyin\Pinyin;
use think\facade\Cache;
use function app\community\controller\village_api\log_write;

class PropertyStatisticalController extends CommunityBaseController
{

    /**
     * Notes: 获取配置
     * @return \json
     * @author: wanzy
     * @date_time: 2020/9/2 21:42
     */
    public function getPropertyBaseData() {
        $property_name = $this->adminUser['property_name'];
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'登录信息丢失, 请重新登录');
        }
        $property_id = $this->adminUser['property_id'];
        if(!$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $service_house_village = new HouseVillageService();
        $service_house_village_user_bind = new HouseVillageUserBindService();
        $service_house_village_user_vacancy = new HouseVillageUserVacancy();
        $village_list = $service_house_village->getList(['property_id'=>$property_id],'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $area_num = $service_house_village->getAreaNum([['village_id','in',$village_arr],['status','=',1]]); //入驻城市
        $village_num = $service_house_village->getVillageNum([['village_id','in',$village_arr],['status','=',1]]);  //项目数量
        $size_sum = $service_house_village->getAreaSize([['village_id','in',$village_arr]],'building_area');  //覆盖面积
        $user_bind_num = $service_house_village_user_bind->getUserCount([['village_id','in',$village_arr],['status','=',1],['type','in',[0,1,2,3]]]);//住户数量
        $user_bind_where[] = ['a.village_id','in',$village_arr];
        $user_bind_where[] = ['b.status','=',1];
        $user_bind_where[] = ['b.type','in',[0,1,2,3]];

        $user_bind_residence_where = array_merge( $user_bind_where, [['a.house_type','=',1]] );
        $user_bind_residence_num = $service_house_village_user_vacancy->getRoomUserNumber($user_bind_residence_where);//住宅数量

        $user_bind_store_where = array_merge( $user_bind_where, [['a.house_type','=',2]] );
        $user_bind_store_num = $service_house_village_user_vacancy->getRoomUserNumber($user_bind_store_where);//商铺数量

        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $vacancy=array();
        $vacancy[] = ['village_id','in',$village_arr];
        $vacancy[] = ['is_del','=',0];
        $vacancy[] = ['status','in',array(1,3)];
        $house_size = $dbHouseVillageUserVacancy->getSum($vacancy,'housesize');
        $size_sum = $size_sum+$house_size;//覆盖面积
        $communityStatisticsService =new CommunityStatisticsService();
        $size_sum = $communityStatisticsService->bigDataChange($size_sum);
        $data = [
            'property_id' => $property_id,
            'property_name' => $property_name,
            'settle_city' => $area_num,
            'village_num' => $village_num,
            'area' => $size_sum,
            'resident_num' => $user_bind_residence_num,
            'store_num' => $user_bind_store_num,
        ];
        return api_output(0,$data);
    }

    /**
     * 统计数组中user_status_txt等于指定值的元素数量
     * @param array $dataArr 待统计的数组（支持关联数组/对象数组）
     * @param string $targetStatus 要查询的目标状态（比如"未入住"、"已入住"、"待入住"等）
     * @return int 匹配目标状态的数量，数组无效/目标值为空时返回0
     */
    function countUserStatus($dataArr, $targetStatus) {
        // 1. 基础校验：数组无效/目标值为空，直接返回0
        if (!is_array($dataArr) || empty($dataArr) || !is_string($targetStatus) || trim($targetStatus) === '') {
            return 0;
        }

        // 2. 格式化目标值（去空格，避免因空格导致匹配失败）
        $target = trim($targetStatus);
        $count = 0;

        // 3. 遍历数组统计
        foreach ($dataArr as $item) {
            $currentStatus = '';
            // 处理关联数组（最常见）
            if (is_array($item)) {
                $currentStatus = isset($item['user_status_txt']) ? trim($item['user_status_txt']) : '';
            }
            // 兼容对象数组（数据库查询返回的对象）
            elseif (is_object($item)) {
                $currentStatus = isset($item->user_status_txt) ? trim($item->user_status_txt) : '';
            }

            // 匹配目标状态
            if ($currentStatus === $target) {
                $count++;
            }
        }

        return $count;
    }

    /*
     * 获取组织架构
     * */
    public function getPropertyOrgList()
    {
        // -------------- 第一步：获取基础参数（做空值容错，避免参数未定义报错）--------------
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;
        $initVillageId = 0; // 初始小区ID，改名区分

        // -------------- 第二步：初始化服务&获取原始组织数据（业务接口调用）--------------
        $propertyFrameworkService = new PropertyFrameworkService();
        $navRes = $propertyFrameworkService->businessNav(1, $propertyId, $initVillageId) ?: [];
        $menuList = $navRes['menu_list'][0] ?? [];
        $deptList = $menuList['children'] ?? [];
        $resultList = [];
        $totalUserCount = 0; // 新增：人员总数初始化

        // -------------- 新增：定义递归统计人员总数的方法 --------------
        /**
         * 递归统计所有type=1/2部门的人员总数
         * @param array $deptList 部门列表
         * @return int 累计人员数
         */
        $countTotalUser = function ($deptList) use (&$countTotalUser) {
            $count = 0;
            foreach ($deptList as $dept) {
                // 累加当前部门的人员数
                $userInfo = $dept['user_info'] ?? [];
                $count += is_array($userInfo) ? count($userInfo) : 0;

                // 递归统计子部门的人员数
                if (!empty($dept['children']) && is_array($dept['children'])) {
                    $count += $countTotalUser($dept['children']);
                }
            }
            return $count;
        };

        // -------------- 新增：定义递归处理部门的方法 --------------
        /**
         * 递归处理部门层级，type=1/2查人员，type=0递归children
         * @param array $deptItem 单个部门数据
         * @param int $propertyId 物业ID
         * @param int $currentVillageId 当前部门的小区ID
         * @param PropertyFrameworkService $service 服务实例
         * @return array 处理后的部门数据（含人员/子部门）
         */
        $handleDeptRecursive = function ($deptItem, $propertyId, $currentVillageId, $service) use (&$handleDeptRecursive) {
            // 基础容错：非数组/无ID直接返回空
            if (!is_array($deptItem) || empty($deptItem['id'])) {
                return [];
            }

            $deptId = $deptItem['id'];
            $deptType = $deptItem['type'] ?? 0;
            // 优先用当前部门自己的village_id，没有则用上级传递的
            $currentVillageId = $deptItem['village_id'] ?? $currentVillageId;
            $processedDept = $deptItem; // 复制原始部门数据

            // type=1 或 type=2 都调用方法查询人员（用当前部门的village_id）
            if ($deptType == 1 || $deptType == 2) {
                $userQueryParam = [
                    'role_type'   => 2,
                    'property_id' => $propertyId,
                    'village_id'  => $currentVillageId,
                    'group_id'    => $deptId,
                ];
                $tissueUserRes = $service->businessTissueUser($userQueryParam) ?: [];
                $processedDept['user_info'] = $tissueUserRes['list'] ?? [];
            }

            // type=0：递归处理children子部门（传递当前部门的village_id）
            if ($deptType == 0 && !empty($deptItem['children']) && is_array($deptItem['children'])) {
                $processedChildren = [];
                foreach ($deptItem['children'] as $child) {
                    $handledChild = $handleDeptRecursive($child, $propertyId, $currentVillageId, $service);
                    if (!empty($handledChild)) {
                        $processedChildren[] = $handledChild;
                    }
                }
                $processedDept['children'] = $processedChildren; // 替换为处理后的子部门
            }

            return $processedDept;
        };

        // -------------- 第三步：遍历部门，改用递归处理 --------------
        if (is_array($deptList) && !empty($deptList)) {
            foreach ($deptList as $dept) {
                // 初始调用：传递初始小区ID 0
                $handledDept = $handleDeptRecursive($dept, $propertyId, $initVillageId, $propertyFrameworkService);
                if (!empty($handledDept)) {
                    $resultList[] = $handledDept;
                }
            }
            // 新增：统计所有人员总数（递归累加）
            $totalUserCount = $countTotalUser($resultList);
        }

        // -------------- 第四步：组装前端ECharts树图所需的myOrgData格式（适配递归后的层级）--------------
        $myOrgData = [
            'name'     => $menuList['title'] ?? '智慧物业总部',
            'users'    => [],
            'children' => [],
        ];

        // 新增：递归组装ECharts树图数据
        $buildOrgTreeRecursive = function ($deptList) use (&$buildOrgTreeRecursive) {
            $treeChildren = [];
            foreach ($deptList as $dept) {
                $deptTitle = $dept['title'] ?? '';
                // 提取当前部门人员姓名
                $userInfo = $dept['user_info'] ?? [];
                $userNames = is_array($userInfo) ? array_column($userInfo, 'name') : [];
                $userNames = array_filter($userNames, function ($name) {
                    return !empty(trim($name));
                });
                $userNames = array_values($userNames);

                // 递归组装子部门树图数据
                $childTree = [];
                if (!empty($dept['children']) && is_array($dept['children'])) {
                    $childTree = $buildOrgTreeRecursive($dept['children']);
                }

                $treeChildren[] = [
                    'name'     => $deptTitle,
                    'users'    => $userNames,
                    'children' => $childTree,
                ];
            }
            return $treeChildren;
        };

        // 生成带层级的树图数据
        $myOrgData['children'] = $buildOrgTreeRecursive($resultList);

        // -------------- 第五步：组装最终返回数据（新增人员总数）--------------
        $finalData = [
            'data'           => $resultList,  // 原始部门+人员数据（含递归层级）
            'orgData'      => $myOrgData,   // 前端ECharts树图专用数据
            'total_user_num' => $totalUserCount, // 新增：所有部门人员总数
        ];

        // 标准化接口返回
        return api_output(0, $finalData);
    }

    public function getPropertyAllProjectBaseData()
    {
        // -------------- 第一步：获取基础参数（做空值容错，避免参数未定义报错）--------------
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;
        if(!$propertyId){
            return api_output_error(1001,'登录信息丢失, 请重新登录');
        }
        $service_house_village = new HouseVillageService();
        $houseVillageInfoService = new HouseVillageInfo();
        $village_list = $service_house_village->getList(['property_id'=>$propertyId],'village_id')->toArray();
        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $village_where[] = ['hvi.village_id','in',$village_arr];
        $list = $houseVillageInfoService->getVillageInfoList($village_where,false,'',1,9999);
        $list = $list ? $list->toArray() : [];

        $result = [];
        foreach( $list as $item ){
            $resident = $item['zhuzhai_yijiao']. ' / ' . $item['zhuzhai_yishou'] .' / '.($item['zhuzhai_yijiao'] + $item['zhuzhai_weijiao']);
            $store = $item['shangpu_yijiao']. ' / ' . $item['shangpu_yishou'] .' / '.($item['shangpu_yijiao'] + $item['shangpu_weijiao']);
            $parking_space = $item['chewei_yijiao']. ' / ' . $item['chewei_yishou'] .' / '.($item['chewei_yijiao'] + $item['chewei_weijiao']);
            $result[] = [
                'id' => $item['info_id'],
                'project_name' => $item['village_name'],
                'resident' => $resident,
                'store' => $store,
                'parking_space' => $parking_space,
            ];
        }
        return api_output(0, $result);
    }


    /**
     * 获取用户使用情况
     *
     * */
    public function getPropertyCustomChartData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;
        if(!$propertyId){
            return api_output_error(1001,'登录信息丢失, 请重新登录');
        }

        [$startDate, $endDate] = $this->completeDateRange($_GET['startDate'] ?? '', $_GET['endDate'] ?? '', 12);
        $service_house_village = new HouseVillageService();
        $houseVillageInfoService = new HouseVillageInfo();
        $village_list = $service_house_village->getList(['property_id'=>$propertyId],'village_id')->toArray();

        if(empty($village_list)){
            return api_output_error(1001,'该物业没有小区');
        }
        $village_arr = [];
        foreach ($village_list as $v){
            $village_arr[] = $v['village_id'];
        }
        $where[] = ['o.village_id','in',$village_arr];
        $where[] = ['o.add_time','between',[strtotime($startDate),strtotime($endDate)]];
        //获取工单
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $workOrderList = $db_house_new_repair_works_order->getAllList($where)->toArray();
        if( !empty($workOrderList) ){
            foreach( $workOrderList as $k => &$v ){
                $v['add_time_text'] = date('Y-m-d', $v['add_time']);
            }
        }
        unset($v);
        $finalWorkOrderList = $this->countDataByMonth($workOrderList, $startDate, $endDate);
        //获取快递代取
        $db_express_application = new ExpressApplication();
        $express_where[] = ['p.village_id' , 'in' , $village_arr];
        $expressList = $db_express_application->getList($express_where,'p.*',false,false,false)->toArray();
        if( !empty($expressList) ){
            foreach( $expressList as $k => &$v ){
                $v['add_time_text'] = date('Y-m-d', strtotime($v['create_time']));
            }
        }
        unset($v);
        $finalExpressList = $this->countDataByMonth($expressList, $startDate, $endDate);
        $finalList['data'] = [
            'xAxis' => array_keys( $finalExpressList ),
            'series' => [
                [ 'name'=> '报事报修','data'=> array_values($finalWorkOrderList) ],
                [ 'name'=> '代取快递','data'=> array_values($finalExpressList) ]
            ]
        ];
        return api_output(0, $finalList);
    }

    /**
     * 获取物业工单情况数据
     */
    public function getPropertyWorkOrderSituationData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;

        if (!$propertyId) {
            return api_output_error(1001, '登录信息丢失, 请重新登录');
        }

        [$startDate, $endDate] = $this->completeDateRange($_GET['startDate'] ?? '', $_GET['endDate'] ?? '', 12);

        $serviceHouseVillage = new HouseVillageService();
        $serviceRepairCate = new RepairCateService();
        $dbHouseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
        $pinyin = new Pinyin();

        // 1. 获取物业下的小区
        $villageList = $serviceHouseVillage->getList(['property_id' => $propertyId], 'village_id')->toArray();
        if (empty($villageList)) {
            return api_output_error(1001, '该物业没有小区');
        }

        $villageArr = array_column($villageList, 'village_id');

        // 2. 获取分类，并去重组装为前端需要的格式
        $cateWhere = [
            'village_id' => $villageArr,
            'status'    => 1,
            'parent_id' => 0,
        ];

        $cateRes = $serviceRepairCate->getRepairCate($cateWhere);

        $uniqueData = [];
        $temp = [];

        if (!empty($cateRes['list'])) {
            $cateList = $cateRes['list']->toArray();

            foreach ($cateList as $item) {
                $cateName = trim($item['cate_name'] ?? '');

                if ($cateName === '' || isset($temp[$cateName])) {
                    continue;
                }

                $temp[$cateName] = true;
                $uniqueData[] = [
                    'name' => $cateName,
                    'key'  => $pinyin->str2py($cateName),
                ];
            }
        }

        // 3. 获取工单数据
        $where = [];
        $where[] = ['o.village_id', 'in', $villageArr];
        $where[] = ['o.add_time', 'between', [strtotime($startDate), strtotime($endDate)]];

        $workOrderList = $dbHouseNewRepairWorksOrder
            ->getAllCateList($where, 'o.*,hc.cate_name,hv.village_name')
            ->toArray();

        // 4. 补充日期字段 + 按 village_id 分组
        $groupList = [];

        if (!empty($workOrderList)) {
            foreach ($workOrderList as &$item) {
                $item['add_time_text'] = !empty($item['add_time']) ? date('Y-m-d', $item['add_time']) : '';
                $villageId = $item['village_id'] ?? 0;
                $groupList[$villageId][] = $item;
            }
            unset($item);
        }

        // 5. 组装图表数据
        $result = [];

        foreach ($groupList as $villageId => $datum) {
            $datumList = $this->countDataByCateName($uniqueData, $datum);

            $row = [
                'village_id'   => $datum[0]['village_id'] ?? 0,
                'village_name' => $datum[0]['village_name'] ?? '',
            ];

            foreach ($datumList as $cate) {
                $row[$cate['key']] = $cate['count'];
            }

            $result[] = $row;
        }

        // 6. 组装 series 配置
        $series = $this->buildSeriesConfig($uniqueData);

        $finalList = [
            'data' => [
                'workOrderSituationChartData'  => $result,
                'workOrderSituationSeriesList' => $series,
            ]
        ];

        return api_output(0, $finalList);
    }

    /**
     * 组装图表 series 配置
     * @param array $data
     * @return array
     */
    function buildSeriesConfig(array $data): array
    {
        // 前4个默认颜色
        $defaultColors = [
            '#00ff99',
            '#ff6699',
            '#6699ff',
            '#00ffd9',
        ];

        // 额外补充的同主题颜色
        $extraColors = [
            '#33ccff',
            '#ffcc66',
            '#9966ff',
            '#66ff66',
            '#ff9966',
            '#00ccff',
            '#ff66cc',
            '#66cccc',
            '#99ff00',
            '#ff3366',
        ];

        $colorList = array_merge($defaultColors, $extraColors);

        $result = [];

        foreach ($data as $index => $item) {
            $result[] = [
                'key'      => 's' . ($index + 1),
                'name'     => $item['name'] ?? '',
                'type'     => 'bar',
                'yAxisKey' => 'y1',
                'field'    => $item['key'] ?? '',
                'stack'    => 'stack1',
                'color'    => $colorList[$index] ?? sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
            ];
        }

        return $result;
    }

    /**
     * 根据分类统计数量
     * @param array $allCategories 分类列表
     * @param array $allData 工单数据
     * @return array
     */
    function countDataByCateName(array $allCategories, array $allData): array
    {
        // 先统计所有 cate_name
        $dataCount = array_count_values(array_column($allData,'cate_name'));

        $result = [];

        foreach ($allCategories as $cate) {

            $name = trim($cate['name']);

            $result[] = [
                'name'  => $name,
                'key'   => $cate['key'],
                'count' => $dataCount[$name] ?? 0
            ];
        }

        return $result;
    }


    /**
     * 补全日期范围（支持自定义往前推的月份数）
     * @param string $startDate 传入的开始日期（可为空）
     * @param string $endDate 传入的结束日期（可为空）
     * @param int $months 往前推的月份数（默认1个月，传12则推12个月）
     * @return array 补全后的 [startDate, endDate]
     */
    function completeDateRange($startDate, $endDate, $months = 1) {
        // 1. 清洗参数：处理空值+去除首尾空格
        $startDate = trim($startDate ?? '');
        $endDate = trim($endDate ?? '');

        // 2. 校验月份数：确保是正整数（避免传负数/非数字）
        $months = is_numeric($months) && $months > 0 ? (int)$months : 1;

        // 3. 基础配置：根据传入的月份数生成默认开始日期
        $config = [
            'defaultEnd' => date('Y-m-d'), // 默认结束日期：当前日期
            'defaultStart' => date('Y-m-d', strtotime("-{$months} month")) // 自定义往前推N个月
        ];

        // 4. 补全end日期：为空则用默认当前日期
        $endDate = $endDate ?: $config['defaultEnd'];

        // 5. 补全start日期：为空则用end日期往前推N个月
        $startDate = $startDate ?: date('Y-m-d', strtotime("{$endDate} -{$months} month"));

        // 6. 校验日期合法性：非法则重置为默认日期
        if (strtotime($startDate) === false || strtotime($endDate) === false) {
            return [$config['defaultStart'], $config['defaultEnd']];
        }

        // 额外优化：确保开始日期不晚于结束日期（避免逻辑错误）
        if (strtotime($startDate) > strtotime($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        return [$startDate, $endDate];
    }

    /**
     * 按add_time_text按月分组统计数据条数（指定时间区间，无数据月份填0）
     * @param array $data 原始数据数组（就是你提供的包含add_time_text的数组）
     * @param string $startDate 开始日期（格式：Y-m-d，比如"2026-02-26"）
     * @param string $endDate 结束日期（格式：Y-m-d，比如"2027-02-26"）
     * @return array 按月份分组的统计结果（key为Y-m，value为条数）
     */
    function countDataByMonth($data, $startDate, $endDate) {
        // 1. 校验日期合法性
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        if ($startTs === false || $endTs === false || $startTs > $endTs) {
            return []; // 日期非法返回空数组
        }

        // 2. 生成时间区间内的所有月份（确保每个月都有，无数据填0）
        $monthList = [];
        $currentTs = $startTs;
        while ($currentTs <= $endTs) {
            $monthKey = date('Y-m', $currentTs);
            $monthList[$monthKey] = 0; // 初始化所有月份为0
            // 跳到下一个月的第一天
            $currentTs = strtotime('+1 month', strtotime(date('Y-m-01', $currentTs)));
        }

        // 3. 遍历原始数据，统计每个月的条数
        foreach ($data as $item) {
            // 跳过无add_time_text的条目
            if (empty($item['add_time_text']) || strtotime($item['add_time_text']) === false) {
                continue;
            }

            // 获取当前条目的月份（Y-m）
            $itemMonth = date('Y-m', strtotime($item['add_time_text']));
            $itemTs = strtotime($item['add_time_text']);

            // 只统计在时间区间内的数据
            if ($itemTs >= $startTs && $itemTs <= $endTs && isset($monthList[$itemMonth])) {
                $monthList[$itemMonth]++;
            }
        }

        // 4. 保持月份顺序返回（按时间从早到晚）
        ksort($monthList);
        return $monthList;
    }

    public function getRevenueData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;

        if (!$propertyId) {
            return api_output_error(1001, '登录信息丢失, 请重新登录');
        }

        $serviceHouseVillage = new HouseVillageService();

        // 1. 获取物业下的小区
        $villageList = $serviceHouseVillage->getList(['property_id' => $propertyId], 'village_id')->toArray();
        if (empty($villageList)) {
            return api_output_error(1001, '该物业没有小区');
        }

        $villageArr = array_column($villageList, 'village_id');

        $startDate = $this->request->post('startDate');
        $endDate = $this->request->post('endDate');

        [$startDate, $endDate] = $this->completeDateRange($startDate ?? '', $endDate ?? '', 12);


        // 获取当前年份
//        $currentYear = date('Y');
        $currentYear = date('2025');

        // 获取当年第一天（格式：YYYY-01-01 00:00:00）
        $firstDayOfYear = date('Y-m-d H:i:s', strtotime($currentYear . '-01-01 00:00:00'));

        // 获取当年最后一天（格式：YYYY-12-31 23:59:59）
        $lastDayOfYear = date('Y-m-d H:i:s', strtotime($currentYear . '-12-31 23:59:59'));

        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $data = [];
        foreach( $villageArr as $villageId ) {
            $villageInfo = $serviceHouseVillage->getHouseVillage($villageId);
            $param=[
                'village_id'=>$villageId,
                'project_type'=>'cycle',
                'status'=>1,
            ];
            $projectList = $HouseNewChargeProjectService->getAllPropertyList($param);

            $order_where = [
                'village_id'=>$villageId,
                'start_time'=>$firstDayOfYear,
                'end_time'=>$lastDayOfYear,
            ];
            $payableOrderList = $this->payableOrderList($order_where);

            $total_amound = 0; // 应缴费总金额汇总
            $total_paid_amound = 0; // 已缴费总金额汇总
            $total_remaining_paid_amound_num = 0; // 剩余应缴金额汇总

            foreach ($projectList as &$item) {
                $item['amound'] = 0;//应缴费总金额
                $item['paid_amound'] = 0;//已缴费总金额
                $item['remaining_paid_amound_num'] = 0;//剩余应缴金额
                $item['paid_amound_rate'] = 0;//缴费率
                $item['account_num'] = 0;//总户数
                $item['area'] = 0;//面积
                $item['paid_amound_txt'] = '0万元';
                $item['amound_txt'] = '0万元';
                $item['remaining_paid_amound_num_txt'] = '0万元';
                $item['paid_amound_str'] = '0';
                $item['remaining_paid_amound_num_str'] = '0';

                // 已缴户数
                $origin_project_name_key = 'project_name';
                $origin_project_name_value = $item['name'];

                // 第一步：先按project_name筛选出匹配的数组
                $matchedArr = array_filter($payableOrderList, function ($row) use ($origin_project_name_key, $origin_project_name_value) {
                    return isset($row[$origin_project_name_key]) && $row[$origin_project_name_key] == $origin_project_name_value;
                });

                // 第二步：按room_id去重，每个room_id只保留第一条匹配的数据
                $uniqueArr = [];
                $roomIdRecord = [];

                if (!empty($matchedArr)) {
                    foreach ($matchedArr as $value) {
                        if (isset($value['room_id']) && !empty($value['room_id'])) {
                            $roomId = $value['room_id'];
                            if (!in_array($roomId, $roomIdRecord)) {
                                $roomIdRecord[] = $roomId;
                                $uniqueArr[] = $value;
                            }
                        } else {
                            $uniqueArr[] = $value;
                        }
                    }
                }

                /* 获取当前收费标准绑定用户数 */
                $current_project_where = [
                    'village_id' => $villageId,
                    'rule_id'    => $item['rule_id'],
                    'bind_type'  => 1,
                ];

                $currentRoomList = $this->standardBindList($current_project_where);
                $currentRoomList = isset($currentRoomList['list']) && $currentRoomList['list'] > 0 ? $currentRoomList['list'] : [];
                $currentProjectRoomNum = count($currentRoomList);

                $item['account_num'] = $currentProjectRoomNum;//总户数

                if ($currentProjectRoomNum <= 0) {
                    continue;
                }

                // 已缴费总金额
                $item['paid_amound'] = array_sum(array_column($matchedArr, 'pay_money'));
                $item['paid_amound_txt'] = $this->formatAmountToWan($item['paid_amound']) . '万元';

                // 判断账单生成周期
                if ($item['bill_create_set'] == 1) {
                    // 按日
                    $charge_price = $item['charge_price'] * 365;
                } else if ($item['bill_create_set'] == 2) {
                    // 按月
                    $charge_price = $item['charge_price'] * 12;
                } else if ($item['bill_create_set'] == 3) {
                    // 按年
                    $charge_price = $item['charge_price'];
                } else {
                    $charge_price = $item['charge_price'];
                }

                $roomArea = 0;

                if ($item['fees_type'] == 2) {
                    // 单位计价
                    if ($item['unit_gage_type'] == 2) {
                        // 车位面积
                        $roomArea = array_sum(array_column($currentRoomList, 'position_area'));
                    } else {
                        // 房屋面积
                        $roomArea = array_sum(array_column($currentRoomList, 'housesize'));
                    }

                    // 应缴费总金额
                    $item['amound'] = round($roomArea * $charge_price, 2);
                    $item['amound_txt'] = $this->formatAmountToWan($item['amound']) . '万元';
                } else {
                    // 固定费用
                    $item['amound'] = round($item['account_num'] * $charge_price, 2);
                    $item['amound_txt'] = $this->formatAmountToWan($item['amound']) . '万元';
                }

                $item['area'] = $roomArea;

                // 剩余应缴
                $item['remaining_paid_amound_num'] = round($item['amound'] - $item['paid_amound'], 2);
                $item['remaining_paid_amound_num_txt'] = $this->formatAmountToWan($item['remaining_paid_amound_num']) . '万元';

                // 缴费率
                $item['paid_amound_rate'] = $item['amound'] > 0
                    ? round($item['paid_amound'] / $item['amound'], 2) * 100
                    : 0;

                $item['paid_amound_str'] = $this->formatAmountToWan($item['paid_amound']);
                $item['remaining_paid_amound_num_str'] = $this->formatAmountToWan($item['remaining_paid_amound_num']);

                // ===== 汇总累加 =====
                $total_amound += $item['amound'];
                $total_paid_amound += $item['paid_amound'];
                $total_remaining_paid_amound_num += $item['remaining_paid_amound_num'];
            }
            unset($item);

            // 总缴费率
            $total_paid_amound_rate = $total_amound > 0
                ? round($total_paid_amound / $total_amound, 2) * 100
                : 0;
            $data[] = [
                'village_id' => $villageInfo['village_id'],
                'village_name' => $villageInfo['village_name'],
                'amound_total' => round($this->formatAmountToWan($total_amound), 2),
                'paid_amound_total' => round($this->formatAmountToWan($total_paid_amound), 2),
                'remaining_paid_amound_total' => round($this->formatAmountToWan($total_remaining_paid_amound_num), 2),
                'paid_amound_rate' => round( $total_paid_amound_rate, 2 ),
                'amound_total_txt' => $this->formatAmountToWan($total_amound) . '万元',
                'paid_amound_total_txt' => $this->formatAmountToWan($total_paid_amound) . '万元',
                'remaining_paid_amound_total_txt' => $this->formatAmountToWan($total_remaining_paid_amound_num) . '万元',
                'paid_amound_rate_txt' => $total_paid_amound_rate . '%',
            ];
        }

        $filteredData['data'] = $data;

        return api_output(1000,$filteredData);
    }
    /**
     * 获取所有已缴账单
     * @author lijie
     * @date_time 2021/07/08
     * @return \json
     */
    function payableOrderList($param){
        $service_house_new_cashier = new HouseNewCashierService();
        $village_id = $param['village_id'];

        try{
            $where[] = ['o.pay_time','>=',strtotime($param['start_time'])];
            $where[] = ['o.pay_time','<=',strtotime($param['end_time'])];
            $where[] = ['o.is_discard','=',1];
            $where[] = ['o.is_paid','=',1];
            $where[] = ['o.order_type','<>','non_motor_vehicle'];
            $where[] = ['o.village_id','=',$village_id];
            $field='o.car_number,o.car_type,o.order_id,o.order_no,o.room_id,o.position_id,p.name as project_name,o.pay_money,o.pay_bind_name,o.pay_bind_phone,o.pay_time,o.pay_type,o.is_refund,o.refund_money,o.village_id,o.property_id,o.order_type,o.invoice_status';
            $list = $service_house_new_cashier->getCancelOrderUser($where,'',$field,1,99999999,'o.pay_time DESC');
            if( empty($list['list']) ){
                return [];
            }
            return $list['list'];

        }catch (\Exception $e){
            return [];
        }
        return $list;
    }


    /**
     * 查询收费标准绑定列表, 以及房间信息
     * @author:zhubaodi
     * @date_time: 2021/6/18 10:27
     */
    public function standardBindList($param)
    {
        $data['village_id'] = $param['village_id'];
        $data['rule_id'] = $param['rule_id'];
        $data['bind_type'] = $param['bind_type'];

        if ($data['bind_type']==1){
            $data['vecancy_id'] = $param['vecancy_id'];
        }else{
            $data['garage_id'] = $param['garage_id'];
            $data['position_num'] = $param['position_num'];
        }

        $data['page'] = 1;
        $data['limit']= 999999999;
        $serviceChargeProject = new HouseNewChargeRuleService();


        // print_r($data);exit;
        try {
            $chargeSetInfo = $serviceChargeProject->getStandardBindList2($data);
            $configCustomizationService=new ConfigCustomizationService();
            $isWSXCustomizedOpen=$configCustomizationService->getWSXCustomizedOpen();
            $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();

            $standardBindImportBtnJudge=$configCustomizationService->getStandardBindImportBtnJudge();

            $is_standard_bind_import_btn=0;
            if($isWSXCustomizedOpen || $is_aihorse_customized || $standardBindImportBtnJudge){
                $is_standard_bind_import_btn=1;
            }

            $chargeSetInfo['standard_bind_import_btn']=$is_standard_bind_import_btn;

        } catch (\Exception $e) {
            return [
                'count'=>0,
                'list'=>[],
            ];
        }
        return $chargeSetInfo;
    }

    /**
     * 金额格式化：转为万元单位 + 千位分隔
     * @param float|int|string $amount 原始金额（单位：元），支持数字/数字字符串
     * @param int $decimal 保留小数位数，默认2位
     * @param bool $keepTrailingZero 是否保留末尾的0（如 1.00 而非 1），默认true
     * @return string 格式化后的金额（如：12,345.67 万元）
     */
    function formatAmountToWan($amount, $decimal = 2, $keepTrailingZero = true) {
        // 1. 基础校验与转换：确保金额为数字类型
        $amount = is_numeric($amount) ? (float)$amount : 0.00;

        // 2. 转换为万元单位（除以10000）
        $amountWan = $amount / 10000;

        // 3. 处理小数位数：四舍五入到指定精度
        $formatted = round($amountWan, $decimal);

        // 4. 千位分隔处理
        // number_format会自动处理千位分隔，同时控制小数位数
        $formattedStr = number_format($formatted, $decimal, '.', ',');

        // 5. 可选：移除末尾的0（如果需要）
        if (!$keepTrailingZero) {
            // 去掉末尾的0和多余的小数点
            $formattedStr = rtrim(rtrim($formattedStr, '0'), '.');
        }

        // 6. 拼接单位并返回
        return $formattedStr;
    }

    public function getAllWorkOrderResponseData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;

        if (!$propertyId) {
            return api_output_error(1001, '登录信息丢失, 请重新登录');
        }

        $serviceHouseVillage = new HouseVillageService();

        // 1. 一次性获取物业下所有小区
        $villageList = $serviceHouseVillage->getList(
            ['property_id' => $propertyId],
            'village_id,village_name'
        )->toArray();

        if (empty($villageList)) {
            return api_output_error(1001, '该物业没有小区');
        }

        $villageIds = array_column($villageList, 'village_id');
        $villageMap = [];
        foreach ($villageList as $item) {
            $villageMap[$item['village_id']] = $item['village_name'];
        }

        $startDate = $this->request->post('startDate') ?: date('Y-m-01');
        $endDate   = $this->request->post('endDate') ?: date('Y-m-t');

        // 测试用
        $startDate = '2025-12-01';
        $endDate   = '2025-12-31';

        $startTs = strtotime($startDate . ' 00:00:00');
        $endTs   = strtotime($endDate . ' 23:59:59');

        // 2. 一次性查出所有小区在时间范围内的工单
        $dbWorkOrder = new HouseNewRepairWorksOrder();
        $dbWorkOrderLog = new HouseNewRepairWorksOrderLog();

        $where = [];
        $where[] = ['o.village_id', 'in', $villageIds];
        $where[] = ['o.add_time', '>=', $startTs];
        $where[] = ['o.add_time', '<=', $endTs];

        $workOrderList = $dbWorkOrder->getAllList($where)->toArray();

        if (empty($workOrderList)) {
            $result = [];
            foreach ($villageIds as $villageId) {
                $result[] = [
                    'village_id' => $villageId,
                    'village_name' => $villageMap[$villageId] ?? '',
                    'completedTime' => 0,
                    'respondTime' => 0,
                ];
            }
            return api_output_success($result);
        }

        // 3. 收集所有工单ID，一次性查日志
        $orderIds = array_column($workOrderList, 'order_id');

        $status = [
            'center_assign_work',
            'property_admin_assign',
            'property_work_assign',
            'house_admin_assign',
            'house_auto_assign',
            'work_grab',
        ];

        $logWhere = [];
        $logWhere[] = ['order_id', 'in', $orderIds];
        $logWhere[] = ['log_name', 'in', $status];

        $logList = $dbWorkOrderLog->where($logWhere)
            ->field('order_id,add_time')
            ->order('add_time ASC')
            ->select()
            ->toArray();

        // 4. 取每个工单最早的一条响应日志
        $firstLogMap = [];
        foreach ($logList as $log) {
            $orderId = $log['order_id'];
            if (!isset($firstLogMap[$orderId])) {
                $firstLogMap[$orderId] = $log['add_time'];
            }
        }

        // 5. 按小区汇总
        $targetStatus = [40, 60, 70];
        $villageStats = [];

        // 初始化所有小区
        foreach ($villageIds as $villageId) {
            $villageStats[$villageId] = [
                'completed_total' => 0,
                'completed_count' => 0,
                'response_total' => 0,
                'response_count' => 0,
            ];
        }

        foreach ($workOrderList as $item) {
            $villageId = $item['village_id'] ?? 0;
            if (!$villageId || !isset($villageStats[$villageId])) {
                continue;
            }

            $orderId = $item['order_id'] ?? 0;
            $addTime = isset($item['add_time']) && is_numeric($item['add_time']) ? (int)$item['add_time'] : 0;
            $timelyTime = isset($item['timely_time']) && is_numeric($item['timely_time']) ? (int)$item['timely_time'] : 0;
            $eventStatus = isset($item['event_status']) && is_numeric($item['event_status']) ? (int)$item['event_status'] : 0;

            if (!$addTime) {
                continue;
            }

            // 响应时长
            $firstRecordTime = $firstLogMap[$orderId] ?? 0;
            $responseTime = $firstRecordTime ? round(abs($firstRecordTime - $addTime) / 3600, 2) : 0;

            // 完成时长
            $completedTime = $timelyTime ? round(abs($timelyTime - $addTime) / 3600, 2) : 0;

            // response_time：全部工单统计
            if ($responseTime >= 0) {
                $villageStats[$villageId]['response_total'] += $responseTime;
                $villageStats[$villageId]['response_count']++;
            }

            // completed_time：仅统计指定状态
            if (empty($eventStatus) || in_array($eventStatus, $targetStatus)) {
                if ($completedTime >= 0) {
                    $villageStats[$villageId]['completed_total'] += $completedTime;
                    $villageStats[$villageId]['completed_count']++;
                }
            }
        }

        // 6. 组装结果
        $result = [];
        foreach ($villageIds as $villageId) {
            $completedAvg = $villageStats[$villageId]['completed_count'] > 0
                ? round($villageStats[$villageId]['completed_total'] / $villageStats[$villageId]['completed_count'], 4)
                : 0;

            $responseAvg = $villageStats[$villageId]['response_count'] > 0
                ? round($villageStats[$villageId]['response_total'] / $villageStats[$villageId]['response_count'], 4)
                : 0;

            $result[] = [
                'village_id' => $villageId,
                'village_name' => $villageMap[$villageId] ?? '',
                'completedTime' => $completedAvg,
                'respondTime' => $responseAvg,
            ];
        }

        $chartData = [
            'xAxis' => [],
            'series' => [
                [
                    'name' => '完成时长',
                    'data' => [],
                ],
                [
                    'name' => '响应时长',
                    'data' => [],
                ],
            ],
        ];
        foreach ($result as $item) {
            $chartData['xAxis'][] = $item['village_name'] ?? '';
            $chartData['series'][0]['data'][] = isset($item['completedTime']) ? (float)$item['completedTime'] : 0;
            $chartData['series'][1]['data'][] = isset($item['respondTime']) ? (float)$item['respondTime'] : 0;
        }

        return api_output(1000,$chartData);
    }
    /**
     * 获取巡检情况
     *
     *
     * */
    public function getInspectionChartData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;

        if (!$propertyId) {
            return api_output_error(1001, '登录信息丢失, 请重新登录');
        }

        $serviceHouseVillage = new HouseVillageService();

        // 1. 获取物业下的小区
        $villageList = $serviceHouseVillage->getList(['property_id' => $propertyId], 'village_id')->toArray();
        if (empty($villageList)) {
            return api_output_error(1001, '该物业没有小区');
        }

        $villageArr = array_column($villageList, 'village_id');

        $startDate = $this->request->post('startDate');
        $endDate = $this->request->post('endDate');

        [$startDate, $endDate] = $this->completeDateRange($startDate ?? '', $endDate ?? '', 12);

        $finaList = [];
        foreach($villageArr as $villageId){
            $param = [
                'village_id' => $villageId,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ];
            $chart_data = $this->getProjectInspectionData($param);
            $villageInfo = $serviceHouseVillage->getHouseVillage($villageId);
            $chart_data['village_name'] = $villageInfo['village_name'];
            $finaList[] = $chart_data;
        }

        return api_output(1000,$finaList);
    }


    /*
     * 获取项目巡检情况
     * */
    public function getProjectInspectionData($params)
    {
        // -------------- 1. 基础参数校验 & 初始化 --------------
        $villageId = $params['village_id'] ?? 0;
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        if( empty($startDate) && empty( $endDate ) ){
            $startDate = date('Y-m-d', strtotime('-1 month'));
            $endDate = date('Y-m-d');
        }else if( !empty($startDate) && empty( $endDate ) ){
            $endDate = date('Y-m-d');
        }else if( empty($startDate) && !empty( $endDate ) ){
            $startDate = date('Y-m-d', strtotime($endDate.'-1 month'));
        }

        $qrcodeCateDb = new WisdomQrcodeCate();
        $where[] = ['village_id', '=', $villageId];
        $cateList = $qrcodeCateDb->where($where)->select()->toArray();

        //获取项目巡检完成率
        $inspectionCompletedRate = $this->getInspectionCompletedRate($cateList, $villageId,$startDate,$endDate);
        $list = $this->mergeArrByIntersectId( $cateList, $inspectionCompletedRate );

        //获取项目巡检异常情况以及点位异常次数
        $abnormalAmount = $this->getAbnormalAmount($cateList, $villageId,$startDate,$endDate);
        $list = $this->mergeArrByIntersectId( $list, $abnormalAmount );

        //获取覆盖率
        $overrideRate = $this->getOverrideRate($cateList, $villageId,$startDate,$endDate);
        $list = $this->mergeArrByIntersectId( $list, $overrideRate );

        $chart_data = [];
        $sum = [
            'total_num' => 0,
            'inspectin_num' => 0,
            'complete_rate' => 0,
            'abnormal_amount' => 0,
            'abnormal_repeat_amount' => 0,
            'override_rate' => 0,
        ];
        foreach ($list as &$v) {
            $chart_data[] = [
                'name' => $v['cate_name'],
                'total_num' => $v['total_num'],
                'inspectin_num' => $v['inspectin_num'],
                'complete_rate' => $v['complete_rate'],
                'abnormal_amount' => $v['abnormal_amount'],
                'abnormal_repeat_amount' => $v['abnormal_repeat_amount'],
                'should_be_inspection_amount' => $v['should_be_inspection_amount'],
                'actual_inspection_amount' => $v['actual_inspection_amount'],
                'override_rate' => $v['override_rate'],
            ];
            $sum['total_num'] += $v['total_num'] ?? 0;
            $sum['inspectin_num'] += $v['inspectin_num'] ?? 0;
            $sum['abnormal_amount'] += $v['abnormal_amount'] ?? 0;
            $sum['abnormal_repeat_amount'] += $v['abnormal_repeat_amount'] ?? 0;
            $sum['should_be_inspection_amount'] += $v['should_be_inspection_amount'] ?? 0;
            $sum['actual_inspection_amount'] += $v['actual_inspection_amount'] ?? 0;
        }

        $sum['complete_rate'] = $sum['total_num'] > 0 ? round(($sum['inspectin_num'] / $sum['total_num']) * 100, 2) : 0;
        $sum['override_rate'] = $sum['should_be_inspection_amount'] > 0 ? round(($sum['actual_inspection_amount'] / $sum['should_be_inspection_amount']) * 100, 2) : 0;
        unset($v);
//
//        $result = [
//            'data' => $list,
//            'chart_list' => array_column($list, 'chart_data'),
//        ];
        return $sum;
    }


    public function getInspectionCompletedRate($cateList, $villageId, $startDate, $endDate)
    {
        $qrcodeDb = new WisdomQrcode();
        $qrcodeRecordLogDb = new WisdomQrcodeRecordLog();

        $headerMap = [
            1 => 'day',
            2 => 'week',
            3 => 'month',
        ];

        if (empty($cateList)) {
            return [];
        }

        $cateIds = array_column($cateList, 'id');

        // 1. 一次性查所有分类下的二维码
        $allQrcodeList = $qrcodeDb->where('cate_id', 'in', $cateIds)->select()->toArray();
        $qrcodeMap = [];
        foreach ($allQrcodeList as $item) {
            $qrcodeMap[$item['cate_id']][] = $item;
        }

        // 2. 一次性查时间范围内全部巡检记录
        $globalStartTime = strtotime($startDate . ' 00:00:00');
        $globalEndTime = strtotime($endDate . ' 23:59:59');

        $allLogList = $qrcodeRecordLogDb->where([
            ['village_id', '=', $villageId],
            ['cate_id', 'in', $cateIds],
            ['add_time', 'between', [$globalStartTime, $globalEndTime]]
        ])->select()->toArray();

        // 3. 按 cate_id 分组
        $logCateMap = [];
        foreach ($allLogList as $log) {
            $logCateMap[$log['cate_id']][] = $log;
        }

        foreach ($cateList as &$cate) {
            $cate['date_list'] = $this->getHeaderList($startDate, $endDate, $headerMap[$cate['record_type']]);
        }
        unset($cate);

        foreach ($cateList as &$v) {
            $v['complete_rate'] = 0;

            $currentCateQrcodeList = $qrcodeMap[$v['id']] ?? [];
            if (empty($currentCateQrcodeList)) {
                $v['inspectin_num'] = 0;
                $v['total_num'] = count($v['date_list']);
                $v['complete_rate'] = 0;
                unset($v['date_list']);
                continue;
            }

            $qrcodeIds = array_column($currentCateQrcodeList, 'id');
            $currentLogList = $logCateMap[$v['id']] ?? [];

            foreach ($v['date_list'] as &$vv) {
                $vv['isInspection'] = 2;

                if ($v['record_type'] == 1) {
                    if (empty($v['record_time'])) {
                        $startTime = strtotime($vv['fullDate'] . ' 00:00:00');
                        $endTime = strtotime($vv['fullDate'] . ' 23:59:59');

                        $list__ = $this->filterLogsByTimeRange($currentLogList, $startTime, $endTime);
                        $vv['isInspection'] = !empty($list__) ? 1 : 2;
                    } else {
                        $dayRange = explode(',', $v['record_time']);
                        $vv['isInspection'] = 1;

                        foreach ($dayRange as $dayV) {
                            $dayV = explode('-', $dayV);
                            $startTime = strtotime($vv['fullDate'] . ' ' . $dayV[0]);
                            $endTime = strtotime($vv['fullDate'] . ' ' . $dayV[1]);

                            $list__ = $this->filterLogsByTimeRange($currentLogList, $startTime, $endTime);
                            $numList = $this->groupByQrcodeId($list__, $qrcodeIds);
                            $isExists = $this->checkEachQrcodeHasTimeData($numList, $startTime, $endTime);

                            if ($isExists == 'false') {
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                } elseif ($v['record_type'] == 2) {
                    if (empty($v['record_time'])) {
                        $startTime = strtotime($vv['weekRangeArr'][0] . ' 00:00:00');
                        $endTime = strtotime($vv['weekRangeArr'][1] . ' 23:59:59');

                        $list__ = $this->filterLogsByTimeRange($currentLogList, $startTime, $endTime);
                        $vv['isInspection'] = !empty($list__) ? 1 : 2;
                    } else {
                        $dayRange = explode(',', $v['record_time']);
                        $vv['isInspection'] = 1;

                        foreach ($dayRange as $rangeItem) {
                            $curDay = $this->findDateByWeekday($vv['weekRangeArr'], $rangeItem);
                            $dayStartTime = strtotime($curDay . ' ' . $v['start_time']);
                            $dayEndTime = strtotime($curDay . ' ' . $v['end_time']);

                            $list__ = $this->filterLogsByTimeRange($currentLogList, $dayStartTime, $dayEndTime);
                            $numList = $this->groupByQrcodeId($list__, $qrcodeIds);
                            $isExists = $this->checkEachQrcodeHasTimeData($numList, $dayStartTime, $dayEndTime);

                            if ($isExists == 'false') {
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                } elseif ($v['record_type'] == 3) {
                    if (empty($v['record_time'])) {
                        $fullDate = $this->getLastDayOfMonth($vv['fullMonth']);
                        $startTime = strtotime($vv['fullMonth'] . '-01 00:00:00');
                        $endTime = strtotime($fullDate . ' 23:59:59');

                        $list__ = $this->filterLogsByTimeRange($currentLogList, $startTime, $endTime);
                        $vv['isInspection'] = !empty($list__) ? 1 : 2;
                    } else {
                        $dayRange = explode(',', $v['record_time']);
                        $vv['isInspection'] = 1;

                        foreach ($dayRange as $rangeItem) {
                            $dayStartTime = strtotime($vv['fullMonth'] . '-' . sprintf('%02d', $rangeItem) . ' ' . $v['start_time']);
                            $dayEndTime = strtotime($vv['fullMonth'] . '-' . sprintf('%02d', $rangeItem) . ' ' . $v['end_time']);

                            $list__ = $this->filterLogsByTimeRange($currentLogList, $dayStartTime, $dayEndTime);
                            $numList = $this->groupByQrcodeId($list__, $qrcodeIds);
                            $isExists = $this->checkEachQrcodeHasTimeData($numList, $dayStartTime, $dayEndTime);

                            if ($isExists == 'false') {
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                }
            }
            unset($vv);

            $total_num = count($v['date_list']);
            $inspectionArr = array_column($v['date_list'], 'isInspection', null);
            $inspectionArr = array_map(function ($val) {
                return ($val === 1 || $val === '1') ? 1 : 0;
            }, $inspectionArr);

            $inspectin_num = array_sum($inspectionArr);
            $v['inspectin_num'] = $inspectin_num;
            $v['total_num'] = $total_num;
            $v['complete_rate'] = $total_num > 0 ? round(($inspectin_num / $total_num) * 100, 2) : 0;
            unset($v['date_list']);
        }
        unset($v);

        return $cateList;
    }

    /**
     * 从内存中过滤某个时间范围内的日志
     */
    private function filterLogsByTimeRange($logList, $startTime, $endTime)
    {
        if (empty($logList)) {
            return [];
        }

        return array_values(array_filter($logList, function ($item) use ($startTime, $endTime) {
            $addTime = $item['add_time'] ?? 0;
            return $addTime >= $startTime && $addTime <= $endTime;
        }));
    }

    function getAbnormalAmount($cateList, $villageId, $startDate, $endDate)
    {
        $qrcodeRecordLogDb = new WisdomQrcodeRecordLog();
        foreach ($cateList as $cateK => &$cateV) {
            $log_where = [];
            $log_where[] = ['cate_id', '=', $cateV['id']];
            $log_where[] = ['village_id', '=', $villageId];
            $log_where[] = ['is_abnormal', '=', '1'];
            $startTime = strtotime($startDate . ' 00:00:00');
            $endTime = strtotime($endDate . ' 23:59:59');
            $log_where[] = ['add_time', 'between', [$startTime, $endTime]];
            $abnormal_list = $qrcodeRecordLogDb->where($log_where)->select()->toArray();
            $cateV['abnormal_amount'] = count( $abnormal_list );
            $cateV['abnormal_repeat_amount'] = $this->countRepeatQrcodeId($abnormal_list);
        }
        unset($cateV);
        return $cateList;
    }

    function getOverrideRate($cateList, $villageId,$startDate,$endDate)
    {
        $qrcodeRecordLogDb = new WisdomQrcodeRecordLog();
        foreach ($cateList as $cateK => &$cateV) {
            $log_where = [];
            $log_where[] = ['village_id', '=', $villageId];
            $startTime = strtotime($startDate . ' 00:00:00');
            $endTime = strtotime($endDate . ' 23:59:59');
            $log_where[] = ['add_time', 'between', [$startTime, $endTime]];
            $inspection_list = $qrcodeRecordLogDb->where($log_where)->select()->toArray();
            $should_be_inspection = json_decode(htmlspecialchars_decode($cateV['bind_qrcode'] ?? '[]'), true);
            $should_be_inspection_qrcode = count( $should_be_inspection );

            //计算总应巡检
            if( $cateV['record_type'] == 1){
                $diff =  abs(strtotime($endDate) - strtotime($startDate)) / 86400;
                $diff = $diff ? $diff : 1;
                $inspection_list = $this->uniqueRecordByCatePeriodQrcodeVillage($cateV, $inspection_list, 1);
            }else if( $cateV['record_type'] == 2){
                $should_be_inspection_current_week = explode( ',',$cateV['record_time'] );
                $should_be_inspection_total = count( $this->findDatesByWeekdays([$startDate,$endDate],$should_be_inspection_current_week) );
                $diff = $should_be_inspection_total ? $should_be_inspection_total : 1;
                $inspection_list = $this->uniqueRecordByCatePeriodQrcodeVillage($cateV, $inspection_list, 2);
            }else if( $cateV['record_type'] == 3){
                $should_be_inspection_current_month = explode( ',',$cateV['record_time'] );
                $should_be_inspection_total = count( $this->findDatesByDayNums([$startDate,$endDate],$should_be_inspection_current_month) );
                $diff = $should_be_inspection_total ? $should_be_inspection_total : 1;
                $inspection_list = $this->uniqueRecordByCatePeriodQrcodeVillage($cateV, $inspection_list, 3);
            }else{
                $diff = 1;
                $inspection_list = $this->uniqueRecordByCatePeriodQrcodeVillage($cateV, $inspection_list, 1);
            }

            $should_be_inspection_amount = $diff * $should_be_inspection_qrcode;
            $actual_inspection_amount = $inspection_list['data'] ? count( $inspection_list['data'] ) : 0;
            $cateV['should_be_inspection_amount'] = $should_be_inspection_amount;
            $cateV['actual_inspection_amount'] = $actual_inspection_amount;
            if( $should_be_inspection_amount == 0 || $actual_inspection_amount == 0){
                $cateV['override_rate'] = 0;
            }else{
                $cateV['override_rate'] = round(  ( $cateV['actual_inspection_amount'] / $cateV['should_be_inspection_amount'] ) * 100 , 2);
            }
        }
        unset($cateV);
        return $cateList;
    }

    /**
     * 按id获取两个数组交集并合并不同字段
     * @param array $arr1 原数组1（子项为含id的关联数组）
     * @param array $arr2 原数组2（子项为含id的关联数组）
     * @param bool $arr2CoversArr1 重复字段是否让arr2覆盖arr1（true=arr2覆盖，false=保留arr1）
     * @return array 交集合并后的新数组（索引保持连续）
     */
    function mergeArrByIntersectId(array $arr1, array $arr2, bool $arr2CoversArr1 = true): array
    {
        $result = [];
        // 步骤1：将arr1重构为「id=>数组项」的关联数组，方便快速匹配
        $arr1Map = [];
        foreach ($arr1 as $item) {
            // 过滤无id的无效项
            if (isset($item['id']) && $item['id'] !== '') {
                $arr1Map[$item['id']] = $item;
            }
        }
        if (empty($arr1Map)) return $result;

        // 步骤2：遍历arr2，按id匹配交集并合并字段
        foreach ($arr2 as $item) {
            if (!isset($item['id']) || $item['id'] === '' || !isset($arr1Map[$item['id']])) {
                continue; // 无id/非交集，直接跳过
            }
            $id = $item['id'];
            // 合并字段：arr2覆盖arr1 或 arr1保留优先，用+会保留左侧数组的重复字段，array_merge会覆盖左侧
            $mergeItem = $arr2CoversArr1
                ? array_merge($arr1Map[$id], $item)
                : array_merge($item, $arr1Map[$id]);
            $result[] = $mergeItem;
        }
        foreach ($result as &$item) {
            unset($item['date_list']);
        }
        unset($item);
        return $result;
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
     * @param int $weekNum 目标星期数（1=周一，2=周二...6=周六，7=周日）【已适配你的定义】
     * @return array 符合条件的日期列表（格式：Y-m-d），无匹配返回空数组
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

        // 新增：校验星期数入参（仅允许1-7，防止传非法值）
        if ($weekNum < 1 || $weekNum > 7) {
            return [];
        }

        // 3. 初始化结果数组【修复：改为空数组，而非null】
        $targetDates = [];
        $currentDate = clone $startDate;

        // 4. 遍历区间内的所有日期，筛选目标星期【核心修复星期数匹配逻辑】
        while ($currentDate <= $endDate) {
            // 获取PHP默认星期数（0=周日，1=周一...6=周六）
            $currentWeekNum = (int)$currentDate->format('w');
            // 转换为你的业务星期数（0→7，1-6保持不变），实现匹配
            $bizWeekNum = $currentWeekNum === 0 ? 7 : $currentWeekNum;

            // 匹配目标星期数，追加到结果数组【修复：用[]追加，而非覆盖】
            if ($bizWeekNum == $weekNum) {
                $targetDates[] = $currentDate->format('Y-m-d');
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
     * 统计数组中qrcode_id重复2次及以上的数量
     * @param array $data 原始多维数组（格式如你提供的array(11)结构）
     * @return int 重复2次及以上的qrcode_id的个数
     */
    function countRepeatQrcodeId(array $data): int
    {
        // 1. 处理空数组，直接返回0
        if (empty($data)) {
            return 0;
        }

        // 2. 遍历数组，收集所有有效的qrcode_id（过滤非int/非数字的无效值）
        $qrcodeIdArr = [];
        foreach ($data as $item) {
            // 确保子元素是数组，且存在qrcode_id键，且值为有效数字（int/正整数）
            if (is_array($item) && isset($item['qrcode_id']) && is_numeric($item['qrcode_id']) && $item['qrcode_id'] > 0) {
                $qrcodeIdArr[] = (int)$item['qrcode_id'];
            }
        }

        // 3. 处理无有效qrcode_id的情况，返回0
        if (empty($qrcodeIdArr)) {
            return 0;
        }

        // 4. 统计每个qrcode_id出现的次数（array_count_values：键为qrcode_id，值为出现次数）
        $countRes = array_count_values($qrcodeIdArr);

        // 5. 筛选出出现次数>=2的qrcode_id，统计其个数
        $repeatCount = 0;
        foreach ($countRes as $num => $times) {
            if ($times >= 2) {
                $repeatCount++;
            }
        }

        return $repeatCount;
    }

    /**
     * 从时间区间中找出多个指定星期几的所有日期
     * 兼容：星期数为字符串数字（如"3"/"7"），自动转int校验
     * @param array $weekRangeArr 时间区间数组 [起始日期, 结束日期]，格式：Y-m-d（string）
     * @param array $weekNums 目标星期数数组（1=周一，2=周二...6=周六，7=周日），支持int/字符串数字（如[3,"4","5",7]）
     * @return array 符合条件的日期列表（格式：Y-m-d，按时间正序排列），无匹配返回空数组
     */
    function findDatesByWeekdays($weekRangeArr, $weekNums) {
        // 1. 基础入参校验：区间必须是2个元素、星期数必须是数组
        if (count($weekRangeArr) != 2 || !is_array($weekNums)) {
            return [];
        }
        $startDateStr = $weekRangeArr[0];
        $endDateStr = $weekRangeArr[1];

        // 2. 日期格式校验：转换为DateTime对象，格式错误直接返回空
        $startDate = DateTime::createFromFormat('Y-m-d', $startDateStr);
        $endDate = DateTime::createFromFormat('Y-m-d', $endDateStr);
        if (!$startDate || !$endDate) {
            return [];
        }

        // 3. 星期数数组校验【核心优化：兼容字符串数字，自动转int+过滤非法值】
        $validWeekNums = array_filter($weekNums, function($num) {
            // 步骤1：字符串数字转int（纯数字字符串如"3"转3，非数字转0）
            $intNum = is_numeric($num) ? (int)$num : 0;
            // 步骤2：仅保留1-7的有效整数
            return $intNum >= 1 && $intNum <= 7;
        });
        // 遍历转换：把过滤后的元素（可能还是字符串）统一转为int，方便后续in_array匹配
        $validWeekNums = array_map(function($num) {
            return (int)$num;
        }, $validWeekNums);
        // 若无有效星期数，直接返回空
        if (empty($validWeekNums)) {
            return [];
        }

        // 4. 初始化结果数组，克隆起始日期（避免修改原对象）
        $targetDates = [];
        $currentDate = clone $startDate;

        // 5. 遍历日期区间，筛选匹配多个星期数的日期
        while ($currentDate <= $endDate) {
            // PHP默认星期数（0=周日，1=周一...6=周六）转换为业务定义（0→7，1-6不变）
            $currentWeekNum = (int)$currentDate->format('w');
            $bizWeekNum = $currentWeekNum === 0 ? 7 : $currentWeekNum;

            // 匹配有效星期数数组中的任意一个，追加到结果
            if (in_array($bizWeekNum, $validWeekNums)) {
                $targetDates[] = $currentDate->format('Y-m-d');
            }

            // 日期+1天，继续遍历
            $currentDate->modify('+1 day');
        }

        return $targetDates;
    }

    /**
     * 从时间区间中找出多个指定日期号的所有日期
     * 兼容：日期号为字符串数字（如"3"/"7"），自动转int校验；日期区间为Y-m-d字符串数组
     * @param array $dateRangeArr 时间区间数组 [起始日期, 结束日期]，格式：Y-m-d（string）
     * @param array $dayNums 目标日期号数组（1-31），支持string/int型数字（如["3","4","5",7]）
     * @return array 符合条件的日期列表（格式：Y-m-d，按时间正序排列），无匹配/入参错误返回空数组
     */
    function findDatesByDayNums($dateRangeArr, $dayNums) {
        // 1. 基础入参校验：区间必须是2个元素、日期号必须是数组
        if (count($dateRangeArr) != 2 || !is_array($dayNums)) {
            return [];
        }
        $startDateStr = $dateRangeArr[0];
        $endDateStr = $dateRangeArr[1];

        // 2. 日期格式校验：转换为DateTime对象，非Y-m-d格式直接返回空
        $startDate = DateTime::createFromFormat('Y-m-d', $startDateStr);
        $endDate = DateTime::createFromFormat('Y-m-d', $endDateStr);
        if (!$startDate || !$endDate) {
            return [];
        }

        // 3. 日期号数组校验：兼容字符串数字+自动转int+过滤1-31外的非法值
        $validDayNums = array_filter($dayNums, function($num) {
            $intNum = is_numeric($num) ? (int)$num : 0; // 字符串数字转int，非数字转0
            return $intNum >= 1 && $intNum <= 31; // 日期号仅保留1-31的有效整数
        });
        $validDayNums = array_map(function($num) {
            return (int)$num; // 统一转为int，避免类型不匹配导致匹配失败
        }, $validDayNums);
        if (empty($validDayNums)) {
            return []; // 无有效日期号，直接返回空
        }

        // 4. 初始化结果数组，克隆起始日期（避免修改原DateTime对象）
        $targetDates = [];
        $currentDate = clone $startDate;

        // 5. 遍历日期区间，筛选匹配指定日期号的日期
        while ($currentDate <= $endDate) {
            $currentDayNum = (int)$currentDate->format('d'); // 获取当前日期的日期号（1-31，d自动补0但转int后无影响）
            // 匹配有效日期号数组，追加到结果
            if (in_array($currentDayNum, $validDayNums)) {
                $targetDates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->modify('+1 day'); // 日期+1天，继续遍历
        }

        return $targetDates;
    }

    /**
     * 针对单个 cate 的巡检记录去重
     *
     * 去重维度：
     * period(日/周/月) + qrcode_id + village_id
     *
     * 只处理当前 cate_id 的数据
     * 每组最多保留条数 = 当前 cate 的 record_time 按逗号拆分后的数量
     *
     * @param array $cate 单个分类数据
     * @param array $recordList 巡检记录列表
     * @param int $status 1=自然日 2=自然周 3=自然月
     * @return array
     */
    function uniqueRecordByCatePeriodQrcodeVillage(array $cate, array $recordList, int $status): array
    {
        if (empty($cate) || empty($recordList)) {
            return [
                'data' => [],
                'date_list' => [],
            ];
        }

        $cateId = (int)($cate['id'] ?? 0);
        if ($cateId <= 0) {
            return [
                'data' => [],
                'date_list' => [],
            ];
        }

        // 当前分类每个周期最多保留几条
        $recordTime = trim((string)($cate['record_time'] ?? ''));
        $timeList = array_values(array_filter(array_map('trim', explode(',', $recordTime))));
        $limit = count($timeList) ?: 1;

        // 只取当前 cate_id 的记录
        $currentRecordList = array_filter($recordList, function ($item) use ($cateId) {
            return (int)($item['cate_id'] ?? 0) === $cateId;
        });

        if (empty($currentRecordList)) {
            return [
                'data' => [],
                'date_list' => [],
            ];
        }

        // 按时间升序，保留最早的几条
        usort($currentRecordList, function ($a, $b) {
            return (int)($a['add_time'] ?? 0) <=> (int)($b['add_time'] ?? 0);
        });

        $groupCount = [];
        $result = [];
        $dateList = [];

        foreach ($currentRecordList as $item) {
            $addTime = (int)($item['add_time'] ?? 0);
            if ($addTime <= 0) {
                continue;
            }

            $qrcodeId = $item['qrcode_id'] ?? '';
            $villageId = $item['village_id'] ?? '';

            // 按状态生成日期分组
            if ($status == 1) {
                $periodKey = date('Y-m-d', $addTime);
            } elseif ($status == 2) {
                $periodKey = date('o-W', $addTime);
            } elseif ($status == 3) {
                $periodKey = date('Y-m', $addTime);
            } else {
                $periodKey = date('Y-m-d', $addTime);
            }

            // 最终分组键：日期 + qrcode_id + village_id
            $groupKey = $periodKey . '_' . (string)$qrcodeId . '_' . (string)$villageId;

            if (!isset($groupCount[$groupKey])) {
                $groupCount[$groupKey] = 0;
                $dateList[$groupKey] = [
                    'period_key' => $periodKey,
                    'qrcode_id' => $qrcodeId,
                    'village_id' => $villageId,
                    'group_key' => $groupKey,
                ];
            }

            if ($groupCount[$groupKey] < $limit) {
                $result[] = $item;
                $groupCount[$groupKey]++;
            }
        }

        return [
            'data' => array_values($result),
            'date_list' => array_values($dateList),
        ];
    }

    public function getExpenseTableData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;

        if ($propertyId <= 0) {
            return api_output_error(1001, '登录信息丢失, 请重新登录');
        }

        $serviceHouseVillage = new HouseVillageService();

        // 获取物业下所有小区
        $villageList = $serviceHouseVillage->getList(
            ['property_id' => $propertyId],
            'village_id,village_name'
        )->toArray();

        sort($villageList);
        if (empty($villageList)) {
            return api_output_error(1001, '该物业没有小区');
        }

        $result = [];

        // 初始化总计
        $total = [
            'project_id' => '',
            'project_name' => '总计',

            'currentMonthTotalExpend' => '0万元',
            'currentYearTotalExpend'  => '0万元',
            'lastYearTotalExpend'     => '0万元',
            'totalBudget'             => '0万元',
            'yearGrowth'              => 0,

            'currentMonthTotalExpend_num' => 0,
            'currentYearTotalExpend_num'  => 0,
            'lastYearTotalExpend_num'     => 0,
            'totalBudget_num'             => 0,
        ];

        foreach ($villageList as $village) {
            $villageId = intval($village['village_id']);

            $info = $this->getExpendData([
                'village_id' => $villageId,
            ]);

            $info['project_id'] = $villageId;
            $info['project_name'] = $village['village_name'] ?? '';

            $result[] = $info;

            // 汇总原始数值
            $total['currentMonthTotalExpend_num'] += $info['currentMonthTotalExpend_num'];
            $total['currentYearTotalExpend_num']  += $info['currentYearTotalExpend_num'];
            $total['lastYearTotalExpend_num']     += $info['lastYearTotalExpend_num'];
            $total['totalBudget_num']             += $info['totalBudget_num'];
        }

        // 统一格式化总计
        $total['currentMonthTotalExpend'] = $this->formatAmountToWan($total['currentMonthTotalExpend_num']) . '万元';
        $total['currentYearTotalExpend']  = $this->formatAmountToWan($total['currentYearTotalExpend_num']) . '万元';
        $total['lastYearTotalExpend']     = $this->formatAmountToWan($total['lastYearTotalExpend_num']) . '万元';
        $total['totalBudget']             = $this->formatAmountToWan($total['totalBudget_num']) . '万元';

        // 用原始数值计算同比/增长率
        $total['yearGrowth'] = $this->calcGrowthRate(
            $total['currentYearTotalExpend_num'],
            $total['lastYearTotalExpend_num']
        );

        array_unshift($result, $total);

        return api_output(1000, $result);
    }

    /**
     * 获取项目支出情况
     */
    public function getExpendData($params)
    {
        $villageId = intval($params['village_id'] ?? 0);
        if ($villageId <= 0) {
            return [
                'currentMonthTotalExpend' => '0万元',
                'currentYearTotalExpend'  => '0万元',
                'lastYearTotalExpend'     => '0万元',
                'totalBudget'             => '0万元',
                'currentMonthTotalExpend_num' => 0,
                'currentYearTotalExpend_num'  => 0,
                'lastYearTotalExpend_num'     => 0,
                'totalBudget_num'             => 0,
                'yearGrowth' => 0,
            ];
        }

        $currentMonthStart = strtotime(date('Y-m-01 00:00:00'));
        $currentMonthEnd   = strtotime(date('Y-m-t 23:59:59'));

        $currentYearStart  = strtotime(date('Y-01-01 00:00:00'));
        $currentYearEnd    = strtotime(date('Y-12-31 23:59:59'));

        $lastYearStart     = strtotime((date('Y') - 1) . '-01-01 00:00:00');
        $lastYearEnd       = strtotime((date('Y') - 1) . '-12-31 23:59:59');

        // 支出
        $currentMonthTotalExpend = $this->sumVillageBillPriceByTimeRange($villageId, $currentMonthStart, $currentMonthEnd);
        $currentYearTotalExpend  = $this->sumVillageBillPriceByTimeRange($villageId, $currentYearStart, $currentYearEnd);
        $lastYearTotalExpend     = $this->sumVillageBillPriceByTimeRange($villageId, $lastYearStart, $lastYearEnd);

        // 年预算
        $totalBudget = $this->sumVillageBudgetByYear($villageId, date('Y'));

        // 年累计增长率
        $yearGrowth = $this->calcGrowthRate($currentYearTotalExpend, $lastYearTotalExpend);

        return [
            'currentMonthTotalExpend' => $this->formatAmountToWan($currentMonthTotalExpend) . '万元',
            'currentYearTotalExpend'  => $this->formatAmountToWan($currentYearTotalExpend) . '万元',
            'lastYearTotalExpend'     => $this->formatAmountToWan($lastYearTotalExpend) . '万元',
            'totalBudget'             => $this->formatAmountToWan($totalBudget) . '万元',

            'currentMonthTotalExpend_num' => $currentMonthTotalExpend,
            'currentYearTotalExpend_num'  => $currentYearTotalExpend,
            'lastYearTotalExpend_num'     => $lastYearTotalExpend,
            'totalBudget_num'             => $totalBudget,

            'yearGrowth' => $yearGrowth,
        ];
    }

    /**
     * 统计某小区某时间范围内支出总额
     */
    private function sumVillageBillPriceByTimeRange($villageId, $startTime, $endTime)
    {
        $where = [];
        $where[] = ['vb.village_id', '=', $villageId];
        $where[] = ['vb.payment_time', 'between', [$startTime, $endTime]];

        $list = (new VillageBill())->getListAll($where, 'vb.price', 1, 999999999)->toArray();

        return array_reduce($list, function ($carry, $item) {
            return $carry + (float)($item['price'] ?? 0);
        }, 0);
    }

    /**
     * 统计某小区某年预算总额
     */
    private function sumVillageBudgetByYear($villageId, $year)
    {
        $where = [];
        $where[] = ['vb.village_id', '=', $villageId];
        $where[] = ['vb.month', 'between', [$year . '-01', $year . '-12']];

        $budgetList = (new BudgetManagementService())->getBudgetList(
            $where,
            'vb.amount',
            false,
            1,
            999999999
        );

        return array_reduce($budgetList, function ($carry, $item) {
            return $carry + (float)($item['amount'] ?? 0);
        }, 0);
    }

    /**
     * 计算增长率
     * 公式：(现期 - 基期) / 基期 * 100
     */
    private function calcGrowthRate($currentValue, $baseValue)
    {
        $currentValue = (float)$currentValue;
        $baseValue = (float)$baseValue;

        if ($baseValue <= 0) {
            return 0;
        }

        return round((($currentValue - $baseValue) / $baseValue) * 100, 2);
    }

    /**
     * 将账单原始数据按分类求和后转换为旭日图所需格式（仅数据结构，无颜色配置）
     * @param array $rawData 原始账单数组
     * @param bool $showDetail 是否显示分类下的具体账单（true=显示，false=仅显示汇总）
     * @return array 纯数据结构的旭日图适配数据
     */
    function convertToSunburstData($rawData, $showDetail = true) {
        // 1. 第一步：按分类分组并求和
        $categorySum = []; // 分类汇总数据
        $categoryDetails = []; // 分类下的具体账单（用于多层展示）

        foreach ($rawData as $item) {
            $categoryName = $item['category_name'] ?? '未分类';
            $price = floatval($item['price'] ?? 0); // 转浮点型避免计算错误

            // 汇总该分类的总金额
            if (!isset($categorySum[$categoryName])) {
                $categorySum[$categoryName] = 0;
                $categoryDetails[$categoryName] = [];
            }
            $categorySum[$categoryName] += $price;

            // 记录该分类下的具体账单（用于子节点）
            if ($showDetail) {
                $categoryDetails[$categoryName][] = [
                    'name' => $item['title'],
                    'value' => $price,
                    'id' => $item['id'] // 保留账单ID，方便前端扩展
                ];
            }
        }

        // 2. 第二步：组装纯数据结构的旭日图格式（无任何颜色配置）
        $sunburstData = [];
        foreach ($categorySum as $categoryName => $totalPrice) {
            // 核心层：分类名称 + 汇总金额
            $categoryItem = [
                'name' => $categoryName,
                'value' => $totalPrice, // 汇总金额作为节点数值（决定区块大小）
                'children' => []
            ];

            $sunburstData[] = $categoryItem;
        }

        return $sunburstData;
    }

    public function getAllUtilitiesData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;

        if ($propertyId <= 0) {
            return api_output_error(1001, '登录信息丢失, 请重新登录');
        }

        $currentCharTitleType = trim((string)($this->request->post('currentCharTitleType') ?? $this->request->post('currentCharTitleType') ?? ''));
        if ($currentCharTitleType === '') {
            return api_output_error(1001, '缺少参数 currentCharTitleType');
        }

        $serviceHouseVillage = new HouseVillageService();

        $villageList = $serviceHouseVillage->getList(
            ['property_id' => $propertyId],
            'village_id,village_name'
        )->toArray();

        if (empty($villageList)) {
            return api_output_error(1001, '该物业没有小区');
        }

        usort($villageList, function ($a, $b) {
            return ($a['village_id'] ?? 0) <=> ($b['village_id'] ?? 0);
        });

        $param = [
            'village_id' => array_column($villageList, 'village_id'),
            'startDate' => $_GET['startDate'] ?? $_POST['startDate'] ?? '',
            'endDate' => $_GET['endDate'] ?? $_POST['endDate'] ?? '',
            'currentCharTitleType' => $currentCharTitleType,
        ];

        $list = $this->getUtilitiesData($param);
        return api_output(1000, $list);
    }

    public function getUtilitiesData($params)
    {
        $villageId = $params['village_id'] ?? [];
        $startDate = $params['startDate'] ?? '';
        $endDate = $params['endDate'] ?? '';
        $currentCharTitleType = trim((string)($params['currentCharTitleType'] ?? ''));

        [$startDate, $endDate] = $this->completeDateRange($startDate, $endDate, 12);

        if (empty($villageId)) {
            return [
                'chartData' => [],
                'seriesList' => [],
                'legendList' => [],
            ];
        }

        if (!in_array($currentCharTitleType, ['水', '电'], true)) {
            return [
                'chartData' => [],
                'seriesList' => [],
                'legendList' => [],
            ];
        }

        $db_utilities_log = new UtilitiesLog();

        $where = [];
        $where[] = ['ul.village_id','in',$villageId];

        if (!empty($startDate) && !empty($endDate)) {
            $startMonth = date('Y-m', strtotime($startDate));
            $endMonth = date('Y-m', strtotime($endDate));
            $where[] = ['ul.add_date', 'between', [$startMonth, $endMonth]];
        }

        $list = $db_utilities_log
            ->getListByType($where, 'ul.*,uc.name,hv.village_name', 1, 99999999)
            ->toArray();

        $dateRange = $this->getMonthListBetweenDates($startDate, $endDate);

        return $this->buildUtilitiesStackChartData($dateRange, $list, $currentCharTitleType);
    }

    function buildUtilitiesStackChartData(array $monthList, array $sourceList, string $currentCharTitleType): array
    {
        $villageMap = [];

        $waterColors = [
            '#00F5FF',
            '#19D4FF',
            '#00E5FF',
            '#36CFC9',
            '#13C2C2',
            '#4DA3FF',
            '#00E5A8',
            '#64FFDA',
            '#00BFFF',
            '#2F54EB',
        ];

        $electricColors = [
            '#FF6B8B',
            '#FF4DCE',
            '#D946EF',
            '#9254DE',
            '#722ED1',
            '#FF7A45',
            '#FF9F1A',
            '#FF7849',
            '#FF3D71',
            '#F759AB',
        ];

        $isWater = $currentCharTitleType === '水';
        $fieldPrefix = $isWater ? 'water_' : 'electric_';
        $feeName = $isWater ? '水费' : '电费';
        $yAxisKey = $isWater ? 'y1' : 'y2';
        $stackKey = $isWater ? 'water' : 'electric';
        $colorList = $isWater ? $waterColors : $electricColors;

        // 1. 提取小区
        foreach ($sourceList as $item) {
            $villageId = (int)($item['village_id'] ?? 0);
            if ($villageId <= 0) {
                continue;
            }

            $villageName = trim((string)($item['village_name'] ?? ''));
            if ($villageName === '') {
                $villageName = '项目' . $villageId;
            }

            $villageMap[$villageId] = $villageName;
        }

        ksort($villageMap);

        // 2. 初始化 chartData
        $chartData = [];
        foreach ($monthList as $month) {
            $row = [
                'month' => $month,
            ];

            foreach ($villageMap as $villageId => $villageName) {
                $row[$fieldPrefix . $villageId] = 0;
            }

            $chartData[$month] = $row;
        }

        // 3. 汇总数据
        foreach ($sourceList as $item) {
            $month = (string)($item['add_date'] ?? '');
            $villageId = (int)($item['village_id'] ?? 0);
            $name = trim((string)($item['name'] ?? ''));
            $value = (float)($item['consumption'] ?? 0);

            if ($month === '' || $villageId <= 0 || !isset($chartData[$month])) {
                continue;
            }

            if ($isWater && mb_strpos($name, '水') !== false) {
                $chartData[$month][$fieldPrefix . $villageId] += $value;
            }

            if (!$isWater && mb_strpos($name, '电') !== false) {
                $chartData[$month][$fieldPrefix . $villageId] += $value;
            }
        }

        // 4. 保留两位小数
        foreach ($chartData as &$row) {
            foreach ($row as $key => $val) {
                if ($key !== 'month') {
                    $row[$key] = round((float)$val, 2);
                }
            }
        }
        unset($row);

        // 5. 生成 seriesList：name 改成小区名，供 legend 按小区筛选
        $seriesList = [];
        $legendList = [];
        $index = 0;

        foreach ($villageMap as $villageId => $villageName) {
            $seriesList[] = [
                'key' => $fieldPrefix . $villageId,
                'groupName' => $villageName,
                'name' => $villageName,          // 关键：legend 按小区筛选
                'villageName' => $villageName,
                'tooltipName' => $feeName,       // 关键：tooltip 里显示 水费/电费
                'type' => 'bar',
                'yAxisKey' => $yAxisKey,
                'field' => $fieldPrefix . $villageId,
                'stack' => $stackKey,
                'color' => $colorList[$index % count($colorList)],
                'showLabel' => true,
                'barWidth' => 10,
                'barGap' => '0%',
            ];

            $legendList[] = [
                'key' => $fieldPrefix . $villageId,
                'name' => $villageName,
            ];

            $index++;
        }

        return [
            'chartData' => array_values($chartData),
            'seriesList' => $seriesList,
            'legendList' => $legendList,
            'currentType' => $currentCharTitleType,
        ];
    }
    /**
     * 补全时间区间内的所有年月（YYYY-MM格式）
     * @param string $startDate 开始日期（如2025-02-27）
     * @param string $endDate 结束日期（如2026-02-27）
     * @return array 年月列表（['2025-02', '2025-03', ..., '2026-02']）
     */
    function getMonthListBetweenDates($startDate, $endDate) {
        // 初始化返回数组
        $monthList = [];

        // 解析开始/结束日期为DateTime对象
        $start = DateTime::createFromFormat('Y-m-d', $startDate);
        $end = DateTime::createFromFormat('Y-m-d', $endDate);

        // 校验日期格式是否合法
        if (!$start || !$end) {
            return $monthList;
        }

        // 确保开始日期≤结束日期
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        // 循环生成每个月的年月
        $current = clone $start;
        while ($current <= $end) {
            // 格式化为YYYY-MM
            $month = $current->format('Y-m');
            $monthList[] = $month;

            // 月份+1（关键：自动处理跨年，如2025-12→2026-01）
            $current->modify('+1 month');
        }

        return $monthList;
    }

}