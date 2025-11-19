<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('order_list')}">订单管理</a>
            </li>
            <li class="active">订单列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Pile/order_list')}" method="get" >
                    <input type="hidden" name="c" value="Pile"/>
                    <input type="hidden" name="a" value="order_list"/>
                    <select name="find_type" id="find_type" class="col-sm-1" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>姓名</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>手机号</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    支付方式：
                    <select name="pay_type" class="" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['pay_type']) || $_GET['pay_type'] heq ''">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['pay_type'] eq 1">selected="selected"</if>>余额支付</option>
                        <option value="2" <if condition="$_GET['pay_type'] eq 2">selected="selected"</if>>微信支付</option>
                        <option value="3" <if condition="$_GET['pay_type'] eq 3">selected="selected"</if>>月卡支付</option>
                    </select>
                    订单类型：
                    <select name="order_type" class="" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['pay_type']) || $_GET['order_type'] heq ''">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['order_type'] eq 1">selected="selected"</if>>消费订单</option>
                        <option value="2" <if condition="$_GET['order_type'] eq 2">selected="selected"</if>>月卡购买</option>
                    </select>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Pile/order_list')}'">重置</a>
                    <if condition="in_array(38,$house_session['menus'])">
                        <a onclick="location.href='{pigcms{:U('order_export',$_GET)}'" class="btn btn-success">EXCEL导出</a>
                    </if>
                </form>
                <!-- <button class="btn btn-success fr" onclick="importAdd()">导入数据</button> -->
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">消费订单编号</th>
                                <th width="10%">设备名称</th>
                                <th width="5%">订单类型</th>
                                <th width="8%">消费时长（分）</th>
                                <th width="10%">状态（结束原因）</th>
                                <th width="5%">消费金额（元）</th>
                                <th width="5%">消费电量（度）</th>
                                <th width="5%">结束时功率（瓦）</th>
                                <th width="5%">业主姓名</th>
                                <th width="5%">联系方式</th>
                                <th width="12%">支付方式</th>
                                <th width="15%">支付时间</th>
                                <th class="button-column" width="18%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$order_list">
                                <volist name="order_list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.order_no}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.order_type}</div></td>
                            <td><div class="tagDiv"><if condition="$vo.order_type eq 月卡购买">---<else />{pigcms{$vo.continued_time}</if></div></td>
                            <td><div class="tagDiv"><if condition="$vo.order_type eq 月卡购买" >---<elseif condition="$vo.status eq 1" />已结束<else />未结束</if></div></td>
                            <td><div class="tagDiv">{pigcms{$vo.use_money}</div></td>
                            <td><div class="tagDiv"><if condition="$vo.order_type eq 月卡购买">---<else />{pigcms{$vo.use_ele}</if></div></td>
                            <td><div class="tagDiv"><if condition="$vo.order_type eq 月卡购买">---<else />{pigcms{$vo.end_power}</if></div></td>
                            <td><div class="tagDiv">{pigcms{$vo.nickname}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.pay_type}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.pay_time_txt}</div></td>
                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a title="详情" onclick="get_order_info('{pigcms{$vo.id}')">详情</a>
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
        var url = "{pigcms{:U('Pile/order_info')}";
        var href = url+'&order_id='+id;
        layer.open({
            type: 2,
            title:'订单详情',
            content: href,
            area: ['600px', '400px'],
            maxmin: false,

        });
    }
</script>
<include file="Public:footer"/>
