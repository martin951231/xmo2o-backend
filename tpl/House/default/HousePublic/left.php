
<div id="new-sidebar">
	<ul class="new-nav new-nav-list">
		<volist name="house_menu" id="vo">
			<php>if($vo['menu_list'] || $vo['url']){</php>
				<li class="<if condition="$vo['is_active']">open active<elseif condition="$vo['menu_list']"/>not-open</if>">
					<a data-jump_type="{pigcms{$vo.jump_type}" <if condition="$vo['menu_list']">href="javascript:;" class="dropdown-toggle"<else/>href="{pigcms{$vo.url}"</if> <if condition="$vo['module'] eq 'House'">target="_blank"</if>>
						<i class="menu-icon fa {pigcms{$vo.icon}"></i>
						<span class="menu-text">{pigcms{$vo.name}</span>
					</a>
					<b class="arrow"></b>
					<if condition="$vo['menu_list']">
						<div class="submenu-box">
							<div class="second-sidebar-title">{pigcms{$vo.name}</div>
							<ul class="submenu">
								<volist name="vo['menu_list']" id="voo">
									<li <if condition="$voo['is_active']">class="show open"</if>>
										<a data-jump_type="{pigcms{$voo.jump_type}" <if condition="$voo['menu_list']">href="javascript:;" class="dropdown-toggle"<else/>href="{pigcms{$voo.url}"</if>><span class="menu-text">{pigcms{$voo.name}</span></a>
										<ul <if condition="$voo['is_active']">class="submenu nav-show open"<else />class="submenu nav-hide"</if>>
											<volist name="voo['menu_list']" id="val">
												<li <if condition="$val['is_active']">class="nav-show open"</if>>
												<a data-jump_type="{pigcms{$val.jump_type}" href="{pigcms{$val.url}"><span class="menu-text">{pigcms{$val.name}</span></a>
												</li>
											</volist>
										</ul>
									</li>
								</volist>
							</ul>
						</div>
					</if>
				</li>
			<php>}</php>
		</volist>
	</ul>
<!-- /.nav-list -->
</div>
<div id="new-third-sidebar">
	<div class="swiper-container" id='nav-scroll'>
		<div class="ui-nav clearfix swiper-wrapper"></div>
	</div>
</div>