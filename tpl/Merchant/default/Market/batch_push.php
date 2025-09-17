<?php if(empty($_GET['not_first'])){ ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{pigcms{:L_("微页面")}</title>
    <link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <style type="text/css">
        .tips{
            text-align: center;margin-bottom: 15px;color: red;
        }
        .table .td-cont {
            line-height: 30px;
        }
        .btn_msg {
            border: none;
            display: inline-block;
            padding: 4px 11px;
            margin-bottom: 0;
            font-size: 12px;
            line-height: 20px;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            border-radius: 2px;
            background: #009adb;
        }
        .btn_qx {
            border: none;
            display: inline-block;
            padding: 4px 11px;
            margin-bottom: 0;
            font-size: 12px;
            line-height: 20px;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            border-radius: 2px;
            background: #999999;
        }
        .btn_fixed {
            position: fixed;
            bottom: 15px;
            right: 20px;
        }
        .col-sm-6 {
            width: 50%;
        }
        .form-group>label {
            font-size: 12px;
            line-height: 24px;
        }
        .col-lg-1, .col-lg-10, .col-lg-11, .col-lg-12, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-md-1, .col-md-10, .col-md-11, .col-md-12, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-sm-1, .col-sm-10, .col-sm-11, .col-sm-12, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-xs-1, .col-xs-10, .col-xs-11, .col-xs-12, .col-xs-2, .col-xs-3, .col-xs-4, .col-xs-5, .col-xs-6, .col-xs-7, .col-xs-8, .col-xs-9 {
            position: relative;
            min-height: 1px;
            padding-left: 12px;
            padding-right: 12px;
        }
        label {
            font-weight: 400;
            display: inline-block;
            margin: 0 5px 0 3px;
            vertical-align: middle;
            cursor: pointer;
        }
    </style>
</head>
<body style="background-color:#ffffff;">
<?php } ?>
	<!-- {pigcms{:L_("内容头部")} -->
<div class="modal-header">
    <a class="close js-news-modal-dismiss">×</a>
    <!-- {pigcms{:L_("顶部")}tab -->
    <ul class="module-nav modal-tab">
        <li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("销售订单")}</a></li>
    </ul>
</div>

<div class="modal-body">
    <div class="tab-content">
        <div id="js-module-feature" class="tab-pane module-feature active">
            <table class="table" style="margin-bottom: 15px;">
                <colgroup>
                    <col class="modal-col-title">
                    <col class="modal-col-time">
                    <col class="modal-col-action">
                </colgroup>
                <!-- {pigcms{:L_("表格头部")} -->
                <thead>
                <tr>
                    <th class="title" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>{pigcms{:L_("选择填写对应信息")}</span>
                        </div>
                    </th>
                </tr>
                </thead>
                <!-- {pigcms{:L_("表格数据区")} -->
                <tbody>
                    <tr style="border: none;">
                        <td>
                            <div class="form-group">
                                <label class="col-sm-1">{pigcms{:L_("快递公司")}: &nbsp;&nbsp;</label>
                                <select name="express_id" id="batch_push_express_id">
                                    <volist name="express_list" id="vo">
                                        <option value="{pigcms{$vo.id}" <if condition="$now_goods['express_id'] eq $vo['id']">selected</if>>{pigcms{$vo.name}</option>
                                    </volist>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr style="border: none;">
                        <td>
                            <div class="form-group">
                                <label class="col-sm-1">{pigcms{:L_("快递单号")}: &nbsp;&nbsp;</label>
                                <input class="col-sm-6" name="express_number" type="text" id="batch_push_express_number" value="{pigcms{$now_goods.express_number}"/>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="form-group">
                                <label class="col-sm-1">{pigcms{:L_("备注说明")}: &nbsp;&nbsp;</label>
                                <textarea class="col-sm-6" rows="5" cols="10" name="sell_note" id="batch_push_sell_note">{pigcms{$now_goods.sell_note}</textarea>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div>

                <input type="hidden" name="order_list" id="order_list_arr" value="{pigcms{$order_id_str}"/>
                <div class="tips capture_merchant_info_tip">{pigcms{:L_("只有选择快递并填写单号才可进行发货，点击发货后，所有选择的商品都会记录该单号信息")}</div>

            </div>
        </div>
    </div>
</div>

<div class="btn_fixed">
    <input type="button" class="btn btn-primary btn_msg" value="{pigcms{:L_("发货")}">
    &nbsp;&nbsp;&nbsp;&nbsp;
    <input type="reset" value="{pigcms{:L_("取消")}" class="button btn_qx" />
</div>

<style type="text/css">
    .tag_box .tag{
        display: inline-block;
        padding: 2px 2px;
        margin: 0px 5px 5px 0;
        border: 1px solid #ccc;
    }
    .tag_box .tag .del{
        display: inline-block;
        padding: 0 4px;
        color: #277fc1;
        font-weight: bold;
        cursor: pointer;
    }
</style>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/layer/layer.js"></script>
<script type="text/javascript">

    $(function(){
        $('.js-modal iframe',parent.document).height($('body').height()+16);
        $('.modal-header .close').live('click',function(){
            var index = parent.layer.getFrameIndex(window.name); //"先得到当前iframe层的索引")}
            parent.layer.close(index); //"再执行关闭")}
        });
        $('.btn_qx').live('click',function(){
            var index = parent.layer.getFrameIndex(window.name); //"先得到当前iframe层的索引")}
            parent.layer.close(index); //"再执行关闭")}
        });

        $('.btn_msg').click(function(){
            if(!$('#batch_push_express_id').val()){
                layer.alert('{pigcms{:L_("请先选择快递公司！")}');
                return false;
            }
            if(!$('#batch_push_express_number').val()){
                layer.alert('{pigcms{:L_("请填写快递单号！")}');
                return false;
            }
            var express_id = $('#batch_push_express_id').val();
            var express_number = $('#batch_push_express_number').val();
            var sell_note = $('#batch_push_sell_note').val();
            var order_list = $('#order_list_arr').val();
            console.log('express_id', express_id);
            console.log('express_number', express_number);
            console.log('sell_note', sell_note);
            console.log('order_list', order_list);

            $.post("{pigcms{:U('Market/batch_push')}&order_list="+order_list,{express_id: express_id, express_number: express_number, sell_note:sell_note},function(result){
                console.log('result', result);
                if(result.status == 1){
                    layer.alert(result.info);
                    sessionStorage.removeItem("sell_order_check_info");
                    setTimeout(function () {
                        parent.window.location.reload();
                    },1000);
                }else{
                    layer.alert(result.info);
                }
            })

        });
    });
</script>
<?php if(empty($_GET['not_first'])){ ?>
</body>
</html>
<?php } ?>
