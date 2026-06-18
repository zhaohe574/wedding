<template>
    <div class="activity-zone mx-[10px] mt-[10px]">
        <!-- 标题栏 -->
        <div v-if="content.title" class="flex items-center justify-between mb-[16px]">
            <div class="flex items-center">
                <div class="w-[4px] h-[20px] rounded-full mr-[10px]" 
                     style="background: linear-gradient(180deg, #7C3AED 0%, #A78BFA 100%);"></div>
                <span class="text-[17px] font-semibold" style="color: #4C1D95;">
                    {{ content.title }}
                </span>
            </div>
            <div v-if="content.show_more" class="flex items-center text-[13px] cursor-pointer hover:opacity-80 transition-opacity duration-200" style="color: #7C3AED;">
                <span>更多活动</span>
                <icon name="el-icon-ArrowRight" :size="14" class="ml-1" />
            </div>
        </div>

        <!-- 大图样式 -->
        <div v-if="content.style == 1" class="activity-banner space-y-[12px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item relative rounded-[16px] overflow-hidden cursor-pointer group"
                style="box-shadow: 0 4px 12px rgba(124, 58, 237, 0.08); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);"
            >
                <decoration-img
                    width="100%"
                    height="180px"
                    :src="item.image"
                    fit="cover"
                    class="group-hover:scale-105 transition-transform duration-500"
                />
                <div class="absolute inset-0 flex flex-col justify-end p-[16px]"
                     style="background: linear-gradient(180deg, transparent 0%, transparent 50%, rgba(0,0,0,0.4) 75%, rgba(0,0,0,0.8) 100%);">
                    <!-- 标签 -->
                    <span v-if="item.tag" class="absolute top-[12px] left-[12px] px-[10px] py-[4px] rounded-full text-white text-[11px] font-medium"
                          style="background: #F97316; box-shadow: 0 2px 6px rgba(249, 115, 22, 0.4);">
                        {{ item.tag }}
                    </span>
                    
                    <h3 class="text-white text-[17px] font-bold mb-[6px] line-clamp-1">{{ item.title }}</h3>
                    <p v-if="item.desc" class="text-white/90 text-[13px] mb-[10px] line-clamp-1">{{ item.desc }}</p>
                    <div class="flex flex-wrap items-center gap-[6px] mb-[10px]">
                        <span v-if="item.startText" class="activity-meta-pill activity-meta-pill--light">
                            {{ item.startText }}
                        </span>
                        <span v-if="item.registrationText" class="activity-meta-pill activity-meta-pill--light">
                            {{ item.registrationText }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex flex-wrap items-center gap-[6px]">
                            <span v-if="item.priceLabel" class="activity-price-label activity-price-label--light">
                                {{ item.priceLabel }}
                            </span>
                            <span v-if="item.remainingText" class="activity-meta-pill activity-meta-pill--light">
                                {{ item.remainingText }}
                            </span>
                        </div>
                        <div class="cta-button px-[16px] py-[8px] rounded-full text-[12px] font-semibold transition-all duration-200"
                             style="background: white; color: #7C3AED;">
                            立即参与
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 卡片网格样式 -->
        <div v-if="content.style == 2" class="activity-grid grid grid-cols-2 gap-[12px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item bg-white rounded-[16px] overflow-hidden cursor-pointer group"
                style="box-shadow: 0 2px 8px rgba(124, 58, 237, 0.06); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);"
            >
                <div class="relative overflow-hidden">
                    <decoration-img
                        width="100%"
                        height="140px"
                        :src="item.image"
                        fit="cover"
                        class="group-hover:scale-110 transition-transform duration-500"
                    />
                    <span v-if="item.tag" class="absolute top-[8px] left-[8px] px-[8px] py-[3px] rounded-full text-white text-[11px] font-medium"
                          style="background: #F97316; box-shadow: 0 1px 4px rgba(249, 115, 22, 0.3);">
                        {{ item.tag }}
                    </span>
                </div>
                <div class="p-[10px]">
                    <h4 class="text-[14px] font-semibold line-clamp-1 mb-[6px]" style="color: #4C1D95;">
                        {{ item.title }}
                    </h4>
                    <div class="activity-card-meta">
                        <span v-if="item.startText" class="activity-meta-pill">
                            {{ item.startText }}
                        </span>
                        <span v-if="item.remainingText" class="activity-meta-pill">
                            {{ item.remainingText }}
                        </span>
                    </div>
                    <div v-if="item.priceLabel" class="activity-price-label">
                        {{ item.priceLabel }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 3" class="activity-scroll flex gap-[12px] overflow-x-auto pb-2 -mx-[10px] px-[10px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item relative rounded-[16px] overflow-hidden flex-shrink-0 cursor-pointer group"
                style="width: 300px; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.08); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);"
            >
                <decoration-img
                    width="300px"
                    height="160px"
                    :src="item.image"
                    fit="cover"
                    class="group-hover:scale-105 transition-transform duration-500"
                />
                <div class="absolute inset-0 flex flex-col justify-end p-[14px]"
                     style="background: linear-gradient(180deg, transparent 0%, transparent 50%, rgba(0,0,0,0.4) 75%, rgba(0,0,0,0.8) 100%);">
                    <span v-if="item.tag" class="absolute top-[10px] left-[10px] px-[8px] py-[3px] rounded-full text-white text-[11px] font-medium"
                          style="background: #F97316; box-shadow: 0 2px 6px rgba(249, 115, 22, 0.4);">
                        {{ item.tag }}
                    </span>
                    <h4 class="text-white text-[15px] font-bold line-clamp-1 mb-[6px]">{{ item.title }}</h4>
                    <div class="flex flex-wrap items-center gap-[6px] mb-[8px]">
                        <span v-if="item.startText" class="activity-meta-pill activity-meta-pill--light">
                            {{ item.startText }}
                        </span>
                        <span v-if="item.registrationText" class="activity-meta-pill activity-meta-pill--light">
                            {{ item.registrationText }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex flex-wrap items-center gap-[6px]">
                            <span v-if="item.priceLabel" class="activity-price-label activity-price-label--light">
                                {{ item.priceLabel }}
                            </span>
                            <span v-if="item.remainingText" class="activity-meta-pill activity-meta-pill--light">
                                {{ item.remainingText }}
                            </span>
                        </div>
                        <div class="px-[12px] py-[5px] rounded-full text-white text-[11px] font-medium"
                             style="background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(5px);">
                            查看详情
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 列表样式 -->
        <div v-if="content.style == 4" class="activity-list space-y-[12px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="activity-item bg-white rounded-[16px] overflow-hidden cursor-pointer group"
                style="box-shadow: 0 2px 8px rgba(124, 58, 237, 0.06); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);"
            >
                <div class="flex">
                    <div class="relative flex-shrink-0 overflow-hidden">
                        <decoration-img
                            width="140px"
                            height="100px"
                            :src="item.image"
                            fit="cover"
                            class="group-hover:scale-110 transition-transform duration-500"
                        />
                        <span v-if="item.tag" class="absolute top-[8px] left-[8px] px-[8px] py-[3px] rounded-full text-white text-[11px] font-medium"
                              style="background: #F97316; box-shadow: 0 1px 4px rgba(249, 115, 22, 0.3);">
                            {{ item.tag }}
                        </span>
                    </div>
                    <div class="flex-1 p-[12px] flex flex-col justify-between">
                        <div>
                            <h4 class="text-[14px] font-semibold line-clamp-1 mb-[4px]" style="color: #4C1D95;">
                                {{ item.title }}
                            </h4>
                            <p v-if="item.desc" class="text-[12px] line-clamp-2" style="color: #9CA3AF;">
                                {{ item.desc }}
                            </p>
                            <div class="activity-card-meta mt-[8px]">
                                <span v-if="item.startText" class="activity-meta-pill">
                                    {{ item.startText }}
                                </span>
                                <span v-if="item.registrationText" class="activity-meta-pill">
                                    {{ item.registrationText }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-[8px]">
                            <div class="flex flex-wrap items-center gap-[6px]">
                                <span v-if="item.priceLabel" class="activity-price-label">
                                    {{ item.priceLabel }}
                                </span>
                                <span v-if="item.remainingText" class="activity-meta-pill">
                                    {{ item.remainingText }}
                                </span>
                            </div>
                            <div class="cta-button px-[12px] py-[5px] rounded-full text-white text-[11px] font-medium transition-colors duration-200"
                                 style="background: #7C3AED;">
                                立即参与
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import { ref, computed, watch } from 'vue'
import DecorationImg from '../../decoration-img.vue'
import { getDecorateActivityList } from '@/api/decoration'
import type options from './options'

type OptionsType = ReturnType<typeof options>

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})

const activityList = ref<any[]>([])

// 加载活动数据
const loadActivities = async () => {
    try {
        // 确保有默认的data_source值
        const dataSource = props.content.data_source || 'auto'
        
        if (dataSource === 'auto') {
            // 自动获取最新活动
            const res = await getDecorateActivityList({ 
                limit: props.content.show_count || 10 
            })
            activityList.value = res.data || []
        } else if (dataSource === 'manual' && props.content.activity_ids?.length) {
            // 手动选择的活动
            const res = await getDecorateActivityList({ limit: 100 })
            const allActivities = res.data || []
            // 按照选择的顺序筛选活动
            activityList.value = props.content.activity_ids
                .map((id: number) => allActivities.find((item: any) => item.id === id))
                .filter(Boolean)
        } else {
            activityList.value = []
        }
    } catch (error) {
        console.error('加载活动数据失败:', error)
        activityList.value = []
    }
}

// 监听配置变化
watch(() => [props.content.data_source, props.content.activity_ids, props.content.show_count], () => {
    loadActivities()
}, { immediate: true, deep: true })

// 显示列表
const showList = computed(() => {
    const limit = props.content.show_count || activityList.value.length
    return activityList.value.slice(0, limit).map(item => {
        // 获取第一个标签 - 处理tags字段（字符串或数组）
        let tag = ''
        if (item.tags_arr && item.tags_arr.length > 0) {
            tag = item.tags_arr[0]
        } else if (item.tags) {
            if (typeof item.tags === 'string') {
                // 如果tags是字符串，直接使用或按逗号分割取第一个
                const trimmedTags = item.tags.trim()
                if (trimmedTags) {
                    const tagsArray = trimmedTags.split(',').filter((t: string) => t.trim())
                    tag = tagsArray.length > 0 ? tagsArray[0].trim() : trimmedTags
                }
            } else if (Array.isArray(item.tags) && item.tags.length > 0) {
                // 如果tags是数组，取第一个
                tag = item.tags[0]
            }
        }
        
        return {
            image: item.cover_image || '',
            title: item.title || '',
            desc: item.content_preview || '',
            tag: tag,
            priceLabel: getActivityPriceLabel(item),
            startText: getActivityStartText(item),
            remainingText: getActivityRemainingText(item),
            registrationText: getActivityRegistrationText(item),
            is_show: '1',
            link: {
                path: '/pages/dynamic_detail/dynamic_detail',
                query: { id: item.id },
                name: '活动详情'
            }
        }
    })
})

const formatTimestamp = (timestamp: any) => {
    const value = Number(timestamp || 0)
    if (!value) return ''
    const date = new Date(value * 1000)
    const pad = (num: number) => String(num).padStart(2, '0')
    return `${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(
        date.getMinutes()
    )}`
}

const getActivityPayload = (item: any) => item?.activity || item || {}

const getActivityPriceLabel = (item: any) => {
    const activity = getActivityPayload(item)
    if (activity.price_label) return activity.price_label
    const tickets = Array.isArray(activity.tickets) ? activity.tickets : []
    const enabledTickets = tickets.filter((ticket: any) => Number(ticket.status ?? 1) === 1)
    if (enabledTickets.length === 0) return '免费'
    const prices = enabledTickets.map((ticket: any) => Number(ticket.price || 0))
    const minPrice = Math.min(...prices)
    return minPrice > 0 ? `¥${minPrice.toFixed(2)}起` : '免费'
}

const getActivityStartText = (item: any) => {
    const activity = getActivityPayload(item)
    const timestamp = activity.start_time || activity.activity_start_time || item?.activity_start_time
    const text = formatTimestamp(timestamp)
    return text ? `开始 ${text}` : ''
}

const getActivityRemainingText = (item: any) => {
    const activity = getActivityPayload(item)
    if (activity.remaining_text) return activity.remaining_text
    const remaining = Number(
        activity.remaining_count ?? item?.activity_remaining_count ?? item?.remaining_count ?? -1
    )
    if (remaining >= 0) return `余量 ${remaining}`
    const totalQuota = Number(activity.total_quota ?? item?.activity_total_quota ?? 0)
    if (totalQuota <= 0) return '名额不限'
    const used = Number(activity.registered_count ?? item?.activity_registered_count ?? 0)
    return `余量 ${Math.max(totalQuota - used, 0)}`
}

const getActivityRegistrationText = (item: any) => {
    const activity = getActivityPayload(item)
    return activity.activity_registration_status_text || activity.registration_status_text || '可报名'
}
</script>

<style lang="scss" scoped>
.activity-zone {
    .activity-item {
        &:hover {
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.15);
            transform: translateY(-2px);
        }
    }
    
    .cta-button {
        &:hover {
            background: #F97316 !important;
            color: white !important;
        }
    }
    
    .activity-scroll {
        scrollbar-width: none;
        -ms-overflow-style: none;
        
        &::-webkit-scrollbar {
            display: none;
        }
    }

    .activity-card-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }

    .activity-meta-pill {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        padding: 0 8px;
        border-radius: 999px;
        background: rgba(124, 58, 237, 0.08);
        color: #6D28D9;
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
    }

    .activity-meta-pill--light {
        background: rgba(255, 255, 255, 0.2);
        color: #FFFFFF;
    }

    .activity-price-label {
        color: #7C3AED;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }

    .activity-price-label--light {
        color: #FFFFFF;
    }
}

/* 支持 prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
    .activity-zone {
        .activity-item,
        .group-hover\:scale-105,
        .group-hover\:scale-110 {
            transition: none !important;
            transform: none !important;
        }
    }
}
</style>
