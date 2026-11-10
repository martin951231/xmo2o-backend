<?php
namespace app\community\model\service;

use think\facade\Db;

/** Booking form adapter. Identity and the submission token never come from the model. */
class CommunityAgentBookingService
{
    public static function normalize(array $input)
    {
        if (($input['booking_kind'] ?? '') === 'activity') { return CommunityAgentActivityService::normalize($input); }
        $form = [];
        foreach (['booking_kind','day','slot','remark','contact_name','contact_phone','address_text','quote'] as $key) {
            $value = $input[$key] ?? '';
            if (!is_string($value) || mb_strlen($value) > ($key === 'remark' ? 1000 : 500)) { throw new \InvalidArgumentException('预约信息格式错误'); }
            $form[$key] = trim($value);
        }
        if (!in_array($form['booking_kind'], ['', 'merchant', 'venue'], true)) { throw new \InvalidArgumentException('请选择预约类型'); }
        foreach (['item_id','product_id','store_id','worker_id'] as $key) {
            $value = $input[$key] ?? 0;
            if ((!is_int($value) && !is_string($value)) || !ctype_digit((string)$value) || strlen((string)$value) > 10) { throw new \InvalidArgumentException('预约选项格式错误'); }
            $form[$key] = (int)$value;
        }
        foreach (['latitude','longitude'] as $key) {
            $value = $input[$key] ?? 0;
            if (!is_numeric($value) || abs((float)$value) > ($key === 'latitude' ? 90 : 180)) { throw new \InvalidArgumentException('服务位置坐标无效'); }
            $form[$key] = (float)$value;
        }
        $form['custom_values'] = $input['custom_values'] ?? [];
        if (!is_array($form['custom_values']) || count($form['custom_values']) > 50) { throw new \InvalidArgumentException('预约表单过长'); }
        foreach ($form['custom_values'] as $value) {
            if (!is_string($value) || mb_strlen($value) > 1000) { throw new \InvalidArgumentException('预约表单格式错误'); }
        }
        $form['custom_locations'] = $input['custom_locations'] ?? [];
        if (!is_array($form['custom_locations']) || count($form['custom_locations']) > 50) { throw new \InvalidArgumentException('服务位置格式错误'); }
        foreach ($form['custom_locations'] as &$location) {
            if (!is_array($location) || !is_numeric($location['latitude'] ?? null) || !is_numeric($location['longitude'] ?? null)
                || abs($location['latitude']) > 90 || abs($location['longitude']) > 180) { throw new \InvalidArgumentException('服务位置坐标无效'); }
            $location = ['latitude'=>(float)$location['latitude'],'longitude'=>(float)$location['longitude']];
        }
        unset($location);
        return $form;
    }

