
<div class="fixed-box">
	<div class="header-box" style="<if condition='$config["mobile_head_color"]'>background: {pigcms{$config["mobile_head_color"]};</if>">
		<div class="ks-clear link" data-url="{pigcms{:U('leader',array('leader_id'=>$_GET['leader_id']))}">
			<div class="avatar">
				<img class="img <if condition="$config.village_group_avatar_head_img neq ''">active</if>" src="{pigcms{$header.avatar}">
				<!-- 头像上的小图标  clas类名hidden表示隐藏 如果有小图标的话，要给头像的类名加active-->
				<if condition="$config.village_group_avatar_head_img neq ''"><img class="icon" src="{pigcms{$config.village_group_avatar_head_img}"></if>
			</div>
			<div class="info">
				<div>
					<span>{pigcms{$header.name}</span>
					<a href="tel:{pigcms{$header.phone}"><span>{pigcms{$header.phone}</span></a>
				</div>
				<div> {pigcms{$header.pick_address.name}</div>
				<i class="icon iconfont">&#xe603;</i>
			</div>
		</div>
		<div class="copy" style="<if condition='$config["mobile_head_color"]'>color: {pigcms{$config["mobile_head_color"]};</if>">
			<p>一键复制</p>
			<p>拼团信息</p>
		</div>
	</div>

	<!-- 菜单 -->
	<div class="swiper-container" id='menuBox'>
	    <div class="swiper-wrapper">
			<div class="swiper-slide" data-cat_id="0">
				<div data-color="<if condition='$config["mobile_head_color"]'>{pigcms{$config["mobile_head_color"]}</if>"><span class="active" style="<if condition='$config["mobile_head_color"]'>    border-bottom: 3px solid {pigcms{$config["mobile_head_color"]};color: {pigcms{$config["mobile_head_color"]};</if>">{pigcms{$config.village_group_hotcat_title}</span></div>
			</div>
			<volist name="category" id="vo">
				<div class="swiper-slide" data-cat_id="{pigcms{$key}">
					<div data-color="<if condition='$config["mobile_head_color"]'>{pigcms{$config["mobile_head_color"]}</if>"><span>{pigcms{$vo}</span></div>
				</div>
			</volist>
	    </div>
	</div>
</div>