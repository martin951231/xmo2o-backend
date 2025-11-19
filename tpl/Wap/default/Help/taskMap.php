<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{pigcms{:L_("任务地图使用说明")}</title>
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
            background-color: #eeeeee;
        }
        .task-contain{
            background-color: #fff;
            height: 3.5rem;
            font-size: 0.3rem;
            margin: 0.2rem 0;
            padding-left: 0.4rem;
        }
        .task-contain .task-title{
            font-weight: normal;
            font-size: 0.34rem;
            line-height: 1rem;
            border-bottom: 0.05rem solid #f6f6f6;

        }
        .task-contain .task-content{
            padding-top: 0.2rem;
            line-height: 0.55rem;
            width: 6.5rem;
        }
        .task-contain .task-content{
            line-height: 0.5rem;
        }
        .task-contain .task-content .imgsrc{
            margin: 0.3rem 0;
        }
        .task-contain .task-content .imgsrc img{
            width: 100%;
        }

    </style>
</head>
<body>
    <div class="task-contain">
        <h3 class="task-title">{pigcms{:L_("任务地图有什么用？")}</h3>
        <ul class="task-content">
            <li>{pigcms{:L_("1.系统帮您规划已接订单的送单路线，按照此顺序送单是最高效的。")}</li>
            <li>{pigcms{:L_("2.跑腿订单也会在任务地图中")}</li>
        </ul>
    </div>
    <div class="task-contain" style="height: 18rem;margin-bottom: 0">
        <h3 class="task-title">{pigcms{:L_("任务地图基本信息")}</h3>
        <ul class="task-content">
            <li>{pigcms{:L_("1.黄色图标是取货点，绿色的图标是送货点")}</li>
            <li>{pigcms{:L_("2.地图上的数字代表送单顺序")}</li>
            <li>{pigcms{:L_("3.地图上的连线仅作为顺序指引，不是真实行驶路线")}</li>
            <li class="imgsrc">
                <img src="{pigcms{$static_path}help/img/taskmap/tapd1.png">
            </li>
            <li>{pigcms{:L_("4.点击地图上某个点可以查看该点的详细信息")}</li>
            <li class="imgsrc">
                <img src="{pigcms{$static_path}help/img/taskmap/tapd2.png">
            </li>
            <li>{pigcms{:L_("5.点击或上滑这个列表可以全局查看路线规划")}</li>
            <li class="imgsrc">
                <img src="{pigcms{$static_path}help/img/taskmap/tapd3.png">
            </li>
        </ul>
    </div>
</body>
</html>
