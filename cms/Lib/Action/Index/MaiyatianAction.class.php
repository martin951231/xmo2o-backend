<?php

class MaiyatianAction extends BaseAction
{
	public function authBack()
	{
		$this->uatAuthBack();
	}
	private function uatAuthBack()
	{
		if ($_POST) {
			$code = S($_POST['code_key']);
			if (empty($code)) {
				$this->error('授权码已失效，请重新授权！');
			}
			$account = trim($_POST['account']);
			$password = trim($_POST['password']);
			empty($account) && $this->error('请输入商户账号！');
			empty($password) && $this->error('请输入商户密码！');
			$map['_complex'] = [
				'account' => ['eq', $account],
				'phone' => ['eq', $account],
				'_logic' => 'or',
			];
			$map['pwd'] = md5($password);
			$map['status'] = 1;
			$merchant = M('Merchant')->where($map)->find();
			if (empty($merchant)) {
				$this->error('账号或密码错误，授权失败！');
			}
			$data = invoke_v20_service('\app\maiyatian\service\MaiyatianService/getAccessToken', ['params' => [
				'code' => $code,
				'mer_id' => $merchant['mer_id'],
			]]);
			$data = $data['data'];
			fdump_api($data, 'maiyatian/getAccessToken', 1);
			if (!empty($data)) {
				if(!empty($data['error_msg'])) {
					$this->error($data['data']['error_msg'] . '，授权失败！');
				}else if(!empty($data['retval']['code'] !== 200)){
					$message = $data['retval']['message'] ?? $data['retval']['msg'];
					$this->error($message . '，授权失败！');
				}
			} else {
				$this->error('授权失败！');
			}
			$this->success('授权成功，正在跳转到配送后台！');
		} else {
			$codeKey = time() . rand(1000, 9999) . uniqid();
			S($codeKey, $_GET['code'], 5 * 60);
			$redirectUri = $_GET['redirect_uri'] . '?state=' . $_GET['state'];
			$this->assign('redirect_uri', $redirectUri);
			$this->assign('code_key', $codeKey);
			$this->display('uatAuthBack');
		}
	}

	public function recharge()
	{
		$backPath = urlencode(C('config.site_url') . '/packapp/my/maiyatian_recharge.html?shop_id=' . $_GET['shop_id']);
		$failPath = urlencode(C('config.site_url') . '/packapp/plat/pages/plat_menu/my');
		$path = urlencode(C('config.site_url') . '/packapp/my/login.html?from_type=maiyatian&thirdLogin=1&back=' . $backPath . '&loginFail=' . $failPath);
		$payQrcode = C('config.site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($path);
		$this->assign('pay_qrcode', $payQrcode);
		$this->assign('shop_id', $_GET['shop_id']);
		$this->display();
	}
}