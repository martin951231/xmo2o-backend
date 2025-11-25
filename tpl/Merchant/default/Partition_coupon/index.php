<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-qrcode"></i>
				<a href="{pigcms{:U('index')}">{pigcms{:L_("好友瓜分券")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
			
					<button class="btn btn-success" onclick="add()">{pigcms{:L_("添加好友瓜分券")}</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>ID</th>
									<th>名称</th>
									<th>瓜分方式</th>
									<th>瓜分内容</th>
									<th>进行中数量</th>
									<th>瓜分成功数</th>
									<th>剩余库存</th>
									<th>已使用数</th>
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
								<if condition="$coupon_list">
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
												<a href="/wap.php?g=Wap&c=Partition_coupon&a=partition_coupon_detail&type=merchant&id={pigcms{$vo.pigcms_id}" target="_blank">详情</a>&nbsp;/&nbsp;
												<img src="{pigcms{$static_path}images/qrcode.png" href="{pigcms{:U('url_qrcode',array('pigcms_id'=>$vo['pigcms_id']))}" class="see_qrcode" style="width: 20px;cursor: pointer;">
											</td>
											<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">进行中</font><elseif condition="$vo['status'] eq 2"/><font color="blue">已结束</font><elseif condition="$vo['status'] eq 3" /><font color="black">领完了</font><else /><font color="red">禁止</font></if></td>
											<td class="textcenter">
											
											<a href="{pigcms{:U('edit',array('pigcms_id'=>$vo['pigcms_id']))}">编辑</a>|
											<a  class="" title="删除" href="javascript:void(0)" onClick="if(confirm('确定停止活动么？停止活动后，用户将不可发起新的瓜分券活动，已经进行中的活动还可继续，已经瓜分成功的活动只要券在有效期内还可正常使用!')){location.href='{pigcms{:U('stop_coupon',array('pigcms_id'=>$vo['pigcms_id']))}'}">停止活动</a>
											<if condition="$vo.status neq 1">
											|<a  class="" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('delete_coupon',array('pigcms_id'=>$vo['pigcms_id']))}'}">删除</a>
											</if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("您没有添加过渠道消息！")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	function add(){
		window.location.href = "{pigcms{:U('add')}";
	}
	$(function(){
		$('.group_name').hover(function(){
			var top = $(this).offset().top;
			var left = $(this).offset().left+$(this).width()+10;
			$('body').append('<div id="group_name_div" style="position:absolute;z-index:5555;background:white;top:'+top+'px;left:'+left+'px;border:1px solid #ccc;padding:10px;"><div style="margin-bottom:10px;"><b>{pigcms{:L_("商品标题：")}</b>'+$(this).data('title')+'</div><div><b>{pigcms{:L_("商品图片：")}</b><img src="'+$(this).data('pic')+'" style="width:180px;"/></div></div>');
		},function(){
			$('#group_name_div').remove();
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
