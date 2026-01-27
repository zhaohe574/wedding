<template>
    <div class="pages-preview-wrapper">
        <!-- 顶部操作按钮 -->
        <div class="flex justify-center gap-2">
            <el-button v-if="pageMeta !== null" @click="handleClickPageMeta">页面设置</el-button>
            <el-button type="primary" @click="showWidgetSelector = true">添加组件</el-button>
        </div>
        <el-scrollbar class="pages-preview-container">
            <div class="pages-preview-content">
                <div class="shadow pages-preview">
            <div
                v-for="(widget, index) in pageData"
                :key="widget.id"
                class="relative"
                :class="{
                    'cursor-pointer': !widget?.disabled
                }"
                @click="handleClick(widget, index)"
            >
                <!--  选中的边框  -->
                <div
                    class="absolute w-full h-full z-[100] border-dashed"
                    :class="{
                        select: index == modelValue,
                        hide: canShowCom(widget.content),
                        'border-[#dcdfe6] border-2': !widget?.disabled
                    }"
                ></div>
                <!--  选中的组件  -->
                <slot>
                    <component
                        :is="widgets[widget?.name]?.content"
                        :content="widget.content"
                        :styles="widget.styles"
                        :key="widget.id"
                    />
                </slot>
                <!--  部件操作按钮组  -->
                <div class="widget-btns py-[5px]" v-if="index == modelValue">
                    <div>
                        <el-tooltip
                            effect="dark"
                            :content="canShowCom(widget.content) ? '显示' : '隐藏'"
                            placement="right"
                        >
                            <el-button
                                class="py-[5px]"
                                type="primary"
                                :icon="canShowCom(widget.content) ? View : Hide"
                                @click="changeShowCom(widget.content)"
                            />
                        </el-tooltip>
                    </div>
                    <div>
                        <el-tooltip effect="dark" content="上移" placement="right">
                            <el-button
                                class="py-[5px]"
                                type="primary"
                                :icon="ArrowUpBold"
                                :disabled="canMoveUpCom(index)"
                                @click.stop="rearrangeArray(index, index - 1)"
                            />
                        </el-tooltip>
                    </div>
                    <div>
                        <el-tooltip effect="dark" content="下移" placement="right">
                            <el-button
                                class="py-[5px]"
                                type="primary"
                                :icon="ArrowDownBold"
                                :disabled="canMoveDownCom(index)"
                                @click.stop="rearrangeArray(index, index + 1)"
                            />
                        </el-tooltip>
                    </div>
                    <div>
                        <el-tooltip effect="dark" content="删除组件" placement="right">
                            <el-button
                                class="py-[5px]"
                                type="danger"
                                :icon="Delete"
                                @click.stop="handleDeleteWidget(index)"
                            />
                        </el-tooltip>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </el-scrollbar>
    </div>

    <!-- 组件选择器弹窗 -->
    <el-dialog v-model="showWidgetSelector" title="添加组件" width="600px">
        <div v-if="filteredAvailableWidgets.length > 0" class="grid grid-cols-3 gap-4">
            <div
                v-for="item in filteredAvailableWidgets"
                :key="item.name"
                class="p-4 border rounded-lg cursor-pointer hover:border-primary hover:bg-blue-50 transition-colors"
                :class="{ 'border-primary bg-blue-50': isWidgetAdded(item.name), 'opacity-50': isWidgetAdded(item.name) }"
                @click="handleAddWidget(item)"
            >
                <div class="text-center">
                    <div class="w-12 h-12 mx-auto mb-2 bg-gray-100 rounded-lg flex items-center justify-center">
                        <el-icon class="text-xl text-gray-500">
                            <component :is="item.icon" />
                        </el-icon>
                    </div>
                    <div class="text-sm font-medium">{{ item.title }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ isWidgetAdded(item.name) ? '已添加' : '点击添加' }}</div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-8 text-gray-400">
            暂无可添加的组件
        </div>
        <template #footer>
            <el-button @click="showWidgetSelector = false">关闭</el-button>
        </template>
    </el-dialog>
</template>
<script lang="ts" setup>
import { ArrowDownBold, ArrowUpBold, Hide, View, Picture, Document, Star, User, ShoppingCart, Ticket, Calendar, Delete } from '@element-plus/icons-vue'
import { cloneDeep } from 'lodash-es'
import type { PropType } from 'vue'
import { computed } from 'vue'

import { getNonDuplicateID } from '@/utils/util'
import widgets from '../widgets'

// 页面类型定义
type PageType = 'home' | 'user' | 'service'

// 页面类型映射
const pageTypeMap: Record<string, PageType> = {
    '1': 'home',    // 首页
    '2': 'user',    // 个人中心
    '3': 'service'  // 客服设置
}

// 可用的组件列表
const availableWidgets = [
    { name: 'search', title: '搜索框', icon: 'Search' },
    { name: 'banner', title: '轮播图', icon: Picture },
    { name: 'nav', title: '导航菜单', icon: 'Grid' },
    { name: 'middle-banner', title: '中部轮播图', icon: Picture },
    { name: 'staff-showcase', title: '人员推荐', icon: User },
    { name: 'service-packages', title: '服务套餐', icon: ShoppingCart },
    { name: 'portfolio-gallery', title: '案例作品', icon: Picture },
    { name: 'customer-reviews', title: '客户评价', icon: Star },
    { name: 'activity-zone', title: '活动专区', icon: Ticket },
    { name: 'order-quick-entry', title: '订单快捷入口', icon: Document },
    { name: 'news', title: '最新资讯', icon: Document },

    { name: 'quick-entry', title: '快捷入口', icon: Document },
    { name: 'coupon-receive', title: '优惠券领取', icon: Ticket },
    { name: 'data-stats', title: '数据统计', icon: Document },
    { name: 'faq', title: '常见问题', icon: Document },
    { name: 'service-process', title: '服务流程', icon: Document },
    { name: 'notice-bar', title: '公告通知', icon: Document },
    { name: 'hot-topics', title: '热门话题', icon: Document },
    { name: 'store-map', title: '门店地图', icon: Document },
    { name: 'wedding-countdown', title: '婚礼倒计时', icon: Calendar },
]

