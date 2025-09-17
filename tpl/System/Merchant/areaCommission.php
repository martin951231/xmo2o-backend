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
	<form id="myform"  frame="true" refresh="true" mycheck="true">
		  <ul class="tab_ul">
		  <li <if condition="$_GET['type'] eq 'merchant'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'merchant','mer_id'=>$mer_id))}" >店铺</a></li>	
		  <li <if condition="$_GET['type'] eq 'group'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'group','mer_id'=>$mer_id))}" >{pigcms{$config.group_alias_name}</a></li>
			<if condition="$config['shop_alias_name']">
				<li <if condition="$_GET['type'] eq 'shop'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'shop','mer_id'=>$mer_id))}" >{pigcms{$config.shop_alias_name}</a></li>
			</if>
			
			<if condition="$config['pay_in_store']">
				<li <if condition="$_GET['type'] eq 'shop_offline'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'shop_offline','mer_id'=>$mer_id))}" >线下零售</a></li>
			</if>
			<if condition="$config['meal_alias_name']">
				<li <if condition="$_GET['type'] eq 'meal'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'meal','mer_id'=>$mer_id))}" >{pigcms{$config.meal_alias_name}</a></li>
			</if>
			<if condition="$config['appoint_alias_name']">
				<li <if condition="$_GET['type'] eq 'appoint'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'appoint','mer_id'=>$mer_id))}" >{pigcms{$config.appoint_alias_name}</a></li>
			</if>
			
			<if condition="$config['is_cashier'] OR $config['pay_in_store']">
				<li <if condition="$_GET['type'] eq 'store'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'store','mer_id'=>$mer_id))}" >{pigcms{$config.cash_alias_name}</a></li>
                <li <if condition="$_GET['type'] eq 'cash'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'cash','mer_id'=>$mer_id))}" >到店消费</a></li>
            </if>

            <if condition="$config['mall_alias_name_new']">
				<li <if condition="$_GET['type'] eq 'mall'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/areaCommission',array('type'=>'mall','mer_id'=>$mer_id))}" >{pigcms{$config.mall_alias_name_new}</a></li>
			</if>

		  </ul>

		  <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_mer_pr" style="display: table;">
		   <tbody>

            <tr>
			 <th width="200">区域代理佣金单独配置：</th>
			 <td>
                <span class="cb-enable">
                    <label class="cb-enable <if condition="$percent['area_percent_open'] eq 1">selected</if>">
                        <span>设置</span>
                        <input type="radio" name="{pigcms{$_GET['type']}_area_percent_open" value="1" <if condition="$percent['area_percent_open'] eq 1">checked="checked"</if>/>
                    </label>
                </span>			     
                <span class="cb-disable">
                    <label class="cb-disable <if condition="$percent['area_percent_open'] eq 0">selected</if>">
                        <span>跳过</span>
                        <input type="radio" name="{pigcms{$_GET['type']}_area_percent_open" value="0" <if condition="$percent['area_percent_open'] eq 0">checked="checked"</if>/>
                    </label>
                </span>
                <input type="hidden" tips="“开启”则区域代理商佣金比例将按照当前设置的计算。“跳过”则读取上一级。"/>
             </td>
			</tr>

            <tr>
                <th style="color: red;text-align: center;" colspan="2">以下设置只有当“设置”区域代理商佣金单独配置才生效</th>
            </tr>
            <tr>
                <th>区域代理商</th>
                <td>佣金比例(单位：%)</td>
            </tr>

            <tr>
                <th>省级</th>
                <td><input type="text" class="input-text" name="{pigcms{$_GET['type']}_province_percent" value="{pigcms{$percent.province_percent}" validate="number:true,maxlength:6,min:0,max:100" tips="请填写一个百分比数字，不需要增加%"/></td>
            </tr>

            <tr>
                <th>市级</th>
                <td><input type="text" class="input-text" name="{pigcms{$_GET['type']}_city_percent" value="{pigcms{$percent.city_percent}" validate="number:true,maxlength:6,min:0,max:100" tips="请填写一个百分比数字，不需要增加%"/></td>
            </tr>

            <tr>
                <th>区级</th>
                <td><input type="text" class="input-text" name="{pigcms{$_GET['type']}_area_percent" value="{pigcms{$percent.area_percent}" validate="number:true,maxlength:6,min:0,max:100" tips="请填写一个百分比数字，不需要增加%"/></td>
            </tr>
		

		   </tbody>
		  </table>
		<div class="btn">
			<input  type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" style="float: right;margin-right: 15px;"/>
		</div>
	</form>

		
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<include file="Public:footer"/>