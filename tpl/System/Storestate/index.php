<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<table class="search_table" width="100%">
				<tr>
					<td style="width:60%;">
						<form action="{pigcms{:U('Storestate/index')}" method="get">
							<input type="hidden" name="c" value="Storestate"/>
							<input type="hidden" name="a" value="index"/>
							筛选: 
							<if condition="$admin_area neq 3">
							<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
							</if>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="storeid" <if condition="$_GET['searchtype'] eq 'storeid'">selected="selected"</if>>店铺ID</option>								
								<option value="storename" <if condition="$_GET['searchtype'] eq 'storename'">selected="selected"</if>>店铺名称</option>							
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<select name="searchstatus">
								<option value="0" <if condition="$_GET['searchstatus'] eq 0">selected="selected"</if>>外卖状态</option>
								<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>已开启</option>
								<option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>未开启</option>
								<option value="3" <if condition="$_GET['searchstatus'] eq '3'">selected="selected"</if>>紧急关店</option>
							</select>
							<select name="yystatus">
								<option value="0" <if condition="$_GET['yystatus'] eq 0">selected="selected"</if>>营业状态</option>
								<option value="1" <if condition="$_GET['yystatus'] eq '1'">selected="selected"</if>>营业</option>
								<option value="2" <if condition="$_GET['yystatus'] eq '2'">selected="selected"</if>>未营业</option>
							</select>
							<select name="lang">
								<option value="0" <if condition="$_GET['lang'] eq 0">selected="selected"</if>>店铺主语言</option>
								<volist name="langs" id="lang">
									<option value="{pigcms{$lang.val}" <if condition="$_GET['lang'] eq $lang['val']">selected="selected"</if>>{pigcms{$lang.display}</option>
								</volist>
							</select>
							<select name="notice">
								<option value="0" <if condition="$_GET['notice'] eq 0">selected="selected"</if>>是否接收新订单通知</option>
								<option value="1" <if condition="$_GET['notice'] eq '1'">selected="selected"</if>>接收新订单通知</option>
								<option value="2" <if condition="$_GET['notice'] eq '2'">selected="selected"</if>>不接收新订单通知</option>
							</select>
							<select name="last_time">
								<option value="0" <if condition="$_GET['last_time'] eq 0">selected="selected"</if>>当前通信状态</option>
								<option value="1" <if condition="$_GET['last_time'] eq '1'">selected="selected"</if>>通信正常</option>
								<option value="2" <if condition="$_GET['last_time'] eq '2'">selected="selected"</if>>通信异常</option>
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
				<tr>
                    <td>
                    <b>该筛选条件下店铺共计：<span id="all_count_count" >{pigcms{$all_count.count}</span>家&nbsp;&nbsp;&nbsp;<i class="layer-tip" style="border-radius: 10%;border: 1px solid orange;padding: 0px 2px;color: orange;" tips="当前城市下所有店铺为“启用”状态的店铺数量。">?</i></b>　
                    <b>开启外卖：<span id="all_count_have_shop">{pigcms{$all_count.have_shop}</span>家&nbsp;&nbsp;&nbsp;<i class="layer-tip" style="border-radius: 10%;border: 1px solid orange;padding: 0px 2px;color: orange;" tips="当前城市下，所有为“开启”状态的店铺中，开启外卖店铺的数量。">?</i></b>　
                    <b>未营业：<span id="all_count_no_business">{pigcms{$all_count.no_business}</span>家&nbsp;&nbsp;&nbsp;<i class="layer-tip" style="border-radius: 10%;border: 1px solid orange;padding: 0px 2px;color: orange;" tips="当前城市下，所有为“开启”状态的店铺中，当前没有营业的店铺数量">?</i></b>
                </tr>
			</table>
			
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup><col> <col> <col> <col><col><col><col><col><col width="240" align="center"> </colgroup>
						<thead>
							<tr>
								<th class="textcenter">店铺ID</th>
								<th class="textcenter">店铺名称</th>
								<th class="textcenter">所在城市</th>
								<th class="textcenter">所在区域</th>
								<th class="textcenter">外卖状态</th>
								<th class="textcenter">营业状态</th>
								<th class="textcenter">接收新订单通知&nbsp;&nbsp;&nbsp;<i class="layer-tip" style="border-radius: 10%;border: 1px solid orange;padding: 0px 2px;color: orange;" tips="店铺APP中，在设置里面选择‘接单’或‘不接单’。若该店铺的所有店员APP账号都设置为‘不接单’，则此处显示‘不接单’，如果有一个设置为‘接单’，则此处显示‘接单’">?</i></th>
								<th class="textcenter">接单店员数</th>
								<th class="textcenter">当前通讯状态&nbsp;&nbsp;&nbsp;<i class="layer-tip" style="border-radius: 10%;border: 1px solid orange;padding: 0px 2px;color: orange;" tips="每60秒获取一次店员APP与服务端的通讯状态。如果没有获取到，则为异常，若该店铺的所有店员APP都未登录，或者登录状态下都未获取到店员APP的通讯状态，则此处显示“异常”">?</i></th>
								<th class="textcenter">主语言</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<volist name="store_list" id="vo">
							<tr>
								<td class="textcenter">{pigcms{$vo.store_id}</td>
								<td class="textcenter">{pigcms{$vo.name}</td>
								<td class="textcenter">{pigcms{$vo.city}</td>
								<td class="textcenter">{pigcms{$vo.area}</td>
								<td class="textcenter"><if condition="$vo.have_shop eq '1'"><if condition="$vo.is_close eq 1"><font color='red'>紧急关店</font><else />已开启</if><else /><font color='red'>未开启</font></if></td>
								<td class="textcenter"><if condition="$vo.is_business_open eq '1'">当前营业<else /><font color='red'>当前未营业</font></if></td>
								<td class="textcenter"><if condition="$vo.jd_status eq '1'">接收<else /><font color='red'>不接收</font></if></td>
								<td class="textcenter">{pigcms{$vo.nums}</td>
								<td class="textcenter"><if condition="$vo.tx_status eq '1'">正常<else /><font color='red'>异常</font></if></td>	
								<td class="textcenter">{pigcms{$vo.lang}</td>
								<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Storestate/staff_list',array('store_id'=>$vo['store_id'],'frame_show'=>true))}','查看{pigcms{$vo.name}店员账号',950,600,true,false,false,false,'detail',true);">查看店员账号</a></td>
							</tr>
							</volist>
							<tr>
								<td colspan="13" class="textcenter pagebar">{pigcms{$pagebar}</td>
							</tr>
						</tbody>
					</table>
				</div>
		
		</div>
<script>
   var url = "{pigcms{$config.site_url}";
   $(function(){
   	$(".layer-tip").bind("mouseover", function(){
   		layer.tips($(this).attr("tips"), this);
   	})	
   })
   
</script>
<include file="Public:footer"/>