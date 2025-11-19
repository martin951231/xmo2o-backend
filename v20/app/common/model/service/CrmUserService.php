<?php

namespace app\common\model\service;

use app\common\model\db\Admin;
use app\common\model\db\CrmUser;
use net\Http;
use scrm\Qiye;
use think\Exception;

/**
 * Class CrmUserService
 * @package app\common\model\service
 */
class CrmUserService
{
    public $userMod = null;

    public function __construct()
    {
        $this->userMod = new CrmUser();
    }

    /**
     * 是否绑定企业微信
     * @author: 张涛
     * @date: 2021/03/24
     */
    public function isBind()
    {
        $isExisit = $this->userMod->whereRaw('1=1')->find();
        return $isExisit ? true : false;
    }

    /**
     * 注册用户，以系统后台超级管理员手机号注册
     * @author: 张涛
     * @date: 2021/03/16
     */
    public function register()
    {
        $user = $this->userMod->whereRaw('1=1')->find();
        if ($user) {
            return true;
        }
        $root = (new Admin())->getOne(['level' => 2, 'status' => 1]);
        $root = $root ? $root->toArray() : [];
        if (empty($root['phone'])) {
            throw new Exception('系统后台管理员未绑定手机号');
        }
        $passwd = create_random_str(10);
        $data = [
            'account' => $root['phone'],
            'password' => $passwd,
            'from_type' => cfg('crm_from_type'),
            'corp_name' => cfg('wechat_name')
        ];
        $qiye = new Qiye();
        $rs = $qiye->register($data);
        if ($rs['error'] != 0) {
            throw new Exception('注册用户失败：' . $rs['error_msg']);
        }
        $userData = [
            'uid' => $rs['data']['uid'],
            'account' => $root['phone'],
            'password' => $passwd,
            'access_token' => $rs['data']['access_token'],
            'expire_time' => 0
        ];
        $this->userMod->insert($userData);
        return true;
    }

    /**
     * 获取快速登录地址
     * @author: 张涛
     * @date: 2021/03/19
     */
    public function getLoginUrl()
    {
        $root = (new Admin())->getOne(['level' => 2, 'status' => 1]);
        $root = $root ? $root->toArray() : [];
        if (empty($root['phone'])) {
            throw new Exception('系统后台管理员未绑定手机号');
        }

        $crmUser = $this->userMod->where('account',$root['phone'])->findOrEmpty()->toArray();
        if(empty($crmUser)){
            throw new Exception('系统后台管理员未绑定手机号');
        }

        $accessToken = $this->getAccessToken(true);
        $param = ['auth_code' => $accessToken];
        $rs = (new Qiye())->setHeader($accessToken)->getLoginUrl($param);
        if ($rs['error'] != 0) {
            throw new Exception('获取登录地址失败：' . $rs['error_msg']);
        }
        return ['url' => $rs['data']['url'], 'account' => $crmUser['account'], 'password' => $crmUser['password']];
    }

    /**
     * 获取accesstoken
     * @author: 张涛
     * @date: 2021/03/17
     */
    public function getAccessToken($foceUpdate = false)
    {
        $user = $this->userMod->whereRaw('1=1')->find();
        if (empty($user)) {
            throw new Exception('未注册用户');
        }
        $tm = time();
        $sec = 3600;
        $qiye = new Qiye();
        if ($foceUpdate || empty($user->access_token) || $user->expire_time < $tm - $sec) {
            $param = ['account' => $user->account, 'password' => $user->password];
            $rs = $qiye->getAccessToken($param);
            if ($rs['error'] != 0) {
                throw new Exception('获取access_token失败：' . $rs['error_msg']);
            }
            $accessToken = $rs['data']['access_token'];
            $user->access_token = $accessToken;
            $user->expire_time = $tm + 3600;
            $user->save();
        } else {
            $accessToken = $user->access_token;
        }
        return $accessToken;
    }

    /**
     * 成员列表
     * @author: 张涛
     * @date: 2021/03/16
     */
    public function users()
    {
        //获取成员列表
        $qiye = new Qiye();
        $accessToken = $this->getAccessToken();
        $param = ['corp_id' => cfg('crm_corp_id'),'is_all'=>1];
        $qiyeUsers = $qiye->setHeader($accessToken)->getAllUser($param);
        return $qiyeUsers['data']['info'] ?? [];
    }


