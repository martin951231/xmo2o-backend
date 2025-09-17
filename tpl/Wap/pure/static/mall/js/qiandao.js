/**
 * Created by win7 on 2018-10-27.
 */
/**
 * Created by Administrator on 2017/6/21.
 */
require(['common','validator','droprefresh'], function(APP){
    var loading = false;
    var classId='';
    var AppView = {
        init: function() {
            this.initPicker();
            this.initRefresh(false,false);
            this.reNew();
            APP.initCheck();
            this.checkAll()
        },
        settings: {
            pageNo: 0,
            pageSize: 1000,
            totalPages: 0,
            name:''
        },
        isEmpty: function(list){
            var b = AppView.settings.pageNo == 1 && !list.length;
            /* if(b){
             this.resetInfinite(false);
             }*/
            return b;
        },
        isNomore: function(list){
            var b = (this.settings.pageNo > this.settings.totalPages) || (list.length < this.settings.pageSize);

            /* if(b){
             this.resetInfinite(false);
             }*/
            return b;
        },
        initPicker:function(){
            var self = this;
            var $searchBar = $('#searchBar'),
                $searchText = $('#searchText'),
                $searchInput = $('#searchInput'),
                $searchClear = $('#searchClear'),
                $searchCancel = $('#searchCancel');
            function hideSearchResult(){
                $searchInput.val('');
            };

            function cancelSearch(){
                hideSearchResult();
                $searchBar.removeClass('weui-search-bar_focusing');
                $searchText.show();
            };

            $searchText.on('click', function(){
                $searchBar.addClass('weui-search-bar_focusing');
                $searchInput.focus();
            });


            $searchClear.on('click', function(){
                hideSearchResult();
                $searchInput.focus();
            });

            $searchCancel.on('click', function(){
                self.settings.name = $searchInput.val();
                $("#checkAll").attr("checked",false);
                cancelSearch();
                $searchInput.blur();
                self.initRefresh(true,true,function(list){
                    if(list && list.length){
                        $('#allCheck').removeClass('app-hide');
                    }else{
                        $('#allCheck').addClass('app-hide');
                    }
                });
            });
        },
        initRefresh: function(types,allPlase,callback) {
            var self = this;
            var $body = $('body');
            if(types){
                var data={
                    openId: openId,
                    type: $(".weui_navbar_item.active").attr("data-type"),
                    //pageNo: self.settings.pageNo,
                    //pageSize: self.settings.pageSize,
                    templateId: 'listTemplate',
                    url:'/service_sign/qd_stu_clas_list',
                    name:self.settings.name,
                    classId:classId
                };
            }else{
                var data={
                    openId: openId,
                    type: $(".weui_navbar_item.active").attr("data-type"),
                    //pageNo: self.settings.pageNo,
                    //pageSize: self.settings.pageSize,
                    url:'/service_sign/classList',
                    templateId: 'listTemplates'
                };
            }
            //self.lists(data,types,allPlase);
            self.initList(data,allPlase,callback);
        },
        initList:function(data,allPlase,callback){
            var self = this;
            var isNoData = function(){
                APP.render({
                    templateId:'noMore',
                    element: '#listBox',
                    replaceAll: false,
                    data:  []
                });
                if($("#openWait").css("display")==='block'){
                    $("#openWait").hide();
                };
            };
            APP.request({
                url: data.url,
                type: 'GET',
                data: data,
                loadingText: '加载中...',
                success: function(result){
                    result.list = result.list ? result.list : [];
                    callback && callback(result.list);
                    if(result.list && result.list.length>0){
                        // 插入数据到页面，放到最后面
                        setTimeout(function(){
                            APP.render({
                                templateId: data.templateId,
                                element: '#listBox',
                                replaceAll: true,
                                data: result.list || []
                            });
                            // 每次数据插入，必须重置
                            if($("#openWait").css("display")==='block'){
                                $("#openWait").hide();
                            };
                        },300);
                    }else{
                        APP.render({
                            templateId: data.templateId,
                            element: '#listBox',
                            replaceAll: true,
                            data: []
                        });
                        isNoData();
                    }
                },
                error: function(xhr, type){
                    isNoData();
                }
            });
        },
        lists:function(data,types,allPlase){
            var $body = $('body');
            var self = this;
            $('#listBox').dropload({
                scrollArea : window,
                loadDownFn : function(me){
                    data.pageNo++;
                    self.settings.pageNo++;
                    APP.request({
                        url: data.url,
                        type: 'GET',
                        data: data,
                        loadingText: '加载中...',
                        success: function(result){
                            result.list = result.list ? result.list : [];
                            if(result.list.length>0){
                                // 插入数据到页面，放到最后面
                                self.settings.totalPages = result.pageCount;
                                setTimeout(function(){
                                    APP.render({
                                        templateId: data.templateId,
                                        element: '#listBox',
                                        replaceAll: allPlase,
                                        data: result.list || []
                                    });
                                    // 每次数据插入，必须重置
                                    if($("#openWait").css("display")==='block'){
                                        $("#openWait").hide();
                                    };

                                    me.resetload(result.list.length,self.settings.pageSize);
                                },300);
                            }else{
                                if(self.settings.pageNo==1){
                                    APP.render({
                                        templateId:'noMore',
                                        element: '#listBox',
                                        replaceAll: false,
                                        data:  []
                                    });
                                }
                                if($("#openWait").css("display")==='block'){
                                    $("#openWait").hide();
                                };
                                // 锁定
                                me.lock();
                                // 无数据
                                me.noData(true,self.settings.pageNo);
                            }
                        },
                        error: function(xhr, type){
                            // 即使加载出错，也得重置
                            console.log("加载失败"+self.settings.pageNo);
                            if($("#openWait").css("display")==='block'){
                                $("#openWait").hide();
                            };
                            me.resetload();
                        }
                    });
                }
            });


        },
        checkAll:function(){
            var self=this;
            var values = "";
            var value='';
            $(document).on('click','#checkAll', function(event) {
                if ($(this).prop("checked")) {
                    $(document).find(".oneCheck").prop("checked",true);
                } else {
                    $(document).find(".oneCheck").prop("checked",false);
                }
            });
            $(document).on("click",'.appCode',function(){
                value=$(this).attr('data-id');
                $(this).closest(".weui-cell").addClass("weui-cell-checked");
                qian(value)
            });
            $(document).on("click",'#btnSubmit',function(event){
                values = APP.getChecked();
                if(!values) return $.toptip('请选择学员', 1000, 'warning');
                qian(values)
            });
            var qian=function(val){
                APP.render({
                    templateId: 'listTemplates1',
                    element: '#modal',
                    replaceAll: true,
                    data:val
                });
                setTimeout(function(){
                    $(".yui-auth-form").removeClass("auth-animated");
                    self.submit()
                },1000);

            }
        },
        reNew:function(){
            var self=this;

            $(".weui_navbar_item").off("click").on("click",function(){
                $("#openWait").show();
                $(".weui_navbar_item").removeClass("active");
                $(this).addClass("active");
                $('#searchBar').addClass('app-hide').removeClass('weui-search-bar_focusing');
                self.settings.name = '';
                $('#searchInput').val('');
                $("#checkAll").attr("checked",false);
                $("#listBox").html("");

                $("#allCheck").addClass("app-hide");
                /*self.settings.pageNo = 0;
                 self.settings.totalPages = 0;*/``
                self.initRefresh(false,false)

            });

            $(document).on("click",'.qianHodaly',function(e){
                e.stopPropagation();
                $("#openWait").show();
                $('#searchBar').removeClass('app-hide');
                if(self.settings.pageNo>=2){
                    $("#noMore").hide().remove();
                }
                self.settings.pageNo =0;
                self.settings.totalPages = 0;
                $("#listBox").html("");
                classId=$(this).attr("data-id");
                self.class_type=$(this).attr('data-classType');
                self.initRefresh(true,false);
                $("#allCheck").removeClass("app-hide");
            });
        },
        submit:function(){
            var self=this;
            var time1 = null;
            var time2 = null;
            var $self=$('.weui_navbar_item.active');
            $("#weui-prompt-textarea").off().on("click",function(){
                $(".hodayle").prop("ckecked",true)
            });
            $(".canser").off().on("click",function(){
                $(".weui-dialogs:visible").addClass("close-animated");
                //$("#modiles").hide().remove()
                setTimeout(function(){
                    var handle = document.getElementById("modiles");
                    if (handle) {
                        $(".weui-cell").removeClass("weui-cell-checked");
                        handle.parentNode.removeChild(handle);
                    }
                },1000);
            });
            $(".confire").off().on("click",function(e){
                e.stopPropagation();
                var data;
                var _type = $(".weui_navbar_item.active").attr("data-type");
                var lei=$(this).attr("data-type");
                if(lei==0){
                    data = Validator.getData(false, '#showOne');
                }else{
                    data = Validator.getData(false, '#showTwo');
                }

                if(data){
                    data.ids=$("#id").val();
                    data.type = _type;
                    if(_type==3){
                        data.classType = self.class_type;
                    }
                    data.repairType = 1;
                    if(data.status==2){
                        var comment=$("#weui-prompt-textarea").val();
                        if(comment==''){
                            data.comment=='null'
                        }else{
                            data.comment=comment
                        }
                    }
                    APP.request({
                        url: '/service_sign/qd_or_qj.do',
                        type: 'GET',
                        data: data,
                        loadingText: '加载中...',
                        success: function(result){
                            if(result.status){
                                var mesg='请假申请成功';
                                if(data.status==1){
                                    mesg='签到成功';
                                }else if(data.status==3){
                                    mesg='扣课时申请成功';
                                }
                                $.alert(mesg,function(){
                                    if(lei==0){
                                        $(".yui-auth-form").addClass("close-animated");
                                    }else{
                                        $(".yui-auth-layer ").addClass("close-animated");
                                    }
                                    setTimeout(function(){
                                        var handle = document.getElementById("modiles");
                                        if (handle) {
                                            handle.parentNode.removeChild(handle);
                                            //$self.click()
                                            //var len=$("input[name=check]:checked").length;
                                            $("input[name=check]:checked").closest(".weui-cell").remove();
                                            $(".weui-cell-checked").remove();
                                            $("#listBox").html("");
                                            self.settings.name = '';
                                            self.initRefresh(true,false,function(list){
                                                if(list.length){
                                                    $("#allCheck").removeClass("app-hide");
                                                }else{
                                                    $("#allCheck").addClass("app-hide");
                                                }
                                            });


                                        }
                                    },1000);
                                })
                            }else{
                                $.alert(result.msg)
                                /*if(data.big_type==0){
                                 $.alert('签到失败')
                                 }else{
                                 $.alert('请假失败')
                                 }*/

                            }


                        }
                    });
                }
            });
            $(".backRight").off().on("click",function(){
                var r=$(this).attr("data-r");
                $("#modiles").find("input[type=radio]").prop('checked',false);
                if(r==0){
                    $(".yui-auth-form").addClass("change-animated");
                }else{
                    $(".yui-auth-layer ").addClass("change-animated");
                }

                clearTimeout(time1);
                clearTimeout(time2);
                time1 = setTimeout(function(){
                    if(r==0){
                        $("#modiles").find('.yui-auth-layer').show();
                        $("#modiles").find('.yui-auth-form').hide();
                    }else{
                        $("#modiles").find('.yui-auth-form').show();
                        $("#modiles").find('.yui-auth-layer').hide();

                    }

                },500);

                time2 = setTimeout(function(){
                    if(r==0){
                        $("#modiles").find(".yui-auth-form").removeClass("change-animated");
                    }else{
                        $("#modiles").find(".yui-auth-layer").removeClass("change-animated");
                    }

                },1000);
            })
        }

    };

    window.AppView = AppView;

    AppView.init();
});