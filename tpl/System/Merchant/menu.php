<include file="Public:header"/>
	<form id="myform" action="{pigcms{:U('savemenu')}"  frame="true" refresh="true">
		<input type="hidden" name="mer_id" value="{pigcms{$merchant.mer_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr><td colspan="2"><label><input type="checkbox" id="all"/> 全选</label></td></tr>
		<volist name="menus" id="rowset">
			<tr>
				<th width="20%" style=" font-weight:bold; font-size:14px">
					<label><!--input type="checkbox" class="menu_{pigcms{$rowset['id']} father_menu" value="{pigcms{$rowset['id']}" name="menus[]" <if condition="in_array($rowset['id'], $merchant['menus'])">checked</if>/-->&nbsp;{pigcms{$rowset['name']}</label>
				</th>
			</tr>
			<if condition="$rowset['menu_list']">
				<volist name="rowset['menu_list']" id="row">
				<tr style="font-weight:bold;">
					<td>
							<label><input type="checkbox" class="menu_{pigcms{$row['id']} father_menu" value="{pigcms{$row['id']}"  name="menus[]" data-fid="{pigcms{$row['fid']}"  <if condition="in_array($row['id'], $merchant['menus'])">checked</if> />&nbsp;{pigcms{$row['name']}</label>
							<if condition='$row["menu_list"]'>
								<tr style=" background:#fff; ">
									<td style=" padding-left:35px;">
										<volist name="row['menu_list']" id="srow">
											<label><input type="checkbox" class="child_menu_{pigcms{$srow['fid']} child_menu" value="{pigcms{$srow['id']}"  name="menus[]" data-fid="{pigcms{$srow['fid']}"  <if condition="in_array($srow['id'], $merchant['menus'])">checked</if> />&nbsp;{pigcms{$srow['name']}</label>
										</volist>
									</td>
								</tr>
							</if>
					</td>
				</tr>
				</volist>
			</if>
		</volist>
		</table>
		<div class="btn">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		</div>
	</form>
<style>
.frame_form td label{
	margin: 5px;
    display: inline-block;
}
</style>


<script type="text/javascript">
$(document).ready(function(){
	
	
/* 	$('#dosubmit').click(function(){
		$.post('/admin.php?g=System&c=Merchant&a=savemenu', $('#myform').serialize(), function(data, textStatus, xhr) {
			if(data.status==1){
				window.o2oTop.msg(2,data.info,true,2);
			}else{
				window.o2oTop.msg(0,data.info,true,2);
			}
			
			window.o2oTop.art.dialog({id:'menu'}).close();
		});
		
	}); */

	$('#all').click(function(){
		if ($(this).attr('checked')) {
			$('.father_menu, .child_menu').attr('checked', true);
		} else {
			$('.father_menu, .child_menu').attr('checked', false);
		}
	});
	$('.father_menu').click(function(){
		var fid = $(this).val();
		if ($(this).attr('checked')) {
			$('.child_menu_' + fid).attr('checked', true);
		} else {
			$('.child_menu_' + fid).attr('checked', false);
		}
		 check_all()
	});
	$('.child_menu').click(function(){
		var fid = $(this).attr('data-fid');
		if ($(this).attr('checked')) {
			$('.menu_' + fid).attr('checked', true);
		} else {
			var flag = false;
			$('.child_menu_' + fid).each(function(){
				if ($(this).attr('checked')) {
					flag = true;
				}
			});
			$('.menu_' + fid).attr('checked', flag);
		}
		 check_all()
	});
});


function check_all(){
	var flags = true;
	
	$(".father_menu").each(function(){
	
		if ($(this).is(':checked')) {
			
		}else{
			console.log($(this).val())
			flags = false;
		}
	});
	
	$(".child_menu").each(function(){
	
		if ($(this).is(':checked')) {
			
		}else{
	
			flags = false;
		}
	});

	$('#all').attr('checked', flags);
	
	
}
</script>
<include file="Public:footer"/>