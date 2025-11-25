<?php
	namespace app\job;

    use think\Exception;
    use think\queue\Job as DoJob;

    class NbLotWaterElectricMeterJob
    {

        public function fire(DoJob $job, $data)
        {
            if ($job->attempts() >= 1) {
                $job->delete();
            }
            $root = dirname(dirname(dirname(__DIR__)));
            $basePath = rtrim($root, '/');
            $_SERVER['DOCUMENT_ROOT'] = $basePath;
            fdump_api(['basePath' => $basePath, 'data' => $data], '00NbLotWaterElectricMeterJob', 1);

            $meter_pf = $data && isset($data['meter_pf']) ? $data['meter_pf'] : '';
            $village_id = $data && isset($data['village_id']) ? $data['village_id'] : '';
            switch ($meter_pf) {
                case 'easymeter':
                    try {
                        $nbLotEasyMeterApiService = new \app\community\model\service\Device\NbLotEasyMeterApiService($village_id);
                        if ($data['opt_type'] == 'meter_create_order') {
                            $nbLotEasyMeterApiService->meter_create_order($data);
                        }
                    } catch (Exception $ee) {
                        fdump_api(['basePath' => $basePath, 'data' => $data, 'e_msg' => $ee->getMessage()], 'easymeter/NbLotWaterElectricMeterJob', 1);
                    }
                    break;
                case 'jisheyunmeter':
                    try {
                        $jiSheYunMeterApiService = new \app\community\model\service\Device\JiSheYunMeterApiService($village_id);
                        if ($data['opt_type'] == 'meter_village_reading') {
                            $jiSheYunMeterApiService->meter_village_reading($data);
                        }else if ($data['opt_type'] == 'meter_create_order') {
                            $jiSheYunMeterApiService->meter_create_order($data);
                        }
                    } catch (Exception $ee) {
                        fdump_api(['basePath' => $basePath, 'data' => $data, 'e_msg' => $ee->getMessage()], 'jisheyun/NbLotWaterElectricMeterJob', 1);
                    }
                    break;
                case 'lingyinmeter':
                    try {
                        $lingYinPfMeterApiService=new \app\community\model\service\Device\LingYinPfMeterApiService($village_id);
                        if ($data['opt_type'] == 'meter_create_order') {
                            $lingYinPfMeterApiService->meter_create_order($data);
                        }
                    } catch (Exception $ee) {
                        fdump_api(['basePath' => $basePath, 'data' => $data, 'e_msg' => $ee->getMessage()], 'lingyinmeter/NbLotWaterElectricMeterJob', 1);
                    }
                    break;
                case 'bomingmeter':
                    try {
                        $boMingPfMeterApiService = new \app\community\model\service\Device\BoMingPfMeterApiService($village_id);
                        if ($data['opt_type'] == 'meter_village_reading') {
                            $boMingPfMeterApiService->meter_village_reading($data);
                        }else if ($data['opt_type'] == 'meter_create_order') {
                            $boMingPfMeterApiService->meter_create_order($data);
                        }
                    } catch (Exception $ee) {
                        fdump_api(['basePath' => $basePath, 'data' => $data, 'e_msg' => $ee->getMessage()], 'lingyinmeter/NbLotWaterElectricMeterJob', 1);
                    }
                    break;
                default:
                    break;
            }
            return true;
        }

        public function failed($data)
        {
            
        }
	}