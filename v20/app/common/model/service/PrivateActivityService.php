<?php

namespace app\common\model\service;

use app\common\model\db\PrivateActivity;
use app\common\model\db\PrivateActivityArea;
use app\common\model\db\PrivateActivityStore;
use app\merchant\model\db\MerchantStore;
use file_handle\FileHandle;
use think\Exception;
use think\facade\Db;

/**
 * 私域流量活动管理
 * @package app\common\model\service
 */
class PrivateActivityService
{
    public $activityMod = null;

    public $activityAreaMod = null;

    public $activityStoreMod = null;

    public function __construct()
    {
        $this->activityMod = new PrivateActivity();
        $this->activityAreaMod = new PrivateActivityArea();
        $this->activityStoreMod = new PrivateActivityStore();
    }

    protected function checkData($data)
    {
        if (empty($data['name'])) {
            throw new Exception('请填写活动名称');
        }
        if (empty($data['show_page'])) {
            throw new Exception('请选择展示页面');
        }
        if (empty($data['hover_pic'])) {
            throw new Exception('请上传悬浮图标');
        }
        if (empty($data['alert_pic'])) {
            throw new Exception('请上传弹层图片');
        }
        if (empty($data['qiye_uid'])) {
            throw new Exception('请选择企业微信成员二维码');
        }
    }

    /**
     * 创建活动
     * @param $data
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function saveActivity($data)
    {
        $this->checkData($data);
        $id = $data['id'] ?? 0;
        $activity = [];
        if ($id > 0) {
            $activity = $this->activityMod->where('id', $id)->find();
            if (empty($activity)) {
                throw new Exception('请选择一条记录');
            }
        }

        if (is_array($data['show_page'])) {
            $data['show_page'] = array_sum($data['show_page']);
        }

        //图片均去掉域名保存到数据库
        $data['hover_pic'] && $data['hover_pic'] = parse_url($data['hover_pic'], PHP_URL_PATH);
        $data['alert_pic'] && $data['alert_pic'] = parse_url($data['alert_pic'], PHP_URL_PATH);
        $data['style_tpl_pic'] && $data['style_tpl_pic'] = parse_url($data['style_tpl_pic'], PHP_URL_PATH);
        $data['reply_pic'] && $data['reply_pic'] = parse_url($data['reply_pic'], PHP_URL_PATH);

        $activityData = [
            'name' => $data['name'],
            'show_page' => $data['show_page'],
            'hover_pic' => $data['hover_pic'],
            'alert_pic' => $data['alert_pic'],
            'qiye_uid' => $data['qiye_uid'],
            'qrcode_style' => $data['qrcode_style'],
            'style_tpl_pic' => $data['style_tpl_pic'] ?? '',
            'is_point_area' => $data['is_point_area'],
            'is_point_store' => $data['is_point_store'],
            'reply_txt' => $data['reply_txt'] ?? '',
            'reply_pic' => $data['reply_pic'] ?? '',
            'status' => $data['status'],
        ];
        Db::startTrans();
        try {
            $buildChannelCode = false;
            if ($id > 0) {
                $this->activityMod->where('id', $id)->update($activityData);
                if (!$activity['way_id'] || $activity['name'] != $activityData['name'] || $activity['qiye_uid'] != $activityData['qiye_uid'] || $activity['reply_txt'] != $activityData['reply_txt'] || $activity['reply_pic'] != $activityData['reply_pic']) {
                    $buildChannelCode = true;
                }
            } else {
                $activityData['create_time'] = time();
                $id = $this->activityMod->insertGetId($activityData);
                $buildChannelCode = true;
            }

            //生成渠道码
            if ($buildChannelCode) {
                if (!empty($activity['way_id'])) {
                    (new CrmUserService())->delChannelCode($activity['way_id']);
                }

                $codeRs = (new CrmUserService())->buildChannelCodeByActivityId($id);
                $this->activityMod->where('id', $id)->update($codeRs);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    /**
     * 根据ID删除活动
     * @param $ids
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function delActivityByIds($ids)
    {
        if (empty($ids)) {
            throw new Exception('请选择一条记录');
        }
        $this->activityMod->whereIn('id', $ids)->update(['is_del' => 1]);
        return true;
    }

    /**
     * 获取活动列表
     * @param $params
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function activityLists($params, $sort = ['id' => 'desc'])
    {
        $where = [];
        if (isset($params['is_del'])) {
            $where['is_del'] = $params['is_del'];
        }
        if (isset($params['id'])) {
            $where['id'] = $params['id'];
        }
        $total = $this->activityMod->where($where)->count();
        if ($total > 0) {
            $lists = $this->activityMod->where($where)->page($params['page'], $params['pageSize'])->order($sort)->select()->toArray();
        } else {
            $lists = [];
        }

        $areaService = new PrivateActivityAreaService();
        $storeService = new PrivateActivityStoreService();
        foreach ($lists as &$v) {
            $v['create_time'] = date('Y/m/d', $v['create_time']);
            if ($v['is_point_area']) {
                $v['area_ids'] = $areaService->getAreaIdsByAid($v['id']);
            } else {
                $v['area_ids'] = [];
            }
            if ($v['is_point_store']) {
                $v['store_ids'] = $storeService->getStoreIdsByAid($v['id']);
            } else {
                $v['store_ids'] = [];
            }
        }
        return ['list' => $lists, 'total' => $total];
    }

    /**
     * 活动指定区域
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function assignArea($id, $areaIds)
    {
        $areaIds = array_filter(array_unique($areaIds));
        $data = [];
        foreach ($areaIds as $areaId) {
            $data[] = [
                'aid' => $id,
                'area_id' => $areaId
            ];
        }
        $this->activityAreaMod->where('aid', $id)->delete();
        return $this->activityAreaMod->insertAll($data);
    }

    /**
     * 活动指定店铺
     * @author: 张涛
     * @date: 2021/02/25
     */
    public function assignStore($id, $storeIds, $operate)
    {
        if ($operate == 'bind') {
            //绑定
            foreach ($storeIds as $storeId) {
                $isExisit = $this->activityStoreMod->where('aid', $id)->where('store_id', $storeId)->find();
                if ($isExisit) {
                    continue;
                }
                $data = [
                    'aid' => $id,
                    'store_id' => $storeId
                ];
                $this->activityStoreMod->insert($data);
            }
            return true;
        } else {
            //解绑
            return $this->activityStoreMod->where('aid', $id)->whereIn('store_id', $storeIds)->delete();
        }
    }

