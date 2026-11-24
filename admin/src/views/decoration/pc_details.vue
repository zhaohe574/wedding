<template>
    <div class="decoration-pc-details min-w-[1200px]">
        <el-card shadow="never" class="decoration-pc-details__topbar !border-none !rounded-none">
            <div class="flex items-center justify-between w-full">
                <el-button link :icon="ArrowLeft" class="decoration-pc-details__back" @click="handleBack">
                    返回
                </el-button>
                <div class="decoration-pc-details__title">
                    <strong>PC 官网展示装修</strong>
                    <span>3 个精简模块 · 暖墨黑高定影院风，自动复用系统 Logo、备案与小程序码</span>
                </div>
                <div class="decoration-pc-details__actions">
                    <el-button class="decoration-pc-details__ghost" :disabled="!pcInfo.pc_url" @click="openFrontPage">
                        查看前台
                    </el-button>
                    <el-button
                        v-perms="['decorate.page/save']"
                        class="decoration-pc-details__save"
                        :loading="saving"
                        @click="savePage"
                    >
                        保存装修
                    </el-button>
                </div>
            </div>
        </el-card>
        <div class="decoration-pc-details__body">
            <aside class="decoration-pc-details__modules">
                <el-card shadow="never" class="!border-none">
                    <div class="mb-4 text-base font-medium text-[#101010]">展示模块</div>
                    <div class="space-y-2">
                        <button
                            v-for="(widget, index) in pageData"
                            :key="widget.name"
                            class="decoration-pc-details__module"
                            :class="{ 'is-active': index === selectWidgetIndex }"
                            type="button"
                            @click="selectWidgetIndex = index"
                        >
                            <span>
                                <strong>
                                    <em class="decoration-pc-details__no">{{ padIndex(index) }}</em>
                                    {{ PC_WIDGET_META[widget.name].title }}
                                </strong>
                                <small>{{ PC_WIDGET_META[widget.name].description }}</small>
                            </span>
                            <em :class="{ 'is-hidden': widget.content.enabled === 0 }">
                                {{ widget.content.enabled === 0 ? '已隐藏' : '显示中' }}
                            </em>
                        </button>
                    </div>
                    <div class="decoration-pc-details__tips">
                        模块顺序固定，与前台 /pc 三屏结构一一对应；系统 Logo、备案与小程序码自动复用
                    </div>
                </el-card>
            </aside>

            <pc-preview
                class="decoration-pc-details__preview"
                v-model="selectWidgetIndex"
                :page-data="pageData"
                :pc-url="pcInfo.pc_url"
                :fallback-mnp-qr-code="pcInfo.mnp_qr_code"
                :site-logo="pcInfo.pc_logo"
                :brand-name="pcInfo.brand_name"
                @toggle-visible="toggleVisible"
            />

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

import {
    PC_DECORATE_PAGE_ID,
    PC_DECORATE_PAGE_TYPE,
    PC_WIDGET_META,
    parsePcDecorateWidgets,
    type PcDecorateWidget
} from '@shared/contracts/pc-decorate'

import { getDecoratePc, getDecoratePages, setDecoratePages } from '@/api/decoration'
import feedback from '@/utils/feedback'

import AttrSetting from './component/pages/attr-setting.vue'
import PcPreview from './component/preview/pc-preview.vue'

interface PcInfo {
    update_time: string
    pc_url: string
    mnp_qr_code: string
    pc_logo: string
    brand_name: string
}

const router = useRouter()

const createEmptyPcInfo = (): PcInfo => ({
    update_time: '',
    pc_url: '',
    mnp_qr_code: '',
    pc_logo: '',
    brand_name: ''
})

const pageData = ref<PcDecorateWidget[]>(parsePcDecorateWidgets([]))
const pcInfo = ref<PcInfo>(createEmptyPcInfo())
const selectWidgetIndex = ref(0)
const saving = ref(false)

const getSelectWidget = computed(() => {
    return pageData.value[selectWidgetIndex.value] ?? pageData.value[0] ?? {}
})

const padIndex = (index: number) => String(index + 1).padStart(2, '0')

const handleBack = async () => {
    await feedback.confirm('确定离开此页面？系统可能不会保存您所做的更改。')
    router.back()
}

const openFrontPage = () => {
    if (!pcInfo.value.pc_url) return
    window.open(pcInfo.value.pc_url, '_blank')
}

const updateContent = (content: any) => {
    const widget = pageData.value[selectWidgetIndex.value]
    if (widget) {
        widget.content = content
    }
}

