<?php

/**
 * 淘宝天猫商品采集
 */
class TaobaoAction extends BaseAction
{
    /**
     * 根据商品ID获取商品详情
     * @author 张涛
     * @date 2020/05/27
     */
    public function getDetailById()
    {
        $this->parseGoods();
        return;
        $url = htmlspecialchars_decode(urldecode($_POST['url']));
        $api = $this->config['taobao_goods_detail_api'];
        $key = $this->config['taobao_appkey'];
        $rs = ['error_no' => 0, 'error_msg' => ''];
        try {
            if (empty($api) || empty($key)) {
                throw new \Exception(L_('秘钥或者接口未配置'));
            }
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query, $res);
            $id = $res['id'] ?? null;
            if (is_null($id)) {
                throw new \Exception(L_('URL解析商品ID失败'));
            }
            import('ORG.Net.Http');
            $http = new Http();
            $json = Http::curlGet($api . '?appkey=' . $key . '&tao_id=' . $id);
            $return = json_decode($json, true);
            if ($return['status'] != 200) {
                throw new \Exception(L_($return['content']));
            }
            $goodsDetail = $return['content'][0];
            $info = [
                'goods_name' => $goodsDetail['title'] ?? $goodsDetail['tao_title'],
                'describe' => $goodsDetail['jianjie'],
                'price' => $goodsDetail['size'],
                'pict_url' => $goodsDetail['pict_url'],
                'pict_url_title' => $goodsDetail['pict_url_title'],
                'zhibo_url' => $goodsDetail['zhibo_url'],
                'pcDescContent' => $goodsDetail['pcDescContent'] ? explode('|', $goodsDetail['pcDescContent']) : [],
                'origin_data' => $goodsDetail
            ];

            $merId = sprintf("%09d", $this->mer_id);
            $randNum = substr($merId, 0, 3) . '/' . substr($merId, 3, 3) . '/' . substr($merId, 6, 3);
            $uploadDir = "./upload/goods/{$randNum}/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            //判断是否需要上传至云存储
            $file_handle = new file_handle();
            //上传商品主图
            if ($info['pict_url']) {
                $targetPic = $uploadDir . uniqid() . '.jpg';
                $result = file_put_contents($targetPic, file_get_contents($info['pict_url']));
                if ($result) {
                    $file_handle->upload($targetPic);
                    $info['pict_url'] = replace_file_domain($targetPic);
                    $info['pict_url_title'] = $targetPic;
                }
            }
            //上传内容图
            if ($info['pcDescContent']) {
                foreach ($info['pcDescContent'] as $k => $v) {
                    $targetPic = $uploadDir . uniqid() . '.jpg';
                    $result = file_put_contents($targetPic, file_get_contents($info['pict_url']));
                    if ($result) {
                        $file_handle->upload($targetPic);
                        $info['pcDescContent'][$k] = replace_file_domain($targetPic);
                    }
                }
            }
            $rs['info'] = $info;
        } catch (\Exception $e) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }
        $this->ajaxReturn($rs);
    }


    /**
     * 根据淘宝/天猫/阿里巴巴商品详情地址解析商品信息
     * 【ID1004069】商城商品在线采集的对接(平台：https://www.99api.com/)
     * 示例
     * 淘宝：https://item.taobao.com/item.htm?spm=a219r.lm874.14.1.b00e21405sP8ww&id=611915480078&ns=1&abbucket=9#detail
     * 天猫：https://detail.tmall.com/item.htm?spm=a220m.1000858.1000725.1.2ca22a68dIXYUb&id=600663172170&skuId=4396271955397&user_id=2616970884&cat_id=2&is_b=1&rn=fdd7c8b8866e4e7b92ee7fbcbf119563
     * 阿里巴巴：https://detail.1688.com/offer/606886606273.html?spm=a26e3.8027625.1999173159.2.651c540cQwyW6M
     * @author 张涛
     * @date 2020/07/15
     */
    public function parseGoods()
    {
        set_time_limit(300);
        $url = htmlspecialchars_decode(urldecode($_POST['url']));
        $parseInfo = parse_url($url);
        $query = [];
        parse_str($parseInfo['query'], $query);
        $host = $parseInfo['host'] ?? '';
        $rs = ['error_no' => 0, 'error_msg' => ''];
        try {
            $apikey = $this->config['taobao_appkey'];
            if (empty($apikey)) {
                throw new \Exception(L_('apikey未配置'));
            }
            $plat = '';
            $url = '';
            $itemid = '';
            if (stripos($host, 'taobao.com') !== false) {
                $plat = 'taobao';
                $url = 'https://api03.6bqb.com/taobao/detail?apikey=' . $apikey;
                $itemid = $query['id'];
            } else if (stripos($host, 'tmall.com') !== false || stripos($host, 'tmall.hk') !== false) {
                $plat = 'tmall';
                $url = 'https://api03.6bqb.com/tmall/detail?apikey=' . $apikey;
                $itemid = $query['id'];
            } else if (stripos($host, '1688.com') !== false) {
                $plat = '1688';
                $url = 'https://api03.6bqb.com/alibaba/pro/detail?video=true&apikey=' . $apikey;
                preg_match('/(\d+)\.html/', $parseInfo['path'], $match);
                $itemid = $match[1] ?? '';
            } else if(stripos($host, 'yangkeduo.com') !== false){
                $plat = 'pdd';
                $url = 'https://api03.6bqb.com/pdd/detail?apikey=' . $apikey;
                $itemid = $query['goods_id'];
            }else {
                throw new \Exception(L_('地址解析失败，请输入淘宝/天猫/阿里巴巴/拼多多的商品详情页地址'));
            }

            if (!$itemid) {
                throw new \Exception(L_('商品ID解析失败'));
            }
            import('ORG.Net.Http');
            $http = new Http();
            $json = Http::curlGet($url . '&itemid=' . $itemid);
            /*if ($plat == 'taobao') {
                $json = file_get_contents('taobao.php');
            } else if ($plat == 'tmall') {
                $json = file_get_contents('tmall.php');
            } else {
                $json = file_get_contents('1688.php');
            }*/

            $return = json_decode($json, true);
            if ($return['retcode'] != '0000') {
                throw new \Exception($return['message'] ?? $return['data']);
            }
            $log = [
                'plat' => $plat,
                'itemid' => $itemid,
                'response' => $json,
                'add_time' => time()
            ];
            M('99api_log')->add($log);
            if (in_array($plat, ['taobao', 'tmall'])) {
                $goodsDetail = $return['data']['item'];
                $info = [
                    'goods_name' => $goodsDetail['title'] ?? '',
                    'describe' => $goodsDetail['subTitle'] ?? '',
                    'price' => isset($goodsDetail['sku']) ? $goodsDetail['sku'][0]['price'] : round($goodsDetail['priceRange'], 2),
                    'pict_url' => isset($goodsDetail['images']) ? array_slice($goodsDetail['images'], 0, 5) : [],
                    'zhibo_url' => isset($goodsDetail['videos']) ? $goodsDetail['videos'][0]['url']: '',
                    'pcDescContent' => $goodsDetail['desc'] ? $goodsDetail['desc'] : '',
                ];
            } else if($plat == 'pdd'){
                $goodsDetail = $return['data']['item'];
                $detail = $goodsDetail['detail'] ?? [];
                $descHtml = '';
                foreach ($detail as $v) {
                    $descHtml .= '<img src="' . $v['url'] . '"/>';
                }
                $info = [
                    'goods_name' => $goodsDetail['goodsName'] ?? '',
                    'describe' => $goodsDetail['goodsDesc'] ?? '',
                    'price' => round($goodsDetail['minNormalPrice'], 2),
                    'pict_url' => isset($goodsDetail['banner']) ? array_slice(array_column($goodsDetail['banner'], 'url'), 0, 5) : [],
                    'zhibo_url' => isset($goodsDetail['video']) ? $goodsDetail['video'][0]['url'] : '',
                    'pcDescContent' => $descHtml,
                ];
            }else {
                $goodsDetail = $return['data'];
                $info = [
                    'goods_name' => $goodsDetail['title'] ?? '',
                    'describe' => $goodsDetail['subTitle'] ?? '',
                    'price' => round(preg_replace('/[^\d|\.]/', '', $goodsDetail['displayPrice']), 2),
                    'pict_url' => isset($goodsDetail['images']) ? array_slice($goodsDetail['images'], 0, 5) : [],
                    'zhibo_url' => $goodsDetail['videoInfo']['videoUrl'] ?? '',
                    'pcDescContent' => $goodsDetail['desc'] ? $goodsDetail['desc'] : '',
                ];
            }

            $merId = sprintf("%09d", $this->mer_id);
            $randNum = substr($merId, 0, 3) . '/' . substr($merId, 3, 3) . '/' . substr($merId, 6, 3);
            $uploadDir = "./upload/goods/{$randNum}/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            //商品主图同步到oss
            $file_handle = new file_handle();
            if ($info['pict_url']) {
                $ossPic = [];
                foreach ($info['pict_url'] as $v) {
                    //最多5张图
                    if (count($ossPic) == 5) {
                        break;
                    }
                    $targetPic = $uploadDir . md5($v) . '.jpg';
                    $pic = substr($v, 0, 4) == 'http' ? $v : 'http:' . $v;
                    $result = file_put_contents($targetPic, file_get_contents($pic));
                    if ($result) {
                        $file_handle->upload($targetPic);
                        $ossPic[] = [
                            'url' => replace_file_domain($targetPic),
                            'title' => substr($targetPic,1)
                        ];
                    }
                }
                $info['pict_url'] = $ossPic;
            }

            //替换富文本里的商品图
            $uploadDir = "./upload/shop/content/{$randNum}/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $descImgs = $goodsDetail['descImgs'] ?? [];
            $replaceImgs = [];
            /*foreach ($descImgs as $v) {
                $targetPic = $uploadDir . md5($v) . '.jpg';
                $pic = substr($v, 0, 4) == 'http' ? $v : 'http:' . $v;
                $result = file_put_contents($targetPic, file_get_contents($pic));
                if ($result) {
                    $file_handle->upload($targetPic);
                    $replaceImgs[] = substr($targetPic,1);
                }
            }
            $info['pcDescContent'] = str_replace($descImgs, $replaceImgs, $info['pcDescContent']);*/
            $rs['info'] = $info;
        } catch (\Exception $e) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = $e->getMessage();
        }
        $this->ajaxReturn($rs);
    }
}