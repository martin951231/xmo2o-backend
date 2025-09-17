<include file="Publics:header"/>
<link rel="stylesheet" href="https://cdn.staticfile.org/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>

                                    <th width="10%">姓名</th>
                                    <th width="10%">手机号</th>
                                    <th width="10%">详细地址</th>
                                    <th width="10%">状态</th>
                                    <th width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.address}</div></td>
                                            <td>
                                                <if condition="$vo['status'] eq 0">
                                                    <div class="tagDiv" style="color: red;">禁止</div>
                                                    <elseif condition="$vo['status'] eq 2"/>
                                                    <div class="tagDiv">审核中</div>
                                                    <else/>
                                                    <div class="tagDiv">正常</div>
                                                </if>
                                            </td>
                                            <td class="textcenter">
                                                <a style="color: #1686ef;" href="javascript:void(0);" onclick="into_url(this)" data-url="{pigcms{:U('NewStreet/owner_index',array('community_idss'=>$_GET['community_idss'],'floor_idss' => $vo['floor_id'],'layer_ids' => $vo['layer_id'],'pigcms_id' => $vo['pigcms_id'],'iframe'=> $_GET['iframe']))}">进入</a>
                                            </td>
                                        </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="3" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                           
                        </table>
                        <if condition="$list">
                        <div style="float: right; padding-left: 5px; ">
                            <input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">
                            <button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>
                            <script>
                                function checkPage(){
                                    var page = $("#page").val();
                                    if(page == ''){
                                        return false;
                                    }
                                    location.href = "{pigcms{:U('floor_index',$_GET)}"+"&page="+page;
                                }
                            </script>
                        </div>
                        {pigcms{$pagebar}
                            </if>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    function del() {
        layer.open({
            content: '需要删除用户信息请联系对应小区进行删除！'
            ,btn: ['确定']
            ,yes: function(index){
                layer.closeAll();
            }
        });
    }
    function into_url(that) {
        var url = $(that).data('url');
        console.log('url',url);
        parent.location.href = url;
    }
</script>



<include file="Publics:footer"/>
