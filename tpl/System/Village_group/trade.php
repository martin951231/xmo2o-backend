<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/trade')}" class="on">数据统计</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="" method="get" id="myform1">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="trade"/>
							
							<if condition="$now_area['area_type'] neq 3">
								<span class="fl">地区筛选：</span>
								<div class="fl" id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
							</if>
							

							<span class="fl" style="margin-left: 30px;">选择社区：</span>
							<span class="fl">全部社区</span><input type="radio"  class="input-text fl " data-type="all_village" name="village_list" value="-1" <if condition="$_GET['village_list'] eq -1 OR !isset($_GET['village_list'])">checked </if>>
							<span class="fl">部分社区</span><input type="radio"  class="input-text fl "  href="{pigcms{:U('select_village_checkbox')}" id="part_village" data-type="part_village" name="village_list"  <if condition="$_GET['village_list'] neq -1 AND !empty($_GET['village_list'])">checked value="{pigcms{$_GET['village_list']}" <else />value="0"</if> >
							<a href="javascript:void(0)" id="edit_village" style="display:none;float:left;line-height: 16px;
							 <if condition="$_GET['village_list'] neq -1 AND !empty($_GET['village_list'])">display:block</if>" data-type="part_village">编辑</a>

							
							<br>
							<span class="fl">日期筛选：</span>
							<input type="text" class="input-text fl" name="begin_time" style="width:120px;" id="d4311"  value="<if condition="$_GET['selectTimeType'] eq 1 AND empty($_GET['begin_time'])">{pigcms{:date('Y-m-d')}</if>{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text fl" name="end_time" style="width:120px;" id="d4311" value="<if condition="$_GET['selectTimeType'] eq 1 AND empty($_GET['end_time'])">{pigcms{:date('Y-m-d')}</if>{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
							<div class="fl time-choice-wrap cf stat-time-choice">
								<span class="time-btn time-choice  <if condition="($_GET['selectTimeType'] eq 1   AND empty($_GET['begin_time']) AND empty($_GET['end_time'])) OR (!isset($_GET['selectTimeType']) AND !isset($_GET['begin_time']) AND !isset($_GET['end_time']))">time-current</if>" timetype="1" id="choice-today">
								今天</span>
								<span class="time-btn time-choice bor-gray <if condition="$_GET['selectTimeType'] eq 7">time-current</if>" id="choice-week" timetype="7" >
								近7天</span>
								<span class="time-btn time-choice <if condition="$_GET['selectTimeType'] eq 30">time-current</if>" id="choice-month" timetype="30" >
								近30天</span>
								<input type="hidden" id="_selectTimeType" name="selectTimeType" value="{pigcms{$_GET['selectTimeType']}">
							</div>

							<span class="fl" style="margin-left: 30px;    margin-top: 3px;">支付方式筛选</span>
							<select id="pay_type" name="pay_type" style="    margin-top: -10px;">
								<option value="all" <if condition="'all' eq $_GET['pay_type']">selected="selected"</if>>全部支付方式</option>
								<volist name="pay_method" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $_GET['pay_type']">selected="selected"</if>>{pigcms{$vo.name}</option>
								</volist>
								<option value="balance" <if condition="'balance' eq $_GET['pay_type']">selected="selected"</if>>余额支付</option>
								<if condition="$config['open_pufa_pay'] && empty($pay_method['offline'])">
									<option value="offline" <if condition="$_GET['pay_type'] eq 'offline'">selected="selected"</if>>线下支付</option>
								</if>
							</select>
							<input type="submit" value="查询" class="button" style="margin-top: -8px;"/>　　
							<input type="reset" value="清空" class="button" id="reset" style="margin-top: -8px;"/>
							<a  href="javascript:void(0)" onclick="exports()" class="button" >导出</a>
						</form>
					</td>
				
				</tr>
			</table>
			
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					.statist-wrap{
						width: 15%;
						margin-right: 2.3%;
						padding:0 15px;
						border: 1px solid #ccc;
						border-radius: 5px;
						margin-bottom:15px;
					}
					.sta-mon{
					
						font-size: 25px;
						color: #333;
						width: 100%;
						text-align: center;
						white-space: nowrap;
						overflow: hidden;
						text-overflow: ellipsis;
						line-height: 1;
					}
					.sta-num{
						font-size: 18px;
						color: #999;
						width: 100%;
						text-align: center;
						white-space: nowrap;
						overflow: hidden;
						text-overflow: ellipsis;
					}
					
					.sta-total{
						background: #1c84c6;
						padding: 2px 7px;
						color: #fff;
					}
					.sta-trade{
						background: #23c6c8;
						padding: 2px 7px;
						color: #fff;
					}
					.sta-refund{
						background: #ed5565;
						padding: 2px 7px;
						color: #fff;
					}
					
					.time-choice-wrap {
						margin: 0 10px;
						border-radius: 3px;
						border: 1px solid #ccc
					}

					.time-choice {
						float: left;
						display: block;
						padding: 0 10px;
						line-height: 22px;
						font-size: 12px;
						color: #333;
						outline: 0;
						cursor: pointer
					}

					.time-choice.time-current {
						color: #fff;
						background: #1ab394
					}

					.bor-gray {
						border-left: 1px solid #ccc;
						border-right: 1px solid #ccc
					}
					.fl{
						line-height: 16px;
						margin-right:5px;
					}

					/* 清除浮动 */
					.ks-clear:after, .clear:after {
					    content: '\20';
					    display: block;
					    height: 0;
					    clear: both;
					}
					.ks-clear, .clear {
					    *zoom: 1;
					}
					</style>
					<div class="white-bg receivables-statist cf" style="padding: 15px;margin-bottom: 15px;">
						<h3 class="payment-titile clearfix">
							<span class="title-text">关键指标 <i id="layer-tip" class="layer-tip" style="border-radius: 50%;border: 1px solid orange;padding: 1px 5px;color: orange;">?</i></span>
							<div class="com-layer-box">
								<i class="icon-question-sign com-layer" ></i>
								<span class="com-layer-text dn" style="display:none">
									<p>1. 总成交额：实际支付的订单总额，含已退款金额</p>
									<p>2. 团长总数：当前筛选条件下的团长总数合计</p>
									<p>3. 总订单数：实际支付的订单总数，含已退款订单数</p>
									<p>4. 总佣金： 当前筛选条件下所有团长获得总佣金合计</p>
									<p>5. 总退款金额：实际退款的订单总金额</p>
								</span>
							</div>
						</h3>
						
						<div class="statist-wrap-list ks-clear">
								<div class="statist-wrap fl" style="margin-right:50px;">
									<p><span class="statist-wrap-title sta-total">总成交额</span></p>
									<p class="sta-mon">{pigcms{$total_money_sum|floatval}</p>
								</div>
								<div class="statist-wrap fl" style="margin-right:50px;">
									<p><span class="statist-wrap-title sta-trade">团长总数</span></p>
									<p class="sta-mon">{pigcms{$header_count}</p>
								</div>
								<div class="statist-wrap fl" style="margin-right:50px;">
									<p><span class="statist-wrap-title sta-trade">总订单数</span></p>
									<p class="sta-mon">{pigcms{$total_money_count|floatval}笔</p>
								</div>
								<div class="statist-wrap fl" style="margin-right:50px;">
									<p><span class="statist-wrap-title sta-trade">总佣金</span></p>
									<p class="sta-mon">{pigcms{$spread_money_sum|floatval}</p>
								</div>
							
								<div class="statist-wrap fl statist-wrap-spe" style="margin-right:50px;"> 
									<p><span class="statist-wrap-title sta-refund">总退款金额</span></p>
									<p class="sta-mon">{pigcms{$refund_money_sum|floatval}</p>
								</div>
						</div>
					</div>
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>日期</th>
								<th>社区</th>
								<th>总成交额</th>
								<th>总订单数</th>
								<th>总佣金</th>
								<th>总退款金额</th>
							</tr>
						</thead>
						<tbody style="">
							<if condition="is_array($sale_date)">
								<volist name="sale_date" id="vo">
									<tr>
										<td>{pigcms{$vo.date_id}</td>
										<td><php>echo $vo['name']?:'所属团长：'.$vo['header_name'];</php></td>
										<td>{pigcms{$vo.total_money|floatval}</td>
										<td>{pigcms{$vo.counts}</td>
										<td>{pigcms{$vo.spread_money|floatval}</td>
										<td>{pigcms{$vo.refund_money|floatval}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
					
				</div>
			</form>
		</div>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script>
$(function(){
	$('#reset').click(function(){
		window.location.href="{pigcms{:U(trade)}";
	})
		
	$('#layer-tip').on('mouseover',function(){
		//$('.com-layer-text').show();
		layer.tips($('.com-layer-text').html(), '#layer-tip',{
		  tips: 1
		});
	});
	$('#layer-tip').on('mouseout',function(){
		layer.closeAll()
	});
	$('.time-btn').click(function(){
		$('.time-btn').removeClass('time-current')
		$(this).addClass('time-current');
		$('input[name="begin_time"]').val('')
		$('input[name="end_time"]').val('')
		$('#_selectTimeType').val($(this).attr('timetype'))
		$('#myform1').submit();
	})
	$('input:radio').click(function(){
		var obj = $(this);
		
		var title= '选择社区'
		
		var type= $(obj).data('type');
		
		if(type=='part_village'){	
			art.dialog.data('village_list', $(obj).val());			
			var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&village_list='+$(obj).val();
			$('#edit_village').show();
			
			
			art.dialog.open(href,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					art.dialog.data('iframe_handle',iframe);
				},
				id: type,
				title:title,
				padding: 0,
				width: 600,
				height: 500,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4',
				close:function(){
					var village_list = art.dialog.data('village_list'); 
					$(obj).val(village_list)
					
				}
			});
						
		}else{
			if(type=='all_village'){	
				$('#edit_village').hide();
			}else{
				$('#edit_store').hide();
			}
		}
		
	})
	
	$('#edit_village').click(function(){
		var obj = $('#part_village');
		var title= '选择商家'
		var type= $(obj).data('type')
		art.dialog.data('village_list', $(obj).val());			
		var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&village_list='+$(obj).val();

		art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				art.dialog.data('iframe_handle',iframe);
			},
			id: type,
			title:title,
			padding: 0,
			width: 600,
			height: 500,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4',
			close:function(){
				  var village_list = art.dialog.data('village_list'); 
				  $(obj).val(village_list)
				  	$('#part_store').val(0)
					$("input[name='store_list']:eq(0)").attr("checked",'checked'); 
				$("input[name='store_list']:eq(1)").removeAttr("checked");
						$('#edit_store').hide();
			}
		});
	})
});

   	var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('trade_export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>