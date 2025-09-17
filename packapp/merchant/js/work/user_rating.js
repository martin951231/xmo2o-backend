var client = common.checkAndroidApp() ? 2 : common.checkIosApp() ? 1 : 0;
new Vue({
  el: "#app",
  template: ``,
  data() {
    return {
      tabsItem: 3,
      tabsList: [
        {
          order_type: "3",
          title: "快店评论",
        },
        {
          order_type: "0",
          title: "团购评论",
        },
        {
          order_type: "4",
          title: "快店评论",
        },
      ],
      mainColor: "#ffa200",
      option1: [],
      option2: [
        {
          value: "all",
          text: L_("评价类型"),
        },
        {
          value: "high",
          text: L_("满意(3-5分)"),
        },
        {
          value: "mid",
          text: L_("不满意(1-3分)"),
        },
      ],
      option3: [
        {
          value: "0",
          text: L_("评价回复"),
        },
        {
          value: "yes",
          text: L_("已回复"),
        },
        {
          value: "no",
          text: L_("未回复"),
        },
      ],
      screen1: "", //店铺id
      screen2: "all",
      screen3: "0",
      merchant_reply: "", //回复与未回复
      tab: "", //好评
      page: 1,
      loading: false,
      finished: false,
      order_type: 3,
      reply_list: [],
      pageData: null
    };
  },
  created() {
    common.vue_render_show(".loading", "#app");
    var ashop = common.getCache("ashop");
    var ctuan = common.getCache("ctuan");
    var byin = common.getCache("byin");
    if (!ashop) {
      this.tabsList[0].title = "快店" + "评论";
    }
    if (!ctuan) {
      this.tabsList[1].title = "团购" + "评论";
    }
    if (!byin) {
      this.tabsList[2].title = "新版餐饮" + "评论";
    }
  },
  methods: {
    onClickLeft() {
      if (
        (common.checkApp() && window.history.length <= 1) ||
        (common.checkApp() && index != undefined)
      ) {
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
        window.document.execCommand("Refresh");
      }
    },
    tabChange(e) {
      this.order_type = this.tabsList[e].order_type;
      this.page = 1;
      this.tab = "all";
      this.loading = true;
      this.finished = false;
      this.reply_list = [];
      this.getList();
    },
    menuChange(e, type) {
      if (type == 1) {
        this.screen1 = e;
      } else if (type == 2) {
        this.tab = e;
      } else {
        this.merchant_reply = e;
      }
      this.loading = true;
      this.finished = false;
      this.page = 1;
      this.reply_list = [];
      this.getList();
    },
    getList() {
      common.http(
        "Merchantapp&a=user_rating_list",
        {
          client: client,
          tab: this.tab,
          order_type: this.order_type,
          merchant_reply: this.merchant_reply,
          store_id: this.screen1,
          page: this.page,
        },
        (data) => {
          this.pageData = data;
          this.reply_list = this.reply_list.concat(data.reply_list);
          if (data.store_list && data.store_list.length > 0) {
            this.option1 = data.store_list.map((v) => {
              return {
                text: v.name,
                value: v.store_id,
              };
            });
            this.option1.unshift({
              text: L_("全部店铺"),
              value: "",
            });
          }
          this.page++;
          this.loading = false;
          if (data.reply_list && data.reply_list.length <= 5) {
            this.finished = true;
          } else if (!data.reply_list) {
            this.finished = true;
          }
        }
      );
    },
    onLoad() {
      this.getList();
    },
    lookImg(item, items, indexs) {
      vant.ImagePreview({
        images: item.reply_pic.map((v) => v.image),
        startPosition: indexs,
      });
    },
    goUrl(item) {
      openWebviewUrl(`reply.html?data=${item.pigcms_id}`);
    },
  },
});
