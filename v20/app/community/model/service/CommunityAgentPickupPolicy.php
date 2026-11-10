<?php
namespace app\community\model\service;

/** Pure validation, shared by preparation and final submission. */
class CommunityAgentPickupPolicy
{
    public static function options()
    {
        return [
            ['value' => 'express_pickup', 'label' => '代取快递', 'enabled' => true],
            ['value' => 'repair', 'label' => '提交工单', 'enabled' => true],
            ['value' => 'bill_payment', 'label' => '缴费', 'enabled' => true],
            ['value' => 'service_booking', 'label' => '预约服务', 'enabled' => true],
            ['value' => 'activity_signup', 'label' => '社群活动', 'enabled' => true],
        ];
    }

    public static function fields()
    {
        return [
            ['name'=>'pickup_code', 'label'=>'取件码', 'type'=>'text', 'required'=>true, 'max_length'=>64],
            ['name'=>'recipient_name', 'label'=>'收件人', 'type'=>'text', 'required'=>true, 'max_length'=>50],
            ['name'=>'recipient_phone', 'label'=>'收件电话', 'type'=>'tel', 'required'=>true, 'max_length'=>20],
            ['name'=>'location_id', 'label'=>'取件地址', 'type'=>'select', 'required'=>true],
            ['name'=>'track_num', 'label'=>'运单号', 'type'=>'text', 'required'=>false, 'max_length'=>64],
            ['name'=>'remark', 'label'=>'备注', 'type'=>'textarea', 'required'=>false, 'max_length'=>500],
        ];
    }

    public static function normalize(array $input)
    {
        $output = [];
        foreach (self::fields() as $field) {
            $name = $field['name'];
            $value = isset($input[$name]) ? $input[$name] : '';
            if (!is_string($value) && !is_int($value)) {
                throw new \InvalidArgumentException($field['label'].'格式错误');
            }
            $value = trim((string)$value);
            if ($name === 'location_id') {
                if ($value !== '' && (!ctype_digit($value) || strlen($value) > 10)) {
                    throw new \InvalidArgumentException('取件地址格式错误');
                }
                $output[$name] = (int)$value;
            } else {
                if (mb_strlen($value, 'UTF-8') > $field['max_length'] || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) {
                    throw new \InvalidArgumentException($field['label'].'内容过长或格式错误');
                }
                $output[$name] = $value;
            }
        }
        if ($output['recipient_phone'] !== '' && !preg_match('/^[0-9+() -]{5,20}$/D', $output['recipient_phone'])) {
            throw new \InvalidArgumentException('请填写有效收件电话');
        }
        return $output;
    }

    public static function missing(array $form)
    {
        $missing = [];
        foreach (self::fields() as $field) {
            if ($field['required'] && empty($form[$field['name']])) {
                // A numeric-looking pickup code such as "0" is still a value.
                if ($field['name'] !== 'location_id' && isset($form[$field['name']]) && $form[$field['name']] !== '') {
                    continue;
                }
                $missing[] = $field['name'];
            }
        }
        return $missing;
    }

    public static function requireVersion(array $session, $version)
    {
        if ((int)$version !== (int)$session['version']) {
            throw new \InvalidArgumentException('信息已更新，请刷新后重新确认');
        }
    }

    public static function requireEditable(array $session)
    {
        if ($session['state'] === 'submitting') { throw new \InvalidArgumentException('预约正在确认中，请查询提交结果'); }
        if (in_array($session['state'], ['submitted', 'cancelled'], true)) {
            throw new \InvalidArgumentException('当前办理已结束');
        }
        if ((int)$session['expires_at'] <= time()) {
            throw new \InvalidArgumentException('本次办理已过期，请重新发起');
        }
    }

    /** These existing config fields describe a blocked window, not opening hours. */
    public static function blockedTime($start, $end, $now)
    {
        $start = trim((string)$start);
        $end = trim((string)$end);
        if ($start === '' && $end === '') { return false; }
        foreach ([$start, $end] as $value) {
            if ($value !== '' && !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $value)) {
                throw new \InvalidArgumentException('代取服务时段配置异常，请联系物业');
            }
        }
        if ($start === '') { return $now <= $end; }
        if ($end === '') { return $now >= $start; }
        // Preserve the existing same-day comparison; do not reinterpret configuration.
        return $now >= $start && $now <= $end;
    }

    public static function assertQuota($houseLimit, $projectLimit, $houseCount, $projectCount)
    {
        if ($houseLimit > 0 && $houseCount >= $houseLimit) {
            throw new \InvalidArgumentException('每户每天最多只能申请'.$houseLimit.'次');
        }
        if ($projectLimit > 0 && $projectCount >= $projectLimit) {
            throw new \InvalidArgumentException('小区今日代取名额已满');
        }
    }
}
