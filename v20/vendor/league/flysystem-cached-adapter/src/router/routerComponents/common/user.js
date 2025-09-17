/**
 *******************
 *   @author 郑亚莉
 *   @date 2020-05-14
 *   @description 用户相关组件
 *******************
 */
const userComponents = {
  // 你需要动态引入的页面组件
  'System': () => import('@/views/common/iframePage/IframePage'),
  'Index': () => import('@/views/common/iframePage/IframePage'),
}
export default userComponents