    public function showActivity($id)
    {
        $detail = $this->activityMod->where('id', $id)->findOrEmpty()->toArray();
        $detail['hover_pic'] && $detail['hover_pic'] = replace_file_domain($detail['hover_pic']);
        $detail['alert_pic'] && $detail['alert_pic'] = replace_file_domain($detail['alert_pic']);
        $detail['style_tpl_pic'] && $detail['style_tpl_pic'] = replace_file_domain($detail['style_tpl_pic']);
        $detail['reply_pic'] && $detail['reply_pic'] = replace_file_domain($detail['reply_pic']);
        return $detail;
    }

    /**
     * 私域流量指定店铺列表
     * @author: 张涛
     * @date: 2021/03/11
     */
    public function storeLists($params)
    {
        $storeMod = new MerchantStore();
        $where = [['m.status', '=', 1],['ms.status', '=', 1]];
        $searchType = $params['search_type'] ?? '';
        $keyword = $params['keyword'] ?? '';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 20;
        if (isset($params['is_selected']) && $params['is_selected'] > -1) {
            if ($params['is_selected'] == 1) {
                $where[] = ['s.id', '>', 0];
            } else {
                $where[] = ['s.id', 'exp', Db::raw('IS NULL')];
            }
        }
        $ext = '';
        if (isset($params['activity_id']) && $params['activity_id'] > 0) {
            $ext = ' AND s.aid = ' . $params['activity_id'];
        }
        if ($searchType && $keyword) {
            if ($searchType == 'merchant_name') {
                $where[] = ['m.name', 'like', '%' . $keyword . '%'];
            } else if ($searchType == 'store_name') {
                $where[] = ['ms.name', 'like', '%' . $keyword . '%'];
            }
        }

        $total = $storeMod->alias('ms')
            ->leftJoin('merchant m', 'ms.mer_id = m.mer_id')
            ->leftJoin('private_activity_store s', 's.store_id = ms.store_id'.$ext)
            ->field('ms.store_id')
            ->where($where)->count();

        $lists = [];
        if ($total > 0) {
            $lists = $storeMod->alias('ms')
                ->leftJoin('merchant m', 'ms.mer_id = m.mer_id')
                ->leftJoin('private_activity_store s', 's.store_id = ms.store_id' . $ext)
                ->field('ms.store_id,ms.name AS store_name,m.name AS merchant_name,IF(s.aid,1,0) AS is_selected')
                ->where($where)
                ->page($page, $pageSize)
                ->order('ms.store_id', 'asc')
                ->select()
                ->toArray();
        }
        return ['list' => $lists, 'total' => $total];
    }

