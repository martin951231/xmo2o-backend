<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('deliverList')}">配送列表</a>|
					<a href="{pigcms{:U('desk')}" target="_blank">调度控制台</a>|
					<a href="{pigcms{:U('deliver_model')}" class="on">调度模式设置</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('deliver_model')}" method="get">
							<input type="hidden" name="c" value="Deliver"/>
							<input type="hidden" name="a" value="deliver_model"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="area_name" <if condition="$_GET['searchtype'] eq 'area_name'">selected="selected"</if>>城市名称</option>
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
								<th style="width: 20%;">城市ID</th>
								<th style="width: 30%;">城市名称</th>
								<th>调度模式</th>
                                <th>调度配置</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($city_list)">
								<volist name="city_list" id="vo">
									<tr>
										<td>{pigcms{$vo.area_id}</td>
										<td>{pigcms{$vo.area_name}</td>
										<td>
											<select class="deliver_model" data-city_id="{pigcms{$vo.area_id}">
												<if condition="!isset($config['is_hark_system'])">
													<option value="0" <if condition="$vo['deliver_model'] eq 0">selected="selected"</if>>配送员抢单</option>
												</if>
												<option value="1" <if condition="$vo['deliver_model'] eq 1">selected="selected"</if>>手动调度</option>
												<option value="2" <if condition="$vo['deliver_model'] eq 2">selected="selected"</if>>智能调度</option>
											</select>
										</td>
                                        <td>
											转单：<if condition="$vo['open_transfer'] eq 1">开启<else/>关闭</if>；
											抢单时长：{pigcms{$vo.no_grab_min}分钟；
											智能调度时长：{pigcms{$vo.smart_deliver_min}分钟；
											最后调度模式：
											<php>
												if($vo['last_deliver_model'] == 1){
													echo '手动调度';
												}else if($vo['last_deliver_model'] == 2){
													echo '就近指派';
												}else{
													echo '--';
												}
											</php>
											&nbsp;&nbsp;
											<a style="padding: 5px 10px;vertical-align:center;cursor:pointer;border: 1px solid #ddd;border-radius: 2px" href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Deliver/setting',array('area_id' => $vo['area_id']))}','调度模式设置',800,300,true,false,false,editbtn,'edit',true);">去设置</a>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="4">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="4">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script>
			$('.deliver_model').change(function(){
				$.get("{pigcms{:U('changeCityModel')}", {'deliver_model':$(this).val(),'city_id':$(this).data('city_id')}, function(response){
					layer.alert(response.info,{
						offset:($(parent.window).height() + $(parent.window).scrollTop())/2
					});
				});
			});
		</script>
<include file="Public:footer"/>