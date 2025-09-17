<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Deliver/user')}">平台配送员管理</a>|
					<a href="{pigcms{:U('Deliver/lenTime')}">配送员时长统计</a>|
                    <a href="{pigcms{:U('Deliver/applyUser')}">待审核配送员</a>|
                    <if condition="C('config.open_scan_qrcode_receive') eq 1">
                        <a href="{pigcms{:U('Deliver/scan_report')}">配送员扫码收单统计</a>|
                    </if>
					<a href="{pigcms{:U('Deliver/reply')}">配送员评论列表</a>|
					<a href="{pigcms{:U('Deliver/cancel')}">配送员扔回订单列表</a>|
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('user_add')}','添加配送员',900,560,true,false,false,editbtn,'edit',true);">添加配送员</a>
					<!-- <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('field')}','配送员注册自定义字段',580,360,true,false,false,false,'detail',true);">配送员注册自定义字段</a> -->
                    <a href="{pigcms{:U('Deliver/merchant_user')}" class="on">商家配送员管理</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Deliver/merchant_user')}" method="get">
							<input type="hidden" name="c" value="Deliver"/>
							<input type="hidden" name="a" value="merchant_user"/>
                            <input type="hidden" name="is_merchant_user" value="1"/>

                            <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家名称</option>
								<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>ID</th>
                                <th>所属商家</th>
								<th>姓名</th>
								<th>联系电话</th>
                                <th class="textcenter">状态</th>
								<th>注册时间</th>
								<th>最后登录时间</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($user_list)">
								<volist name="user_list" id="vo">
									<tr>
										<td>{pigcms{$vo.uid}</td>
                                        <td>{pigcms{$vo.mer_name}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.phone|phone_demo_show}</td>
                                        <td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">正常</font><elseif condition="$vo['status'] eq 4"/><font color="red">删除</font><else/><font color="red">禁止</font></if></td>
                                        <td><if condition="$vo['create_time']">{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</if></td>
										<td><if condition="$vo['last_time']">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}<else/>未登录</if></td>
										<td class="textcenter">
                                            <if condition="$vo['status'] eq 4">
                                                <a href="javascript:void(0);" class="delete_row_new" parameter="uid={pigcms{$vo.uid}&status=1" url="{pigcms{:U('Deliver/del')}">恢复</a>
                                            <else/>
                                                <a href="javascript:void(0);" class="delete_row_new" parameter="uid={pigcms{$vo.uid}&status=4" url="{pigcms{:U('Deliver/del')}">删除</a>
                                            </if>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>
<script>
    //删除
    $('.delete_row_new').click(function () {
        var now_dom = $(this);
        var check_deliver_order_url = '{pigcms{:U('Deliver/check_deliver_order')}';
        $.post(check_deliver_order_url, now_dom.attr('parameter'), function (result) {
            if (result.status == 1) {
                var msg_txt = result.info;
                window.top.art.dialog({
                    icon: 'question',
                    title: '请确认',
                    id: 'msg' + Math.random(),
                    lock: true,
                    fixed: true,
                    opacity: '0.4',
                    resize: false,
                    content: msg_txt+'确定要执行此操作吗？',
                    ok: function () {
                        $.post(now_dom.attr('url'), now_dom.attr('parameter'), function (result) {
                            if (result.status == 1) {
                                art.dialog({
                                    content: result.info,
                                    title: '成功提示',
                                    cancelVal: '关闭',
                                    cancel: true,
                                });
                                window.location.reload();
                            } else {
                                art.dialog({
                                    content: result.info,
                                    title: '错误提示',
                                    cancelVal: '关闭',
                                    cancel: true,
                                });
                            }
                        });
                    },
                    cancel: true
                });
            }else{
                art.dialog({
                    content: result.info,
                    title: '错误提示',
                    cancelVal: '关闭',
                    cancel: true,
                });
            }
        });
        return false;
    });
</script>