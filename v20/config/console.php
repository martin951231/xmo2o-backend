<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'waterAlarmGateway' => 'app\\command\\WaterAlarmGateway',
        'socialActivityRefundQuery' => 'app\\command\\SocialActivityRefundQuery',
		'buildingImport' => 'app\command\BuildingImport',
        'unitImport' => 'app\command\UnitImport',
        'floorImport' => 'app\command\FloorImport',
        'roomImport' => 'app\command\RoomImport',
        'threeTableImport' => 'app\command\ThreeTableImport',
        'billExcelImport' => 'app\command\BillExcelImport',
        'standardVacancyBindImport'=>'app\command\StandardVacancyBindImport',
        'standardPositionBindImport'=>'app\command\StandardPositionBindImport',
    ],
];
