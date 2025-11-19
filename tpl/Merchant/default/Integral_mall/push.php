<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Integral_mall/push')}">{pigcms{:L_("发货")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post">
						<input type="hidden" value="{pigcms{$now_goods.goods_id}" id="goods_id" />
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("商品名称")}</label>
                                    <label class="col-sm-9">{pigcms{$order_info.goods_name}</label>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("总计积分")}</label>
                                    <label class="col-sm-9">{pigcms{$order_info.total_integral}</label>
                                </div>
                                <if condition="$order_info['is_pick_in_store'] eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("运费")}</label>
                                        <label class="col-sm-9">
                                            {pigcms{$config['Currency_symbol']}{pigcms{$order_info.freight_charge|floatval}
                                            <span style="font-size: 12px;color: red;"><php>if ($order_info['freight_charges'] == 1 && $order_info['freight_charge'] > 0) {echo '(商户承担)';}</php></span>
                                        </label>
                                    </div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("商品图片")}</label>
                                    <label class="col-sm-9">
                                        <img src="{pigcms{$order_info.pic_image}" width="100" height="100" class="view_img">
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("订单状态")}</label>
                                    <label class="col-sm-9">
                                        {pigcms{$order_info.show_status}
                                    </label>
                                </div>
                                <if condition="$order_info['spec_value'] && $order_info['spec_value']['spec_list'] && $choose_spe">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("规格信息")}</label>
                                        <label class="col-sm-9">
                                            <div class="topic_box">
                                                <table class="table table-striped table-bordered table-hover" id="table_list">
                                                    <tbody>
                                                    <tr>
                                                        <volist name="order_info['spec_value']['spec_list']" id="gs">
                                                            <th>{pigcms{$gs['name']}</th>
                                                        </volist>
                                                        <th>{pigcms{:L_("积分")}</th>
                                                        <th>{pigcms{:L_("兑换数量")}</th>
                                                    </tr>

                                                    <tr>
                                                        <volist name="choose_spe['spec']" id="g">
                                                            <td>{pigcms{$g['spec_val_name']}</td>
                                                        </volist>
                                                        <td>{pigcms{$choose_spe['payment_pure_integral']|floatval}</td>
                                                        <td>{pigcms{$choose_spe['num']}</td>

                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </label>
                                    </div>
                                    <else/>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="wholesale_price">{pigcms{:L_("积分")}</label></label>
                                        <label class="col-sm-9">{pigcms{$order_info['payment_pure_integral']}</label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="wholesale_price">{pigcms{:L_("兑换数量")}</label></label>
                                        <label class="col-sm-9">{pigcms{$order_info['num']}</label>
                                    </div>
								</if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("收货人姓名")}</label>
									<label class="col-sm-1">{pigcms{$order_info.username}</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("联系电话")}</label>
									<label class="col-sm-1">{pigcms{$order_info.userphone}</label>
								</div>
								<div class="form-group">
                                    <if condition="$order_info['is_pick_in_store'] eq 1">
                                        <label class="col-sm-1">{pigcms{:L_("收货地址")}</label>
                                        <label class="col-sm-3">{pigcms{$order_info.address}</label>
                                        <else/>
                                        <label class="col-sm-1">{pigcms{:L_("自提地址")}</label>
                                        <label class="col-sm-3">{pigcms{$order_info.address}</label>
                                    </if>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("用户备注")}</label>
									<label class="col-sm-3">{pigcms{$order_info.desc}</label>
								</div>

                                <if condition="$order_info['is_pick_in_store'] eq 1">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("快递公司")}</label>
                                        <select name="express_id">
                                            <volist name="express_list" id="vo">
                                            <option value="{pigcms{$vo.id}" <if condition="$order_info['express_id'] eq $vo['id']">selected</if>>{pigcms{$vo.name}</option>
                                            </volist>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("快递单号")}</label>
                                        <input class="col-sm-3" name="express_number" type="text" value="{pigcms{$order_info.express_number}"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("发货备注说明")}</label>
                                        <textarea class="col-sm-3" rows="5" cols="10" name="send_note">{pigcms{$order_info.send_note}</textarea>
                                    </div>
                                </if>
							</div>
                            <if condition="$order_info['is_pick_in_store'] eq 1">
                                <div class="clearfix form-actions">
                                    <div class="col-md-offset-3 col-md-9">
                                        <button class="btn btn-info" type="submit">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            {pigcms{:L_("发货")}
                                        </button>
                                    </div>
                                </div>
                            </if>
						</div>
					</form>
                    <if condition="$order_info['is_pick_in_store'] eq 0">
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9">
                                <button class="btn btn-info self_mention" data-url="{pigcms{:U('Integral_mall/self_mention', array('order_id' => $order_info['order_id']))}">
                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                    {pigcms{:L_("确定用户自提")}
                                </button>
                            </div>
                        </div>
                    </if>
				</div>
			</div>
		</div>
	</div>
</div>
<script>

    $(function(){
        $('.view_img').click(function(){
            var obj = $(this);
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                skin: 'layui-layer-nobg', //"没有背景色")}
                shadeClose: true,
                content: '<img src="' + obj.attr('src') + '" style="height:100%;width:100%"/>'
            });
        });

        $('.self_mention').click(function(){
            var postUrl = $(this).data('url');
            layer.open({
                content: '您确定客户已经取物了？'
                ,btn: ['确定', '取消']
                ,yes: function(index){
                    $.post(postUrl, function(res) {
                        layer.close(index);
                        if (res.errcode == 1) {
                            layer.open({
                                content: res.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                            });
                        } else {
                            var url = "{pigcms{:U('Integral_mall/sell_order')}";
                            window.location.href = url;
                            layer.close(index);
                        }
                    }, 'json');
                }
            });
        });

    });
</script>
<style>
input.ke-input-text {
	background-color: #FFFFFF;
	background-color: #FFFFFF!important;
	font-family: "sans serif",tahoma,verdana,helvetica;
	font-size: 12px;
	line-height: 24px;
	height: 24px;
	padding: 2px 4px;
	border-color: #848484 #E0E0E0 #E0E0E0 #848484;
	border-style: solid;
	border-width: 1px;
	display: -moz-inline-stack;
	display: inline-block;
	vertical-align: middle;
	zoom: 1;
}
.form-group>label{font-size:12px;line-height:24px;}
#upload_pic_box{margin-top:20px;height:150px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}
.webuploader-element-invisible {
    position: absolute !important;
    clip: rect(1px 1px 1px 1px);
    clip: rect(1px,1px,1px,1px);
}
.webuploader-pick-hover .btn{
	background-color: #629b58!important;
    border-color: #87b87f;
}
</style>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<include file="Public:footer"/>
