<?php
/**
 * 企业微信会话存档消息接收
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/30 14:57
 */
class qywxMsgAction extends BaseAction {

    public function SendQywxMessage() {
        fdump_api('会话存档消息接收'.__LINE__,'qyweixin/SendQywxMessageLog',1);
        $requestUrl = C('config.site_url').'/v20/public/index.php/community/manage_api.v1.SessionFile/index';
        import('ORG.Net.Http');
        $http = new Http();
        $post_data = json_encode($_POST,JSON_UNESCAPED_UNICODE);
        fdump_api($post_data,'qyweixin/SendQywxMessageLog',1);
        $data = Http::curlQyWxPost($requestUrl,$post_data);
        $msg = [
            'error' => 0,
            'data' => $data
        ];
        fdump_api($data,'qyweixin/SendQywxMessageLog',1);
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        fdump_api($return,'qyweixin/SendQywxMessageLog',1);
//        header("Content-type: application/json");
        echo $return;exit();
    }


    public function SendQywxMessageError() {
        fdump_api('会话存档消息接收'.__LINE__,'qyweixin/SendQywxMessageErrorLog',1);
        fdump_api($_GET,'qyweixin/SendQywxMessageErrorLog',1);
        fdump_api($_POST,'qyweixin/SendQywxMessageErrorLog',1);
        $msg = [
            'error' => 0,
            'data' => []
        ];
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        fdump_api($return,'qyweixin/SendQywxMessageErrorLog',1);
//        header("Content-type: application/json");
        echo $return;exit();
    }

    public function SendQywxMessageGetList() {
        fdump_api('会话存档消息接收'.__LINE__,'qyweixin/SendQywxMessageGetListLog',1);
        fdump_api($_GET,'qyweixin/SendQywxMessageGetListLog',1);
        fdump_api($_POST,'qyweixin/SendQywxMessageGetListLog',1);

        $id = $_GET['id'];
        if (!$id) {
            $msg = [
                'error' => 0,
                'data' => []
            ];
            $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
            echo $return;exit();
        } else {
            $enterprise_wx_corpid = D('House_enterprise_wx_bind')->where(array('bind_id' => $id, 'bind_type' => 0))->find();
            if (empty($enterprise_wx_corpid)) {
                $msg = [
                    'error' => 0,
                    'data' => []
                ];
                $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
                echo $return;exit();
            }
            $corpid = $enterprise_wx_corpid['corpid'];
            $corp_name = $enterprise_wx_corpid['corp_name'];
            $corp_type = $enterprise_wx_corpid['corp_type'];
            $corp_square_logo_url = $enterprise_wx_corpid['corp_square_logo_url']?$enterprise_wx_corpid['corp_square_logo_url']:'';
            $subject_type = $enterprise_wx_corpid['subject_type'];

            $db_work_msg_audit = M('Work_msg_audit');
            $where_audit_key = [
                'a.corp_id' => $corpid,
            ];
            $field = 'a.id,a.create_time,a.credit_code,a.contact_user,a.contact_phone,a.secret,a.seq,a.status,b.key_version,b.private_key,b.private_key_path,b.public_key,b.public_key_path,b.id as key_id';
            $info = $db_work_msg_audit
                ->join('as a LEFT JOIN '.C('DB_PREFIX').'work_msg_audit_key as b ON a.id=b.audit_id')
                ->where($where_audit_key)
                ->group('a.corp_id')
                ->field($field)
                ->find();
            if (empty($info) || !$info['secret']) {
                $msg = [
                    'error' => 0,
                    'data' => []
                ];
                $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
                echo $return;exit();
            }
            $private_key_path = replace_file_domain($info['private_key_path']);
            $public_key_path = replace_file_domain($info['public_key_path']);
            $private_key = $info['private_key'];
            $public_key = $info['public_key'];
            if (!$private_key) {
                $private_key = file_get_contents($private_key_path);
            }
            if (!$public_key) {
                $public_key = file_get_contents($public_key_path);
            }
            $key_data = [
                'key_id'           => $info['key_id'],
                'key_version'      => $info['key_version'],
                'private_key'      => trim($private_key),
                'private_key_path' =>$private_key_path,
                'public_key'       => trim($public_key),
                'public_key_path'  => $public_key_path,
            ];
            $key_list = [];
            $key_list[] = $key_data;
            $corp = [
                'corpid' => $corpid,
                'corp_name' => $corp_name,
                'state' => $corpid,
                'corp_type' => $corp_type,
                'corp_square_logo_url' => $corp_square_logo_url,
                'subject_type' => $subject_type,
            ];
            $corp_list = $corp;
            $data = [
                'credit_code'   => $info['credit_code'] ? $info['credit_code'] : '342427111111111111',
                'contact_user'  => $info['contact_user'] ? $info['contact_user'] : '用户',
                'contact_phone' => $info['contact_phone'] ? $info['contact_phone'] : '12345678910',
                'secret'        => $info['secret'],
                'status'        => $info['status'],
                'seq'           => $info['seq'],
                'create_time'   => $info['create_time'],
                'key_list' => $key_list,
                'corp' => $corp_list,
            ];
            $msg = [
                'error' => 0,
                'data' => $data
            ];
            fdump_api($data,'qyweixin/SendQywxMessageGetListLog',1);
        }
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        fdump_api($return,'qyweixin/SendQywxMessageGetListLog',1);
        echo $return;exit();
    }
}