<template>
    <div class="decoration-pc-details min-w-[1200px]">
        <el-card shadow="never" class="!border-none !rounded-none !bg-primary">
            <div class="flex items-center justify-between w-full">
                <el-button link type="primary" :icon="ArrowLeft" style="color: #fff" @click="handleBack">
                    返回
                </el-button>
                <div class="decoration-pc-details__title">
                    <strong>PC 企业展示首页装修</strong>
                    <span>固定 6 个展示模块，保存后同步前台 `/pc`</span>
                </div>
                <el-button v-perms="['decorate.page/save']" :loading="saving" @click="setData">保存装修</el-button>
            </div>
        </el-card>
        <div class="decoration-pc-details__body">
            <aside class="decoration-pc-details__modules">
                <el-card shadow="never" class="!border-none">
                    <div class="mb-4 text-base font-medium text-[#101010]">展示模块</div>
                    <div class="space-y-2">
                        <button
                            v-for="(widget, index) in getPageData"
                            :key="widget.id"
                            class="decoration-pc-details__module"
                            :class="{ 'is-active': index === selectWidgetIndex }"
                            type="button"
                            @click="selectWidgetIndex = index"
                        >
                            <span>
                                <strong>{{ widget.title }}</strong>
                                <small>{{ widgetDescriptions[widget.name] }}</small>
                            </span>
                            <em :class="{ 'is-hidden': widget.content?.enabled === 0 }">
                                {{ widget.content?.enabled === 0 ? '已隐藏' : '显示中' }}
                            </em>
                        </button>
                    </div>
                </el-card>
            </aside>

            <preview-pc class="decoration-pc-details__preview" v-model="selectWidgetIndex" :pageData="getPageData" />

            <attr-setting
                class="decoration-pc-details__setting"
                :widget="getSelectWidget"
                type="pc"
                @update:content="updateContent"
            />
        </div>
    </div>
</template>
<script lang="ts" setup name="decorationPc">
import { ArrowLeft } from '@element-plus/icons-vue'

import { getDecoratePages, setDecoratePages } from '@/api/decoration'
import { getNonDuplicateID } from '@/utils/util'
import feedback from '@/utils/feedback'

import AttrSetting from './component/pages/attr-setting.vue'
import PreviewPc from './component/pages/preview-pc.vue'
import widgets from './component/widgets'

const router = useRouter()

enum pagesTypeEnum {
    HOME = '4'
}

const PC_WIDGET_NAMES = ['pc-hero', 'pc-about', 'pc-advantages', 'pc-gallery', 'pc-stats', 'pc-contact']

const isNumericKeyObject = (value: any) => {
    if (!value || Array.isArray(value) || typeof value !== 'object') {
        return false
    }

    const keys = Object.keys(value)
    return keys.length > 0 && keys.every((key) => /^\d+$/.test(key))
}

const normalizeListLikeValue = (value: any) => {
    if (Array.isArray(value)) {
        return value
    }

    if (isNumericKeyObject(value)) {
        return Object.values(value)
    }

    return value
}

const normalizePageWidgets = (rawData: any) => {
    const parsedData = typeof rawData === 'string' ? JSON.parse(rawData || '[]') : rawData
    const normalizedData = normalizeListLikeValue(parsedData)
    return Array.isArray(normalizedData) ? normalizedData : []
}

const buildWidgetOptions = (widgetName: string, rawWidget: any = null) => {
    const defaultOptions = widgets[widgetName]?.options?.() || {}
    const widget = rawWidget || {}

    return {
        id: widget?.id || getNonDuplicateID(),
        ...defaultOptions,
        ...widget,
        content: {
            ...(defaultOptions.content || {}),
            ...(widget.content || {})
        },
        styles: {
            ...(defaultOptions.styles || {}),
            ...(widget.styles || {})
        }
    }
}

const generatePageData = () => PC_WIDGET_NAMES.map((widgetName) => buildWidgetOptions(widgetName))

const normalizeLoadedPageData = (rawData: any) => {
    return normalizePageWidgets(rawData)
        .filter((item: any) => PC_WIDGET_NAMES.includes(item?.name))
        .map((item: any) => {
            if (item?.content && 'data' in item.content) {
                item.content.data = normalizeListLikeValue(item.content.data)
            }
            if (item.content && !('enabled' in item.content)) {
                item.content.enabled = 1
            }
            return buildWidgetOptions(item.name, item)
        })
}

