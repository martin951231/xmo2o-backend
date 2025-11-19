<footer>
	<div class="footer1">
		<div class="footer_txt cf">
			<div class="footer_list cf">
				<ul class="cf">
					<pigcms:footer_link var_name="footer_link_list">
						<li><a href="{pigcms{$vo.url}" target="_blank">{pigcms{$vo.name}</a><if condition="$i neq count($footer_link_list)"><span>|</span></if></li>
					</pigcms:footer_link>
				</ul>
			</div>
			<div class="footer_txt">{pigcms{:nl2br(strip_tags($config['site_show_footer'],'<a>'))}
			<br/>
                    <a href="https://beian.miit.gov.cn" target="_blank">{pigcms{$config.site_icp}&nbsp;</a><img src="/static/images/gongan.png" style="vertical-align:middle;margin-right:5px;"/><a href="http://www.beian.gov.cn/portal/registerSystemInfo" target="_blank">{pigcms{$config.public_security_number}</a>
			</div>
		</div>
	</div>
</footer>
<div style="display:none;">{pigcms{$config.site_footer}</div>