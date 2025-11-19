// 在 #app 标签下渲染一个按钮组件
new Vue({
  el: "#app",
  data() {
    return {
      loading: false,
      finished: false,
      calendarShow: false,
      params: {
        page: 1,
        page_size: 10,
        start_date: "",
        end_date: "",
      },
      defaultDate: "",
      minDate: "",
      dataList: [],
    };
  },
  created() {
    common.vue_render_show(".loading", "#app");
    // 创建一个新的 Date 对象，表示当前日期和时间
    var currentDate = new Date();

    // 获取50年前的日期
    var tenYearsAgo = new Date();
    tenYearsAgo.setFullYear(currentDate.getFullYear() - 10);
    this.minDate = tenYearsAgo;

    // 获取前7天的日期
    var sevenDaysAgo = new Date(currentDate);
    sevenDaysAgo.setDate(currentDate.getDate() - 6);
    this.defaultDate = [sevenDaysAgo, currentDate];

    this.params.start_date = this.formatDate(sevenDaysAgo);
    this.params.end_date = this.formatDate(currentDate);
  },
  methods: {
    getDataList(type) {
      common.httpV20(
        "/storestaff/storestaff.index/storeDateBill",
        this.params,
        (data) => {
          if (type == "search") {
            this.dataList = data.data;
          } else {
            this.dataList = this.dataList.concat(data.data);
          }
          // 加载状态结束
          this.loading = false;
          // 数据全部加载完成
          if (data.data.length <= 9) {
            //没有更多数据了
            this.finished = true;
          }
          this.params.page++;
        }
      );
    },
    //返回上一页
    toBack() {
      if (common.checkApp()) {
        if (common.checkAndroidApp()) {
          window.pigcmspackapp.closewebview(2);
        } else {
          common.iosFunction("closewebview/2", {
            name: "closewebview",
            type: "2",
          });
        }
      } else {
        window.history.go(-1);
      }
    },
    //搜索事件
    onSearch(e) {
      this.params.page = 1;
      this.getDataList("search");
    },
    //打开选择框
    openSecect() {
      this.calendarShow = true;
    },
    onConfirm(date) {
      const [start, end] = date;
      this.date = `${this.formatDate(start)} - ${this.formatDate(end)}`;
      this.params.start_date = this.formatDate(start);
      this.params.end_date = this.formatDate(end);
      this.calendarShow = false;
      this.params.page = 1;
      this.getDataList("search");
    },
    onLoad() {
      // 异步更新数据
      this.getDataList();
    },
    formatDate(date) {
      return `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`;
    },
    storeDateBillPrint(item) {
      vant.Dialog.confirm({
        title: L_("提示"),
        message: L_("确定要打印吗？"),
        confirmButtonColor: "#2ecc71",
      }).then(() => {
        common.httpV20(
          "/storestaff/storestaff.index/printStoreDateBill",
          { bill_date: item.bill_date },
          (data) => {
            vant.Toast( L_("打印成功"));
          }
        );
      });
    },
  },
});
