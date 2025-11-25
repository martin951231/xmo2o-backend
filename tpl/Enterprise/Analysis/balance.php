<include file="Public:header"/>


<div class="main-content">

    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <i class="ace-icon fa fa-comments-o comments-o-icon"></i>
            <li class="active"><a href="{pigcms{:U('Analysis/trade')}" class="on">{pigcms{:L_("交易信息")}</a></li>
        </ul>
    </div>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="form-group" style="margin-bottom:0px">
                    <form action="{pigcms{:U('Analysis/balance')}" method="get" id="myform1">
                        <input type="hidden" name="c" value="Analysis"/>
                        <input type="hidden" name="a" value="balance"/>
                        <span class="fl">{pigcms{:L_("日期筛选：")}</span>
                        <input type="text" class="input-text fl" name="begin_time" style="width:120px;" id="d4311"
                               value="{pigcms{$_GET.begin_time}"
                               onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                        <input type="text" class="input-text fl" name="end_time" style="width:120px;" id="d4311"
                               value="{pigcms{$_GET.end_time}"
                               onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                        <div class="fl time-choice-wrap cf stat-time-choice" style="float: left;">
                            <span class="time-btn time-choice  <if condition=" $_GET['selectTimeType'] eq
                            1">time-current</if>" timetype="1" id="choice-today">
                            {pigcms{:L_("今天")}</span>
                            <span class="time-btn time-choice bor-gray <if condition=" $_GET['selectTimeType'] eq
                            7">time-current</if>" id="choice-week" timetype="7" >
                            {pigcms{:L_("近7天")}</span>
                            <span class="time-btn time-choice <if condition=" $_GET['selectTimeType'] eq
                            30">time-current</if>" id="choice-month" timetype="30" >
                            {pigcms{:L_("近30天")}</span>
                            <input type="hidden" id="_selectTimeType" name="selectTimeType"
                                   value="{pigcms{$_GET['selectTimeType']}">
                        </div>

                        <span class="fl">{pigcms{:L_("审核状态：")}</span>
                        <select class="fl" id="status" name="status">
                            <option value="-1">全部</option>
                            <volist name="handleStatus" id="vo">
                                <option value="{pigcms{$key}"
                                <if condition="isset($_GET['type']) AND ($key eq $_GET['type'])">selected="selected"</if>
                                >{pigcms{$vo}</option>
                            </volist>
                        </select>

                        <input type="submit" value="{pigcms{:L_(" 查询")}" class="button"/>　　
                        <input type="reset" id="reset" value="{pigcms{:L_(" 清空")}" class="button"/>

                        <input type="button" onclick="export_excel()" value="{pigcms{:L_(" 导出订单")}" class="button" style="float:right;margin-right: 10px;"/>
                    </form>
                </div>
                <script type="text/javascript">
                    function export_excel() {
                        var status = $('select[name=status] option:selected').val();
                        var begin_time = $('input[name=begin_time]').val();
                        var end_time = $('input[name=end_time]').val();
                        var selectTimeType = $('input[name=selectTimeType]').val();
                        var url = "{pigcms{:U('Analysis/exportBalance')}"
                        url += '&status=' + status;
                        url += '&selectTimeType=' + selectTimeType;
                        url += '&begin_time=' + begin_time;
                        url += '&end_time=' + end_time;
                        window.open(url);
                    }
                </script>
                <div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">

                            <thead>
                            <tr>
                                <th>{pigcms{:L_("操作时间")}</th>
                                <th>{pigcms{:L_("卡号/工号/ID")}</th>
                                <th>{pigcms{:L_("组别名称")}</th>
                                <th>{pigcms{:L_("员工名称")}</th>
                                <th>{pigcms{:L_("操作类型")}</th>
                                <th>{pigcms{:L_("变动金额")}</th>
                                <th>{pigcms{:L_("审核状态")}</th>
                                <th>{pigcms{:L_("操作后余额")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="is_array($lists)">
                                <volist name="lists" id="vo">
                                    <tr>
                                        <td>{pigcms{$vo.handle_time|date='Y-m-d H:i:s',###}</td>
                                        <td>{pigcms{$vo.job_number}</td>
                                        <td>{pigcms{$vo.group_name|default=''}</td>
                                        <td>{pigcms{$vo.staff_name}</td>
                                        <td><if condition="$vo['operate'] EQ 1">预存<else/>扣减</if></td>
                                        <td>{pigcms{$vo.money|floatval}</td>
                                        <td>
                                            <if condition="$vo['status'] eq 0">
                                                待审核
                                                <elseif condition="$vo['status'] EQ 1"/>
                                                通过
                                                <else/>
                                                驳回
                                            </if>
                                        </td>
                                        <td>{pigcms{$vo.after_money|floatval}</td>
                                    </tr>
                                </volist>
                                <tr>
                                    <td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td>
                                </tr>
                                <else/>
                                <tr>
                                    <td class="textcenter red" colspan="9">{pigcms{:L_("列表为空！")}</td>
                                </tr>
                            </if>
                            </tbody>

                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .statist-wrap {
        width: 15%;
        margin-right: 2.3%;
        padding: 0 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 15px
    }

    .sta-mon {
        font-size: 25px;
        color: #333;
        width: 100%;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1
    }

    .sta-num {
        font-size: 18px;
        color: #999;
        width: 100%;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis
    }

    .sta-total {
        background: #1c84c6;
        padding: 2px 7px;
        color: #fff
    }

    .sta-trade {
        background: #23c6c8;
        padding: 2px 7px;
        color: #fff
    }

    .sta-refund {
        background: #ed5565;
        padding: 2px 7px;
        color: #fff
    }

    .time-choice-wrap {
        margin: 0 10px;
        border-radius: 3px;
        border: 1px solid #ccc
    }

    .time-choice {
        float: left;
        display: block;
        padding: 0 10px;
        line-height: 29px;
        font-size: 12px;
        color: #333;
        outline: 0;
        cursor: pointer
    }

    .time-choice.time-current {
        color: #fff;
        background: #1ab394
    }

    .bor-gray {
        border-left: 1px solid #ccc;
        border-right: 1px solid #ccc
    }

    .fl {
        margin-right: 5px;
        margin-bottom: 5px;
    }

    .plat_form {
        display: none;
    }
</style>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    $(function () {
        if ($('#type').val() == 'shop') {
            $('.plat_form').show()
        }
        $('#type').change(function () {
            console.log($(this).val())
            if ($(this).val() == 'shop') {
                $('.plat_form').show()
            } else {
                $('.plat_form').hide()
            }
        })

        $('#reset').click(function () {
            window.location.href = "{pigcms{:U(balance)}";
        })

        $('#layer-tip').on('mouseover', function () {
            console.log(111)
            //$('.com-layer-text').show();
            layer.tips($('.com-layer-text').html(), '#layer-tip', {
                tips: 1
            });
        });
        $('#layer-tip').on('mouseout', function () {
            layer.closeAll()
        });

        $('.time-btn').click(function () {
            $('.time-btn').removeClass('time-current')
            $(this).addClass('time-current');
            $('input[name="begin_time"]').val('')
            $('input[name="end_time"]').val('')
            $('#_selectTimeType').val($(this).attr('timetype'))
            $('#myform1').submit();
        })
        $('input:radio').click(function () {
            var obj = $(this);
            if ($(obj).attr('name') == 'staff_list') {
                var title = '{pigcms{:L_("选择店员")}'
            } else {
                var title = '{pigcms{:L_("选择店铺")}'
            }
            var type = $(obj).data('type')


            if (type == 'part_staff' || type == 'part_store') {
                if (type == 'part_store') {
                    window.top.art.dialog.data('store_list', $(obj).val());
                    var href = $(obj).attr('href') + '&type' + type + '&store_list=' + $(obj).val();
                    $('#edit_store').show();
                } else {
                    window.top.art.dialog.data('staff_list', $(obj).val());
                    var href = $(obj).attr('href') + '&type' + type + '&staff_list=' + $(obj).val() + '&store_list=' + $('#part_store').val();
                    $('#edit_staff').show();
                }

                window.top.art.dialog.open(href, {
                    init: function () {
                        var iframe = this.iframe.contentWindow;
                        window.top.art.dialog.data('iframe_handle', iframe);
                    },
                    id: type,
                    title: title,
                    padding: 0,
                    width: 600,
                    height: 500,
                    lock: true,
                    resize: false,
                    background: 'black',
                    button: null,
                    fixed: false,
                    close: null,
                    left: '50%',
                    top: '38.2%',
                    opacity: '0.4',
                    close: function () {
                        if (type == 'part_staff') {
                            var staff_list = window.top.art.dialog.data('staff_list');
                            $(obj).val(staff_list)
                        } else {
                            var store_list = window.top.art.dialog.data('store_list');
                            $(obj).val(store_list)
                            $('#part_staff').val(0)
                            $("input[name='staff_list']:eq(0)").attr("checked", 'checked');
                            $("input[name='staff_list']:eq(1)").removeAttr("checked");
                            $('#edit_staff').hide();
                        }
                    }
                });

            } else {
                if (type == 'all_store') {
                    $('#edit_store').hide();
                } else {
                    $('#edit_staff').hide();
                }
            }

        })

        $('#edit_store').click(function () {
            var obj = $('#part_store');
            var title = '{pigcms{:L_("选择店铺")}'
            var type = $(obj).data('type')
            window.top.art.dialog.data('store_list', $(obj).val());
            var href = $(obj).attr('href') + '&type' + type

            window.top.art.dialog.open(href, {
                init: function () {
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle', iframe);
                },
                id: type,
                title: title,
                padding: 0,
                width: 600,
                height: 500,
                lock: true,
                resize: false,
                background: 'black',
                button: null,
                fixed: false,
                close: null,
                left: '50%',
                top: '38.2%',
                opacity: '0.4',
                close: function () {
                    var store_list = window.top.art.dialog.data('store_list');
                    $(obj).val(store_list)
                    $('#part_staff').val(0)

                    $("input[name='staff_list']:eq(0)").attr("checked", 'checked');
                    $("input[name='staff_list']:eq(1)").removeAttr("checked");
                    $('#edit_staff').hide();
                }
            });
        })


        $('#edit_staff').click(function () {
            var obj = $('#part_staff');
            var title = '{pigcms{:L_("选择店员")}'
            var type = $(obj).data('type')
            window.top.art.dialog.data('staff_list', $(obj).val());
            var href = $(obj).attr('href') + '&type' + type + '&staff_list=' + $(obj).val() + '&store_list=' + $('#part_store').val();

            window.top.art.dialog.open(href, {
                init: function () {
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle', iframe);
                },
                id: type,
                title: title,
                padding: 0,
                width: 600,
                height: 500,
                lock: true,
                resize: false,
                background: 'black',
                button: null,
                fixed: false,
                close: null,
                left: '50%',
                top: '38.2%',
                opacity: '0.4',
                close: function () {
                    var staff_list = window.top.art.dialog.data('staff_list');

                    $(obj).val(staff_list)

                }
            });
        })
    });


    // $('#merchant_worker_id').change(function(){
    // var merchant_worker_id = $(this).val();
    // var Url="{pigcms{:U('Appoint/order_list')}"+'&merchant_worker_id='+merchant_worker_id;
    // location.href=Url;
    // });


    var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Analysis/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"></script>
<include file="Public:footer"/>