const showWidgetSelector = ref(false)

const props = defineProps({
    pageMeta: {
        type: Object as any,
        default: () => null
    },
    pageData: {
        type: Array as PropType<any[]>,
        default: () => []
    },
    modelValue: {
        type: Number,
        default: 0
    },
    activeMenu: {
        type: String,
        default: '1'
    }
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void
    (event: 'updatePageData', value: any[]): void
}>()

const oldModelValue = ref<number>(-1)

// 获取当前页面类型
const currentPageType = computed<PageType>(() => {
    const pageType = pageTypeMap[props.activeMenu]
    
    if (!pageType) {
        console.warn('未知的页面类型:', props.activeMenu, '使用默认值: home')
        return 'home' // fallback
    }
    
    return pageType
})

// 过滤可用组件
const filteredAvailableWidgets = computed(() => {
    const currentType = currentPageType.value
    
    return availableWidgets.filter(widget => {
        const widgetConfig = widgets[widget.name]
        const widgetOptions = widgetConfig?.options?.()
        const pageScope = widgetOptions?.pageScope
        
        // 如果未定义pageScope，视为通用组件，在所有页面显示
        if (!pageScope || !Array.isArray(pageScope)) {
            return true
        }
        
        // 检查当前页面类型是否在pageScope中
        return pageScope.includes(currentType)
    })
})

const handleClickPageMeta = () => {
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

// 是否可以移动组件
const canMoveUpCom = computed(() => {
    return (index: number) => {
        return index === 0
    }
})

// 是否可以移动组件
const canMoveDownCom = computed(() => {
    return (index: number) => {
        return props.pageData?.length === index + 1
    }
})

// 是否显示组件
const canShowCom = computed(() => {
    return (data: any) => {
        return data?.enabled == 0
    }
})

// 修改组件显示/隐藏
const changeShowCom = (data: any) => {
    if (data.enabled === undefined) return
    data.enabled = data.enabled ? 0 : 1
}

const rearrangeArray = (currentIdx: number, targetIdx: number) => {
    if (
        currentIdx < 0 ||
        currentIdx >= props.pageData.length ||
        targetIdx < 0 ||
        targetIdx >= props.pageData.length
    ) {
        return
    }

    // const element = props.pageData.splice(currentIdx, 1)[0]
    // props.pageData.splice(targetIdx, 0, element)
    const newPageData = cloneDeep(props.pageData)
    const element = newPageData.splice(currentIdx, 1)[0]
    newPageData.splice(targetIdx, 0, element)

    emit('updatePageData', newPageData)
    emit('update:modelValue', targetIdx)
}

// 判断组件是否已添加
const isWidgetAdded = (widgetName: string) => {
    return props.pageData.some((item: any) => item.name === widgetName)
}

// 添加组件
const handleAddWidget = (widgetInfo: any) => {
    if (isWidgetAdded(widgetInfo.name)) {
        return // 已添加的组件不能重复添加
    }
    
    const widgetConfig = widgets[widgetInfo.name]
    
    if (!widgetConfig) {
        console.error('组件配置不存在:', widgetInfo.name)
        return
    }
    
    const widgetOptions = widgetConfig.options?.()
    
    if (!widgetOptions) {
        console.error('组件options方法不存在:', widgetInfo.name)
        return
    }
    
    const newWidget = {
        id: getNonDuplicateID(),
        ...widgetOptions
    }
    
    const newPageData = cloneDeep(props.pageData)
    newPageData.push(newWidget)
    
    emit('updatePageData', newPageData)
    emit('update:modelValue', newPageData.length - 1)
    showWidgetSelector.value = false
}

// 删除组件
const handleDeleteWidget = (index: number) => {
    const newPageData = cloneDeep(props.pageData)
    newPageData.splice(index, 1)
    
    emit('updatePageData', newPageData)
    // 如果删除的是当前选中的组件，选中前一个或第一个
    if (props.modelValue >= newPageData.length) {
        emit('update:modelValue', Math.max(0, newPageData.length - 1))
    } else if (props.modelValue === index) {
        emit('update:modelValue', Math.max(0, index - 1))
    }
}
</script>

<style lang="scss" scoped>
.pages-preview-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.pages-preview-container {
    flex: 1;
    overflow: hidden;
    
    :deep(.el-scrollbar__wrap) {
        width: 100%;
    }
    
    :deep(.el-scrollbar__view) {
        display: flex;
        justify-content: center;
    }
}

.pages-preview-content {
    padding: 0 80px 10px 20px; // 减少内边距
}

.pages-preview {
    background-color: #f8f8f8;
    width: 360px;
    min-height: 500px; // 从 615px 减少到 500px
    color: #333;

    .select {
        @apply border-primary border-solid;
    }

    .hide::before {
        content: '已隐藏';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 14px;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .widget-btns {
        position: absolute;
        top: 10px;
        right: -60px;
        z-index: 200;

        width: 46px;
        border-radius: 8px;
        @apply bg-primary;

        :deep(.el-button) {
            width: 46px;
            border-radius: 0;
        }
    }
}
</style>
