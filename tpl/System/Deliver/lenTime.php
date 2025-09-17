<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Deliver/user')}">配送员管理</a>|
					<a href="javascript:void(0)" class="on">配送员时长统计</a>|
                    <a href="{pigcms{:U('Deliver/applyUser')}">待审核配送员</a>|
                    <if condition="C('config.open_scan_qrcode_receive') eq 1">
                        <a href="{pigcms{:U('Deliver/scan_report')}">配送员扫码收单统计</a>|
                    </if>
					<a href="{pigcms{:U('Deliver/reply')}">配送员评论列表</a>|
					<a href="{pigcms{:U('Deliver/cancel')}">配送员扔回订单列表</a>|
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('user_add')}','添加配送员',900,560,true,false,false,editbtn,'edit',true);">添加配送员</a>
					<!-- <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('field')}','配送员注册自定义字段',580,360,true,false,false,false,'detail',true);">配送员注册自定义字段</a> -->
                    <if condition="$show_merchant_user eq 1">
                        <a href="{pigcms{:U('Deliver/merchant_user')}">商家配送员管理</a>
                    </if>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Deliver/lenTime')}" method="get">
							<input type="hidden" name="c" value="Deliver"/>
							<input type="hidden" name="a" value="lenTime"/>
							筛选:
                            <!-- <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div> -->

                            <!-- <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/> -->
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>配送员姓名</option> 
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
							</select>

                            <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="请输入姓名/手机号"/>
                             
                            查询日期：
                            <input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>至
                            <input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>


							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<p style="color:red;padding: 0.5rem;">默认展示最近60天数据，其他时间请指定日期进行筛选</p>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>昵称</th>
								<th>手机号</th>
								<th>在线时长</th>
								<th>在线时间段</th>
								<th>平均每日在线时长</th>
								<th>常在线时间段</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>  
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.phone|phone_demo_show}</td>
										<td>{pigcms{$vo.total_time}</td>

                                        <td><a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Deliver/lenTimeDetail','uid='.$vo['uid'])}','最近200条在线时间记录',650,500,true,false,false);">查看详情</a></td>
                                        <td>{pigcms{$vo.avg_time}</td>
										<td>{pigcms{$vo.often_start_time} - {pigcms{$vo.often_end_time}</td>
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