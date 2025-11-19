<?php

declare(strict_types=1);

namespace app\channel_code\model\service;

use app\channel_code\model\model\ChanelMsgContent;
use app\channel_code\model\model\ChanelMsgList;
use Exception;
use think\facade\Db;

use function PHPSTORM_META\type;

class ChanelMsgListService
{
    protected $chanelMsgListMod, $chanelMsgContentMod;

    public function __construct()
    {
        $this->chanelMsgListMod = new ChanelMsgList();
        $this->chanelMsgContentMod = new ChanelMsgContent();
    }

    /**
     * 获取渠道码列表
     *
     * @param integer $type  1：平台渠道码   2：商家渠道码  
     * @param array $params  筛选参数
     * @return void
     * @author: zt
     * @date: 2024/03/23
     */
    public function getLists($type = 0, $params = [])
    {
        $where = [];
        if ($type == 1) {
            //平台渠道码
            $where[] = ['type_id', '=', 0];
        } else if ($type == 2) {
            //商家渠道码
            $where[] = ['type_id', '>', 0];
        }

        if (!empty($params['title'])) {
            $where[] = ['title', 'like', '%' . $params['title'] . '%'];
        }

        $lists = $this->chanelMsgListMod->where($where)->paginate(20)->each(function ($item) {
            $item->add_time = date('Y-m-d H:i:s', $item->add_time);
            $item->last_time = date('Y-m-d H:i:s', $item->last_time);
            $item->qrcode = cfg('site_url') . '/index.php?g=Index&c=Recognition&a=see_qrcode&type=chanel&id=' . $item->chanel_id;
        });

        return $lists;
    }

    private function check($params)
    {
        if (empty($params['name'])) {
            throw new Exception('标题不能为空');
        }
        if ($params['qrcode_type'] == 1) {
            if ($params['days'] < 1 || $params['days'] > 30) {
                throw new Exception('临时二维码最长有效期不能超过30天');
            }
        } else {
            $params['days'] = 0;
        }

        if ($params['reply_type'] == 0) {
            //文本内容
            if (empty($params['content'])) {
                throw new Exception('文字内容不能为空');
            }
            $params['title'] = $params['des'] = $params['img'] = $params['url'] = '';
        } else if ($params['reply_type'] == 1) {
            //图文内容
            if (empty($params['title'])) {
                throw new Exception('图文消息标题不能为空');
            }
            if (empty($params['des'])) {
                throw new Exception('图文消息描述不能为空');
            }
            if (empty($params['img'])) {
                throw new Exception('图文消息图片不能为空');
            }
            if (empty($params['url'])) {
                throw new Exception('图文消息链接不能为空');
            }
            $params['content'] = '';
        } else {
            throw new Exception('回复内容选择有误');
        }
        return $params;
    }