    /**
     * 生成弹层图片
     * @author: 张涛
     * @date: 2021/03/11
     */
    public function buildAlertPic($params)
    {
        $tplId = $params['tpl_id'] ?? 0;
        $title = $params['title'] ?? '';
        $subTitle = $params['sub_title'] ?? '';
        if (empty($tplId)) {
            throw new Exception('请选择模板');
        }
        if (empty($title)) {
            throw new Exception('主标题不能为空');
        }
        if (empty($subTitle)) {
            throw new Exception('附标题不能为空');
        }

        $font = app()->getRootPath() . '/../static/fonts/apple_lihei.otf';
        $fontBold = app()->getRootPath() . '/../static/fonts/apple_lihei_bold.otf';
        $config = [
            '1' => [
                'file_path' => app()->getRootPath() . '/../static/images/qiye_private_flow/tpl1.png',
                'main' => ['font' => $fontBold, 'size' => 45, 'color' => '#FF7B23', 'locate' => 8, 'offset' => [0, -610]],
                'sub' => ['font' => $font, 'size' => 25, 'color' => '#FF7B23', 'locate' => 8, 'offset' => [0, -550]],
            ],
            '2' => [
                'file_path' => app()->getRootPath() . '/../static/images/qiye_private_flow/tpl2.png',
                'main' => ['font' => $fontBold, 'size' => 25, 'color' => '#FEFBE9', 'locate' => 8, 'offset' => [0, -290]],
                'sub' => ['font' => $font, 'size' => 15, 'color' => '#FEFBE9', 'locate' => 8, 'offset' => [0, -250]],
            ],
            '3' => [
                'file_path' => app()->getRootPath() . '/../static/images/qiye_private_flow/tpl3.png',
                'main' => ['font' => $fontBold, 'size' => 50, 'color' => '#FFFFFF', 'locate' => 8, 'offset' => [0, -400]],
                'sub' => ['font' => $font, 'size' => 26, 'color' => '#FDD2A5', 'locate' => 8, 'offset' => [0, -320]],
            ],
            '4' => [
                'file_path' => app()->getRootPath() . '/../static/images/qiye_private_flow/tpl4.png',
                'main' => ['font' => $fontBold, 'size' => 50, 'color' => '#F08537', 'locate' => 8, 'offset' => [0, -470]],
                'sub' => ['font' => $font, 'size' => 26, 'color' => '#F29956', 'locate' => 8, 'offset' => [0, -400]],
            ]
        ];
        $thisConfig = $config[$tplId] ?? [];
        if (empty($thisConfig)) {
            throw new Exception('模板未适配');
        }


        $fileName = uniqid('', true) . '.png';
        $uploadDir = app()->getRootPath() . '../upload/private_flow/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileUrl = cfg('site_url') . '/upload/private_flow/' . $fileName;
        $saveFile = $uploadDir . $fileName;
        $image = \think\Image::open($thisConfig['file_path']);
        $image->text($title, $thisConfig['main']['font'], $thisConfig['main']['size'], $thisConfig['main']['color'], $thisConfig['main']['locate'], $thisConfig['main']['offset'])
            ->text($subTitle, $thisConfig['sub']['font'], $thisConfig['sub']['size'], $thisConfig['sub']['color'], $thisConfig['sub']['locate'], $thisConfig['sub']['offset'])
            ->save($saveFile);


        $ossRs = (new FileHandle())->upload($saveFile);
        return ['url' => replace_file_domain($fileUrl)];
    }
}