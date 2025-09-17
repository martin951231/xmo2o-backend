<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					
				<if condition="$_GET['cat_fid'] neq 0">
					<a href="{pigcms{:U('Village_group/cate_index')}" >返回分类</a> 
				</if>
					<a href="{pigcms{:U('Village_group/index', array('cat_fid' => $_GET['cat_fid']))}" class="on">商品列表</a>
					
				
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
                    <if condition="$city_manage eq 1 and $level eq 1">
                        <if condition="$origin neq null">
                            <td>
                                <input type="button" value="添加拼团商品" class="button" onclick="window.top.artiframe('{pigcms{:U('add_group_product')}&group_id={pigcms{$_GET['group_id']}&cat_id={pigcms{$_GET['cat_fid']}&city_id={pigcms{$city_id}','添加拼团商品','80%','90%',true,false,false,addbtn,'add',true);"/>
                            </td>
                        </if>
                    <else />
                        <td>
                            <input type="button" value="添加拼团商品" class="button" onclick="window.top.artiframe('{pigcms{:U('add_group_product')}&group_id={pigcms{$_GET['group_id']}&cat_id={pigcms{$_GET['cat_fid']}','添加拼团商品','80%','90%',true,false,false,addbtn,'add',true);"/>
                        </td>
                    </if>
			
					<td>
						<form action="{pigcms{:U('Village_group/index')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="index"/>
							<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
							<input type="hidden" name="cat_fid" value="{pigcms{$_GET.cat_fid}"/>
                            <if condition="$city_manage eq 1 and $level eq 1">
                                <if condition="$origin eq null">
                                    <if condition="$admin_area neq 3">
                                        <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                                    </if>
                                </if>
                            </if>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>商品ID</option>
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商品名称</option>
								<option value="village_name" <if condition="$_GET['searchtype'] eq 'village_name'">selected="selected"</if>>社区名称</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            商品分类: <select name="cat_fid">
                                <option value="0" <if condition="$_GET['cat_fid'] eq 0">selected="selected"</if>>全部</option>
                                <volist name="cates" id="cate_vo">
                                    <option value="{pigcms{$cate_vo.cat_id}" <if condition="$_GET['cat_fid'] eq $cate_vo['cat_id']">selected="selected"</if>>{pigcms{$cate_vo.cat_name}</option>
                                </volist>
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
							
						
						</form>
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
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>商品ID</th>
								<th>名称</th>
								<th>商家信息</th>
                                <if condition="$city_manage eq 1">
                                    <th>区域</th>
                                </if>
								<th>分类</th>
								<th>开始结束时间</th>
								<th>浏览量</th>
								<th style="text-align:center;">商品图片</th>
                                <th>虚拟销量</th>
								<th>原始库存</th>
								<th>销量</th>
								<th>锁定库存</th>
								<th>团状态</th>
								<!--th>预售</th-->
								<!--th>统计</th-->
							
				
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($group_list)">
								<volist name="group_list" id="vo">
									<tr>
										<td>{pigcms{$vo.goods_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.mername}</td>
                                        <if condition="$city_manage eq 1">
                                            <td>{pigcms{$vo.area_name}</td>
                                        </if>
										<td>{pigcms{$vo.cat_name}</td>
										<td><if condition="$vo['end_time'] neq 0">{pigcms{$vo.start_time|date='Y-m-d',###}至{pigcms{$vo.end_time|date='Y-m-d',###}<else />{pigcms{$vo.start_time|date='Y-m-d',###}后永久售卖</if></td>
										<td>{pigcms{$vo.browse_num}</td>
										<td style="text-align:center;"><img src="{pigcms{:thumb($vo['image'], 200)}" style="height:80px;"/></td>
                                        <td>{pigcms{$vo.virtual_sell_count|floatval}</td>
										<td>{pigcms{$vo.stock_num}</td>
										<td>{pigcms{$vo.sell_count|floatval}</td>
										<td>{pigcms{$vo.locking_num}</td>
										<td>
							
											<if condition="$vo['status'] eq 1 AND ($vo['start_time']) GT time() ">
											
												<font color="red">还未开始</font>
											
											<elseif condition="$vo['status'] eq 2 OR $vo['sell_count'] eq $vo['stock_num']"/>
												<font color="red">已售罄</font>
											<elseif condition="$vo['status'] eq 1" />
												进行中
											</if>
										
											<if condition="$vo['status'] eq 3">
												<font color="red">已过期</font>
											<elseif condition="$vo['status'] eq 5"/>
												<font color="green">已结束</font>
											
											</if>
										</td>
										<!--td><if condition="$vo['get_time'] gt strtotime(date('Y-m-d')+86400)">
												<font color="green">是</font>
											<else/>
												<font color="red">否</font>
											</if></td-->
										<!--td>
										<a href="{pigcms{:U('Village_group/analysis',array('goods_id'=>$vo['goods_id']))}">统计</a>
										|
										<a href="{pigcms{:U('Village_group/analysis_area',array('goods_id'=>$vo['goods_id']))}">区域统计</a>
										
										</td-->
										<td class="textcenter">
										<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/add_group_product',array('goods_id'=>$vo['goods_id'],'group_id'=>$_GET['group_id'],'cat_id'=>$_GET['cat_fid'],'edit'=>1,'copy'=>1))}','复制（提示：当前在进行“复制”操作，请确认商品信息无误后提交）','80%','90%',true,false,false,editbtn,'add',true);">复制</a>  | 
										<a onclick="window.top.artiframe('{pigcms{:U('Village_group/send_list',array('goods_id'=>$vo['goods_id'],'is_menu_hide'=>1))}&cat_fid={pigcms{$_GET['cat_fid']}','发货单','80%','80%',true,false,false,'','add',true)">发货单</a>
                                            <if condition="$level eq 1 and $city_manage eq 1"> <!-- 非区域管理员账号 -->
                                                <if condition="$origin neq null">
                                                |<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/add_group_product',array('goods_id'=>$vo['goods_id'],'group_id'=>$_GET['group_id'],'cat_id'=>$_GET['cat_fid'],'edit'=>1))}&city_id={pigcms{$city_id}','编辑','80%','80%',true,false,false,editbtn,'add',true);">编辑</a>  | <a class="delete_row" tip="城市绑定的对应商品也会被删除" parameter="goods_id={pigcms{$vo.goods_id}&city_id={pigcms{$city_id}" url="{pigcms{:U('Village_group/del_group')}">删除</a>
                                                </if>
                                                <else />
                                                |<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/add_group_product',array('goods_id'=>$vo['goods_id'],'group_id'=>$_GET['group_id'],'cat_id'=>$_GET['cat_fid'],'edit'=>1))}','编辑','80%','90%',true,false,false,editbtn,'add',true);">编辑</a>  | <a class="delete_row" tip="城市绑定的对应商品也会被删除" parameter="goods_id={pigcms{$vo.goods_id}" url="{pigcms{:U('Village_group/del_group')}">删除</a>
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
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<include file="Public:footer"/>