<?php

namespace app\social_activity\model\db;

use app\common\model\db\Config;
use think\Model;

class SocialActivityCommentSetting extends Model
{
    public function getSettings()
    {
        $config = \think\facade\Db::name('config');
        
        $settings = [
            'comment_alias' => '',
            'share_audit' => 1,
        ];
        
        $configList = $config->where('name', 'in', ['social_comment_alias_name', 'social_comment_check', 'social_comment_filer_word'])->select();
        
        foreach ($configList as $item) {
            switch ($item['name']) {
                case 'social_comment_alias_name':
                    $settings['comment_alias'] = $item['value'];
                    break;
                case 'social_comment_check':
                    $settings['share_audit'] = intval($item['value']);
                    break;
            }
        }
        
        return $settings;
    }
    
    public function saveSettings($data)
    {
        $configData = [
            ['name' => 'social_comment_alias_name', 'value' => $data['comment_alias']],
            ['name' => 'social_comment_check', 'value' => $data['share_audit']],
        ];
        foreach ($configData as $item) {
            $exist = Config::where('name', $item['name'])->find();
            if ($exist) {
                Config::where('name', $item['name'])->save(['value' => $item['value']]);
            } else {
                Config::create($item);
            }
        }
        
        return true;
    }
}
