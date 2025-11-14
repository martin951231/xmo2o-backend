<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('index')}" class="on">营销活动列表</a>
					<a href="{pigcms{:U('add')}">添加营销活动</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form id="myform1" action="{pigcms{:U('index')}" method="get">
							<input type="hidden" name="c" value="MarketingActivity"/>
							<input type="hidden" name="a" value="index"/>
							筛选: 
							<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" style="display:inline"></div>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="title" <if condition="$_GET['searchtype'] eq 'title'">selected="selected"</if>>名称</option>
								<option value="activity_id" <if condition="$_GET['searchtype'] eq 'activity_id'">selected="selected"</if>>活动ID</option>
							</select>
							<input type="submit" value="查询" class="button"/>
							状态:
							<select name="status">
								<option value="-1" <if condition="$_GET['status'] eq -1 OR !isset($_GET['status'])">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>启用</option>
								<option value="0" <if condition="$_GET['status'] eq 0 AND isset($_GET['status'])">selected="selected"</if>>关闭</option>
							</select>
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
								<th width="300px">活动名称</th>
								<th>已参与用户数</th>
								<th>已参与新用户数</th>
								<th class="textcenter">链接/二维码</th>
								<th class="textcenter">状态</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($activity_list)">
								<volist name="activity_list" id="vo">
									<tr>
										<td>{pigcms{$vo.activity_id}</td>
										<td>{pigcms{$vo.title}</td>
										<td>{pigcms{$vo.join_num}</td>
										<td>{pigcms{$vo.join_num_new}</td>
										<td>
											<a href="/packapp/westpac/index.html#/?activity_id={pigcms{$vo.activity_id}" target="_blank">详情</a>&nbsp;/&nbsp;
											<img src="{pigcms{$static_path}images/qrcode.png" href="{pigcms{:U('url_qrcode',array('activity_id'=>$vo['activity_id']))}" class="see_qrcode" style="width: 20px;cursor: pointer;">
										</td>
										<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">开启</font><else /><font color="red">关闭</font></if></td>
										<td class="textcenter">
											<a href="{pigcms{:U('edit',array('activity_id'=>$vo['activity_id']))}">编辑</a>
											|<a href="javascript:void(0);" class="delete_row" parameter="activity_id={pigcms{$vo.activity_id}" url="{pigcms{:U('del_activity')}">删除</a>
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
					window.o2oTop.art.dialog.data('iframe_handle',iframe);
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