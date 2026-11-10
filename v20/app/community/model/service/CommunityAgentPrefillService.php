<?php
namespace app\community\model\service;

/** Literal proposals from user text and already-authorized option lists; no model calls. */
class CommunityAgentPrefillService
{
    public static function uniqueMention(array $options, $text, $label = 'label', $leaf = false)
    {
        if (!is_string($text) || $text === '') { return null; }
        $matches=[];
        foreach ($options as $option) {
            $name=trim((string)($option[$label] ?? ''));
            if ($leaf) { $parts=explode(' / ', $name); $name=trim(end($parts)); }
            if (mb_strlen($name, 'UTF-8') < 2) { continue; }
            $pattern=preg_quote($name, '/');
            if (preg_match('/^[0-9]/', $name)) { $pattern='(?<![0-9])'.$pattern; }
            if (preg_match('/[0-9]$/', $name)) { $pattern.='(?![0-9])'; }
            if (preg_match('/'.$pattern.'/u', $text)) { $matches[]=$option; }
        }
        return count($matches) === 1 ? $matches[0] : null;
    }

    public static function repair(array $form, array $meta, $text)
    {
        $category=self::uniqueMention($meta['repair_categories'], $text, 'label', true);
        if ($category) { $form['cat_id']=(int)$category['id']; }
        $address=self::uniqueMention($meta['repair_addresses'], $text);
        preg_match_all('/[0-9一二三四五六七八九十]+\s*(?:号楼|栋|单元|室)/u',$text,$qualifiers);
        foreach ($qualifiers[0] as $qualifier) {
            if ($address && strpos($address['label'],$qualifier)===false) { $address=null; }
        }
        if ($address) {
            $form['address_type']=$address['type']; $form['address_id']=(int)$address['id'];
        } elseif (preg_match('/公共|楼道|电梯|大厅|大堂|车库|楼梯|楼顶|天台|绿化|道路|单元门|[0-9一二三四五六七八九十]+\s*(?:号楼|栋|单元|室)/u', $text)) {
            // Never silently reinterpret a stated common area or another room as this home.
            $form['address_id']=0;
        }
        return $form;
    }

    public static function contacts($text)
    {
        $result=[];
        if (preg_match_all('/(?:联系人|收件人)\s*[：:]?\s*([\p{Han}·A-Za-z][\p{Han}·A-Za-z ]{0,49})(?=[，,。；;\n]|$)/u', $text, $names)===1) {
            $result['contact_name']=trim($names[1][0]);
        }
        if (preg_match_all('/(?:联系电话|手机号|电话|手机)\s*[：:]?\s*(\+?[0-9][0-9() -]{4,19})(?=[，,。；;\n]|$)/u', $text, $phones)===1) {
            $result['contact_phone']=trim($phones[1][0]);
        }
        return $result;
    }

    public static function pickup($text)
    {
        $result=[];
        foreach (['pickup_code'=>'取件码|提货码','track_num'=>'运单号|快递单号'] as $field=>$labels) {
            if (preg_match_all('/(?:'.$labels.')\s*[：:]?\s*([A-Za-z0-9-]{1,64})(?![A-Za-z0-9-])/u',$text,$matches)===1) {
                $result[$field]=$matches[1][0];
            }
        }
        foreach (self::contacts($text) as $field=>$value) { $result[$field==='contact_name' ? 'recipient_name' : 'recipient_phone']=$value; }
        return $result;
    }

    public static function activityKeyword($text)
    {
        if (preg_match('/(?:报名|参加|预约)(?:一下|一个|个)?\s*([^，,。；;\n]{1,60}?)活动/u',$text,$match)) {
            return trim($match[1]);
        }
        if (preg_match('/^\s*([\p{Han}A-Za-z0-9 ]{1,20})活动\s*$/u',$text,$match)) {
            $keyword=trim($match[1]);
            if (!preg_match('/报名|参加|预约|帮我|我想/u',$keyword) && !in_array($keyword,['社群','社区'],true)) { return $keyword; }
        }
        return '';
    }
}
