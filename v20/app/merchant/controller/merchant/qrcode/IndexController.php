<?php
/**
 * 后台首页
 * author by hengtingmei
 */
namespace app\merchant\controller\merchant\qrcode;

use app\common\controller\CommonBaseController;
use app\common\model\service\weixin\AccessTokenWxappExpiresService;
use app\common\model\service\weixin\RecognitionService;
use app\merchant\model\service\MerchantStoreService;
require_once '../extend/phpqrcode/phpqrcode.php';

class IndexController extends CommonBaseController {
    public function initialize()
    {
        parent::initialize();
    }


    /**
     * desc: 查看微信渠道二维码
     * return :array
     */
    public function seeWxQrcode(){

        $param['type'] = $this->request->param('type', '', 'trim');
        $param['id'] = $this->request->param('id', '', 'intval');
        if(empty($param['id'])){
            return api_output(0, []);
        }
        switch ($param['type']){
            case 'merchantstore':
                // 店铺综合二维码
                $returnArr = (new MerchantStoreService())->seeQrcode($param['id']);
                break;
            case 'mallstore':
                // 商城店铺主页二维码
                $returnArr = (new MerchantStoreService())->seeQrcode($param['id'],$param['type']);
                break;
        }
        return api_output(0, $returnArr);
    }


    /**
     * desc: 查看微信渠道二维码
     * return :array
     */
    public function createPageQrcode(){
        $page = $this->request->param('page', '', 'trim');
        $qr_path = htmlspecialchars_decode($page);
        $access_token_array = (new AccessTokenWxappExpiresService())->getAccessToken();
        if ($access_token_array['errcode']) {
            exit('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
        }

        $qrcode_url = TICKET_PREFIX.$access_token_array['access_token'];
        $post_data = array(
            'path'=>$qr_path,
            'width'=>425
        );
        $img_content = $this->curlPost($qrcode_url,json_encode($post_data));
        header('Content-type: image/jpeg');
        echo $img_content;
    }

    /**
     * desc: 生成网页二维码
     * return :array
     */
    public function seeH5Qrcode(){
        $url = $this->request->param('url');
        $qrcode = new \QRcode();

        $size = $this->request->param('size','10');
        $errorLevel = "L";

        //打开缓冲区
        ob_start();
        $qrcode->png($url, false, $errorLevel, $size, 2);
        //这里就是把生成的图片流从缓冲区保存到内存对象上，使用base64_encode变成编码字符串，通过json返回给页面。
        $imageString = base64_encode(ob_get_contents());
        //关闭缓冲区
        ob_end_clean();
        //把生成的base64字符串返回给前端
        $data = array(
            'code'=>200,
            'data'=>$imageString
        );

        return api_output(0, $data);
    }


}
