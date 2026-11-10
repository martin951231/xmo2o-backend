<?php
namespace app\community\model\service;

class VisitorRegisterValidation
{
    public static function hasMenu($admin, $role, $dismissRoles, $menuId)
    {
        if (!$menuId || empty($admin['village_id'])) { return false; }
        // Ordinary accounts must have an explicit grant, even when legacy helpers
        // interpret an empty menu list as unrestricted access.
        if (empty($admin['normal_admin_id']) && in_array($role, array_merge([6], $dismissRoles))) { return true; }
        $menus = $admin['menus'] ?? [];
        if (!is_array($menus)) { $menus = explode(',', $menus); }
        return in_array((string)$menuId, array_map('strval', $menus), true);
    }

    public static function type($type)
    {
        if (!is_string($type) || !in_array($type, ['visitor', 'vehicle'], true)) {
            throw new \InvalidArgumentException('登记类型不正确');
        }
        return $type;
    }

    public static function date($value, $label)
    {
        if (!is_string($value)) { throw new \InvalidArgumentException($label . '格式不正确'); }
        $date = \DateTime::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('Asia/Shanghai'));
        if (!$date || $date->format('Y-m-d H:i:s') !== $value || substr($value, 0, 4) < '2000' || substr($value, 0, 4) > '2100') {
            throw new \InvalidArgumentException($label . '格式不正确');
        }
        return $value;
    }

    public static function data($type, $data)
    {
        self::type($type);
        if (!is_array($data)) { throw new \InvalidArgumentException('登记数据格式不正确'); }
        $fields = $type === 'visitor'
            ? ['visitor_name' => [60, '访客姓名'], 'organization_or_id' => [120, '单位或有效证件号码'], 'phone' => [11, '联系电话'], 'visit_reason' => [255, '事由'], 'host_name_room' => [120, '被访者姓名、房号'], 'remark' => [500, '备注']]
            : ['plate_number' => [16, '车牌号码'], 'destination' => [120, '车辆去向'], 'phone' => [11, '联系电话']];
        $result = [];
        foreach ($fields as $field => $rule) {
            $value = $data[$field] ?? '';
            if (!is_string($value)) { throw new \InvalidArgumentException($rule[1] . '格式不正确'); }
            $value = trim($value);
            if (($field !== 'remark' && $value === '') || mb_strlen($value, 'UTF-8') > $rule[0] || preg_match('/[<>\x00-\x08\x0B\x0C\x0E-\x1F]/u', $value)) {
                throw new \InvalidArgumentException($rule[1] . '为空、过长或包含非法字符');
            }
            $result[$field] = $value;
        }
        if (!preg_match('/^1[3-9][0-9]{9}$/D', $result['phone'])) { throw new \InvalidArgumentException('请输入正确的手机号'); }
        if ($type === 'visitor') {
            $result['visit_time'] = self::date($data['visit_time'] ?? '', '来访时间');
        } else {
            $result['plate_number'] = mb_strtoupper($result['plate_number'], 'UTF-8');
            if (!preg_match('/^[\x{4e00}-\x{9fff}][A-Z][A-HJ-NP-Z0-9]{5,6}$/u', $result['plate_number'])) {
                throw new \InvalidArgumentException('请输入正确的普通或新能源车牌');
            }
            $result['entry_time'] = self::date($data['entry_time'] ?? '', '进入时间');
            $result['exit_time'] = empty($data['exit_time']) ? null : self::date($data['exit_time'], '离开时间');
            if ($result['exit_time'] && $result['exit_time'] < $result['entry_time']) { throw new \InvalidArgumentException('离开时间不能早于进入时间'); }
            $result['status'] = $result['exit_time'] ? 'exited' : 'entered';
        }
        return $result;
    }
}
