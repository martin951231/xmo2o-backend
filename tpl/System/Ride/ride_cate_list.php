<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('index')}">根列表</a>|
				<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Ride/add_cate')}','添加分类',700,480,true,false,false,addbtn,'add',true);">添加分类</a>
				<a href="{pigcms{:U('ride_cate_list')}" class="on">分类列表</a>|
			</ul>
		</div>
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
						<col/>
						<col width="160" align="center"/>
					</colgroup>
					<thead>
						<tr>
							<th>编号</th>
							<th>分类名称</th>
							<th>分类状态</th>
							<th>开启/禁止</th>
							<th>添加时间</th>
							<th>操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($list)">
							<volist name="list" id="vo">
								<tr>
									<td>{pigcms{$vo.ride_cate_id}</td>
									<td>{pigcms{$vo.ride_cate_name}</td>
									<td>
										<if condition="$vo['ride_cate_is_del'] eq 0">
                                            开启
                                            <else/>
                                            禁止
                                        </if>
									</td>
									<td>
										<span class="cb-enable"><label class="cb-enable <if condition="$vo['ride_cate_is_del'] eq 0">selected</if>"><span onclick="discoverSwitch('{pigcms{$vo.ride_cate_id}', 0)" >开启</span><input type="radio" value="0" <if condition="$vo['ride_cate_is_del'] eq 0">checked="checked"</if> /></label></span>
                                        <span class="cb-disable"><label class="cb-disable <if condition="$vo['ride_cate_is_del'] eq 1">selected</if>"><span onclick="discoverSwitch('{pigcms{$vo.ride_cate_id}', 1)">禁止</span><input type="radio" value="1" <if condition="$vo['ride_cate_is_del'] eq 1">checked="checked"</if> /></label></span>
									</td>
									<td>{pigcms{$vo.ride_cate_addtime|date='Y-m-d H:i',###}</td>
									<td class="textcenter">
										<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Ride/edit_ride_cate',array('ride_cate_id'=>$vo['ride_cate_id']))}','编辑分类信息',700,480,true,false,false,editbtn,'add',true);" >编辑</a>
                                        <a href="javascript:void(0);"  onclick='del_category("{pigcms{$vo.ride_cate_id}")'>删除</a>
                                    </td>
								</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
	<script>
            var lockInfo = false;
            function del_category(ride_cate_id){
                if (lockInfo) return;
                lockInfo = true;
                var del_category = "{pigcms{:U('Ride/del_ride_cate')}";
                $.post(del_category,{'ride_cate_id':ride_cate_id},function(data){
                    console.log(data)
                    if (data && (data.status === 1 || data.status === '1')) {
                        location.reload();
                    } else {
                        alert(data.info)
                    }
                },'json')
                setTimeout(function(){
                    lockInfo = false;
                },1000);
            }
            function discoverSwitch(ride_cate_id, status) {
                if (lockInfo) return;
                lockInfo = true;
                var url = '';
                var close_category = "{pigcms{:U('Ride/close_ride_cate')}";
                var open_category = "{pigcms{:U('Ride/open_ride_cate')}";
                if (0 == status) {
                    url = open_category;
                } else if(1 == status) {
                    url = close_category;
                } else {
                    return;
                }
                $.post(url,{'ride_cate_id': ride_cate_id},function(data){
                    console.log(data)
                    if (data && (data.status === 1 || data.status === '1')) {
                        location.reload();
                    } else {
                        alert(data.info)
                    }
                },'json')
                setTimeout(function(){
                    lockInfo = false;
                },1000);
            }
        </script>
<include file="Public:footer"/>