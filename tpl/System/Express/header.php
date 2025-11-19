<div id="nav" class="mainnav_title">
	<ul>
		<a href="{pigcms{:U('Express/index')}" <if condition="MODULE_NAME eq 'Express'">class="on"</if>>快递公司列表</a>|
		<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Express/add')}','添加快递公司',520,450,true,false,false,addbtn,'add',true);">添加快递公司</a>|
		<a href="{pigcms{:U('Config/index',array('galias'=>'kdniao','header'=>'Express/header'))}" <if condition="$galias eq 'kdniao'">class="on"</if>>快递鸟配置</a>
		<a href="{pigcms{:U('Config/index',array('galias'=>'kd100','header'=>'Express/header'))}" <if condition="$galias eq 'kd100'">class="on"</if>>快递100配置</a>
	</ul>
</div>