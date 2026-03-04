<?php


namespace app\community\controller\village_api;
use app\common\model\service\config\ConfigCustomizationService;
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
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargeProjectService;
use app\community\model\service\HouseNewChargeRuleService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\NewBuildingService;
use app\community\model\service\PropertyFrameworkService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageService;
use DateTime;
use think\facade\Cache;

class StatisticalController extends CommunityBaseController
{

    /**
     * Notes: 获取配置
     * @return \json
     * @author: wanzy
     * @date_time: 2020/9/2 21:42
     */
    public function config() {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $arr = [];
        $arr['config_site_name'] = cfg('config_site_name');
        $service_house_village = new HouseVillageService();
        // 小区信息
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_logo');
        $arr['village_info'] = $now_village;
        // 系统后台LOGO
        if (cfg('system_admin_logo')) {
            $system_admin_logo = cfg('system_admin_logo');
            $system_admin_logo = replace_file_domain($system_admin_logo);
        } else {
            $system_admin_logo = cfg('site_url') . "/tpl/System/Static/images/pigcms_logo.png";
        }
        if($now_village['village_logo']){
            $system_admin_logo = replace_file_domain($now_village['village_logo']);
        }
        $arr['system_admin_logo'] = $system_admin_logo;
        $House_village_info_service = new HouseVillageInfo();
        $village_about_info = $House_village_info_service->getOne(array('village_id'=>$village_id))->toArray();
        $param=array(
            'village_id'=>$village_id,
        );
        $list = (new NewBuildingService())->getRoomDataList($param);
        $ruzhu = $this->countUserStatus($list['list'],'业主入住');
        $chuzu = $this->countUserStatus($list['list'],'租客入住');
        $village_about_info['total_num'] = count($list['list']);
        $village_about_info['occupancy_num'] = $ruzhu;
        $village_about_info['occupancy_rate'] = round( $ruzhu / count($list['list']) ,1) * 100;
        $village_about_info['rental_num'] = $chuzu;
        $village_about_info['rental_rate'] =  round( $chuzu / count($list['list']) ,1) * 100;

        $arr['project_info'] = $village_about_info;
        return api_output(0,$arr);
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
    public function getMyOrgList()
    {
        // -------------- 第一步：获取基础参数（做空值容错，避免参数未定义报错）--------------
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $villageId = $this->adminUser['village_id'] ?? 0;
        // 非数字参数兜底，适配接口入参规范
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;
        $villageId = is_numeric($villageId) ? intval($villageId) : 0;

        // -------------- 第二步：初始化服务&获取原始组织数据（业务接口调用）--------------
        $propertyFrameworkService = new PropertyFrameworkService();
        // 调用业务导航接口，做空值兜底
        $navRes = $propertyFrameworkService->businessNav(2, $propertyId, $villageId) ?: [];
        // 提取首层菜单数据，多层空值判断，彻底避免报错
        $menuList = $navRes['menu_list'][0] ?? [];
        $deptList = $menuList['children'] ?? [];
        // 原始部门数据容器（保留原业务返回的原始数据）
        $resultList = [];

        // -------------- 第三步：遍历部门，关联人员信息（核心业务逻辑）--------------
        if (is_array($deptList) && !empty($deptList)) {
            foreach ($deptList as $dept) {
                // 部门ID空值容错，跳过无效部门
                $deptId = $dept['id'] ?? 0;
                if (empty($deptId)) {
                    continue;
                }
                // 组装人员查询参数，语义化命名
                $userQueryParam = [
                    'role_type'   => 2,
                    'property_id' => $propertyId,
                    'village_id'  => $villageId,
                    'group_id'    => $deptId,
                ];
                // 调用人员查询接口，空值兜底
                $tissueUserRes = $propertyFrameworkService->businessTissueUser($userQueryParam) ?: [];
                // 给部门追加人员信息，统一空数组兜底
                $dept['user_info'] = $tissueUserRes['list'] ?? [];
                $resultList[] = $dept;
            }
        }

        // -------------- 第四步：组装前端ECharts树图所需的myOrgData格式 --------------
        $myOrgData = [
            'name'     => $menuList['title'] ?? '智慧物业总部', // 顶层名称兜底，避免为空
            'users'    => [], // 顶层负责人，保持原逻辑空数组
            'children' => [], // 子部门容器
        ];

        // 遍历处理后的部门数据，组装子部门
        if (is_array($resultList) && !empty($resultList)) {
            foreach ($resultList as $dept) {
                $deptTitle = $dept['title'] ?? '';

                // 提取人员姓名：用array_column简化，替代嵌套foreach，性能更优
                $userInfo = $dept['user_info'] ?? [];
                $userNames = is_array($userInfo) ? array_column($userInfo, 'name') : [];
                // 过滤空姓名，避免前端显示空节点
                $userNames = array_filter($userNames, function ($name) {
                    return !empty(trim($name));
                });
                // 重置数组索引，避免前端遍历异常
                $userNames = array_values($userNames);

                // 组装子部门数据
                $myOrgData['children'][] = [
                    'name'     => $deptTitle,
                    'users'    => $userNames,
                    'children' => [], // 暂无二级部门，保持空数组
                ];
            }
        }

        // -------------- 第五步：组装最终返回数据，统一格式 --------------
        $finalData = [
            'data'       => $resultList,  // 原始部门+人员数据
            'myOrgData'  => $myOrgData,   // 前端ECharts树图专用数据
        ];

        // 标准化接口返回
        return api_output(0, $finalData);
    }

    /*
     * 获取项目基础数据
     * */
    public function getProjectBaseData()
    {
        // -------------- 1. 基础参数校验 & 初始化 --------------
        $villageId = $this->adminUser['village_id'] ?? 0;
        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }
        $villageId = intval($villageId);
        $res = ['data' => [], 'chartList' => []]; // 初始化返回数据，避免空值

        try {
            // -------------- 2. 实例化服务 & 获取小区基础数据 --------------
            $houseVillageInfoService = new HouseVillageInfo();
            // 容错：查询结果判空，避免toArray()报错
            $villageAboutInfo = $houseVillageInfoService->getOne(['village_id' => $villageId]);
            if (empty($villageAboutInfo)) {
                return api_output_error(1002, '未查询到该小区的基础信息');
            }
            // 转换为数组，框架模型对象转数组兼容
            $villageAboutInfo = $villageAboutInfo->toArray() ?: [];

            $data = [
                [
                    'name' => '住宅',
                    'yishou' => $villageAboutInfo['zhuzhai_yishou'],
                    'weishou' => $villageAboutInfo['zhuzhai_weishou'],
                    'yijiao' => $villageAboutInfo['zhuzhai_yijiao'],
                    'weijiao' => $villageAboutInfo['zhuzhai_weijiao'],
                ],
                [
                    'name' => '商铺',
                    'yishou' => $villageAboutInfo['shangpu_yishou'],
                    'weishou' => $villageAboutInfo['shangpu_weishou'],
                    'yijiao' => $villageAboutInfo['shangpu_yijiao'],
                    'weijiao' => $villageAboutInfo['shangpu_weijiao'],
                ],
                [
                    'name' => '车位',
                    'yishou' => $villageAboutInfo['chewei_yishou'],
                    'weishou' => $villageAboutInfo['chewei_weishou'],
                    'yijiao' => $villageAboutInfo['chewei_yijiao'],
                    'weijiao' => $villageAboutInfo['chewei_weijiao'],
                ],
            ];

            // -------------- 3. 核心配置：字段/名称/堆叠映射（唯一修改入口） --------------
//            $fieldMap = [
//                // 房屋类型：数据库字段前缀 => X轴显示名 + 索引
//                'type' => [
//                    'zhuzhai' => '住宅',
//                    'shangpu' => '商铺',
//                    'chewei'  => '车位'
//                ],
//                // 统计项：数据库字段后缀 => 系列名 + 堆叠分组 + 索引
//                'item' => [
//                    'yishou' => ['name' => '已售', 'stack' => 'group1'],
//                    'weishou' => ['name' => '未售', 'stack' => 'group1'],
//                    'yijiao' => ['name' => '已交', 'stack' => 'group2'],
//                    'weijiao' => ['name' => '未交', 'stack' => 'group2']
//                ]
//            ];

            // -------------- 4. 初始化ECharts图表数据结构（基于fieldMap自动生成，无需硬编码） --------------
//            $baseChartData = [
//                'xAxis'  => array_values($fieldMap['type']), // 自动提取X轴名称
//                'series' => []
//            ];
            // 按统计项自动初始化系列（名称/堆叠/默认0数据）
//            $typeCount = count($fieldMap['type']);
//            foreach ($fieldMap['item'] as $itemConf) {
//                $baseChartData['series'][] = [
//                    'name'  => $itemConf['name'],
//                    'stack' => $itemConf['stack'],
//                    'data'  => array_fill(0, $typeCount, 0) // 按类型数量填充默认0，避免错位
//                ];
//            }
//
//            // -------------- 5. 建立快速索引（基于fieldMap，后续扩展无需改索引） --------------
//            $typeKeyToIdx = array_flip(array_keys($fieldMap['type'])); // zhuzhai=>0, shangpu=>1, chewei=>2
//            $itemKeyToIdx = array_flip(array_keys($fieldMap['item'])); // yishou=>0, weishou=>1...
//
//            // -------------- 6. 遍历映射提取数据（全容错，适配字段缺失/非数字场景） --------------
//            foreach ($fieldMap['type'] as $typeKey => $typeName) {
//                foreach ($fieldMap['item'] as $itemKey => $itemConf) {
//                    $fieldName = $typeKey . '_' . $itemKey; // 拼接数据库字段名
//                    // 严格容错：字段存在+是有效数字才赋值，否则保留0
//                    $fieldValue = 0;
//                    if (isset($villageAboutInfo[$fieldName]) && is_numeric($villageAboutInfo[$fieldName])) {
//                        $fieldValue = intval($villageAboutInfo[$fieldName]);
//                    }
//                    // 赋值到对应系列的对应位置
//                    $baseChartData['series'][$itemKeyToIdx[$itemKey]]['data'][$typeKeyToIdx[$typeKey]] = $fieldValue;
//                }
//            }
//
//            // -------------- 7. 组装最终返回数据 --------------
//            $res['data'] = $villageAboutInfo;       // 小区原始基础数据
//            $res['chartList'] = $baseChartData;     // ECharts适配的图表数据

            $res['chartList'] = $data;
        } catch (\Exception $e) {
            // 异常捕获：避免服务调用/数据处理报错导致接口500
            log_write('获取小区项目基础数据异常：' . $e->getMessage(), 'error'); // 框架日志记录
            return api_output_error(500, '服务器内部错误，获取数据失败');
        }

        // 正常返回
        return api_output(0, $res);
    }

