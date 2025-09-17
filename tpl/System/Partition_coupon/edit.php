<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Partition_coupon/edit')}" frame="true" refresh="true">
		<input type="hidden" name="pigcms_id" value="{pigcms{$coupon.pigcms_id}">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">	
			<tr>
				<td width="100">瓜分券名称：</td>
				<td>
					{pigcms{$coupon.name}
				</td>
			</tr>
			<tr>
				<td width="100">瓜分券数量：</td>
				<td>{pigcms{$coupon.num}</td>		
			</tr>
			
			<tr>
				<td width="100">瓜分券活动时间：</td>
				<td colspan="2">
					{pigcms{$coupon.start_time|date="Y-m-d H:i",###}-
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$coupon.end_time|date='Y-m-d H:i',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"  placeholder="结束时间" validate="required:true"/>
				</td>
			</tr>

			<tr>
				<td width="100">瓜分券有效期：</td>
				<td colspan="2">
					{pigcms{$coupon.day}
					<span>天</span>
					{pigcms{$coupon.hour}
					<span>时</span>
					{pigcms{$coupon.minute}
					<span>分</span>
				</td>
			</tr>

			<tr>
				<td width="100">瓜分方式：</td>
				<td colspan="2">
					<if condition="$coupon['type'] eq 0">
					好友均分（每人领取金额相同）
					<else/>
					拼手气（每人领取金额随机）
					</if>
				</td>
			</tr>
			
			
			<tr class="rand_send1">
				<td width="100">瓜分内容：</td>
				<td>
					{pigcms{$coupon.user_num}名好友，瓜分{pigcms{$coupon.money}元
				</td>
			</tr>
			
			<tr class="">
				<td width="100" >可用券时间</td>
				<td >
					<if condition="$coupon['time_type'] eq 0">
						{pigcms{$coupon.use_start_time|date="Y-m-d H:i",###}-{pigcms{$coupon.use_end_time|date="Y-m-d H:i",###}
					<else/>
						成功瓜分当日起{pigcms{$coupon.use_time}天内可用
					</if>
				</td>
			</tr>
			<tr class="">
				<td width="100" >用券门槛</td>
				<td >
					<if condition="$coupon['use_condition'] eq 0">
					无使用门槛
					<else/>
					订单满{pigcms{$coupon.order_money}元
					</if>
				</td>
			</tr>
			
			<tr>
				<td width="100">每人限瓜分次数：</td>
				<td colspan="2">
					<if condition="$coupon['partition_times'] eq 0">
					不限次数
					<else/>
					{pigcms{$coupon.partition_times}次
					</if>
				</td>
			</tr>
			<tr>
				<th width="15%">分享图片</th>
                <td>
	                <ul class="avatar_list" id="avatar_show" >
		                <if condition="$coupon.img_url neq ''">
		                    <li  class="upload_pic_li1" style="float:left;list-style:none;"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Recruit/view_image',array('url'=>urlencode(file_domain().$coupon['img_url'])))}','查看大图',700,600,true,false,false,'','',true);"><img src="{pigcms{:file_domain().$coupon['img_url']}" style="width:250px;height:200px;margin-right:5px;"></a></li>
		                   
		                </if>
	                </ul>
                </td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>

