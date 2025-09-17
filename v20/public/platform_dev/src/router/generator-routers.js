import Vue from 'vue'
import path from '@/router/router-path.js'
import {
  request
} from '@/utils/request'
import userPlatformApi from '../api/user/platform'
import routerComponents from './routerComponents'
import mockRouter from './routerComponents/example/mockRouter'
import {
  getRouter
} from '@/utils/util'
import {
  getUserMenu
} from '@/api/user/user'
import {
  AppPage
} from '@/views/example/account/center/page/index'

const constantRouterComponents = routerComponents

// 前端未找到页面路由（固定不用改）
const notFoundRouter = {
  path: '*',
  redirect: path.notFound,
  hidden: true
}



/**
 * 动态生成菜单
 * @param token
 * @returns {Promise<Router>}
 */
export const generatorDynamicRouter = (tokenName, location) => {
  const url = getUserMenu(tokenName)
  // /community/property_api.adminmenu/menuList
  return new Promise((resolve, reject) => {
    request(url).then(res => {
      Vue.ls.set('systemMenu', res.systemMenu)
      let result = res.systemMenu
      // 一级菜单的父id
      let parantId = 0
      // 首页路由
      let indexPath = getRouter(tokenName, 'index') // 可以不维护首页了，默认取菜单第一个做首页
      // 一级菜单的component
      let indexComponent = 'BasicLayout'
      // ----------------- 处理应用菜单 start -----------------
      let app_id = 0
      let homeItem = null
      let arr = result.filter(item => item.path == location) // 找出当前路由
      if (arr.length) { //
        app_id = arr[0].app_id
        console.log('app_id', app_id)
        if (app_id) { // 如果有appid表示此路由时应用菜单路由，应该筛选出此应用下的路由
          indexPath = ''
          parantId = arr[0].id
          indexComponent = 'AppLayout'
          result = result.filter(item => item.app_id == app_id)
          result.forEach((item, i) => {
            if (item.parentId == 0) {
              // 当前应用下的一级菜单
              homeItem = item
              homeItem.parentId = -100
              parantId = item.id
              const storageName = 'home_menu_' + app_id
              Vue.ls.set(storageName, item)
            }
            item.meta.show = true
          })
          console.log('appArr', result)
        }
      }
      // ----------------- 处理应用菜单 end ----------------- 
      //      后端数据, 根级树数组,  根级 PID
      const menuNav = []
      const childrenNav = []
      listToTree(result, childrenNav, parantId)
      console.log('childrenNav', childrenNav)
      if (childrenNav.length && !indexPath) {
        indexPath = childrenNav[0].path // 选中第一个作为首页展示
      }
      console.log('indexPath', indexPath)

      // 根级菜单 根据系统设置不同的重定向首页
      const rootRouter = {
        key: '',
        name: 'index',
        path: '/',
        component: indexComponent,
        redirect: indexPath,
        meta: {
          title: '首页'
        },
        children: []
      }
      rootRouter.children = childrenNav
      if (homeItem) {
        homeItem.redirect = indexPath
        homeItem.meta.show = false
        rootRouter.children.push(homeItem)
      }
      menuNav.push(rootRouter)
      // console.log('menuNav', menuNav)
      const routers = generator(menuNav)
      routers.push(notFoundRouter)
      console.log('routers', routers)
      resolve(routers)
    }).catch(err => {
      reject(err)
    })
  })
}

/**
 * 格式化树形结构数据 生成 vue-router 层级路由表
 *
 * @param routerMap
 * @param parent
 * @returns {*}
 */
export const generator = (routerMap, parent) => {
  return routerMap.map(item => {
    const {
      title,
      show,
      hideChildren,
      hiddenHeader,
      target,
      icon,
      keepAlive,
      permission
    } = item.meta || {}
    const currentRouter = {
      // 如果路由设置了 path，则作为默认 path，否则 路由地址 动态拼接生成如 /dashboard/workplace
      path: item.path || `${parent && parent.path || ''}/${item.key}`,
      // 路由名称，建议唯一
      name: item.name || item.key || '',
      // 该路由对应页面的 组件
      component: (constantRouterComponents[item.component || item.key]) || (() => import(`@/views/${item.component}`)),
      // meta: 页面标题, 菜单图标, 页面权限(供指令权限用，可去掉)
      meta: {
        title: title,
        icon: icon || undefined,
        hiddenHeaderContent: hiddenHeader,
        target: target,
        permission: permission,
        keepAlive: keepAlive ? keepAlive : false
      }
    }
    // 是否设置了隐藏菜单
    if (show === false) {
      currentRouter.hidden = true
    }
    // 是否设置了隐藏子菜单
    if (hideChildren) {
      currentRouter.hideChildrenInMenu = true
    }
    // 为了防止出现后端返回结果不规范，处理有可能出现拼接出两个 反斜杠
    if (!currentRouter.path.startsWith('http')) {
      currentRouter.path = currentRouter.path.replace('//', '/')
    }
    // 重定向
    item.redirect && (currentRouter.redirect = item.redirect)
    // 是否有子菜单，并递归处理
    if (item.children && item.children.length > 0) {
      // Recursion
      currentRouter.children = generator(item.children, currentRouter)
    }
    return currentRouter
  })
}

/**
 * 数组转树形结构
 * @param list 源数组
 * @param tree 树
 * @param parentId 父ID
 */
const listToTree = (list, tree, parentId) => {
  list.forEach(item => {
    // 判断是否为父级菜单
    if (item.parentId == parentId) {
      const child = {
        ...item,
        key: item.name,
        children: []
      }
      // 迭代 list， 找到当前菜单相符合的所有子菜单
      listToTree(list, child.children, item.id)
      // 删掉不存在 children 值的属性
      if (child.children.length <= 0) {
        delete child.children
      }
      // 加入到树中
      tree.push(child)
    }
  })
}