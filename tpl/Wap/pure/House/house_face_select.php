<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>选择人员</title>
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name='apple-touch-fullscreen' content='yes' />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="format-detection" content="address=no" />

    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/new_village.css" />
    <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js?210" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
	
	<style>
		.page__hd {
			padding: 20px;
		}
		.page__title {
			text-align: left;
			font-size: 20px;
			font-weight: 400;
		    margin: 20px 0 10px;
		}
		.page__desc {
			margin-top: 4px;
			color: rgba(0,0,0,.5);
			text-align: left;
			font-size: 12px;
		}
	</style>
</head>

<body class="village_my">
	<div class="page__hd">
        <h1 class="page__title">请选择您要绑定的人员</h1>
        <p class="page__desc">为了方便没有手机的家属也可正常使用人脸门禁，业主可以为家属上传照片。</p>
    </div>
	<nav>
		<section class="link-url" data-url="{pigcms{:U('House/face_img_add',array('pigcms_id'=>$now_village_bind['pigcms_id']))}" >
			<p style="padding-left: 10px;">本人</p><p style="float: right;padding-right: 25px;"><if condition="$now_village_bind['is_bind_face']">已绑定<else/>前往绑定</if></p>
		</section>
		<if condition="$village_info">
			<volist name="village_info" id="item">
				<section class="link-url" data-url="{pigcms{:U('House/face_img_add',array('pigcms_id'=>$item['pigcms_id']))}" >
					<p style="padding-left: 10px;">{pigcms{$item['name']}</p><p style="float: right;padding-right: 25px;"><if condition="$item['is_bind_face']">已绑定<else/>前往绑定</if></p>
				</section>
			</volist>
		</if>
	</nav>
	{pigcms{$hideScript}
</body>
</html>