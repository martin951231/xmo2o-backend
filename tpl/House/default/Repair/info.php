<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>详情</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			.repair_pic img{max-width:100%;}
		</style>
	</head>
	<body>
		<table>
			
			<tr>
				<th width="15%">业主姓名</th>
				<td width="35%"><if condition="$repair['pigcms_id']">{pigcms{$repair.name}<else/><span style="color: grey">{pigcms{$repair.user_name}</span></if></td>
				<th width="15%">业主编号</th>
				<td width="35%">
                    <if condition="$repair['bind_number']">
                        {pigcms{$repair.bind_number}
                        <elseif condition="$repair['usernum']"/>
                        {pigcms{$repair.usernum}
                        <else/>
                        <span style="color: grey"></span>
                    </if>
                </td>
			</tr>
			<tr>
				<th width="15%">上报时间</th>
				<td width="35%">{pigcms{$repair.time|date='Y-m-d H:i:s',###}</td>
				<th width="15%">上报地址</th>
				<td width="35%"><if condition="$repair['pigcms_id']">{pigcms{$repair.address}<else/><span style="color: grey"></span></if></td>
			</tr>
			<tr>
				<th width="15%">联系方式</th>
<!--				<td width="85%" colspan="3">{pigcms{$repair.phone}</td>-->
				<td width="85%" colspan="3"><if condition="$repair['phone']">{pigcms{$repair.phone}<else/><span style="color: grey">{pigcms{$repair.repair_phone}</span></if></td>
			</tr>
			<if condition="$repair['type'] eq 4">
				<tr>
					<th width="15%">废品属性</th>
					<td width="85%" colspan="3">{pigcms{$repair.waste_type_name}</td>
				</tr>
				<tr>
					<th width="15%">废品数量或质量</th>
					<td width="85%" colspan="3">{pigcms{$repair.waste_detail}</td>
				</tr>
			</if>
            <if condition="$repair['repair_type'] && $repair['type'] eq 1">
                <tr>
                    <th width="15%">报修类别</th>
                    <td width="85%" colspan="3">{pigcms{$repair.repair_type}</td>
                </tr>
            </if>
            <if condition="$repair['cate_name'] && $repair['type'] neq 2">
                <tr>
                    <th width="15%"><if condition="$repair['type'] eq 3"> 投诉建议分类 <else/>报修分类</if></th>
                    <td width="85%" colspan="3">{pigcms{$repair.cate_name}</td>
                </tr>
            </if>
			<tr>
				<th width="15%">上报内容</th>
				<td width="85%" colspan="3">{pigcms{$repair.content}</td>
			</tr>

            <if condition="$repair['repair_time'] && $repair['type'] eq 1">
            <tr>
                <th width="15%">上门时间</th>
                <td width="85%" colspan="3">{pigcms{$repair['repair_time']|date="Y-m-d H:i:s",###}</td>
            </tr>
            </if>
			<if condition="repair.pic">
				<tr>
					<th width="15%">上报图例</th>
					<td width="85%" colspan="3" class="repair_pic">
						<volist name="repair.pic" id="p">
							<img src="{pigcms{:replace_file_domain($p)}"/><br/> 
						</volist>
					</td>
				</tr>
			</if>
			
			<if condition="$repair['r_status'] eq 0">
				<tr>
					<th width="15%">{pigcms{$msg['title']}</th>
					<td width="85%"  colspan="3">
						<select id="worker_id">
							<option>{pigcms{$msg['tip']}</option>
							<volist name="workers" id="worker">
							<option value="{pigcms{$worker['wid']}">{pigcms{$worker['name']}</option>
							</volist>
						</select>
					</td>
				</tr>
				<tr>
					<td align="center" colspan="4">
					<button id="suggent_submit" type="button" class="btn btn-info"><i class="ace-icon fa fa-check bigger-110"></i>提交</button>
					</td>
				</tr>
			<elseif condition='$repair["r_status"] eq 1'/>
				<tr>
					<th width="15%">状态</th>
					<td width="85%" colspan="3"><font color="green">已指派</font></td>
				</tr>
				<tr>
					<th width="15%">处理人员</th>
					<td width="85%" colspan="3"><font color="green">{pigcms{$worker['name']}, {pigcms{$worker['phone']}</font></td>
				</tr>
			<elseif condition='$repair["r_status"] eq 2'/>
				<tr>
					<th width="15%">状态</th>
					<td width="85%" colspan="3"><font color="green">已受理</font></td>
				</tr>
				<tr>
					<th width="15%">处理人员</th>
					<td width="85%" colspan="3"><font color="green">{pigcms{$worker['name']}, {pigcms{$worker['phone']}</font></td>
				</tr>
				<tr>
					<th width="15%">接单留言</th>
					<td width="85%" colspan="3"><font>{pigcms{$repair['msg']}</font></td>
				</tr>
                <if condition="$follow">
                    <tr>
                        <th width="15%">接单跟进</th>
                        <td width="85%" colspan="3">
                        <volist name="follow" id="vo">
                            <p>{pigcms{$vo['time']} - {pigcms{$vo['content']}</p>
                        </volist>
                        </td>
                    </tr>
                </if>
			<elseif condition='$repair["r_status"] eq 3'/>
				<tr>
					<th width="15%">状态</th>
					<td width="85%" colspan="3"><font color="green">已处理</font></td>
				</tr>
				<if condition="$worker">
				<tr>
					<th width="15%">处理人员</th>
					<td width="85%" colspan="3"><font color="green">{pigcms{$worker['name']}, {pigcms{$worker['phone']}</font></td>
				</tr>
				<tr>
					<th width="15%">接单留言</th>
					<td width="85%" colspan="3"><font>{pigcms{$repair['msg']}</font></td>
				</tr>
                <if condition="$follow">
                    <tr>
                        <th width="15%">接单跟进</th>
                        <td width="85%" colspan="3">
                            <volist name="follow" id="vo">
                                <p>{pigcms{$vo['time']} - {pigcms{$vo['content']}</p>
                            </volist>
                        </td>
                    </tr>
                </if>
				<tr>
					<th width="15%">处理时间</th>
					<td width="85%" colspan="3">{pigcms{$repair['reply_time']|date="Y-m-d H:i:s",###}</td>
				</tr>
				<tr>
					<th width="15%">处理意见</th>
					<td width="85%" colspan="3">{pigcms{$repair.reply_content}</td>
				</tr>
				</if>
				<if condition="repair.reply_pic">
					<tr>
						<th width="15%">处理图例</th>
						<td width="85%" colspan="3" class="repair_pic">
							<volist name="repair.reply_pic" id="p">
								<img src="{pigcms{:replace_file_domain($p)}"/><br/>
							</volist>
						</td>
					</tr>
				</if>
			<elseif condition='$repair["r_status"] eq 4'/>
				<tr>
					<th width="15%">状态</th>
					<td width="85%" colspan="3"><font color="green">业主已评价</font></td>
				</tr>
				<if condition="$worker">
				<tr>
					<th width="15%">处理人员</th>
					<td width="85%" colspan="3"><font color="green">{pigcms{$worker['name']}, {pigcms{$worker['phone']}</font></td>
				</tr>
				<tr>
					<th width="15%">接单留言</th>
					<td width="85%" colspan="3"><font>{pigcms{$repair['msg']}</font></td>
				</tr>
                <if condition="$follow">
                    <tr>
                        <th width="15%">接单跟进</th>
                        <td width="85%" colspan="3">
                            <volist name="follow" id="vo">
                                <p>{pigcms{$vo['time']} - {pigcms{$vo['content']}</p>
                            </volist>
                        </td>
                    </tr>
                </if>
				<tr>
					<th width="15%">处理时间</th>
					<td width="85%" colspan="3">{pigcms{$repair['reply_time']|date="Y-m-d H:i:s",###}</td>
				</tr>
				</if>
				<tr>
					<th width="15%">处理意见</th>
					<td width="85%" colspan="3">{pigcms{$repair.reply_content}</td>
				</tr>
				<if condition="repair.reply_pic">
					<tr>
						<th width="15%">处理图例</th>
						<td width="85%" colspan="3" class="repair_pic">
							<volist name="repair.reply_pic" id="p">
								<img src="{pigcms{:replace_file_domain($p)}"/><br/>
							</volist>
						</td>
					</tr>
				</if>
				<tr>
					<th width="15%">评论时间</th>
					<td width="85%" colspan="3">{pigcms{$repair['comment_time']|date="Y-m-d H:i:s",###}</td>
				</tr>
				<tr>
					<th width="15%">评分</th>
					<td width="85%" colspan="3"><font color="red">{pigcms{$repair.score}分</font></td>
				</tr>
				<tr>
					<th width="15%">评论内容</th>
					<td width="85%" colspan="3">{pigcms{$repair.comment}</td>
				</tr>
				<if condition="repair.comment_pic">
					<tr>
						<th width="15%">评论图例</th>
						<td width="85%" colspan="3" class="repair_pic">
							<volist name="repair.comment_pic" id="p">
								<img src="{pigcms{:replace_file_domain($p)}"/><br/>
							</volist>
						</td>
					</tr>
				</if>
			</if>
		</table>
		
		
		
		<script type="text/javascript">
			$('#suggest_select').change(function(){
				$('#content').text('');
				if($(this).val()!='请选择'){
					$('#content').text($(this).val());
				}
			});
			
			$('#suggent_submit').click(function(){
				var url = "{pigcms{:U('ajax_suggest_reply')}";
				var pigcms_id = "{pigcms{$_GET['pid']}";
				var worker_id = $('#worker_id').val();
				$.post(url, {'worker_id':worker_id,'pigcms_id':pigcms_id}, function(data){
					alert(data.msg)
					if(data.status){
						location.reload();
					}
				},'json')
			});
			
// 			$('#suggent_submit').click(function(){
// 				if(confirm('提交后，将无法修改，确认提交？')){
// 					var url = "{pigcms{:U('ajax_suggest_reply')}";
// 					var pigcms_id = "{pigcms{$_GET['pid']}";
// 					var reply_content = $('#content').val();
// 					$.post(url,{'reply_content':reply_content,'pigcms_id':pigcms_id},function(data){
// 						alert(data.msg)
// 						if(data.status){
// 							location.reload();
// 						}
// 					},'json')
// 				}
				
// 			});
		</script>
	</body>
</html>