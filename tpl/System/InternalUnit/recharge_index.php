<include file="Public:header"/>
		<div class="mainbox">
            <include file="internal_unit_menu"/>
			<table class="search_table" width="100%">
				<tr>
					<td>
                        <form method="get">
                            <input type="hidden" name="c" value="{pigcms{:MODULE_NAME}"/>
                            <input type="hidden" name="a" value="{pigcms{:ACTION_NAME}"/>
                            <if condition="$admin_area_id eq 0">
                                区域：
                                <div id="choose_pca" street_id="-1" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>
                            归属单位: <input type="text" name="unit_name" class="input-text" value="{pigcms{$_GET['unit_name']}"/>

							<font color="#000">收款时间范围：</font>
                            <input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                            <input type="submit" style="margin-right:20px;" value="查询" class="button"/>
                            <a href="javascript:void(0)" onclick="exportData()" class="button" style="float:right;margin-right: 10px;">导出Excel</a>
                            <a href="javascript:void(0);" class="button" style="float:right;margin-right: 10px;" onclick="window.top.artiframe('{pigcms{:U('InternalUnit/rechargeAdd', ['id' => $vo['id']])}','添加付款记录',780,400,true,false,false,addbtn,'add',true);">添加收款</a>

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
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>归属单位</th>
								<th>区域</th>
								<th>批次号</th>
								<th>收款时间</th>
								<th style="width: 150px;">收款金额(合计：<span style="color: #ff5100;">￥{pigcms{$pay_money}</span>)</th>
                                <th>操作员</th>
								<th>备注</th>
								<th>创建时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$data['list']">
								<volist name="data.list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.unit_name}</td>
                                        <td>{pigcms{$vo.region_str}</td>
                                        <td>{pigcms{$vo.batch_no}</td>
                                        <td>{pigcms{$vo.payment_time}</td>
                                        <td>{pigcms{$vo.money}</td>
                                        <td>{pigcms{$vo.admin_name}</td>
                                        <td>{pigcms{$vo.remark}</td>
                                        <td>{pigcms{$vo.create_time}</td>
                                        <td>
                                            <if condition="$vo['is_del']">
                                                <a href="javascript:void(0);" class="delete_row" parameter="recharge_id={pigcms{$vo['id']}" url="{pigcms{:U('InternalUnit/rechargeDel')}">删除</a>
                                            </if>
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="10">{pigcms{$data.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>

<if condition="$admin_area_id eq 0">
    <script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
</if>

<script type="text/javascript">
    function exportData() {
        window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
    }
</script>