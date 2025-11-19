<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Platform_delivery/index')}">{pigcms{:L_("平台配送开放接口管理")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
                .title {
                    padding: 15px;
                    font-size: 1.6rem;
                    font-weight: 600;
                }
                .msg {
                    padding: 10px 0;
                }
			</style>
			<div class="row">
                <div class="col-xs-12">
                    <div class="title">平台配送开放接口管理</div>
                    <if condition="$info && $info['appid'] && $info['key']">
                        <div class="grid-view">
                            <div class="span12" style="margin-top:10px;">
                                <div class="col-xs-12 msg">
                                    <div class="col-xs-2">平台配送appid :</div>
                                    <div class="col-xs-10">{pigcms{$info['appid']}</div>
                                </div>
                                <div class="col-xs-12 msg">
                                    <div class="col-xs-2">平台配送key :</div>
                                    <div class="col-xs-10">{pigcms{$info['key']}</div>
                                </div>
                            </div>
                        </div>
                    </if>
                </div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function(){
	});
</script>
<include file="Public:footer"/>
