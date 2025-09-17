	<div class="site-info-w">
	    <div class="site-info">
	        <div class="copyright">
	            {pigcms{:nl2br($config['site_show_footer'],'<a>')}
				<br/>
                    <a href="https://beian.miit.gov.cn" target="_blank">{pigcms{$config.site_icp}&nbsp;</a><img src="/static/images/gongan.png" style="vertical-align:middle;margin-right:5px;"/><a href="http://www.beian.gov.cn/portal/registerSystemInfo" target="_blank">{pigcms{$config.public_security_number}</a>
	        </div>
	    </div>
		<div style="display:none;">{pigcms{$config.site_footer}</div>
	</div>