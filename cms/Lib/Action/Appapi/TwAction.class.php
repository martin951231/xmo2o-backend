<?php

/**
 * 图维软件对接
 *
 * @date: 2023/07/20
 */
class TwAction extends BaseAction
{
    protected $taskMod = null;

    protected $logId;

    protected function _initialize()
    {
        parent::_initialize();
        $this->taskMod = D('Tw_task');
        $this->logId = date('YmdHis') . createRandNumber(8);
    }

    protected function _output($code = 0, $result = [], $errorMsg = '成功')
    {
        $array = [
            'logId' => $this->logId,
            'errorCode' => $code,
            'errorMsg' => $errorMsg,
            'result' => $result
        ];
        $this->ajaxReturn($array);
    }

    //全量同步门店商品
    public function syncAllProducts()
    {
        $itemLists = $_POST['item_lists'];
        $shopId = $_POST['shop_id'];
        if (empty($shopId) || empty($itemLists)) {
            $this->_output(1001, [], '参数有误');
        }

        $tm = time();
        foreach ($itemLists as $i) {
            $i['shop_id'] = $shopId;
            $data = [
                'type' => 'sync_product',
                'data' => json_encode($i, JSON_UNESCAPED_UNICODE),
                'status' => 0,
                'create_time' => $tm
            ];
            $this->taskMod->add($data);
        }
        $this->_output();
    }

    //保存商品
    public function saveProduct()
    {
        $shopId = $_POST['shop_id'];
        if (empty($shopId)) {
            $this->_output(1001, [], '参数有误');
        }
        $data = [
            'type' => 'save_product',
            'data' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'status' => 0,
            'create_time' => time()
        ];
        $this->taskMod->add($data);
        $this->_output();
    }

    public function delProduct()
    {
        $shopId = $_POST['shop_id'] ?? '';
        $itemNo = $_POST['item_no'] ?? '';
        if (empty($shopId) || empty($itemNo)) {
            $this->_output(1001, [], '参数有误');
        }
        try {
            $this->taskMod->delProduct($shopId, $itemNo);
            $this->_output();
        } catch (Exception $e) {
            $this->_output(1001, [], $e->getMessage());
        }
    }

    //商品更新图片
    public function updateProductImage()
    {
        $shopId = $_POST['shop_id'];
        if (empty($shopId)) {
            $this->_output(1001, [], '参数有误');
        }
        $data = [
            'type' => 'sync_product_image',
            'data' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'status' => 0,
            'create_time' => time()
        ];
        $this->taskMod->add($data);
        $this->_output();
    }

    //全量同步门店类别
    public function syncAllCategory()
    {
        $itemLists = $_POST['item_lists'];
        $shopId = $_POST['shop_id'];
        if (empty($shopId) || empty($itemLists)) {
            $this->_output(1001, [], '参数有误');
        }

        $tm = time();
        foreach ($itemLists as $i) {
            $i['shop_id'] = $shopId;
            $data = [
                'type' => 'sync_category',
                'data' => json_encode($i, JSON_UNESCAPED_UNICODE),
                'status' => 0,
                'create_time' => $tm
            ];
            $this->taskMod->add($data);
        }
        $this->_output();
    }

    //保存类别
    public function saveCategory()
    {
        $shopId = $_POST['shop_id'];
        if (empty($shopId)) {
            $this->_output(1001, [], '参数有误');
        }
        $data = [
            'type' => 'save_category',
            'data' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'status' => 0,
            'create_time' => time()
        ];
        $this->taskMod->add($data);
        $this->_output();
    }

    //删除类别
    public function delCategory()
    {
        $shopId = $_POST['shop_id'] ?? '';
        $clsNo = $_POST['item_clsno'] ?? '';
        if (empty($shopId) || empty($clsNo)) {
            $this->_output(1001, [], '参数有误');
        }
        try {
            $this->taskMod->delCategory($shopId, $clsNo);
            $this->_output();
        } catch (Exception $e) {
            $this->_output(1001, [], $e->getMessage());
        }
    }

    //Base64图片转HTTP
    public function base64ToImage()
    {
        $encode = $_POST['image'];
        try {
            $url = $this->writeImage($encode);
            $this->_output(0, ['url' => $url]);
        } catch (Exception $e) {
            $this->_output(1001, [], $e->getMessage());
        }
    }

    private function writeImage($data)
    {
        $arr = explode(';base64,', $data);
        $ext = str_replace('data:image/', '', $arr[0]);
        $base64Data = $arr[1] ?? '';

        if (empty($ext) || empty($base64Data)) {
            throw new Exception('base64格式错误');
        }

        $imageData = base64_decode($base64Data);
        $fileDir = "./upload/tw/" . date('Ymd');
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0755, true);
        }
        $fileName = $fileDir . '/' . uniqid() . '.' . $ext;
        //二进制方式写入到文件
        $file = fopen($fileName, 'wb');
        $len = fwrite($file, $imageData);
        fclose($file);

        if ($len !== false) {
            return replace_file_domain($fileName);
        } else {
            throw new Exception('生成图片失败');
        }
    }


    public function syncOrder(){
        $shopId = $_POST['shop_id'];
        if (empty($shopId)) {
            $this->_output(1001, [], '参数有误');
        }
        $data = [
            'type' => 'sync_order',
            'data' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'status' => 0,
            'create_time' => time()
        ];
        $this->taskMod->add($data);
        $this->_output();
    }
}
