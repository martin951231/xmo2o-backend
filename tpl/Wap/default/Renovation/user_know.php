<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width,maximum-scale=1,minimum-scale=1,initial-scale=1">
    <title>{pigcms{$res.title}</title>
    <style>
        body,p,h2,h3{
            padding:0;
            margin:0;
        }
        .WB_innerwrap {
            padding: 10px;
            color:#000;
        }
        .head_title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            color:inherit;
        }
        .WB_licence .private_cont {
            padding: 0px 12px;
            padding-bottom: 20px;
        }
        .WB_licence .private_cont h3 {
            font-size: 16px;
            text-align: left;
            line-height: 1.5;
            letter-spacing: 1.5px;
            font-weight: bold;
            margin-top: 20px;
            color:inherit;
        }
        .WB_licence .private_cont p {
            font-size: 14px;
            text-align: left;
            line-height: 1.5;
            letter-spacing: 1.5px;
        }
        .W_fb {
            font-weight: 700;
        }
        a {
            color: #FF8200;
        }
        .tx_al{
            text-align: center;
            margin-bottom: 10px;
        }
        .in_block{
            display: inline-block
        }
        .bt1{
            border: none;
            background-color: transparent;
            outline: none;
            width: 100px;
            height: 35px;
            border-radius:25px;
        }
        .refuse{
            border: 1px solid #169BD5;
            background: #FEFEFE;color: #169BD5;
        }
        .agree{
            background: #169BD5;color: #FFFFFF;
        }

    </style>
</head>
<body>
<div class="WB_miniblog">
    <div class="WB_miniblog_fb">
        <div class="WB_main">
            <div class="WB_licence">
                <div class="WB_cardwrap S_bg2">
                    <div class="WB_innerwrap" style="word-break: break-all;">
                        {pigcms{:htmlspecialchars_decode(htmlspecialchars_decode($res['content']))}
                    </div>
                    <div class="tx_al">
                        <div class="in_block">
                            <button class="bt1 refuse">不同意</button>
                            <div style="width: 40px" class="in_block"></div>
                            <button class="bt1 agree">同意</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<div id="cli_dialog_div"></div>
<script>
    $(function () {
        $('.refuse').click(function () {
            location.href =	"{pigcms{$res['refuse_url']}";
        })
        $('.agree').click(function () {
            location.href =	"{pigcms{$res['agree_url']}";
        })
    })
</script>
</body>
</html>