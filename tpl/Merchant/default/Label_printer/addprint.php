<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cloud"></i>
				<a href="{pigcms{:U('index')}">{pigcms{:L_("微硬件")} </a>
			</li>
			<li class="active"><if condition="$_GET['pigcms_id']">{pigcms{:L_("修改")}<else/>{pigcms{:L_("增加")}</if>{pigcms{:L_("标签打印机")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<input type="hidden" name="pigcms_id" value="{pigcms{$orderprint['pigcms_id']}"/>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort_name">{pigcms{:L_("打印机名称")}</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$orderprint['name']}"/>
									<span class="form_tips">{pigcms{:L_("给打印机取个名称方便菜品在选择打印机更直观")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="print_type">{pigcms{:L_("打印机品牌")}</label>
									<select name="print_type" id="print_type">
										<option value="1" <if condition="$orderprint['print_type'] eq 1">selected="selected"</if>>{pigcms{:L_("飞鹅打印机")}</option>
										
									</select>
								</div>
								<div class="form-group feie_box">
									<label class="col-sm-1"><label for="sort">USER</label></label>
									<input class="col-sm-2" size="20" name="feie_user" id="feie_user" type="text" value="{pigcms{$orderprint['feie_user']}"/>
									<span class="form_tips feie_tip">{pigcms{:L_("在飞鹅管理后台首页查看，")}<a href="http://admin.feieyun.com/" target="_blank">{pigcms{:L_("飞鹅后台跳转链接")}</a></span>
								</div>
								<div class="form-group feie_box">
									<label class="col-sm-1"><label for="sort">UKEY</label></label>
									<input class="col-sm-2" size="20" name="feie_ukey" id="feie_ukey" type="text" value="{pigcms{$orderprint['feie_ukey']}"/>
									<span class="form_tips feie_tip">{pigcms{:L_("在飞鹅管理后台首页查看，")}<a href="http://admin.feieyun.com/" target="_blank">{pigcms{:L_("飞鹅后台跳转链接")}</a></span>
								</div>
							
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("终端号")}</label></label>
									<input class="col-sm-2" size="20" name="mcode" id="mcode" type="text" value="{pigcms{$orderprint['mcode']}"/>
									<span class="form_tips feie_tip">{pigcms{:L_("在打印机背面查看【打印机编号(SN)】")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("密钥")}</label></label>
									<input class="col-sm-2" size="20" name="mkey" id="mkey" type="text" value="{pigcms{$orderprint['mkey']}"/>
									<span class="form_tips feie_tip">{pigcms{:L_("在打印机背面查看【打印机密钥(KEY)】")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("打印份数")}</label></label>
									<input class="col-sm-1" maxlength="2" name="count" id="count" type="text" value="{pigcms{$orderprint['count']|default='1'}" readonly="true" />
									<span class="form_tips">{pigcms{:L_("每个订单打印几份（默认1份）")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="store_id">{pigcms{:L_("选择店面")}</label>
									<select name="store_id" id="store_id">
										<volist name="stores" id="store">
										<option value="{pigcms{$store['store_id']}" <if condition="$store['store_id'] eq $orderprint['store_id']">selected="selected"</if>>{pigcms{$store['name']}</option>
										</volist>
									</select>
								</div>
								<div class="form-group goods-label-print" style="display: none;">
									<label class="col-sm-1" for="FoodType_week">{pigcms{:L_("支持的业务")}</label>
									<div class="col-sm-10" style="margin-top:5px;padding-left:0;">
										<div style="width:180px;float:left;font-size:16px;">
											<label><input type="checkbox" value="shop" name="bussiness_type[]" <if condition="in_array('shop', $orderprint['bussiness_type'])">checked="checked"</if>/>{pigcms{:L_("外卖")} </label>&nbsp;&nbsp;
										</div>
									</div>
								</div>
                                <div class="form-group goods-label-print">
                                    <label class="col-sm-1"><label>{pigcms{:L_("指定商品")}</label></label>
                                    <a href="javascript:;" class="btn btn-sm btn-success add_goods">{pigcms{:L_("添加商品")}</a>
                                    <table class="table table-striped table-bordered repWrap" style="width: 450px" width="450px">
                                    	<tr>
                                    		<th width="60%">{pigcms{:L_("商品名")}</th>
                                    		<th width="20%">{pigcms{:L_("价格")}</th>
                                    		<th width="20%">{pigcms{:L_("操作")}</th>
                                    	</tr>
                                    	<volist name="goods_list" id="vo">
                                    		<tr>
                                    			<td>{pigcms{$vo.name}<input type="hidden" name="goods_id[]"  value="{pigcms{$vo.goods_id}" ></td>
       											<td>{pigcms{$vo.price}</td>
       											<td> <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td>
       										</tr>
                                    	</volist>
                                    </table>
                                </div>
								<if condition="$ok_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:blue;">{pigcms{$ok_tips}</span>				
									</div>
								</if>
								<if condition="$error_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:red;">{pigcms{$error_tips}</span>				
									</div>
								</if>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
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
<script>
	$(function(){
	

	});


     // 打开添加商品弹层
    $('.add_goods').on('click',function(){
        var savegood=$(this).attr('data-savegood');
        var selecteditemsArr = []
        $.each($("input[name='goods_id[]'"),function (e) {
        	selecteditemsArr.push($(this).val())
        })
        var selecteditems = selecteditemsArr.join(',')
        var store_id = $('#store_id').val();
        parent.layer.open({
            title: false,
            closeBtn: 0,
            type: 2,
            content: "{pigcms{:U('Diypage/good')}&type=more&store_id="+store_id+"&selecteditems="+selecteditems,
            area: ['650px', '500px'],
            shade: 0.2,
            cancel: function () {
            },
            move: false
        });
    })

    // 选择商品后回调方法
    function widget_box_after(name,data){
    	console.log(data)
        //菜品不能重复
        var goods_ids= $("input[name='goods_id[]'").val();
        
        if(data.length>0){
        	var html= ''
        	for (var i = 0; i < data.length; i++) {
        		html += '<tr><td>'+data[i].title+'<input type="hidden" name="goods_id[]"  value="'+data[i].id+'" ></td>'
		        html += '<td>'+data[i].price+'</td>'
		        html += '<td>  <a href="javascript:;" class="tr_del">{pigcms{:L_("删除")}</a></td></tr>'
        	}
        }
       
        $(".repWrap").append(html);
    }

    // 删除商品
    $(document).on('click', '.tr_del', function(){
        $(this).parents('tr').remove();
    })
</script>
<include file="Public:footer"/>