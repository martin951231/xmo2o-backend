<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{pigcms{:L_("附属商品列表")}</title>
    <link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
    <link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
   <!-- <link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">-->
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script type="text/javascript">
        $(function () {
            //设置高度
            $('.js-modal iframe', parent.document).height($('body').height() + 16);

            //关闭
            $('.modal-header .close').live("click", function () {
                var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                parent.layer.close(index); //再执行关闭
            });

            //分页
            $('.js-page-list a').live('click', function (e) {
                if (!$(this).hasClass('active')) {
                    var input_val = $('.js-modal-search-input').val();
                    var page = $(this).data('page-num');
                    search(input_val, page);
                }
            });

            //搜索
            $('.js-modal-search').live('click', function (e) {
                var input_val = $('.js-modal-search-input').val();
                search(input_val);
                return false;
            });

            //回车提交搜索
            $(window).keydown(function (event) {
                if (event.keyCode == 13 && $('.js-modal-search-input').is(':focus')) {
                    var input_val = $('.js-modal-search-input').val();
                    search(input_val);
                    return false;
                }
            });
        });

        /*搜索*/
        function search(val, page) {
            page = page || 1;
            $('body').html('<div class="loading-more"><span></span></div>');
            location.href  = '{pigcms{:U("Mall/get_time_limited_discount_goods",$_GET)}&keyword=' + val + '&p=' + page;
        }

        //获取iframe嵌套的页面的url参数,第一个参数为需要获取的参数名,第二个参数为iframe的id.
        function GetIframeQueryString(name, id) {
            var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
            var r = window.parent.document.getElementById(id).contentWindow.location.search.substr(1).match(reg);
            if (r != null) {
                return decodeURI(r[2]);
            }
            return null;
        }

        $('button.js-choose').live('click', function () {
              var topIframe = window.top.getTopIframe()
            // var index = parent.layer.getFrameIndex(window.name);
            // var savegood = GetIframeQueryString("savegood", "layui-layer-iframe" + index)
            topIframe.login_box_after($(this).data('name'), $(this).data('price'), $(this).data('spec_str'), 0, 0, $(this).data('goods_id'), $(this).data('stock_num'),$(this).data('image'));
            window.top.closeiframe()
        });
    </script>
    <style type="text/css">
        .table th,.table td {
            vertical-align: middle;
        }
    </style>
</head>
<body style="background-color:#ffffff;">
<div class="modal-header">
    <a class="close js-news-modal-dismiss">×</a>
    <!-- 顶部tab -->
    <ul class="module-nav modal-tab">
        <li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("可选附属商品列表")}</a></li>
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
                <thead>
                <tr>
                    <th class="title" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>{pigcms{:L_("商品名称")}</span>
                            <a class="js-update" href="javascript:window.location.reload();">{pigcms{:L_("刷新")}</a>
                        </div>
                    </th>
                    <th class="time" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>{pigcms{:L_("售价")}</span>
                        </div>
                    </th>
                    <th class="time" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>{pigcms{:L_("库存")}</span>
                        </div>
                    </th>
                    <th class="time" style="background-color:#f5f5f5;text-align: right;">
                        <div class="td-cont">
                            <span>{pigcms{:L_("是否是多规格商品")}</span>
                        </div>
                    </th>
                    <th class="opts" style="background-color:#f5f5f5;">
                        <div class="td-cont" style="padding:7px 0 3px 10px;">
                            <form class="form-search" onsubmit="return false;">
                                <div class="input-append">
                                    <input class="input-small js-modal-search-input" type="text" style="border-radius:4px 0px 0px 4px;text-indent:5px;" value="{pigcms{$_GET.keyword}"/>
                                    <a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" style="border-radius:0 4px 4px 0;margin-left:0px;">{pigcms{:L_("搜")}</a>
                                </div>
                            </form>
                        </div>
                    </th>
                </tr>
                </thead>
                <!-- 表格数据区 -->
                <tbody>
                <volist name="goods_list" id="vo">
                    <tr>
                        <td class="title" style="max-width:300px;">
                            <div class="td-cont">
                                <a target="_blank" class="new_window" href="javascript:">{pigcms{$vo.name}</a>
                            </div>
                        </td>
                        <td class="time">
                            <div class="td-cont">
                                <span>{pigcms{$vo.price}</span>
                            </div>
                        </td>
                        <td class="time">
                            <div class="td-cont">
                                <span>{pigcms{$vo.stock_num}</span>
                            </div>
                        </td>
                        <td class="time" style="max-width:300px;text-align: right;">
                            <div class="td-cont">
                                <if condition="$vo.spec_value == ''">
                                    <span>{pigcms{:L_("否")}</span>
                                <else/>
                                    <span>{pigcms{:L_("是")}</span>
                                </if>
                            </div>
                        </td>
                        <td class="title" style="max-width:300px;text-align: right;">

                                <if condition="$vo.is_clik eq 0">
                                    <div class="td-cont">
                                    <button style="background: #a09da2" class="btn disable" data-spec_str="{pigcms{$vo.spec_json_str}" data-name="{pigcms{$vo.name}" data-goods_id="{pigcms{$vo.goods_id}" data-price="{pigcms{$vo.price}" data-stock_num="{pigcms{$vo.stock_num}" data-image="{pigcms{$vo.image}">{pigcms{$vo.tip}</button>
                                    </div>
                                    <else/>
                                    <div class="td-cont">
                                    <button style="background: #009ADB" class="btn js-choose" data-spec_str="{pigcms{$vo.spec_json_str}" data-name="{pigcms{$vo.name}" data-goods_id="{pigcms{$vo.goods_id}" data-price="{pigcms{$vo.price}" data-stock_num="{pigcms{$vo.stock_num}" data-image="{pigcms{$vo.image}">{pigcms{:L_("选取")}</button>
                                    </div>
                                </if>

                        </td>
                    </tr>
                </volist>
                </tbody>
            </table>
            {pigcms{$page_bar}
        </div>
    </div>
</div>
</body>
</html>