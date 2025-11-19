var groupImgBoxSlider = new Swiper("#groupImgBox", {
  pagination: ".swiper-pagination",
});
goodList_before();
//店铺更多点击
$(".storeList .more.down").click(function () {
  $(".storeList .item").removeClass("hide");
  $(this).removeClass("hide");
  $(".storeList .more.down").addClass("hide");
  $(".storeList .more.up").removeClass("hide");
});
$(".storeList .more.up").click(function () {
  $(".storeList .item:gt(1)").addClass("hide");
  $(this).removeClass("hide");
  $(".storeList .more.down").removeClass("hide");
  $(".storeList .more.up").addClass("hide");
});

//分享按钮
if ($(".buyListBox").size() == 0) {
  $(".shareBox").addClass("show-important");
}

//处理规格弹层的最高高度
if ($(".specificationsBox .specifications").size() > 0) {
  $(".specificationsBox .specifications").css(
    "max-height",
    $(window).height() * 0.6
  );
}

//页面滚动
var topFixedClick = false;
var merInfoBoxTop = $("#merInfoBox").position().top - 40;
console.log("merInfoBoxTop", merInfoBoxTop);

if ($("#introListBox").size() > 0) {
  var introListBoxTop = $("#introListBox").position().top - 40;
  console.log("introListBoxTop", introListBoxTop);
} else {
  $(".introListBoxBtn").hide();
  $("#topFixed .item").width("25%");
}

var groupDetailBoxTop = $("#groupDetailBox").position().top - 40;
console.log("groupDetailBoxTop", groupDetailBoxTop);

if ($("#otherGroupBox").size() > 0) {
  var otherGroupBoxTop = $("#otherGroupBox").position().top - 40;
} else {
  var otherGroupBoxTop =
    $("#groupDetailBox").position().top + $("#groupDetailBox").height() - 40;
}
console.log("otherGroupBoxTop", otherGroupBoxTop);

$("#page").scroll(function () {
  var scrollTop = $(this).scrollTop();
  if (scrollTop >= merInfoBoxTop) {
    var activeName = "merInfoBox";
    if (scrollTop >= otherGroupBoxTop) {
      activeName = "otherGroupBox";
    } else if (scrollTop >= groupDetailBoxTop) {
      activeName = "groupDetailBox";
    } else if (scrollTop >= introListBoxTop) {
      activeName = "introListBox";
    }
    if (!topFixedClick) {
      $("." + activeName + "Btn")
        .addClass("active")
        .siblings("div")
        .removeClass("active");
    } else {
      topFixedClick = false;
    }

    $("#topFixed,#shareBox,#gotopBox").removeClass("hide");
    $(".buyHistoryBox").css("top", "50px");
  } else {
    $("#topFixed,#shareBox,#gotopBox").addClass("hide");
    $(".buyHistoryBox").css("top", "15px");
  }
});

//多规格
if ($("#specifications").size() > 0) {
  $("#specifications").click(function () {
    $(".specificationsBg,.specificationsBox").show();
  });

  $(".specificationsBg").click(function () {
    $(".specificationsBg,.specificationsBox").hide();
  });

  $(".specifications .item").click(function () {
    $(".specificationsBox .content .tip").html("");
    $(".specifications .item").removeClass("active");
    $(this).addClass("active");
    $(".specificationsBox .content .price").html($(this).data("price"));
    $(".specificationsBox .content .old").html($(this).data("old"));
    var stock = $(this).data("stock");
    var num_desc = $(this).data("num_desc");

    if (stock == 0) {
      $(".specificationsBuy").addClass("disabled");
      $(".specificationsBox .content .tip").html(
        num_desc ? num_desc : "小伙伴们太热情了，商品已售罄。"
      );
    } else {
      $(".specificationsBuy").removeClass("disabled");
      if (stock < 10 && stock > -1) {
        $(".specificationsBox .content .tip").html(
          num_desc
            ? num_desc
            : "商品剩下 <span>" + stock + "</span> 份了，请快速购买哦。"
        );
      }
    }
  });
  $(".specificationsBuy").click(function () {
    if (!$(this).hasClass("disabled")) {
      location.href =
        $("#specifications").data("url") +
        "&specifications_id=" +
        $(".specifications .item.active").data("id") +
        "&num=" +
        $(".specifications .item.active").data("num");
    }
  });

  //模拟点击一下第一个。
  $(".specifications .item").eq(0).click();
}