    /**
     * 根据活动id生成渠道活码
     * @author: 张涛
     * @date: 2021/03/17
     */
    public function buildChannelCodeByActivityId($id)
    {
        $activity = (new PrivateActivityService())->showActivity($id);
        $user = $this->userMod->whereRaw('1=1')->find();
        if (empty($user)) {
            throw new Exception('未注册用户');
        }
        if ($activity && $activity['status'] == 1 && $activity['is_del'] == 0) {
            $param = [
                'way_group_id' => 0,
                'title' => $activity['name'],
                'type' => 1,
                'scene' => 2,
                'remark' => '',
                'skip_verify' => 0,
                'verify_all_day' => 1,
                'state' => '',
                'style' => 1,
                'user' => [],
                'specialTime' => 0,
                'specialWeekList' => [
                    [
                        'mon' => [
                            [
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'userList' => $activity['qiye_uid'],
                                'party' => [],
                            ]
                        ],
                        'tues' => [
                            [
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'userList' => $activity['qiye_uid'],
                                'party' => [],
                            ]
                        ],
                        'wednes' => [
                            [
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'userList' => $activity['qiye_uid'],
                                'party' => [],
                            ]
                        ],
                        'thurs' => [
                            [
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'userList' => $activity['qiye_uid'],
                                'party' => [],
                            ]
                        ],
                        'fri' => [
                            [
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'userList' => $activity['qiye_uid'],
                                'party' => [],
                            ]
                        ],
                        'satur' => [
                            [
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'userList' => $activity['qiye_uid'],
                                'party' => [],
                            ]
                        ],
                        'sun' => [
                            [
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'userList' => $activity['qiye_uid'],
                                'party' => [],
                            ]
                        ],
                    ]
                ],
                'specialDateList' => [
                    [
                        [
                            'specialDate' => [],
                            'date' => [],
                            'time' => [
                                [

                                ]
                            ]
                        ]
                    ]
                ],
                'is_limit' => 1,
                'spare_employee' => [],
                'user_limit' => [],
                'party' => [],
                'tag_ids' => '',
                'is_welcome_week' => 1,
                'is_welcome_date' => 1,
                'suite_id' => 1,
                'corp_id' => cfg('crm_corp_id'),
                'uid' => $user['uid'],
                'status' => 1,
                'add_type' => 0,
                'text_content' => $activity['reply_txt'],
                'link_title' => '',
                'link_desc' => '',
                'link_url' => '',
                'mini_title' => '',
                'mini_appid' => '',
                'mini_page' => '',
                'attachment_id' => '',
                'materialSync' => 0,
                'groupId' => '',
                'verify_date' => [],
                'welcome_week_list' => [],
                'welcome_date_list' => []
            ];

            $qiye = new Qiye();
            $accessToken = $this->getAccessToken();
            $qiye = $qiye->setHeader($accessToken);

            //有图片上传图片
            $mediaId = 0;
            if ($activity['reply_pic']) {
                $localPic = app()->getRootPath() . '/..' . parse_url($activity['reply_pic'], PHP_URL_PATH);
                $ossPic = replace_file_domain($activity['reply_pic']);
                $md5 = md5($ossPic);
                if (!file_exists($localPic)) {
                    $localDir = dirname($localPic);
                    if (!file_exists($localDir)) {
                        mkdir($localDir, 0777, true);
                    }
                    Http::curlDownload($ossPic, $localPic);
                }
                $oldImage = \think\facade\Db::name('crm_image')->where('md5', $md5)->find();
                if ($oldImage && $oldImage['media_id']) {
                    $mediaId = $oldImage['media_id'];
                } else {
                    $picData = [
                        'uid' => $user['uid'],
                        'isMasterAccount' => 1,
                        'sub_id' => $user['uid'],
                        'file_type' => 1,
                        'is_sync' => 1
                    ];
                    $rs = $qiye->attachmentAdd($picData, $localPic);
                    if ($rs['error'] != 0) {
                        throw new Exception('上传图片失败：' . $rs['error_msg']);
                    }
                    $attachmentId = $rs['data']['attachment_id'];
                    $rs = $qiye->uploadMedia(['attachment_id' => $attachmentId, 'corp_id' => cfg('crm_corp_id')]);
                    if ($rs['error'] != 0) {
                        throw new Exception('同步媒体库失败：' . $rs['error_msg']);
                    }
                    $mediaId = $rs['data']['id'];

                    $imageRecord = [
                        'file_path' => $activity['reply_pic'],
                        'md5' => $md5,
                        'attachment_id' => $attachmentId,
                        'media_id' => $mediaId
                    ];
                    \think\facade\Db::name('crm_image')->insert($imageRecord);
                }

                $param['link_attachment_id'] = $param['media_id'] = $param['mini_pic_media_id'] = $mediaId;
                $param['add_type'] = 1;
            }

            //创建活码

            $wayRs = $qiye->workContactWayAdd($param);
            if ($wayRs['error'] != 0) {
                throw new Exception('获取渠道活码失败：' . $wayRs['error_msg']);
            }

            //获取活码地址
            $wayId = $wayRs['data']['way_id'];
            $param = ['corp_id' => cfg('crm_corp_id'), 'id' => $wayId];
            $qrcodeRs = $qiye->getQrcode($param);
            if ($qrcodeRs['error'] != 0) {
                throw new Exception('获取渠道活码地址：' . $qrcodeRs['error_msg']);
            }

            $record = [
                'qiye_uid' => $activity['qiye_uid'],
                'activity_id' => $id,
                'way_id' => $wayId,
                'wx_qrcode' => $qrcodeRs['data']['wx_qrcode'],
                'local_qrcode' => $qrcodeRs['data']['local_qrcode'],
                'media_id' => $mediaId,
                'create_time' => time()
            ];
            \think\facade\Db::name('crm_channel_code')->insert($record);
            return ['way_id' => $wayId, 'wx_qrcode' => $qrcodeRs['data']['wx_qrcode'], 'local_qrcode' => $qrcodeRs['data']['local_qrcode']];
        }else{
            return [];
        }
    }

    /**
     * 删除渠道码
     * @author: 张涛
     * @date: 2021/03/19
     */
    public function delChannelCode($wayId)
    {
        $qiye = new Qiye();
        $accessToken = $this->getAccessToken();
        $param = ['id' => $wayId];
        $wayRs = $qiye->setHeader($accessToken)->workContactWayDel($param);
        if ($wayRs['error'] != 0) {
            throw new Exception('删除渠道活码失败：' . $wayRs['error_msg']);
        }
        return true;
    }

    public function getCrmAdminUser()
    {
        $root = (new Admin())->getOne(['level' => 2, 'status' => 1]);
        $root = $root ? $root->toArray() : [];
        if (empty($root['phone'])) {
            throw new Exception('系统后台管理员未绑定手机号');
        }

        $crmUser = $this->userMod->where('account', $root['phone'])->findOrEmpty()->toArray();
//        if (empty($crmUser)) {
//            throw new Exception('系统后台管理员未绑定手机号');
//        }
        return $crmUser;
    }

}