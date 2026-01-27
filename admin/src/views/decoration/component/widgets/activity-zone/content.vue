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
                    <div class="flex items-center justify-between">
                        <div v-if="item.price" class="flex items-baseline">
                            <span class="text-[12px] text-white/80">¥</span>
                            <span class="text-[20px] font-bold text-white">{{ item.price }}</span>
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
                    <div v-if="item.price" class="flex items-baseline">
                        <span class="text-[11px]" style="color: #7C3AED;">¥</span>
                        <span class="text-[15px] font-bold" style="color: #7C3AED;">{{ item.price }}</span>
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
                    <div class="flex items-center justify-between">
                        <div v-if="item.price" class="flex items-baseline">
                            <span class="text-[11px] text-white/80">¥</span>
                            <span class="text-[17px] font-bold text-white">{{ item.price }}</span>
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
                        </div>
                        <div class="flex items-center justify-between mt-[8px]">
                            <div v-if="item.price" class="flex items-baseline">
                                <span class="text-[11px]" style="color: #7C3AED;">¥</span>
                                <span class="text-[17px] font-bold" style="color: #7C3AED;">{{ item.price }}</span>
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
            price: '',
            original_price: '',
            is_show: '1',
            link: {
                path: '/pages/dynamic/detail',
                query: { id: item.id },
                name: '活动详情'
            }
        }
    })
})
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
