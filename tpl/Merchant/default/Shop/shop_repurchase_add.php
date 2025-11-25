<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Shop/shop_repurchase',['store_id' => $_GET['store_id']])}">{pigcms{:L_("超值抢购优惠")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="myform" method="post" >
								<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}">
								<div class="tab-content card_new" id="tab1" >
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("活动名称")}:</label></label>
										<input class="input" type="text" name="name" value="{pigcms{$detail.name}">&nbsp;<font color="red">*</font>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("活动时间")}:</label></label>
										<input type="text" class="input-text" name="start_time" id="start_time" style="width:160px;" placeholder="{pigcms{:L_("点击设置开始时间")}" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" />&nbsp;&nbsp;-&nbsp;&nbsp;
										<input type="text" class="input-text" name="end_time" id="end_time" style="width:160px;" placeholder="{pigcms{:L_("点击设置结束时间")}" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" />&nbsp;<font color="red">*</font>
										<span class="tip">({pigcms{:L_("开始时间最早为当天")})</span>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("换购门槛")}:</label></label>

										<input type="radio" name="is_limit" value="0"/>{pigcms{:L_("不使用门槛")}&nbsp;&nbsp;&nbsp;&nbsp;
										<label><input type="radio" name="is_limit" value="1" checked="checked" />{pigcms{:L_("使用门槛")}  &nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("订单满")}&nbsp;&nbsp;<input type="text" class="input" name="limit_money" value="30" autocomplete="off" />&nbsp;&nbsp;{pigcms{$config['Currency_txt']}，{pigcms{:L_("可换购")}</label>
									</div>
                                    <div class="form-group flex flex-align">
										<label class="tiplabel" style="margin-top: 12px;"><label>{pigcms{:L_("星期几显示")}:</label></label>
										<label class=" cycle_type" style="margin-top: 12px;">
				                            <volist name="week_arr" id="item">
				                                <label class="show_date fl active" data-value="{pigcms{$key}">{pigcms{$item}</label>
				                            </volist>
				                            <input type="hidden" id="week" name="week" value="0,1,2,3,4,5,6">
				                        </label>&nbsp;&nbsp;{pigcms{:L_("的")}&nbsp;&nbsp;
			                            <input type="text" class="input-text" name="show_time_start" id="show_time_start" style="width:80px;" value="00:00" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})" />&nbsp;&nbsp;{pigcms{:L_("至")}&nbsp;&nbsp;
										<input type="text" class="input-text" name="show_time_end" id="show_time_end" style="width:80px;" value="23:59" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})" />&nbsp;<font color="red">*</font>
									</div>
									
									
                                    <div class="form-group wx_coupon">
                                        <label class="tiplabel"><label>{pigcms{:L_("活动商品")}</label></label>
                                        <a href="javascript:;" class="btn btn-sm btn-success add_goods">{pigcms{:L_("添加换购商品")}</a>&nbsp;
                                        <span class="tip">({pigcms{:L_("最多只能添加3个商品")})</span>
                                        <table class="table table-striped table-bordered repWrap" style="margin-top: 22px;margin-left: 12px;">
                                        	<tr><th>{pigcms{:L_("换购商品")}</th><th>{pigcms{:L_("原价")}</th><th>{pigcms{:L_("活动价")}</th><th>{pigcms{:L_("活动库存")}</th><th>{pigcms{:L_("原库存")}</th><th>{pigcms{:L_("操作")}</th></tr>
                                        </table>
                                    </div>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit" onclick="confirm_coupon()">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="./static/js/cart/jscolor.js" type="text/javascript"></script>
<link rel="stylesheet" href="./static/kindeditor/themes/default/default.css"/>
<link rel="stylesheet" href="./static/kindeditor/plugins/code/prettify.css"/>
<style>

	.show_date.active {
	    color: #067ADE;
	    border: #067ADE 1px solid;
	}
	.show_date {
	    background-color: white;
	    color: #0a0a0a;
	    border: #b6b4a4 1px solid;
    	padding: 5px 10px;
	    margin-bottom: 5px;
	    cursor: pointer;
	    margin-right: 5px;
	}
	.fl {
    float: left;
    display: inline;
}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>

<link rel="stylesheet" href="./static/validate/dist/css/bootstrapValidator.css"/>
<!-- Include the FontAwesome CSS if you want to use feedback icons provided by FontAwesome -->
<!--<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" />-->
<script type="text/javascript" src="./static/validate/vendor/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="./static/validate/vendor/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="./static/validate/dist/js/bootstrapValidator.js"></script>
<script type="text/javascript">
</script>
<script type="text/javascript">
        function 
        seloptall(s) {
            if(s == 0){
                $('input[class="px2"]').each(function(){
                    $(this).prop('checked',true);
                });
            }else{
                $('input[class="px2"]').each(function(){
                    $(this).prop('checked',false);
                });
            }
        }
        seloptall(0)

    // 点击选择日期
    $('.show_date').click(function ()  {
        var value = $(this).data('value');
        var is_active = $(this).hasClass('active');
        if (is_active) {
            $(this).removeClass('active');
        }else{
            $(this).addClass('active');
        }
        var date_arr = [];
        var node = $(this).parent().children('label');
        node.each(function(i,val){
            var val_active = $(val).hasClass('active');
            if (val_active) {
                date_arr.push($(val).data('value'));
            }
        });
        date_arr = date_arr.join(',');
        $('#week').val(date_arr);
    });

     // 打开添加商品弹层
    $('.add_goods').on('click',function(){
        var savegood=$(this).attr('data-savegood');
        parent.layer.open({
            title: false,
            closeBtn: 0,
            type: 2,
            content: '{pigcms{$this->config['
            site_url']}/merchant.php?c=Shop&a=subsidiary_goods_op_piece_list&type=rep&store_id={pigcms{$_GET.store_id}&savegood='+savegood+'',
            area: ['650px', '576px'],
            shade: 0.2,
            cancel: function () {
            },
            move: false
        });
    })

    // 选择商品后回调方法
    function login_box_after(name,price,spec_value,index,savegood,goods_id,stock_num){
        //菜品不能重复
        var goods_ids= $("input[name='goods_id[]'").val();
        var is_repeat=false;
        $.each($("input[name='goods_id[]'"),function (e) {
        	if ($(this).val()==goods_id) {
        		is_repeat = true;
        	}
        })
        if (is_repeat) {
        	layer.alert('{pigcms{:L_("不能添加重复商品")}');
        	return false;
        }

        if ($("input[name='goods_id[]'").length>2) {
        	layer.alert('{pigcms{:L_("最多添加三个商品")}');
        	return false;
        }

        var html='<tr><td>'+name+'<input type="hidden" name="goods_id[]"  value="'+goods_id+'" ></td>'
      
        html+='<td>'+price+'</td>'
        html+='<td><input type="text" name="price[]"  value="'+price+'" ></td>'
       
        html+='<td><input type="text" name="stock_num[]"  value="'+stock_num+'" ></td>';
        html+='<td>'+stock_num+'</td>';

        html+='<td>  <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td></tr>'
        var numSavegood=parseInt(savegood)+1;
        $(".repWrap").append(html);
    }

    // 删除商品
    $(".tr_del").on('click',function(){
        $(this).parents('tr').remove();
    });
</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/card_new.css"/>
<style>
	#tab1 .tiplabel {
		margin-left: 20px;
		margin-right: 20px;
		min-width: 200px;!important
	}
</style>
<include file="Public:footer"/>