    /*
     * 获取项目巡检情况
     * */
    public function getProjectInspectionData()
    {
        date_default_timezone_set('PRC');
        // -------------- 1. 基础参数校验 & 初始化 --------------
        $villageId = $this->adminUser['village_id'] ?? 0;
        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }
        $villageId = intval($villageId);
        $startDate = $_GET['startDate'];
        $endDate = $_GET['endDate'];
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

        foreach ($list as &$v) {
            $v['chart_data'] = [
                'name' => $v['cate_name'],
                'complete_rate' => $v['complete_rate'],
                'abnormal_amount' => $v['abnormal_amount'],
                'abnormal_repeat_amount' => $v['abnormal_repeat_amount'],
                'override_rate' => $v['override_rate'],
            ];
        }
        unset($v);

        $result = [
            'data' => $list,
            'chart_list' => array_column($list, 'chart_data'),
        ];

        return api_output(0, $result);
    }

    function getInspectionCompletedRate($cateList, $villageId, $startDate, $endDate)
    {
        $where[] = ['village_id', '=', $villageId];

        $qrcodeDb = new WisdomQrcode();
        $qrcodeRecordLogDb = new WisdomQrcodeRecordLog();

        $headerMap = [
            1 => 'day',
            2 => 'week',
            3 => 'month',
        ];

        foreach ($cateList as &$cate) {
            $cate['date_list'] = $this->getHeaderList($startDate, $endDate, $headerMap[$cate['record_type']]);
        }
        unset($cate);

        foreach($cateList as $k => &$v){
            $v['complete_rate'] = 0;
            $currentCateQrcodeList = $qrcodeDb->where(['cate_id' => $v['id']])->select()->toArray();
            if( empty($currentCateQrcodeList) ){
                continue;
            }
            $qrcodeIds = array_column( $currentCateQrcodeList,'id' );
            //循环查询当前日期是否完成
            $where[] = ['qrcode_id','in',$qrcodeIds];
            $where[] = ['cate_id','in',[$v['id']]];

            foreach( $v['date_list'] as &$vv ){
                if( $v['record_type'] == 1 ){
                    //查看当前天是否区分了时间段,如果是,则查看每个时间段是否巡检,所有时间段都有巡检记录才为巡检完成, 如果没有设置时间段, 那只要当前天有记录就算完成
                    if( empty( $v['record_time'] ) ){
                        $startTime = strtotime($vv['fullDate'] . ' 00:00:00');
                        $endTime = strtotime($vv['fullDate'] . ' 23:59:59');
                        $where[] = ['add_time','between', [$startTime, $endTime]];
                        $num = $qrcodeRecordLogDb->getCount($where);
                        $vv['isInspection'] = $num > 0 ? 1 : 2;//1为完成, 2为未完成
                    }else{
                        $day_range = explode( ',',$v['record_time']);
                        //根据数组创建sql, 查看是否每个时间段都有巡检记录
                        $vv['isInspection'] = 1;
                        foreach($day_range as $dayV){
                            $dayV = explode('-', $dayV);
                            $startTime = strtotime($vv['fullDate'] .' '. $dayV[0]);
                            $endTime = strtotime($vv['fullDate'] .' '. $dayV[1]);
                            $where[] = ['add_time','between', [$startTime, $endTime]];
                            $list__ = $qrcodeRecordLogDb->where($where)->select()->toArray();
                            $num_list = $this->groupByQrcodeId($list__,$qrcodeIds);
                            $isExists = $this->checkEachQrcodeHasTimeData($num_list, $startTime, $endTime);
                            if( $isExists == 'false' ){
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                }else if( $v['record_type'] == 2 ){
                    //周任务查看是哪天需要巡检, 每天的那个时间段需要巡检,必须全部完成才算完成, 否则当前任务未完成
                    //如果数据为空, 那默认为本周有一次巡检就算完成
                    if( empty( $v['record_time'] ) ){
                        $startTime = strtotime($vv['weekRangeArr'][0] . ' 00:00:00');
                        $endTime = strtotime($vv['weekRangeArr'][1] . ' 23:59:59');
                        $where[] = ['add_time','between', [$startTime, $endTime]];
                        $num = $qrcodeRecordLogDb->getCount($where);
                        $vv['isInspection'] = $num > 0 ? 1 : 2;//1为完成, 2为未完成
                    }else{
                        $day_range = explode( ',',$v['record_time']);
                        $vv['isInspection'] = 1;
                        foreach($day_range as $rangeItem){
                            $cur_day = $this->findDateByWeekday($vv['weekRangeArr'],$rangeItem);
                            $day_start_time = strtotime($cur_day . ' ' . $v['start_time']);
                            $day_end_time = strtotime($cur_day . ' ' . $v['end_time']);
                            $where[] = ['add_time','between', [$day_start_time, $day_end_time]];
                            $list__ = $qrcodeRecordLogDb->where($where)->select()->toArray();
                            $num_list = $this->groupByQrcodeId($list__,$qrcodeIds);
                            $isExists = $this->checkEachQrcodeHasTimeData($num_list, $day_start_time, $day_end_time);
                            if( $isExists == 'false' ){
                                $vv['isInspection'] = 2;
                                break;
                            }
                        }
                    }
                }else if( $v['record_type'] == 3 ){
                    //月任务查看是哪天需要巡检, 每天的那个时间段需要巡检,必须全部完成才算完成, 否则当前任务未完成
                    //如果数据为空, 那默认为本月有一次巡检就算完成
                    if( empty( $v['record_time'] ) ){
                        $fullData = $this->getLastDayOfMonth($vv['fullMonth']);
                        $startTime = strtotime($vv['fullMonth'].'-01' . ' 00:00:00');
                        $endTime = strtotime($fullData . ' 23:59:59');
                        $where[] = ['add_time','between', [$startTime, $endTime]];
                        $num = $qrcodeRecordLogDb->getCount($where);
                        $vv['isInspection'] = $num > 0 ? 1 : 2;//1为完成, 2为未完成
                    }else{
                        $day_range = explode( ',',$v['record_time']);
                        $vv['isInspection'] = 1;
                        foreach($day_range as $rangeItem){
                            $day_start_time = strtotime($vv['fullMonth'] .'-'.sprintf('%02d', $rangeItem). ' ' . $v['start_time']);
                            $day_end_time = strtotime($vv['fullMonth'] .'-'.sprintf('%02d', $rangeItem). ' ' . $v['end_time']);
                            $where[] = ['add_time','between', [$day_start_time, $day_end_time]];
                            $list__ = $qrcodeRecordLogDb->where($where)->select()->toArray();
                            $num_list = $this->groupByQrcodeId($list__,$qrcodeIds);
                            $isExists = $this->checkEachQrcodeHasTimeData($num_list, $day_start_time, $day_end_time);
                            if( $isExists == 'false' ){
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
//             求和（1+0+1=2）
            $inspectin_num = array_sum($inspectionArr);
            $v['inspectin_num'] = $inspectin_num;
            $v['total_num'] = $total_num;
            $v['complete_rate'] = round( ($inspectin_num/$total_num) * 100,2 );
            unset($vv);
        }
        return $cateList;
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
            $inspection_list = $qrcodeRecordLogDb->where($log_where)->buildSql();
            $inspection_list = $qrcodeRecordLogDb->where($log_where)->select()->toArray();
            $should_be_inspection = json_decode(htmlspecialchars_decode($cateV['bind_qrcode'] ?? '[]'), true);
            $should_be_inspection_qrcode = count( $should_be_inspection );
            //计算总应巡检
            if( $cateV['record_type'] == 1){
                $diff =  abs(strtotime($endDate) - strtotime($startDate)) / 86400;
                $diff = $diff ? $diff : 1;
            }else if( $cateV['record_type'] == 2){
                $should_be_inspection_current_week = explode( ',',$cateV['record_time'] );
                $should_be_inspection_total = count( $this->findDatesByWeekdays([$startDate,$endDate],$should_be_inspection_current_week) );
                $diff = $should_be_inspection_total ? $should_be_inspection_total : 1;
            }else if( $cateV['record_type'] == 3){
                $should_be_inspection_current_month = explode( ',',$cateV['record_time'] );
                $should_be_inspection_total = count( $this->findDatesByDayNums([$startDate,$endDate],$should_be_inspection_current_month) );
                $diff = $should_be_inspection_total ? $should_be_inspection_total : 1;
            }else{
                $diff = 1;
            }
            $should_be_inspection_amount = $diff * $should_be_inspection_qrcode;
            $actual_inspection_amount = $inspection_list ? count( $inspection_list ) : 0;
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
     * 时间戳计算间隔天数（适配add_time等int型日期字段）
     * @param int $timestamp1 开始时间戳（秒级）
     * @param int $timestamp2 结束时间戳（秒级）
     * @param bool $abs 绝对值，默认true
     * @return int|false 间隔天数/失败false
     */
    function getTimestampDiffDays($timestamp1, $timestamp2, $abs = true)
    {
        // 过滤非数字/非正整数的时间戳（解决A non-numeric value encountered错误）
        if (!is_numeric($timestamp1) || !is_numeric($timestamp2) || $timestamp1 < 0 || $timestamp2 < 0) {
            return false;
        }
        // 强制转为int型，避免浮点型干扰
        $ts1 = (int)$timestamp1;
        $ts2 = (int)$timestamp2;

        // 一天的秒数：86400 = 24*60*60
        $oneDaySec = 86400;
        // 计算时间戳差值，再除以一天的秒数，取整
        $diffSec = $ts2 - $ts1;
        $diffDays = floor($diffSec / $oneDaySec);

        return $abs ? abs($diffDays) : $diffDays;
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

        return $result;
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
     * 获取用户使用报修,快递代取人数
     *
     * */
    public function getCustomChartData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $villageId = $this->adminUser['village_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;
        $villageId = is_numeric($villageId) ? intval($villageId) : 0;
        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }
        [$startDate, $endDate] = $this->completeDateRange($_GET['startDate'] ?? '', $_GET['endDate'] ?? '', 12);

        $where[] = ['o.village_id','=',$villageId];
//        $where[] = ['o.add_time','between',[strtotime($startDate),strtotime($endDate)]];
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
        $express_where[] = ['p.village_id' , '=' , $villageId];
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
     * 获取用户使用报修,快递代取人数
     *
     * */
    public function getWorkOrderResponseData()
    {
        $propertyId = $this->adminUser['property_id'] ?? 0;
        $villageId = $this->adminUser['village_id'] ?? 0;
        $propertyId = is_numeric($propertyId) ? intval($propertyId) : 0;
        $villageId = is_numeric($villageId) ? intval($villageId) : 0;
        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }
        [$startDate, $endDate] = $this->completeDateRange($_GET['startDate'] ?? '', $_GET['endDate'] ?? '', 12);

        $where[] = ['o.village_id','=',$villageId];
        //获取工单
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $workOrderList = $db_house_new_repair_works_order->getAllList($where)->toArray();
        if( !empty($workOrderList) ){
            foreach( $workOrderList as $k => &$v ){
                $v['add_time_text'] = date('Y-m-d', $v['add_time']);
                $log_where[] = ['order_id','=',$v['order_id']];
                $log_info = $db_house_new_repair_works_order_log->getOne($log_where,true,'add_time ASC');
                $v['first_record_time'] = $log_info['add_time']?$log_info['add_time']:'';
                $v['response_time'] = $log_info['add_time']? round(abs($log_info['add_time'] - $v['add_time'] ?? 0) / 60, 1) :0;
                $v['completed_time'] = $v['timely_time']? round( abs($v['timely_time'] - $v['add_time'] ?? 0) / 60, 1) :0;
            }
        }
        unset($v);
        $completedList = [];
        $unfinishedList = [];
        $targetStatus = [40, 60, 70];

        foreach ($workOrderList as $item) {
            // 提取event_status，处理空值/非数值情况
            $eventStatus = isset($item['event_status']) ? $item['event_status'] : '';
            // 统一转为数值型（避免字符串"10"和数字10被误判）
            $eventStatus = is_numeric($eventStatus) ? (int)$eventStatus : '';

            // 判断分组
            if (empty($eventStatus) || in_array($eventStatus, $targetStatus)) {
                // 空值 或 10/20/30 → 归为normal组
                $completedList[] = $item;
            } else {
                // 其他值 → 归为other组
                $unfinishedList[] = $item;
            }
        }

        $finalCompletedList = $this->calcMonthlyAvgByType($completedList, $startDate, $endDate,'completed_time');
        $finalUnfinishedList = $this->calcMonthlyAvgByType($workOrderList, $startDate, $endDate,'response_time');

        $finalList['data'] = [
            'xAxis' => array_keys( $finalCompletedList ),
            'series' => [
                [ 'name'=> '响应时间','data'=> array_values($finalUnfinishedList) ],
                [ 'name'=> '处理时间','data'=> array_values($finalCompletedList) ]
            ]
        ];
        return api_output(0, $finalList);
    }

    /**
     * 按指定类型统计对应字段的月度平均值（仅过滤当前类型字段的异常值）
     * @param array $data 原始多维数组（包含completed_time/response_time字段，值为时间戳/数值）
     * @param string $startDate 开始日期（Y-m-d，如"2026-02-26"）
     * @param string $endDate 结束日期（Y-m-d，如"2027-02-26"）
     * @param string $type 统计类型："completed_time" / "response_time"
     * @return array 月度平均值（key=Y-m，value=保留1位小数的平均值，无数据为0.0）
     */
    function calcMonthlyAvgByType($data, $startDate, $endDate, $type) {
        // 1. 基础参数校验
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        if ($startTs === false || $endTs === false || $startTs > $endTs) {
            return [];
        }

        // 字段映射（仅支持指定类型）
        $fieldMap = [
            'completed_time' => 'completed_time',
            'response_time' => 'response_time'
        ];
        if (!isset($fieldMap[$type])) {
            return [];
        }
        $targetField = $fieldMap[$type];

        // 2. 初始化时间区间内的所有月份（默认0.0）
        $monthAvg = [];
        $currentTs = $startTs;
        while ($currentTs <= $endTs) {
            $monthKey = date('Y-m', $currentTs);
            $monthAvg[$monthKey] = 0.0;
            $currentTs = strtotime('+1 month', strtotime(date('Y-m-01', $currentTs)));
        }

        // 3. 统计：仅过滤当前目标字段的异常值，其他字段不处理
        $monthStats = [];
        // 3. 核心统计逻辑（修正：月份用add_time_text，仅过滤目标字段异常值）
        $monthStats = [];
        foreach ($data as $item) {
            // ========== 第一步：先判断add_time_text的合法性和时间区间 ==========
            // 过滤无add_time_text或格式错误的条目（月份归属以该字段为准）
            if (empty($item['add_time_text']) || strtotime($item['add_time_text']) === false) {
                continue;
            }
            $addTimeTs = strtotime($item['add_time_text']);
            // 过滤add_time_text不在指定时间区间内的条目
            if ($addTimeTs < $startTs || $addTimeTs > $endTs) {
                continue;
            }
            // 确定当前条目的月份（以add_time_text为准）
            $itemMonthKey = date('Y-m', $addTimeTs);

            // ========== 第二步：仅处理目标字段，过滤其异常值 ==========
            // 提取目标字段值（仅处理当前类型字段，其他字段不管）
            $fieldValue = isset($item[$targetField]) ? $item[$targetField] : 0;
            $fieldValue = is_numeric($fieldValue) ? (float)$fieldValue : 0;
            // 仅过滤当前目标字段的异常值（负数，0保留可按需调整）
            if ($fieldValue < 0) {
                continue;
            }

            // ========== 第三步：累加目标字段的有效数据 ==========
            // 初始化当月统计数据
            if (!isset($monthStats[$itemMonthKey])) {
                $monthStats[$itemMonthKey] = ['total' => 0, 'count' => 0];
            }
            // 仅累加当前目标字段的有效数据
            $monthStats[$itemMonthKey]['total'] += $fieldValue;
            $monthStats[$itemMonthKey]['count']++;
        }

        // 4. 计算平均值（保留1位小数）
        foreach ($monthStats as $month => $stats) {
            if ($stats['count'] > 0 && isset($monthAvg[$month])) {
                $avg = $stats['total'] / $stats['count'];
                $monthAvg[$month] = round($avg, 1);
            }
        }

        // 5. 按时间排序返回
        ksort($monthAvg);
        return $monthAvg;
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
     * 获取投诉建议情况
     *
     * */
    public function getComplaintTableData()
    {
        $villageId = $this->adminUser['village_id'] ?? 0;
        $villageId = is_numeric($villageId) ? intval($villageId) : 0;
        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }
        [$startDate, $endDate] = $this->completeDateRange($_GET['startDate'] ?? '', $_GET['endDate'] ?? '', 1);
        $service_house_new_repair = new HouseNewRepairService();
        $params = ['source_type' => 'village', 'village_id' => $villageId];
        $db_house_new_repair_cate = new HouseNewRepairCate();
        $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();

        $cate_where[] = ['cate_name','=','投诉建议'];
        $cate_where[] = ['village_id','=',$villageId];
        $cate_id = $db_house_new_repair_cate->getOne($cate_where);
        if( empty($cate_id) ){
            return api_output_error(1001, '找不到投诉建议分类');
        }

        [$prev_start, $prev_end] = $this->getLastCycleDateRange($startDate, $endDate);
        $list_where[] = ['o.cat_fid','=',$cate_id['id']];
        $list_where[] = ['o.village_id','=',$villageId];
        $list_where[] = ['o.add_time','between',[strtotime($startDate),strtotime($endDate)]];

        $list = $db_house_new_repair_works_order->getComplainList($list_where,'o.*,hc.cate_name,hw.name as wname')->toArray();
        foreach( $list as &$item ){
            if( in_array($item['event_status'],[40,60,70]) ){
                $item['accumulate_time'] = round( abs($item['timely_time'] - $item['add_time']) / 60 / 60, 1 );
            }else{
                $item['accumulate_time'] = 0;
            }
        }
        unset($item);
        $prev_list_where[] = ['o.cat_fid','=',$cate_id['id']];
        $prev_list_where[] = ['o.village_id','=',$villageId];
        $prev_list_where[] = ['o.add_time','between',[strtotime($prev_start),strtotime($prev_end)]];

        $prev_list = $db_house_new_repair_works_order->getComplainList($prev_list_where,'o.*')->toArray();
        //闭环率
        $current_month_completed_num = $this->countKeyInTargetValues($list, 'event_status', [40,60,70]);
        $current_month_completed_rate = count($list) > 0 ? round( $current_month_completed_num/count($list) , 1) * 100 : 0;
        //投诉量环比增长率
        $complain_growth_rate = count($prev_list) > 0 ? round( (count($list) - count($prev_list)) / count($prev_list) , 1) * 100 : 0;
        $finallist = [];
        foreach( $list as &$item ){
            $event_status_txt = '';
            switch($item['event_status']){
                case 10: $event_status_txt = '未指派'; break;
                case 20: $event_status_txt = '已指派'; break;
                case 30: $event_status_txt = '处理中'; break;
                case 40: $event_status_txt = '已办结'; break;
                case 50: $event_status_txt = '已撤回'; break;
                case 60: $event_status_txt = '已关闭'; break;
                case 70: $event_status_txt = '已评价'; break;
                case 70: $event_status_txt = '已评价'; break;
                default: $event_status_txt = '其他'; break;
            }
            $finallist[] = [
                'id' => $item['order_id'],
                'event_type' => $item['cate_name'],
                'order_content' => $item['order_content'],
                'event_status' => $item['event_status'],
                'event_status_txt' => $event_status_txt,
                'accumulate_time' => $item['accumulate_time'] . 'h',
                'name' => $item['wname'],
            ];
        }
        unset($item);
        $finallist = [
            'current_month_completed_rate' => $current_month_completed_rate,
            'complain_growth_rate' => $complain_growth_rate,
            'data' => $finallist,
        ];
        return api_output(0, $finallist);
    }
    /**
     * 获取指定时间区间的环比上一个周期（日期完全对齐，无偏移）
     * @param string $startDate 原区间开始日期（格式：Y-m-d，如"2026-01-26"）
     * @param string $endDate 原区间结束日期（格式：Y-m-d，如"2026-02-26"）
     * @return array 环比区间：['prev_start' => 上周期开始日期, 'prev_end' => 上周期结束日期]
     *               日期非法时返回空数组
     */
    function getLastCycleDateRange($startDate, $endDate) {
        // 1. 校验日期格式合法性
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        if ($startTs === false || $endTs === false || $startTs > $endTs) {
            return [];
        }

        // 2. 计算原区间的总天数（核心：按天计算周期长度，避免秒数偏移）
        $cycleDays = (int)ceil(($endTs - $startTs) / 86400); // 86400=一天的秒数

        // 3. 计算上一周期的开始/结束日期（整体往前推cycleDays天，日期完全对齐）
        $prevEndTs = strtotime("-{$cycleDays} day", $endTs);   // 结束日期往前推cycleDays天
        $prevStartTs = strtotime("-{$cycleDays} day", $startTs); // 开始日期往前推cycleDays天

        // 4. 转换为Y-m-d格式，保证日期精准
        $prevStart = date('Y-m-d', $prevStartTs);
        $prevEnd = date('Y-m-d', $prevEndTs);

        return [$prevStart,$prevEnd];
    }

    /**
     * 统计数组中指定键的值属于目标列表的数量
     * @param array $array 待统计的数组（一维/多维）
     * @param string $targetKey 要检查的键名（如"event_status"）
     * @param array $targetValues 目标值列表（如[40,60,70]）
     * @return int 符合条件的元素数量
     */
    function countKeyInTargetValues($array, $targetKey, $targetValues) {
        $count = 0;

        // 遍历数组
        foreach ($array as $item) {
            // 多维数组递归处理
            if (is_array($item)) {
                $count +=$this->countKeyInTargetValues($item, $targetKey, $targetValues);
                continue;
            }

            // 一维数组：检查是否存在目标键，且值在目标列表中
            if (isset($array[$targetKey]) && in_array($array[$targetKey], $targetValues)) {
                $count++;
                // 一维数组只统计一次，跳出循环
                break;
            }
        }

        return $count;
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

    /**
     * 获取项目能耗数据
     * */
    public function getUtilitiesData()
    {
        $villageId = $this->adminUser['village_id'] ?? 0;
        $villageId = is_numeric($villageId) ? intval($villageId) : 0;

        $startDate = $this->request->post('startDate');
        $endDate = $this->request->post('endDate');
        $type = $this->request->post('type');

        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }
        [$startDate, $endDate] = $this->completeDateRange($startDate ?? '', $endDate ?? '', 12);
        $db_utilities_log = new UtilitiesLog();
        $db_utilities_category = new UtilitiesCategory();
        $where[] = ['village_id','=',$villageId];
        $list = $db_utilities_log->getList($villageId,true,1,9999)->toArray();
        $date_range = $this->getMonthListBetweenDates($startDate, $endDate);

        $map = [
            '水'=> 1,
            '电'=> 2,
            '燃气'=> 49,
        ];
        $type_num = $map[$type];
        iF( !$type_num ){
            return api_output_error(1001, '找不到该类别的能耗');
        }
        $category_where[] = ['village_id','=',$villageId];
        $category_where[] = ['parent_id','=',$type_num];

        $categoryList = $db_utilities_category->getList($category_where,true,1,9999)->toArray();


        $heatData = $this->buildHeatData($categoryList,$date_range,$list);

        $finallist = [
            'monthList' => $date_range,
            'categoryList' => array_column( $categoryList, 'name' ),
            'heatData' => $heatData,
        ];

        return api_output(0, $finallist);
    }

    /**
     * 生成ECharts所需的heatData二维数组
     * @param array $categoryList 分类列表（含id/name）
     * @param array $dateList 日期列表（YYYY-MM格式，如['2025-02', ...]）
     * @param array $rawData 原始费用数据（含category_id/add_date/rate）
     * @return array 目标格式：[[分类1的所有月份rate], [分类2的所有月份rate], ...]
     */
    function buildHeatData($categoryList, $dateList, $rawData) {
        // 步骤1：构建「分类ID→分类名称」映射（方便按分类排序）
        $cateIdToName = [];
        foreach ($categoryList as $cate) {
            $cateIdToName[$cate['id']] = $cate['name'];
        }

        // 步骤2：构建「分类ID→月份→rate」的临时映射（无数据默认0）
        $cateMonthRate = [];
        // 初始化：每个分类的所有月份rate为0
        foreach ($categoryList as $cate) {
            $cateId = $cate['id'];
            $cateMonthRate[$cateId] = array_fill_keys($dateList, 0.00);
        }

        // 步骤3：填充原始数据中的rate值
        foreach ($rawData as $item) {
            $cateId = $item['category_id'];
            $month = $item['add_date'];
            $rate = floatval($item['rate'] ?? 0); // 转浮点型，方便后续使用

            // 仅填充存在的分类和月份
            if (isset($cateMonthRate[$cateId]) && isset($cateMonthRate[$cateId][$month])) {
                $cateMonthRate[$cateId][$month] = $rate;
            }
        }

        // 步骤4：按分类列表顺序，生成heatData二维数组
        $heatData = [];
        foreach ($categoryList as $cate) {
            $cateId = $cate['id'];
            // 按日期列表顺序提取rate值，转成数字数组
            $monthRates = [];
            foreach ($dateList as $month) {
                $monthRates[] = $cateMonthRate[$cateId][$month];
            }
            $heatData[] = $monthRates;
        }

        return $heatData;
    }

    /**
     * 获取项目支出情况
     * @return array 目标格式：[[分类1的所有月份rate], [分类2的所有月份rate], ...]
     */
    public function getExpendData()
    {
        $villageId = $this->adminUser['village_id'] ?? 0;
        $villageId = is_numeric($villageId) ? intval($villageId) : 0;

        $startDate = $this->request->post('startDate');
        $endDate = $this->request->post('endDate');

        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }

        $where[] = ['vb.village_id','=',$villageId];

        $dbVillageBill = new VillageBill();
        $list = $dbVillageBill->getListAll($where,'vb.*,bc.category_name',1,999999)->toArray();

        $finallist = $this->convertToSunburstData($list);

        $budgetList = (new BudgetManagementService())->getBudgetList($where,'vb.*,bc.category_name',false,1,999999);
        $totalBudget = array_reduce($budgetList, function($carry, $item) {
            // $carry是累加结果，$item是当前数组元素
            return $carry + (float)($item['amount'] ?? 0);
        }, 0.00);
        $totalExpend = array_reduce($list, function($carry, $item) {
            // $carry是累加结果，$item是当前数组元素
            return $carry + (float)($item['price'] ?? 0);
        }, 0.00);

        $data = [
            'data' => $finallist,
            'count' => [
                'total_budget' => $this->formatAmountToWan( $totalBudget ),
                'total_expend' => $this->formatAmountToWan( $totalExpend )
            ]
        ];
        return api_output(1000, $data);
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

            // 如果需要显示具体账单（子节点）
//            if ($showDetail && !empty($categoryDetails[$categoryName])) {
//                foreach ($categoryDetails[$categoryName] as $detail) {
//                    $categoryItem['children'][] = [
//                        'name' => $detail['name'],
//                        'value' => $detail['value'],
//                        'id' => $detail['id']
//                    ];
//                }
//            }

            $sunburstData[] = $categoryItem;
        }

        return $sunburstData;
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

    public function getChargeStatusData()
    {
        $villageId = $this->adminUser['village_id'] ?? 0;
        $villageId = is_numeric($villageId) ? intval($villageId) : 0;

        $startDate = $this->request->post('startDate');
        $endDate = $this->request->post('endDate');

        // 严格校验：非数字/0直接返回参数错误
        if (!is_numeric($villageId) || intval($villageId) <= 0) {
            return api_output_error(1001, '必传参数【小区ID】缺失或无效');
        }
        [$startDate, $endDate] = $this->completeDateRange($startDate ?? '', $endDate ?? '', 12);


        // 获取当前年份
//        $currentYear = date('Y');
        $currentYear = date('2025');

        // 获取当年第一天（格式：YYYY-01-01 00:00:00）
        $firstDayOfYear = date('Y-m-d H:i:s', strtotime($currentYear . '-01-01 00:00:00'));

        // 获取当年最后一天（格式：YYYY-12-31 23:59:59）
        $lastDayOfYear = date('Y-m-d H:i:s', strtotime($currentYear . '-12-31 23:59:59'));

        $HouseNewChargeProjectService = new HouseNewChargeProjectService();
        $param=[
            'village_id'=>$villageId,
            'project_type'=>'cycle',
            'status'=>1,
        ];
        $projectList = $HouseNewChargeProjectService->getListALL($param);

        if( empty($projectList) ){
            return [];
        }

        $order_where = [
            'village_id'=>$villageId,
            'start_time'=>$firstDayOfYear,
            'end_time'=>$lastDayOfYear,
        ];
        $payableOrderList = $this->payableOrderList($order_where);

//        //房屋面积
//        $residenceRoomDataWhere=array(
//            'village_id'=>$villageId,
//            'house_type'=>1
//        );
//        $storeRoomDataWhere=array(
//            'village_id'=>$villageId,
//            'house_type'=>2
//        );
//        $residenceRoomDataList = (new NewBuildingService())->getRoomDataList($residenceRoomDataWhere);
//        $storeRoomDataList = (new NewBuildingService())->getRoomDataList($storeRoomDataWhere);
//
//        $roomDataList = array_merge($residenceRoomDataList['list'], $storeRoomDataList['list']);
//        $pigcms_ids = array_column($roomDataList, 'pigcms_id');
//        $vacancy_where[] = ['pigcms_id','in', $pigcms_ids];
//        $roomDateList = (new HouseVillageUserVacancy())->getList($vacancy_where);
//        $residenceRoomArea = 0;
//        $storeRoomArea = 0;
//        if(!empty($roomDateList)){
//            $roomDateList = $roomDateList->toArray();
//            $residenceRoomList = $this->filterByPigcmsId($roomDateList,array_column($residenceRoomDataList['list'], 'pigcms_id'));
//            $storeRoomList = $this->filterByPigcmsId($roomDateList,array_column($storeRoomDataList['list'], 'pigcms_id'));
//            $residenceRoomArea = array_sum( array_column( $residenceRoomList , 'housesize') );
//            $storeRoomArea = array_sum( array_column( $storeRoomList , 'housesize') );
//        }

        foreach ($projectList as &$item) {
            $item['account_num'] = 0;//总户数
            $item['paid_account_num'] = 0;//已缴户数
            $item['remaining_paid_account_num'] = 0;//剩余应缴户数
            $item['paid_account_rate'] = 0;//户数缴费率
            $item['amound'] = 0;//应缴费总金额
            $item['paid_amound'] = 0;//已缴费总金额
            $item['remaining_paid_amound_num'] = 0;//剩余应缴金额
            $item['paid_amound_rate'] = 0;//缴费率

            //已缴户数
            $origin_project_name_key = 'project_name';
            $origin_project_name_value = $item['name'];
            // 第一步：先按project_name筛选出匹配的数组
            $matchedArr = array_filter($payableOrderList, function($item) use ($origin_project_name_key, $origin_project_name_value) {
                return isset($item[$origin_project_name_key]) && $item[$origin_project_name_key] == $origin_project_name_value;
            });

            // 第二步：按room_id去重，每个room_id只保留第一条匹配的数据
            $uniqueArr = [];
            // 记录已经处理过的room_id，避免重复
            $roomIdRecord = [];

            foreach ($matchedArr as $value) {
                // 先校验room_id字段是否存在且非空
                if (isset($value['room_id']) && !empty($value['room_id'])) {
                    $roomId = $value['room_id'];
                    // 如果该room_id未记录过，则保留这条数据并标记已记录
                    if (!in_array($roomId, $roomIdRecord)) {
                        $roomIdRecord[] = $roomId;
                        $uniqueArr[] = $value;
                    }
                } else {
                    // 可选：如果没有room_id，是否保留？这里默认保留（也可以注释掉这行不保留）
                    $uniqueArr[] = $value;
                }
            }

            /*获取当前收费标准绑定用户数结束*/
                $current_project_where = [
                    'village_id'=>$villageId,
                    'rule_id'=>$item['rule_id'],
                    'bind_type'=>1,
                ];
                $currentRoomList = $this->standardBindList($current_project_where);
                $currentRoomList = isset($currentRoomList['list']) && $currentRoomList['list'] > 0 ? $currentRoomList['list'] : [];
                $currentProjectRoomNum = count($currentRoomList);
            /*获取当前收费标准绑定用户数*/

            $item['account_num'] = $currentProjectRoomNum;//总户数
            if($currentProjectRoomNum<=0){
                continue;
            }
            $item['paid_account_num'] = count($uniqueArr);//已缴户数
            $item['remaining_paid_account_num'] = round( $currentProjectRoomNum - count($uniqueArr) ,0 );//剩余应缴户数
            $item['paid_account_rate'] = $currentProjectRoomNum > 0? round( count($uniqueArr) / $currentProjectRoomNum ,2 ) * 100 : 0;//户数缴费率
            $item['paid_amound'] =  array_sum( array_column( $matchedArr , 'pay_money' ) );//已缴费总金额
            $item['paid_amound_txt'] = $this->formatAmountToWan( $item['paid_amound'] ).'万元';//已缴费总金额


            if( count( $matchedArr ) > 0 ) {
                //判断账单是按月生成, 还是按年, 还是按日, 1：按日生成2：按月生成3：按年生成, 默认按年生成
                if( $item['bill_create_set'] == 1 ){
                    $charge_price = $item['charge_price'] * 365;
                }else if( $item['bill_create_set'] == 2 ){
                    $charge_price = $item['charge_price'] * 12;
                }else if( $item['bill_create_set'] == 3 ){
                    $charge_price = $item['charge_price'];
                }else{
                    $charge_price = $item['charge_price'];
                }

                if( $item['fees_type'] == 2){//单位计价
                    if( $item['unit_gage_type'] == 2 ){//车位面积
                        //车位面积
                        $roomArea = array_sum( array_column( $currentRoomList ,'position_area') );
                    }else{//其余的都默认为房屋面积
                        $roomArea = array_sum( array_column( $currentRoomList ,'housesize') );
                    }

                    //应缴费总金额
                    $item['amound'] = round( $roomArea * $charge_price , 2);
                    $item['amound_txt'] = $this->formatAmountToWan( $item['amound'] ) . '万元';
                }else {//默认固定费用
                    //应缴费总金额
                    $item['amound'] = round( $item['account_num'] * $charge_price , 2);
                    $item['amound_txt'] = $item['amound'] . '万元';
                }
                $item['area'] = $roomArea;

                //剩余应缴
                $item['remaining_paid_amound_num'] = round( $item['amound'] - $item['paid_amound'] ,2);
                $item['remaining_paid_amound_num_txt'] = $this->formatAmountToWan( $item['remaining_paid_amound_num'] ) . '万元';
                //缴费率
                $item['paid_amound_rate'] = $item['amound'] > 0? round( $item['paid_amound'] / $item['amound'] , 2) * 100 : 2;

                $item['paid_amound_str'] = $this->formatAmountToWan( $item['paid_amound'] );
                $item['remaining_paid_amound_num_str'] = $this->formatAmountToWan( $item['remaining_paid_amound_num'] );
            }
        }
        unset($item);
        foreach ($projectList as $item) {
            // 严格判断account_num不等于0（避免空值/字符串0的干扰）
            if (isset($item['account_num']) && $item['account_num'] !== 0) {
                $filteredData[] = $item;
            }
        }
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
     * 筛选出数组中pigcms_id匹配的元素
     * @param array $sourceArray 原始房源数据数组（就是你说的包含1645个元素的数组）
     * @param array $targetIds 需要匹配的pigcms_id列表（可以是一维数组，如[14415, 12345]）
     * @return array 匹配到的房源数据新数组
     */
    function filterByPigcmsId(array $sourceArray, array $targetIds): array
    {
        // 空值校验：原始数组为空或匹配ID列表为空，直接返回空数组
        if (empty($sourceArray) || empty($targetIds)) {
            return [];
        }

        // 将目标ID列表转为键值对（key为ID，value为true），提升匹配效率（避免循环内多次in_array）
        $idMap = array_flip($targetIds);
        $result = [];

        // 遍历原始数组，筛选匹配的元素
        foreach ($sourceArray as $item) {
            // 校验元素是否包含pigcms_id字段，且字段值为有效数字
            if (isset($item['pigcms_id']) && is_numeric($item['pigcms_id'])) {
                $currentId = (int)$item['pigcms_id'];
                // 匹配到目标ID，加入结果数组
                if (isset($idMap[$currentId])) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

}