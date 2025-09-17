<div class="modal-header">
	<a class="close js-news-modal-dismiss">×</a>
	<!-- {pigcms{:L_("顶部")}tab -->
	<ul class="module-nav modal-tab">
		<li class="<?php echo $huodong_type=="seckill"?" active":"" ?>"><a href="{pigcms{$url_arr['seckill']}" class="js-modal-tab">{pigcms{:L_("秒杀")}</a></li>
		<if condition="$page_goods_source eq 2">
			<li class="<?php echo $huodong_type=="bargain"?" active":"" ?>">|&nbsp;&nbsp;<a href="{pigcms{$url_arr['bargain']}" class="js-modal-tab">{pigcms{:L_("砍价")}</a></li>
			<li class="<?php echo $huodong_type=="tuan"?" active":"" ?>">|&nbsp;&nbsp;<a href="{pigcms{$url_arr['tuan']}" class="js-modal-tab">{pigcms{:L_("拼团")}</a></li>
		</if>

	</ul>
</div>
<div class="modal-body">
	<div class="tab-content">
		<div id="js-module-feature" class="tab-pane module-feature active">
			<?php if($is_system){ ?>
				<div style="font-size:12px;margin-bottom:15px;">您登录了管理员帐号，已显示网站所有列表。（如需只显示该店铺，请在后台退出后再点击下方的“刷新”按钮。<a href="./admin.php" target="_blank" style="color:blue;">后台链接</a>）</div>
			<?php } ?>
			<table class="table">
				<colgroup>
					<col class="modal-col-title">
					<col class="modal-col-time" span="2">
					<col class="modal-col-action">
				</colgroup>
				<!-- 表格头部 -->
				<thead>
				<tr>
                    <th class="title" style="background-color:#f5f5f5;">
                        <div class="td-cont">
                            <span>活动ID</span>
                        </div>
                    </th>
					<th class="title" style="background-color:#f5f5f5;">
						<div class="td-cont">
							<span>{pigcms{$title_msg}</span> <a class="js-update" href="javascript:window.location.reload();">刷新</a>
						</div>
					</th>
					<th class="time" style="background-color:#f5f5f5;">
						<div class="td-cont">
							<span>开始时间</span>
						</div>
					</th>
					<th class="opts" style="background-color:#f5f5f5;">
						<div class="td-cont" style="padding:7px 0 3px 10px;">
							<form class="form-search" onsubmit="return false;">
								<div class="input-append">
									<input class="input-small js-modal-search-input" type="text" style="border-radius:4px 0px 0px 4px;"/><a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" style="color:white;border-radius:0 4px 4px 0;margin-left:0px;">搜</a>
								</div>
							</form>
						</div>
					</th>
				</tr>
				</thead>
				<!-- 表格数据区 -->
				<tbody>
                <?php foreach($activity_list as $activity){ ?>
					<tr>
                        <td class="title">
                            <div class="td-cont">
                                <span><?php echo $activity['activity_id']; ?></span>
                            </div>
                        </td>
						<td class="title" style="max-width:300px;">
							<div class="td-cont">
                                <a target="_blank" class="new_window" href="javascript:"><?php echo $activity['name']; ?></a>
							</div>
						</td>
						<td class="time">
							<div class="td-cont">
								<span><?php echo date('Y-m-d H:i:s', $activity['activity_stime']); ?></span>
							</div>
						</td>
						<td class="opts">
                            <div class="td-cont">
                                <button class="btn js-choose" data-link_url="<?php echo $activity['link_url'];?>" data-price="<?php echo $activity['price'];?>" data-image_url="<?php echo $activity['image_url'];?>"  data-id="<?php echo $activity['activity_choose_id'];?>" data-atype="5" data-title="<?php echo $activity['name'];?>" <?php if(in_array($activity['activity_choose_id'],$selecteditemsArr)){echo 'disabled';}?>><?php if(in_array($activity['activity_choose_id'],$selecteditemsArr)){echo "已选过";}else{echo "选取";}?></button>
                            </div>
						</td>
					</tr>
				<?php } ?>
				</tbody>

			</table>
		</div>
	</div>
</div>
<div class="modal-footer">
	<div style="display:none;" class="js-confirm-choose left">
		<input type="button" class="btn btn-primary" value="确定使用">
	</div>
    <div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$activity_page}</div>
</div>
<script>

    $(function(){
        if ("{pigcms{$keyword}") {
            $('.js-modal-search-input').val("{pigcms{$keyword}");
        }
    });
</script>