//历史分享、购买、浏览
var record_list;
var nowI = 0;
$.getJSON(getShareDataUrl, function (data) {
  if (data.record_list && data.record_list.length > 0) {
    record_list = data.record_list;
    showShareData();
  }
});

function showShareData() {
  console.log(record_list.length, "record_list.length");
  console.log(nowI, "nowI");
  var temp = record_list[nowI];
  $(".buyHistoryBox .img img").attr("src", temp.avatar);
  var type_txt = "";
  if (temp.type == 1) {
    type_txt = "浏览";
  } else if (temp.type == 2) {
    type_txt = "购买";
  } else if (temp.type == 3) {
    type_txt = "分享";
  }
  $(".buyHistoryBox .nickname").html(
    temp.nickname + " " + type_txt + "了这个商品"
  );
  $(".buyHistoryBox").removeClass("hide");
  if (record_list.length > nowI + 1) {
    setTimeout(function () {
      nowI++;
      showShareData();
    }, 4000);
  } else {
    $(".buyHistoryBox").addClass("hide");
  }
}

$("#topFixed .item").click(function () {
  topFixedClick = true;
  $(this).addClass("active").siblings("div").removeClass("active");
  $("#page").scrollTop(window[$(this).data("id") + "Top"] + 2);
});
$("#gotopBox").click(function () {
  topFixedClick = false;
  $("#topFixed .groupImgBoxBtn")
    .addClass("active")
    .siblings("div")
    .removeClass("active");
  $("#page").scrollTop(0);
});

$(".footerMenu .home").click(function () {
  if (page_num > 0) {
    localStorage.setItem("goPageIndex", "1");
    if (
      typeof wx !== "undefined" &&
      window.__wxjs_environment === "miniprogram"
    ) {
      wx.miniProgram.navigateBack({ delta: page_num });
    } else {
      if (history.length > 1) {
        history.go(-page_num);
      } else if (motify.checkLifeApp() && page_num == 1) {
        if (motify.checkIos()) {
          iosCommonNew.iosFunction("closeWebView", { name: "closeWebView" });
        } else {
          window.lifepasslogin.webViewGoBack();
        }
      } else {
        location.href = page_index;
      }
    }
  } else {
    if (history.length > 1) {
      location.replace(page_index);
    } else {
      location.href = page_index;
    }
  }
});
// 安卓显示分享按钮
if (is_app_browser && !motify.checkIos()) {
  window.lifepasslogin.shareLifePass(
    window.shareData.tTitle,
    window.shareData.tContent,
    window.shareData.imgUrl,
    window.shareData.sendFriendLink
  );
}

