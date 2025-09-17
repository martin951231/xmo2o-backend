<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{pigcms{:L_("操作记录")}</title>
    <link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
    <link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <style type="text/css">
        .table th,.table td {
            vertical-align: middle;
        }
    </style>
    <script type="text/javascript">
        //关闭
        $('.modal-header .close').live("click", function () {
            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
            parent.layer.close(index); //再执行关闭
        });
    </script>
</head>
<body style="background-color:#ffffff;">
<div class="modal-header">
    <a class="close js-news-modal-dismiss">×</a>
    <!-- 顶部tab -->
    <ul class="module-nav modal-tab">
        <li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("操作记录")}</a></li>
    </ul>
</div>
<div class="modal-body">
    <div class="tab-content">
        <div id="js-module-feature" class="tab-pane module-feature active">
            <table class="table">
                <colgroup>
                    <col class="modal-col-title">
                    <col class="modal-col-time" span="2">
                    <col class="modal-col-action">
                </colgroup>
                <!-- 表格头部 -->
                <div id="shopList" class="grid-view">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th id="shopList_c1" width="50">{pigcms{:L_("操作内容")}</th>
                            <th id="shopList_c1" width="50">{pigcms{:L_("备注")}</th>

                        </tr>
                        </thead>
                        <tbody>
                        <volist name="parse_logs" id="l">
                            <tr>
                                <td>{pigcms{$l.action}<br/>{pigcms{$l.time}</td>
                                <td>{pigcms{$l.remark}</td>
                            </tr>
                        </volist>
                        </tbody>
                    </table>
                </div>
            </table>
        </div>
    </div>
</div>
</body>
</html>