<?php
/**
 * 种草文章service
 * Author: hengtingmei
 * Date Time: 2021/5/15 11:48
 */

namespace app\grow_grass\model\service;

use app\common\model\db\Area;
use app\common\model\db\MerchantCategory;
use app\common\model\db\User;
use app\common\model\service\ConfigDataService;
use app\common\model\service\UserService;
use app\group\model\db\Group;
use app\group\model\db\GroupStore;
use app\grow_grass\model\db\GrowGrassArticle;
use app\grow_grass\model\db\GrowGrassArticleCollect;
use app\grow_grass\model\db\GrowGrassArticleLike;
use app\grow_grass\model\db\GrowGrassArticleReply;
use app\grow_grass\model\db\GrowGrassBindStore;
use app\grow_grass\model\db\GrowGrassFile;
use app\grow_grass\model\db\GrowGrassFollow;
use app\grow_grass\model\db\GrowGrassBindGoods;
use app\grow_grass\model\db\GrowGrassCategory;
use app\mall\model\db\MerchantStore;
use think\facade\App;

class GrowGrassArticleService
{
    public $growGrassArticleModel = null;

    public function __construct()
    {
        $this->growGrassArticleModel = new GrowGrassArticle();
    }


    /**
     * @param $param
     * 最近使用
     */
    public function getMyArticleList($uid)
    {
        $where = [['uid', '=', $uid], ['is_del', '=', 0], ['is_system_del', '=', 0]];
        $assign['list'] = (new GrowGrassArticle())->getMyArticleList($where, "article_id,name,category_id", 'audit_time desc', 1, 10, "category_id");
        if (!empty($assign['list'])) {
            foreach ($assign['list'] as $key => $val) {
                $arr = (new GrowGrassCategory())->getOne(['category_id' => $val['category_id']])->toArray();
                $assign['list'][$key]['name'] = $arr['name'];
            }
        }
        return $assign;
    }

