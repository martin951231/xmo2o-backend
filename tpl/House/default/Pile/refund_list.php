<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('order_list')}">订单管理</a>
            </li>
            <li class="active">退款列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Pile/refund_list')}" method="get" >
                    <input type="hidden" name="c" value="Pile"/>
                    <input type="hidden" name="a" value="refund_list"/>
                    <select name="find_type" id="find_type" class="col-sm-1" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>姓名</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>手机号</option>
                        <option value="3" <if condition="$_GET['find_type'] eq 3">selected="selected"</if>>订单编号</option>
                        <option value="4" <if condition="$_GET['find_type'] eq 4">selected="selected"</if>>订单号</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    退款状态：
                    <select name="is_refund" class="" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['is_refund']) || $_GET['pay_type'] heq ''">selected="selected"</if>>全部</option>
                        <option value="2" <if condition="$_GET['is_refund'] eq 2">selected="selected"</if>>待审核</option>
                        <option value="4" <if condition="$_GET['is_refund'] eq 4">selected="selected"</if>>未通过</option>
                        <option value="3" <if condition="$_GET['is_refund'] eq 3">selected="selected"</if>>已退款</option>
                    </select>
                    时间筛选：
                    <input type="text" name="begin_time" class="input-text" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>-
                    <input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                </form>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">订单编号</th>
                                <th width="8%">订单号</th>
                                <th width="8%">设备名称</th>
                                <th width="8%">消费时长（分）</th>
                                <th width="8%">状态（结束原因）</th>
                                <th width="8%">消费金额（元）</th>
                                <th width="8%">消费电量（度）</th>
                                <th width="7%">业主姓名</th>
                                <th width="7%">联系方式</th>
                                <th width="8%">退款金额（元）</th>
                                <th width="8%">退款时间</th>
                                <th width="10%">退款原因</th>
                                <th width="5%">状态</th>
                                <th class="button-column" width="5%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$order_list">
                                <volist name="order_list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.order_no}</div></td>
                           <td><div class="tagDiv">{pigcms{$vo.order_id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.continued_time}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.end_result}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.use_money}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.use_ele}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.nickname}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.refund_money}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.refund_time_txt}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.refund_reason}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.refund_status}</div></td>
                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a title="查看详情" class="handle_btn" href="{pigcms{:U('Charging/refund_info',array('order_id'=>$vo['id']))}">详情</a>
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有退款订单。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    $(function(){
        $('.handle_btn').live('click',function(){
            art.dialog.open($(this).attr('href'),{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: 'handle',
                title:'查看详情',
                padding: 0,
                width: 820,
                height: 620,
                lock: true,
                resize: false,
                background:'black',
                button: null,
                fixed: false,
                close: null,
                left: '50%',
                top: '38.2%',
                opacity:'0.4'
            });
            return false;
        });

    });
</script>
<include file="Public:footer"/>
