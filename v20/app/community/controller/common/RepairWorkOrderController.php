<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      工单相关
 */

namespace app\community\controller\common;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\NewBuildingService;
use app\community\model\service\workorder\HouseCommunityQualityService;

class RepairWorkOrderController extends CommunityBaseController
{
    public function getRepairQualityInfo() {
        $village_id = $this->request->param('village_id', 0);
        $cat_id = $this->request->param('cat_id', 0);
        $pigcms_id = $this->request->param('pigcms_id', 0);
        $address_type = $this->request->param('address_type', '');
        $address_id = $this->request->param('address_id', 0);
        if (!$village_id || !$cat_id) {
            return api_output_error(1001, '必传参数缺失');
        }
        $data = [];
        try{
            if ($address_id && $address_type != 'room') {
                $data['text'] = '';
                $data['qualityInfo'] = [];
                return api_output(0,$data);
            }
            $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
            if (!$jz_open) {
                $data['text'] = '';
                $data['qualityInfo'] = [];
                return api_output(0,$data);
            }
            $newBuildingService = new NewBuildingService();
            if ($address_id) {
                $whereVacancy = [];
                $whereVacancy[] = ['pigcms_id', '=', $address_id];
                $roomInfo = $newBuildingService->getHouseVillageUserVacancyFindMethod($whereVacancy, 'pigcms_id, single_id');
                $room_id = isset($roomInfo['pigcms_id']) && $roomInfo['pigcms_id'] ? intval($roomInfo['pigcms_id']) : 0;
                $single_id = isset($roomInfo['single_id']) && $roomInfo['single_id'] ? intval($roomInfo['single_id']) : 0;
            } else {
                $shouseVillageUserBindService = new HouseVillageUserBindService();
                $whereBind = [];
                $whereBind[] = ['pigcms_id', '=', $pigcms_id];
                $userBindInfo = $shouseVillageUserBindService->getBindInfo($whereBind, 'pigcms_id, single_id, vacancy_id');
                $room_id = isset($userBindInfo['vacancy_id']) && $userBindInfo['vacancy_id'] ? intval($userBindInfo['vacancy_id']) : 0;
                $single_id = isset($userBindInfo['single_id']) && $userBindInfo['single_id'] ? intval($userBindInfo['single_id']) : 0;
            }

            $params = [
                'village_id' => $village_id,
                'repair_cate_id' => $cat_id,
                'single_id' => $single_id,
                'room_id' => 0,
            ];
            $qualityInfo = (new HouseCommunityQualityService())->getRepairCateQuality($params);
            if ($room_id > 0) {
                $roomParams = [
                    'village_id' => $village_id,
                    'single_id' => $single_id,
                    'room_id' => $room_id,
                    'quality_id' => $qualityInfo['quality_id'],
                ];
                $roomQualityInfo = (new HouseCommunityQualityService())->getRepairCateQuality($roomParams);
                $room_quality_start_time = isset($roomQualityInfo['quality_start_time']) ? intval($roomQualityInfo['quality_start_time']) : 0;
                $room_quality_end_time = isset($roomQualityInfo['quality_end_time']) ? intval($roomQualityInfo['quality_end_time']) : 0;
            } else {
                $room_quality_start_time = 0;
                $room_quality_end_time = 0;
            }
            if ($room_quality_start_time > 100 && $room_quality_end_time > 100) {
                $quality_start_time = $room_quality_start_time;
                $quality_end_time = $room_quality_end_time;
            } else {
                $quality_start_time = isset($qualityInfo['quality_start_time']) ? intval($qualityInfo['quality_start_time']) : 0;
                $quality_end_time = isset($qualityInfo['quality_end_time']) ? intval($qualityInfo['quality_end_time']) : 0;
            }
            $quality_id = isset($qualityInfo['quality_id']) ? intval($qualityInfo['quality_id']) : 0;
            !$quality_id && $quality_id = isset($roomQualityInfo['quality_id']) ? intval($roomQualityInfo['quality_id']) : 0;
            if ($quality_start_time > 0 && $quality_end_time > 0 && $quality_id > 0) {
                $params = [
                    'id' => $quality_id,
                ];
                $detail = (new HouseCommunityQualityService())->getCommunityQualityDetail($params);
                $fid_category_name = isset($detail['fid_category_name']) ? trim($detail['fid_category_name']) : '';
                $category_name = isset($detail['category_name']) ? trim($detail['category_name']) : '';
                $quality_start_time_text = date('Y-m-d', $quality_start_time);
                $quality_end_time_text = date('Y-m-d', $quality_end_time);
                $text = $category_name;
                if ($fid_category_name && $category_name) {
                    $text = $fid_category_name . '-' . $category_name;
                }
                $text .= "的保修期限是{$quality_start_time_text}至{$quality_end_time_text}";
                $data['text'] = $text;
            } else {
                $data['text'] = '';
            }
            $data['qualityInfo'] = $qualityInfo;
            // 默认弹框提示 10秒
            $data['textTime'] = 10000;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

}