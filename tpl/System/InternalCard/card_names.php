<include file="card_manage_header"/>
<style>
    .statistics {
        display: grid;
        grid-template-columns: repeat(6, 1fr); /*创建三列，每列占据可用空间的1/3 */
        grid-gap: 30px; /*网格项之间的间距为10rpx */
        column-gap: 50px; /*网格项上下之间的间距为10rpx */
        margin-bottom: 20px;
        .item {
            flex: 1;
            border: 1px solid rgba(153, 153, 153, 0.4);
            padding: 15px 20px;
            .title {
                color: #000;
                margin-bottom: 5px;
                font-size: 16px;
                text-align: left;
            }
            .money {
                color: #272727;
                font-size: 22px;
                font-weight: bold;
                text-align: center;
            }
        }
    }
</style>
		<div class="mainbox">
            <include file="internal_unit_menu"/>
			<table class="search_table" width="100%">
                <if condition="$system_session['level'] == 2">
                    <tr>
                        <td>
                            <a href="javascript:void(0);" class="button" style="float:right;margin-right: 10px;" onclick="window.top.artiframe('{pigcms{:U('InternalCard/namesAdd')}','添加一卡通',780,400,true,false,false,addbtn,'add',true);">添加</a>
                        </td>
                    </tr>
                </if>
			</table>
            <div class="statistics">
                <div class="item">
                    <div class="title">总用户数</div>
                    <div class="money">{pigcms{$sumStatistics['card_count']|getFormatNumber=###}</div>
                </div>
                <div class="item">
                    <div class="title">卡内总余额</div>
                    <div class="money">￥{pigcms{$sumStatistics['card_total_money']|getFormatNumber=###}</div>
                </div>
                <div class="item">
                    <div class="title">总充值余额</div>
                    <div class="money">￥{pigcms{$sumStatistics['card_recharge_money']|getFormatNumber=###}</div>
                </div>
            </div>
            <form action="{pigcms{:U('InternalCard/names')}" method="get">
                <input type="hidden" name="c" value="InternalCard"/>
                <input type="hidden" name="a" value="names"/>
                <div style="padding-bottom: 10px;">
                    <if condition="$admin_area_id eq 0">
                        区域：
                        <div id="choose_pca" street_id="-1" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                    </if>
                    卡名称: <input type="text" name="card_name" class="input-text" value="{pigcms{$_GET['card_name']}"/>
                    归属单位: <input type="text" name="unit_name" class="input-text" value="{pigcms{$_GET['unit_name']}"/>
                    <if condition="$level == 2">
                        管理员名称: <input type="text" name="belong_admin_name" class="input-text" value="{pigcms{$_GET['belong_admin_name']}"/>
                    </if>
                    店铺名称: <input type="text" name="store_name" class="input-text" value="{pigcms{$_GET['store_name']}"/>
                    <input type="submit" style="margin-right:10px;" value="查询" class="button"/>
                </div>
           
                
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
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>一卡通名称</th>
								<th>归属管理员</th>
								<th>总用户数<span style="color: #ff5100;">({pigcms{$card_count})</span></th>
								<th>卡内总余额<span style="color: #ff5100;">(￥{pigcms{$card_total_money})</span></th>
								<th>总充值金额<span style="color: #ff5100;">(￥{pigcms{$card_recharge_money})</span></th>
								<th>状态</th>
								<th>指定区域</th>
								<th>指定店铺</th>
								<th>充值店铺</th>
								<th>最后操作员</th>
								<th>更新时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$lists">
								<volist name="lists" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.card_name}</td>
										<td>{pigcms{$vo.belong_admin_name}</td>
										<td>
											<!-- <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/index', ['card_name' => $vo['card_name'],'type'=>'show_user_list'])}','会员列表',780,400,true,false,false,editbtn,'add',true);"> -->
												{pigcms{$vo.card_count}
											<!-- </a> -->
										</td>
										<td>{pigcms{$vo.card_total_money|getFormatNumber=###}</td>
                                        <td>{pigcms{$vo.card_recharge_money|getFormatNumber=###}</td>
										<td>
											<if condition="$vo['status'] eq 1">
												<font color="blue">正常</font>
											<else/>
												<font color="red">禁用</font>
											</if>
										</td>
										<td>
											<a onclick="window.top.artiframe('{pigcms{:U('InternalCard/area', ['name_id' => $vo['id']])}','指定区域',780,600,true,false,false,false,'',true);">指定区域</a>
										</td>
										<td>
											<a onclick="window.top.artiframe('{pigcms{:U('InternalCard/store', ['name_id' => $vo['id']])}','指定店铺',900,600,true,false,false,false,'',true);">指定店铺</a>
										</td>
										<td>{pigcms{$vo.store_name}<br/>【ID:{pigcms{$vo.store_id}】</td>
                                        <td>{pigcms{$vo.admin_name}</td>
                                        <td>{pigcms{$vo.update_time}</td>
										<td>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/cardUnits', ['card_name' => $vo['card_name'],'from'=>$_GET['from']])}','查看关联单位列表',900,450,true,false,false,false,'',false);">单位列表</a>
                                            <if condition="$system_session['level'] == 2">
                                                &nbsp;|&nbsp;
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/namesAdd', ['id' => $vo['id']])}','编辑一卡通',780,400,true,false,false,editbtn,'add',true);">编辑</a> &nbsp;|&nbsp;
                                            <a href="javascript:void(0);" class="delete_row" parameter="id=10" url="{pigcms{:U('InternalCard/namesDel',array('id'=>$vo['id']))}">删除</a>
                                            </if>
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="14">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="14">列表为空！</td></tr>
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

