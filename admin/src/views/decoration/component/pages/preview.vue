<template>
    <div class="decorate-preview">
        <!-- ============ 工具栏 ============ -->
        <div class="decorate-preview__toolbar">
            <el-button v-if="showPageMetaButton" @click="handleClickPageMeta">页面设置</el-button>
            <el-button
                v-if="!isWidgetStructureLocked"
                type="primary"
                @click="showWidgetSelector = true"
                >添加组件</el-button
            >
            <el-button v-if="currentPageType === 'splash'" @click="replayKey += 1"
                >重播倒计时</el-button
            >
            <div class="decorate-preview__status">
                <el-tag size="small" type="info">{{ currentPageName }}</el-tag>
                <span class="decorate-preview__hint">
                    预览为结构同构版，最终以真机为准
                </span>
            </div>
        </div>

        <!-- ============ 主体：组件树 + 手机壳 ============ -->
        <div class="decorate-preview__body">
            <!-- 左：页面结构（选中 / 显隐 / 上下移 / 删除），替代原来画布上的悬浮按钮 -->
            <div class="widget-tree">
                <div class="widget-tree__title">页面结构</div>
                <div
                    v-for="(widget, index) in pageData"
                    :key="widget.id"
                    class="widget-tree__row"
                    :class="{
                        'is-active': index === effectiveModelValue,
                        'is-disabled': widget?.disabled,
                        'is-hidden': widget?.content?.enabled === 0
                    }"
                    @click="handleClick(widget, index)"
                >
                    <span class="widget-tree__index">{{ index + 1 }}</span>
                    <span class="widget-tree__name">{{ widget.title || widget.name }}</span>
                    <span v-if="widget?.content?.enabled === 0" class="widget-tree__badge"
                        >隐藏</span
                    >
                    <div v-if="showWidgetActions(index)" class="widget-tree__ops" @click.stop>
                        <el-tooltip
                            v-if="canToggleWidgetVisibility(widget)"
                            effect="dark"
                            :content="widget?.content?.enabled === 0 ? '显示' : '隐藏'"
                            placement="top"
                        >
                            <el-button
                                size="small"
                                :icon="widget?.content?.enabled === 0 ? Hide : View"
                                @click="changeShowCom(widget.content)"
                            />
                        </el-tooltip>
                        <el-tooltip
                            v-if="!isWidgetStructureLocked"
                            effect="dark"
                            content="上移"
                            placement="top"
                        >
                            <el-button
                                size="small"
                                :icon="ArrowUpBold"
                                :disabled="canMoveUpCom(index)"
                                @click="rearrangeArray(index, index - 1)"
                            />
                        </el-tooltip>
                        <el-tooltip
                            v-if="!isWidgetStructureLocked"
                            effect="dark"
                            content="下移"
                            placement="top"
                        >
                            <el-button
                                size="small"
                                :icon="ArrowDownBold"
                                :disabled="canMoveDownCom(index)"
                                @click="rearrangeArray(index, index + 1)"
                            />
                        </el-tooltip>
                        <el-tooltip
                            v-if="!isWidgetStructureLocked"
                            effect="dark"
                            content="删除"
                            placement="top"
                        >
                            <el-button
                                size="small"
                                type="danger"
                                :icon="Delete"
                                @click="handleDeleteWidget(index)"
                            />
                        </el-tooltip>
                    </div>
                </div>
                <div v-if="!pageData.length" class="widget-tree__empty">本页暂无可配置组件</div>
            </div>

            <!-- 右：手机壳。375×812，默认 1:1，可切「适应」按画布高度缩放 -->
            <div ref="canvasRef" class="canvas">
                <div class="canvas__toolbar">
                    <el-radio-group v-model="zoom" size="small">
                        <el-radio-button :label="0">适应</el-radio-button>
                        <el-radio-button :label="1">100%</el-radio-button>
                        <el-radio-button :label="1.25">125%</el-radio-button>
                    </el-radio-group>
                </div>
                <div class="canvas__frame" :style="{ zoom: scale }">
                    <!-- 首页 -->
                    <home-preview
                        v-if="currentPageType === 'home'"
                        :widgets="pageData"
                        :selected="effectiveModelValue"
                        :brand="brand"
                        :domain="domain"
                        @select="(i) => emit('update:modelValue', i)"
                        @preview-popup="showPopup = true"
                    />

                    <!-- 开屏广告 -->
                    <splash-preview
                        v-else-if="currentPageType === 'splash'"
                        :content="splashContent"
                        :domain="domain"
                        :replay-key="replayKey"
                    />

                    <!-- 个人中心 -->
                    <user-preview
                        v-else-if="currentPageType === 'user'"
                        :widgets="pageData"
                        :selected="effectiveModelValue"
                        :brand="brand"
                        @select="(i) => emit('update:modelValue', i)"
                    />

                    <!-- 客服设置 -->
                    <service-preview
                        v-else-if="currentPageType === 'service'"
                        :content="serviceContent"
                    />

                    <!-- 登录页 -->
                    <login-preview
                        v-else-if="currentPageType === 'login'"
                        :content="loginContent"
                        :brand="brand"
                        :domain="domain"
                    />

                    <!-- 未接入的页面：不假装能预览 -->
                    <div v-else class="canvas__pending">
                        <el-icon :size="34"><Monitor /></el-icon>
                        <p class="canvas__pending-title">本页尚未在小程序端接入</p>
                        <p class="canvas__pending-desc">
                            可以编辑和保存，但小程序不会渲染该页的装修配置，
                            因此这里不提供预览，避免「预览有、真机没有」。
                        </p>
                    </div>

                    <!-- 弹窗广告：真机会盖屏，预览里点开才盖，避免挡住其他组件 -->
                    <div v-if="showPopup" class="pv-popup-mask" @click.self="showPopup = false">
                        <div class="pv-popup" :style="{ background: popupContent.background_color }">
                            <img
                                v-if="popupImage"
                                class="pv-popup__img"
                                :src="popupImage"
                                alt=""
                            />
                            <div class="pv-popup__body">
                                <p v-if="popupContent.title" class="pv-popup__title">
                                    {{ popupContent.title }}
                                </p>
                                <p v-if="popupContent.content" class="pv-popup__text">
                                    {{ popupContent.content }}
                                </p>
                            </div>
                            <div class="pv-popup__actions">
                                <div v-if="popupContent.show_close === 1" class="pv-popup__ghost">
                                    暂不
                                </div>
                                <div class="pv-popup__cta">
                                    {{ popupContent.button_text || '查看详情' }}
                                </div>
                            </div>
                            <div class="pv-popup__close" @click="showPopup = false">×</div>
                        </div>
                    </div>
                </div>
                <p class="canvas__caption">375 × 812 · 当前 {{ Math.round(scale * 100) }}%</p>
            </div>
        </div>

        <!-- ============ 组件选择器（仅非固定结构页可用） ============ -->
        <el-dialog
            v-if="!isWidgetStructureLocked"
            v-model="showWidgetSelector"
            title="添加组件"
            width="600px"
        >
            <div v-if="filteredAvailableWidgets.length > 0" class="grid grid-cols-3 gap-4">
                <div
                    v-for="item in filteredAvailableWidgets"
                    :key="item.name"
                    class="p-4 border rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 transition-colors"
                    :class="{
                        'border-primary bg-blue-50': isWidgetAdded(item.name),
                        'opacity-50': isWidgetAdded(item.name)
                    }"
                    @click="handleAddWidget(item)"
                >
                    <div class="text-center">
                        <div
                            class="w-12 h-12 mx-auto mb-2 bg-gray-100 rounded-lg flex items-center justify-center"
                        >
                            <el-icon class="text-xl text-gray-500">
                                <component :is="item.icon" />
                            </el-icon>
                        </div>
                        <div class="text-sm font-medium">{{ item.title }}</div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ isWidgetAdded(item.name) ? '已添加' : '点击添加' }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-400">暂无可添加的组件</div>
            <template #footer>
                <el-button @click="showWidgetSelector = false">关闭</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup>