//分享
$("#inviteBtn,#shareBox").click(function () {
  if ($(this).data("share_url")) {
    getShareUrl = $(this).data("share_url");
  }
  if (
    typeof wx !== "undefined" &&
    window.__wxjs_environment === "miniprogram"
  ) {
    var layerLoadTip = layer.open({
      type: 2,
      shade: "background-color: rgba(0,0,0,.3)",
      shadeClose: false,
    });
    $.post(getShareUrl, function (result) {
      layer.close(layerLoadTip);
      if (result.status == 1) {
        var wxappUrl =
          "/pages/index/share?shareImg=" +
          encodeURIComponent(result.info.friend_img) +
          "&sharePath=" +
          encodeURIComponent(result.info.wxapp_path) +
          "&poster=" +
          encodeURIComponent(result.info.group_img) +
          "&shareTitle=" +
          encodeURIComponent(result.info.share_title) +
          "&title=" +
          encodeURIComponent(result.info.title) +
          "&bg=" +
          encodeURIComponent(result.info.good_img);
        console.log("wxapp_share_url", wxappUrl);
        wx.miniProgram.navigateTo({
          url: wxappUrl,
          fail: function (res) {
            layer.open({
              content: "打开分享失败，请重试",
              btn: "好的",
            });
          },
        });
      } else {
        layer.open({
          content: result.info,
          btn: "好的",
        });
      }
    });
  } else if (typeof wx !== "undefined") {
    $(".detail_mask").show();
    $(".guide").show();
  } else if (is_app_browser) {
    $.post(getShareUrl, function (result) {
      layer.close(layerLoadTip);
      if (result.status == 1) {
        if (motify.checkIos()) {
          var ios_shareWeChat = {
            name: "shareWeChat",
            imgUrl: window.shareData.imgUrl,
            sendFriendLink: window.shareData.sendFriendLink,
            tTitle: window.shareData.tTitle,
            tContent: window.shareData.tContent,
          };
          ios_shareWeChat.share_wx = share_wx;
          if (ios_shareWeChat.share_wx == "wxapp") {
            ios_shareWeChat.share_wx_info = JSON.stringify({
              userName: pay_wxapp_username,
              path:
                "/pages/index/index?redirect=webview&webview_url=" +
                encodeURIComponent(location.href),
              image: result.info.friend_img,
              title: result.info.share_title,
            });
          }
          iosCommonNew.iosFunction("shareWeChat", ios_shareWeChat);
        } else {
          if (iosCommonNew.getVersion() >= 1400) {
            var android_shareWeChat = {
              imgUrl: window.shareData.imgUrl,
              sendFriendLink: window.shareData.sendFriendLink,
              tTitle: window.shareData.tTitle,
              tContent: window.shareData.tContent,
            };
            android_shareWeChat.share_wx = share_wx;
            if (android_shareWeChat.share_wx == "wxapp") {
              android_shareWeChat.share_wx_info = {
                userName: pay_wxapp_username,
                path:
                  "/pages/index/index?redirect=webview&webview_url=" +
                  encodeURIComponent(location.href),
                image: result.info.friend_img,
                title: result.info.share_title,
              };
              window.lifepasslogin.shareWeChatNew(
                JSON.stringify(android_shareWeChat)
              );
            } else {
              window.lifepasslogin.shareWeChat(
                window.shareData.tTitle,
                window.shareData.tContent,
                window.shareData.imgUrl,
                window.shareData.sendFriendLink
              );
            }
          } else {
            window.lifepasslogin.shareWeChat(
              window.shareData.tTitle,
              window.shareData.tContent,
              window.shareData.imgUrl,
              window.shareData.sendFriendLink
            );
          }
        }
      } else {
        layer.open({
          content: result.info,
          btn: "好的",
        });
      }
    });
  } else {
    // layer.open({
    // 	content: '请使用浏览器分享功能'
    // 	,btn: '好的'
    // });
    ///self.location.href="/wap.php?g=Wap&c=Groupnew&a=share&group_id=251&origin=web";

    var getShareUrl =
      "/wap.php?g=Wap&c=Groupnew&a=share&group_id=" + group_id + "&origin=web"; // 参数origin=web 来源必传
    $.get(
      getShareUrl,
      function (result) {
        console.log(result);
        $("#poster").attr("src", result.info.group_img);
        $("#webShare").removeClass("hide");
      },
      "json"
    );
  }
});
// 取消弹层
$(document).on("click", ".detail_mask", function (e) {
  $(".detail_mask").hide();
  $(".guide").hide();
});

//分享海报
$("#webShareBg,#closePoster").click(function () {
  $("#webShare").addClass("hide");
});

