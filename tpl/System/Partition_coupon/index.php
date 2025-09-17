<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('index')}" class="on">平台瓜分券列表</a>
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('add')}','添加平台瓜分券','50%','75%',true,false,false,addbtn,'edit',true);">添加平台瓜分券</a>
					<!-- <a href="{pigcms{:U('had_pull')}" >领取列表</a> -->
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form id="myform1" action="{pigcms{:U('index')}" method="get">
							<input type="hidden" name="c" value="Partition_coupon"/>
							<input type="hidden" name="a" value="index"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>标题</option>
								<option value="pigcms_id" <if condition="$_GET['searchtype'] eq 'pigcms_id'">selected="selected"</if>>瓜分券ID</option>
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>名称</th>
								<th>瓜分方式</th>
								<th>瓜分内容</th>
								<th>进行中数量</th>
								<th>瓜分成功数</th>
								<th>剩余库存</th>
								<th>已领取数</th>
								<!-- <th>起始时间</th>
								<th>瓜分券有效期</th>
								<th>可用券起始时间</th>
								<th>用券门槛</th>
								<th>每人瓜分次数</th> -->
								<th class="textcenter">链接/二维码</th>
								<th class="textcenter">状态</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($coupon_list)">
								<volist name="coupon_list" id="vo">
									<tr>
										<td>{pigcms{$vo.pigcms_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td><if condition="$vo.type eq '0'">好友均分（每人领取金额相同）<else/>拼手气（每人领取金额随机）</if></td>
										<td>{pigcms{$vo.user_num}名好友，瓜分{pigcms{$vo.money}元</td>
										<td>{pigcms{$vo.going_num}</td>
										<td>{pigcms{$vo.has_partition}</td>
										<td>{pigcms{$vo.sy_num}</td>
										<td>{pigcms{$vo.had_pull}</td>
										<!-- <td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
										<td>{pigcms{$vo.day}天{pigcms{$vo.hour}时{pigcms{$vo.minute}分</td>
										<td>{pigcms{$vo.use_start_time|date='Y-m-d',###} 到 {pigcms{$vo.use_end_time|date='Y-m-d',###}</td>
										<td><if condition="$vo.use_condition eq '0'">不限制<else/>{pigcms{$vo.order_money}</if></td>
										<td class="textcenter"><if condition="$vo.partition_times eq '0'">不限制<else/>{pigcms{$vo.partition_times}次</if></td> -->
										<td>
											<a href="/wap.php?g=Wap&c=Partition_coupon&a=partition_coupon_detail&id={pigcms{$vo.pigcms_id}" target="_blank">详情</a>&nbsp;/&nbsp;
											<img src="{pigcms{$static_path}images/qrcode.png" href="{pigcms{:U('url_qrcode',array('pigcms_id'=>$vo['pigcms_id']))}" class="see_qrcode" style="width: 20px;cursor: pointer;">
										</td>
										<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">进行中</font><elseif condition="$vo['status'] eq 2"/><font color="blue">已结束</font><elseif condition="$vo['status'] eq 3" /><font color="black">领完了</font><else /><font color="red">禁止</font></if></td>
										<td class="textcenter">
										
										<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('edit',array('pigcms_id'=>$vo['pigcms_id']))}','编辑瓜分券信息','50%','50%',true,false,false,editbtn,'edit',true);">编辑</a>
										|<a  class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确定停止活动么？停止活动后，用户将不可发起新的瓜分券活动，已经进行中的活动还可继续，已经瓜分成功的活动只要券在有效期内还可正常使用!')){location.href='{pigcms{:U('stop_coupon',array('pigcms_id'=>$vo['pigcms_id']))}'}">停止活动</a>
										<if condition="$vo.status neq 1">|
										<a href="javascript:void(0);" class="delete_row" parameter="pigcms_id={pigcms{$vo.pigcms_id}" url="{pigcms{:U('delete_coupon')}">删除</a></if>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="15">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	$(function(){
		$('#indexsort_edit_btn').click(function(){
			$(this).prop('disabled',true).html('提交中...');
			$.post("/merchant.php?g=Merchant&c=Config&a=merchant_indexsort",{group_indexsort:$('#group_indexsort').val(),indexsort_groupid:$('#indexsort_groupid').val()},function(result){
				alert('处理完成！正在刷新页面。');
				window.location.href = window.location.href;
			});
		});
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'查看二维码',
				padding: 0,
				width: 400,
				height: 400,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
			return false;
		});
	});
	
</script>
<include file="Public:footer"/>