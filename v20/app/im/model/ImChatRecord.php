<?php

declare(strict_types=1);

namespace app\im\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class ImChatRecord extends Model
{
    const TEXT = 'text';

    const IMAGE = 'image';

    const VIDEO = 'video';

    public  function getSimpleText($msgType, $msgBody)
    {
        if ($msgType == self::TEXT) {
            return $msgBody['text'] ?? '';
        } else if ($msgType == self::IMAGE) {
            return '[图片]';
        } else if ($msgType == self::VIDEO) {
            return '[视频]';
        } else {
            return '[特殊消息]';
        }
    }

    //
    public function buildChatIndexKey($from, $to)
    {
        $params = [];
        $split = explode('_', $from);
        $belong1 = $split[0] . '_' . $split[1];

        $split = explode('_', $to);
        $belong2 = $split[0] . '_' . $split[1];

        $params = [$belong1, $belong2];
        arsort($params);
        return md5(implode('_', $params));
    }

    public function getRecord($chatKey, $username, $page, $pageSize, $keyword = '')
    {

        $where = sprintf("target_type<>'group' AND `msg_type` <> 'location' AND `index_key` = '%s' AND (`show_rule` = 0 OR (`from_id` = '%s' AND `show_rule` = 1) OR (`target_id` = '%s' AND `show_rule` = 2))", $chatKey, $username, $username);
        if ($keyword) {
            $where .= sprintf(" AND `msg_body` LIKE '%s'", '%' . $keyword . '%');
        }

        $imUserMod = new ImUser();
        $fromUUID = $imUserMod->getUUIDByUsername($username);

        $records = $this->whereRaw($where)
            ->field('id,msg_id,from_id,target_id,msg_type,msg_body,create_time,show_read,is_read')
            ->order('create_time DESC')
            ->page($page, $pageSize)
            ->select()
            ->each(function ($item) use ($imUserMod, $fromUUID) {
                $msgBody = json_decode($item->msg_body, true);
                if ($item->msg_type == 'video') {
                    $msgBody['thunm'] = replace_file_domain($msgBody['thunm'] ?? '');
                    $msgBody['url'] = replace_file_domain($msgBody['url']);
                }

                if ($fromUUID == $imUserMod->getUUIDByUsername($item['from_id'])) {
                    $item->is_oneself = 1;
                } else {
                    $item->is_oneself = 0;
                }
                $item->msg_body = $msgBody;
            })
            ->toArray();
        return $records;
    }
}