    /**
     * 添加渠道码
     *
     * @return void
     * @author: zt
     * @date: 2024/03/23
     */
    public function add($params)
    {
        $tm = time();
        $params = $this->check($params);

        $data = [
            'qrcode_id' => 0,
            'title' => $params['name'],
            'type_id' => $params['type_id'],
            'status' => 1,
            'qrcode_type' => $params['qrcode_type'],
            'days' => $params['days'],
            'reply_type' => $params['reply_type'],
            'content' => $params['content'],
            'add_time' => $tm,
            'last_time' => $tm
        ];

        $this->chanelMsgListMod->startTrans();
        try {
            $fid = $this->chanelMsgListMod->insertGetId($data);

            $cData = [
                'fid' => $fid,
                'title' => $params['title'],
                'img' => $params['img'],
                'des' => $params['des'],
                'url' => $params['url'],
                'add_time' => $tm,
                'last_time' => $tm
            ];
            $this->chanelMsgContentMod->insert($cData);

            $this->chanelMsgListMod->commit();
        } catch (Exception $e) {
            $this->chanelMsgListMod->rollback();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function edit($params)
    {
        $tm = time();
        $params = $this->check($params);
        $channelId = $params['chanel_id'] ?? 0;
        if ($channelId <= 0) {
            throw new Exception('请选择一条渠道码记录');
        }

        $thisChannelCode = $this->chanelMsgListMod->where('chanel_id', $channelId)->find();
        if (empty($thisChannelCode)) {
            throw new Exception('渠道码不存在');
        }

        $data = [
            'title' => $params['name'],
            'type_id' => $params['type_id'],
            'status' => 1,
            'qrcode_type' => $params['qrcode_type'],
            'days' => $params['days'],
            'reply_type' => $params['reply_type'],
            'content' => $params['content'],
            'last_time' => $tm
        ];

        $this->chanelMsgListMod->startTrans();
        try {
            $this->chanelMsgListMod->where('chanel_id', $channelId)->update($data);

            //图文类型编辑
            if ($data['reply_type'] == 1) {
                $cData = [
                    'fid' => $channelId,
                    'title' => $params['title'],
                    'img' => $params['img'],
                    'des' => $params['des'],
                    'url' => $params['url'],
                    'last_time' => $tm
                ];
                if ($thisChannelCode->reply_type == 0) {
                    $cData['add_time'] = $tm;
                    $this->chanelMsgContentMod->insert($cData);
                } else {
                    $this->chanelMsgContentMod->where('fid', $channelId)->update($cData);
                }
            }

            $this->chanelMsgListMod->commit();
        } catch (Exception $e) {
            $this->chanelMsgListMod->rollback();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function delByChannelId($channelId)
    {
        $this->chanelMsgListMod->where('chanel_id', $channelId)->delete();
        $this->chanelMsgContentMod->where('fid', $channelId)->delete();
        return true;
    }

    public function changeStatusByChannelId($channelId)
    {
        $code = $this->chanelMsgListMod->where('chanel_id', $channelId)->find();
        $code->status = $code->status == 0 ? 1 : 0;
        return $code->save();
    }

    public function getDetailByChannelId($channelId)
    {
        $msg = $this->chanelMsgListMod->where('chanel_id', $channelId)->findOrEmpty()->toArray();
        if (empty($msg)) {
            throw new Exception('渠道码不存在');
        }
        $content = $this->chanelMsgContentMod->where('fid', $channelId)->findOrEmpty()->toArray();
        return [
            'chanel_id' => $msg['chanel_id'],
            'type_id' => $msg['type_id'],
            'name' => $msg['title'],
            'qrcode_type' => $msg['qrcode_type'],
            'days' => $msg['days'],
            'reply_type' => $msg['reply_type'],
            'content' => $msg['content'],
            'title' => $content['title'] ?? '',
            'des' =>  $content['des'] ?? '',
            'img' =>  $content['img'] ?? '',
            'img_preview' =>  replace_file_domain($content['img'] ?? ''),
            'url' =>  $content['url'] ?? '',
        ];
    }

    public function getCharts($params = [])
    {
        $channelId = $params['chanel_id'] ?? 0;
        $startTime = !empty($params['start_time']) ? strtotime($params['start_time']) : strtotime(date('Y-m-d', strtotime('-15 days')));
        $endTime = !empty($params['end_time']) ? strtotime($params['end_time']) : strtotime(date('Y-m-d', time()));

        $thisChannelCode = $this->chanelMsgListMod->where('chanel_id', $channelId)->find();
        if (empty($thisChannelCode)) {
            throw new Exception('渠道码不存在');
        }
        $qrcodeId = $thisChannelCode->qrcode_id;
        $rs = [];
        while ($startTime <= $endTime) {
            //计算扫码次数
            $scanCount = Db::name('wechat_event_logs')->whereIn('event_key', [$qrcodeId, 'qrscene_' . $qrcodeId])
                ->where('create_time', 'between', [$startTime, $startTime + 86400])
                ->count();

            //计算关注次数
            $followCount = Db::name('wechat_event_logs')->whereIn('event_key', [$qrcodeId, 'qrscene_' . $qrcodeId])
                ->where('event', 'subscribe')
                ->where('create_time', 'between', [$startTime, $startTime + 86400])
                ->count('distinct openid');
            $rs[] = [
                'product'=> date('m-d', $startTime),
                '新增扫码'=>(int)$scanCount,
                '新增关注'=>(int)$followCount
            ];
            
            $startTime += 86400;
        }
        return $rs;
    }


    public function getTodayStatistics($channelId)
    {
        $today = strtotime(date('Y-m-d', time()));
        $yesterday = strtotime(date('Y-m-d', strtotime('-1 day')));
        $thisChannelCode = $this->chanelMsgListMod->where('chanel_id', $channelId)->find();
        $qrcodeId = $thisChannelCode->qrcode_id;
        $todayScanCount = Db::name('wechat_event_logs')->whereIn('event_key', [$qrcodeId, 'qrscene_' . $qrcodeId])
            ->where('create_time', 'between', [$today, $today + 86400])
            ->count();
        $yesterdayScanCount = Db::name('wechat_event_logs')->whereIn('event_key', [$qrcodeId, 'qrscene_' . $qrcodeId])
            ->where('create_time', 'between', [$yesterday, $yesterday + 86400])
            ->count();
        $todayFollowCount = Db::name('wechat_event_logs')->whereIn('event_key', [$qrcodeId, 'qrscene_' . $qrcodeId])
            ->where('event', 'subscribe')
            ->where('create_time', 'between', [$today, $today + 86400])
            ->count('distinct openid');
        $yesterdayFollowCount = Db::name('wechat_event_logs')->whereIn('event_key', [$qrcodeId, 'qrscene_' . $qrcodeId])
            ->where('event', 'subscribe')
            ->where('create_time', 'between', [$yesterday, $yesterday + 86400])
            ->count('distinct openid');
        $totalScanCount = Db::name('wechat_event_logs')->whereIn('event_key', [$qrcodeId, 'qrscene_' . $qrcodeId])
            ->count();
        return [
            [
                'title' => '今日扫码次数',
                'value' => $todayScanCount,
                'percent' => $yesterdayScanCount > 0 ? round(($todayScanCount - $yesterdayScanCount) / $yesterdayScanCount * 100, 2) : 0,
                'show_percent' => 1
            ],
            [
                'title' => '今日新增关注用户',
                'value' => $todayFollowCount,
                'percent' => $yesterdayFollowCount > 0 ? round(($todayFollowCount - $yesterdayFollowCount) / $yesterdayFollowCount * 100, 2) : 0,
                'show_percent' => 1
            ],
            [
                'title' => '总扫码次数',
                'value' => $totalScanCount,
                'percent' => 0,
                'show_percent' => 0
            ]
        ];
    }
}
