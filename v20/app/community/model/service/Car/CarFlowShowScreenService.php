<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      门禁权限下发状态订阅消息(目前仅用于海康云眸内部)
 */

namespace app\community\model\service\Car;

use app\community\model\db\ParkShowscreenLog;
use app\community\model\service\Park\D3ShowScreenService;
use app\consts\Car\CarFlowConst;

class CarFlowShowScreenService
{
    /**
     * 添加显屏指令
     * 整合来源：\app\community\model\service\HouseVillageParkingService::addParkShowScreenLog
     */
    public function addParkShowScreenLog($data) {
        $db_park_showscreen_log = new ParkShowscreenLog();
        /*----------添加显屏指令start-----------*/
        //临时车入场
        $temp_in_content = '欢迎光临';
        //临时车出场
        $temp_out_content1 = '停车';
        $temp_out_content2 = '请缴费';
        $temp_out_content3 = '一路顺风';
        //月租车入场
        $mouth_in_content = '欢迎光临';
        //月租车出场
        $mouth_out_content1 = '停车有效期';

        //车辆类型
        $carType = isset($data['car_type']) ? $data['car_type'] : '';
        //通道方向
        $passageDirection = isset($data['passage']['passage_direction']) ? $data['passage']['passage_direction'] : 0;
        //设备类型
        $deviceType = isset($data['passage']['device_type']) ? $data['passage']['device_type'] : 0;
        //设备类型 下发显屏指令类型（27 临显 25广告）
        $orderType = 27;
        
        $car_end_time = isset($data['end_time']) && intval($data['end_time']) > 0 ? intval($data['end_time']) : 0;

        $showContentArr = [];
        //todo 进出场 显屏
        switch ($carType) {
            case 'temporary_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($deviceType == 1) {
                        //todo 横屏
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number']
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $temp_in_content : $data['content']
                        ];
                    } elseif ($deviceType == 2) {
                        //todo 竖屏
                        if (empty($data['content'])) {
                            $content = $temp_in_content;
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $content = mb_substr($data['content'], 0, 6);
                            } else {
                                $content = $data['content'];
                            }
                        }
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number']
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => $content
                        ];
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($deviceType == 1) {
                        //todo 横屏
                        if (!isset($data['price']) || empty($data['price'])) {
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 1,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => 0
                            ];
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '02',
                                'content' => empty($data['content']) ? $temp_out_content3 : $data['content'],
                                'duration' => $data['duration'],
                                'price' => 0,
                            ];
                        } else {
                            if (empty($data['content'])) {
                                if (isset($data['voice_content']) && $data['voice_content'] == 9) {
                                    $content = '扣款' . $data['price'] . '元,请通行';
                                } else {
                                    $content = $temp_out_content2 . $data['price'] . '元';
                                }

                            } else {
                                $content = $data['content'];
                            }
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 1,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];

                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '02',
                                'content' => empty($data['content']) ? $temp_out_content1 . $data['duration_txt'] : $data['content'],
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];

                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 3,
                                'serial' => '03',
                                'content' => $content,
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                        }
                    } elseif ($deviceType == 2) {
                        //todo 竖屏
                        if (empty($data['price'])) {
                            if (empty($data['content'])) {
                                $content = $temp_out_content3;
                            } else {
                                if (mb_strlen($data['content']) > 6) {
                                    $content = mb_substr($data['content'], 0, 6);
                                } else {
                                    $content = $data['content'];
                                }
                            }
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 1,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => 0
                            ];
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '02',
                                'content' => $content,
                                'duration' => $data['duration'],
                                'price' => 0
                            ];
                        } else {
                            if (empty($data['content'])) {
                                $content = $temp_out_content1 . $data['duration_txt'];
                            } else {
                                if (mb_strlen($data['content']) > 6) {
                                    $content = mb_substr($data['content'], 0, 6);
                                } else {
                                    $content = $data['content'];
                                }
                            }
                            if (empty($data['content'])) {
                                if (isset($data['voice_content']) && $data['voice_content'] == 9) {
                                    $content2 = '扣款' . $data['price'] . '元,请通行';
                                    if (mb_strlen($content2) > 6) {
                                        $content2 = mb_substr($content2, 0, 6);
                                    }
                                } else {
                                    $content2 = $temp_out_content2 . $data['price'] . '元';
                                }
                            } else {
                                if (mb_strlen($data['content']) > 6) {
                                    $content2 = mb_substr($data['content'], 0, 6);
                                } else {
                                    $content2 = $data['content'];
                                }
                            }
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 1,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '02',
                                'content' => $content,
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                            $showContentArr[] = [
                                'content_type' => 1,
                                'screen_row' => 3,
                                'serial' => '03',
                                'content' => $content2,
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                        }
                    }
                }
                break;
            case 'month_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($deviceType == 1) {
                        //todo 横屏
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $mouth_in_content : $data['content'],
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    } elseif ($deviceType == 2) {
                        //todo 竖屏
                        if (empty($data['content'])) {
                            $content = $mouth_in_content;
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $content = mb_substr($data['content'], 0, 6);
                            } else {
                                $content = $data['content'];
                            }
                        }
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($deviceType == 1) {
                        //todo 横屏
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $mouth_out_content1 . $data['end_time_txt'] : $data['content'],
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    } elseif ($deviceType == 2) {
                        //todo 竖屏
                        if (empty($data['content'])) {
                            $content = $mouth_out_content1 . $data['end_time_txt'];
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $content = mb_substr($data['content'], 0, 6);
                            } else {
                                $content = $data['content'];
                            }
                        }
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    }
                }
                break;
            default:
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($deviceType == 1) {
                        //todo 横屏
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $mouth_in_content : $data['content'],
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    } elseif ($deviceType == 2) {
                        //todo 竖屏

                        if (empty($data['content'])) {
                            $content = $mouth_in_content;
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $content = mb_substr($data['content'], 0, 6);
                            } else {
                                $content = $data['content'];
                            }
                        }
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($deviceType == 1) {
                        //todo 横屏
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $temp_out_content3 : $data['content'],
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    } elseif ($deviceType == 2) {
                        //todo 竖屏

                        if (empty($data['content'])) {
                            $content = $temp_out_content3;
                        } else {
                            if (mb_strlen($data['content']) > 6) {
                                $content = mb_substr($data['content'], 0, 6);
                            } else {
                                $content = $data['content'];
                            }
                        }
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $car_end_time
                        ];
                        $showContentArr[] = [
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $car_end_time
                        ];
                    }
                }
        }

        //todo 进场 语音
        if ($passageDirection == 1) {
            $showContentArr[] = [
                'content_type' => 2,
                'screen_row' => 2,
                'serial' => '01',
                'car_number' => $data['car_number'],
                'content' => (!isset($data['voice_content']) || empty($data['voice_content'])) ? 1 : $data['voice_content'],
                'end_time' => $car_end_time,
                'duration' => isset($data['duration']) ? $data['duration'] : 0,
                'price' => isset($data['price']) ? $data['price'] : 0
            ];
        }

        //todo 出场 语音
        if ($passageDirection == 0) {
            switch ($carType) {
                case 'temporary_type':
                    if (!empty($data['duration'])) {
                        $content = (!isset($data['voice_content']) || empty($data['voice_content'])) ? 5 : $data['voice_content'];
                    } else {
                        $content = (!isset($data['voice_content']) || empty($data['voice_content'])) ? 2 : $data['voice_content'];
                    }
                    $showContentArr[] = [
                        'content_type' => 2,
                        'screen_row' => 2,
                        'serial' => '01',
                        'car_number' => $data['car_number'],
                        'content' => $content,
                        'end_time' => $car_end_time,
                        'duration' => isset($data['duration']) ? $data['duration'] : 0,
                        'price' => isset($data['price']) ? $data['price'] : 0
                    ];
                    break;
                case 'month_type':
                    if ($car_end_time > 1) {
                        if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                            $content = 4;
                        } else {
                            $content = $data['voice_content'];
                        }
                    } else {
                        if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                            $content = 2;
                        } else {
                            $content = $data['voice_content'];
                        }
                    }
                    $showContentArr[] = [
                        'content_type' => 2,
                        'screen_row' => 2,
                        'serial' => '01',
                        'car_number' => $data['car_number'],
                        'content' => $content,
                        'end_time' => $car_end_time,
                        'duration' => isset($data['duration']) ? $data['duration'] : 0,
                        'price' => isset($data['price']) ? $data['price'] : 0
                    ];
                    break;
                default:
                    if (!isset($data['voice_content']) || empty($data['voice_content'])) {
                        $content = 2;
                    } else {
                        $content = $data['voice_content'];
                    }
                    $showContentArr[] = [
                        'content_type' => 2,
                        'screen_row' => 2,
                        'serial' => '01',
                        'car_number' => $data['car_number'],
                        'content' => $content,
                        'end_time' => $car_end_time,
                        'duration' => isset($data['duration']) ? $data['duration'] : 0,
                        'price' => isset($data['price']) ? $data['price'] : 0
                    ];
            }
        }

        $parkShowScreen = new D3ShowScreenService();
        $serialData = [];
        if ($showContentArr) {
            foreach ($showContentArr as $showKey => $item) {
                if ($item['content_type'] == 1) { //显屏
                    $showVoiceTxt = $parkShowScreen->showVoiceTxt($item['content'], $item['screen_row'], $item['serial'], $orderType);
                } else { //语音
                    $showVoiceTxt = $parkShowScreen->showVoice($item, '01');
                }
                $base64DataText = $parkShowScreen->translation($showVoiceTxt);
                $base64DataTextLength = strlen($base64DataText);
                $serialData[] = [
                    'serialChannel' => 0,
                    'data' => $base64DataText,
                    'dataLen' => $base64DataTextLength,
                ];
            }
        }
        $showscreen_log = [
            'village_id' => $data['village_id'],
            'park_sys_type' => 'D3',
            'content' => $data['car_number'],
            'content_type' => 1,
            'screen_row' => 1,
            'serial' => '01',
            'car_number' => $data['car_number'],
            'channel_id' => $data['channel_id'],
            'park_type' => 1,
            'add_time' => time(),
            'showcontent' => json_encode($serialData, JSON_UNESCAPED_UNICODE)
        ];
        $logId = $db_park_showscreen_log->add($showscreen_log);
        fdump_api(['参数进来了==' . __LINE__, [
            'car_number' => $data['car_number'], 'showContentArr' => $showContentArr, 'logId' => $logId, 'showscreen_log' => $showscreen_log
        ]], 'parkD3/addShowScreenLog', 1);
        return $logId;
    }

    /**
     * 添加显屏指令
     * 整合来源：\app\community\model\service\Park\A11Service::addParkShowScreenLog
     */
    public function addA11ParkShowScreenLog($data)
    {
        $db_park_showscreen_log = new ParkShowscreenLog();
        /*----------添加显屏指令start-----------*/
        //临时车入场
        $temp_in_content = '欢迎光临';
        //临时车出场
        $temp_out_content1 = '停车';
        $temp_out_content2 = '请缴费';
        $temp_out_content3 = '一路顺风';
        //月租车入场
        $mouth_in_content = '欢迎光临';
        //月租车出场
        $mouth_out_content1 = '停车有效期';
        //黑名单禁止通行
        $black_content = '黑名单禁止通行';

        $is_log = false;
        $car_number = isset($data['car_number']) ? trim($data['car_number']) : '';
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        !$village_id && $village_id = isset($data['passage']['village_id']) ? intval($data['passage']['village_id']) : 0;
        $channel_id = isset($data['channel_id']) ? trim($data['channel_id']) : '';
        !$channel_id && $channel_id = isset($data['passage']['device_number']) ? trim($data['passage']['device_number']) : '';
//        if ($car_number == '川A43611') {
//            $is_log = true;
//        }
        if ($is_log) {
            fdump_api(['data' => $data], 'screen/addParkShowScreenLog', 1);
        }

        //车辆类型
        $carType = isset($data['car_type']) ? $data['car_type'] : '';
        //通道方向
        $passageDirection = isset($data['passage']['passage_direction']) ? intval($data['passage']['passage_direction']) : 0;
        //设备类型
        $deviceType = isset($data['passage']['device_type']) ? intval($data['passage']['device_type']) : 0;
        //设备类型 下发显屏指令类型（27 临显 25广告） 
        $orderType = 27;
        //通道类型
        if ((isset($data['passage']['passage_type']) && $data['passage']['passage_type'] == 2) && $passageDirection == 1) {
            $passageType = 2;
        } else {
            $passageType = 1;
        }
        $showContentArr = [];
        $data_voice_content = isset($data['voice_content']) && intval($data['voice_content']) > 0 ? intval($data['voice_content']) : 0;
        //todo 进出场 显屏
        $data_end_time = isset($data['end_time']) ? intval($data['end_time']) : 0;
        $data_end_time_text = isset($data['end_time_txt']) ? trim($data['end_time_txt']) : '';
        if ($data_end_time > 0 && !$data_end_time_text) {
            $data_end_time_text = date('Y-m-d');
        }
        $data_duration = isset($data['duration']) ? $data['duration'] : 0;
        $data_duration_txt = isset($data['duration_txt']) ? $data['duration_txt'] : '';
        $data_price = isset($data['price']) ? $data['price'] : 0;
        if (floatval($data_price) > 0) {
            $data_price = getFormatNumber($data_price);
        }

        $stored_balance = isset($data['stored_balance']) && floatval($data['stored_balance']) > 0 ? $data['stored_balance'] : 0;
        if ($stored_balance > 0) {
            $stored_balance = getFormatNumber($stored_balance);
        }
        $car_type = isset($data['car_type']) ? trim($data['car_type']) : '';
        switch ($carType) {
            case 'black_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($is_log) {
                        fdump_api(['msg' => '黑名单-车辆入场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场横屏-黑名单 2-车牌 3-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场竖屏-黑名单 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($is_log) {
                        fdump_api(['msg' => '黑名单-车辆出场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场横屏-黑名单 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $black_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场竖屏-黑名单 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                break;
            case 'month_type':
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($is_log) {
                        fdump_api(['msg' => '月租车-车辆入场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_in_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场横屏-月租车 2-车牌 3-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_in_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场竖屏-月租车 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($is_log) {
                        fdump_api(['msg' => '月租车-车辆出场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_out_content1 . $data_end_time_text;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场横屏-月租车 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $mouth_in_content . $data_end_time_text;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'end_time' => $data_end_time];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'end_time' => $data_end_time];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-出场竖屏-月租车 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                break;
            case 'temporary_type':
            default:
                if ($passageDirection == 1) {
                    //todo 车辆入场
                    if ($is_log) {
                        fdump_api(['msg' => '车辆入场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_in_content;
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        if ($car_type == 'storedCar') {
                            $content .= ",余额{$stored_balance}元";
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场横屏 1-车牌 2-内容', 'car_number' => $car_number, 'content' => $content], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_in_content;
                        if (mb_strlen($content) > 6) {
                            $content = mb_substr($content, 0, 6);
                        }
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number];
                        $showContentArr[] = ['content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content];
                        if ($is_log) {
                            fdump_api(['msg' => '显屏-入场竖屏 1-车牌 2-内容', 'car_number' => $car_number, 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                        }
                    }
                }
                if ($passageDirection == 0) {
                    //todo 车辆出场
                    if ($is_log) {
                        fdump_api(['msg' => '车辆出场'], 'screen/addParkShowScreenLog', 1);
                    }
                    if ($deviceType == 1) {
                        //todo 横屏
                        if (empty($data_price)) {
                            $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content3;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => 0,
                            ];
                            if ($car_type == 'storedCar') {
                                $data_duration .= ",余额{$stored_balance}元";
                            }
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'duration' => $data_duration, 'price' => 0
                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场横屏（无金额） 1-车牌 2-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        } else {
                            if (empty($data['content'])) {
                                if ($data_voice_content == 9) {
                                    $content = '扣款' . $data_price . '元';
                                } else {
                                    $content = $temp_out_content2 . $data_price . '元';
                                }
                            } else {
                                $content = $data['content'];
                            }
                            $data_content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content1 . $data_duration_txt;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => $data_price,
                            ];
                            // 将金额+内容展示在第2行
                            $text = $content;
                            if ($car_type == 'storedCar') {
                                $text .= ",余额{$stored_balance}元";
                            }
                            $text .= ",".$data_content;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $text, 'duration' => $data_duration, 'price' => $data_price
                            ];
//                            $showContentArr[] = [
//                                'content_type' => 1, 'screen_row' => 3, 'serial' => '03', 'content' => $content, 'duration' => $data_duration, 'price' => $data_price
//                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场横屏（有金额） 1-车牌 2-金额内容+内容', 'data_price' => $data_price, 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        }
                    }
                    if ($deviceType == 2) {
                        //todo 竖屏
                        if (empty($data_price)) {
                            $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content3;
                            if (mb_strlen($content) > 6) {
                                $content = mb_substr($content, 0, 6);
                            }
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => 0,
                            ];
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $content, 'duration' => $data_duration, 'price' => 0
                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场竖屏（无金额） 2-车牌 3-内容', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        } else {
                            $content = isset($data['content']) && trim($data['content']) ? trim($data['content']) : $temp_out_content1 . $data_duration_txt;
                            if (mb_strlen($content) > 6) {
                                $content = mb_substr($content, 0, 6);
                            }
                            $content2 = isset($data['content']) && trim($data['content']) ? trim($data['content']) : '';
                            if (empty($content2)) {
                                if ($data_voice_content == 9) {
                                    $content2 = '扣款' . $data['price'] . '元,请通行';
                                } else {
                                    $content2 = $data['price'] . '元';
                                }
                            }
                            if (mb_strlen($content2) > 6) {
                                $content2 = mb_substr($content2, 0, 6);
                            }
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 1, 'serial' => '01', 'content' => $car_number, 'duration' => $data_duration, 'price' => $data_price,
                            ];
                            // 将金额+内容展示在第2行
                            $text = $content2 . $content;
                            $showContentArr[] = [
                                'content_type' => 1, 'screen_row' => 2, 'serial' => '02', 'content' => $text, 'duration' => $data_duration, 'price' => $data_price
                            ];
//                            $showContentArr[] = [
//                                'content_type' => 1, 'screen_row' => 3, 'serial' => '03', 'content' => $content2, 'duration' => $data_duration, 'price' => $data['price']
//                            ];
                            if ($is_log) {
                                fdump_api(['msg' => '显屏-出场竖屏（有金额） 1-车牌 2-金额+内容', 'data_price' => $data_price, 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                            }
                        }
                    }
                }
                break;
        }
        //todo 进场 语音
        if ($passageDirection == 1) {
            $voice_content = $data_voice_content ? $data_voice_content : 0;
            $showContentArr[] = [
                'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number, 'content' => $voice_content,
                'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
            ];
            if ($is_log) {
                fdump_api(['msg' => '进场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
            }
        }
        //todo 出场 语音
        if ($passageDirection == 0) {
            switch ($carType) {
                case 'temporary_type':
                    if (!empty($data_duration)) {
                        $voice_content = $data_voice_content ? $data_voice_content : 5;
                        $content = $voice_content;
                    } else {
                        $voice_content = $data_voice_content ? $data_voice_content : 2;
                        $content = $voice_content;
                    }
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '临时车-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
                case 'black_type':
                    $voice_content = $data_voice_content ? $data_voice_content : 5;
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $voice_content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '黑名单-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
                case 'month_type':
                    if ($data_end_time > 1) {
                        if (!$data_voice_content) {
                            $content = 4;
                        } else {
                            $content = $data_voice_content;
                        }
                    } else {
                        if (!$data_voice_content) {
                            $content = 2;
                        } else {
                            $content = $data_voice_content;
                        }
                    }
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '月租车-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
                default:
                    if (!$data_voice_content) {
                        $content = 2;
                    } else {
                        $content = $data_voice_content;
                    }
                    $showContentArr[] = [
                        'content_type' => 2, 'screen_row' => 2, 'serial' => '01', 'car_number' => $car_number,
                        'content' => $content, 'end_time' => $data_end_time, 'duration' => $data_duration, 'price' => $data_price
                    ];
                    if ($is_log) {
                        fdump_api(['msg' => '默认类型-出场-语音', 'showContentArr' => $showContentArr], 'screen/addParkShowScreenLog', 1);
                    }
                    break;
            }
        }

        $parkShowScreen = new D3ShowScreenService();
        $serialData = [];
        if ($showContentArr) {
            foreach ($showContentArr as $showKey => $item) {
                if ($item['content_type'] == 1) { //显屏
                    $showVoiceTxt = $parkShowScreen->showVoiceTxt($item['content'], $item['screen_row'], $item['serial'], $orderType, $passageType);
                } else { //语音
                    $showVoiceTxt = $parkShowScreen->showVoice($item, '01', $passageType);
                }
                if ($is_log) {
                    fdump_api(['msg' => '显屏语音解析', 'item' => $item, 'showVoiceTxt' => $showVoiceTxt], 'screen/addParkShowScreenLog', 1);
                }
//                $base64DataText = $parkShowScreen->translation($showVoiceTxt);
                $base64DataText = $parkShowScreen->handleBase64Bit16($showVoiceTxt);
                $base64DataTextLength = strlen($base64DataText);
                $serialData[] = [
                    'serialChannel' => 0,
                    'data' => $base64DataText,
                    'dataLen' => $base64DataTextLength,
                ];
            }
            if ($is_log) {
                fdump_api(['msg' => '处理好的相关信息', 'showContentArr' => $showContentArr, 'serialData' => $serialData], 'screen/addParkShowScreenLog', 1);
            }
        }
        $showscreen_log = [
            'village_id' => $village_id, 'park_sys_type' => 'A11', 'content' => $car_number, 'content_type' => 1, 'screen_row' => 1, 'serial' => '01',
            'car_number' => $car_number, 'channel_id' => $channel_id, 'park_type' => 1, 'add_time' => time(),
            'showcontent' => json_encode($serialData, JSON_UNESCAPED_UNICODE)
        ];
        if ($is_log) {
            fdump_api(['msg' => '处理好的相关信息', 'showscreen_log' => $showscreen_log], 'screen/addParkShowScreenLog', 1);
        }
        $logId = $db_park_showscreen_log->add($showscreen_log);
        if ($is_log) {
            fdump_api(['msg' => '处理好的相关信息', 'logId' => $logId], 'screen/addParkShowScreenLog', 1);
        }
        fdump_api(['参数进来了==' . __LINE__, [
            '$passageType' => $passageType,
            'car_number' => $data['car_number'],
            '$showContentArr' => $showContentArr,
            '$logId' => $logId,
            '$showscreen_log' => $showscreen_log
        ]], 'screen/addParkShowScreenLog', 1);
        return $logId;
    }
    
    /**
     * 清理超出一定时间的显屏语音.
     */
    public function clearOverShowScreen($data) {
        $showLimitTime = isset($data['showLimitTime']) && $data['showLimitTime'] ? intval($data['showLimitTime']) : 0;
        $nowTime = isset($data['nowTime']) && $data['nowTime'] ? intval($data['nowTime']) : time();
        $limitTime = isset($data['limitTime']) && $data['limitTime'] ? intval($data['limitTime']) : CarFlowConst::CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME;
        if ($showLimitTime <= 0) {
            $showLimitTime = $nowTime - $limitTime;
        }
        if ($showLimitTime <= 0) {
            return true;
        }
        $db_park_showscreen_log = new ParkShowscreenLog();
        $where = [];
        $where[] = ['add_time', '<=', $showLimitTime];
        return $db_park_showscreen_log->delOne($where);
    }
}