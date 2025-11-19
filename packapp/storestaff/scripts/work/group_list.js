var order_id = 0;
var isSearch = false;
var hasMore = true;
var nowPage = 1;
var writeParams = {
	keywords: "",
	start_add_time: "",
	end_add_time: "",
	end_verify_time: '',
	start_verify_time: '',
};
let navType = "order";
let scrollIndexs = ''
$(document).ready(function () {
  var indexData = common.getCache("indexData", true);
  if (indexData && indexData.have_group_name) {
    $(".public .content").html(indexData.have_group_name + L_("订单"));
  }

  setOrderListHeight()
  $("#order_list ul").after(
    '<div class="jroll-infinite-tip">' + L_("正在加载中") + "...</div>"
  );
  common.scroll($("#order_list"), function (scrollIndex) {
	scrollIndexs = scrollIndex
    if (navType == "order") {
      showList(scrollIndex);
    } else {
      writeList(scrollIndex);
    }
  });

  showList();

  $("#searchForm").submit(function () {
    $("#order_list ul").empty();
    if ($("#find_value").val() == "") {
      isSearch = false;
    } else {
      isSearch = true;
    }
    hasMore = true;
    order_id = 0;
    nowPage = 1;
    showList();

    return false;
  });

  $(document).on("click", "div.consum", function () {
    var type = $(this).data("type"),
      order_id = $(this).data("order_id");
    if (type == 1) {
      common.http(
        "Storestaff&a=group_pass_array",
        { order_id: order_id, noTip: true },
        function (data) {
          laytpl($("#passList").html()).render(
            data.pass_array,
            function (html) {
              $(".consum_tc").find("ul").html(html);
              $("#allVerify").data("order_id", order_id);
            }
          );
          if (
            (data.now_order.status != 0 && data.now_order.status != 7) ||
            data.now_order.un_consume_num != data.now_order.num
          ) {
            $(".all").css("display", "none");
          }
        }
      );

      $(".consum_tc,.mask").fadeIn();
      myScroll.refresh();
    } else {
      layer.open({
        content: L_("是否验证?"),
        btn: [L_("是"), L_("否")],
        yes: function (index) {
          common.http(
            "Storestaff&a=group_verify",
            { order_id: order_id, noTip: true },
            function (data) {
              motify.log(L_("验证消费成功！"));
              setTimeout(location.reload(), 5000);
            }
          );
          layer.close(index);
        },
        no: function () {
          motify.log(L_("已取消验证"));
        },
      });
    }
  });
  $(document).on("click", ".consum_tc_n span.a39", function () {
    var group_pass = $(this).data("pass"),
      obj = $(this),
      order_id = $(this).data("order_id");
    layer.open({
      content: L_("是否验证?"),
      btn: [L_("是"), L_("否")],
      yes: function (index) {
        common.http(
          "Storestaff&a=group_array_verify",
          { order_id: order_id, group_pass: group_pass, noTip: true },
          function (data) {
            motify.log(L_("验证消费成功！"));
            obj
              .removeClass("a39")
              .addClass("ecc")
              .html(L_("已消费"))
              .unbind("click");
          }
        );
        layer.close(index);
      },
      no: function () {
        motify.log(L_("已取消验证"));
      },
    });
  });
  $(document).on("click", "#allVerify", function () {
    var order_id = $(this).data("order_id");
    layer.open({
      content: L_("是否验证?"),
      btn: [L_("是"), L_("否")],
      yes: function (index) {
        common.http(
          "Storestaff&a=group_verify",
          { order_id: order_id, noTip: true },
          function (data) {
            motify.log(L_("验证消费成功！"));
            setTimeout(location.reload(), 5000);
          }
        );
        layer.close(index);
      },
      no: function () {
        motify.log(L_("已取消全部验证"));
      },
    });
  });

  $(document).on("click", ".del, .mask", function () {
    $(".consum_tc,.mask").fadeOut();
    $(".write-filter-pop").css('transform','translateX(100%)')
  });
});

function setOrderListHeight() {
    var headerNavHeight = $('.header-nav').height() || 0
    var navHeight = $('.nav-height').height() || 0
    $("#order_list").css({ height: $(window).height() - headerNavHeight - navHeight });
}

function showList(scrollIndex) {
  if (hasMore == false) {
    return false;
  }
  if (isSearch == false) {
    common.http(
      "Storestaff&a=group_list",
      { order_id: order_id, noTip: true },
      function (data) {
        if (nowPage == 1) {
          common.scrollEnd(scrollIndex);
        }
        if (data.order_list.length > 0) {
          order_id = data.order_list[data.order_list.length - 1].order_id;
        }
        if (nowPage >= data.page) {
          hasMore = false;
          $(".jroll-infinite-tip").addClass("hideText");
        }
        laytpl($("#listTpl").html()).render(data.order_list, function (html) {
          $("#order_list ul").append(html);
          common.scrollEnd(scrollIndex);
        });
        nowPage++;
      }
    );
  } else {
    // 团购优惠组合
    if ($("#find_value").val().length == 16 && $("#find_type").val() == 1) {
      $(".jroll-infinite-tip").addClass("hideText");
      var params = {
        group_pass: $("#find_value").val(),
        order_id: "",
      };
      common.http("Storestaff&a=group_combine_find", params, function (data) {
        if (data.row_count && data.row_count == 1 && data.data) {
          laytpl($("#groupCombine").html()).render(data.data, function (html) {
            $("#groupCombineModal").html(html);
            $("#groupCombineModal").addClass("show");
          });
          return;
        }
        if (data && data.row_count == 0) {
          motify.log(L_("未查询到相关订单"));
          return;
        }
      });
    } else {
      // 团购订单
      if($('.jroll-infinite-tip')) {
        $('.jroll-infinite-tip').removeClass('hideText')
      } else {
        $("#order_list ul").after(
            '<div class="jroll-infinite-tip">' + L_("正在加载中") + "...</div>"
          );
      }
      common.http(
        "Storestaff&a=group_find",
        {
          order_id: order_id,
          find_type: $("#find_type").val(),
          find_value: $("#find_value").val(),
          noTip: true,
        },
        function (data) {
          if (data.order_list.length > 0) {
            order_id = data.order_list[data.order_list.length - 1].order_id;
          }
          if (nowPage >= data.page) {
            hasMore = false;
            $(".jroll-infinite-tip").addClass("hideText");
          }
          laytpl($("#listTpl").html()).render(data.order_list, function (html) {
            $("#order_list ul").append(html);
            common.scrollEnd(scrollIndex);
          });
          nowPage++;
        }
      );
    }
  }
}

