<include file="Public:header"/>


<div class="main-content">
	<style >

		/*flex兼容*/
		.flex{
		    display:-webkit-box;
		    display:-moz-box;
		    display:-webkit-flex;
		    display:-ms-flexbox;
		    display:flex;
		}

		.flex-x-between{
		    -webkit-box-pack: justify;
		    -moz-box-pack: justify;
		    -ms-flex-pack: justify;
		    -webkit-justify-content: space-between;
		    justify-content: space-between;
		}

		.flex-x-center{
		    -webkit-box-pack: center;
		    -moz-box-pack: center;
		    -ms-flex-pack: center;
		    -webkit-justify-content: center;
		    justify-content: center;
		}

		.flex-x-around{
		    -ms-flex-pack: distribute;
		    -webkit-justify-content: space-around;
		    justify-content: space-around;
		}

		.flex-y-center{
		    -webkit-box-align: center;
		    -moz-box-align: center;
		    -ms-flex-align: center;
		    -webkit-align-items: center;
		    align-items: center;
		}

		.flex-wrap{
		    -webkit-flex-wrap: wrap;
		    -ms-flex-wrap: wrap;
		    flex-wrap: wrap;
		}

		.flex-grow1{
		    -webkit-box-flex: 1;
		    -moz-box-flex: 1;
		    -webkit-flex-grow: 1;
		    -ms-flex: 1;
		    flex-grow: 1;
		}
		.flex1{
		    -webkit-box-flex: 1;
		    -moz-box-flex: 1;
		    -webkit-flex-grow: 1;
		    -ms-flex: 1;
		    flex-grow: 1;
		    -webkit-flex-basis: 0%;
		    flex-basis: 0%;
		    -webkit-flex-shrink: 0;
		    -moz-flex-shrink: 0;
		    -ms-flex: 0;
		    flex-shrink: 0;
		}

		.flex-shink0{
		    -webkit-flex-shrink: 0;
		    -moz-flex-shrink: 0;
		    -ms-flex: 0;
		    flex-shrink: 0;
		}
		#new-third-sidebar+.main-content{
		    background: #fff;
		}
		.main-container:before {
		    background: #fff;
		}
		.application-list {
			margin-top: 30px;
		    margin-bottom: 24px;
		    animation: enterIn .2s ease-out;
		    animation-fill-mode: forwards;
		}
		.application-item {
		    background: #f8f8f8;
		    margin-left: 16px;
		    margin-bottom: 16px;
		    min-width: 245px;
		    float: left;
		    border-radius: 2px;    
		    padding: 16px;
		}
		.application-item .application-entry {
		    width: 100%;
		    box-sizing: border-box;
		    zoom: 1;
		    display: block;
		}
		.application-item .application-entry .application-logo {
		    float: left;
		    width: 80px;
		    height: 80px;
		    border-radius: 4px;
		}
		.application-item .application-entry .application-logo>img {
		    width: 80px;
		    height: 80px;
		    vertical-align: bottom;
		}
		.application-item .application-entry .application-name {
		    display: -ms-flexbox;
		    display: flex;
		    -ms-flex-align: center;
		    align-items: center;
		    font-size: 14px;
		    height: 20px;
		    line-height: 20px;
		    vertical-align: bottom;
		    color: #323233;
		    font-weight: 700;
		    margin-left: 100px;
		    margin-top: 5px;
		}
		.application-item .application-entry .application-desc {
		    font-size: 12px;
		    height: 56px;
		    line-height: 18px;
		    vertical-align: top;
		    color: #969799;
		    text-overflow: ellipsis;
		    white-space: nowrap;
		    overflow: hidden;
		    margin-left: 100px;
		    margin-top: 10px;
		}
	</style>
	<div class="">
		<div class="application-list">
			<div class="flex flex-wrap">
				<volist name="village_list" id="vo">
					<li class="application-item">
						<a href="{pigcms{:U('login',array('village_id'=>$vo['village_id']))}" target="_blank" class="application-entry">
							<div class="application-logo">
								<img src="<if condition='$vo["village_logo"]'>{pigcms{:file_domain().$vo["village_logo"]}<else/>{pigcms{$site_url}/static/images/wap_house/village_logo.png</if>"/>
							</div>
							<div class="application-name">
								<span class="application-name-content">{pigcms{$vo.village_name}</span>
							</div>
							<div class="application-desc">
								<p>地址：{pigcms{$vo.village_address} </p>
								<p>物业电话：{pigcms{$vo.property_phone}</p>
							</div>
						</a>
					</li>
				</volist>
			</div>
		</div>
	</div>
</div>



<include file="Public:footer"/>