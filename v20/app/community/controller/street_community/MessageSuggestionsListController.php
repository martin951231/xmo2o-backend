<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/27 15:12
 */

namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetService;

class MessageSuggestionsListController extends CommunityBaseController
{

    /**
     * 获取留言建议列表
     * @param 传参
     * array (
     *  'key_val'=> '查询关键字',
     *  'value'=> '对应查询关键字的内容',
     *  'page'=> '查询页数 必传',
     *  'status'=> '对应查询状态',
     *  'ticket' => '', 登录标识 必传
     * )
     * @author: wanziyang
     * @date_time: 2020/5/27 17:48
     * @return \json
     */
    public function getList() {
        $info = $this->adminUser;
        $service_area_street = new AreaStreetService();

        $where = [];
        if (0==$info['area_type']) {
            $where[] = ['street_id','=',$info['area_id']];
        } elseif(1==$info['area_type']) {
            $where[] = ['community_id','=',$info['area_id']];
        }
        // 页数
        $page = $this->request->param('page','','intval');
        // 查询关键字
        $key_val = $this->request->param('key_val','','trim');
        // 对应查询关键字的内容
        $search_val = $this->request->param('value','','trim');
        if ('name'==$key_val && $search_val) {
            $where[] = ['name', 'like', '%'.$search_val.'%'];
        } elseif('phone'==$key_val && $search_val) {
            $where[] = ['phone', 'like', '%'.$search_val.'%'];
        }
        // 对应查询状态
        $status = $this->request->param('status','','intval');
        if ($status) {
            $where[] = ['status','=',$status];
        }
        // 对应查询时间
        $date = $this->request->param('date');
        if ($date && $date[0] && $date[1]) {
            $where[] = ['add_time','between',[strtotime($date[0]),strtotime(date('Y-m-d 23:59:59',strtotime($date[1])))]];
        }

        $out = $service_area_street->getLimitSuggestsList($where, $page);
        return api_output(0,$out);
    }

    /**
     * 获取留言建议详情
     * @param 传参
     * array (
     *  'suggestions_id'=> '建议id',
     *  'ticket' => '', 登录标识 必传
     * )
     * @author: wanziyang
     * @date_time: 2020/5/27 17:51
     * @return \json
     */
    public function detail() {
        $info = $this->adminUser;
        $service_area_street = new AreaStreetService();
        $where = [];
        if (0==$info['area_type']) {
            $where[] = ['street_id','=',$info['area_id']];
        } elseif(1==$info['area_type']) {
            $where[] = ['community_id','=',$info['area_id']];
        }
        // 街道社区留言建议id
        $suggestions_id = $this->request->param('suggestions_id','','intval');
        if(empty($suggestions_id)){
            return api_output_error(1001,'请上传id！');
        }
        $where[] = ['suggestions_id','=',$suggestions_id];
        $out = $service_area_street->getSuggestsDetail($where);
        // 如果是社区这边用户提的，街道只能看 不能回复
        $out['info']['is_edit'] = true;
        if (0==$info['area_type'] && $out['info']['community_id']>0) {
            $out['info']['is_edit'] = false;
        }
        if ($out['info'] && $out['info']['phone']){
            $out['info']['phone']=phone_desensitization($out['info']['phone']);
        }
        return api_output(0,$out);
    }

    /**
     * 添加留言建议回复
     * @param 传参
     * array (
     *  'suggestions_id'=> '建议id',
     *  'reply_content'=> '回复内容',
     *  'ticket' => '', 登录标识 必传
     * )
     * @author: wanziyang
     * @date_time: 2020/5/27 17:51
     * @return \json
     */
    public function saveMessageSuggestionsReplyInfo() {
        $info = $this->adminUser;
        $service_area_street = new AreaStreetService();
        $where = [];
        if (0==$info['area_type']) {
            $where[] = ['street_id','=',$info['area_id']];
        } elseif(1==$info['area_type']) {
            $where[] = ['community_id','=',$info['area_id']];
        }
        // 街道社区留言建议id
        $suggestions_id = $this->request->param('suggestions_id','','intval');
        if(empty($suggestions_id)){
            return api_output_error(1001,'请上传id！');
        }
        $where[] = ['suggestions_id','=',$suggestions_id];
        $out = $service_area_street->getSuggestsDetail($where);
        if (!$out['info']) {
            return api_output_error(1002,'对应留言建议不存在！');
        }
        $reply_content = $this->request->param('reply_content');
        if(empty($reply_content)){
            return api_output_error(1001,'请填写回复！');
        }
        $data = [
            'suggestions_id' => $suggestions_id,
            'reply_uid' => $info['area_id'],
            'reply_role' => $info['area_type'],
            'reply_content' => $reply_content,
            'add_time' => time(),
        ];
        $reply_id = $service_area_street->addSuggestsReplyOne($data);
        if(!$reply_id){
            return api_output_error(1002,'回复失败！');
        } else {
            if (2!=$out['info']['status']) {
                $service_area_street->saveSuggestsOne($where,['status'=>2]);
            }
            return api_output(0,['reply_id'=>$reply_id]);
        }
    }
	
	public function deleteMessageSuggestionsReplyInfo()
	{
		$info = $this->adminUser;
		$service_area_street = new AreaStreetService();
		$where = [];
		if (0==$info['area_type']) {
			$where[] = ['street_id','=',$info['area_id']];
		} elseif(1==$info['area_type']) {
			$where[] = ['community_id','=',$info['area_id']];
		}
		// 街道社区留言建议id
		$suggestions_id = $this->request->param('suggestions_id','','intval');
		if(empty($suggestions_id)){
			return api_output_error(1001,'请上传id！');
		}
		$where[] = ['suggestions_id','=',$suggestions_id];
		$out = $service_area_street->getSuggestsDetail($where);
		if (!$out['info']) {
			return api_output_error(1002,'对应留言建议不存在！');
		}

		$suggestions_id = $out['info']['suggestions_id'];
		$service_area_street->deleteSuggestsAndReply($suggestions_id);
		
		return api_output(0,['$suggestions_id'=>$suggestions_id],"删除成功");
	}
}