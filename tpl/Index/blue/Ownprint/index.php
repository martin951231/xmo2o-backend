<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>今日未打印小票记录</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<style>
			body{margin:0;}
		</style>
	</head>
	<body>
		<h2 style="text-align:center;">今日未打印小票记录</h2>
		<p style="width:80%;margin:20px auto 0px;">当前服务器时间：{pigcms{:date('Y-m-d H:i:s')}</p>
		<p style="width:80%;margin:0px auto;">今日总小票数：{pigcms{$orderprint_count}</p>
		<p style="width:80%;margin:0px auto 10px;">今日未打小票数：{pigcms{:count($orderprint_list)}</p>
		<table style="width:80%;margin:0 auto;" border="1" cellspacing="0">
			<tr>
				<th>打印机密钥</th>
				<th>打印内容</th>
				<th>生成时间</th>
				<th>操作</th>
			</tr>
			<volist name="orderprint_list" id="value">
				<tr>
					<td style="width:17%;">
						{pigcms{$value['mkey']}
						<br/>
						<span style="font-size:12px;">
							<if condition="$value['staff']">
								店员版本号：{pigcms{$value.staff.app_version_name}
								<br/>
								最后通信时间：{pigcms{$value.staff.last_time|date='m-d H:i:s',###}
								<br/>
								所在城市：{pigcms{$value.staff.city_name}
							<else/>
								不存在店员，疑似作废机器
							</if>
						</span>
					</td>
					<td style="width:40%;"><pre>{pigcms{$value['content']}</pre></td>
					<td style="width:17%;">{pigcms{:date('H:i:s',$value['add_time'])}</td>
					<td style="width:17%;text-align:center;"><a href="{pigcms{:U('del',['print_id'=>$value['print_id']])}">删除</a></td>
				</tr>
			</volist>
		</table>
	</body>
</html>