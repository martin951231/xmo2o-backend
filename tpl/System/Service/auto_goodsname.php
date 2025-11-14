<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Service/auto_goodsname')}" class="on">分类列表</a>|
			<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/auto_cat_add')}','添加主分类',580,450,true,false,false,addbtn,'add',true);">添加分类</a>|
			<a href="{pigcms{:U('Service/auto_store_order_list')}"> 指定商家订单查询</a>
		</ul>
	</div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Service/auto_goodsname')}" method="get">
                    <input type="hidden" name="c" value="Service"/>
                    <input type="hidden" name="a" value="auto_goodsname"/>
                    筛选:
                    <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}"  style="display:inline"></div>
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
						<th>排序</th>
						<th>编号</th>
						<th>分类名称</th>
                        <th>所属城市</th>
						<th>管理商品</th>
						<th>状态</th>
						<th class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="!empty($category_list)">
						<volist name="category_list" id="vo">
							<tr>
								<td>{pigcms{$vo.sort}</td>
								<td>{pigcms{$vo.id}</td>
								<td>{pigcms{$vo.name}</td>
                                <td>
                                    <if condition="$vo.is_common eq 0">
                                          通用
                                        <else/>
                                        {pigcms{$vo.area_name}
                                    </if>
                                </td>
								<td>
									<a href="{pigcms{:U('Service/auto_goods',array('cid'=>$vo['id'],'type'=>$vo['type']))}">
										<if condition="$vo['type'] eq 0">
											管理自定义商品
										<else/>
											管理指定店铺
										</if>
										（{pigcms{$vo.nums}）
									</a>
								</td>
								<td><if condition="$vo['status'] eq 1"><font color="green">启用</font><else/><font color="red">关闭</font></if></td>

								<td class="textcenter">
									<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/auto_cat_add',array('id'=>$vo['id']))}','编辑分类信息',580,450,true,false,false,editbtn,'add',true);">编辑</a> | 
									<a href="javascript:void(0);" class="delete_row" tip="操作后会同步删除该分类且不能恢复！" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Service/auto_cat_del')}">删除</a>
								</td>
							</tr>
						</volist>
					<else/>
						<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
					</if>
				</tbody>
			</table>
		</div>
	</form>
</div>
<include file="Public:footer"/>