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
		 
			<li <if condition="$_GET['type'] eq 'merchant'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'merchant','mer_id'=>$mer_id))}" >商家抽成设置</a></li>
			<if condition="$config['group_alias_name']">
				<li <if condition="$_GET['type'] eq 'group'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'group','mer_id'=>$mer_id))}" >{pigcms{$config.group_alias_name}</a></li>
			</if>
			<if condition="$config['shop_alias_name']">
				<li <if condition="$_GET['type'] eq 'shop'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'shop','mer_id'=>$mer_id))}" >{pigcms{$config.shop_alias_name}</a></li>
			</if>
			<if condition="$config['mall_alias_name']">
				<li <if condition="$_GET['type'] eq 'mall'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'mall','mer_id'=>$mer_id))}" >{pigcms{$config.mall_alias_name}</a></li>
			</if>
			<if condition="$config['pay_in_store']">
				<li <if condition="$_GET['type'] eq 'shop_offline'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'shop_offline','mer_id'=>$mer_id))}" >{pigcms{$config.shop_alias_name}线下零售</a></li>
			</if>
			<if condition="$config['meal_alias_name']">
				<li <if condition="$_GET['type'] eq 'meal'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'meal','mer_id'=>$mer_id))}" >{pigcms{$config.meal_alias_name}</a></li>
			</if>
			<if condition="C('config.appoint_page_row')">
				<li <if condition="$_GET['type'] eq 'appoint'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'appoint','mer_id'=>$mer_id))}" >{pigcms{$config.appoint_alias_name}</a></li>
			</if>
			
			<if condition="$config['wxapp_url']"><li <if condition="$_GET['type'] eq 'wxapp'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'wxapp','mer_id'=>$mer_id))}" >营销</a></li>
			</if>
			<if condition="$config['is_open_weidian']"><li <if condition="$_GET['type'] eq 'weidian'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'weidian','mer_id'=>$mer_id))}" >微店</a></li>
			</if>
			<if condition="$config['is_cashier'] OR $config['pay_in_store']">
				<li <if condition="$_GET['type'] eq 'store'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'store','mer_id'=>$mer_id))}" >{pigcms{$config.cash_alias_name}</a></li>
				<li <if condition="$_GET['type'] eq 'cash'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'cash','mer_id'=>$mer_id))}" >到店消费</a></li>
			</if>
			<if condition="empty($config['single_system'])">
				<li <if condition="$_GET['type'] eq 'activity'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_percent',array('type'=>'activity','mer_id'=>$mer_id))}" >平台活动</a></li>
			</if>
		  </ul>
		  <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_mer_pr" style="display: table;">
		   <tbody>
		   <php> if($_GET['type']=='meal' && $config['open_meal_scan_percent'] == 1){</php>
			<tr>
			 <th width="160">商家扫码抽成比例：</th>
			 <td>
			 <span class="cb-enable">
				<label class="cb-enable  <php>if($mer_pr[$_GET['type'].'_scan_percent']>=0&&$mer_pr[$_GET['type'].'_scan_percent']!=''){</php>selected<php>}</php>">
					<span>设置</span>
					<input type="radio" name="open_scan_percent" value="1" <php>if($mer_pr[$_GET['type'].'_scan_percent']>=0&&$mer_pr[$_GET['type'].'_scan_percent']!=''){</php>checked="checked"<php>}</php>/>
				</label>
			</span>			     
			<span class="cb-disable">
				<label class="cb-disable  <php>if($mer_pr[$_GET['type'].'_scan_percent']<0||$mer_pr[$_GET['type'].'_scan_percent']==''){</php>selected<php>}</php>">
					<span>跳过</span>
					<input type="radio" name="open_scan_percent" value="0"   <php>if($mer_pr[$_GET['type'].'_scan_percent']<0||$mer_pr[$_GET['type'].'_scan_percent']==''){</php>checked="checked"<php>}</php>/>
				</label>
			</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			
			 <input type="text" id="scan_percent" class="input-text" name="<php>echo $_GET['type'].'_scan_percent';</php>"  value="<php>echo $mer_pr[$_GET['type'].'_scan_percent'];</php>" size="10" validate="number:true,max:100" tips="商家独立抽成比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前业务设置，向上查找<php>if($_GET['type']=='merchant'){echo '系统的相关业务';}else{echo '商家抽成设置';}</php>的抽成比例）" <php>if($mer_pr[$_GET['type'].'_scan_percent']<0||$mer_pr[$_GET['type'].'_scan_percent']==''){</php>style="display:none"<php>}</php> /></td>
			</tr>
		   <php> }</php>
			<tr>
			 <th width="160">商家抽成比例：</th>
			 <td>
			 <span class="cb-enable">
				<label class="cb-enable  <php>if($mer_pr[$_GET['type'].'_percent']>=0&&$mer_pr[$_GET['type'].'_percent']!=''){</php>selected<php>}</php>">
					<span>设置</span>
					<input type="radio" name="open_percent" value="1" <php>if($mer_pr[$_GET['type'].'_percent']>=0&&$mer_pr[$_GET['type'].'_percent']!=''){</php>checked="checked"<php>}</php>/>
				</label>
			</span>			     
			<span class="cb-disable">
				<label class="cb-disable  <php>if($mer_pr[$_GET['type'].'_percent']<0||$mer_pr[$_GET['type'].'_percent']==''){</php>selected<php>}</php>">
					<span>跳过</span>
					<input type="radio" name="open_percent" value="0"   <php>if($mer_pr[$_GET['type'].'_percent']<0||$mer_pr[$_GET['type'].'_percent']==''){</php>checked="checked"<php>}</php>/>
				</label>
			</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			
			 <input type="text" id="percent" class="input-text" name="<php>echo $_GET['type'].'_percent';</php>"  value="<php>echo $mer_pr[$_GET['type'].'_percent'];</php>" size="10" validate="number:true,max:100" tips="商家独立抽成比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前业务设置，向上查找<php>if($_GET['type']=='merchant'){echo '系统的相关业务';}else{echo '商家抽成设置';}</php>的抽成比例）" <php>if($mer_pr[$_GET['type'].'_percent']<0||$mer_pr[$_GET['type'].'_percent']==''){</php>style="display:none"<php>}</php> /></td>
			</tr>

			<php> if($config['open_old_age_pension'] == '1' && in_array($_GET['type'], ['mall','group','store','merchant'])){</php>
			<tr>
			 <th width="160">养老金比例：</th>
			 <td>		     
			
			 <input type="text" class="input-text" name="<php>echo 'oap_'.$_GET['type'].'_percent';</php>"  value="<php>echo $mer_pr['oap_'.$_GET['type'].'_percent'];</php>" size="10" validate="number:true,max:100" tips="养老金获得比例，填写一个100以内的数字，不要填写%，不设置则跳过当前业务，向上查找" />
			</td>
			</tr>
			<php>}</php>

           <if condition="$_GET['type'] eq 'merchant'">
           <tr>
               <th width="160">新大陆pos抽成比例：</th>
               <td>
			 <span class="cb-enable">
				<label class="cb-enable  <php>if($mer_pr['newlandpos_percent']>=0&&$mer_pr['newlandpos_percent']!=''){</php>selected<php>}</php>">
					<span>设置</span>
					<input type="radio" name="open_newlandpos_percent" value="1" <php>if($mer_pr['newlandpos_percent']>=0&&$mer_pr['newlandpos_percent']!=''){</php>checked="checked"<php>}</php>/>
				</label>
			</span>
                   <span class="cb-disable">
				<label class="cb-disable  <php>if($mer_pr['newlandpos_percent']<0||$mer_pr['newlandpos_percent']==''){</php>selected<php>}</php>">
					<span>跳过</span>
					<input type="radio" name="open_newlandpos_percent" value="0"   <php>if($mer_pr['newlandpos_percent']<0||$mer_pr['newlandpos_percent']==''){</php>checked="checked"<php>}</php>/>
				</label>
			</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                   <input type="text" id="open_newlandpos_percent_input" class="input-text" name="newlandpos_percent"  value="<php>echo $mer_pr['newlandpos_percent'];</php>" size="10" validate="number:true,max:100" tips="商家独立抽成比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前业务设置，向上查找<php>if($_GET['type']=='merchant'){echo '系统的相关业务';}else{echo '商家抽成设置';}</php>的抽成比例）" <php>if($mer_pr['newlandpos_percent']<0||$mer_pr['newlandpos_percent']==''){</php>style="display:none"<php>}</php> /></td>
           </tr>
           </if>


           <if condition="$_GET['type'] eq 'shop'">
               <tr>
                   <th width="160">平台配送抽成比例：</th>
                   <td>
                     <span class="cb-enable">
                        <label class="cb-enable  <php>if($mer_pr['shop_plat_deliver_percent']>=0&&$mer_pr['shop_plat_deliver_percent']!=''){</php>selected<php>}</php>">
                            <span>单独设置</span>
                            <input type="radio" name="plat_deliver" value="1" <php>if($mer_pr['shop_plat_deliver_percent']>=0&&$mer_pr['shop_plat_deliver_percent']!=''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>
                    <span class="cb-disable">
                        <label class="cb-disable  <php>if($mer_pr['shop_plat_deliver_percent']<0||$mer_pr['shop_plat_deliver_percent']==''){</php>selected<php>}</php>">
                            <span>以总的设置为准</span>
                            <input type="radio" name="plat_deliver" value="0"   <php>if($mer_pr['shop_plat_deliver_percent']<0||$mer_pr['shop_plat_deliver_percent']==''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" id="plat_deliver_input" class="input-text" name="shop_plat_deliver_percent"  value="<php>echo getFormatNumber($mer_pr['shop_plat_deliver_percent']);</php>" size="10" validate="number:true,max:100" tips="商家独立抽成比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前业务设置，向上查找<php>if($_GET['type']=='merchant'){echo '系统的相关业务';}else{echo '商家抽成设置';}</php>的抽成比例）" <php>if($mer_pr['shop_plat_deliver_percent']<0||$mer_pr['shop_plat_deliver_percent']==''){</php>style="display:none"<php>}</php> /></td>
               </tr>
               <tr>
                   <th width="160">商家配送抽成比例：</th>
                   <td>
                     <span class="cb-enable">
                        <label class="cb-enable  <php>if($mer_pr['shop_merchant_deliver_percent']>=0&&$mer_pr['shop_merchant_deliver_percent']!=''){</php>selected<php>}</php>">
                            <span>单独设置</span>
                            <input type="radio" name="merchant_deliver" value="1" <php>if($mer_pr['shop_merchant_deliver_percent']>=0&&$mer_pr['shop_merchant_deliver_percent']!=''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>
                       <span class="cb-disable">
                        <label class="cb-disable  <php>if($mer_pr['shop_merchant_deliver_percent']<0||$mer_pr['shop_merchant_deliver_percent']==''){</php>selected<php>}</php>">
                            <span>以总的设置为准</span>
                            <input type="radio" name="merchant_deliver" value="0"   <php>if($mer_pr['shop_merchant_deliver_percent']<0||$mer_pr['shop_merchant_deliver_percent']==''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                       <input type="text" id="merchant_deliver_input" class="input-text" name="shop_merchant_deliver_percent"  value="<php>echo getFormatNumber($mer_pr['shop_merchant_deliver_percent']);</php>" size="10" validate="number:true,max:100" tips="商家独立抽成比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前业务设置，向上查找<php>if($_GET['type']=='merchant'){echo '系统的相关业务';}else{echo '商家抽成设置';}</php>的抽成比例）" <php>if($mer_pr['shop_merchant_deliver_percent']<0||$mer_pr['shop_merchant_deliver_percent']==''){</php>style="display:none"<php>}</php> /></td>
               </tr>
               <tr>
                   <th width="160">快递配送抽成比例：</th>
                   <td>
                     <span class="cb-enable">
                        <label class="cb-enable  <php>if($mer_pr['shop_express_deliver_percent']>=0&&$mer_pr['shop_express_deliver_percent']!=''){</php>selected<php>}</php>">
                            <span>单独设置</span>
                            <input type="radio" name="express_deliver" value="1" <php>if($mer_pr['shop_express_deliver_percent']>=0&&$mer_pr['shop_express_deliver_percent']!=''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>
                       <span class="cb-disable">
                        <label class="cb-disable  <php>if($mer_pr['shop_express_deliver_percent']<0||$mer_pr['shop_express_deliver_percent']==''){</php>selected<php>}</php>">
                            <span>以总的设置为准</span>
                            <input type="radio" name="express_deliver" value="0"   <php>if($mer_pr['shop_express_deliver_percent']<0||$mer_pr['shop_express_deliver_percent']==''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                       <input type="text" id="express_deliver_input" class="input-text" name="shop_express_deliver_percent"  value="<php>echo getFormatNumber($mer_pr['shop_express_deliver_percent']);</php>" size="10" validate="number:true,max:100" tips="商家独立抽成比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前业务设置，向上查找<php>if($_GET['type']=='merchant'){echo '系统的相关业务';}else{echo '商家抽成设置';}</php>的抽成比例）" <php>if($mer_pr['shop_express_deliver_percent']<0||$mer_pr['shop_express_deliver_percent']==''){</php>style="display:none"<php>}</php> /></td>
               </tr>
               <tr>
                   <th width="160">自提抽成比例：</th>
                   <td>
                     <span class="cb-enable">
                        <label class="cb-enable  <php>if($mer_pr['shop_self_pick_percent']>=0&&$mer_pr['shop_self_pick_percent']!=''){</php>selected<php>}</php>">
                            <span>单独设置</span>
                            <input type="radio" name="self_pick" value="1" <php>if($mer_pr['shop_self_pick_percent']>=0&&$mer_pr['shop_self_pick_percent']!=''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>
                       <span class="cb-disable">
                        <label class="cb-disable  <php>if($mer_pr['shop_self_pick_percent']<0||$mer_pr['shop_self_pick_percent']==''){</php>selected<php>}</php>">
                            <span>以总的设置为准</span>
                            <input type="radio" name="self_pick" value="0"   <php>if($mer_pr['shop_self_pick_percent']<0||$mer_pr['shop_self_pick_percent']==''){</php>checked="checked"<php>}</php>/>
                        </label>
                    </span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                       <input type="text" id="self_pick_input" class="input-text" name="shop_self_pick_percent"  value="<php>echo getFormatNumber($mer_pr['shop_self_pick_percent']);</php>" size="10" validate="number:true,max:100" tips="商家独立抽成比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前业务设置，向上查找<php>if($_GET['type']=='merchant'){echo '系统的相关业务';}else{echo '商家抽成设置';}</php>的抽成比例）" <php>if($mer_pr['shop_self_pick_percent']<0||$mer_pr['shop_self_pick_percent']==''){</php>style="display:none"<php>}</php> /></td>
               </tr>
           </if>
			
		

		   </tbody>
		  </table>
		 <p class="line_m detail">抽成细则（抽成金额范围按实际支付的金额计算）</p>
		 <table cellpadding="0" cellspacing="0" class="table_form detail" width="100%" id="tab_mer_pr_detail" style="display: table;overflow-y:scroll">
			<thead>
				<tr>
				<th>ID</th>
				<td>抽成范围</td>
				<td>抽成比例</td>
				</tr>
			</thead>
		   <tbody>
			<input type="hidden" name="type" value="<if condition="$_GET['type'] eq 'merchant'">merchant<else />{pigcms{$_GET['type']}</if>">
			<input type="hidden" name="mer_id" value="{pigcms{$_GET['mer_id']}">
	
			<volist name="percent_detail" id="vo">
				<tr>
					<th width="160">{pigcms{$i}</th>
					<td>{pigcms{$vo.money_start}---{pigcms{$vo.money_end}{pigcms{$config['Currency_txt']}</td>
					<td><input class="input-text valid" type="text" name="money_percent[]" value="<if condition="$detail[$i-1]==''">{pigcms{$vo.percent}<else />{pigcms{$detail[$i-1]}</if>"></td>
					<td class="textcenter" ></td>
				</tr>
			</volist>
		   </tbody>
		  </table>
		<div class="btn ">
			<input  type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		</div>
	</form>

	<script>
		$(function(){

            $('input[name=open_newlandpos_percent]').on('click', function () {
                if ($(this).val() == 1) {
                    $('#open_newlandpos_percent_input').show();
                } else {
                    $('#open_newlandpos_percent_input').hide();
                }
            })

			

            $('input[name=plat_deliver],input[name=merchant_deliver],input[name=express_deliver],input[name=self_pick]').on('click', function () {
                var name = $(this).attr('name');
                var value = $(this).val();
                if($('#' + name + '_input').val() == -1){
                    $('#' + name + '_input').val('');
                }
                if (value == 1) {
                    $('#' + name + '_input').show();
                } else {
                    $('#' + name + '_input').hide();
                }
            })



			$('a[data-toggle="tab"]').click(function(){
				layer.load(1, {
					shade: [0.1,'#fff']
				});
				var href = $(this).attr('href');
				setTimeout(function(){
					location.href = href;
				},100);
				return false;
			});
		
			$('#dosubmit').click(function(){
				$.post('{pigcms{:U('Merchant/edit_percent')}', $('#myform').serialize(), function(data, textStatus, xhr) {
					if(data.status==1){
						window.o2oTop.msg(2,data.info,true,2);
					}else{
						window.o2oTop.msg(0,data.info,true,2);
					}
					window.o2oTop.art.dialog({id:'edit_percent'}).close();
				});
			});

			$('.table_form:eq(0)').show();
			
			var percent = $('#percent');
			var scan_percent = $('#scan_percent');
			
			var open_percent=percent.val();
			var open_scan_percent=scan_percent.val();
			
			percent.blur(function(){
				open_percent = $(this).val();
			})
			scan_percent.blur(function(){
				open_scan_percent = $(this).val();
			})
			
			if(open_percent<0||open_percent==''){
				$('.detail').hide();
			}
			$('input[name="open_percent"]').click(function(){
				if($(this).val()==1){
					percent.show();
					if(open_percent<0){
						percent.val('');
					}else{
						percent.val(open_percent);
					}
					$('.detail').show();
				}else{
					percent.hide();
					percent.attr('value',-1);
					$('.detail').hide();
				}
			});
			
			$('input[name="open_scan_percent"]').click(function(){
				if($(this).val()==1){
					scan_percent.show();
					if(open_scan_percent<0){
						scan_percent.val('');
					}else{
						scan_percent.val(open_scan_percent);
					}
				}else{
					scan_percent.hide();
					scan_percent.attr('value',-1);
				}
			});
			
			$('#im_key').click(function(){
				window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
				$.get("{pigcms{:U('Config/im')}",function(data){
					if(data.error_code){
						window.o2oTop.msg(0,data.msg,true,3);
					}else{
						window.o2oTop.msg(1,data.msg,true,3);
					}
				},'json');
			});
			$('#live_service_key').click(function(){
				window.o2oTop.msg(2,'正在请求中,请稍等...',true,100);
				$.get("{pigcms{:U('Config/live_service')}",function(data){
					if(data.error_code){
						window.o2oTop.msg(0,data.msg,true,3);
					}else{
						window.o2oTop.msg(1,data.msg,true,3);
					}
				},'json');
			});
			
		});
	</script>
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		KindEditor.ready(function(K){
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			$('.config_upload_image_btn').click(function(){
				var upload_file_btn = $(this);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
				editor.loadPlugin('image', function(){
					editor.plugin.imageDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							upload_file_btn.siblings('.input-image').val(site_url+url);
							editor.hideDialog();
						}
					});
				});
			});
			$('.config_upload_file_btn').click(function(){
				var upload_file_btn = $(this);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_file')}&name="+upload_file_btn.siblings('.input-file').attr('name');
				editor.loadPlugin('insertfile', function(){
					editor.plugin.fileDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							upload_file_btn.siblings('.input-file').val(url);
							editor.hideDialog();
						}
					});
				});
			});
		});
	</script>
<include file="Public:footer"/>