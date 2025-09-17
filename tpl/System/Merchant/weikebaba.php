<include file="Public:header"/>
	<form id="myform"  frame="true" refresh="true">
		
		  <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_mer_pr" style="display: table;">
		   <tbody>
			
		 
			<tr>
				<th width="160">商家号</th>
				<td >
					 <input type="text" class="input-text" name="user_id" id="user_id" value="{pigcms{$data.user_id}" size="20"   />
				</td>
			</tr>
			<tr>
				<th width="160">微信公众号APPID</th>
				<td >
					 <input type="text" class="input-text" name="wxappid" id="wxappid" value="{pigcms{$data.wxappid}" size="20"   />
				</td>
			</tr>
			<tr>
				<th width="160">微信小程序APPID</th>
				<td >
					 <input type="text" class="input-text" name="wxappid_mini" id="wxappid_mini" value="{pigcms{$data.wxappid_mini}" size="20"   />
				</td>
			</tr>
			<tr>
				<th width="160">商家加密秘钥</th>
				<td >
					 <input type="text" class="input-text" name="key" id="key" value="{pigcms{$data.key}" size="20"   />
				</td>
			</tr>
		  
		  
			
		   </tbody>
		  </table>
		 
		<input type="hidden" name="mer_id" value="{pigcms{$_GET['mer_id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			
		</table>
		<div class="btn ">
			<input  type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			
		</div>
	</form>
<script>
$(function(){
			$('#dosubmit').click(function(){
				$.post('{pigcms{:U('Merchant/weikebaba')}', $('#myform').serialize(), function(data, textStatus, xhr) {
					//console.log(data);
					if(data.status==1){
						window.top.msg(2,data.info,true,2);
					}else{
						window.top.msg(0,data.info,true,2);
					}
					window.top.art.dialog({id:'edit_weikebaba'}).close();
				});
				return false;
			});
});
			
</script>
	
	<style>
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
<include file="Public:footer"/>