<?php
/**
 * 企业微信
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/19 14:31
 */

namespace app\community\model\service;

use app\community\model\db\VillageQywxImgContent;

class  VillageQywxService{

    /**
     * Notes: 添加图片
     * @param $data
     * {
     *    'img_url' => '图片链接',
     *    'name' => '名称',
     *    'business_type' => '业务区分', 业务类别 1 渠道活码  2 内容引擎
     *    'business_id' => '对应业务id',
     *    'gid' => '对应业务归属父组id'
     * }
     * @return mixed
     * @throws \Exception
     * @author: wanzy
     * @date_time: 2021/3/19 14:39
     */
    public function addVillageQywxImgContent($data) {
        if (!isset($data['img_url']) || !isset($data['name']) || !isset($data['business_type']) || !isset($data['business_id'])|| !isset($data['gid'])) {
            throw new \Exception('请传递正确齐全的参数');
        }
        if (!$data['img_url'] || !$data['name'] || !$data['business_type'] || !$data['business_id']|| !$data['gid']) {
            throw new \Exception('请传递正确齐全的参数');
        }
        $data['add_time'] = time();
        $dbVillageQywxImgContent = new VillageQywxImgContent();
        $add_id = $dbVillageQywxImgContent->add($data);
        return $add_id;
    }

    /**
     * Notes:
     * @param int $business_id 业务类别 1 渠道活码  2 内容引擎
     * @param int $business_type 对应业务id
     * @return bool
     * @throws \Exception
     * @author: wanzy
     * @date_time: 2021/3/19 14:44
     */
    public function delVillageQywxImgContent($business_id, $business_type=1) {
        $dbVillageQywxImgContent = new VillageQywxImgContent();
        if ($business_id && $business_type) {
            $where = [];
            $where[] = ['business_id', '=', $business_id];
            $where[] = ['business_type', '=', $business_type];
            $del_id = $dbVillageQywxImgContent->delOne($where);
            return $del_id;
        } else {
            throw new \Exception('请传递正确齐全的参数');
        }
    }
}