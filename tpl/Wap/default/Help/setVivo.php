<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{pigcms{:L_("权限设置")}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="flexible" content="initial-dpr=2" />
    <script src="{pigcms{$static_path}help/js/rem.js"></script>
    <style>
        *{
            padding: 0;
            margin: 0;
        }
        ul{
            list-style: none;
        }
        html{
            background-color: #ffffff;
        }
        .task-contain{
            background-color: #fff;
            height: 2.5rem;
            font-size: 0.28rem;
            margin:0 0.3rem;
            border-bottom: 0.02rem solid #eee;
        }
        .task-contain .task-title{
            color: #323232;
            font-weight: bold;
            font-size: 0.3rem;
            line-height: 0.9rem;
        }
        .task-contain .task-content{
            line-height: 0.4rem;
            color: #646464;
            width: 6.5rem;
        }
        .task-contain .task-content .imgsrc img{
            float: left;
            width: 46.8%;
            display: block;
            margin-right: 0.2rem;
            margin-bottom: 0.2rem;
        }
        .clearfix:after{
            content: '';
            clear: both;
            height: 0;
            display: block;
        }
        .task-contain .bottom-title{
            font-size: 0.29rem;
            font-weight: bolder;
            line-height: normal;
            padding-top: 0.2rem;
        }
        .btn button{
            width: 100%;
            height: 0.8rem;
            background-color: #4696f5;
            color: #fff;
            border: none;
            font-size: 0.3rem;
            border-radius: 0.05rem;
            display: block;
            margin: 0.2rem 0 0.3rem;
        }
    </style>
</head>
<body>
<div class="task-contain">
    <h3 class="task-title">{pigcms{:L_("不再错过系统派单/推荐订单")}</h3>
    <ul class="task-content">
        <li>{pigcms{:L_("为确保及时收到系统派单、推荐订单等，请开启以下权限设置")}</li>
        <li style="color: #eb625a;line-height: 0.5rem">{pigcms{:L_("如果你已设置，请忽略")}</li>
    </ul>
</div>
<div class="task-contain" style="margin-bottom: 0;border-bottom: none;">
    <h3 class="task-title bottom-title">{pigcms{:L_("1. 开启后台运行")}</h3>
    <ul class="task-content">
        <li style="margin-bottom: 0.3rem;margin-top: 0.1rem">{pigcms{:L_("i管家-省电管理-后台高耗电-找到某某骑手-打开开关")}</li>
        <li class="imgsrc">
            <img src="{pigcms{$static_path}help/img/perSet/vivo/0.png">
            <img src="{pigcms{$static_path}help/img/perSet/vivo/1.png">
            <img src="{pigcms{$static_path}help/img/perSet/vivo/2.png">
        </li>
    </ul>
    <div class="btn" onclick="setBack()">
        <button>{pigcms{:L_("去设置")}</button>
    </div>
    <h3 class="task-title bottom-title">{pigcms{:L_("2. 允许自启动")}</h3>
    <ul class="task-content">
        <li style="margin-bottom: 0.3rem;margin-top: 0.1rem">{pigcms{:L_("i管家-软件管理-选择自启动管理-找到某某骑手-允许某某骑手自启动")}</li>
        <li class="imgsrc clearfix">
            <img src="{pigcms{$static_path}help/img/perSet/vivo/3.png">
        </li>
    </ul>
    <!-- toDo 暂时隐藏 -->
<!--    <div class="btn" onclick="setBtn()">-->
<!--        <button>去设置</button>-->
<!--    </div>-->
    <h3 class="task-title bottom-title">{pigcms{:L_("3. 开启多任务锁")}</h3>
    <ul class="task-content">
        <li style="margin-bottom: 0.3rem;margin-top: 0.1rem">{pigcms{:L_("从屏幕底部边缘上滑-进入多任务界面-找到某某骑手-长按-点击“加入白名单”锁定")}</li>
        <li class="imgsrc clearfix">
            <img src="{pigcms{$static_path}help/img/perSet/vivo/4.png">
        </li>
    </ul>
</div>
</body>
<script type="application/javascript">
    function setBack() {
        window.pigcmspackapp.setBack();
    }
    function openSet() {
        window.pigcmspackapp.openSet();
    }
</script>
</html>
