<?php
/*
 * 微信小程序订消息授权信息
 * @author hengtingmei
 * @date 20201105
 */
    class Wxapp_template_bind_user_listModel extends Model{

        /**
         * 获得授权信息
         * @param $param array
         * @return array
         */
        public function getTemplateRole($param){
            // 绑定的模板id
            $templateKey = $param['template_key'] ?? '';
            // 微信小程序openID
            $wxappOpenid = $param['wxapp_openid'] ?? '';

            $where = [
                'template_key' => $templateKey,
                'wxapp_openid' => $wxappOpenid,
                'status' => 0
            ];
            $bind = $this->where($where)->find();
            return $bind;
        }

        /**
         * 保存小程序订阅消息模板授权信息
         * @param $param array
         * @return array
         */
        public function saveTemplateRole($param){
            // 绑定的模板id
            $templateList = $param['template_list'] ?? [];
            // 微信小程序openID
            $wxappOpenid = $param['wxapp_openid'] ?? '';

            if(empty($templateList) || empty($wxappOpenid)){
                return false;
            }

            foreach ($templateList as $templateKey){
                $data[]  = [
                    'template_key' => $templateKey,
                    'wxapp_openid' => $wxappOpenid,
                ];
            }

            $res = $this->addAll($data);
            return $res;
        }
    }
?>