// 团购优惠组合验证
$("#groupCombineModal").on("click", "#groupCombineDialog", function (event) {
  event.stopPropagation();
});
$("#groupCombineModal").click(function (event) {
  $(this).removeClass("show");
});
$("#groupCombineModal").on("click", "#groupCombineBtn", function (e) {
  var order_id = $(this).data("id");
  common.http(
    "Storestaff&a=group_combine_verify",
    {
      order_id: order_id,
    },
    function (data) {
      console.log(data, "data-group_combine_verify");
      openWebviewUrl("group_detail.html?order_id=" + data.order_id);
      $("#find_value").val("");
      $("#groupCombineModal").removeClass("show");
    }
  );
});

//头部导航栏点击事件
$(document).on("click", ".nav .nav-item", function () {
  nowPage = 1;
  navType = this.id;
  setScollStatus()
  hasMore = true;
  $(".order_list ul").empty();
  if (this.id == "write") {
    $(".cable_n").hide();
    $(".write-filter").show();
    $("#order_list").addClass("order_list_active");
    writeList();
  } else {
    $(".cable_n").show();
    $(".write-filter").hide();
    $("#order_list").removeClass("order_list_active");
    order_id = 0;
    showList();
  }
  $(this).addClass("nav-item-active").siblings().removeClass("nav-item-active");
});

$(document).on("click", ".filter", function () {
  $(".mask").show();
  $(".write-filter-pop").show();
  $(".write-filter-pop").css('transform','translateX(0)')
});

//点击选择时间
var currYear = new Date().getFullYear();
var opt = {
  dateYMD: {
    preset: "date",
    dateFormat: "yyyy-mm-dd",
    theme: "android-ics light", //皮肤样式
    display: "bottom", //显示方式
    mode: "scroller", //日期选择模式
    showNow: true,
    nowText: "今天",
    onSelect: function (valueText, inst) {
      $(this).html(valueText);
      $(this).attr('data-time',valueText)
      $(this).css('color','#333')
    },
  },
  select: {
    preset: "select",
  },
};
$("#start_add_time").scroller($.extend(opt["dateYMD"], opt["default"]));
$("#end_add_time").scroller($.extend(opt["dateYMD"], opt["default"]));
$("#start_verify_time").scroller($.extend(opt["dateYMD"], opt["default"]));
$("#end_verify_time").scroller($.extend(opt["dateYMD"], opt["default"]));
//重置
$(document).on("click", ".rest", function () {
  writeParams.start_add_time = '';
	writeParams.end_add_time = '';
	writeParams.start_verify_time = '';
	writeParams.end_verify_time = '';

	$('.write-filter-pop-btn .item').attr('data-time','')
  $('.write-filter-pop-btn .start').html('起始时间')
	$('.write-filter-pop-btn .end').html('终止时间')
  $('.write-filter-pop-btn .item').css('color','#808080')

  $("#start_add_time").scroller('setDate', new Date(), true);
  $("#end_add_time").scroller('setDate', new Date(), true);
  $("#start_verify_time").scroller('setDate', new Date(), true);
  $("#end_verify_time").scroller('setDate', new Date(), true);
  });
  //完成
  $(document).on("click", ".complete", function () {
	$(".mask").fadeOut();
  $(".write-filter-pop").css('transform','translateX(100%)')
  setScollStatus()
  hasMore = true;
  nowPage = 1;
  $(".order_list ul").empty();
  writeList();
  });
$("#searchForms").submit(function () {
    $("#order_list ul").empty();
    hasMore = true;
    nowPage = 1;
    writeList();
    return false;
});

function writeList(scrollIndex) {
  if (hasMore == false) {
    return false;
  }
  writeParams.keywords = $("#searchForms input").val();
	writeParams.start_add_time = $("#start_add_time").attr('data-time');
	writeParams.end_add_time = $("#end_add_time").attr('data-time');
	writeParams.start_verify_time = $("#start_verify_time").attr('data-time');
	writeParams.end_verify_time = $("#end_verify_time").attr('data-time');
  common.httpV20(
    "/group/storestaff.order/groupCouponList",
    {
      page: nowPage,
      pageSize: 10,
      keywords: writeParams.keywords,
      start_add_time: writeParams.start_add_time,
      end_add_time: writeParams.end_add_time,
      start_verify_time: writeParams.start_verify_time,
      end_verify_time: writeParams.end_verify_time,
    },
    function (data) {
      if (nowPage >= data.last_page) {
        hasMore = false;
        $(".jroll-infinite-tip").addClass("hideText");
      }
      laytpl($("#writeList").html()).render(data.data, function (html) {
        $("#order_list ul").append(html);
        common.scrollEnd(scrollIndex);
      });
      if (data.data.length > 0) {
        nowPage++;
      }
    }
  );
}
function setScollStatus(){
	if (!hasMore) {
		common.scrollEnd(scrollIndexs);
	  }
}