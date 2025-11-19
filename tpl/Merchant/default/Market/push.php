<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Market/market')}">{pigcms{:L_("进销存")}</a>
			</li>
			<li class="active">{pigcms{:L_("支付")}</li>
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
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.name}</label>
								</div>
								<if condition="empty($now_goods['list'])">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品条形码")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.number}</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="wholesale_price">{pigcms{:L_("批发价")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.price}</label>
								</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_goods['pic_arr']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/></li>
											</volist>
										</ul>
									</div>
								</div>
								<if condition="$now_goods['discount_info']">
								<div class="form-group topic_box">
								    <div class="question_box properties">
    								    <p class="question_info">
        								    <span>{pigcms{:L_("批发满：")}</span>
        								    <input type="text" class="txt properties_name" value="{pigcms{$now_goods['discount_info']['num']}" style="width:50px" disabled/>
        								    <span>{pigcms{:L_("件，享受：")}</span>
        								    <input type="text" class="txt properties_num" value="{pigcms{$now_goods['discount_info']['discount']}" style="width:50px" disabled/>{pigcms{:L_("折优惠")}
    								    </p>
								    </div>
								</div>
								</if>
                                <div class="form-group" style="font-weight: bolder;">
                                    <label class="col-sm-1">{pigcms{:L_("配送方式")}</label>
                                    <if condition="$now_goods['is_pick_in_store'] eq 0">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="0" checked='checked'/>&nbsp;&nbsp;{pigcms{:L_("到店自提")}
                                        </label>
                                    </if>
                                    <if condition="$now_goods['is_pick_in_store'] eq 1">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="1" checked='checked'/>&nbsp;&nbsp;{pigcms{:L_("快递配送")}
                                        </label>
                                    </if>
                                    <if condition="$now_goods['is_pick_in_store'] eq 2">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="2" checked='checked'/>&nbsp;&nbsp;{pigcms{:L_("物流配送")}
                                        </label>
                                    </if>
                                </div>

                                <if condition="$now_goods['is_pick_in_store'] eq 0">
                                    <div class="form-group pick_info" style="font-weight: bolder;">
                                        <label class="col-sm-1">{pigcms{:L_("自提点信息")}</label>
                                        <label class="flex" style="float: left;">
                                            <div style="padding: 5px;">
                                                <p>地址： {pigcms{$now_goods['address']}</p>
                                                <p>电话： {pigcms{$now_goods['userphone']}</p>
                                                <p>省市区： {pigcms{$now_goods.province}&nbsp;{pigcms{$now_goods.city}&nbsp;{pigcms{$now_goods.area}</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div style="clear: both;"></div>
                                </if>
								<div class="topic_box">
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
									<tbody>
										<tr>
											<th>{pigcms{:L_("商品条形码")}</th>
											<volist name="now_goods['spec_list']" id="gs">
											<th>{pigcms{$gs['name']}</th>
											</volist>
											<th>{pigcms{:L_("批发价")}</th>
											<th>{pigcms{:L_("本次批发数")}</th>
											<th>{pigcms{:L_("总价")}</th>
										</tr>
										
										<volist name="now_goods['list']" id="gl" key="id_index" >
											<tr id="{pigcms{$gl['index']}">
												<td>{pigcms{$gl['number']}</td>
												<volist name="gl['spec']" id="g">
												<td>{pigcms{$g['spec_val_name']}</td>
												</volist>
												<td>{pigcms{$gl['price']}</td>
												<td>{pigcms{$gl['stock_num']}</td>
												<td>{pigcms{$gl['stock_num'] * $gl['price']}</td>
											</tr>
										</volist>
									</tbody>
									</if>
									</table>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("批发总数")}</label>
									<label class="col-sm-1">{pigcms{$now_goods.num}({pigcms{$now_goods.unit})</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("总金额")}</label>
									<label class="col-sm-1">{pigcms{$now_goods.total_price|floatval}({pigcms{$config['Currency_txt']})</label>
								</div>
								<if condition="$now_goods['discount_info']">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("优惠后的总价")}</label>
									<label class="col-sm-1">{pigcms{$now_goods.money|floatval}({pigcms{$config['Currency_txt']})</label>
								</div>
								</if>
                                <if condition="$now_goods['is_pick_in_store'] neq 0">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("收货人")}</label>
                                        <label class="col-sm-1">{pigcms{$now_goods.username}</label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("联系电话")}</label>
                                        <label class="col-sm-1">{pigcms{$now_goods.userphone}</label>
                                    </div>
                                    <if condition="$now_goods['province'] neq '' OR $now_goods.city neq '' OR $now_goods.area neq ''">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label>{pigcms{:L_("收货所在地")}</label></label>
                                            <label class="col-sm-9">
                                                {pigcms{$now_goods.province}&nbsp;{pigcms{$now_goods.city}&nbsp;{pigcms{$now_goods.area}
                                            </label>
                                        </div>
                                    </if>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("收货详细地址")}</label>
                                        <label class="col-sm-3">{pigcms{$now_goods.address}</label>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("购买备注说明")}</label>
									<label class="col-sm-3">{pigcms{$now_goods.desc}</label>
								</div>

                                <if condition="$now_goods['is_pick_in_store'] neq 0">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("快递公司")}</label>
                                        <select name="express_id">
                                            <volist name="express_list" id="vo">
                                            <option value="{pigcms{$vo.id}" <if condition="$now_goods['express_id'] eq $vo['id']">selected</if>>{pigcms{$vo.name}</option>
                                            </volist>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("快递单号")}</label>
                                        <input class="col-sm-3" name="express_number" type="text" value="{pigcms{$now_goods.express_number}"/>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("发货/自提备注说明")}</label>
									<textarea class="col-sm-3" rows="5" cols="10" name="sell_note">{pigcms{$now_goods.sell_note}</textarea>
								</div>
							</div>
                            <if condition="$now_goods['is_pick_in_store'] neq 0">
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit" id="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										    {pigcms{:L_("发货")}
									</button>
								</div>
							</div>
                            </if>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
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
<script>
	$('#submit').click(function(){
		if(typeof top.contentScrollToTop == 'function'){
        $.each($('.iframe-container iframe', parent.document), function(i, item){
            if($(item).css('display') != 'none'){
                console.log($(item).attr('src'), $(item).css('min-height'));
                if($(item).css('min-height') != '0' && $(item).css('min-height') != '0px'){
                    $(item).css('min-height', $(parent.document).height() - 50);
                    $(item).css('min-height', $('body').height() + 40 < $(parent.document).height() - 50 ? $(parent.document).height() - 50 : $('body').height() + 40);
                }
            }
        });
    }
	})
</script>
