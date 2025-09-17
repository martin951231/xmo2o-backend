<?php

/**
 * 群聊
 */
class Im_group_chatModel extends Model
{
    public function buildGroupChat($owner, $name, $avatar,array $members = [], $businessType = '', $businessId = 0)
    {
        sort($members);
        $index = md5(var_export($members, true) . $businessType . $businessId);
        $record = $this->where(['group_index' => $index])->find();
        if ($record) {
            $this->where(['group_index' => $index])->save(['status' => 1, 'name' => $name, 'avatar' => $avatar]);
            $gid = $record['gid'];
        } else {
            require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
            $configData = PD('Config_data')->get_jg_im_config();
            $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
            $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
            $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
            $groupObj = new \JMessage\IM\Group($jm);
            $result = $groupObj->create($owner, $name, '', $members);
            if (!empty($result['body']['error']['message'])) {
                throw new \Exception($result['body']['error']['message']);
            } else {
                //创建群聊
                $gid = $result['body']['gid'];
                $chat = [
                    'gid' => $gid,
                    'group_index' => $index,
                    'name' => $name,
                    'avatar' => $avatar,
                    'create_time' => time(),
                    'status' => 1,
                    'business_type' => $businessType,
                    'business_order_id' => $businessId
                ];
                $groupId = $this->add($chat);

                //保存群聊成员
                $chatMembers = [];
                foreach ($members as $m) {
                    $chatMembers[] = [
                        'username' => $m,
                        'uuid' => Im_userModel::getUUIDByUsername($m),
                        'group_id' => $groupId
                    ];
                }
                $chatMembers && M('Im_group_chat_members')->addAll($chatMembers);
            }
        }
        return ['gid' => $gid];
    }
}