/**
 * 装修预览（后台自绘版，不依赖 H5 页面）。
 *
 * 定位：**结构同构**的近似预览，不是像素级仿真。
 *   - 覆盖装修真正驱动的部分：首页（banner 含图片上浮文字 / home-brand / home-service-categories /
 *     home-recommend / home-lucky-days / home-flow / home-popup-ad）与开屏页（splash-ad）；
 *   - 尺寸取自小程序源码的 rpx 数字（`rpx()` 换算），版式骨架一致；
 *   - 兜底文案与断行规则来自 `shared/contracts/decorate.ts`，与小程序同源；
 *   - 接口内容（本期推荐 / 近期吉日）画成中性占位：内容不可配，但已支持后台「显示本区块」
 *     开关；关闭或无数据时真机整块不渲染（含标题）。
 *
 * 可接受的差异见 docs §11；编辑与保存逻辑零改动（props / emits 与改造前一致）。
 */
import { ArrowDownBold, ArrowUpBold, Delete, Hide, Monitor, View } from '@element-plus/icons-vue'
import { DECORATE_PAGES } from '@shared/contracts/decorate'
import { cloneDeep } from 'lodash-es'
import type { PropType } from 'vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { getWebsite } from '@/api/setting/website'
import { getNonDuplicateID } from '@/utils/util'

