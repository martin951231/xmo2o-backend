<include file="Public:header"/>
<div class="main-content">
	<style type="text/css">
		*{list-style: none;}
		#upload_pic_box{overflow: auto;}
		#upload_pic_box .upload_pic_li,.wap_upload_pic_li{width:130px;float:left;list-style:none;display: inline-block;}
		#upload_pic_box img{width:100px;height:70px;}
		#upload_pic_ul,#wap_upload_pic_ul{overflow: auto;margin: 0;}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('index')}">{pigcms{:L_("商家")}{pigcms{:L_("积分商城管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("积分商品列表添加")}/{pigcms{:L_("编辑")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
                        <input type="hidden" name='goods_id' value="{pigcms{$info.goods_id}"/>
                        <input type="hidden" name='market_goods_id' value="{pigcms{$info.market_goods_id}"/>
                        <input type="hidden" name='market_order_id' value="{pigcms{$info.market_order_id}"/>
                        <input type="hidden" name='is_spec' value="{pigcms{$info.is_spec}"/>
						<div class="tab-content">				
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商品名称")}</label>
                                    <input class="col-sm-2" size="20" maxlength="20" name="goods_name" type="text" value="{pigcms{$info.goods_name}" <if condition="$info['market_goods_id'] gt 0 && $info.goods_name">readonly</if>/>
									<span class="form_tips"></span>
								</div>
                                <if condition="!$info['spec_list']">
                                    <div class="form-group">
                                        <label class='col-sm-1' >{pigcms{:L_("所需")}{pigcms{$config['score_name']}</label>
                                        <input class="col-sm-1" size="20" maxlength="20" name="payment_pure_integral" type="text" value="{pigcms{$info.payment_pure_integral}" />
                                        <span class="form_tips"></span>
                                    </div>
                                </if>
								<!-- 选择分类 -->
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("选择分类")}</label></label>
									<select id="choose_catfid" name="cat_fid" class="col-sm-1" style="margin-right:10px;" onchange="get_son_category($(this).val())">
										<option value="0" >{pigcms{:L_("请选择")}</option>
										<volist name="cate_list" id="vo">
											<option value="{pigcms{$key}" <if condition='$info["cat_fid"] eq $key'>selected="selected"</if>>{pigcms{$vo}</option>
										</volist>
									</select>
									<select id="choose_catid" name="cat_id" class="col-sm-1" style="margin-right:10px;">
										<option value="0" >{pigcms{:L_("请选择")}</option>
										<volist name="cate_list2" id="vo">
											<option value="{pigcms{$key}" <if condition='$info["cat_id"] eq $key'>selected="selected"</if>>{pigcms{$vo}</option>
										</volist>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("商品配送方式")}</label></label>
                                    <div class="col-sm-1">
                                        {pigcms{:L_("到店自提")}
                                    </div>
<!--                                    <select name="deliver_type" class="col-sm-2">-->
<!--										<option value="0" <if condition='$info["deliver_type"] eq 0'>selected="selected"</if>>{pigcms{:L_("快递配送")}</option>-->
<!--										<option value="1" <if condition='$info["deliver_type"] eq 1'>selected="selected"</if>>{pigcms{:L_("到店自提")}</option>-->
<!--										<option value="2" <if condition='$info["deliver_type"] eq 2'>selected="selected"</if>>{pigcms{:L_("快递配送")}+{pigcms{:L_("门店自提")}</option>-->
<!--									</select>-->
                                    <input class="col-sm-1" size="10"  name="deliver_type" type="hidden" value="1" />
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
                                <if condition="!$info['spec_list']">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("每人限制兑换数量")}</label>
                                        <input class="col-sm-1" size="10" maxlength="20" name="exchange_limit_num" type="text" value="{pigcms{$info.exchange_limit_num}" />
                                        <span class="form_tips red">0{pigcms{:L_("为不限制")}</span>
                                    </div>
                                </if>

                                <if condition="$info['spec_list']">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("总库存数量")}</label>
                                        <input class="col-sm-1 goods_sku" data-max_num="{pigcms{$info.sku}" data-market_goods_id="{pigcms{$info.market_goods_id}" size="10" maxlength="20" name="sku" type="text" value="{pigcms{$info.sku}" readonly/>
                                        <span class="form_tips red">{pigcms{:L_("单个规格库存")}-1{pigcms{:L_("为不限制")}</span>
                                    </div>
                                    <else/>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("库存数量")}</label>
                                        <input class="col-sm-1 goods_sku" data-max_num="{pigcms{$info.sku}" data-market_goods_id="{pigcms{$info.market_goods_id}" size="10" maxlength="20" name="sku" type="text" value="{pigcms{$info.sku}"/>
                                        <span class="form_tips red">-1{pigcms{:L_("为不限制")}</span>
                                    </div>
                                    <php>if (1 == $config['merchant_integral_mall_control'] && 0) {</php>
                                        <php>if ($_GET['goods_id'] && $_GET['goods_id']>0) {</php>
                                            <div class="form-group">
                                                <label class="col-sm-1">{pigcms{:L_("配送计费方式")}</label>
                                                <div  class="col-sm-1">
                                                <php>
                                                    if (2==$info['freight_type']) {
                                                        echo '按重量';
                                                    } else if(3==$info['freight_type']) {
                                                        echo '按体积';
                                                    } else {
                                                        echo '按件数';
                                                    }
                                                </php>
                                                </div>
                                            </div>
                                        <php>} else {</php>
                                            <div class="form-group">
                                                <label class="col-sm-1">{pigcms{:L_("配送计费方式")}</label>
                                                <select name='freight_type' class="freight_type_change">
                                                    <option value="1" <eq name='now_goods.freight_type' value='1'>selected='selected'</eq>>{pigcms{:L_("按件数")}</option>
                                                    <option value='2' <eq name='now_goods.freight_type' value='2'>selected='selected'</eq>>{pigcms{:L_("按重量")}</option>
                                                    <option value='3' <eq name='now_goods.freight_type' value='3'>selected='selected'</eq>>{pigcms{:L_("按体积")}</option>
                                                </select>
                                                <span class="form_tips">{pigcms{:L_("选择按重量-重量必填，想着按体积-体积必填。")}</span>
                                            </div>
                                        <php>}</php>

                                    <php>}</php>
                                    <div class="form-group goods_weight" <neq name='info.freight_type' value='2'>style="display: none;"</neq>>
                                        <label class="col-sm-1"><label for="stock">{pigcms{:L_("重量")}</label></label>
                                        <input <if condition="$_GET['goods_id'] && $_GET['goods_id'] gt 0">readonly</if> class="col-sm-1" size="20" name="goods_weight" id="goods_weight" type="text" value="{pigcms{$info.goods_weight}"/>
                                        <span class="form_tips">{pigcms{:L_("必填。")}{pigcms{:L_("重量")}（{pigcms{:L_("千克")}kg） {pigcms{:L_("支持2位小数")}</span>
                                    </div>
                                    <div class="form-group goods_volume" <neq name='info.freight_type' value='3'>style="display: none;"</neq>>
                                        <label class="col-sm-1"><label for="stock">{pigcms{:L_("体积")}</label></label>
                                        <input <if condition="$_GET['goods_id'] && $_GET['goods_id'] gt 0">readonly</if> class="col-sm-1" size="20" name="goods_volume" id="goods_volume" type="text" value="{pigcms{$info.goods_volume}"/>
                                        <span class="form_tips">{pigcms{:L_("必填。")}{pigcms{:L_("体积")}（{pigcms{:L_("立方")}m³） {pigcms{:L_("支持2位小数")}</span>
                                    </div>
                                </if>
                                <if condition="!$info['spec_list']">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("已兑换人数")}</label>
									<input class="col-sm-1" size="10" maxlength="20" name="exchanged_num" type="text" value="{pigcms{$info.exchanged_num}" />
									<span class="form_tips"></span>
								</div>
                                </if>
                                <div class="form-group" >
                                    <label class="col-sm-1">{pigcms{:L_("电脑端图片")}</label>
                                    <div style="display:inline-block;" id="J_selectImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips red">{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！建议尺寸：")}800*800</span>
                                </div>
								<div class="form-group background">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name='info.pc_pic_list' id='pvo'>
												<li class="upload_pic_li">
													<img src="{pigcms{$pvo.url}"/>
													<input type="hidden" name="pc_pic[]" value="{pigcms{$pvo.title}"/><br/>
													<a href="#" onclick="deleteImage('{pigcms{$pvo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a>
												</li>
											</volist>
										</ul>
									</div>
								</div>
								<div class="form-group" >
                                    <label class="col-sm-1">{pigcms{:L_("手机端图片")}</label>
                                    <div style="display:inline-block;" id="J_wap_selectImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips red">{pigcms{:L_("第一张将作为列表页图片展示！最多上传5个图片！建议尺寸：")}800*800</span>
                                </div>
								<div class="form-group background">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="wap_upload_pic_ul">
											<volist name='info.wap_pic_list' id='wvo'>
												<li class="upload_pic_li">
													<img src="{pigcms{$wvo.url}"/>
													<input type="hidden" name="wap_pic[]" value="{pigcms{$wvo.title}"/><br/>
													<a href="#" onclick="deleteImage('{pigcms{$wvo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a>
												</li>
											</volist>
										</ul>
									</div>
								</div>


                                <if condition="$info['spec_list']">
                                    <php>if (1 == $config['merchant_integral_mall_control'] && 0) {</php>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("配送计费方式")}</label>
                                        <div  class="col-sm-1">
                                            <php>
                                                if (2==$info['freight_type']) {
                                                echo '按重量';
                                                } else if(3==$info['freight_type']) {
                                                echo '按体积';
                                                } else {
                                                echo '按件数';
                                                }
                                            </php>
                                        </div>
                                    </div>
                                    <php>}</php>

                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("规格")}</label>
                                        <div class="topic_box col-sm-11">
                                            <table class="table table-striped table-bordered table-hover" id="table_list">
                                                <tbody>
                                                <tr>
                                                    <volist name="info['spec_list']" id="gs">
                                                        <th>{pigcms{$gs['name']}</th>
                                                    </volist>
                                                    <th>{pigcms{:L_("所需积分")}</th>
                                                    <th>{pigcms{:L_("库存数量（正整数）")}</th>
                                                    <th class="goods_weight_spec" style="display: none;">{pigcms{:L_("重量")}（{pigcms{:L_("千克")}kg）</th>
                                                    <th class="goods_volume_spec" style="display: none;">{pigcms{:L_("体积")}（{pigcms{:L_("立方")}m³）</th>
                                                    <th>{pigcms{:L_("每人限制兑换数量（正整数,0为不限制）")}</th>
                                                    <th>{pigcms{:L_("已兑换人数（正整数）")}</th>
                                                    <th>{pigcms{:L_("实际兑换人数（正整数）")}</th>
                                                </tr>

                                                <volist name="info['list']" id="gl" key="id_index" >
                                                    <tr id="{pigcms{$gl['index']}">
                                                        <volist name="gl['spec']" id="g">
                                                            <td>{pigcms{$g['spec_val_name']}</td>
                                                        </volist>
                                                        <td>
                                                            <input type="text" class="txt" name="payment_pure_integrals[{pigcms{$gl['index']}]" value="{pigcms{$gl['payment_pure_integral']}" style="min-width:50px;">
                                                        </td>
                                                        <td>
                                                            <if condition="$info.market_goods_id elt 0 || !$gl.sku">
                                                                <input type="text" class="txt" onkeyup="buy_num_keyup(this.value,this,'{pigcms{$gl.stock_num}')" name="skus[{pigcms{$gl['index']}]" value="{pigcms{$gl['stock_num']}" style="min-width:50px;">
                                                                <else/>
                                                                <input type="text" class="txt" onkeyup="buy_num_keyup(this.value,this,'{pigcms{floatval($gl.sku)}')" name="skus[{pigcms{$gl['index']}]" value="{pigcms{$gl['sku']}" style="min-width:50px;">
                                                            </if>
                                                            <span class="form_tips" style="color: red; display: none; height: 20px;">{pigcms{:L_("最多不得超过")}{pigcms{$gl.stock_num}{pigcms{:L_("库存")}</span>
                                                        </td>
                                                        <td class="goods_weight_spec" style="display: none;">
                                                            <input readonly type="text" class="txt" name="goods_weights[{pigcms{$gl['index']}]" value="{pigcms{$gl['goods_weight']}" style="width:80px;">
                                                        </td>

                                                        <td class="goods_volume_spec" style="display: none;">
                                                            <input readonly type="text" class="txt" name="goods_volumes[{pigcms{$gl['index']}]" value="{pigcms{$gl['goods_volume']}" style="width:80px;">
                                                        </td>
                                                        <td>
                                                            <if condition="$info.market_goods_id gt 0">
                                                                <input type="text" class="txt" onkeyup="buy_num_keyup(this.value,this,'{pigcms{$gl.stock_num}')" name="exchange_limit_nums[{pigcms{$gl['index']}]" value="{pigcms{$gl['exchange_limit_num']}" style="min-width:50px;">
                                                                <else/>
                                                                <input type="text" class="txt" onkeyup="buy_num_keyup(this.value,this,'{pigcms{$gl.stock_num}')" name="exchange_limit_nums[{pigcms{$gl['index']}]" value="" style="min-width:50px;">
                                                            </if>
                                                            <span class="form_tips" style="color: red; display: none; height: 20px;">{pigcms{:L_("最多不得超过")}{pigcms{$gl.stock_num}{pigcms{:L_("库存")}</span>
                                                        </td>
                                                        <td>
                                                            <if condition="$info.market_goods_id gt 0">
                                                                <input type="text" class="txt" name="exchanged_nums[{pigcms{$gl['index']}]" value="{pigcms{$gl['exchanged_num']}" style="min-width:50px;">
                                                                <else/>
                                                                <input type="text" class="txt" name="exchanged_nums[{pigcms{$gl['index']}]" value="" style="min-width:50px;">
                                                            </if>
                                                        </td>
                                                        <td>
                                                            <if condition="$gl['sell_num'] && $gl['sell_num'] gt 0">
                                                                <input type="text" class="txt" value="{pigcms{$gl['sell_num']}" style="min-width:50px;" readonly>
                                                                <else/>
                                                                <input type="text" class="txt" value="0" style="min-width:50px;" readonly>
                                                            </if>
                                                        </td>
                                                    </tr>
                                                </volist>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </if>
<!--                                <div class="form-group">-->
<!--                                    <label class="col-sm-1">{pigcms{:L_("简述")}</label>-->
<!--                                    <textarea rows="6" cols="104" name="intro">{pigcms{$info.intro}</textarea>-->
<!--                                    <span class="form_tips"></span>-->
<!--                                </div>-->
<!---->
<!--                                <div class="form-group">-->
<!--									<label class="col-sm-1">{pigcms{:L_("发货清单")}</label>-->
<!--									<input class="col-sm-1" size="104" maxlength="20" name="invoice_content" type="text" value="{pigcms{$info.invoice_content}" />-->
<!--									<span class="form_tips"></span>-->
<!--								</div>-->
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商品详情")}</label>
									<textarea rows="6" cols="104" name="goods_content" id="content">{pigcms{$info.goods_content}</textarea>
									<span class="form_tips"></span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("排序")}</label>
									<input class="col-sm-1" size="10" maxlength="20" name="sort" type="text" value="{pigcms{$info.sort}" />
									<span class="form_tips red">{pigcms{:L_("排序值越大，越往前显示！")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("今日新品")}</label></label>
									<select name="is_new" class="col-sm-1">
										<option value="1" <if condition='$info["is_new"] eq 1'>selected="selected"</if>>{pigcms{:L_("开启")}</option>
										<option value="0" <if condition='$info["is_new"] eq 0'>selected="selected"</if>>{pigcms{:L_("关闭")}</option>
									</select>
									<span class="form_tips red" id="showDeliverTips"> </span>
								</div>
								<if condition="!empty($info) && $info['status'] neq 2">
									<div class="form-group">
										<label class="col-sm-1"><label>{pigcms{:L_("商品状态")}</label></label>
										<select name="status" class="col-sm-1">
											<option value="1" <if condition='$info["status"] eq 1'>selected="selected"</if>>{pigcms{:L_("开启")}</option>
											<option value="0" <if condition='$info["status"] eq 0 || $info["status"] eq 3'>selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										</select>
										<span class="form_tips red" id="showDeliverTips"> </span>
									</div>
								</if>	
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<!-- {pigcms{:L_("富文本")} -->
<script type="text/javascript">

    function buy_num_keyup(val,obj,stock_num){
        if(parseInt(val) > parseInt(stock_num)){
            $(obj).val(stock_num);
            $(obj).next("span").css("display", "");
        }else{
            $(obj).next("span").css("display", "none");
        }
    }
    $('.goods_sku').blur(function() {
        var market_goods_id = $(this).data('market_goods_id');
        console.log('market_goods_id',market_goods_id);
        if (market_goods_id>0) {
            var max_num = $(this).data('max_num');
            console.log('max_num',max_num);
            var val = $(this).val();
            console.log('val',val);
            if (val>max_num) {
                alert('{pigcms{:L_("库存不得大于")}'+max_num);
                $(this).val(max_num);
                return false;
            }
        }
    });
    $('.freight_type_change').change(function(){
        var val=$(this).val();

        if(1==val){
            $('.goods_weight').hide();
            $('.goods_volume').hide();
        }else if (2==val){
            $('.goods_weight').show();
            $('.goods_volume').hide();
        }else if (3==val){
            $('.goods_weight').hide();
            $('.goods_volume').show();
        }

    });
    $('.freight_type_spec_change').change(function(){
        var val=$(this).val();

        if(1==val){
            $('.goods_weight_spec').hide();
            $('.goods_volume_spec').hide();
        }else if (2==val){
            $('.goods_weight_spec').show();
            $('.goods_volume_spec').hide();
        }else if (3==val){
            $('.goods_weight_spec').hide();
            $('.goods_volume_spec').show();
        }

    });

    $('select[name="freight_type"]').change();

var diyTool = "{pigcms{:U('Article/diytool')}";
var editor;
var diyVideo = "{pigcms{:U('Article/diyVideo')}";
KindEditor.ready(function(K) {
	editor = K.create('#content', {
		filterMode: false,
		resizeType : 1,
		allowPreviewEmoticons : false,
		allowImageUpload : true,
		height:200,
		width:650,
		uploadJson : '/admin.php?g=System&c=Upyun&a=kindedtiropic',
		items : ['fontname', 'fontsize','subscript','superscript','indent','outdent','|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline','hr',
		 '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist','link', 'unlink','image','diyVideo','diyTool']
	});
});
KindEditor.ready(function(K) {
	K('#J_selectImage').click(function(){
		if($('.upload_pic_li').size() >= 5){
			alert('{pigcms{:L_("最多上传5个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic', array('path' => 'goods'))}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
                    console.log('url1', url);
					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pc_pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
		});
		setTimeout(function(){
			$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
		},200);
	});


	K('#J_wap_selectImage').click(function(){
		if($('.wap_upload_pic_li').size() >= 5){
			alert('{pigcms{:L_("最多上传5个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic', array('path' => 'goods'))}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
				    console.log('url2', url);
					$('#wap_upload_pic_ul').append('<li class="wap_upload_pic_li"><img src="'+url+'"/><input type="hidden" name="wap_pic[]" value="'+title+'"/><br/><a href="#" onclick="wap_deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
		});
		setTimeout(function(){
			$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
		},200);
	});
});
</script>
<script type="text/javascript">
$(function(){
	$('.tag').on('click','.close',function(){
		$('#coupon_id').val('');
		$('.tag').html('');
		$('#deliver_type').show();
	});
});
</script>
<script type="text/javascript">
var url = "{pigcms{:U('ajax_category')}";
function get_son_category(fid){
	if(fid==0){
		var shtml = '<option value="0" >{pigcms{:L_("请选择")}</option>';
		$('#choose_catid').empty('').append(shtml);
	}else{
		$.post(url,{'cat_fid':fid},function(data){
			if(data.status){
				$('#choose_catid').show();
				var shtml = '<option value="0" >{pigcms{:L_("请选择")}</option>';
				for(var i in data['cat_list']){
					shtml += '<option value="'+i+'">'+data['cat_list'][i]+'</option>';
				}

				$('#choose_catid').empty('').append(shtml);
			}else{
				$('#choose_catid').html('');
				$('#choose_catid').hide();
			}
		},'json');
	}
}
function deleteImage(path,obj){
	$.post("{pigcms{:U('ajax_del_pic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}
function wap_deleteImage(path,obj){
	$.post("{pigcms{:U('ajax_del_pic')}",{path:path});
	$(obj).closest('.wap_upload_pic_li').remove();
}

function addCoupon(){
	art.dialog.data('titledom', 'titledom');
	art.dialog.data('imgids', 'imgids');
	art.dialog.data('multinews', 'multinews');
	art.dialog.data('singlenews', 'singlenews');
	art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
	art.dialog.data('multione', 'multione');
	art.dialog.open('admin.php?g=System&c=Coupon&a=select_coupon',{lock:true,title:'{pigcms{:L_("选择平台优惠券',width:900,height:600,yesText:'关闭")}',background: '#000',opacity: 0.45});
}
</script>
<include file="Public:footer"/>
