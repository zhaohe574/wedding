<template>
    <div class="coupon-receive mx-[10px] mt-[10px]">
        <!-- 标题 -->
        <div v-if="content.title" class="flex items-center justify-between mb-[12px]">
            <div class="flex items-center">
                <div class="w-[4px] h-[18px] rounded-full mr-[8px]" style="background: linear-gradient(180deg, #2563EB 0%, #3B82F6 100%);"></div>
                <span class="text-base font-semibold" style="color: #1E293B;">{{ content.title }}</span>
            </div>
            <div v-if="content.show_more" class="flex items-center text-xs cursor-pointer" style="color: #64748B;">
                <span>查看更多</span>
                <icon name="el-icon-ArrowRight" :size="12" />
            </div>
        </div>

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 1" class="coupon-scroll flex gap-[12px] overflow-x-auto pb-2">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="coupon-card-horizontal flex-shrink-0 cursor-pointer"
            >
                <!-- 玻璃态背景 -->
                <div class="coupon-glass-bg"></div>
                
                <!-- 内容区 -->
                <div class="coupon-content-wrapper">
                    <!-- 左侧金额区 -->
                    <div class="coupon-amount-section">
                        <div class="coupon-symbol">¥</div>
                        <div class="coupon-value">{{ item.discount_amount || 0 }}</div>
                        <div class="coupon-condition">满{{ item.threshold_amount || 0 }}元</div>
                    </div>
                    
                    <!-- 分隔线 -->
                    <div class="coupon-divider"></div>
                    
                    <!-- 右侧信息区 -->
                    <div class="coupon-info-section">
                        <div class="coupon-name">{{ item.name || '优惠券' }}</div>
                        <div class="coupon-time">{{ formatValidPeriod(item) }}</div>
                        <div class="coupon-btn coupon-btn-active">
                            立即领取
                        </div>
                    </div>
                </div>
                
                <!-- 装饰圆点 -->
                <div class="coupon-dot coupon-dot-left"></div>
                <div class="coupon-dot coupon-dot-right"></div>
            </div>
        </div>

        <!-- 纵向列表样式 -->
        <div v-if="content.style == 2" class="coupon-list space-y-[12px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="coupon-card-vertical cursor-pointer"
            >
                <!-- 玻璃态背景 -->
                <div class="coupon-glass-bg"></div>
                
                <!-- 内容区 -->
                <div class="coupon-content-wrapper">
                    <!-- 左侧金额区 -->
                    <div class="coupon-amount-section">
                        <div class="coupon-symbol">¥</div>
                        <div class="coupon-value">{{ item.discount_amount || 0 }}</div>
                        <div class="coupon-condition">满{{ item.threshold_amount || 0 }}元</div>
                    </div>
                    
                    <!-- 分隔线 -->
                    <div class="coupon-divider"></div>
                    
                    <!-- 右侧信息区 -->
                    <div class="coupon-info-section">
                        <div class="coupon-name">{{ item.name || '优惠券' }}</div>
                        <div class="coupon-time">{{ formatValidPeriod(item) }}</div>
                        <div class="coupon-btn coupon-btn-active">
                            立即领取
                        </div>
                    </div>
                </div>
                <div class="coupon-glass-bg"></div>
                
                <!-- 内容区 -->
                <div class="coupon-content-wrapper">
                    <!-- 左侧金额区 -->
                    <div class="coupon-amount-section">
                        <div class="coupon-symbol">¥</div>
                        <div class="coupon-value">{{ item.discount_amount || 0 }}</div>
                        <div class="coupon-condition">满{{ item.threshold_amount || 0 }}元</div>
                    </div>
                    
                    <!-- 分隔线 -->
                    <div class="coupon-divider"></div>
                    
                    <!-- 右侧信息区 -->
                    <div class="coupon-info-section">
                        <div class="coupon-name">{{ item.name || '优惠券' }}</div>
                        <div class="coupon-time">{{ formatDate(item.valid_end_time) }} 到期</div>
                        <div class="coupon-btn coupon-btn-active">
                            立即领取
                        </div>
                    </div>
                </div>
                
                <!-- 装饰圆点 -->
                <div class="coupon-dot coupon-dot-left"></div>
                <div class="coupon-dot coupon-dot-right"></div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
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

const showList = computed(() => {
    const data = props.content.data?.filter((item: any) => item.is_show == '1') || []
    const limit = props.content.show_count || data.length
    return data.slice(0, limit)
})

// 格式化日期
function formatDate(timestamp: number): string {
    if (!timestamp) return '长期有效'
    const date = new Date(timestamp * 1000)
    return `${date.getFullYear()}.${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`
}

// 格式化有效期显示
function formatValidPeriod(item: any): string {
    // valid_type: 1=固定日期, 2=领取后N天
    if (item.valid_type == 2 && item.valid_days) {
        return `领取后${item.valid_days}天内有效`
    }
    if (item.valid_end_time) {
        return `${formatDate(item.valid_end_time)} 到期`
    }
    return '长期有效'
}
</script>

<style lang="scss" scoped>
.coupon-receive {
    .coupon-scroll {
        &::-webkit-scrollbar {
            display: none;
        }
    }
    
    // 横向卡片样式
    .coupon-card-horizontal {
        position: relative;
        width: 290px;
        height: 100px;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.2s ease;

        &:hover {
            transform: translateY(-2px);
        }
    }
    
    // 纵向卡片样式
    .coupon-card-vertical {
        position: relative;
        width: 100%;
        height: 100px;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.2s ease;

        &:hover {
            transform: translateY(-2px);
        }
    }
    
    // 玻璃态背景
    .coupon-glass-bg {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.95) 0%, rgba(59, 130, 246, 0.9) 100%);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25),
                    0 2px 6px rgba(37, 99, 235, 0.15),
                    inset 0 1px 2px rgba(255, 255, 255, 0.2);
    }
    
    // 内容包裹层
    .coupon-content-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        height: 100%;
        padding: 16px;
        z-index: 1;
    }
    
    // 左侧金额区域
    .coupon-amount-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100px;
        
        .coupon-symbol {
            font-size: 16px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1;
            margin-bottom: 4px;
        }
        
        .coupon-value {
            font-size: 40px;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1;
            margin-bottom: 6px;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }
        
        .coupon-condition {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }
    }
    
    // 分隔线
    .coupon-divider {
        width: 1px;
        height: 60px;
        background: linear-gradient(180deg, 
            rgba(255, 255, 255, 0) 0%, 
            rgba(255, 255, 255, 0.4) 50%, 
            rgba(255, 255, 255, 0) 100%
        );
        margin: 0 14px;
    }
    
    // 右侧信息区域
    .coupon-info-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        
        .coupon-name {
            font-size: 15px;
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .coupon-time {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 10px;
        }
        
        .coupon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 28px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .coupon-btn-active {
            background: #FFFFFF;
            color: #2563EB;
            box-shadow: 0 2px 6px rgba(255, 255, 255, 0.3);
            
            &:hover {
                transform: scale(0.95);
            }
        }
        
        .coupon-btn-received {
            background: rgba(255, 255, 255, 0.25);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
    }
    
    // 装饰圆点（模拟撕边效果）
    .coupon-dot {
        position: absolute;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #F8FAFC;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
    }
    
    .coupon-dot-left {
        left: -6px;
    }
    
    .coupon-dot-right {
        right: -6px;
    }
}
</style>