import HomePreview from '../preview/home-preview.vue'
import LoginPreview from '../preview/login-preview.vue'
import ServicePreview from '../preview/service-preview.vue'
import SplashPreview from '../preview/splash-preview.vue'
import UserPreview from '../preview/user-preview.vue'
import widgets from '../widgets'

type PageType = 'home' | 'user' | 'service' | 'splash' | 'login'

/** 页面类型映射：由 shared 契约生成，避免页面 id 改动后这里忘了同步 */
const pageTypeMap: Record<string, PageType> = DECORATE_PAGES.reduce(
    (acc, page) => {
        acc[String(page.id)] = page.key as PageType
        return acc
    },
    {} as Record<string, PageType>
)

// 可用的组件列表
//
// ⚠ 不再手写清单：只暴露 `shared/contracts/decorate.ts` 里真正被某个页面引用的组件。
const availableWidgets = Array.from(new Set(DECORATE_PAGES.flatMap((page) => page.widgets)))
    .map((name) => {
        const options = widgets[name]?.options?.()
        return { name, title: options?.title || name, icon: Monitor }
    })
    .filter((item) => !!widgets[item.name])

const showWidgetSelector = ref(false)
const showPopup = ref(false)
const replayKey = ref(0)

const props = defineProps({
    pageMeta: { type: Object as any, default: () => null },
    pageData: { type: Array as PropType<any[]>, default: () => [] },
    modelValue: { type: Number, default: 0 },
    activeMenu: { type: String, default: '1' }
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void
    (event: 'updatePageData', value: any[]): void
}>()

const oldModelValue = ref<number>(-1)

const currentPageType = computed<PageType>(() => pageTypeMap[props.activeMenu] || 'home')

const currentPageName = computed(
    () => DECORATE_PAGES.find((p) => String(p.id) === props.activeMenu)?.name || '装修页'
)

const isWidgetStructureLocked = computed(() =>
    ['home', 'user', 'service', 'splash', 'login'].includes(currentPageType.value)
)
const canToggleVisibility = computed(
    () => currentPageType.value === 'user' || !isWidgetStructureLocked.value
)

const filteredAvailableWidgets = computed(() => {
    const currentType = currentPageType.value
    return availableWidgets.filter((widget) => {
        const pageScope = widgets[widget.name]?.options?.()?.pageScope
        if (!pageScope || !Array.isArray(pageScope)) return true
        return pageScope.includes(currentType)
    })
})

const showPageMetaButton = computed(
    () => props.pageMeta !== null && !['home', 'user'].includes(currentPageType.value)
)

const firstEnabledWidgetIndex = computed(() =>
    props.pageData.findIndex((item: any) => !item?.disabled)
)

const effectiveModelValue = computed(() => {
    if (props.modelValue !== -1 || showPageMetaButton.value) return props.modelValue
    return firstEnabledWidgetIndex.value
})

/* ---------------- 预览用到的站点信息 ---------------- */

/** 文件域名：装修图片大多是相对路径，预览里要补成可访问地址 */
const domain = String(import.meta.env.VITE_APP_BASE_URL || '')

/** 首屏品牌位与页脚用的站点名称/图标/Slogan（来自后台「网站信息」，不写死品牌名） */
const brand = ref({ name: '站点名称', logo: '', slogan: '' })

onMounted(async () => {
    try {
        const data: any = await getWebsite()
        brand.value = {
            name: String(data?.shop_name || '').trim() || '站点名称',
            logo: String(data?.shop_logo || '').trim(),
            slogan: String(data?.shop_slogan || '').trim()
        }
    } catch {
        // 没有权限或接口失败时用中性占位，不影响预览其它部分
    }
})

/* ---------------- 预览缩放：默认 1:1，可切「适应」 ---------------- */
const FRAME_WIDTH = 375
const FRAME_HEIGHT = 812
/** 工具栏 + 说明文字 + 画布内边距占位（px） */
const CANVAS_RESERVED = 96

const canvasRef = ref<HTMLElement | null>(null)
/** 0 = 适应画布高度；其余为固定缩放比 */
const zoom = ref(1)
const fitScale = ref(0.78)
const scale = computed(() => (zoom.value > 0 ? zoom.value : fitScale.value))

function updateFitScale() {
    const el = canvasRef.value
    if (!el) return
    const availableHeight = el.clientHeight - CANVAS_RESERVED
    const availableWidth = el.clientWidth - 24
    if (availableHeight <= 0 || availableWidth <= 0) return
    const next = Math.min(availableHeight / FRAME_HEIGHT, availableWidth / FRAME_WIDTH, 1)
    // 下限保底：再小的屏也不把手机壳缩成邮票，宁可让它滚动
    fitScale.value = Number(Math.max(0.6, next).toFixed(3))
}

let canvasResizeObserver: ResizeObserver | null = null

onMounted(() => {
    updateFitScale()
    if (canvasRef.value && typeof ResizeObserver !== 'undefined') {
        canvasResizeObserver = new ResizeObserver(updateFitScale)
        canvasResizeObserver.observe(canvasRef.value)
    } else {
        window.addEventListener('resize', updateFitScale)
    }
})

onBeforeUnmount(() => {
    canvasResizeObserver?.disconnect()
    canvasResizeObserver = null
    window.removeEventListener('resize', updateFitScale)
})

/* ---------------- 弹窗广告内容 ---------------- */
function widgetContent(name: string): any {
    const hit = props.pageData.find((w: any) => w?.name === name)
    return hit?.content || {}
}

const popupContent = computed(() => widgetContent('home-popup-ad'))
const popupImage = computed(() => {
    const url = String(popupContent.value?.image || '').trim()
    if (!url) return ''
    if (/^https?:/i.test(url)) return url
    return domain ? `${domain.replace(/\/+$/, '')}/${url.replace(/^\/+/, '')}` : url
})

const splashContent = computed(() => widgetContent('splash-ad'))
const serviceContent = computed(() => widgetContent('customer-service'))
const loginContent = computed(() => widgetContent('login-hero'))

/* ---------------- 编辑操作（与改造前完全一致） ---------------- */

const handleClickPageMeta = () => {
    if (!showPageMetaButton.value) return
    if (props.modelValue === -1) {
        emit('update:modelValue', oldModelValue.value)
    } else {
        oldModelValue.value = props.modelValue
        emit('update:modelValue', -1)
    }
}

const handleClick = (widget: any, index: number) => {
    if (widget.disabled) return
    emit('update:modelValue', index)
}

const canMoveUpCom = computed(() => (index: number) => index === 0)
const canMoveDownCom = computed(() => (index: number) => props.pageData?.length === index + 1)

const canToggleWidgetVisibility = (widget: any) =>
    canToggleVisibility.value && !widget?.disabled && widget?.content?.enabled !== undefined

const showWidgetActions = (index: number) => {
    if (index !== effectiveModelValue.value) return false
    return canToggleVisibility.value || !isWidgetStructureLocked.value
}

const changeShowCom = (data: any) => {
    if (!canToggleVisibility.value) return
    if (data.enabled === undefined) return
    data.enabled = data.enabled ? 0 : 1
}

const rearrangeArray = (currentIdx: number, targetIdx: number) => {
    if (isWidgetStructureLocked.value) return
    if (
        currentIdx < 0 ||
        currentIdx >= props.pageData.length ||
        targetIdx < 0 ||
        targetIdx >= props.pageData.length
    ) {
        return
    }

    const newPageData = cloneDeep(props.pageData)
    const element = newPageData.splice(currentIdx, 1)[0]
    newPageData.splice(targetIdx, 0, element)

    emit('updatePageData', newPageData)
    emit('update:modelValue', targetIdx)
}

const isWidgetAdded = (widgetName: string) =>
    props.pageData.some((item: any) => item.name === widgetName)

const handleAddWidget = (widgetInfo: any) => {
    if (isWidgetStructureLocked.value) return
    if (isWidgetAdded(widgetInfo.name)) return

    const widgetOptions = widgets[widgetInfo.name]?.options?.()
    if (!widgetOptions) {
        console.error('组件 options 缺失:', widgetInfo.name)
        return
    }

    const newPageData = cloneDeep(props.pageData)
    newPageData.push({ id: getNonDuplicateID(), ...widgetOptions })

    emit('updatePageData', newPageData)
    emit('update:modelValue', newPageData.length - 1)
    showWidgetSelector.value = false
}

const handleDeleteWidget = (index: number) => {
    if (isWidgetStructureLocked.value) return
    const newPageData = cloneDeep(props.pageData)
    newPageData.splice(index, 1)

    emit('updatePageData', newPageData)
    if (props.modelValue >= newPageData.length) {
        emit('update:modelValue', Math.max(0, newPageData.length - 1))
    } else if (props.modelValue === index) {
        emit('update:modelValue', Math.max(0, index - 1))
    }
}
</script>

<style lang="scss" scoped>
.decorate-preview {
    display: flex;
    flex-direction: column;
    height: 100%;

    &__toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 8px;
    }

    &__status {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
    }

    &__hint {
        font-size: 11px;
        color: var(--el-text-color-secondary);
    }

    &__body {
        display: flex;
        flex: 1;
        min-height: 0;
        gap: 12px;
    }
}

