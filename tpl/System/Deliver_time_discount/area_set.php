<include file="Public:header"/>
<style type="text/css">
	@charset "UTF-8";
	* {
	    box-sizing: border-box;
	}
	a {
	    text-decoration: none;
	    color: #379937;
	}
	body {
	    margin: 40px;
	}
	.dropdown {
	    position: relative;
	    font-size: 14px;
	    color: #333;
	}
	.dropdown .dropdown-list {
	    padding: 12px;
	    background: #fff;
	    position: absolute;
	    /*top: 30px;*/
	    left: 2px;
	    right: 2px;
	    box-shadow: 0 1px 2px 1px rgba(0, 0, 0, 0.15);
	    transform-origin: 50% 0;
	    transform: scale(1, 0);
	    transition: transform .15s ease-in-out .15s;
	    max-height: 66vh;
	    overflow-y: scroll;
	    z-index: 1;
	}
	.dropdown .dropdown-option {
	    display: block;
	    padding: 8px 12px;
	    opacity: 0;
	    transition: opacity .15s ease-in-out;
	}
	.dropdown .dropdown-label {
	    display: block;
	    min-height: 30px;
	    background: #fff;
	    border: 1px solid #ccc;
	    padding: 10px 12px 0;
	    line-height: 1;
	    cursor: pointer;
	}
	.dropdown .dropdown-label:before {
	    content: '▼';
	    float: right;
	}
	.dropdown.on .dropdown-list {
	    transform: scale(1, 1);
	    transition-delay: 0s;
	}
	.dropdown.on .dropdown-list .dropdown-option {
	    opacity: 1;
	    transition-delay: .2s;
	}
	.dropdown.on .dropdown-label:before {
	    content: '▲';
	}
	.dropdown [type="checkbox"] {
	    position: relative;
	    top: -1px;
	    margin-right: 4px;
	}
	.dropdown .dropdown-select{
		border: 1px solid #ccc;
	    padding: 5px;
	    background: #f1f1f1;
	    margin: 0 10px 10px 0;
	    display: inline-block;
	    border-radius: 4px;
	}
	.dropdown .dropdown-select::after{
		content: 'X';
		padding-left: 10px;
		font-size: 12px;
		pointer-events:auto;

	}
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('index')}">优惠时间段列表</a>|
					<a href="javascript:void(0);"  onclick="window.top.artiframe('{pigcms{:U('add')}','添加优惠时间段',800,560,true,false,false,editbtn,'edit',true);">添加优惠时间段</a>
					<a href="{pigcms{:U('area_set')}" class="on">配置设置</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('area_set')}" method="get">
							<input type="hidden" name="c" value="Deliver_time_discount"/>
							<input type="hidden" name="a" value="area_set"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="area_name" <if condition="$_GET['searchtype'] eq 'area_name'">selected="selected"</if>>城市名称</option>
							</select>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th style="width: 20%;">城市ID</th>
								<th style="width: 30%;">城市名称</th>
								<th>是否开启优惠时间段</th>
                                <th>设置优惠时间段</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($city_list)">
								<volist name="city_list" id="vo">
									<tr>
										<td>{pigcms{$vo.area_id}</td>
										<td>{pigcms{$vo.area_name}</td>
										<td>
											 <span class="cb-enable">
											 	<label class="cb-enable <if condition="$vo['open_deliver_time_discount'] eq 1">selected</if>">
											 		<span>开启</span>
											 		<input type="radio" data-city_id="{pigcms{$vo.area_id}" name="open_deliver_time_discount" value="1"  <if condition="$vo['open_deliver_time_discount'] eq 1">checked="checked"</if>/>
											 	</label>
											 </span>
                							<span class="cb-disable">
                								<label class="cb-disable <if condition="$vo['open_deliver_time_discount'] eq 0">selected</if>">
                									<span>关闭</span>
                									<input type="radio" data-city_id="{pigcms{$vo.area_id}" name="open_deliver_time_discount" value="0"  <if condition="$vo['open_deliver_time_discount'] eq 0">checked="checked"</if>/>
                								</label>
                							</span>
										</td>
                                        <td>
											<div class="dropdown" data-control="checkbox-dropdown">
											    <label class="dropdown-label">请选择优惠时间段</label>

											    <div class="dropdown-list" data-city_id="{pigcms{$vo.area_id}">
											    	<volist name="discount_list" id="discount">
											    		<label class="dropdown-option" >
												            <input type="checkbox" name="dropdown-group" value="{pigcms{$discount.discount_id}"  <if condition="in_array($discount['discount_id'],$vo['discount_ids'])">checked</if>/> 
												            {pigcms{$discount.title}
												        </label>
											    	</volist>
											    </div>
											</div>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="6">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="6">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script>
			$('input[name=open_deliver_time_discount]').click(function(){
				$.post("{pigcms{:U('ajax_change_open')}", {'status':$(this).val(),'area_id':$(this).data('city_id')}, function(response){
					layer.msg(response.info,{
						offset:($(parent.window).height() + $(parent.window).scrollTop())/2
					});
				});
			});



			(function($) {
			    var CheckboxDropdown = function(el) {
			        var _this = this;
			        this.isOpen = false;
			        this.areAllChecked = false;
			        this.$el = $(el);
			        this.$label = this.$el.find('.dropdown-label');
			        this.$inputs = this.$el.find('[type="checkbox"]');

			        this.onCheckBox();

			        this.$label.on('click', function(e) {
			            e.preventDefault();
			            _this.toggleOpen();
			        });

			     	this.$label.on('click','.dropdown-select', function(e){
			    		e.stopImmediatePropagation();
			            e.stopPropagation();
			            e.preventDefault()
				        var id = $(this).data('id');


			        	// var checked = this.$el.find(':checked');

			        	// console.log(checked,'checked')

			        	_this.updateStatus(id);
				    	// alert(id)
				    })

			        this.$inputs.on('change', function(e) {
			            _this.onCheckBox();
			        });
			    };

			    CheckboxDropdown.prototype.onCheckBox = function() {
			        this.updateStatus();
			    };

			    CheckboxDropdown.prototype.updateStatus = function( id = 0) {
			        var checked = this.$el.find(':checked');
			        var discount_id_arr = [];
			        var city_id = this.$el.find('.dropdown-list').data('city_id');
			        var show_html = '';
			        checked.each((index, item)=>{
			        	if(id > 0 && id == $(item).val()){
			        		$(item).attr('checked', false)
			        	}else{
				        	discount_id_arr.push($(item).val())
				        	show_html += '<span class = "dropdown-select" data-id="'+$(item).val()+'">' + $(item).parent('label').text() + '</span>'
			        	}
			        })

		        	if(city_id>0){
			        	$.post("{pigcms{:U('ajax_select_discount')}", {'discount_id_arr':discount_id_arr,'city_id':city_id}, function(response){
							
						});
		        	}

			        if (checked.length <= 0) {
			            this.$label.html('请选择优惠时间段');
			        } else if (checked.length === 1) {
			            this.$label.html(show_html);
			        } else {
			            this.$label.html(show_html);
			        }
			    };

			    CheckboxDropdown.prototype.toggleOpen = function(forceOpen) {
			        var _this = this;

			        if (!this.isOpen || forceOpen) {
			            this.isOpen = true;
			            this.$el.addClass('on');
			            $(document).on('click', function(e) {
			                if (!$(e.target).closest('[data-control]').length) {
			                    _this.toggleOpen();
			                }
			            });
			        } else {
			            this.isOpen = false;
			            this.$el.removeClass('on');
			            $(document).off('click');
			        }
			    };

			    var checkboxesDropdowns = document.querySelectorAll('[data-control="checkbox-dropdown"]');
			    for (var i = 0, length = checkboxesDropdowns.length; i < length; i++) {
			        new CheckboxDropdown(checkboxesDropdowns[i]);
			    }
			})(jQuery);
		</script>
<include file="Public:footer"/>