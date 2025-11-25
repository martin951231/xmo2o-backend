<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<if condition="$config['open_school'] eq '1'">
						<a href="{pigcms{:U('User/index')}" <if condition="empty($_GET['school_type'])">class="on"</if>>SYSTEM_USER_INDEX_1</a>
						<a href="{pigcms{:U('User/index?school_type=1')}" <if condition="!empty($_GET['school_type'])">class="on"</if>>校外用户列表</a>
					<else />
					<a href="{pigcms{:U('User/index')}" class="on">SYSTEM_USER_INDEX_2</a>
					</if>
					<a href="{pigcms{:U('User/label_index')}">SYSTEM_USER_INDEX_3</a>
					<a href="{pigcms{:U('User/privileged_user')}">SYSTEM_USER_INDEX_4</a>
					<if condition="$config.open_user_money_verify eq '1'">
						<a href="{pigcms{:U('User/user_money_verify')}">用户余额变动申请列表</a>
					</if>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('User/index')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="index"/>
							<input type="hidden" name="school_type" value="{pigcms{$_GET.school_type}"/>
							<if condition="$admin_area neq 3 && $config['many_city']">
								<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" style="display:inline"></div>
							</if>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
								<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
								<if condition="$config['open_school'] eq '1'">
									<option value="school_no" <if condition="$_GET['searchtype'] eq 'school_no'">selected="selected"</if>>学工号</option>
									<option value="school_sfz" <if condition="$_GET['searchtype'] eq 'school_sfz'">selected="selected"</if>>身份证号</option>
								</if>
								<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>用户ID</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<font color="#000">注册日期筛选：</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							等级:
							<select name="level">
								<option value="-1">无</option>
									<volist name="levelarr" id="vo">
									<option value="{pigcms{$vo['level']}" <if condition="$_GET['level'] eq $vo['level']"> selected="selected"</if>>{pigcms{$vo['lname']}</option>
									</volist>
									</select>
								</if>
							</select>
							<notempty name='labelarr'>
							标签:
							<select name='label_id'>
								<option value='-1'>全部</option>
								<volist name='labelarr' id='vo'>
									<option value="{pigcms{$vo.id}" <if condition="$_GET['label_id'] eq $vo['id']"> selected="selected"</if> >{pigcms{$vo.name}</option>
								</volist>
							</select>
							</notempty>
							状态:
							<select name="status">
								<option value="" <if condition="$_GET['status'] eq ' '">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>正常</option>
								<option value="0" <if condition="$_GET['status'] eq '0'">selected="selected"</if>>禁用</option>
                                <if condition="$isRoot">
                                    <option value="4" <if condition="$_GET['status'] eq '4'">selected="selected"</if>>已注销</option>
                                </if>
							</select>

							<if condition="$config['open_spread_appoint_user']">
								是否具有分佣功能:
								<select name="can_spread">
									<option value="" <if condition="$_GET['can_spread'] eq ' '">selected="selected"</if>>全部</option>
									<option value="1" <if condition="$_GET['can_spread'] eq '1'">selected="selected"</if>>是</option>
									<option value="0" <if condition="$_GET['can_spread'] eq '0'">selected="selected"</if>>否</option>
								</select>
							</if>
							<input type="submit" style="margin-right:20px;" value="查询" class="button"/>
							<notempty name="export">
							<a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出用户</a>
							</notempty>
						</form>
						<p style="margin:5px 0;">
							用户总余额：<if condition="$user_balance['count']">{pigcms{$user_balance['count']}<else/>0</if>{pigcms{$config['Currency_txt']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							正常用户总余额：<if condition="$user_balance['open']">{pigcms{$user_balance['open']}<else/>0</if>{pigcms{$config['Currency_txt']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							禁止用户总余额：<if condition="$user_balance['close']">{pigcms{$user_balance['close']}<else/>0</if>{pigcms{$config['Currency_txt']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							用户总{pigcms{$config.score_name}：{pigcms{$user_balance['score']|floatval}个
						</p>
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
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th><a href="{pigcms{:U('User/index',array('sort'=>'uid'))}" style="color:blue;">ID</a></th>
								<th>昵称</th>
								<if condition="!isset($config['not_o2o_demo'])">
									<th>手机号</th>
								</if>
								<if condition="$config['open_school'] eq '1' && $_GET['school_type'] neq '1'">
									<th>身份证号</th>
									<th>学工号</th>
								</if>
								<if condition="$config['many_city']">
									<th>所在城市</th>
								</if>
								<th><a href="{pigcms{:U('User/index',array('sort'=>'lastTime'))}" style="color:blue;">最后登录时间</a></th>
								<th>最后登录地址</th>
								<th class="textcenter"><a href="{pigcms{:U('User/index',array('sort'=>'money'))}" style="color:blue;">余额</a></th>
								<th class="textcenter"><a href="{pigcms{:U('User/index',array('sort'=>'score'))}" style="color:blue;">{pigcms{$config['score_name']}</a></th>
								<th class="textcenter">来源</th>
								<th class="textcenter">状态</th>
                                <if condition="$config.open_user_spread eq 1"><th class="textcenter">SYSTEM_USER_INDEX_5</th></if>
                                <if condition="$discuzx_url"><th class="textcenter">Discuzx论坛用户名</th></if>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($user_list)">
								<volist name="user_list" id="vo">
									<tr>
										<td>{pigcms{$vo.uid}</td>
										<td>
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','查看用户信息',880,650,true,false,false,editbtn,'edit',true);">	
												{pigcms{$vo.nickname}
											</a>
										</td>
										<php>if(!isset($config['not_o2o_demo'])){</php>
											<td><if condition="$vo['phone_country_type']">+{pigcms{$vo.phone_country_type}&nbsp;</if>{pigcms{$vo.phone}</td>
										<php>}</php>
										<if condition="$config['open_school'] eq '1' && $_GET['school_type'] neq '1'">
											<td>{pigcms{$vo.school_sfz}</td>
											<td>{pigcms{$vo.school_no}</td>
										</if>
										<if condition="$config['many_city']">
											<td>{pigcms{$vo.city_name|default='<font color="#bbb">未知</font>'}</td>
										</if>
                                        <td><if condition="$vo['last_time'] gt 0">
                                                {pigcms{$vo.last_time|date='Y-m-d H:i:s',###}
                                                <else/>
                                                无
                                            </if>
                                        </td>
										<td>{pigcms{$vo.last_ip_txt}</td>
										<td class="textcenter">{pigcms{$config['Currency_symbol']}{pigcms{$vo.now_money|floatval=###}</td>
										<td class="textcenter">{pigcms{$vo.score_count}</td>
										<td class="textcenter"><php>if(strpos($vo['source'],'weixin')===0){</php>
										微信公众号
										<php>}elseif(strpos($vo['source'],'wxapp')===0){</php>
										微信小程序
										<php>}elseif(strpos($vo['source'],'houseautoreg')===0){</php>
                                        小区自动注册
										<php>}elseif($vo['source'] == 'sms'){</php>
                                        手机号注册
										<php>}elseif(strpos($vo['source'],'wap')===0){</php>
										{pigcms{$client.0}
										<php>}elseif(strpos($vo['source'],'alipayapp')===0){</php>
										支付宝小程序
										<php>}elseif(strpos($vo['source'],'alipay')===0){</php>
										{pigcms{$client.6}
										<php>}elseif($vo['client'] == 2 || strpos($vo['source'],'androidapp')===0){</php>
										{pigcms{$client.2}
										<php>}elseif($vo['client'] == 1 || strpos($vo['source'],'iosapp')===0){</php>
										{pigcms{$client.1}
										<php>}elseif($vo['client'] == 1 || strpos($vo['source'],'scan_mer_qrcode')===0){</php>
										{pigcms{$client.7}
										<php>}elseif(strpos($vo['source'],'wxcapp')===0){</php>
										社群小程序
                                        <php>}elseif(strpos($vo['source'],'wxapp_paotui')===0){</php>
                                        跑腿小程序
										<php>}else{</php>
										{pigcms{$client.3}
										<php>}</php></td>
										<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">正常</font><elseif condition="$vo['status'] eq 2" /><font color="red">未审核</font><elseif condition="$vo['status'] eq 4" /><font color="red">已注销</font><else /><font color="red">禁用</font></if></td>
										<if condition="$config.open_user_spread eq 1">
											<td class="textcenter"><if condition="$vo.openid neq '' OR $vo.wxapp_openid neq ''"><a href="{pigcms{:U('User/spread_higher_user',array('openid'=>$vo['openid'],'uid'=>$vo['uid']))}">SYSTEM_USER_INDEX_6</a> | <a href="{pigcms{:U('User/spread_user',array('action'=>'spread_user','openid'=>$vo['openid'],'uid'=>$vo['uid']))}">SYSTEM_USER_INDEX_7</a>
											 | <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/edit_spread',array('uid'=>$vo['uid']))}','编辑推广用户上下级',680,500,true,false,false,editbtn,'edit',true);">修改上下级</a>
											
											</if></td>
										</if>

                                        <if condition="$discuzx_url">
                                            <td class="textcenter">{pigcms{$vo.discuzx_name}</td>
                                        </if>
										<td class="textcenter">
                                            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',880,650,true,false,false,editbtn,'edit',true);"><if condition="$vo['status'] eq 2">审核<else />编辑</if></a>
                                            <if condition="$isRoot AND ($vo['status'] eq 4)">
                                                &nbsp;|&nbsp;<a href="javascript:void(0);" class="restore_row" data-phone="{pigcms{$vo.phone}" data-uid="{pigcms{$vo.uid}" data-nickname="{pigcms{$vo.nickname}">恢复</a>
                                            </if>
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script>
		var url = "{pigcms{$config.site_url}"
		var export_url = "{pigcms{:U('User/export')}"


        $('.restore_row').click(function () {
            var now_dom = $(this);
            var nickname = now_dom.data('nickname');
            var uid = now_dom.data('uid');
            var phone = now_dom.data('phone');
            window.o2oTop.art.dialog({
                icon: 'question',
                title: '请确认',
                id: 'msg' + Math.random(),
                lock: true,
                fixed: true,
                opacity: '0.4',
                resize: false,
                content: "确定恢复用户ID：" + uid + '、手机号：' + phone + '吗？',
                ok: function () {
                    $.post("{pigcms{:U('User/restore')}", {uid:uid}, function (result) {
                        if (result.error_no == 0) {
                            window.o2oTop.msg(1, result.error_msg, true);
                            window.location.reload();
                        } else {
                            window.o2oTop.msg(0, result.error_msg);
                        }
                    });
                },
                cancel: true
            });
            return false;
        });
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>