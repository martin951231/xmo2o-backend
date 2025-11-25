<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('express_service_list')}">功能库</a>
			</li>
			<li class="active">快递代收</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                <if condition='!$has_express_service'>
                    <div style="margin-top:10px; cursor:pointer" class="alert alert-danger" onClick="window.open('{pigcms{:U('Index/config')}')">
                        <button data-dismiss="alert" class="close" type="button"><i class="ace-icon fa fa-times"></i></button>
                        还未开启&nbsp;&nbsp;<span style="font-weight:bold">快递代收</span>&nbsp;&nbsp;功能，请先到&nbsp;&nbsp;<span style="font-weight:bold">小区管理 - 基本信息 - 功能库配置&nbsp;&nbsp;</span>开启相应配置。
                    </div>
                <else />
                	<button class="btn btn-success handle_btn" title="快递配置" href="{pigcms{:U('Library/express_config')}" <if condition="!in_array(208,$house_session['menus'])">disabled="disabled"</if>>代送配置<if condition="$village_send">(已开启代送)</if></button>&nbsp;&nbsp;
                	<button class="btn btn-success" onclick="express_add()" <if condition="!in_array(209,$house_session['menus'])">disabled="disabled"</if>>添加快递</button>&nbsp;&nbsp;
                	<button id="search" class="btn btn-success">搜索</button>&nbsp;&nbsp;
					<if condition="in_array(212,$house_session['menus'])">
                	<a class="btn btn-success" href="{pigcms{:U('Library/express_analysis')}">快递统计</a>&nbsp;&nbsp;
                	<else/>
					<button class="btn btn-success" disabled="disabled">快递统计</button>
					</if>
                	<button id="saomaqujian" class="btn btn-success" <if condition="!in_array(210,$house_session['menus'])">disabled="disabled"</if>>扫码取件</button>
					<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;margin-top:12px;">
						<form method="get" action="{pigcms{:U('Library/express_service_list')}" id="find-form">
							<input type="hidden" name="c" value="Library"/>
							<input type="hidden" name="a" value="express_service_list"/>
							<select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;">
								<option value="1" <if condition="$find_type eq 1">selected="selected"</if>>取件码</option>
								<option value="2" <if condition="$find_type eq 2">selected="selected"</if>>收件人手机号</option>
								<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>快递单号</option>
							</select>
							<input value="{pigcms{$find_value}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
							&nbsp;&nbsp;

							<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
							<a class="btn btn-success" onclick="location.href='{pigcms{:U('express_service_list')}'">重置</a>
						</form>
					</div>
            	</if>
                
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="10%">快递信息</th>
                                    <th width="10%">收件人手机号</th>
                                    <th width="10%">收件人地址</th>
                                    <th width="10%">取件码</th>
								 	<th width="10%">送件费用</th>
                                    <th width="10%">状态</th>
                                    <th width="15%">预约代送时间</th>
                                    <th width="15%">添加时间</th>
                                    <th class="button-column">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list['list']">
                                    <volist name="list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.express_name}<br/>{pigcms{$vo.express_no}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
											<td>
												<div class="tagDiv">
													<if condition="$vo.address neq '' AND $vo.floor_name neq ''">{pigcms{$vo.address}<else />{pigcms{$vo.floor_name}</if>
												</div>
											</td>
											<td><div class="tagDiv">{pigcms{$vo.fetch_code}</div></td>
											<td>
												<div class="tagDiv">
													<if condition="$vo.money gt 0">￥{pigcms{$vo['money']}</if>
												</div>
											</td>
                                            <td>
												<div class="tagDiv">
													<if condition='$vo["status"] eq 0'>
														<span class="red">未取件</span>
													<if condition="in_array(210,$house_session['menus'])">
														<a href="javascript:void(0)" class="chk_express" data-id="{pigcms{$vo['id']}">确认取件</a>
													</if>
													<elseif condition='$vo["status"] eq 1'/>
														<span class="green">已取件（业主）</span>
													<else />
														<span class="green">已取件（小区）</span>
													</if>
												</div>
											</td>
											<td>
												<if condition='$vo["send_time"] gt 0'>
													<div class="tagDiv">{pigcms{$vo.send_time|date="Y-m-d H:i",###}</div>
												</if>
											</td>
                                            <td>
												<div class="tagDiv">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div>
											</td>
                                            <td class="button-column">
												<div class="tpl-table-black-operation">
													<a title="详情" class="handle_btn" href="{pigcms{:U('express_detail',array('id'=>$vo['id']))}">详情</a>
													&nbsp;&nbsp;
													<if condition="in_array(211,$house_session['menus'])">
														<a class="item-delete tpl-table-black-operation-del" title="删除" href="{pigcms{:U('express_del',array('id'=>$vo['id']))}">删除</a>
													</if>
												</div>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="10" >没有任何快递。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
function express_add(){
	window.location.href = "{pigcms{:U('express_add')}";
}


$('#search').live('click',function(){
	var search_url = "{pigcms{:U('express_search')}"
			art.dialog.open(search_url,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'搜索快递',
				padding: 0,
				width: 720,
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

		$('#saomaqujian').live('click',function(){
			var search_url = "{pigcms{:U('fetch_code')}&village_id=1"
			art.dialog.open(search_url,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'扫码取件',
				padding: 0,
				width: 260,
				height: 280,
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
		})
		
		
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title: $(this).html(),
				padding: 0,
				width: 720,
				height: 520,
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
		
		
		$('.chk_express').click(function(){

			var express_edit_url = "{pigcms{:U('express_edit')}";
			var id = $(this).data('id');
			var status = 2;

			layer.prompt({title: '输入取件码，并确认', formType: 3}, function(fetch_code, index){
				layer.close(index);
				// layer.msg('演示完毕！您的口令：'+ fetch_code );
				$.post(express_edit_url,{'id':id,'status':status,'fetch_code':fetch_code},function(data){
						if(data['status']){
							alert(data['msg']);
							location.reload();
						}else{
							alert(data['msg']);
						}
				},'json')


			});

			
		});
</script>

<include file="Public:footer"/>