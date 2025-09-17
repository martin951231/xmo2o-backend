var myScroll = null,
	isLoadSelect = false,
	hasMore = true,
	nowPage = 1,
	tabsType = "sportsTicket",
	params = {
		sportsTicket: {
			nowPage: 1,
			hasMore: true,
			api: "Storestaff&a=toolsVerifyList",
			ft: [
				{
					value: "oid",
					label: "订单号",
				},
				{
					value: "xm",
					label: "下单人姓名",
				},
				{
					value: "dh",
					label: "手机号",
				},
			],
			list: [],
			count: 0,
		},
		timeCard: {
			nowPage: 1,
			hasMore: true,
			api: "Storestaff&a=cardVerifyList",
			ft: [
				{
					value: "oid",
					label: "订单号",
				},
				{
					value: "ck",
					label: "次卡名称",
				},
				{
					value: "nk",
					label: "用户昵称",
				},
				{
					value: "ph",
					label: "用户手机号",
				},
			],
			list: [],
			count: 0,
		},
	};

$(document).ready(function () {
	// tab
	$(".tabs-item").each(function (i) {
		var type = $(this).data("type");
		if (tabsType == type) {
			$(this).addClass("active");
			setFtOptions();
		}
	});
	$(".entry").hide();

	// tab切换点击
	$(document).on("click", ".tabs-item", function () {
		if ($(this).data("type") != tabsType) {
			tabsType = $(this).data("type");
			setFtOptions();
			$(".tabs-item").removeClass("active");
			$(this).addClass("active");
			if (
				params[tabsType]["nowPage"] == 1 &&
				!params[tabsType]["list"].length
			) {
				setListEmpty();
				showList();
			} else {
				listShow();
			}
		}
	});

	// 设置select options
	function setFtOptions() {
		let options = params[tabsType]["ft"];
		var html = "";
		$.each(options, function (i, v) {
			//这里的函数参数是键值对的形式，i代表键名，v代表值
			html += `<option value="${v.value}" class="L_">${v.label}</option>`;
		});
		$("#ft").html(html);
		$("#ft").val(options[0]["value"]);
	}

	// 清空列表
	function setListEmpty() {
		$(".entry").each(function (i) {
			var type = $(this).data("type");
			if (tabsType == type) {
				$(this).find("ul").empty();
			}
		});
	}

	// 列表显隐
	function listShow() {
		$(".entry").hide();
		$(".entry").each(function (i) {
			var type = $(this).data("type");
			if (tabsType == type) {
				$(this).show();
			}
		});
	}

	$(".mask").height($(window).height());

	var datas = new Date();
	var year = datas.getFullYear();
	var month =
		datas.getMonth() + 1 >= 10
			? datas.getMonth() + 1
			: "0" + (datas.getMonth() + 1);
	var day = datas.getDate() >= 10 ? datas.getDate() : "0" + datas.getDate();
	$(".datas").val(year + "-" + month + "-" + day);

	var currYear = new Date().getFullYear();
	var opt = {
		dateYMD: {
			preset: "date",
			dateFormat: "yy-mm-dd",
			theme: "android-ics light", //皮肤样式
			display: "bottom", //显示方式
			mode: "scroller", //日期选择模式
			showNow: true,
			nowText: L_("今天"),
			onSelect: function (valueText, inst) {
				setListEmpty();
				params[tabsType]["hasMore"] = true;
				params[tabsType]["nowPage"] = 1;
				showList();
			},
		},
		select: {
			preset: "select",
		},
	};
	$("#stime").scroller($.extend(opt["dateYMD"], opt["default"]));
	$("#etime").scroller($.extend(opt["dateYMD"], opt["default"]));

	setListEmpty();

	showList();
	$("body,html").animate({ scrollTop: 0 }, 300);
	$("#searchForm").submit(function () {
		setListEmpty();
		params[tabsType]["hasMore"] = true;
		params[tabsType]["nowPage"] = 1;
		showList();
		return false;
	});

	$(".entry").css({ height: $(window).height() - 172 });

	common.scroll($(".entry-sports"), function (scrollIndex) {
		if (params[tabsType]["hasMore"]) {
			params[tabsType]["nowPage"]++;
		}
		showList(scrollIndex);
	});
	common.scroll($(".entry-timeCard"), function (scrollIndex) {
		if (params[tabsType]["hasMore"]) {
			params[tabsType]["nowPage"]++;
		}
		showList(scrollIndex);
	});

	$(document).on(
		"change",
		"select[name=st], select[name=pay_type], select[name=order_from]",
		function (e) {
			setListEmpty();
			params[tabsType]["hasMore"] = true;
			params[tabsType]["nowPage"] = 1;
			showList();
			return false;
		}
	);
	function showList(scrollIndex) {
		$(".jroll-infinite-tip").hide();
		listShow();

		if (params[tabsType]["hasMore"] == false) {
			return false;
		}
		$(".loading").show();
		var ft = $("select[name=ft]").val(),
			fv = $("#find_value").val() || "";
		var stime = $("#stime").val(),
			etime = $("#etime").val();

		common.http(
			params[tabsType]["api"],
			{
				page: params[tabsType]["nowPage"],
				stime: stime,
				etime: etime,
				noTip: true,
				page_size: 10,
				fv: fv,
				ft: ft,
				type: 'sports'
			},
			function (data) {
				if (!isLoadSelect) {
					isLoadSelect = true;
					if ($(".mask").height() == 0 || $(".entry").height() <= 0) {
						$(".mask").height($(window).height());
						$(".entry").css({
							height: $(window).height() - 172,
						});
					}
				}
				var nowPage = params[tabsType]["nowPage"];
				if (nowPage > data.page) {
					if (data.page) {
						params[tabsType]["nowPage"] = nowPage;
					}
					params[tabsType]["hasMore"] = false;
					$(".loading").hide();
				}
				let list =
					tabsType == "sportsTicket"
						? data && data.order_list
							? data.order_list
							: []
						: data && data.data
						? data.data
						: [];
				params[tabsType]["list"] =
					params[tabsType]["list"].concat(list);
				params[tabsType]["count"] = data.count ? data.count - 0 : 0;
				if (list.length != 0) {
					listShow()
					if (tabsType == "sportsTicket") {
						laytpl($("#listTpl").html()).render(
							list,
							function (html) {
								$(".entry-sports ul").append(html);
								common.scrollEnd(scrollIndex);
							}
						);
					} else {
						laytpl($("#timeCardTpl").html()).render(
							list,
							function (html) {
								$(".entry-timeCard ul").append(html);
								common.scrollEnd(scrollIndex);
							}
						);
					}
					$(".loading").hide();
					$(".empty").show();
					$(".empty").text(L_("没有更多了"));
				} else {
					$(".loading").hide();
					$(".empty").show();
					if (params[tabsType]["nowPage"] > 1) {
						$(".empty").text(L_("没有更多了"));
					} else {
						$(".empty").text(L_("暂无结果"));
					}
				}
			}
		);
	}
});
