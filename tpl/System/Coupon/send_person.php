<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Coupon/index')}">平台优惠券列表</a>
			<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Coupon/add')}','添加平台优惠券',800,500,true,false,false,addbtn,'edit',true);">添加平台优惠券</a>
			<a href="{pigcms{:U('Coupon/had_pull')}" >领取列表</a>
			<a href="{pigcms{:U('Coupon/send_coupon')}"  class="on" >派发优惠券</a>
		</ul>
	</div>
	<div id="nav" class="mainnav_title" style="margin-top:5px;">
		<ul  id="myTab">
			<a href="{pigcms{:U('send_coupon')}"  >
				等级派发
			</a>
			<a href="{pigcms{:U('send_label_coupon')}">
				按用户标签派发
			</a>
			<a href="{pigcms{:U('send_all')}" >
				全部派发
			</a>
			<a data-toggle="tab" href="{pigcms{:U('send_person')}"  class="on">
				个人派发
			</a>
			<a href="{pigcms{:U('weixin_send')}" >
				微信购买派发
			</a>
			<a href="{pigcms{:U('send_city')}" >
				按城市派发
			</a>
			<a href="{pigcms{:U('send_multiple')}" >
				部分用户派发
			</a>
			<a href="{pigcms{:U('send_history')}" >
				派发记录
			</a>
		</ul>
	</div>
	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			
			<div class="tab-content">
				
				<div id="groupinfo" class="tab-pane active"  >
					<div class="widget-box">
						<div class="widget-body" id="group_main" style="padding:20px;height:100px;width:100%;">
							<select name="keyword">
								<option value="uid">用户ID</option>
								<option value="nickname">用户昵称</option>	
								<option value="phone">用户手机</option>	
							</select>
							<input type="text" name="search_val" style="padding: 3px 3px 4px;"><a style="padding: 0.8em 0.4em 0.7em;margin-left:10px" class="label label-sm label-info" href="javascript:void(0);" id="search_user">搜索用户</a>
						</div>
						<table class="table table-striped table-bordered table-hover" id="user_table" style="display:none">
							<thead>
								<tr>
									<th>勾选会员</th>
									
									<th>用户昵称</th>
									<th>用户手机</th>
									
								</tr>
							</thead>
							<tbody id="user_list">
								
							</tbody>
						</table>
					</div>	
					<div class="row">					
						<div class="col-xs-12">		
							<div class="grid-view">
								
							</div>						
						</div>
					
					</div>
				</div>
				<style type="text/css">
					.radio {
						min-height: 27px;
					}
				</style>
				<div class="tab-pane " style="display:block">
					<div class="widget-box">
						
						<div class="widget-body form-group" id="coupon_list" style="padding:20px;height:300px;width:100%;line-height: 1.5;overflow:auto">
							<table class="table table-striped table-bordered table-hover" id="user_table" >
								<thead>
									<tr>
										<th>勾选优惠券</th>
										<th>发放数量</th>
										<th>优惠券ID</th>
										<th>优惠券名称</th>
										<th>使用类型</th>
										<th>总数</th>
										<th>已领取</th>
										<th>优惠券描述</th>
									</tr>
								</thead>
								<tbody id="user_list">
							<volist name="coupon_list" id="vo">
								
								
									<tr class="even">
										<td style="width: 120px"><input type="checkbox" name="coupon_id[]" value="{pigcms{$vo.coupon_id}" id="coupon{pigcms{$vo.id}"></td>
										<td style="width: 120px"><input type="number" value="1" id="send_num_{pigcms{$vo.coupon_id}"></td>
										<td style="width: 120px">{pigcms{$vo.coupon_id}</td>
										<td style="width: 120px">{pigcms{$vo.name}</td>
										<td style="width: 120px">{pigcms{$vo.cate_name_txt}</td>
										<td style="width: 120px">{pigcms{$vo.num}</td>
										<td style="width: 120px">{pigcms{$vo.had_pull}</td>
										<td style="width: 120px">{pigcms{$vo.des}</td>
									</tr>
							</volist>
							</tbody>
							</table>
						</div>
					</div>	
					<div class="row">					
						<div class="col-xs-12"  style="    margin-bottom: 8px;">		
							<div class="grid-view">
								<button class="btn btn-info" type="submit" id="save_btn" style="margin-left: 25%;" href="">
									<i class="ace-icon fa fa-check bigger-110"></i>
									派发优惠券
								</button>
							</div>						
						</div>
					
					</div>
				</div>
				
			</div>
		</div>
	</form>
</div>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
	
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
	
	var tab = $('#myTab .on a').attr('data-id');
	$(function(){
		$('#search_user').click(function(){
			var keyword = $('select[name="keyword"]').val();
			var search_val = $('input[name="search_val"]').val();
			$.post("{pigcms{:U('ajax_get_user')}", {keyword:keyword,search_val:search_val}, function(data, textStatus, xhr) {
				if(data == null || data == undefined || data == ''){
					$('#user_list').html('<tr class="odd"><td class="button-column" colspan="4" >没有查询到用户</td></tr>');
				}else{
					var str;
					$.each(data, function(index, val) {
						if(index%2==0){
							str += '<tr class="even">';
						}else{
							str += '<tr class="odd">';
						}
						str+='<td style="width: 120px"><input type="radio" name="uid" value="'+val.uid+'"></td>';
						
						str+='<td style="width: 120px">'+val.nickname+'</td>';
						str+='<td style="width: 120px">'+val.phone+'</td>';
						str+='</tr>';
					});
					$('#user_list').html(str);
					$('#user_table').show();
				}
				
			});
		});
		$('#save_btn').click(function(){
			var send_url = "{pigcms{:C('config.site_url')}{pigcms{:U('send_new')}";
			
			if($("input[name='uid']:checked").length==0){
				alert('没有勾选用户');return false;
			}else{
				uid = $("input[name='uid']:checked").val();
			}
			
			if($("input[name='coupon_id[]']:checked").length==0){
				alert('没有勾选优惠券');return false;				
			}
			

            var coupon_id='';
			var send_num = '';
		 	$('input[name="coupon_id[]"]').each(function () {
                if ($(this).is(":checked")) {
                    coupon_id +=$(this).val()+',';
					send_num +=$('#send_num_'+$(this).val()).val()+','
                }
            });
            coupon_id=coupon_id.substring(0,coupon_id.length-1);
			send_num=send_num.substring(0,send_num.length-1);

			$(this).attr('href',send_url+'&uid='+uid+'&coupon_id='+coupon_id+'&send_num='+send_num);
			
        	
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.o2oTop.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:$(this).data('title'),
				padding: 0,
				width: 720,
				height: 520,
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
		
            console.log(coupon_id);
		});

		$('input[name="level[]"]').click(function(){
		 	var level='';
		 	$('input[name="level[]"]').each(function () {
                if ($(this).is(":checked")) {
                    level +=$(this).val()+',';
                }
            });
            level=level.substring(0,level.length-1);
			$.post("{pigcms{:U('ajax_get_send_coupon')}", {level:level}, function(data, textStatus, xhr) {
				console.log(data);
				if(data == null || data == undefined || data == ''){
					$('#coupon_list').html('没有可用的优惠券');
				}else{
					var str='';
					$.each(data, function(coupon_id, val) {
						str += '<div class="radio"><label>';
						if(val.disable){
							str+='<input class="paycheck ace store-list" disabled="disabled" type="checkbox" name="coupon_id[]" value="'+coupon_id+'" id="coupon'+coupon_id+'"/>';
						}else{							
							str+='<input class="paycheck ace store-list" type="checkbox" name="coupon_id[]" value="'+coupon_id+'" id="coupon'+coupon_id+'"/>';
						}
						str+='<span class="lbl"><label for="coupon'+coupon_id+'">'+val.name+' - '+val.des+'</label></span>';
						str+='</label></div>';
						
					});
					$('#coupon_list').html(str);
				}
			});
		});

		$('#myTab a').click(function(event) {
		    $(":checkbox").attr("checked", false);
			tab = $(this).find('a').attr('data-id');
			$.post("{pigcms{:U('ajax_get_send_coupon')}", '', function(data, textStatus, xhr) {
				if(data == null || data == undefined || data == ''){
					$('#coupon_list').html('没有可用的优惠券');
				}else{
					var str='';
					$.each(data, function(coupon_id, val) {
						str += '<div class="radio"><label>';
						str+='<input class="paycheck ace store-list" type="checkbox" name="coupon_id[]" value="'+coupon_id+'" id="coupon'+coupon_id+'"/>';
						str+='<span class="lbl"><label for="coupon'+coupon_id+'">'+val.name+' - '+val.des+'</label></span>';
						str+='</label></div>';
						
					});
					$('#coupon_list').html(str);
				}
			});
		});
	});
</script>


<include file="Public:footer"/>
