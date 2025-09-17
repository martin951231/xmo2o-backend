/**
 *******************
 *   @author 郑亚莉
 *   @date 2020-04-29
 *   @description 公共方法
 *******************
 */
import {
  SYSTEM_TYPES
} from '@/store/mutation-types'
import path from '@/router/router-path'
import Vue from 'vue'
import moment from 'moment'

const dateFormat = 'YYYY-MM-DD'
const timeFormat = 'HH:mm:ss'
const dtFormat = 'YYYY-MM-DD HH:mm:ss'

// 获取cookie
export function getCookie (name) {
  var arr = []
  var reg = new RegExp('(^| )' + name + '=([^;]*)(;|$)')
  if (arr = document.cookie.match(reg)) {
    return unescape(arr[2])
  } else {
    return null
  }
}

// 设置cookie,增加到vue实例方便全局调用
// eslint-disable-next-line camelcase
export function setCookie (c_name, value, expiredays, path) {
  var exdate = new Date()
  exdate.setDate(exdate.getDate() + expiredays)
  // eslint-disable-next-line camelcase
  // console.log(c_name + '=' + escape(value) + ((expiredays == null) ? '' : ';expires=' + exdate.toGMTString()) + ((path == null) ? ';path=/' : ';path=' + path));
  document.cookie = c_name + '=' + escape(value) + ((expiredays == null) ? '' : ';expires=' + exdate.toGMTString()) + ';path=/'
};

// 删除cookie
export function delCookie (name) {
  var exp = new Date()
  exp.setTime(exp.getTime() - 100000)
  var cval = getCookie(name)
  if (cval != null) {
    document.cookie = name + '=' + cval + ';expires=' + exp.toGMTString() + ';path=/'
  }
};

// 判断接口或路由所属系统并返回当前系统名称
export function getSystemName (url) {
  url = url.toLowerCase()
  let systemName_arr = []
  systemName_arr = SYSTEM_TYPES.filter(item => url.indexOf(item) !== -1)
  if (systemName_arr.length > 1) {
    if (systemName_arr[0] == 'community') {
      return systemName_arr[1]
    }
    systemName_arr = SYSTEM_TYPES.filter(item => url.indexOf('/' + item) !== -1)
  }
  return systemName_arr.length ? systemName_arr[0] : null
};

// 获取系统后台的名称 url为系统名或者接口、路由等
export function getSystemTitle (url) {
  const systemName = getSystemName(url)
  let title = '系统后台'
  switch (systemName) {
    case 'platform':
      title = '系统后台'
      break
    case 'merchant':
      title = '商家后台'
      break
    case 'storestaff':
      title = '店员后台'
      break
    case 'community':
      title = '社区后台'
      break
    case 'property':
      title = '物业后台'
      break
    case 'village':
      title = '小区后台'
      break
  }
  return title
}

// 判断接口或路由所属系统并返回token名称 url:接口请求路径或路由
export function getTokenName (url, token = '') {
  if (token) {
    return token
  }
  return getSystemName(url) + '_access_token'
};

/**
根据系统名称返回不同的登录路由首页路由等
system 系统名称 或 接口名 或 路由地址
type 获取的页面 login: 登录页面; index: 首页; register: 注册页;
*/
export function getRouter (system, type) {
  const systemName = getSystemName(system)
  let routerName = ''
  if (!systemName && type === 'login') { // 如果路由不带系统信息则默认跳入系统后台
    routerName = 'platformLogin'
  } else if (type === 'login') {
    routerName = systemName + 'Login'
  } else if (type === 'index') {
    routerName = systemName + 'Index'
  } else if (type === 'register') {
    routerName = systemName + 'Register'
  }
  return path[routerName]
}

/**
根据路由name返回路由的path
*/
export function getRouterPath (name) {
  const menu = Vue.ls.get('systemMenu')
  console.log('menu', menu)
  if (menu && menu.length) {
    const menuObj = menu.find((item) => {
      return item['name'] == name
    })
    console.log('menuObj', menuObj)
    return menuObj.path
  } else {
    return '/'
  }
}

