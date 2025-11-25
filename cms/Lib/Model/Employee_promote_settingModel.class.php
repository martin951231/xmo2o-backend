<?php
/**
 * 从业者推广模型
 * User: pigcms-s
 * Date: 2015/11/10
 * Time: 15:07
 */
class Employee_promote_settingModel extends Model {
    public function createImage($result, $qrecode, $userInfo, $supplier_info)
    {
        import('@.ORG.Poster');
        $savename = $result['pigcms_id'] . '_' . $qrecode['type'] . '_' . $userInfo['uid'] . '_' . $supplier_info['store_id'] . '.png';
        $root  = './upload/images/promote_qrcode/';
		
        if (strpos($qrecode, 'wb') === false) {
        	$qrecode['ticket'] = strpos($qrecode,'https') !== false ? str_replace('https:','http:',$qrecode) : $qrecode;
        }

        $bannerConfig = $this->object_to_array(json_decode(htmlspecialchars_decode($result['banner_config'])));

        $posterModel = new Poster();
        $createRes = $posterModel->setVars(array(
            'username'=> mb_substr($userInfo['nickname'], 0, 8, 'utf-8'),//网名限制8个字
            'company'=> !empty($result['store_nickname']) ? $result['store_nickname'] : $supplier_info['name'],
            'start_time' => date('Y-m-d',$result['start_time']),
            'end_time' => date('Y-m-d',$result['end_time']),
            'desc' => $result['descr'],
            'name' =>mb_substr($userInfo['nickname'], 0, 8, 'utf-8'),
            'title'=>$result['title']
        ))->setConfig(array(
            'avatar' => !empty($userInfo['avatar']) ?$userInfo['avatar']: './static/images/tx.png',
            'qrcode' => str_replace('https:','http:',$qrecode),
            'qrcode_url' => '',
            'logo' => !empty($supplier_info['logo']) ? $supplier_info['logo']: getAttachmentUrl('images/tx.png', FALSE),
        ))->create($bannerConfig);

        $posterModel->save($root.$savename);

        return file_domain().'/upload/images/promote_qrcode/'.$savename;
    }

    //对象转数组
    public function object_to_array($stdclassobject)
    {
        $_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
        foreach ($_array as $key => $value)
        {
            $value = (is_array($value) || is_object($value)) ? $this->object_to_array($value) : $value;
            $array[$key] = $value;
        }
        return $array;
    }
}