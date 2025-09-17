<include file="Public:header"/>
<!---->
<link href="/tpl/Merchant/default/static/diypage/css/customField.css" type="text/css" rel="stylesheet"/>
<style type="text/css">
    .btn{
        height:auto
    }
</style>
<div class="modal-body">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('SendCoupon/couponLists',array('type'=>'mer','select_plat'=>$_GET['select_plat'],'select_mer'=>$_GET['select_mer']))}" <php>if($_GET['type'] == 'mer'){echo "class='on'";}</php>>{pigcms{:L_('商家券')}</a>
            <a href="{pigcms{:U('SendCoupon/couponLists',array('type'=>'plat','select_plat'=>$_GET['select_plat'],'select_mer'=>$_GET['select_mer'],'hide'=>0))}" <php>if($_GET['type'] == 'plat'){echo "class='on'";}</php>>{pigcms{:L_('平台券')}</a>
        </ul>
    </div>
    <div class="tab-content">
        <div id="js-module-feature" class="tab-pane module-feature active">
            <table class="table">
                <colgroup>
                    <col class="modal-col-title">
                    <col class="modal-col-time">
                    <col class="modal-col-action">
                </colgroup>
                <!-- {pigcms{:L_("表格头部")} -->
                <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>满减条件</th>
                    <th>有效时间</th>
                    <th>使用平台</th>
                    <th>使用类别</th>
                    <th class="opts" style="background-color:#f5f5f5;">
                        <div class="td-cont" style="padding:7px 0 3px 10px;">
                            <form class="form-search" method="get" name="formsearch">
                                <div class="input-append">
                                    <input type="hidden" name="c" value="SendCoupon" />
                                    <input type="hidden" name="a" value="couponLists" />
                                    <input type="hidden" name="type" value="{pigcms{$type}" />
                                    <input type="hidden" name="page" value="{pigcms{$page}" />
                                    <input type="hidden" name="select_mer" value="{pigcms{$Think.get.select_mer}" />
                                    <input type="hidden" name="select_plat" value="{pigcms{$Think.get.select_plat}" />
                                    <input type="hidden" name="is_hide" value="{pigcms{$_GET['is_hide']}" />
                                    <input class="input-small js-modal-search-input" name="keyword" value="{pigcms{$keyword}" type="text" style="border-radius:4px 0px 0px 4px;"/><a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" onclick="formsearch.submit();" style="border-radius:0 4px 4px 0;margin-left:0px;">搜</a>
                                </div>
                            </form>
                        </div>
                    </th>
                </tr>
                </thead>
                <!-- {pigcms{:L_("表格数据区")} -->
                <tbody>
                <volist name="couponList" id="vo">
                    <tr class="user" data-uid="{pigcms{$vo.coupon_id}">
                        <td>{pigcms{$vo.coupon_id}</td>
                        <td>{pigcms{$vo.name}</td>
                        <td>{pigcms{$vo.discount_txt}</td>
                        <td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
                        <td>{pigcms{$vo.platform_txt}</td>
                        <td>{pigcms{$vo.cate_name_txt}</td>
                        <td class="opts">
                            <div class="td-cont">
                                <eq name='vo.select' value='1'>
                                    <button class="btn js-choose2" disabled="true">已选择</button>
                                    <else/>
                                    <button class="btn js-choose2 selectThis" data-json='{pigcms{$vo.data_json}' data-coupon_id='{pigcms{$vo.coupon_id}' data-type='{pigcms{$type}'>选取</button>
                                </eq>
                            </div>
                        </td>
                    </tr>
                </volist>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer">
    <div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$pagebar}</div>
</div>
</body>
<script type="text/javascript">
    var type = '{pigcms{$Think.get.type|default=mer}';
    var check_data = [];

    function in_array(search,array){
        for(var i in array){
            if(array[i]==search){
                return true;
            }
        }
        return false;
    }
    $(function(){
        $('.js-page-list a').live('click',function(e){
            if(!$(this).hasClass('active')){
                $('[name="page"]').val($(this).data('page-num'));
                $(".js-modal-search").trigger('click');
            }
        });

        $(".selectThis").bind("click", function(){
            var json = $.parseJSON($(this).attr('data-json'));
            check_data.push(json);
            localStorage.setItem('check_data', JSON.stringify(check_data));

            $(this).removeClass('selectThis').attr("disabled",true).text('已选择');
        })
    })
</script>
</html>