/**
修改switch默认值
list: 后端获取的form数据数组 数组
formData：表单数据 对象
types：需要修改的type 数组
*/
export function transferFormValue (list, formData, types) {
  if (types.length && list.length) {
    for (const type of types) {
      switch (type) {
        case 'switch':
          const switchs = list.filter(item => {
            return item['type'] == type
          })
          if (switchs.length) {
            for (const item of switchs) {
              formData[item.name] = formData[item.name] ? '1' : '0'
            };
          };
          break
        case 'date':
          const dates = list.filter(item => {
            return item['type'] == type
          })
          if (dates.length) {
            for (const item of dates) {
              formData[item.name] = moment(formData[item.name]).format(dateFormat)
            };
          };
          break
        case 'time':
          const times = list.filter(item => {
            return item['type'] == type
          })
          if (times.length) {
            for (const item of times) {
              formData[item.name] = moment(formData[item.name]).format(timeFormat)
            };
          };
          break
        case 'image':
          const images = list.filter(item => {
            return item['type'] == type
          })
          if (images.length) {
            for (const item of images) {
              if (formData[item.name] && formData[item.name].length) {
                formData[item.name] = formData[item.name][0]
              }
            };
          };
          break
        default:
          break
      }
    }
  }
  return formData
}

//  表单数据里的日期moment格式转化为string
export function dateToDateString (formData, types) {
  // console.log('ayl 44444', formData)
  for (const t in types) {
    for (const k in formData) {
      // 要转化的格式
      if (t == k) {
        // console.log('zyl hhhh', t)
        if (formData[k] != null && typeof formData[k] === 'object') {
          // 数组
          if (typeof formData[k].length === 'number' && formData[k].length > 0) {
            formData[k].forEach(i => {
              if (i != null && typeof i === 'object') {
                i = timeToString(i, types[t])
              }
            })
          } else {
            formData[k] = timeToString(formData[k], types[t])
          }
          // console.log('zyl formData[k]', formData[k])
        }
      }
    }
  }
  // console.log(1111, formData)
  return formData
}
// 验证对象是否是moment格式 如果是返回时间的字符串格式
function timeToString (obj, type) {
  if (type == 'date') {
    return moment(obj).format(dateFormat)
  } else if (type == 'time') {
    return moment(obj).format(timeFormat)
  } else if (type == 'datetime') {
    return moment(obj).format(dtFormat)
  } else {
    return false
  }
}

export function timeFix () {
  const time = new Date()
  const hour = time.getHours()
  return hour < 9 ? '早上好' : hour <= 11 ? '上午好' : hour <= 13 ? '中午好' : hour < 20 ? '下午好' : '晚上好'
}

export function welcome () {
  const arr = ['休息一会儿吧', '准备吃什么呢?', '要不要打一把 DOTA', '我猜你可能累了']
  const index = Math.floor(Math.random() * arr.length)
  return arr[index]
}

/**
 * 触发 window.resize
 */
export function triggerWindowResizeEvent () {
  const event = document.createEvent('HTMLEvents')
  event.initEvent('resize', true, true)
  event.eventType = 'message'
  window.dispatchEvent(event)
}

export function handleScrollHeader (callback) {
  let timer = 0

  let beforeScrollTop = window.pageYOffset
  callback = callback || function () { }
  window.addEventListener(
    'scroll',
    event => {
      clearTimeout(timer)
      timer = setTimeout(() => {
        let direction = 'up'
        const afterScrollTop = window.pageYOffset
        const delta = afterScrollTop - beforeScrollTop
        if (delta === 0) {
          return false
        }
        direction = delta > 0 ? 'down' : 'up'
        callback(direction)
        beforeScrollTop = afterScrollTop
      }, 50)
    },
    false
  )
}

export function isIE () {
  const bw = window.navigator.userAgent
  const compare = (s) => bw.indexOf(s) >= 0
  const ie11 = (() => 'ActiveXObject' in window)()
  return compare('MSIE') || ie11
}

/**
 * Remove loading animate
 * @param id parent element id or class
 * @param timeout
 */
export function removeLoadingAnimate (id = '', timeout = 1500) {
  if (id === '') {
    return
  }
  setTimeout(() => {
    document.body.removeChild(document.getElementById(id))
  }, timeout)
}

// 判断数据是否为空
export function isEmpty (data) {
  if (!data || data instanceof Array && data.length == 0) {
    // 空字符串、undefined、 空数组
    return true
  } else if (data instanceof Object && JSON.stringify(data) == '{}') {
    // 空对象
    return true
  } else {
    return false
  }
}
