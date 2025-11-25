<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账户充值 - 麦芽田平台</title>
    <script type="text/javascript" src="{pigcms{$static_path}../js/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}../js/layer/layer.js"></script>
    <link rel="stylesheet" href="{pigcms{$static_public}font-awesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a3a5f 0%, #2daed6 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            opacity: 0.08;
            z-index: -1;
        }

        .recharge-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .recharge-header {
            background: #2daed6;
            color: white;
            padding: 25px 30px;
            text-align: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logo i {
            color: #FFD700;
            font-size: 28px;
        }

        .recharge-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 10px;
        }

        .recharge-content {
            padding: 10px;
            display: flex;
            flex-direction: column;
        }

        .amount-input span {
            background: #f5f7fa;
            padding: 0 20px;
            height: 52px;
            display: flex;
            align-items: center;
            font-weight: 600;
            color: #555;
            font-size: 18px;
        }

        .amount-input input {
            flex: 1;
            border: none;
            outline: none;
            padding: 15px;
            font-size: 18px;
            font-weight: 500;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 30px;
            background: #ecf1f8;
            border-radius: 12px;
            margin-top: 10px;
            text-align: center;
        }

        .qr-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
        }

        .qr-instructions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 15px;
        }

        .instruction-step {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #555;
        }

        .instruction-step i {
            color: #2daed6;
            font-size: 20px;
        }

        .payment-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .payment-option {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .payment-option img {
            width: 70%;
            height: 70%;
            object-fit: contain;
        }

        .footer {
            text-align: center;
            padding: 20px;
            font-size: 14px;
            color: #777;
            background: #f5f7fa;
            border-top: 1px solid #e0e0e0;
        }

        @media (max-width: 600px) {
            .recharge-content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
<div class="recharge-container">
    <div class="recharge-header">
        <div class="logo">
            <i class="fas fa-coins"></i>
            <span>配送平台</span>
        </div>
        <h1>账户充值</h1>
        <p class="recharge-subtitle">安全、便捷、快速到账</p>
    </div>

    <div class="recharge-content">
        <div class="qr-section" id="qrSection">
            <h2 class="qr-title">请使用微信扫码支付</h2>
            <div class="qr-code">
                <if condition="empty($shop_id)">
                    充值链接已失效<br/>请重新充值
                <else/>
                    <img id="qrImage" src="{pigcms{$pay_qrcode}" alt="支付二维码">
                </if>
            </div>

            <div class="qr-instructions">
                <div class="instruction-step">
                    <i class="fas fa-camera"></i>
                    <span>扫描上方二维码完成支付</span>
                </div>
                <div class="instruction-step">
                    <i class="fas fa-sync-alt"></i>
                    <span>支付成功后系统将自动到账</span>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>充值完成后请及时查看账户余额</p>
    </div>
</div>
</body>
</html>