<?php
namespace app\community\model\service;

use think\facade\Db;
use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\workorder\HouseCommunityAboutService;

class CommunityAgentRepairPolicy
{
    public static function normalize(array $input)
    {
        $out = [];
        foreach (['cat_id', 'address_id'] as $field) {
            $value = $input[$field] ?? 0;
            if ((!is_int($value) && !is_string($value)) || !ctype_digit((string)$value) || strlen((string)$value) > 10) {
                throw new \InvalidArgumentException('工单分类或位置格式错误');
            }
            $out[$field] = (int)$value;
        }
        $out['address_type'] = $input['address_type'] ?? 'room';
        if (!in_array($out['address_type'], ['room','public'], true)) { throw new \InvalidArgumentException('工单位置类型错误'); }
        $content = $input['order_content'] ?? '';
        if (!is_string($content) || mb_strlen($content, 'UTF-8') > 1000 || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $content)) {
            throw new \InvalidArgumentException('工单描述不能超过1000字或包含无效字符');
        }
        $out['order_content'] = trim($content);
        $images = $input['order_imgs'] ?? [];
        if (!is_array($images) || count($images) > 8) { throw new \InvalidArgumentException('最多上传8张图片'); }
        foreach ($images as $image) {
            if (!is_string($image) || !preg_match('#^/upload/work_order/[a-zA-Z0-9/_-]+\.(jpg|jpeg|png|gif|webp)$#iD', $image) || strlen($image) > 512) {
                throw new \InvalidArgumentException('请使用工单上传接口返回的图片');
            }
        }
        $out['order_imgs'] = array_values(array_unique($images));
        return $out;
    }

    public static function missing(array $form, $imageRequired = false)
    {
        $missing = [];
        foreach (['cat_id','address_id','order_content'] as $key) {
            if (!isset($form[$key]) || $form[$key] === '' || $form[$key] === 0) { $missing[] = $key; }
        }
        if ($imageRequired && empty($form['order_imgs'])) { $missing[] = 'order_imgs'; }
        return $missing;
    }

    public static function metadata(array $session, array $resident)
    {
        $all = Db::name('house_new_repair_cate')->where(['village_id'=>$session['village_id'],'status'=>1])
            ->field('id,parent_id,cate_name')->order('sort desc,id desc')->select()->toArray();
        $parents = [];
        foreach ($all as $row) { if (!(int)$row['parent_id']) { $parents[$row['id']] = $row['cate_name']; } }
        $categories = [];
        foreach ($all as $row) {
            if (isset($parents[$row['parent_id']])) {
                $categories[] = ['id'=>(int)$row['id'],'parent_id'=>(int)$row['parent_id'],
                    'label'=>$parents[$row['parent_id']].' / '.$row['cate_name']];
            }
        }
        $room = $resident['room'];
        $roomName = (new HouseVillageService())->getSingleFloorRoom($room['single_id'],$room['floor_id'],$room['layer_id'],$room['pigcms_id'],$session['village_id']);
        $addresses = [['id'=>(int)$room['pigcms_id'], 'type'=>'room','label'=>$roomName]];
        $public = Db::name('house_village_public_area')->where(['village_id'=>$session['village_id'],'status'=>1])->field('public_area_id,public_area_name')->select()->toArray();
        foreach ($public as $row) { $addresses[] = ['id'=>(int)$row['public_area_id'],'type'=>'public','label'=>$row['public_area_name']]; }
        $required = false;
        if ((new ConfigCustomizationService())->getWSXCustomizedOpen()) {
            $config = (new HouseCommunityAboutService())->getHouseCommunityConfig($session['village_id']);
            $required = (int)($config['is_pic_video'] ?? 0) === 1;
        }
        $night = (new HouseNewRepairService())->checkWorkOrder($session['village_id']);
        $blocking = !empty($night['is_night']) ? '当前是晚班时间，请拨打工作人员电话提交工单' : '';
        if (!$categories) { $blocking = '小区暂无可用工单分类，请联系物业配置'; }
        return ['repair_categories'=>$categories,'repair_addresses'=>$addresses,'image_required'=>$required,
            'max_images'=>8,'contact_name'=>$resident['bind']['name'],'contact_phone'=>$resident['bind']['phone'],
            'blocking_reason'=>$blocking];
    }

    public static function validateSelection(array $form, array $meta)
    {
        $category = null;
        foreach ($meta['repair_categories'] as $item) { if ($item['id'] === $form['cat_id']) { $category = $item; break; } }
        if ($form['cat_id'] && !$category) { throw new \InvalidArgumentException('工单分类已停用或不属于当前小区，请重新选择'); }
        $address = false;
        foreach ($meta['repair_addresses'] as $item) { if ($item['id'] === $form['address_id'] && $item['type'] === $form['address_type']) { $address = true; break; } }
        if ($form['address_id'] && !$address) { throw new \InvalidArgumentException('请选择自己绑定的房屋或本小区公共区域'); }
        return $category;
    }
}
