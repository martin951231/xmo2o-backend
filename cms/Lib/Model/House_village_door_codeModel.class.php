<?php
class House_village_door_codeModel extends Model{
    public function get_token($village_id){
        fdump($village_id,'village');
        // if (!$village_id) {
        //     return array('error'=>1,'msg'=>'小区id不存在');
        // }
        // $face_door = $this->where(array('village_id'=>$village_id))->find();
        $face_door = $this->find();
        if (!$face_door) {
            return array('error'=>1,'msg'=>'门禁未授权');
        }
        if ($face_door['expires_in']+$face_door['token_time'] < time()) {
            // 重新获取
            $params = array();
            $params['client_id'] = 'auth_yunnan_id';
            $params['client_secret'] = 'auth_yunnan_secret';
            $params['grant_type'] = 'refresh_token';
            $params['refresh_token'] = $face_door['refresh_token'];
            $params['redirect_uri'] = C('config.site_url').'/shequ.php?g=House&c=Index&a=getCode';
            $url = 'https://openapi.vooct.com/openapi/oauth/token';

            $result = $this->curl_post($url,$params);
            if ($result['error']) {
                return array('error'=>1,'msg'=>'刷新token获取失败');
            }else{
                $data['pigcms_id'] = $face_door['pigcms_id'];
                $data['access_token'] = $result['access_token'];
                $data['refresh_token'] = $result['refresh_token'];
                $data['expires_in'] = $result['expires_in'];
                $data['token_time'] = time();
                $door_code = M('House_village_door_code')->where(array('village_id'=>$village_id))->find();
                
                M('House_village_door_code')->save($data);
                return array('error'=>0,'access_token'=>$result['access_token']);
            }
        }else{
            return array('error'=>0,'access_token'=>$face_door['access_token']);
        }
    }