    /**
     * @param $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $pageSize
     * @return mixed
     * 草稿箱
     */
    public function manuscript($where, $field = true, $order = true, $page = 1, $pageSize = 10)
    {
        $assign['list'] = (new GrowGrassArticle())->getSome($where, $field, $order, ($page - 1) * $pageSize, $pageSize)->toArray();
        $assign['pageSize'] = $pageSize;
        if (!empty($assign['list'])) {
            foreach ($assign['list'] as $key => $val) {
                $assign['list'][$key]['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $image = array();
                if (!empty($val['img'])) {
                        $arr = explode(",", $val['img']);
                        foreach ($arr as $key1 => $value) {
                            $rr['full_url'] =replace_file_domain($value);
                            $rr['image'] =$value;
                            $image[]=$rr;
                        }
                    $assign['list'][$key]['imgs'] = $image;
                }

                if (!empty($val['video_url'])) {
                    //$assign['list'][$key]['video_url'] = $val['video_url'];
                    $arr_video['url']=$val['video_url'];
                    $arr_video['video_full_url']=empty($val['video_url'])?"":replace_file_domain($val['video_url']);
                    $assign['list'][$key]['video_url'] = $arr_video;
                }

                if ($val['category_id']) {
                    $where = [['category_id', '=', $val['category_id']]];
                    $name=(new GrowGrassCategory())->getCategoryName($where, 'name');
                    $assign['list'][$key]['cat_name'] = $name[0];
                }

                $where=[['b.article_id','=', $val['article_id']]];
                $store_list=(new GrowGrassBindStore())->getSome($where)->toArray();
                if(!empty($store_list)){
                    $where=[['area_id','=',$store_list[0]['circle_id']]];
                    $area_name=(new Area())->getOne($where);
                    if(!empty($area_name)){
                        $area_name=$area_name->toArray();
                        $assign['list'][$key]['store_msg']['area_name']=$area_name['area_name'];
                    }else{
                        $assign['list'][$key]['store_msg']['area_name']="";
                    }

                    $where=[['cat_id','=',$store_list[0]['cat_id']]];
                    $cate_name=(new MerchantCategory())->getOne($where);
                    if(!empty($cate_name)){
                        $cate_name=$cate_name->toArray();
                        $assign['list'][$key]['store_msg']['cat_name']=$cate_name['cat_name'];
                    }else{
                        $assign['list'][$key]['store_msg']['cat_name']="";
                    }
                    $assign['list'][$key]['store_msg']['image']=empty($store_list[0]['logo'])?"":replace_file_domain($store_list[0]['logo']);;
                    $assign['list'][$key]['store_msg']['name']=$store_list[0]['name'];
                    $assign['list'][$key]['store_msg']['score']=$store_list[0]['score'];
                    $assign['list'][$key]['store_msg']['store_id']=$store_list[0]['store_id'];
                }else{
                    $assign['list'][$key]['store_msg']=[];
                }

                $goods=(new GrowGrassBindGoods())->getArticleGroup($val['article_id']);
                if(!empty($goods)){
                    $assign['list'][$key]['goods_msg']['goods_id']=$goods['goods_id'];
                    $assign['list'][$key]['goods_msg']['goods_type']=$goods['goods_type'];
                    $assign['list'][$key]['goods_msg']['image']=empty($goods['pic'])?"":replace_file_domain($goods['pic']);
                    $assign['list'][$key]['goods_msg']['name']=$goods['name'];
                    $assign['list'][$key]['goods_msg']['old_price']=$goods['old_price'];
                    $assign['list'][$key]['goods_msg']['price']=$goods['price'];
                    $assign['list'][$key]['goods_msg']['sale_count']=$goods['sale_count'];
                    $where=[['group_id','=',$goods['group_id']]];
                    $store_id=(new GroupStore())->getOne($where);
                    if(empty($store_id)){
                        $assign['list'][$key]['goods_msg']['store_id']="";
                    }else{
                        $store_id=$store_id->toArray();
                        $assign['list'][$key]['goods_msg']['store_id']=$store_id['store_id'];
                    }
                }else{
                    $assign['list'][$key]['goods_msg']=[];
                }
            }
        }
        return $assign;
    }

    /**
     * 添加编辑种草文章详情
     * @param int $articleId 文章id
     * @param array $extro 其他参数
     * @return array
     */
    public function saveArticle($param = [])
    {
        // 文章id
        $articleId = $param['article_id'] ?? 0;

        $saveData = []; // 保存数组
        $saveData['lng'] = $param['lng'] ?? '';// 经度
        $saveData['lat'] = $param['lat'] ?? '';// 维度
//        $saveData['location'] = $param['location'] ?? ''; // 具体位置
        $saveData['name'] = $param['name'] ?? '';// 标题
        $saveData['category_id'] = $param['category_id'] ?? '0';// 话题id
        $saveData['content'] = $param['content'] ?? '';// 文章详情
        $saveData['video_url'] = $param['video_url'] ?? '';// 视频链接
        $saveData['video_img'] = $param['video_img'] ?? '';// 视频封面图
        $saveData['is_manuscript'] = $param['is_manuscript'] ?? '0';// 是否存草稿1-存草稿0-发布
        $saveData['add_ip'] = request()->ip();
        $saveData['add_address'] = '';
        $address_json = \net\Http::curlGet('https://ip.taobao.com/outGetIpInfo?accessKey=alibaba-inc&ip='.$saveData['add_ip']);
        $address = json_decode($address_json,true);
        if($address['code']==0){
            if($address['data']['city']){
                $saveData['add_address'] = $address['data']['city'];
            }elseif ($address['data']['country']){
                $saveData['country'] = $address['data']['country'];
            }
        }
        $imgList = $param['images'] ?? [];// 文章图片数组
        $saveData['img'] = $imgList ? implode(',', $imgList) : '';
        $saveData['last_time'] = time();//最后修改时间


        $storeList = $param['store_list'] ?? [];// 绑定店铺列表
        $goodsList = $param['goods_list'] ?? [];// 绑定商品列表

        if (empty($saveData['name'])) {
            throw new \think\Exception(L_('请输入标题'), 1001);
        }

        if (empty($imgList) && empty($saveData['video_url'])) {
            throw new \think\Exception(L_('请上传图片或者视频'), 1001);
        }

        if (empty($saveData['category_id'])) {
            throw new \think\Exception(L_('请选择话题'), 1001);
        }

        $msg = L_('保存成功');
        // 当前登录的用户信息
        $user = request()->user;
        $uid = $user['uid'] ?? 0;
        $addData['uid'] = $uid;
        $addData['article_id'] = $articleId;
        if (!empty($imgList)) {//图片
            $where_del = [['article_id', '=', $articleId], ['file_style', '=', 0]];
            (new GrowGrassFile())->delData($where_del);
            foreach ($imgList as $key => $val) {
                $addData['url'] = $val;
                $addData['file_style'] = 0;
                $addData['add_time'] = time();
                (new GrowGrassFile())->add($addData);
            }

        }

        if (!empty($saveData['video_url'])) {
            $where_del = [['article_id', '=', $articleId], ['file_style', '=', 1]];
            (new GrowGrassFile())->delData($where_del);
            $addData['url'] = $saveData['video_img'];
            $addData['video_url'] = $saveData['video_url'];
            $addData['file_style'] = 1;
            $addData['add_time'] = time();
            (new GrowGrassFile())->add($addData);
        }

        if ($articleId) {// 编辑
            $where = [
                'article_id' => $articleId
            ];
            if ($saveData['is_manuscript'] == 0) {// 发布 修改状态未待审核
                $saveData['status'] = cfg('grow_grass_audit_auto') ? 20 : 10;
                $msg = L_('发布成功');
            }

            $detail = $this->getOne($where);
            if ($detail['uid'] != $uid) {
                throw new \think\Exception(L_('您没有权限编辑'), 1003);
            }
            if ($detail['status'] == 20) {// 已发布发布 修改状态未待审核
                $saveData['status'] = cfg('grow_grass_audit_auto') ? 20 : 10;
            }
            $saveData['publish_time'] = time();
            $res = $this->updateThis($where, $saveData);
            if ($res === false) {
                throw new \think\Exception(L_('编辑失败'), 1005);
            }

            // 删除绑定的店铺
            $where = [
                'article_id' => $articleId
            ];
            (new GrowGrassBindStoreService())->del($where);
            // 删除绑定的商品
            (new GrowGrassBindGoodsService())->del($where);

        } else {// 添加
            $msg = L_('添加成功');
            $saveData['uid'] = $uid;// 用户id
            $saveData['add_time'] = time();// 创建时间
            $saveData['publish_time'] = time();
            if ($saveData['is_manuscript'] == 0) {// 发布
                $saveData['status'] = cfg('grow_grass_audit_auto') ? 20 : 10;
            }
            $articleId = $this->add($saveData);
            if ($articleId === false) {
                throw new \think\Exception(L_('添加失败'), 1005);
            }
        }


        // 更新发布的文章数 参与人数
        (new GrowGrassCategoryService())->addArticleNum($saveData['category_id']);
        (new GrowGrassCategoryService())->addJoinNum($saveData['category_id'], $uid);

        if ($storeList) {// 绑定店铺
            $addStoreData = [];
            foreach ($storeList as $store) {
                $addStoreData[] = [
                    'store_id' => $store['store_id'],
                    'article_id' => $articleId,
                ];
            }

            (new GrowGrassBindStoreService())->addAll($addStoreData);
        }

        if ($goodsList) {// 绑定商品
            $addGoodsData = [];
            foreach ($goodsList as $goods) {
                $addGoodsData[] = [
                    'store_id' => $goods['store_id'],
                    'goods_id' => $goods['goods_id'],
                    'article_id' => $articleId,
                    'goods_type' => 'group'
                ];
            }
            (new GrowGrassBindGoodsService())->addAll($addGoodsData);
        }

        return ['msg' => $msg];
    }

    /**
     * 获取种草文章详情
     * @param int $articleId 文章id
     * @return array
     */
    public function getArticleEditInfo($articleId)
    {
        if (empty($articleId)) {
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 当前登录的用户信息
        $user = request()->user;
        $uid = $user['uid'] ?? 0;

        $where = [];
        $where[] = ['article_id', '=', $articleId];
        $where[] = ['is_del', '=', 0];
        $detail = $this->getOne(['article_id' => $articleId]);
        if (empty($articleId)) {
            throw new \think\Exception(L_('文章不存在或已删除'), 1003);
        }

        if ($detail['uid'] != $uid) {
            // throw new \think\Exception(L_('您没有权限编辑'), 1003);
        }
        $returnArr['content'] = $detail['content'];
        $returnArr['article_id'] = $detail['article_id'] ?? 0;// 文章ID
        $returnArr['name'] = $detail['name'] ?? '';// 文章名称
        $returnArr['uid'] = $detail['uid'] ?? 0;// 用户id
        $returnArr['category_id'] = $detail['category_id'] ?? '0';// 话题id
        $categoryDetail = (new GrowGrassCategoryService())->getOne(['category_id' => $detail['category_id']]);
        $returnArr['category_name'] = $categoryDetail['name'] ?? '';// 话题名

        // 文章封面图
        $returnArr['images'] = [];
        $returnArr['video_img'] = '';// 视频封面图
        $returnArr['video_url'] = replace_file_domain($detail['video_url']);// 视频链接
        if (isset($detail['video_img']) && $detail['video_img']) {// 视频首图
            $returnArr['video_img'] = replace_file_domain($detail['video_img']);// 视频封面图
        }

        if (isset($detail['img']) && $detail['img']) {
            $images = explode(',', $detail['img']);
            $returnArr['images'] = array_map('replace_file_domain', $images);
        }

        $returnArr['video_url'] = replace_file_domain($detail['video_url']);// 视频链接
        $returnArr['publish_time'] = $detail['publish_time'] ? date('Y-m-d', $detail['publish_time']) : '';// 发布时间
        $returnArr['views_num'] = $detail['views_num'] ?? 0;// 查看数
        $returnArr['reply_num'] = $detail['reply_num'] ?? 0;// 评论数
        $returnArr['like_num'] = $detail['like_num'] ?? 0;// 点赞数
        $returnArr['collect_num'] = $detail['collect_num'] ?? 0;// 收藏数
        // 经纬度和具体位置
        $returnArr['lng'] = $detail['lng'];
        $returnArr['lat'] = $detail['lat'];
        $returnArr['location'] = $detail['location'];

        // 绑定店铺列表
        $returnArr['store_list'] = (new GrowGrassBindStoreService())->getBindStoreList($articleId);
        // 绑定商品列表
        $goods=(new GrowGrassBindGoodsService())->getBindGoodsList($articleId);
        if(empty($goods)){
            $returnArr['goods_list'] =[];
        }else{
            foreach ($goods as $k=>$v){
                if($v['goods_type']=='group'){
                    $arr=(new Group())->getOne(['group_id'=>$v['goods_id']]);
                    if(!empty($arr)){
                        $arr=$arr->toArray();
                        $goods[$k]['name']=$arr['name'];
                        $goods[$k]['old_price']=$arr['old_price'];
                        $goods[$k]['price']=$arr['price'];
                        $goods[$k]['sale_count']=$arr['sale_count'];
                        $goods[$k]['group_id']=$arr['group_id'];
                        $goods[$k]['url'] = get_base_url('pages/group/v1/groupDetail/index?group_id=' . $arr['group_id'], true);
                        if(!empty($arr['pic'])){
                            $imgs=explode(';',$arr['pic']);
                            $goods[$k]['image']=replace_file_domain($imgs[0]);
                        }else{
                            $goods[$k]['image']='';
                        }
                    }
                }
            }
            $returnArr['goods_list'] =$goods;
        }


        return $returnArr;
    }

    /**
     * 获取种草文章详情
     * @param int $articleId 文章id
     * @param array $extro 其他参数
     * @return array
     */
    public function getArticleDetail($articleId, $extro = [])
    {
        if (empty($articleId)) {
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $lng = $extro['lng'] ?? '';// 经度
        $lat = $extro['lat'] ?? '';// 维度

        $where = [];
        $where[] = ['article_id', '=', $articleId];
        $where[] = ['is_del', '=', 0];
        if (isset($extro['is_system']) && $extro['is_system']) {// 系统后台访问
            $where[] = ['is_system_del', '=', 0];
        }

        $detail = $this->getOne(['article_id' => $articleId]);
        if (empty($articleId)) {
            throw new \think\Exception(L_('文章不存在或已删除'), 1003);
        }

        // 当前登录的用户信息
        $user = request()->user;
        $uid = $user['uid'] ?? 0;

        if (!isset($extro['is_system']) && $detail['status'] != 20 && $detail['uid'] != $uid) {
            throw new \think\Exception(L_('文章未发布不能查看'), 1003);
        }

        $returnArr['article_id'] = $detail['article_id'] ?? 0;// 文章ID
        $returnArr['name'] = $detail['name'] ?? ''; // 文章名称
        $returnArr['content'] = $detail['content'] ?: '';// 内容详情
        $returnArr['category_id'] = $detail['category_id'] ?? '0';// 话题id
        $categoryDetail = (new GrowGrassCategoryService())->getOne(['category_id' => $detail['category_id']]);
        $returnArr['category_name'] = $categoryDetail['name'] ?? '';// 话题名
        $returnArr['status'] =$detail['status'];
        $returnArr['is_del'] =$detail['is_del'];
        // 文章封面图
        $returnArr['images'] = [];
        $returnArr['video_img'] = '';// 视频封面图
        $returnArr['video_url'] = replace_file_domain($detail['video_url']);// 视频链接
        if (isset($detail['video_img']) && $detail['video_img']) {// 视频首图
            $returnArr['video_img'] = replace_file_domain($detail['video_img']);// 视频封面图
        }

        if (isset($detail['img']) && $detail['img']) {
            $images = explode(',', $detail['img']);
            $resize = function($r){
                return thumb_img($r,900,900);
            };
            $returnArr['images'] = array_map($resize, $images);
        }

        $returnArr['video_url'] = replace_file_domain($detail['video_url']);// 视频链接

        $nowUser = (new UserService())->getUser($detail['uid']);
        $returnArr['uid'] = $detail['uid'] ?? 0;// 发布者UID
        $returnArr['nickname'] = $nowUser['nickname'] ?? '';// 发布者昵称
        $returnArr['avatar'] = $nowUser['avatar'] ?? cfg('site_url') . '/static/images/user-avatar.jpg';// 发布者头像

        $returnArr['publish_time'] = $detail['publish_time'] ? date('Y-m-d', $detail['publish_time']) : '';// 发布时间
        $returnArr['views_num'] = $detail['views_num'] ?? 0;// 查看数
        $returnArr['reply_num'] = $detail['reply_num'] ?? 0;// 评论数
        $returnArr['like_num'] = $detail['like_num'] ?? 0;// 点赞数
        $returnArr['collect_num'] = $detail['collect_num'] ?? 0;// 收藏数

        // 查看用户点赞情况
        $liked = [];
        $collected = [];
        $followed = false;
        if ($uid) {
            $where = [
                'uid' => $uid,
                'article_id' => $articleId,
                'is_del' => 0,
            ];
            $liked = (new GrowGrassArticleLikeService())->getOne($where);
            $collected = (new GrowGrassArticleCollectService())->getOne($where);
            $followed = (new GrowGrassFollowService())->checkFollow($uid, $detail['uid']);
        }
        $returnArr['is_like'] = $liked ? true : false;// 当前用户是否点赞
        $returnArr['is_collect'] = $collected ? true : false;// 当前用户是否收藏文章
        $returnArr['is_follow'] = $followed ? true : false;// 当前用户关注作者

        // 绑定店铺列表
        $returnArr['store_list'] = (new GrowGrassBindStoreService())->getBindStoreList($articleId, $lng, $lat);

        // 绑定商品列表
        $goods=(new GrowGrassBindGoodsService())->getBindGoodsList($articleId);
        if(empty($goods)){
            $returnArr['goods_list'] =[];
        }else{
            foreach ($goods as $k=>$v){
                if($v['goods_type']=='group'){
                    $arr=(new Group())->getOne(['group_id'=>$v['goods_id']]);
                    if(!empty($arr)){
                        $arr=$arr->toArray();
                        $goods[$k]['name']=$arr['name'];
                        $goods[$k]['old_price']=$arr['old_price'];
                        $goods[$k]['price']=$arr['price'];
                        $goods[$k]['sale_count']=$arr['sale_count'];
                        $goods[$k]['group_id']=$arr['group_id'];
                        $goods[$k]['url'] = get_base_url('pages/group/v1/groupDetail/index?group_id=' . $arr['group_id'], true);
                        if(!empty($arr['pic'])){
                            $imgs=explode(';',$arr['pic']);
                            $goods[$k]['image']=replace_file_domain($imgs[0]);
                        }else{
                            $goods[$k]['image']='';
                        }
                    }
                }
            }
            $returnArr['goods_list'] =$goods;
        }


        // 评论列表
        $returnArr['reply_list'] = [];

        //分享文案
        $shareUrl = get_base_url('pages/wantToBuy/v1/articleDetail/index?article_id=' . $articleId, true);
        $shareWx = (cfg('pay_wxapp_important') && cfg('pay_wxapp_username')) ? 'wxapp' : 'h5';
        $shareImage = $returnArr['images'][0] ?? ($returnArr['video_img']??'');
        $shareTitle = $returnArr['name'] ?? '';
        $returnArr['share_info']['type'] =  $shareWx;
        $returnArr['share_info']['title'] =  $shareTitle;
        $returnArr['share_info']['desc'] =  '';
        $returnArr['share_info']['img'] =  $shareImage;
        if ($shareWx == 'wxapp') {
            $returnArr['share_info']['user_name'] =  cfg('pay_wxapp_username');
            $returnArr['share_info']['path'] =  '/pages/plat_menu/index?redirect=webview&webview_url=' . urlencode($shareUrl) . '&webview_title=' . urlencode($shareTitle);
        } else {
            $returnArr['share_info']['url'] = $shareUrl;
        }
        $returnArr['complaint_url'] = cfg('site_url').'/packapp/plat/pages/my/complaint/submit?type=grow_grass&mer_id=&store_id=';
        return $returnArr;
    }


    /**
     * 获取种草文章列表
     * @param $where array 条件
     * @return array
     */
    public function getArticleList($param = [],$type=0)
    {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $where = [];
        if (isset($param['category_id']) && $param['category_id']) {// 话题id
            $where[] = ['a.category_id', '=', intval($param['category_id'])];
        }
        $where[] = ['a.is_manuscript', '=', 0];
        if($type){
            $where[] = ['a.status', '=', 20];
            $where[] = ['a.is_del', '=', 0];
            $where[] = ['a.is_system_del', '=', 0];
        }
        $order = []; //排序
        if (isset($param['order']) && $param['order']) {
            $order = $param['order'];
        }

        $order['a.publish_time'] = 'DESC';
        $field = 'a.*,u.nickname,u.avatar';
        $list = $this->getArticleListByCondition($where, $field, $order, $page, $pageSize);

        //头像不存在
        foreach($list as $key=>$value){
            if(!$value['avatar']){
                $list[$key]['avatar'] = cfg('site_url') . '/static/images/user-avatar.jpg';
            }
        }

        $returnArr = [
            'list' => $this->formatDataList($list),
        ];

        return $returnArr;
    }

    public function getArticleByUids($uids, $page = 1, $pageSize = 10)
    {
        if (empty($uids)) return [];
        $where = [
            ['uid', 'in', $uids],
            ['status', '=', 20],
            ['is_system_del', '=', 0],
            ['is_manuscript', '=', 0],
            ['is_del', '=', 0],
        ];
        $field = 'article_id, name, uid, content, img, video_url, video_img, publish_time';
        $data = $this->growGrassArticleModel->getSome($where, $field, 'publish_time desc', $pageSize * ($page - 1), $pageSize);
        $return = [];
        if ($data) {
            $data = $data->toArray();
            foreach ($data as $key => $value) {
                // 文章封面图
                $temp['publish_time'] = date('Y-m-d', $value['publish_time']);
                $temp['article_id'] = $value['article_id'];
                $temp['content'] = $value['content'];
                $temp['uid'] = $value['uid'];
                $temp['image'] = [];
                if (isset($value['video_img']) && $value['video_img']) {// 视频首图
                    $temp['image'][] = replace_file_domain($value['video_img']);// 文章图片
                }
                if (isset($value['img']) && $value['img']) {
                    $images = explode(',', $value['img']);
                    foreach ($images as $img) {
                        $temp['image'][] = replace_file_domain($img);
                    }
                }
                $temp['have_video'] = isset($value['video_url']) && $value['video_url'] ? true : false;// 是否有视频
                $temp['video_url'] = replace_file_domain($value['video_url']);
                $return[] = $temp;
            }
        }
        return $return;
    }

    /**
     * 获取种草文章列表
     * @param $where array 条件
     * @return array
     */
    public function getArticleByCategoryList($param = [])
    {
        // 获取种草的配置
        /** @var ConfigDataService $configDataService */
        $configDataService = App::make(ConfigDataService::class);
        $configData = $configDataService->getDataOne(['name' => 'index_guess_like_nearby']);

        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $where = 'a.is_del=0 AND a.is_system_del=0';
        $have = '';
        $field = "a.*,u.nickname,u.avatar";
        if (isset($param['category_id']) && $param['category_id']) {// 话题id
            $where .= " AND a.category_id=" . intval($param['category_id']);
        }
        if(isset($param['is_manuscript'])){
            $where .= " AND a.is_manuscript = ".$param['is_manuscript'];
        }
        $order = "a.views_num DESC";//浏览人次递减

        // 经纬度兼容
        $lat = $param['lat'] ?? '';
        $long = $param['lng'] ?? '';
        if ($param['find_status'] == 0) {
            $type = $param['type']??'';//排序类型
            if (isset($param['lng']) && isset($param['lat']) && $param['lng'] * 1 > 0 && $param['lat'] * 1 > 0) {
                $field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`a`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`a`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`a`.`lng`*PI()/180)/2),2)))*1000) AS juli";
                $order = "juli ASC,a.publish_time DESC";
            } else {
                $order .= ",a.publish_time DESC";
            }
            
            if($type=='sort_new'){
                $order = "a.publish_time DESC";
            }elseif($type=='sort_hot'){
                $field .= ",(reply_num+like_num) as sort_hot";
                $order = "sort_hot DESC";
            }

            $myarr = array();
            if (isset($param['content']) && $param['content']) {//话题、关键字
                if (empty($where)) {
                    $where .= "a.name like" . "'%" . $param['content'] . "%'" . " OR a.content like " . "'%" . $param['content'] . "%'";
                } else {
                    $where .= " AND (a.name like" . "'%" . $param['content'] . "%'" . " OR a.content like " . "'%" . $param['content'] . "%'" . ")";
                }
            }
            if (isset($param['source']) && in_array($param['source'], ['publish','collect','like'])) {
                if (isset($param['uid']) && $param['uid']) {
                    if($param['source']=='publish'){
                        $where .= ($where ? ' AND ':'')."a.uid=" . $param['uid'];
                    }
                    $where_con = [['uid', '=', $param['uid']], ['is_del', '=', 0]];
                    $myarr['followed'] = (new GrowGrassFollow())->getCount($where_con);
                    $where_con = [['uid', '=', $param['uid']]];
                    $man = (new User())->getOne($where_con);
                    if (!empty($man)) {
                        $man = $man->toArray();
                        $myarr['fans'] = $man['follow_nums'];
                        array_push($where_con, ['status', '=', 20]);
                        $myarr['likes'] = (new GrowGrassArticle())->getSum($where_con, 'like_num');
                        $where_con1 = [['uid', '=', $param['uid']], ['is_del', '=', 0], ['is_manuscript', '=', 1]];
                        $myarr['is_manuscript'] = ((new GrowGrassArticle())->getCount($where_con1)) > 0 ? 1 : 0;
                        $myarr['grow_grass_bg_img'] = $man['grow_grass_bg_img']?replace_file_domain($man['grow_grass_bg_img']):'';
                    } else {
                        $myarr['followed'] = 0;
                        $myarr['fans'] = 0;
                        $myarr['likes'] = 0;
                        $myarr['is_manuscript'] = 0;
                        $myarr['grow_grass_bg_img'] = '';
                    }
                }
            }else{
                if (empty($where)) {
                    $where .= "a.is_manuscript=0 AND a.is_manuscript=0 AND a.is_del=0 AND a.status=20";
                }else{
                    $where .= " AND a.is_manuscript=0 AND a.is_manuscript=0 AND a.is_del=0 AND a.status=20";
                }
            }

            if (empty($where)) {
                $where .= "a.status=20";
            }
            if(!empty($param['source'])&&$param['source'] == 'collect'){ //收藏
                $where .= ($where ? ' AND ' : '') . 'c.uid = ' . $param['uid'] . ' AND c.is_del = 0';
                $list = (new GrowGrassArticleCollect())->getList($where, $field, $order, $page, $pageSize);
            }elseif(!empty($param['source'])&&$param['source'] == 'like'){ //点赞
                $where .= ($where ? ' AND ' : '') . 'l.uid = ' . $param['uid'] . ' AND l.is_del = 0';
                $list = (new GrowGrassArticleLike())->getList($where, $field, $order, $page, $pageSize);
            }else {
                $list = $this->getArticleListCategoryCondition($where, $field, $order, $page, $pageSize, $have);
            }
            $returnArr = [
                'list' => $this->formatDataList($list, $long, $lat),
                'pageSize' => $pageSize,
            ];
            if (!empty($myarr)) {
                $returnArr = [
                    'list' => $this->formatDataList($list, $long, $lat),
                    'myArr' => $myarr,
                    'pageSize' => $pageSize,
                ];
            }
        } else {//用户
            if (empty($where)) {
                $where .= "a.uid>0";
            } else {
                $where .= " AND u.uid>0";
            }

            if (isset($param['content']) && $param['content']) {
                $where .= " AND u.nickname like '%" . $param['content'] . "%'";
            }
            $field = "(u.follow_nums+u.article_nums) as total_num,u.follow_nums,u.article_nums,u.uid,u.nickname,u.avatar,a.article_id";
            $order = "total_num desc,a.publish_time desc";
            $list = $this->getArticleListCategoryByUidCondition($where, $field, $order, $page, $pageSize);
            $user = request()->user;
            $uid = $user['uid'] ?? 0;
            if (!empty($list)) {
                foreach ($list as $key => $val) {
                    $where = [['uid', '=', $uid], ['follow_uid', '=', $val['uid']], ['is_del', '=', 0]];
                    $status = (new GrowGrassFollow())->getOne($where);
                    if (empty($status)) {
                        $list[$key]['follow_status'] = 0;//没有关注
                    } else {
                        $list[$key]['follow_status'] = 1;//已关注
                    }
                }
            }
            $returnArr = [
                'list' => $list,
                'pageSize' => $pageSize,
            ];
        }
        return $returnArr;
    }

    /**
     * @param $article_id
     * @return bool|mixed
     * 发布文章更新用户发布文章数
     */
    public function updateArticleStatus($article_id)
    {
        $where = [['article_id', '=', $article_id], ['status', '=', 20]];
        $arr = (new GrowGrassArticle())->getOne($where);
        $ret = false;
        if (!empty($arr)) {
            $arr = $arr->toArray();
            $where = [['uid', '=', $arr['uid']]];
            $ret = (new User())->setInc($where, 'article_nums', 1);
        }
        return $ret;
    }

    /**
     * @param $articleId
     * @return bool
     * 增加文章浏览数
     */
    public function addViewsNum($articleId)
    {
        $where = [['article_id', '=', $articleId]];

        $detail = $this->getOne($where);
        // 更新文章浏览数
        $this->growGrassArticleModel->where($where)->inc('views_num')->update();

        // 更新绑定话题的浏览数
        (new GrowGrassCategoryService())->addViewsNum($detail['category_id']);
        return true;
    }

    /**
     * 重组返回给前端的数据
     * @param $detail array 话题文章详情
     * @return array
     */
    public function formatDataList($list, $lng = 0, $lat = 0)
    {
        $returnArr = [];// 返回数组

        if ($list) {

            // 当前登录的用户信息
            $user = request()->user;
            $uid = $user['uid'] ?? 0;
            $likedList = [];
            if ($uid) {
                // 查看用户点赞情况
                if(is_array($list)){
                    $articleArr = array_column($list, 'article_id');
                    $likedList = (new GrowGrassArticleLikeService())->getLikedList($uid, $articleArr);
                }
            }

            $cate_id_set = array_unique(array_column($list, 'category_id'));
            /** @var GrowGrassCategory $category */
            $category = App::make(GrowGrassCategory::class);
            $cate_name_set = $category->where('category_id', 'in', $cate_id_set)->column('name', 'category_id');

            foreach ($list as $detail) {
                $temp = [];
                $temp['article_id'] = $detail['article_id'] ?? 0;// 文章ID
                $temp['name'] = $detail['name'] ?? '';// 文章名称

                // 文章封面图
                $temp['image'] = '';
                if (isset($detail['video_img']) && $detail['video_img']) {// 视频首图
                    $temp['image'] = thumb_img($detail['video_img'], 640, 640, 'fill');// 文章图片
                } elseif (isset($detail['img']) && $detail['img']) {
                    $images = explode(',', $detail['img']);
                    $temp['image'] = thumb_img($images[0], 640, 640, 'fill');
                }

                $temp['have_video'] = isset($detail['video_url']) && $detail['video_url'] ? true : false;// 是否有视频
                $temp['uid'] = $detail['uid'] ?? 0;// 发布者UID
                $temp['nickname'] = $detail['nickname'] ?? '';// 发布者昵称
                $temp['avatar'] = (isset($detail['avatar']) && $detail['avatar']) ? replace_file_domain($detail['avatar']) : cfg('site_url') . '/static/images/user-avatar.jpg';// 发布者头像
                $temp['like_num'] = $detail['like_num'] ?? 0;// 点赞数
                $temp['url'] = get_base_url('',1).'pages/wantToBuy/v1/articleDetail/index?article_id='.$detail['article_id'];// 跳转链接
                $where2 = [['uid', '=', $uid], ['article_id', '=', $temp['article_id']], ['is_del', '=', 0]];
                $arr = (new GrowGrassArticleLike())->getOne($where2);
                $temp['is_like'] = empty($arr) ? false : true;// 当前用户是否点赞
                $temp['category_id'] = $detail['category_id'];
                $temp['category_name'] = $cate_name_set[$detail['category_id']];
                if ($detail['lat'] > 0 && $detail['lng'] > 0 && $lng > 0 && $lat > 0) {
                    $temp['range'] = getRange(getDistance($detail['lat'],$detail['lng'],$lat,$lng),false,true);
                } else {
                    $temp['range'] = '';
                }
                $returnArr[] = $temp;

            }

        }
        return $returnArr;
    }

    /**
     * @param $where
     * @return array
     * 草稿箱发布
     */
    public function upArticle($where){
        $result = $this->growGrassArticleModel->updateThis($where,['is_manuscript'=>0,'status'=>10]);
        if (!$result) {
            return [];
        }
        return $result;
    }
    /**
     * 获取一条数据
     * @param $where array 条件
     * @return array
     */
    public function getOne($where)
    {
        $result = $this->growGrassArticleModel->getOne($where);
        if (!$result) {
            return [];
        }
        return $result;
    }

    /**
     * 获取前台显示的文章列表
     * @param array $where
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getArticleListByCondition($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $result = $this->growGrassArticleModel->getArticleListByCondition($where, $field, $order, $page, $limit);
        if (empty($result)) return [];
        return $result->toArray();
    }

    /**
     * 获取前台显示的文章列表---有附近条件
     * @param array $where
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getArticleListCategoryCondition($where = "", $field = true, $order = true, $page = 0, $limit = 0, $have = '')
    {
        $result = $this->growGrassArticleModel->getArticleListCategoryCondition($where, $field, $order, $page, $limit, $have);
        if (empty($result)) return [];
        return $result->toArray();
    }

    public function getArticleListCategoryByUidCondition($where = "", $field = true, $order = true, $page = 0, $limit = 0)
    {
        $result = $this->growGrassArticleModel->getArticleListCategoryByUidCondition($where, $field, $order, $page, $limit);
        if (empty($result)) return [];
        return $result->toArray();
    }

    /**
     * 获取前台显示的列表---用户名获取
     * @param array $where
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getArticleListByNickName($where = "", $field = true, $order = true, $page = 0, $limit = 0)
    {
        $result = $this->growGrassArticleModel->getArticleListByNickName($where, $field, $order, $page, $limit);
        if (empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param array $where
     * @param string $field
     * @param array $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getSome($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $start = ($page - 1) * $limit;
        $result = $this->growGrassArticleModel->getSome($where, $field, $order, $start, $limit);
        if (empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数量和
     * @param array $where
     * @param string $field
     * @return int
     */
    public function getCountSum($where = [], $field = 'reply_num' )
    {
        $result = $this->growGrassArticleModel->where($where)->sum($field); 
        if (empty($result)) return 0;
        return $result;
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = [])
    {
        $result = $this->growGrassArticleModel->getCount($where);
        if (empty($result)) return 0;
        return $result;
    }

    /**
     * 添加数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function add($data)
    {
        if (empty($data)) {
            return false;
        }

        $result = $this->growGrassArticleModel->add($data);

        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($where) || empty($data)) {
            return false;
        }

        $result = $this->growGrassArticleModel->updateThis($where, $data);
        if ($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * @param $uid
     * @return mixed
     * 用户列表关注取消关注
     */
    public function updateGrowGrassFollow($uid)
    {
        $user = request()->user;
        $uid1 = $user['uid'] ?? 0;
        $where = [['uid', '=', $uid1], ['follow_uid', '=', $uid]];
        $status = (new GrowGrassFollow())->getOne($where);
        $where1 = [['uid', '=', $uid]];
        if (empty($status)) {
            $data['uid'] = $uid1;
            $data['follow_uid'] = $uid;
            $data['create_time'] = time();
            $ret = (new GrowGrassFollow())->add($data);
            if ($ret) {
                (new User())->setInc($where1, 'follow_nums', 1);//关注加1
            }
        } else {
            $status = $status->toArray();
            if ($status['is_del']) {
                $data['is_del'] = 1;
                (new User())->setDec($where1, 'follow_nums', 1);//取关减1
            } else {
                $data['is_del'] = 0;
                (new User())->setInc($where1, 'follow_nums', 1);//关注加1
            }
            $ret = (new GrowGrassFollow())->updateThis($where, $data);
            return $ret;
        }
    }

    /**
     * 发布列表
     */
    public function getArticleLists($name, $store_id, $category_id, $page, $pageSize, $status, $type)
    {
        $where = [['is_system_del', '=', 0],['is_manuscript', '=', 0]];
        // 标题筛选
        if (!empty($name)) {
            array_push($where, [['a.name', 'like', '%' . $name . '%']]);
        }
        //关联店铺
        if ($store_id > -1) {
            array_push($where, ['d.store_id', '=', $store_id]);
        }
        //关联话题
        if ($category_id > -1) {
            array_push($where, ['a.category_id', '=', $category_id]);
        }
        //状态
        if ($status > -1) {
            if($status = 1){
                array_push($where, ['a.is_del', '=', 1]);
            }else{
                array_push($where, ['a.status', '=', $status]);
            }
        }
        // type
        if ($type == 1) {
            array_push($where, ['a.status', '=', 10]);
        } elseif ($type == 2) {
            array_push($where, ['a.status', '=', 20]);
        } elseif ($type == 3) {
            array_push($where, ['a.status', '=', 30]);
        }elseif ($type == 4) {
            array_push($where, ['a.is_del', '=', 1]);
        }
        //排序
        $order = [
            'a.article_id' => 'DESC',
        ];
        $field = 'a.*,b.nickname as user_name,c.name as category_name,d.name as store_name';
        $arr = $this->growGrassArticleModel->getArticleLists($where, $field, $order, $page, $pageSize);
        foreach ($arr as $k => $v) {
            $arr[$k]['id_name'] = ['name' => $v['name'], 'id' => $v['article_id']];
            $arr[$k]['category_id_name'] = ['name' => $v['category_name'], 'id' => $v['category_id']];
            $arr[$k]['actions'] = ['status' => $v['status'], 'id' => $v['article_id']];
            $arr[$k]['publish_time'] = $v['publish_time'] ? date('Y-m-d H:i:s', $v['publish_time']) : '-';
            // 关联商品
            $goods = (new GrowGrassBindGoods())->getArticleGroup($v['article_id']);
            if(!empty($goods)){
                $arr[$k]['goods_name'] = $goods['group_name'];
                $arr[$k]['goods_id'] = $goods['goods_id'];
            }
        }
        $count = $this->growGrassArticleModel->getArticleCount($where, $field);
        $list['list'] = $arr;
        $list['list_count'] = count($arr);
        $list['count'] = $count;
        return $list;
    }

    /**
     * 关联话题、店铺
     */
    public function getRelation($type)
    {
        if ($type == 1) {
            $list = (new GrowGrassCategory())->field('category_id,name')->where(array('is_del' => 0, 'status' => 1))->select()->toArray();
        } elseif ($type == 2) {
            $list = (new MerchantStore())->field('store_id,name')->where(array('claim_status' => 1, 'auth' => 3))->select()->toArray();
        }
        return $list;
    }

    /**
     * 设置为发布、不予发布、删除
     */
    public function getEditArticle($id, $type)
    {
        // 参与人数、发布文章数操作
        $cate_id = (new GrowGrassArticle())->where(['article_id'=>$id])->find();
        if($cate_id){
            $cate_id = $cate_id->toArray();
            if($type == 1){
                // 设置发布
                // 增加参与人数
                (new GrowGrassCategory())->where(['category_id'=>$cate_id['category_id']])->inc('join_num')->update();
            }elseif($type == 2){
                // 设置不予发布
                if($cate_id['status'] == 20){
                    // 减少参与人数
                    (new GrowGrassCategory())->where(['category_id'=>$cate_id['category_id']])->dec('join_num')->update();
                }
            }elseif($type == 3){
                // 设置删除
                // 减少发布文章数
                (new GrowGrassCategory())->where(['category_id'=>$cate_id['category_id']])->dec('article_num')->update();
                if($cate_id['status'] == 20){
                    // 减少参与人数
                    (new GrowGrassCategory())->where(['category_id'=>$cate_id['category_id']])->dec('join_num')->update();
                }
            }
        }
        $list = $this->growGrassArticleModel->getEditArticle($id, $type);
        return $list;
    }

    /**
     * 文章详情
     */
    public function getArticleDetails($id)
    {
        $list = $this->growGrassArticleModel->getArticleDetails($id);
        return $list;
    }

    /**
     * 同步种草文章评论总数
     * @param $articleId
     * @date: 2021/06/11
     */
    public function syncReplyNum($articleId)
    {
        $count = (new GrowGrassArticleReply())->where([['article_id', '=', $articleId], ['status', '=', 1]])->count();
        return $this->growGrassArticleModel->updateThis(['article_id' => $articleId], ['reply_num' => $count]);
    }

    /**
     * 同步种草文章评论点赞数
     * @param $articleId
     * @date: 2021/06/11
     */
    public function syncLikeNum($articleId)
    {
        $count = (new GrowGrassArticleLike())->where([['article_id', '=', $articleId], ['is_del', '=', 0]])->count();
        return $this->growGrassArticleModel->updateThis(['article_id' => $articleId], ['like_num' => $count]);
    }
}