/** 预览右上角快捷开关：切 content.enabled 1/0 */
const toggleVisible = (index: number) => {
    const widget = pageData.value[index]
    if (!widget) return
    widget.content.enabled = widget.content.enabled === 0 ? 1 : 0
}

/** 并行加载装修数据与 PC 端信息；模块列表始终经 parsePcDecorateWidgets 归一化 */
const loadPage = async () => {
    const [pageRes, pcRes] = await Promise.all([
        getDecoratePages({ id: String(PC_DECORATE_PAGE_ID) }),
        getDecoratePc()
    ])
    pageData.value = parsePcDecorateWidgets(pageRes?.data)
    pcInfo.value = {
        update_time: String(pcRes?.update_time ?? ''),
        pc_url: String(pcRes?.pc_url ?? ''),
        mnp_qr_code: String(pcRes?.mnp_qr_code ?? ''),
        pc_logo: String(pcRes?.pc_logo ?? ''),
        brand_name: String(pcRes?.brand_name ?? '')
    }
}

const savePage = async () => {
    if (saving.value) return
    saving.value = true
    try {
        // 保存前再过一遍契约归一化，杜绝编辑态遗留的旧字段写回数据库
        const normalized = parsePcDecorateWidgets(pageData.value)
        await setDecoratePages({
            id: PC_DECORATE_PAGE_ID,
            type: PC_DECORATE_PAGE_TYPE,
            name: 'PC设置',
            data: JSON.stringify(normalized)
        })
        feedback.msgSuccess('PC 官网装修已保存')
        await loadPage()
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    loadPage()
})
</script>
<style lang="scss" scoped>
.decoration-pc-details {
    min-height: calc(100vh);
    @apply flex flex-col bg-page;

    &__topbar {
        background: linear-gradient(120deg, #211d19 0%, #181614 100%);
        box-shadow: inset 0 -1px 0 rgba(201, 169, 110, 0.28);
    }

    &__back {
        --el-button-text-color: #dfd0b0;
        --el-button-hover-text-color: #efe5d3;
    }

    &__title {
        display: grid;
        gap: 4px;
        text-align: center;

        strong {
            color: #efe5d3;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        span {
            color: rgba(239, 229, 211, 0.66);
            font-size: 12px;
        }
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    &__ghost {
        --el-button-bg-color: transparent;
        --el-button-border-color: rgba(201, 169, 110, 0.45);
        --el-button-text-color: #dfd0b0;
        --el-button-hover-bg-color: rgba(201, 169, 110, 0.12);
        --el-button-hover-border-color: rgba(201, 169, 110, 0.7);
        --el-button-hover-text-color: #efe5d3;
        --el-button-active-bg-color: rgba(201, 169, 110, 0.2);
        --el-button-disabled-bg-color: transparent;
        --el-button-disabled-border-color: rgba(201, 169, 110, 0.25);
        --el-button-disabled-text-color: rgba(223, 208, 176, 0.4);
    }

    &__save {
        --el-button-bg-color: #c9a96e;
        --el-button-border-color: #c9a96e;
        --el-button-text-color: #181614;
        --el-button-hover-bg-color: #d8b980;
        --el-button-hover-border-color: #d8b980;
        --el-button-hover-text-color: #181614;
        --el-button-active-bg-color: #b8965e;
        --el-button-active-border-color: #b8965e;
        --el-button-active-text-color: #181614;
        --el-button-disabled-bg-color: #c9a96e;
        --el-button-disabled-border-color: #c9a96e;
        --el-button-disabled-text-color: rgba(24, 22, 20, 0.55);
        font-weight: 700;
    }

    &__body {
        height: calc(100vh - var(--navbar-height) - 64px);
        min-height: 720px;
        display: grid;
        grid-template-columns: 260px minmax(0, 1fr) 430px;
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
        min-height: 68px;
        padding: 0 14px;
        border: 1px solid var(--el-border-color-extra-light);
        border-radius: 6px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
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
            min-width: 0;
        }

        strong {
            display: flex;
            align-items: baseline;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        small {
            color: var(--el-text-color-secondary);
            font-size: 12px;
            font-weight: 400;
            line-height: 1.5;
        }

        > em {
            flex: none;
            font-style: normal;
            font-size: 12px;
            color: var(--el-text-color-secondary);

            &.is-hidden {
                color: var(--el-color-danger);
            }
        }
    }

    &__no {
        flex: none;
        font-style: normal;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 12px;
        color: var(--el-color-primary);
        opacity: 0.75;
    }

    &__tips {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed var(--el-border-color-extra-light);
        color: var(--el-text-color-secondary);
        font-size: 12px;
        line-height: 1.6;
    }

    &__setting {
        overflow: hidden;
    }
}
</style>
