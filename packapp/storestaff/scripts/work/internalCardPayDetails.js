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
        search_type: "",
        bill_type: 0,
        tax_ticket_type: 0,
        keywords: "",
      },
      dataList: [],
      searchType: [
        {
          label: L_("经办人"),
          value: "admin_user_name",
        },
        {
          label: L_("税点"),
          value: "tax_point",
        },
        {
          label: L_("结算金额"),
          value: "bill_money",
        },
      ],
      settlementList: [
        {
          label: L_("全部"),
          value: 0,
        },
        {
          label: L_("现金"),
          value: 1,
        },
        {
          label: L_("对公转账"),
          value: 2,
        },
      ],
      billList: [
        {
          label: L_("全部"),
          value: 0,
        },
        {
          label: L_("普票"),
          value: 2,
        },
        {
          label: L_("专票"),
          value: 1,
        },
      ],
      dateShow: false,
      date: "",
      mainColor: "#2ecc71",
      urlParam: "",
    };
  },
  created() {
    common.vue_render_show(".loading", "#app");
    this.urlParam = urlParam;
    this.params.order_no = urlParam.order_no;
    this.params.order_type = urlParam.order_type;
    if (this.urlParam.tabsActive == 1) {
      this.params.bill_date = urlParam.bill_date;
      this.params.search_type = "admin_user_name";
    }
  },
  methods: {
    onClickLeft() {
      redirect("back");
    },
    getDataList(isSearch) {
      let success = (res) => {
        // is_read 1-已读，0-未读
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
      if (this.urlParam.tabsActive == 0) {
        url = "/internal_card/storestaff.InternalCard/orderPayList";
      } else {
        url = "/internal_card/storestaff.InternalCard/merBillRecord";
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
    onDateConfirm(date) {
      const [start, end] = date;
      this.dateShow = false;
      this.date = `${this.formatDate(start)} - ${this.formatDate(end)}`;
    },
    search() {
      this.getDataList(true);
    },
  },
});
