<include file="Public:header"/>

	<form id="myform"  frame="true" refresh="true">
		<ul class="tab_ul">

			<li class="active"><a style="width:775px;text-align: left;">例如：当您选择外卖业务线上架到平台后，该店铺的外卖业务线的所有商品将可以在平台售卖。如果选择外卖业务线不上架到平台，那么该店铺的外卖业务的所有商品将不会显示到平台，不会通过平台售卖。</a></li>

		</ul>
		  <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_mer_pr" style="display: table;">
		   <tbody>
			<tr>
			 <th width="160">{pigcms{$config['group_alias_name']}业务：</th>
			 <td>

				 <span class="cb-enable">
					<label class="cb-enable  <php>if($data['group_on_sale']==1){</php>selected<php>}</php>">
						<span>上架</span>
						<input type="radio" name="group_on_sale" value="1" <php>if($data['group_on_sale']==1){</php>checked="checked"<php>}</php>/>
					</label>
				</span>
				<span class="cb-disable">
					<label class="cb-disable  <php>if($data['group_on_sale']==2){</php>selected<php>}</php>">
						<span>不上架</span>
						<input type="radio" name="group_on_sale" value="2"   <php>if($data['group_on_sale']==2){</php>checked="checked"<php>}</php>/>
					</label>
				</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

			<tr>
				<th width="160">{pigcms{$config['meal_alias_name']}业务：</th>
				<td>
				 <span class="cb-enable">
					<label class="cb-enable  <php>if($data['foodshop_on_sale']==1){</php>selected<php>}</php>">
						<span>上架</span>
						<input type="radio" name="foodshop_on_sale" value="1" <php>if($data['foodshop_on_sale']==1){</php>checked="checked"<php>}</php>/>
					</label>
				</span>
				<span class="cb-disable">
					<label class="cb-disable  <php>if($data['foodshop_on_sale']==2){</php>selected<php>}</php>">
						<span>不上架</span>
						<input type="radio" name="foodshop_on_sale" value="2"   <php>if($data['foodshop_on_sale']==2){</php>checked="checked"<php>}</php>/>
					</label>
				</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</tr>
			<tr>
				<th width="160">{pigcms{$config['shop_alias_name']}业务：</th>
				<td>
				 <span class="cb-enable">
					<label class="cb-enable  <php>if($data['shop_on_sale']==1){</php>selected<php>}</php>">
						<span>上架</span>
						<input type="radio" name="shop_on_sale" value="1" <php>if($data['shop_on_sale']==1){</php>checked="checked"<php>}</php>/>
					</label>
				</span>
				<span class="cb-disable">
					<label class="cb-disable  <php>if($data['shop_on_sale']==2){</php>selected<php>}</php>">
						<span>不上架</span>
						<input type="radio" name="shop_on_sale" value="2"   <php>if($data['shop_on_sale']==2){</php>checked="checked"<php>}</php>/>
					</label>
				</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</tr>
			<tr>
				<th width="160">{pigcms{$config['mall_alias_name']}业务：</th>
				<td>
				 <span class="cb-enable">
					<label class="cb-enable  <php>if($data['mall_on_sale']==1){</php>selected<php>}</php>">
						<span>上架</span>
						<input type="radio" name="mall_on_sale" value="1" <php>if($data['mall_on_sale']==1){</php>checked="checked"<php>}</php>/>
					</label>
				</span>
				<span class="cb-disable">
					<label class="cb-disable  <php>if($data['mall_on_sale']==2){</php>selected<php>}</php>">
						<span>不上架</span>
						<input type="radio" name="mall_on_sale" value="2"   <php>if($data['mall_on_sale']==2){</php>checked="checked"<php>}</php>/>
					</label>
				</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</tr>
			
		
		   </tbody>
		  </table>

		<div class="btn ">
			<input type="hidden" name="store_id" value="{pigcms{$_GET['store_id']}"/>
			<input  type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		</div>
	</form>
	
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>

	
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
<include file="Public:footer"/>