//拼团详情介绍
$("#pinRuleBtn").click(function () {
  $("#pinRuleBox").removeClass("hide");
});
$("#pinRuleBg,#closePinRule").click(function () {
  $("#pinRuleBox").addClass("hide");
});
//拼团倒计时
if ($(".pinList .time").size() > 0) {
  pin_time();
  setInterval(function () {
    pin_time();
  }, 1000);
}
function pin_time() {
  $(".pinList .time").each(function (i, item) {
    var time = parseInt($(item).data("time"));
    if (time <= 0) {
      $(item).closest(".item").remove();
    } else {
      var day = Math.floor(time / 86400);
      var hour = Math.floor((time % 86400) / 3600);
      var minutes = Math.floor(((time % 86400) % 3600) / 60);
      var seconds = Math.floor(((time % 86400) % 3600) % 60);
      var html = "";
      if (day > 0) {
        html += day + "天";
      }
      if (hour > 0) {
        html += (hour < 10 ? "0" : "") + hour + ":";
      }
      if (minutes > 0) {
        html += (minutes < 10 ? "0" : "") + minutes + ":";
      }
      if (html != "") {
        html += (seconds < 10 ? "0" : "") + seconds;
      } else {
        html += (seconds < 10 ? "0" : "") + seconds + "秒";
      }
      $(item)
        .data("time", time - 1)
        .html(html);
    }
  });
}

// 团购优惠组合
$("#groupCombineDialog").on("click", ".groupCombineClose", function (e) {
  $("#groupCombineModal").removeClass("show");
});

$("#groupCombineModal").click(function (event) {
  $(this).removeClass("show");
});

$("#groupCombineDialog").click(function (event) {
  event.stopPropagation();
});

$("#buyBtnOpt").click(function () {
  var combine_id = $(this).data("combine_id");
  var group_id = $(this).data("group_id");
  console.log(combine_id, "combine_id");
  console.log(group_id, "group_id");
  if (combine_id) {
    getCombine(combine_id, group_id);
  } else {
    var url = $(this).data("url");
    window.location.href = url;
  }
});
// 去看看
$("#groupCombineDialog").on("click", "#groupCombineDetail", function (e) {
  var combine_id = $(this).data("combine_id");
  var group_id = $(this).data("group_id");
  var url =
    $(this).data("url") + "?combine_id=" + combine_id + "&group_id=" + group_id;
  window.location.href = url;
  $("#groupCombineModal").removeClass("show");
});

// 直接购买
$("#groupCombineDialog").on("click", "#groupCombineBuyBtn", function (e) {
  window.location.href = $(this).data("url");
  $("#groupCombineModal").removeClass("show");
});

var lat = 0,
  lng = 0;

//检查浏览器是否支持地理位置获取
if (navigator.geolocation) {
  //若支持地理位置获取,成功调用showPosition(),失败调用showError
  var config = { enableHighAccuracy: true };
  navigator.geolocation.getCurrentPosition(showPosition, showError, config);
} else {
  alert("定位失败,用户浏览器不支持或已禁用位置获取权限");
}

/**
 * 获取地址位置成功
 */
function showPosition(position) {
  //获得经度纬度
  lat = position.coords.latitude;
  lng = position.coords.longitude;
}

/**
 * 获取地址位置失败[暂不处理]
 */
function showError(error) {
  lng = 0;
  lat = 0;
}

// 团购组合详情
function getCombine(combine_id, group_id) {
  isLoading = true;
  var go_url = location_url;
  var params = {
    combine_id: combine_id,
    group_id: group_id,
    lng: lng,
    lat: lat,
  };
  console.log(params, "params");
  $.post(go_url, params, function (result) {
    console.log(result, "result");
    if (result.data.group_list && result.data.group_list.length) {
      laytpl($("#groupCombineDialogTpl").html()).render(
        result,
        function (html) {
          $("#groupCombineDialog").html(html);
        }
      );
      $("#groupCombineModal").addClass("show");
    }
    isLoading = false;
  });
}
