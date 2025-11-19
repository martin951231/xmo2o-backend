<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Percent/score')}" >积分设置</a>
			<a href="{pigcms{:U('Percent/other_score',array('tab'=>1))}" <if condition="$_GET['tab'] eq 1">class="on"</if>>会员日积分获得倍数设置</a>
			<a href="{pigcms{:U('Percent/other_score',array('tab'=>2))}" <if condition="$_GET['tab'] eq 2">class="on"</if>>会员生日获得积分倍数设置</a>
			<a href="{pigcms{:U('Percent/other_score',array('tab'=>3))}" <if condition="$_GET['tab'] eq 3">class="on"</if>>特殊日期积分获得倍数设置</a>
		
		</ul>
	</div>
	 <form id="myform" method="post" action="{pigcms{:U('other_score')}" refresh="true"> 
	   <table cellpadding="0" cellspacing="0" class="table_form" width="100%"  id="tab_user_score">
	   	<input type="hidden" class="input-text" name="tab"  value="{pigcms{$_GET['tab']}"  />
		<tbody>
		<!--  <tr>
		  <th width="160">会员日</th>
		  <td><input type="text" class="input-text" name="vip_day" id="config_vip_day" value="{pigcms{$config.vip_day}" size="10"  tips="会员日" onfocus="WdatePicker({readOnly:true,dateFmt:'MM-dd'})"/></td>
		 </tr> -->
		 <if condition="$_GET['tab'] eq 1">
		 <tr>
		  	<th width="160">会员日类型</th>
		  	<td>
			  	<select name="vip_day_type" id="vip_day_type" onchange="change_vip_dat_type()">
			  		<option value="1" <if condition="$config['vip_day_type'] eq 1">selected=selected</if> >每周几积分倍数获取形式</option>
			  		<option value="2" <if condition="$config['vip_day_type'] eq 2">selected=selected</if>>每月n号结尾的日期积分倍数获取形式</option>
			  	</select>
			</td>
		 </tr>
		 <tr>
		  	<th width="160">会员日设置</th>
		  	<td>
			  	<select name="vip_day" id="vip_day">
			  		<volist name="week" id="vo">
			  		<option value="{pigcms{$key}">{pigcms{$vo}</option>
			  		</volist>
			  	</select>
			  </td>
		 </tr>
		 
		  <tr>
		  <th width="160">会员日获得积分倍数</th>
		  <td><input type="text" class="input-text" name="vipday_score_times" id="config_vipday_score_times" value="{pigcms{$config.vipday_score_times}" size="10"   validate="number:true" tips="会员日当天获得积分是平台获得积分的多少倍" /></td>
		 </tr>
		 </if>


		 <if condition="$_GET['tab'] eq 2">
		 <tr>
		  <th width="160">会员生日当天获得积分倍数</th>
		  <td><input type="text" class="input-text" name="birthday_score_times" id="config_birthday_score_times" value="{pigcms{$config.birthday_score_times}" size="10" validate="number:true" tips="会员生日当天获得积分是平台获得积分的多少倍" /></td>
		 </tr>
		 </if>
		 
		 <if condition="$_GET['tab'] eq 3">
		 <if condition="$score_times">
			<volist name="score_times" id="vo">
				<tr class="plus">
				  <th width="160"><span class="sort">{pigcms{$i}</span> 特殊日期获得积分倍数</th>
				  <td>
					<input type="hidden" name="id[]" value="{pigcms{$vo.id}">
				  <input type="text" class="input-text" name="score_times_spday_start[]" value="{pigcms{$vo.start}" size="10"  onfocus="WdatePicker({readOnly:true,dateFmt:'MM-dd'})"/>至
				  
				  <input type="text" class="input-text" name="score_times_spday_end[]" value="{pigcms{$vo.end}" size="10"  onfocus="WdatePicker({readOnly:true,dateFmt:'MM-dd'})" />时间内，消费获得平台积分倍数为
				  
				  <input type="text" class="input-text" name="score_times_spday_value[]" value="{pigcms{$vo.value|intval}" size="10" validate="number:true" tips="特殊日期获得积分倍数" />
				  
				  <button class="button add" onclick="add();return false;" <if condition="$i neq count($score_times)">style="display:none"</if> >增加</button>
				  <button class="button del" onclick="del(this);return false;" <if condition="count($score_times) eq 1">style="display:none;" </if> >删除</button>
				  </td>
				 </tr>
			</volist>
		 <else />
		
		 <tr class="plus">
		  <th width="160"><span class="sort">1</span> 特殊日期获得积分倍数</th>
		  <td>
			
		  <input type="text" class="input-text" name="score_times_spday_start[]" value="" size="10"  onfocus="WdatePicker({readOnly:true,dateFmt:'MM-dd'})"/>至
		  
		  <input type="text" class="input-text" name="score_times_spday_end[]" value="" size="10"  onfocus="WdatePicker({readOnly:true,dateFmt:'MM-dd'})" />时间内，消费获得平台积分倍数为
		  
		  <input type="text" class="input-text" name="score_times_spday_value[]" value="" size="10" validate="number:true" tips="特殊日期获得积分倍数" />
		  
		  <button class="button add" onclick="add();return false;">增加</button>
		  <button class="button del" onclick="del(this);return false;" style="display:none;">删除</button>
		  </td>
		 </tr>
		
         </if>

		 </if>
		</tbody>
	   </table> 
	   <div class="btn" style="margin-top:20px;"> 
		<input type="submit" name="dosubmit" value="提交" class="button" /> 
		
	   </div> 
	</form> 
</div>
<script>
	$(function(){
		var data_rate_arr = [];
		var test = $('.input-text').each(function(index,val){
			var ids = $(val).attr('id');
			data_rate_arr[ids] = $(val).val()
			
		});
		change_vip_dat_type()
		
	});

	function add(){
		var item = $('.plus:last');
		$(item).find('.add').hide();
		$(item).find('.del').hide();
		if($('.plus').length<=1&&$('.plus').css('display')=='none'){
			$('.plus').show();
		}else{
			var newitem = $(item).clone(true);
			var No = parseInt(item.find(".sort").html())+1;
			$(item).after(newitem);
			newitem.find('input').attr('value','');
			newitem.find(".sort").html(No);
		
			$('.del').show();
			newitem.find('.add').show();
			
		}
		// newitem.find('input[name="url[]"]').attr('id','url'+No);
	}
	
	function del(obj){
		if($('.plus').length<=1){
			$('.del').hide();
		}else{
			$(obj).parents('.plus').remove();
			$.each($('.plus'), function(index, val) {
				var No =index+1;
				$(val).find('.sort').html(No);
				$(val).find('input[name="url[]"]').attr('id','url'+No);
				
			});
			$('.plus:last').find('.add').show()
			if($('.plus').length<=1){
				$('.del').hide();
			}
		}
	}

	function change_vip_dat_type(){
		var vip_day_type = $('#vip_day_type').val();
		var week = $.parseJSON('{pigcms{$week}');
		var day = $.parseJSON('{pigcms{$day}');
		var vip_day = parseInt('{pigcms{$config.vip_day}');

		console.log(vip_day_type)
		if (vip_day_type==1) {
			var day_list = week;
		}else if(vip_day_type==2){
			var day_list = day;
		}
		if (day_list!='undefined') {
			var html = '<option value="-1">不设置</option>';
			$.each(day_list,function(i,e){
				if (vip_day==i) {
					html += '<option value="'+i+'" selected=selected>'+e+'</option>';
				}else{
					html += '<option value="'+i+'">'+e+'</option>';					
				}
			})
		}
		$('#vip_day').html(html);
	}
</script>
<style>
	.table_form{border:1px solid #ddd;}
	.tab_ul{margin-top:20px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
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
		min-width: 100px;
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
<include file="Public:footer"/>