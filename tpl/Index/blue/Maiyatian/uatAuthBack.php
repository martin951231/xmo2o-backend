<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商家配送授权</title>
    <script type="text/javascript" src="{pigcms{$static_path}../js/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}../js/layer/layer.js"></script>
    <link rel="stylesheet" href="{pigcms{$static_public}font-awesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a3a5f 0%, #2daed6 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .auth-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 550px;
            overflow: hidden;
        }

        .auth-header {
            background: #2daed6;
            color: white;
            padding: 30px;
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
        }

        .auth-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 10px;
        }

        .auth-content {
            padding: 30px;
        }

        .auth-description {
            background: #f0f7ff;
            border-left: 4px solid #2daed6;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            font-size: 14px;
            color: #1a3a5f;
        }

        .auth-description strong {
            color: #2daed6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a3a5f;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #2daed6;
        }

        .form-control {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e1e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #2daed6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 95, 158, 0.1);
        }

        .btn-auth {
            background: #2daed6;
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-auth:hover {
            background: #1a3a5f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .auth-footer {
            text-align: center;
            padding: 20px;
            color: #7f8fa4;
            font-size: 14px;
            border-top: 1px solid #eef2f7;
        }

        @media (max-width: 480px) {
            .auth-container {
                border-radius: 12px;
            }

            .auth-header, .auth-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-header">
        <div class="logo">
            <i class="fas fa-truck"></i>
            商家配送授权
        </div>
        <p class="auth-subtitle">全渠道配送订单智能管理平台</p>
    </div>

    <div class="auth-content">
        <div class="auth-description">
            授权后，<strong>配送平台</strong>将帮助您管理商家配送订单，包括：
            <br/>
            <strong>快速发单｜订单追踪｜配送管理｜数据统计</strong>
        </div>

        <form id="authForm">
            <div class="form-group">
                <label for="username">商家账号</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="account" class="form-control" placeholder="请输入商家账号" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">登录密码</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" class="form-control" placeholder="请输入登录密码" required>
                </div>
            </div>

            <button type="submit" class="btn-auth">
                <i class="fas fa-key"></i> 授权并登录
            </button>
        </form>
    </div>
    
    <div class="auth-footer">
        授权后，您可通过配送平台管理商家配送订单
    </div>
</div>
<script>
    const redirect_uri = "{pigcms{$redirect_uri}";
    const code_key = "{pigcms{$code_key}";
    document.getElementById('authForm').addEventListener('submit',  function(e) {
        e.preventDefault();
        const account = document.getElementById('account').value;
        const password = document.getElementById('password').value;
        $.ajax({
            url:"{pigcms{:U('Maiyatian/authBack')}",
            type:"post",
            data:{"account":account, "password":password, "code_key":code_key},
            dataType: "Json",
            success:function(d){
                if (d.status > 0) {
                    layer.msg(d.info, function(){
                        window.location.replace(redirect_uri);
                    });
                } else {
                    layer.msg(d.info);
                }
            }
        });
    });
</script>
</body>
</html>