/* ---------------- 左侧页面结构 ---------------- */
.widget-tree {
    flex: none;
    width: 200px;
    padding: 8px;
    overflow-y: auto;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
    background: var(--el-bg-color);

    &__title {
        padding: 4px 8px 8px;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    &__row {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s;

        &:hover {
            background: var(--el-fill-color-light);
        }

        &.is-active {
            background: var(--el-color-primary-light-9);
            outline: 1px solid var(--el-color-primary-light-5);
        }

        &.is-disabled {
            cursor: default;
            opacity: 0.6;
        }

        &.is-hidden .widget-tree__name {
            text-decoration: line-through;
            color: var(--el-text-color-secondary);
        }
    }

    &__index {
        width: 18px;
        font-size: 11px;
        color: var(--el-text-color-secondary);
        font-variant-numeric: tabular-nums;
    }

    &__name {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 13px;
    }

    &__badge {
        flex: none;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--el-fill-color);
        font-size: 11px;
        color: var(--el-text-color-secondary);
    }

    &__ops {
        display: flex;
        flex: none;
        gap: 2px;

        :deep(.el-button) {
            padding: 4px;
            height: 22px;
        }
    }

    &__empty {
        padding: 24px 8px;
        text-align: center;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }
}

/* ---------------- 右侧手机壳 ---------------- */
.canvas {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    overflow: auto;
    padding: 8px;
    border-radius: 12px;
    background: repeating-linear-gradient(
        45deg,
        var(--el-fill-color-lighter) 0,
        var(--el-fill-color-lighter) 10px,
        var(--el-bg-color) 10px,
        var(--el-bg-color) 20px
    );

    &__toolbar {
        flex: none;
        display: flex;
        justify-content: center;
        padding: 4px 0 10px;
    }

    &__frame {
        position: relative;
        flex: none;
        width: 375px;
        height: 812px;
        /* 缩放比由脚本按画布高度算出（0=适应）；zoom 会同时改变布局尺寸，
           不需要再用负 margin 抵消高度差 */
        border-radius: 28px;
        overflow: hidden;
        background: #14110f;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
    }

    &__caption {
        margin: 8px 0 0;
        font-size: 11px;
        color: var(--el-text-color-secondary);
    }

    &__pending {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 32px;
        text-align: center;
        background: #faf6ee;
        color: var(--el-text-color-secondary);
    }

    &__pending-title {
        margin: 10px 0 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-regular);
    }

    &__pending-desc {
        margin: 0;
        font-size: 12px;
        line-height: 1.7;
    }
}

/* ---------------- 弹窗预览（覆盖在手机壳内） ---------------- */
.pv-popup-mask {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 32px;
    background: rgba(24, 22, 20, 0.56);
}

.pv-popup {
    position: relative;
    width: 100%;
    max-width: 300px;
    border-radius: 20px;
    overflow: hidden;
    background: #fffdf8;
    box-shadow: 0 12px 32px rgba(24, 22, 20, 0.28);

    &__img {
        display: block;
        width: 100%;
    }

    &__body {
        padding: 16px 16px 0;
    }

    &__title {
        margin: 0 0 6px;
        font-size: 16px;
        font-weight: 600;
        color: #181614;
    }

    &__text {
        margin: 0;
        font-size: 14px;
        line-height: 1.55;
        color: #5a554e;
    }

    &__actions {
        display: flex;
        gap: 8px;
        padding: 16px;
    }

    &__ghost {
        flex: 1;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #f3ede1;
        font-size: 14px;
        color: #5a554e;
    }

    &__cta {
        flex: 2;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #181614;
        font-size: 14px;
        font-weight: 600;
        color: #fff;
    }

    &__close {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(24, 22, 20, 0.36);
        color: #fff;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
    }
}
</style>