    // 人脸门禁同步社区
    public function sync_community($village_id){
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        $village_info['long'] = floatval($village_info['long']);
        $village_info['lat'] = floatval($village_info['lat']);
        $property_phone = explode(' ', $village_info['property_phone']);
        $village_info['property_phone'] = $property_phone[0];

        $token_info = $this->get_token($village_id);
        // /openapi/v1/doorkeeper/findCommunities
        if (!$token_info['error']) {
            $params = array();
            $params['gridCode'] = '110101';
            $params['communityName'] = $village_info['village_name'];
            $params['communityCoordinate'] = $village_info['long'].','.$village_info['lat'];
            $params['communityContact'] = $village_info['property_name'];
            $params['communityContactPhone'] = $village_info['property_phone'];
            if ($village_info['communityId']) { 
                // $params['communityId'] = $village_info['communityId'];
                // $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/modifyCommunity';
                return array('code'=>1,'msg'=>'已同步');
            }else{
                $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/addCommunity';
                $result = $this->curl_post($url,$params,$token_info['access_token']);
                if ($result['code']==1) {
                    
                    $update = D('House_village')->where(array('village_id'=>$village_id))->save(array('communityId'=>$result['data'][0]['communityId']));
                    if ($update) {
                        return array('code'=>1,'msg'=>'同步成功');
                    }else{
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                }else{
                    return $result;
                }
            }

            
        }else{
            return array('code'=>0,'msg'=>'同步失败,请重试');
        }
    }

    // 人脸门禁同步社区
    public function sync_floor($village_id){
        set_time_limit(0);
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        // 小区信息
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        if (!$village_info['communityId']) {
            return array('code'=>0,'msg'=>'小区未同步，请先同步小区');
        }

        $token_info = $this->get_token($village_id);
        if (!$token_info['error']) {
            // 楼栋（单元）信息
            $floor_list = D('House_village_floor')->field('floor_id,floor_name,floor_layer,buildId')->where(array('village_id'=>$village_id,'buildId'=>0))->select();

            foreach ($floor_list as $key => $value) {
                $params = array();
                $params['buildName'] = $value['floor_name'].$value['floor_layer'];
                $params['communityId'] = $village_info['communityId'];
                $params['buildCode'] = $value['floor_id'];
                $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/addBuilding';
                
                
                $result = $this->curl_post($url,$params,$token_info['access_token']);
                if ($result['code']==1) {
                    
                    $update = D('House_village_floor')->where(array('floor_id'=>$value['floor_id']))->save(array('buildId'=>$result['data'][0]['buildId']));
                    if ($update===false) {
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                }else{
                    return array('code'=>0,'msg'=>'同步失败,请重试');
                }
            }
            return array('code'=>1,'msg'=>'同步成功');
        }
    }

    // 人脸门禁同步房间
    public function sync_house($village_id){
        set_time_limit(0);
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        // 小区信息
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        if (!$village_info['communityId']) {
            return array('code'=>0,'msg'=>'小区未同步，请先同步小区');
        }

        $token_info = $this->get_token($village_id);
        if (!$token_info['error']) {

            // 楼栋（单元）信息
            $limit = 0;
            $house_list = D('House_village_user_vacancy')->join(' as h LEFT JOIN '.C('DB_PREFIX').'house_village_floor as f on f.floor_id=h.floor_id')->field('f.buildId,f.floor_name,f.floor_layer,h.layer,h.room,h.pigcms_id')->where(array('h.village_id'=>$village_id,'f.buildId'=>array('gt',0),'h.houseId'=>0))->limit($limit,1000)->order('h.pigcms_id desc')->select();
            while ($house_list) {
                $limit += 1000;
                foreach ($house_list as $key => $value) {
                    $params = array();
                    $params['houseName'] = $value['floor_name'].$value['floor_layer'].$value['layer'].$value['room'];
                    $params['houseCode'] = $value['pigcms_id'];
                    $params['communityId'] = $village_info['communityId'];
                    $params['buildId'] = $value['buildId'];                   
                    $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/addApartment';

                    $result = $this->curl_post($url,$params,$token_info['access_token']);
                    if ($result['code']==1) {
                        $update = D('House_village_user_vacancy')->where(array('pigcms_id'=>$value['pigcms_id']))->save(array('houseId'=>$result['data'][0]['houseId']));

                        if ($update===false) {
                            return array('code'=>0,'msg'=>'同步失败,请重试');
                        }
                    }else{
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                }
                $house_list = D('House_village_user_vacancy')->join(' as h LEFT JOIN '.C('DB_PREFIX').'house_village_floor as f on f.floor_id=h.floor_id')->field('f.buildId,f.floor_name,f.floor_layer,h.layer,h.room,h.pigcms_id')->where(array('h.village_id'=>$village_id,'f.buildId'=>array('gt',0)))->limit($limit,1000)->order('h.pigcms_id desc')->select();
            }
            
            return array('code'=>1,'msg'=>'同步成功');
        }
    }
    // 人脸门禁同步业主
    public function sync_user($village_id){
        set_time_limit(0);
        if(!$village_id){
            return array('code'=>0,'msg'=>'小区id不存在');
        }
        // 小区信息
        $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
        if (!$village_info['communityId']) {
            return array('code'=>0,'msg'=>'小区未同步，请先同步小区');
        }

        $token_info = $this->get_token($village_id);
        if (!$token_info['error']) {

            // 楼栋（单元）信息
            $limit = 0;
            $user_list = D('House_village_user_bind')->join(' as u LEFT JOIN '.C('DB_PREFIX').'house_village_user_vacancy as h on u.vacancy_id=h.pigcms_id')->field('u.pigcms_id,u.name,u.phone,u.property_endtime,u.type,u.parent_id,h.houseId')->where(array('h.village_id'=>$village_id,'h.houseId'=>array('gt',0),'u.keyId'=>0))->limit($limit,1000)->order('u.pigcms_id desc')->select();
            while ($user_list) {
                $limit += 1000;
                foreach ($user_list as $key => $value) {
                    // 添加钥匙
                    $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/addDKKey';
                    $params = array();
                    $params['houseId'] = $value['houseId'];
                    $params['userName'] = $value['name'];
                    $params['userPhone'] = $value['phone'];
                    $params['userIdCardNo'] = $value['phone'];
                    $params['roleType'] = $value['type']+1;
                    if ($value['type']!=0) {
                        // 查询业主
                        $parent_user = D('House_village_user_bind')->where(array('pigcms_id'=>$value['parent_id']))->find();
                        $value['userEndTime'] = $parent_user['userEndTime'];
                    }

                    if ($value['userEndTime']) {
                        $params['userEndTime'] = date('Y-m-d H:i:s',$value['userEndTime']);
                    }else{
                        $params['userEndTime'] = date('Y-m-d H:i:s',strtotime("+1 year"));
                    }

                    $result = $this->curl_post($url,$params,$token_info['access_token']);
                    if ($result['code']==1) {
                        $update = D('House_village_user_bind')->where(array('pigcms_id'=>$value['pigcms_id']))->save(array('keyId'=>$result['data'][0]['keyId']));

                        if ($update===false) {
                            return array('code'=>0,'msg'=>'同步失败,请重试');
                        }
                    }else{
                        return array('code'=>0,'msg'=>'同步失败,请重试');
                    }
                }
                $user_list = D('House_village_user_bind')->join(' as u LEFT JOIN '.C('DB_PREFIX').'house_village_user_vacancy as h on u.vacancy_id=h.pigcms_id')->field('u.pigcms_id,u.name,u.phone,u.property_endtime,u.type,u.parent_id,h.houseId')->where(array('h.village_id'=>$village_id,'h.houseId'=>array('gt',0),'u.keyId'=>0))->limit($limit,1000)->order('u.pigcms_id desc')->select();
            }
            return array('code'=>1,'msg'=>'同步成功');
        }
    }

    // 人脸门禁修改业主
    public function modify_user($pigcms_id,$field='pigcms_id'){
        if(!$pigcms_id){
            return array('code'=>0,'msg'=>'参数不对');
        }

        // 查询业主
        $bind_user = D('House_village_user_bind')->where(array($field=>$pigcms_id))->find();
        if(!$bind_user){
            return array('code'=>0,'msg'=>'业主不存在');
        }

        if (!$bind_user['keyId']) {
            return array('code'=>0,'msg'=>'业主未同步，不能修改');
        }

        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 添修改钥匙
            $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/modifyDKKey';
            $params = array();
            $params['userName'] = $bind_user['name'];
            $params['userPhone'] = $bind_user['phone'];
            $params['userIdCardNo'] = $bind_user['phone'];
            $params['id'] = $bind_user['keyId'];
            $params['roleType'] = $bind_user['type']+1;

            // 有效期结束时间
            if ($bind_user['type']!=0) {
                // 查询业主
                $parent_user = D('House_village_user_bind')->where(array('pigcms_id'=>$bind_user['parent_id']))->find();
                $bind_user['userEndTime'] = $parent_user['userEndTime'];
            }
            if ($bind_user['userEndTime']) {
                $params['userEndTime'] = date('Y-m-d H:i:s',$bind_user['userEndTime']);
            }else{
                $params['userEndTime'] = date('Y-m-d H:i:s',strtotime("+1 year"));
            }

            // 房间信息
            $house_info = D('House_village_user_vacancy')->where(array('pigcms_id'=>$bind_user['vacancy_id']))->find();
            $params['houseId'] = $house_info['houseId'];

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            if ($result['code']==1) {
                return array('code'=>1,'msg'=>'同步成功');
                
            }else{
                return array('code'=>0,'msg'=>'同步失败,请重试');
            }
        }
    }

    // 人脸门禁删除业主
    public function delete_user($pigcms_id){
        if(!$pigcms_id){
            return array('code'=>0,'msg'=>'参数不对');
        }

        // 查询业主
        $bind_user = D('House_village_user_bind')->where(array($field=>$pigcms_id))->find();
        if(!$bind_user){
            return array('code'=>0,'msg'=>'业主不存在');
        }

        if (!$bind_user['keyId']) {
            return array('code'=>0,'msg'=>'业主未同步，不能删除');
        }

        $token_info = $this->get_token();
        if (!$token_info['error']) {
            // 删除钥匙
            $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/removeDKKey';
            $params = array();
            $params['id'] = $bind_user['keyId'];

            $result = $this->curl_post($url,$params,$token_info['access_token']);
            if ($result['code']==1) {
                return array('code'=>1,'msg'=>'删除成功');
            }else{
                return array('code'=>0,'msg'=>'删除失败,请重试');
            }
        }
    }

    public  function get_door_list($where,$pageSize=20){
        if(!$where['village_id']){
            return array('code'=>1,'msg'=>'小区id不存在i');
        }
        $count_arr  =   $this->where($where)->count();
        if(!$count_arr){
            return  2;
        }

        $condition_where = "d.village_id=" . $where['village_id'];
        $condition_field = ' d.*,f.floor_name,f.floor_layer,f.floor_id';
       
        $count = M('House_village_door_face')->join(' as d left join '.C('DB_PREFIX') . 'house_village_floor as f on d.floor_id = f.floor_id ')->where($condition_where)->count();

        $p = new Page($count, $pageSize, 'page');
        $list = M('House_village_door_face')->join(' as d left join '.C('DB_PREFIX') . 'house_village_floor as f on d.floor_id = f.floor_id ')->field($condition_field)->where($condition_where)->order('`d`.`door_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['pagebar'] = $p->show();
        $return['list'] = $list;
        
        return  $return;
    }

    public function curl_post($url,$params,$access_token=''){
        fdump($url,'door');
        fdump($params,'door',true);
        $ch = curl_init(); 
        $header =  array(
            "Accept: application/json", 
        );

        if ($access_token) {
            $header[] =   "Authorization:bearer ".$access_token;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);
        $request_header = curl_getinfo( $ch, CURLINFO_HEADER_OUT);
        curl_close($ch);
        fdump($request_header,'door',true);
        fdump($result,'door',true);
        $result = json_decode($result,true);
        return $result;
    } 
}