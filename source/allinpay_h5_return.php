<?php
// Browser return only: no order status updates and no trust in payment result parameters.
$reqsn = isset($_POST['reqsn']) ? $_POST['reqsn'] : (isset($_GET['reqsn']) ? $_GET['reqsn'] : (isset($_REQUEST['cusorderid']) ? $_REQUEST['cusorderid'] : ''));
if (!is_string($reqsn) || !preg_match('/^[A-Za-z0-9_\-*+#]{0,50}$/D', $reqsn)) {
    http_response_code(400);
    exit('Invalid order reference');
}
$params = array('reqsn' => $reqsn);
if (defined('ALLINPAY_H5_CANCEL')) $params['cancel'] = '1';
header('Cache-Control: no-store');
header('Location: /packapp/village/pages/pay/allinpayReturn?' . http_build_query($params), true, 303);
exit;
