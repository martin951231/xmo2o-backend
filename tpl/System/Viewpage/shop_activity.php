<include file="Public:header"/>
	<style>
		.line_m{/*text-decoration:line-through*/text-align:center} 
			.table_form{border:1px solid #ddd;}
			.tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
			.tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
			.tab_ul>li>a {
				position: relative;
				display: block;
				padding: 10px 15px;
				margin-right: 2px;
				line-height: 1.42857143;
				border: 1px solid transparent;
				border-radius: 4px 4px 0 0;
				padding: 7px 12px 8px;
				min-width: 50px;
				text-align: center;
			}
			.tab_ul>li>a, .tab_ul>li>a:focus {
				border-radius: 0!important;
				border-color: #c5d0dc;
				background-color: #F9F9F9;
				color: #999;
				margin-right: -1px;
				line-height: 18px;
				position: relative;
			}
			.tab_ul>li>a:focus, .tab_ul>li>a:hover {
				text-decoration: none;
				background-color: #eee;
			}
			.tab_ul>li>a:hover {
				border-color: #eee #eee #ddd;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li>a:hover {
				background-color: #FFF;
				color: #4c8fbd;
				border-color: #c5d0dc;
			}
			.tab_ul>li:first-child>a {
				margin-left: 0;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul:before,.tab_ul:after{
				content: " ";
				display: table;
			}
			.tab_ul:after{
				clear: both;
			}
		</style>
	<form id="myform" method="post" action="{pigcms{:U('guess_like_post')}" frame="true" refresh="true" class="frame_form ">
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab1" style="display: table;">
			<tr>
				<th width="80">添加活动</th>
				<td>
					<input type="hidden" name="index_guess_like_activity" value="{pigcms{$config_data.index_guess_like_desc}"/>
					<input type="button" value="添加活动" class="button" onclick="add_activity()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">
					<a></a>
					<table style="width:900px;" class="table_form" id='activity_box'>
						<thead>
						<tr >
							<th width="10%" style="text-align: left;">活动标题</th>
							<th width="10%" style="text-align: left;">活动价格</th>
							<th width="10%" style="text-align: left;">活动描述</th>
							<th width="15%" style="text-align: left;">所属店铺/商家</th>
							<th width="20%" style="text-align: left;">活动图片</th>
							<th width="10%" style="text-align: left;">图片布局形式</th>
							<th width="15%" style="text-align: left;">操作</th>
						</tr>
						</thead>
						<tbody>
						<volist name="activity_list" id="vo">
							<tr>
								<td>{pigcms{$vo.title}</td>
								<td>{pigcms{$vo.price}</td>
								<td>{pigcms{$vo.description}</td>
								<td>{pigcms{$vo.store_name}/{pigcms{$vo.mer_name}</td>
								<td>
									<volist name="vo['image']" id="img">
										<img src="{pigcms{$img}" style="width: 50px;height: 50px;margin-left: 4px;">
									</volist>
								</td>
								<td><php>if($vo['show_type']==0){</php>平铺3张 <php>}else{</php>1大2小<php>}</php></td>
								<td>
									<button type="button" onClick="add_activity({pigcms{$vo.id})">编辑</button>
									<button type="button" onClick="delThis(this,{pigcms{$vo.id})">移除</button>
								</td>
							</tr>
						</volist>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>

	<script>
		$(function(){
			var pay_rate = $('.pay_rate');
			var open_pay_percent=$('.pay_percent').find('input').val();
			
	
			$('.pay_percent').find('input').blur(function(){
				open_pay_percent = $(this).val();
			});
			
			$('input[name="open_pay_rate"]').click(function(){
				if($(this).val()==1){
					pay_rate.show();
					if(open_pay_percent<0){
						$('.pay_percent').find('input').val('');
					}else{
						$('.pay_percent').find('input').val(open_scan_percent);
					}
				}else{
					pay_rate.hide();
					$('.pay_percent').find('input').attr('value',-1);
				}
			});
		});

		function add_activity(id=''){
			var url = '';
			url = '{pigcms{:U("activity_edit",array("business_type"=>"shop"))}' + '&id='+id;
				window.top.artiframe(url,'添加活动',720,600,true,false,false,editbtn,'activity_edit',true,
				function(){
					$("#activity_box tbody").empty();
					$.post("{pigcms{:U('activity_list',array('business_type'=>'shop'))}",{},function(data){

							console.log(data)
						if(data.list.length > 0){
							var html = '';
							data = data.list;
							for(var i = 0; i< data.length; i++){
								console.log(i)
								html += '<tr>';
								html += '	<td>'+data[i].title+'</td>';
								html += '	<td>'+data[i].price+'</td>';
								html += '	<td>'+data[i].description+'</td>';
								html += '	<td>'+data[i].store_name+'/'+data[i].mer_name+'</td>';
								html += '	<td>';

								for(var j = 0; j< data[i].image.length; j++){
									html += '	<img src="'+data[i].image[j]+'" style="width: 50px;height: 50px">';
								}

								html += '	</td>';
								html += '	<td>'+(data[i].show_type==0 ? '平铺3张' : '1大2小')+'</td>';
								html += '	<td>';
								html += '		<button type="button" onClick="add_activity('+data[i].id+')">编辑</button>';
								html += '		<button type="button" onClick="delThis(this,'+data[i].id+')">移除</button>';
								html += '	</td>';
								html += '</tr>';
							}
							console.log(html)
							$("#activity_box tbody").append(html);
						}
					},'json');
			});
			return false;
		}

		function delThis(t,id){
			if(confirm('{pigcms{:L_("你确定要移除此活动吗？")}')){
				$.post("{pigcms{:U('activity_del')}",{id:id},function(data){
					if(data.error > 0){
						layer.alert(data.msg)
					}else{
						layer.alert(data.msg)
						$(t).parents("tr").eq(0).remove();
					}
				},'json');
			}
		}

		var _selected_goods = '{pigcms{$config_data["index_guess_like_goods_shop_goods"]}';
		function select_goods(){
			layer.open({
				type: 2,
				area: ['90%', '90%'],
				fixed: false,
				offset:'10px',
				maxmin: true,
				content: '{pigcms{:U("select_goods")}'+'&selected='+_selected_goods,
				btn: ['确定', '取消'],
				yes: function(index, layero){
					var frameId = $(layero).find("iframe").attr('id');
					var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
					_selected_goods = selected;
					$("#goods_spec tbody").empty();

					// 渲染商品
					ajaxGetGoods(selected);

				    layer.close(index);
				},
				btn2: function(index){
					layer.close(index);
				}
			});
		}

		// 渲染商品
		function ajaxGetGoods(selected){
			$.post("{pigcms{:U('ajaxGetGoods')}",{selected:selected},function(data){
				if(data.length > 0){
					for(var i = 0; i< data.length; i++){
						$("#goods_spec tbody").append('<tr>'+
							'<input type="hidden" name="relation_goods_id[]" value="'+data[i].goods_id+'" />'+
							'<td>'+data[i].goods_id+'</td>'+
							// '<td>'+data[i].model+'</td>'+
							'<td>'+data[i].name+'</td>'+
							'<td>'+data[i].price+'</td>'+
							'<td>'+
									'<button type="button" onClick="delThis(this)">{pigcms{:L_("移除")}</button>'+
							'</td>'+
						'</tr>');
					}
				}
			},'json');
		}

		// 初始化加载商品列表
		ajaxGetGoods(_selected_goods);

		// 移除商品
		function delGoods(t){
			if(confirm('{pigcms{:L_("你确定要移除此商品吗？")}')){
				var goods_id = $(t).parents("tr").find('[name="relation_goods_id[]"]').val();
				var type = $(t).parents("tr").find('[name="relation_type[]"]').val();
				var _selected_goods_arr = _selected_goods.split(',');
				var _selected_goods_arr_new = [];
				for(var i = 0;i< _selected_goods_arr.length;i++){
					if(_selected_goods_arr[i] != type+'-'+goods_id){
						_selected_goods_arr_new.push(_selected_goods_arr[i]);
					}
				}
				_selected_goods = _selected_goods_arr_new.join(",");
				$(t).parents("tr").eq(0).remove();
			}
		}
	</script>
<include file="Public:footer"/>