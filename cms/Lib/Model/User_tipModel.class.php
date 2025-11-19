<?php
class User_tipModel extends Model
{
    /* 添加消息 */
    /**
     * @param integer $uid 用户id
     * @param string $tip_title 消息标题
     * @param array $tip 具体相关信息
     * array(
     *  'type' => '消息类型 face_door 门禁 camera 摄像机',
     *  'pre_title' => '前置消息',
     *  'end_title' => '后置消息',
     *  'url' => '相关链接 点击对应按钮位置跳转链接',
     *  'url_title' => '相关按钮名称 不填写默认为 查看详情',
     * )
     * @param array $content 消息主体信息
     * array(
     *  0=> array(
     *     'title' => '消息小标题',
     *     'content' => '对应消息内容',
     * ),
     *  1=> array(
     *     'title' => '消息小标题',
     *     'content' => '对应消息内容',
     * ),
     * )
     * @return bool
     */
    public function add_msg($uid, $tip_title, $tip=array(),$content = array())
    {
        if (!$uid || !$tip_title || $uid<=0 || ''==$tip_title) {
            return false;
        }
        // 处理消息信息
        $data = array();
        $data['uid'] = $uid;
        $data['title'] = $tip_title;
        if ($tip['type']) {
            $data['type'] = $tip['type'];
        }
        if ($tip['pre_title']) {
            $data['pre_title'] = $tip['pre_title'];
        }
        if ($tip['end_title']) {
            $data['end_title'] = $tip['end_title'];
        }
        if ($tip['url']) {
            $data['url'] = $tip['url'];
        }
        if ($tip['url_title']) {
            $data['url_title'] = $tip['url_title'];
        }
        if ($tip['from_id']) {
            $data['from_id'] = $tip['from_id'];
        }
        if (!empty($content) && is_array($content)) {
            // 存在消息主体 且 为数组 序列化存储
            $data['content'] = serialize($content);
        } elseif (!empty($content) && unserialize($content)) {
            // 如果序列化过直接存储
            $data['content'] = $content;
        }
        $data['add_time']=time();
        $add_id = $this->data($data)->add();
        return $add_id;
    }
}
?>