<include file="Public:header"/>
<style type="text/css">
.mark_box{
	display:block;
	position: relative;
	margin: 10px 0;
}
.mark_box i.mark{
	display: block;
    position: absolute;
    line-height: 16px;
    top: -6px;
    background: #f00;
    color: #fff;
    padding: 0 5px;
    left: 25px;
    border-radius: 10px;
    border-bottom-left-radius: 0;
    font-size: 11px;
}
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="#" class="on">{pigcms{$title} - 导航列表</a>|
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Baodi/adver_add',array('block'=>$block))}','添加导航',600,400,true,false,false,addbtn,'add',true);">添加导航</a>
				</ul>
			</div>
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
							<col width="180" align="center"/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>排序</th>
								<th>名称</th>
								<if condition="$block eq 2"><th>上级</th></if>
								<th>链接地址</th>
								<th>图片(以下为强制小图，点击图片查看大图)</th>
								<th class="textcenter">添加时间</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<notempty name="list">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.sort}</td>
										<td>{pigcms{$vo.title}</td>
										<if condition="$block eq 2"><td>{pigcms{$vo.parent}</td></if>
										<td><a href="{pigcms{$vo.link}" target="_blank">访问链接</a></td>
										<td>
											<if condition="$vo['pic']">
												<img src="{pigcms{$vo.pic}" style="width:50px;height:50px;" class="view_msg"/>
											<else/>
												没有图片
											</if>
										</td>
										<td class="textcenter">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Baodi/adver_add',array('id'=>$vo['id']))}','编辑信息',600,400,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Baodi/adver_del')}">删除</a></td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</notempty>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script type="text/javascript">
			function dialogConfirm() {
	            console.log(3);
	        }

	        function dialogCancel(){
	            console.log(4)
	        }
		</script>
<include file="Public:footer"/>