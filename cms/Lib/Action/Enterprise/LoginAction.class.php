<?php

class LoginAction extends BaseAction
{
    public function index()
    {
        $this->display();
    }

    public function verify()
    {
        $verify_type = $_GET['type'];
        if (empty($verify_type)) {
            exit;
        }
        import('ORG.Util.Image');
        Image::buildImageVerify(4, 1, 'jpeg', 53, 26, 'qiye_' . $verify_type . '_verify');
    }

    public function logout()
    {
        session('qiye', null);
        header('Location: ' . U('Login/index'));
    }

    public function check()
    {
        if ($this->isAjax()) {
            $qiyeMod = D('Qiye');
            if (md5($_POST['verify']) != $_SESSION['qiye_login_verify']) {
                $this->json(['error' => '1', 'msg' => L_('验证码不正确！'), 'dom_id' => 'verify']);
            }
            $where['account'] = trim($_POST['account']);

            $nowQiye = $qiyeMod->field(true)->where(['account' => $_POST['account']])->find();
            if (empty($nowQiye)) {
                $nowQiye = $qiyeMod->field(true)->where(['phone' => $_POST['account']])->find();
                if (empty($now_merchant)) {
                    $this->json(['error' => '2', 'msg' => L_('用户名不存在！'), 'dom_id' => 'account']);
                }
            }
            $pwd = md5($_POST['pwd'] . $nowQiye['salt']);
            if ($pwd != $nowQiye['pwd']) {
                $this->json(['error' => '3', 'msg' => L_('密码错误！'), 'dom_id' => 'pwd']);
            }

            $lognRecord = [
                'last_ip' => get_client_ip(),
                'last_time' => $_SERVER['REQUEST_TIME']
            ];
            if ($qiyeMod->where(['qiye_id' => $nowQiye['qiye_id']])->save($lognRecord)) {
                session('qiye', $nowQiye);
                $this->json(['error' => '0', 'msg' => '登录成功,现在跳转~', 'dom_id' => 'account']);
            } else {
                $this->json(['error' => '6', 'msg' => '登录信息保存失败,请重试！', 'dom_id' => 'account']);
            }
        } else {
            exit('deney Access !');
        }
    }
}