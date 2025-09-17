<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .pager li.next>a, .pager li.prev>a{
        padding: 7px 14px 8px;
    }
    .js-confirm-choose {
        text-align: -webkit-center;
    }
    .js-confirm-choose .btn-primary {
        background-color: #428bca!important;
        color: #ffffff;
        padding: 5px 25px !important;
        border-radius: 5px;
    }
</style>
<div class="page-content" style="background-color: white;">
    <div class="row">
        <div class="col-xs-12">
            <div id="shopList" class="grid-view">
                <table class="table table-striped table-bordered table-hover" style="position:relative;">
                    <thead>
                    <tr>

                        <th width="5%">名称</th>
                        <th width="5%">手机号</th>
                        <th width="8%">入住时间</th>
                        <th width="8%">解绑时间</th>

                    </tr>
                    </thead>
                    <tbody class="class-checkbox">
                    <if condition="$dataList">
                        <volist name="dataList" id="vo">
                            <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.check_in_time|date='Y-m-d H:i:s',###}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>


                             </tr>
                        </volist>
                    <else/>
                        <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                    </if>
                    </tbody>

                </table>
                    {pigcms{$pagebar}
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

</script>
<include file="Publics:footer"/>