    public static function metadata(array $session, array $form)
    {
        if ($form['booking_kind'] === 'activity') { return CommunityAgentActivityService::metadata($session,$form); }
        if ($form['booking_kind'] === 'merchant') { return self::bridge($session, 'metadata', $form); }
        $meta = ['booking_items'=>[], 'booking_fields'=>[], 'missing_fields'=>[], 'blocking_reason'=>'', 'quote'=>'', 'price_label'=>''];
        if (!$form['booking_kind']) { $meta['missing_fields'] = ['booking_kind']; return $meta; }
        $items = Db::name('house_venue_activity')->where(['village_id'=>$session['village_id'], 'is_del'=>0, 'is_appoint'=>1])->order('sort desc,id desc')->select()->toArray();
        foreach ($items as $item) { $meta['booking_items'][] = ['id'=>(int)$item['id'], 'label'=>$item['title']]; }
        if (!$form['item_id']) { $meta['missing_fields'][] = 'item_id'; return $meta; }
        $item = null;
        foreach ($items as $row) { if ((int)$row['id'] === $form['item_id']) { $item = $row; break; } }
        if (!$item) { throw new \InvalidArgumentException('场馆不存在或不属于当前小区'); }
        $days = [];
        for ($i=0; $i < min(366, (int)$item['appoint_cycle']); $i++) {
            $day = date('Y-m-d', strtotime('+'.$i.' day'));
            $days[] = ['id'=>$day, 'label'=>$day];
        }
        $meta['booking_fields'][] = ['key'=>'day','label'=>'预约日期','kind'=>'select','required'=>true,'options'=>$days];
        if ($form['day'] && !in_array($form['day'], array_column($days, 'id'), true)) { throw new \InvalidArgumentException('预约日期已过期，请重新选择'); }
        $slots = [];
        if ($form['day']) {
            $available = (new HouseVenueActivityService())->choiceDate(['village_id'=>$session['village_id'], 'activity_id'=>$form['item_id'], 'day'=>$form['day']]);
            foreach ($available as $slot) {
                $parts = explode('-', $slot['time']);
                if ($slot['appoint_status'] && strtotime($form['day'].' '.$parts[1]) > time()) { $slots[] = ['id'=>$slot['time'], 'label'=>$slot['time'].'（余'.max(0,$slot['appoint_total']-$slot['appoint_num']).'）']; }
            }
            if (!$slots) { $meta['blocking_reason'] = '当天暂无可预约时段，请更换日期'; }
        }
        $meta['booking_fields'][] = ['key'=>'slot','label'=>'预约时段','kind'=>'select','required'=>true,'options'=>$slots];
        foreach (['day','slot'] as $key) { if (!$form[$key]) { $meta['missing_fields'][] = $key; } }
        if ($form['slot'] && !in_array($form['slot'], array_column($slots,'id'), true)) { $meta['blocking_reason'] = '该时段已满或已过期，请重新选择'; }
        $meta['price_label'] = (int)$item['is_examine'] === 1 ? '提交后自动审核' : '提交后等待管理员审核';
        foreach (['contact_name','contact_phone'] as $key) { if (!$form[$key]) { $meta['missing_fields'][]=$key; } }
        if ($form['contact_phone'] && !preg_match('/^[0-9+() -]{5,20}$/D',$form['contact_phone'])) { $meta['blocking_reason']='请填写有效联系电话'; }
        return $meta;
    }

    public static function bridge(array $session, $operation, array $form = [])
    {
        $payload = json_decode($session['payload'], true);
        // The generic invoke helper logs all arguments; keep the private proof and resident form out of those logs.
        $site = cfg('config_site_url'); $time = time();
        $body = ['func'=>'CommunityAgentBooking/gateway', 'params'=>urlencode(serialize([$session['id'], $payload['booking_token'] ?? '', $operation, $form])), 'tm'=>$time];
        $raw = \net\Http::curlPostOwnWithHeader($site.'/index.php?c=Service&a=invoke', json_encode($body), ['Invoke-Auth-Token:'.md5($site.$time)], 20);
        $response = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($response) || !array_key_exists('error_no', $response) || (int)$response['error_no'] !== 0 || !is_array($response['retval'] ?? null)) {
            throw new \RuntimeException('预约服务连接异常');
        }
        $result = $response['retval'];
        if (!empty($result['validation_error'])) { throw new \InvalidArgumentException($result['validation_error']); }
        return $result;
    }

    public static function result(array $session)
    {
        $payload = json_decode($session['payload'], true);
        if ($payload['form']['booking_kind'] === 'activity') { return CommunityAgentActivityService::result($session); }
        if ($payload['form']['booking_kind'] === 'merchant') { return self::bridge($session, 'result'); }
        $row = Db::name('house_venue_appoint_record')->where(['id'=>$session['application_id'], 'uid'=>$session['uid'], 'pigcms_id'=>$session['bind_id'], 'village_id'=>$session['village_id']])->find();
        if (!$row) { throw new \RuntimeException('预约记录不存在'); }
        return ['application_id'=>(int)$row['id'], 'application_status'=>(int)$row['status'], 'message'=>[0=>'预约已提交，等待管理员审核',1=>'场馆预约成功',2=>'预约审核未通过',3=>'预约已取消'][$row['status']] ?? '预约已提交', 'booking_kind'=>'venue'];
    }
}
