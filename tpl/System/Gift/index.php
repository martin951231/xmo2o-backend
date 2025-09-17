<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<eq name="is_check" value='1'>
				<div id="nav" class="mainnav_title">
					<ul>
						<a href="{pigcms{:U('index',array('is_check'=>$is_check))}" class="on">待审核礼品列表</a>
					</ul>
				</div>
			<else/>
				<div id="nav" class="mainnav_title">
					<ul>
						<a href="{pigcms{:U('index')}" class="on">礼品列表</a>|
						<a href="{pigcms{:U('gift_add')}" >添加礼品</a>
					</ul>
				</div>
			</eq>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('index',array('is_check'=>$is_check))}" method="get">
							<input type="hidden" name="c" value="Gift"/>
							<input type="hidden" name="a" value="index"/>
							<input type="hidden" name="is_check" value="{pigcms{$is_check}"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="gift_name" <if condition="$_GET['searchtype'] eq 'gift_name'">selected="selected"</if>>礼品名称</option>
								<option value="gift_id" <if condition="$_GET['searchtype'] eq 'gift_id'">selected="selected"</if>>礼品编号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<neq name="is_check" value='1'>
								商品状态: <select name="searchstatus">
									<option value="0" <if condition="$_GET['searchstatus'] eq 0">selected="selected"</if>>全部</option>
									<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>进行状态</option>
									<option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>关闭状态（或已结束）</option>
								</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							</neq>
							商家区域：<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
							<input type="submit" value="查询" class="button"/>
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
								<th>排序</th>
								<th>编号</th>
								<th>名称</th>
								<th>礼品来源</th>
								<th>分类</th>
								<th>销售概览</th>
								<th>所需{pigcms{$config['score_name']}</th>
								<th>所需金额</th>
								<th>状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list['list'])">
								<volist name="list['list']" id="vo">
									<tr>
										<td>{pigcms{$vo.sort}</td>
										<td>{pigcms{$vo.gift_id}</td>
										<td><if condition="$vo['is_hot']"><font color="red">{pigcms{$vo.gift_name}</font><else/>{pigcms{$vo.gift_name}</if></td>
										<td>
											<gt name='vo.mer_id' value='0'>
											商家：{pigcms{$vo.mer_name}
											<else/>
												平台
											</gt>
										</td>
										<td>{pigcms{$cat_list[$vo["cat_fid"]]}&nbsp;&nbsp;-&nbsp;&nbsp;{pigcms{$cat_list[$vo["cat_id"]]}</td>
										<td>售出：{pigcms{$vo.sale_count} 份</td>
										<td>
										<if condition='$vo["exchange_type"] eq 0'>
											{pigcms{$vo.payment_pure_integral}
										<else />
											{pigcms{$vo.payment_integral}
										</if>
										</td>
										<td>
										<if condition='$vo["exchange_type"] eq 1'>
											{pigcms{$vo.payment_money}
										</if>
										</td>
										<td>
											<if condition="$vo['status'] eq 1">
												<font color="green">启用</font>
											<elseif condition="$vo['status'] eq 2"/>
												<font color="red">待审核</font>
											<elseif condition="$vo['status'] eq 4"/>
												<font color="red">审核不通过</font>
											<else/>
												<font color="red">关闭</font>
											</if>
										</td>
										
										<td class="textcenter">
											<eq name='is_check' value='1'>
												<a href="{pigcms{:U('save_merchant_gift',array('gift_id'=>$vo['gift_id'],'status'=>3))}">通过</a> | <a href="{pigcms{:U('save_merchant_gift',array('gift_id'=>$vo['gift_id'],'status'=>4))}">不通过</a>|
												<a href="{pigcms{:U('gift_edit',array('gift_id'=>$vo['gift_id'],'no_edit'=>1))}">查看详情</a>
											<else/>
												<a href="{pigcms{:U('gift_edit',array('gift_id'=>$vo['gift_id']))}">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="gift_id={pigcms{$vo.gift_id}" url="{pigcms{:U('gift_del')}">删除</a>
											</eq>
                                         </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="30">{pigcms{$list.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="30">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>