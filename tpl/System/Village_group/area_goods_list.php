<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/area_list')}" >返回城市</a> 
					<a href="{pigcms{:U('Village_group/area_goods_list', array('city_id' => $_GET['city_id']))}" class="on">商品列表</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
<!--						<if condition="!empty($_GET['city_id'])">-->
<!--                            <if condition="$city_manage eq 1">-->
<!--                                <input type="button" value="添加拼团商品" class="button" onclick="window.top.artiframe('{pigcms{:U('add_group_product')}&group_id={pigcms{$_GET['group_id']}&cat_id={pigcms{$_GET['cat_fid']}&city_id={pigcms{$_GET['city_id']}','添加拼团商品','80%','90%',true,false,false,addbtn,'add',true);"/>-->
<!--                            <else />-->
<!--                                <input type="button" value="绑定拼团商品" class="button" onclick="window.top.artiframe('{pigcms{:U('area_goods_bind')}&city_id={pigcms{$_GET['city_id']}','绑定拼团商品','60%','60%',true,false,false,addbtn,'add',true);"/>-->
<!--                            </if>-->
<!--						</if>-->
                        <input type="button" value="绑定拼团商品" class="button" onclick="window.top.artiframe('{pigcms{:U('area_goods_bind')}&city_id={pigcms{$_GET['city_id']}','绑定拼团商品','60%','60%',true,false,false,addbtn,'add',true);"/>
					</td>
			
					<td>
						<!-- <form action="{pigcms{:U('Village_group/index')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="index"/>
							<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
							<input type="hidden" name="cat_fid" value="{pigcms{$_GET.cat_fid}"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>商品ID</option>
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商品名称</option>
								<option value="village_name" <if condition="$_GET['searchtype'] eq 'village_name'">selected="selected"</if>>社区名称</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							团购状态: <select name="searchstatus">
								<option value="-1" <if condition="$_GET['searchstatus'] eq -1">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>进行中</option>
								<option value="3" <if condition="$_GET['searchstatus'] eq '3'">selected="selected"</if>>未开始</option>
								<option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>已售罄</option>
								<option value="4" <if condition="$_GET['searchstatus'] eq '4'">selected="selected"</if>>已过期</option>
								<option value="5" <if condition="$_GET['searchstatus'] eq '5'">selected="selected"</if>>已结束</option>
							</select>
							<input type="submit" value="查询" class="button"/>
							
						
						</form> -->
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
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
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>商品ID</th>
								<th>名称</th>
								<th>开始结束时间</th>
								<th>商品图片</th>
								<th>原始库存</th>
								<th>区域售价</th>
								<th>加价区间</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($goods_list)">
								<volist name="goods_list.list" id="vo">
									<tr>
										<td>{pigcms{$vo.pigcms_id}</td>
										<td>{pigcms{$vo.goods_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td><if condition="$vo['end_time'] neq 0">{pigcms{$vo.start_time|date='Y-m-d',###}至{pigcms{$vo.end_time|date='Y-m-d',###}<else />{pigcms{$vo.start_time|date='Y-m-d',###}后永久售卖</if></td>
										<td><img src="{pigcms{$vo.image}" style="width:120px;height:80px;"></td>
										<td>{pigcms{$vo.stock_num}</td>
										<td>{pigcms{$vo.area_price|floatval}</td>
										<td>
											<if condition="$vo['is_add_price'] eq 0">
												<font color="red">不加价</font>
											<else/>
												<font color="green">{pigcms{$vo.min_price} - {pigcms{$vo.max_price}</font>
											
											</if>
										</td>
										<td class="textcenter">
										
										<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/area_goods_bind',array('pigcms_id'=>$vo['pigcms_id'],'city_id'=>$_GET['city_id']))}','编辑','80%','80%',true,false,false,editbtn,'add',true);">编辑</a>  | <a class="delete_row" parameter="pigcms_id={pigcms{$vo.pigcms_id}" url="{pigcms{:U('Village_group/area_goods_bind_del')}">删除</a>
										
										
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="11">{pigcms{$goods_list.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="11">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>