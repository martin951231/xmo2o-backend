// 在 #app 标签下渲染一个按钮组件
new Vue({
  el: "#app",
  template: ``,
  data() {
    return {
      loading: false,
      finished: false,
      params: {
        page: 1,
        page_size: 10,
        order_type: "",
        search_type: "nickname",
        keywords: "",
        start_time: "",
        end_time: "",
        province_id: "",
        city_id: "",
        area_id: "",
        type: "pay_money",
        min_money: 0,
        max_money: 0,
      },
      dataList: [],
      showPopover: false,
      searchType: [
        {
          label: L_("用户姓名"),
          value: "nickname",
        },
        {
          label: L_("手机号"),
          value: "phone",
        },
        {
          label: L_("订单号"),
          value: "order_no",
        },
      ],
      businessType: [],
      tradeType: [
        {
          value: "pay_money",
          label: L_("交易金额"),
        },
        {
          value: "bill_money",
          label: L_("总结算金额"),
        },
        {
          value: "wait_bill_money",
          label: L_("待结算金额"),
        },
        {
          value: "already_bill_money",
          label: L_("已结算金额"),
        },
        {
          value: "service_fee",
          label: L_("总服务费"),
        },
      ],
      businessList: [],
      dateShow: false,
      date: "",
      mainColor: "#2ecc71",
      tabsActive: 0,
      show: false,
      areaOptions: [],
      areaName: "",
      cascaderValue: "",
      minDate: "",
    };
  },
  created() {
    common.vue_render_show(".loading", "#app");
    let currentDate = new Date();
    // 获取当前年份
    let currentYear = currentDate.getFullYear();
    // 计算减去50年的年份
    currentDate.setFullYear(currentYear - 30);
    this.minDate = currentDate;
    this.getAllArea();
    common.httpV20(`/internal_card/index/businessType`, {}, (res) => {
      let obj = {
        "": L_("全部"),
      };
      this.businessType = Object.assign({}, obj, res);
    });
  },
  methods: {
    onClickLeft() {
      redirect("back");
    },
    getAllArea() {
      common.httpV20("/common/common.area/getAllArea", {}, (res) => {
        this.areaOptions = res;
      });
    },
    getDataList(isSearch) {
      let success = (res) => {
        // is_read 1-已读，0-未读
        let url = "";
        if (this.params.page == 1) {
          this.dataList = res.data;
        } else {
          this.dataList = this.dataList.concat(res.data);
        }

        // 加载状态结束
        this.loading = false;
        // 数据全部加载完成
        if (res.data.length < 10) {
          //没有更多数据了
          this.finished = true;
        } else {
          this.finished = false;
        }
        this.params.page++;
      };
      if (isSearch) {
        this.params.page = 1;
        this.loading = true;
      }
      if (this.tabsActive == 0) {
        url = "/internal_card/storestaff.InternalCard/orderList";
      } else {
        url = "/internal_card/storestaff.InternalCard/billList";
      }
      common.httpV20(url, this.params, success);
    },
    onLoad() {
      // 异步更新数据
      this.getDataList();
    },
    formatDate(date) {
      return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`;
    },
    openDate() {
      this.dateShow = true;
    },
    openArea() {
      this.show = true;
    },
    onDateConfirm(date) {
      const [start, end] = date;
      this.dateShow = false;
      this.date = `${this.formatDate(start)} - ${this.formatDate(end)}`;
      this.params.start_time = this.formatDate(start);
      this.params.end_time = this.formatDate(end);
    },
    deleteDate() {
      this.date = "";
      this.params.start_time = "";
      this.params.end_time = "";
    },
    deleteArea() {
      this.params.province_id = "";
      this.params.city_id = "";
      this.params.area_id = "";
      this.cascaderValue = "";
      this.areaName = "";
    },
    search() {
      this.getDataList(true);
    },
    onAreaFinish(e) {
      this.params.province_id = e.selectedOptions[0]
        ? e.selectedOptions[0].area_id
        : "";
      this.params.city_id = e.selectedOptions[1]
        ? e.selectedOptions[1].area_id
        : "";
      this.params.area_id = e.selectedOptions[2]
        ? e.selectedOptions[2].area_id
        : "";
      this.areaName = "";
      e.selectedOptions.forEach((v) => {
        this.areaName += v.area_name + " ";
      });
      this.show = false;
    },
    //去详情页
    goDetail(item) {
      if (this.tabsActive == 0) {
        openWebviewUrl(
          `internalCardPayDetails.html?order_no=${item.order_no}&tabsActive=${this.tabsActive}&order_type=${item.business_type}`
        );
      } else {
        openWebviewUrl(
          `internalCardPayDetails.html?&bill_date=${item.bill_date}&tabsActive=${this.tabsActive}`
        );
      }
    },
    tabsChange() {
      this.getDataList(true);
    },
  },
});