const ensurePcFixedWidgets = (pageData: any[]) => {
    const widgetMap = new Map<string, any>()
    pageData.forEach((item: any) => {
        const widgetName = item?.name
        if (!PC_WIDGET_NAMES.includes(widgetName) || widgetMap.has(widgetName)) {
            return
        }
        widgetMap.set(widgetName, item)
    })

    return PC_WIDGET_NAMES.map((widgetName) => buildWidgetOptions(widgetName, widgetMap.get(widgetName)))
}

const menus: Record<
    string,
    {
        id: number
        type: number
        name: string
        pageData: any[]
    }
> = reactive({
    [pagesTypeEnum.HOME]: {
        id: 4,
        type: 4,
        name: 'PC设置',
        pageData: generatePageData()
    }
})

const activeMenu = ref('4')
const selectWidgetIndex = ref(0)
const saving = ref(false)
const widgetDescriptions: Record<string, string> = {
    'pc-hero': '品牌首屏与行动按钮',
    'pc-about': '品牌定位与服务理念',
    'pc-advantages': '核心服务能力',
    'pc-gallery': '案例现场图集',
    'pc-stats': '服务数据背书',
    'pc-contact': '电话地址与二维码'
}
const getPageData = computed(() => {
    return menus[activeMenu.value]?.pageData ?? []
})
const getSelectWidget = computed(() => {
    return getPageData.value[selectWidgetIndex.value] ?? getPageData.value[0] ?? {}
})

const handleBack = async () => {
    await feedback.confirm('确定离开此页面？系统可能不会保存您所做的更改。')
    router.back()
}

const updateContent = (content: any) => {
    const activeWidgetIndex = selectWidgetIndex.value
    if (activeWidgetIndex < 0 || !menus[activeMenu.value]?.pageData?.[activeWidgetIndex]) {
        return
    }
    menus[activeMenu.value].pageData[activeWidgetIndex].content = content
}

const getData = async () => {
    const data = await getDecoratePages({ id: activeMenu.value })
    menus[String(data.id)].pageData = ensurePcFixedWidgets(normalizeLoadedPageData(data.data))
    selectWidgetIndex.value = getPageData.value.findIndex((item) => !item.disabled)
    if (selectWidgetIndex.value < 0) {
        selectWidgetIndex.value = 0
    }
}

const setData = async () => {
    if (saving.value) return
    saving.value = true
    const pageData = ensurePcFixedWidgets(menus[activeMenu.value].pageData)
    try {
        await setDecoratePages({
            ...menus[activeMenu.value],
            data: JSON.stringify(pageData)
        })
        feedback.msgSuccess('PC 首页装修已保存')
        await getData()
    } finally {
        saving.value = false
    }
}
watch(
    activeMenu,
    () => {
        selectWidgetIndex.value = getPageData.value.findIndex((item) => !item.disabled)
        getData()
    },
    {
        immediate: true
    }
)
</script>
<style lang="scss" scoped>
.decoration-pc-details {
    min-height: calc(100vh);
    @apply flex flex-col bg-page;

    &__title {
        display: grid;
        gap: 4px;
        text-align: center;

        strong {
            color: #fff;
            font-size: 16px;
            font-weight: 800;
        }

        span {
            color: rgba(255, 255, 255, 0.72);
            font-size: 12px;
        }
    }

    &__body {
        height: calc(100vh - var(--navbar-height) - 64px);
        min-height: 720px;
        display: grid;
        grid-template-columns: 246px minmax(0, 1fr) 430px;
        gap: 14px;
        padding: 14px;
        box-sizing: border-box;
    }

    &__modules,
    &__setting,
    &__preview {
        min-height: 0;
    }

    &__module {
        width: 100%;
        min-height: 64px;
        padding: 0 14px;
        border: 1px solid var(--el-border-color-extra-light);
        border-radius: 6px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        color: #101010;
        text-align: left;

        &.is-active {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
            color: var(--el-color-primary);
        }

        span {
            display: grid;
            gap: 5px;
        }

        strong {
            font-size: 14px;
            font-weight: 600;
        }

        small {
            color: var(--el-text-color-secondary);
            font-size: 12px;
            font-weight: 400;
        }

        em {
            font-style: normal;
            font-size: 12px;
            color: var(--el-text-color-secondary);

            &.is-hidden {
                color: var(--el-color-danger);
            }
        }
    }

    &__setting {
        overflow: hidden;
    }
}
</style>
