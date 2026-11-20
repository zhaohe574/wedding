import { computed, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import type { BrandProfile } from '@/constants/brand'

/**
 * 品牌信息的**唯一读取入口**。
 *
 * 页面 / 组件只认这里，不要直接 import `constants/brand`（那是兜底，不是真源），
 * 也不要自己读 `appStore.config.website`（会漏掉缓存与兜底，各处口径漂移）。
 *
 * 三种状态怎么表现（调用方不需要自己写 v-if）：
 *   - 加载中：`brand` 先给缓存值，没有缓存给兜底值 —— 名称永远有内容，**不出现空白/骨架**；
 *   - 配置缺失：字段为空即按「未配置」处理，`logo` 为空 → 回退印章字，`slogan` 为空 → 隐藏该行；
 *   - 请求失败：继续用缓存值；连缓存都没有才用兜底值。`ready=false` 只用于埋点排查。
 */

/**
 * 加载失败的图标 URL（模块级单例）。
 * 品牌图标全站同一个 URL，任一处加载失败就全站回退到印章字，
 * 避免「首页破了、我的页还在转圈」这种一半一半的状态。
 */
const failedLogo = ref('')

export function useBrand() {
    const appStore = useAppStore()

    const brand = computed<BrandProfile>(() => appStore.brand)

    /** 有图标且没加载失败过才渲染 <image>，否则回退印章字 */
    const showLogo = computed(() => !!brand.value.logo && failedLogo.value !== brand.value.logo)

    /** 绑定到 <image @error>：标记当前 URL 不可用 */
    function onLogoError() {
        failedLogo.value = brand.value.logo
    }

    /** 是否已从服务端拿到配置（false = 当前展示的是缓存或兜底，仅供排查/埋点） */
    const ready = computed(() => appStore.configLoaded)

    /** 当前品牌值来源，仅供排查 */
    const source = computed(() => appStore.brandSource)

    /** 强制刷新品牌配置（下拉刷新、从后台改完回来验活时用） */
    function refresh() {
        return appStore.getConfig(true)
    }

    return { brand, showLogo, onLogoError, ready, source, refresh }
}
