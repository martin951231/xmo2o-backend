<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('HkcPile/order_list')}">智能充电桩(好快充)</a>
            </li>
            <li class="active">订单管理</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('HkcPile/order_list')}" method="get" id="myorderform" >
                    <input type="hidden" name="c" value="HkcPile"/>
                    <input type="hidden" name="a" value="order_list"/>
                    <div class="form_div" style="display: inline-block;">
                    <select name="find_type" id="find_type" class="col-sm-1 input" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>姓名</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>手机号</option>
                        <option value="3" <if condition="$_GET['find_type'] eq 3">selected="selected"</if>>消费订单编号</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2 input" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    支付方式：
                    <select name="pay_type" class="input" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['pay_type']) || $_GET['pay_type'] heq ''">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['pay_type'] eq 1">selected="selected"</if>>余额支付</option>
                        <option value="2" <if condition="$_GET['pay_type'] eq 2">selected="selected"</if>>微信支付</option>
                        <option value="3" <if condition="$_GET['pay_type'] eq 3">selected="selected"</if>>月卡支付</option>
                    </select>
                    订单类型：
                    <select name="order_type" class="input" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['pay_type']) || $_GET['order_type'] heq ''">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['order_type'] eq 1">selected="selected"</if>>消费订单</option>
                        <option value="2" <if condition="$_GET['order_type'] eq 2">selected="selected"</if>>月卡购买</option>
                    </select>
                    时间筛选：
                    <input type="text" name="begin_time" class="input-text input" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>&nbsp;&nbsp;-&nbsp;&nbsp;
                    <input type="text" name="end_time" class="input-text input" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;
                    </div>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('HkcPile/order_list')}'">重置</a>

                   <a onclick="order_export();" class="btn btn-success">EXCEL导出</a>
                    
                </form>

            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">消费订单编号</th>
                                <th width="10%">设备名称</th>
                                <th width="8%">订单类型</th>
                                <th width="5%">消费时长（分）</th>
                                <th width="10%">状态（结束原因）</th>
                                <th width="5%">消费金额（元）</th>
                                <th width="5%">退款金额（元）</th>
                                <th width="5%">消费电量（度）</th>
                                <th width="5%">姓名</th>
                                <th width="5%">联系方式</th>
                                <th width="5%">支付方式</th>
                                <th width="10%">支付时间</th>
                                <th class="button-column">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$order_list">
                                <volist name="order_list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.order_no}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.order_type_str}</div></td>
                            <td><div class="tagDiv"><if condition="$vo.order_type eq 2">---<else />{pigcms{$vo.continued_time}</if></div></td>
                            <td><div class="tagDiv"><if condition="$vo.order_type eq 2" >---<elseif condition="$vo.status eq 1" />已结束<br>{pigcms{$vo.end_result}<else />未结束</if></div></td>
                            <td><div class="tagDiv">{pigcms{$vo.use_money}</div></td>

                            <td><div class="tagDiv"><if condition="$vo.is_refund eq 3">{pigcms{$vo.refund_money}<else /> </if></div></td>

                            <td><div class="tagDiv"><if condition="$vo.order_type eq 2">---<else />{pigcms{$vo.use_ele}</if></div></td>
                            <td><div class="tagDiv">{pigcms{$vo.nickname}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.pay_type}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.pay_time_txt}</div></td>
                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a title="详情" onclick="get_order_info('{pigcms{$vo.id}')">详情</a>
                                    <if condition="$vo['is_refund'] neq 3">
                                        &nbsp;
                                        <a title="退款" onclick="refund_order('{pigcms{$vo.id}')">退款</a>
                                    </if>
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有订单。</td></tr>
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
    function get_order_info(id) {
        var url = "{pigcms{:U('HkcPile/order_info')}";
        var href = url+'&order_id='+id;
        layer.open({
            type: 2,
            title:'订单详情',
            content: href,
            area: ['760px', '420px'],
            maxmin: false,

        });
    }
    function refund_order(id){
        var msg='确定要给当前订单操作退款么？';
        if (confirm(msg)==true){
            $.post("{pigcms{:U('HkcPile/refund')}", {'order_id':id,'status':3,'refund_from':'order_list'}, function(data) {
                if(data.status == 1){
                    alert(data.msg);
                    window.location.reload();
                }else{
                    alert(data.msg);
                    window.location.reload();
                }
            },'JSON');
        }
    }

   function order_export(){
         var inputData=new Array();
         $('#myorderform .form_div .input').each(function(ii){
            var tmpV=$(this).val()||'';
            var tmpN=$(this).prop('name')||'';
                inputData.push(tmpN+'='+tmpV);
         });
         var inputDataStr=inputData.join('&');
         console.log(inputDataStr);
         window.location.href='{pigcms{:U('HkcPile/order_export')}&'+inputDataStr;
   }
</script>
<include file="Public:footer"/>
