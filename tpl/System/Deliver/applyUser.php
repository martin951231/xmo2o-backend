<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Deliver/user')}">配送员管理</a>|
					<a href="{pigcms{:U('Deliver/lenTime')}">配送员时长统计</a>|
                    <a href="{pigcms{:U('Deliver/applyUser')}" class="on">待审核配送员</a>|
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
						<form action="{pigcms{:U('Deliver/applyUser')}" method="get">
							<input type="hidden" name="c" value="Deliver"/>
							<input type="hidden" name="a" value="applyUser"/>
							筛选:
							<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" style="display:inline"></div>
                            <select name="searchtype">
                                <option value="truename" <if condition="$_GET['searchtype'] eq 'truename'">selected="selected"</if>>配送员姓名</option>
                                <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
                                <option value="card_number" <if condition="$_GET['searchtype'] eq 'card_number'">selected="selected"</if>>身份证号</option>
                            </select>
                            <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>


                            申请日期：
                            <input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>至
                            <input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
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
								<th style="width: 10%;">昵称</th>
								<th style="width: 15%;">手机号</th>
								<th style="width: 15%;">身份证号</th>
								<th style="width: 15%;">意向工作城市</th>
								<th style="width: 20%;">意向工作地址</th>
								<th style="width: 15%;">申请时间</th>
                                <!--<th>审核状态</th>
                                <th>审核时间</th>-->
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($users)">
								<volist name="users" id="vo">
									<tr>
										<td>{pigcms{$vo.truename}</td>
										<td>{pigcms{$vo.phone}</td>
										<td>{pigcms{$vo.card_number}</td>
										<td><php>echo ($areas[$vo['province_id']]??'-').($areas[$vo['city_id']]??'-');</php></td>
										<td>{pigcms{$vo.adress|default='-'}</td>
										<td>{pigcms{$vo.apply_time|date='Y-m-d H:i:s',###}</td>
                                        <!--<td>
                                            <php>
                                                if($vo['status'] == 0){
                                                    echo '待审核';
                                                }else if($vo['status'] == 1){
                                                    echo '通过';
                                                }else if($vo['status'] == 0){
                                                    echo '驳回';
                                                }
                                            </php>
                                        </td>
                                        <td><if condition="$vo['handle_time'] gt 0">{pigcms{$vo.handle_time|date='Y-m-d H:i:s',###}<else/>--</if></td>-->
										<td class="textcenter">
                                            <if condition="$vo['status'] eq 0">
                                                <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Deliver/handleRegister',array('id'=>$vo['id']))}','配送员审核',860,650,true,false,false,editbtn,'edit',true);">审核</a>
                                            <else/>
                                                --
                                            </if>
										</td>
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