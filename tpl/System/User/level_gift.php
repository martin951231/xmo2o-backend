<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/level_gift')}" class="on">礼包管理</a>
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/level_gift_add')}','添加礼包',800,650,true,false,false,addbtn,'add',true);" style="margin-left:20px;">添加礼包</a>
                    <a href="{pigcms{:U('User/level_gift_order')}">礼包领取记录</a>
				</ul>
			</div>
			<!--<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('User/index')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="index"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>用户ID</option>
								<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>-->
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th width="50px">ID</th>
								<th >礼包名称</th>
								<th >会员等级</th>
								<th >图片</th>
                                <th >状态</th>
                                <th >创建时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.gift_id}</td>
										<td>{pigcms{$vo.gift_name}</td>
                                        <td>{pigcms{$vo.lname}({pigcms{$vo.level})</td>
										<td><img src="{pigcms{:file_domain()}{pigcms{$vo.gift_img}" style="width:90px; height: 80px;" class="view_msg"></td>

                                        <td><if condition="$vo['gift_status'] eq 1">开启<elseif condition="$vo['gift_status'] eq 2"/>关闭<else/>删除</if></td>

                                        <td>{pigcms{$vo['add_time']|date='Y-m-d H:i:s',###}</td>
										<td><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/level_gift_add',array('gift_id'=>$vo['gift_id']))}','编辑礼包信息',650,500,true,false,false,editbtn,'edit',true);">编 辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="gift_id={pigcms{$vo['gift_id']}" url="/admin.php?g=System&c=User&a=del_level_gift">删除</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="7">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>