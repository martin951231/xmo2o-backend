<?php

class Im_chat_recordModel extends Model
{
    const TEXT = 'text';

    const IMAGE = 'image';

    const VIDEO = 'video';

    public static function buildChatIndexKey($from, $to)
    {
        $agrs = func_get_args();
        $params = [];
        if (count($agrs) > 2) {
            foreach ($agrs as $a) {
                $split = explode('_', $a);
                $params[] = $split[0] . '_' . $split[1];
            }
        } else {
            $split = explode('_', $from);
            $belong1 = $split[0] . '_' . $split[1];
            $split = explode('_', $to);
            $belong2 = $split[0] . '_' . $split[1];
            $params = array($belong1, $belong2);
        }
        arsort($params);
        return md5(implode('_', $params));
    }

    public static function getSimpleText($msgType, $msgBody)
    {
        if ($msgType == self::TEXT) {
            return isset($msgBody['text']) ? $msgBody['text'] : '';
        } else if ($msgType == self::IMAGE) {
            return '[图片]';
        } else if($msgType == self::VIDEO){
            return '[视频]';
        }else{
            return '[特殊消息]';
        }
    }

    public static function getShowNumber($prefix)
    {
        $setting = [
            Im_userModel::PREFIX_USER => 1,
            Im_userModel::PREFIX_STORE => 2,
            Im_userModel::PREFIX_PLAT => 4,
            Im_userModel::PREFIX_DELIVER => 8,
        ];
        return $setting[$prefix] ?? 0;
    }
}