<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<if condition="$_GET['property_id']">
						<a href="{pigcms{:U('House/property')}" class="">返回物业列表</a>
					</if>
					<a href="{pigcms{:U('House/village',array('property_id'=>$_GET['property_id']))}" class="on">{pigcms{$config['house_name']}列表</a>

					<if condition="$_GET['property_id']">
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/village_add',array('property_id'=>$_GET['property_id']))}','添加{pigcms{$config['house_name']}',1100,350,true,false,false,addbtn,'add',true);">添加{pigcms{$config['house_name']}</a>
					</if>
<!--					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/village_import')}','添加{pigcms{$config['house_name']}',450,150,true,false,false,importbtn,'add',true);">导入{pigcms{$config['house_name']}</a>-->
					<if condition="$config['buy_sms'] eq 1">
						<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/sms_add_all')}','添加短信',520,350,true,false,false,addbtn,'add',true);">添加短信</a>
					</if>
						<!-- <a href="{pigcms{:U('House/door_notice_map')}" target="_blank" >{pigcms{$config['house_name']}开门状态监控</a> -->
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('House/village')}" method="get">
							<input type="hidden" name="c" value="House"/>
							<input type="hidden" name="a" value="village"/>
							筛选: 
							<if condition="$admin_area neq 3">
								<div id="choose_street" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" street_idss="{pigcms{$_GET.street_idss}" community_idss="{pigcms{$_GET.community_idss}" style="display:inline"></div>
							</if>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="village_name" <if condition="$_GET['searchtype'] eq 'village_name'">selected="selected"</if>>{pigcms{$config['house_name']}名称</option>
								<option value="property_name" <if condition="$_GET['searchtype'] eq 'property_name'">selected="selected"</if>>物业名称</option>
								<option value="property_phone" <if condition="$_GET['searchtype'] eq 'property_phone'">selected="selected"</if>>物业电话</option>
								<option value="account" <if condition="$_GET['searchtype'] eq 'account'">selected="selected"</if>>{pigcms{$config['house_name']}账号</option>
								<option value="village_id" <if condition="$_GET['searchtype'] eq 'village_id'">selected="selected"</if>>{pigcms{$config['house_name']}ID</option>
							</select>
							<input type="submit" value="查询" class="button"/>
                              <if condition="$is_get_hik_info_vision_i_community eq 1">
                                <a href="javascript:;" onclick="get_hik_info_vision_i_community()" class="button btn-success">拉取智慧社区管理平台小区数据</a>
                             </if>
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
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
                                <th>{pigcms{$config['house_name']}名称</th>
                                <th>所属物业</th>
								<th>最后登录时间</th>
								<th>访问</th>
								<th>账单</th>
								<th>{pigcms{$config.house_market_name}账单</th>
                                <th class="textcenter">排序</th>
								<th class="textcenter">状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($village_list)">
								<volist name="village_list" id="vo">
									<tr>
										<td>{pigcms{$vo.village_id}</td>
										<td>
                                            <if condition="$vo['status'] neq 2">
                                                <a href="{pigcms{:U('House/v20_village_login',array('village_id'=>$vo['village_id']))}" target="_blank">
                                                    {pigcms{$vo.village_name}
                                                </a>
                                                <else/>
                                                {pigcms{$vo.village_name}
                                            </if>
                                            <if condition="$vo['facilities_type_text'] && $vo['facilities_type_text'] neq ''">
                                                <span style="color:#2681f3;">[{pigcms{$vo.facilities_type_text}]</span>
                                            </if>
                                        </td>
                                        <td>
                                            <if condition="$vo['property_name']">
                                                <a onclick="go_property({pigcms{$vo['property_id']})" style="cursor:pointer;" target="_blank">{pigcms{$vo.property_name}</a>
                                                <else/>
                                                暂无
                                            </if>
                                        </td>
										<td><if condition="$vo['last_time']">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}<else/>从未登录</if></td>
										<td>
											<if condition="$vo['status'] neq 2">
												<a href="{pigcms{:U('House/v20_village_login',array('village_id'=>$vo['village_id']))}" target="_blank">访问</a>
											<else/>
												禁止状态
											</if>
										</td>
										<td><a href="{pigcms{:U('House/village_money_list',array('village_id'=>$vo['village_id']))}">查看账单</a></td>
										<td><a href="{pigcms{:U('House/market_order',array('village_id'=>$vo['village_id']))}">查看{pigcms{$config.house_market_name}账单</a></td>

                                        <td class="textcenter">{pigcms{$vo.village_sort}</td>
                                        <td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">正常</font><elseif condition="$vo['status'] eq 0"/><font color="red" title="等待{pigcms{$config['house_name']}管理员登录社区后台完善信息">待完善信息</font><else/><font color="red">禁止</font></if></td>
										<td class="textcenter">
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('House/village_edit',array('village_id'=>$vo['village_id']))}','编辑{pigcms{$config['house_name']}信息',960,600,true,false,false,editbtn,'edit',true);">编辑</a>
                                            <if condition="$vo['is_del'] eq 1 && (!$vo['is_saas_user'] || $vo['is_saas_user'] neq 1)">
                                                |
                                                <a href="javascript:void(0);" class="delete_row1" data-name="{pigcms{$vo.village_name}" parameter="village_id={pigcms{$vo.village_id}" url="{pigcms{:U('House/house_village_del')}">删除</a>
                                            </if>
                                        </td>

									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>

<script>
    function go_property(property_id){
        $.post("{pigcms{:U('House/property_login_v20')}&property_id="+property_id,function(result){
            $.cookie('property_access_token',result.data.ticket)
            setTimeout(function(){
                window.open(result.data.url);
            },600)
        },'json');
    }

    $('.delete_row1').click(function(){
        var now_dom = $(this);
        window.top.art.dialog({
            icon: 'question',
            title: '请确认是否要删除['+now_dom.attr('data-name')+']小区',
            id: 'msg' + Math.random(),
            lock: true,
            fixed: true,
            opacity:'0.4',
            resize: false,
            content: '你确定这样操作吗？操作后可能不能恢复！',
            ok:function (){
                $.post(now_dom.attr('url'),now_dom.attr('parameter'),function(result){
                    if(result.status == 1){
                        window.top.msg(1,result.info,true);
                        window.location.reload();
                    }else{
                        window.top.msg(0,result.info);
                    }
                });
            },
            cancel:true
        });
        return false;
    });

    var get_hik_info_community_url = "{pigcms{:U('House/get_hik_info_community')}"
    function get_hik_info_vision_i_community(){
        var ii_index= layer.msg('正在为您拉取智慧社区管理平台小区数据<br/><div style="color:#999;line-height:1.3;margin-top:10px;font-size:12px;">这个过程需要一些时间，请耐心等待...</div>', {
				  icon: 16
				  ,shade: [0.3, '#000']
				  ,time:0
				  ,offset:'50px'
		});
		$.post(get_hik_info_community_url,{},function(ret){
                layer.close(ii_index);
                if(ret.data.err_code==0){
                    layer.msg('数据拉取成功，你可以从编辑小区里对接了！',{icon: 0,time:3,shade: [0.5]});
                }else{
                    layer.msg(ret.data.msg,{icon: 2});
                }
		},'json');
    }
</script>
<include file="Public:footer"/>