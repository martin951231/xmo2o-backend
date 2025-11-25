<if condition="$config['group_alias_name']">
	<article>
		<div class="menu cf">
			<div class="menu_left hide" style="background:{pigcms{$config.mobile_head_color}">
				<div class="menu_left_top" style="background:rgba(0,0,0,.1)">全部分类</div>
				<div class="list">
					<ul>
						<volist name="all_category_list" id="vo" key="k">
							<li>
								<div class="li_top cf">
									<if condition="$vo['cat_pic']"><div class="icon"><img src="{pigcms{$vo.cat_pic}" /></div></if>
									<div class="li_txt"><a href="{pigcms{$vo.url}">{pigcms{$vo.cat_name}</a></div>
								</div>
								<if condition="$vo['cat_count'] gt 1">
									<div class="li_bottom">
										<volist name="vo['category_list']" id="voo" offset="0" length="3" key="j">
											<span><a href="{pigcms{$voo.url}">{pigcms{$voo.cat_name}</a></span>
										</volist>
									</div>
								</if>
							</li>
						</volist>
					</ul>
				</div>
			</div>
			<div class="menu_right cf">
				<div class="menu_right_top" style="background:{pigcms{$config.mobile_head_color}">
					<ul>
						<pigcms:slider cat_key="web_slider" limit="10" var_name="web_index_slider">
							<li class="ctur">
								<a href="{pigcms{$vo.url}">{pigcms{$vo.name}</a>
							</li>
						</pigcms:slider>
					</ul>
				</div>
			</div>
		</div>
	</article>
</if>