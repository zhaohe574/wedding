<template>
    <div class="service-packages mx-[10px] mt-[10px]">
        <!-- 标题 -->
        <div v-if="content.title" class="flex items-center mb-[12px]">
            <div class="title-decoration">
                <div class="title-line"></div>
            </div>
            <span class="title-text">{{ content.title }}</span>
            <div class="flex-1"></div>
            <div v-if="content.show_more" class="more-btn">
                <span class="more-text">查看更多</span>
                <icon name="el-icon-ArrowRight" :size="12" />
            </div>
        </div>

        <!-- 横向滑动样式 -->
        <div v-if="content.style == 1" class="package-scroll flex gap-[10px] overflow-x-auto pb-2">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="package-card"
                style="width: 260px"
            >
                <!-- 卡片头部 -->
                <div class="card-header">
                    <div class="card-header-content">
                        <span class="card-title">{{ item.name }}</span>
                        <div v-if="item.tag" class="tag-badge">
                            <span class="tag-text">{{ item.tag }}</span>
                        </div>
                    </div>
                    <div class="price-wrapper">
                        <div class="price-main">
                            <span class="price-symbol">¥</span>
                            <span class="price-value">{{ item.price }}</span>
                        </div>
                        <span v-if="item.original_price" class="price-original">
                            ¥{{ item.original_price }}
                        </span>
                    </div>
                </div>
                
                <!-- 服务项列表 -->
                <div v-if="item.services && item.services.length" class="services-list">
                    <div
                        v-for="(service, sIndex) in item.services"
                        :key="sIndex"
                        class="service-item"
                    >
                        <div class="service-dot"></div>
                        <span class="service-text">{{ service }}</span>
                    </div>
                </div>
                
                <!-- 描述 -->
                <span v-if="item.desc" class="card-desc">{{ item.desc }}</span>
            </div>
        </div>

        <!-- 纵向列表样式 -->
        <div v-if="content.style == 2" class="package-list space-y-[10px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="package-card-vertical"
            >
                <!-- 卡片头部 -->
                <div class="card-header-vertical">
                    <div class="card-header-left">
                        <span class="card-title-vertical">{{ item.name }}</span>
                        <span v-if="item.desc" class="card-desc-vertical">{{ item.desc }}</span>
                    </div>
                    <div v-if="item.tag" class="tag-badge-vertical">
                        <span class="tag-text-vertical">{{ item.tag }}</span>
                    </div>
                </div>
                
                <!-- 服务项列表 -->
                <div v-if="item.services && item.services.length" class="services-list-vertical">
                    <div
                        v-for="(service, sIndex) in item.services"
                        :key="sIndex"
                        class="service-item-vertical"
                    >
                        <div class="service-dot-vertical"></div>
                        <span class="service-text-vertical">{{ service }}</span>
                    </div>
                </div>
                
                <!-- 价格 -->
                <div class="price-wrapper-vertical">
                    <div class="price-main-vertical">
                        <span class="price-symbol-vertical">¥</span>
                        <span class="price-value-vertical">{{ item.price }}</span>
                        <span v-if="item.original_price" class="price-original-vertical">
                            ¥{{ item.original_price }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 大卡片样式 -->
        <div v-if="content.style == 3" class="package-grid space-y-[10px]">
            <div
                v-for="(item, index) in showList"
                :key="index"
                class="package-card-large"
            >
                <!-- 卡片头部 -->
                <div class="card-header-large">
                    <div class="card-header-large-top">
                        <span class="card-title-large">{{ item.name }}</span>
                        <div v-if="item.tag" class="tag-badge-large">
                            <span class="tag-text-large">{{ item.tag }}</span>
                        </div>
                    </div>
                    <span v-if="item.desc" class="card-desc-large">{{ item.desc }}</span>
                </div>
                
                <!-- 服务项网格 -->
                <div v-if="item.services && item.services.length" class="services-grid">
                    <div
                        v-for="(service, sIndex) in item.services"
                        :key="sIndex"
                        class="service-badge"
                    >
                        <span class="service-badge-text">{{ service }}</span>
                    </div>
                </div>
                
                <!-- 价格 -->
                <div class="price-wrapper-large">
                    <div class="price-main-large">
                        <span class="price-symbol-large">¥</span>
                        <span class="price-value-large">{{ item.price }}</span>
                        <span v-if="item.original_price" class="price-original-large">
                            ¥{{ item.original_price }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import DecorationImg from '../../decoration-img.vue'
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
</script>

<style lang="scss" scoped>
.service-packages {
    // 标题样式
    .title-decoration {
        position: relative;
        width: 3px;
        height: 18px;
        margin-right: 8px;
        
        .title-line {
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, #7c3aed 0%, #a78bfa 100%);
            border-radius: 999px;
            box-shadow: 0 1px 4px rgba(124, 58, 237, 0.3);
        }
    }
    
    .title-text {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        letter-spacing: 0.3px;
    }
    
    .more-btn {
        display: flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(124, 58, 237, 0.08);
        cursor: pointer;
        transition: all 0.2s ease;
        
        &:hover {
            background: rgba(124, 58, 237, 0.15);
        }
        
        .more-text {
            font-size: 12px;
            color: #7c3aed;
            font-weight: 500;
        }
    }

    // 横向滑动样式
    .package-scroll {
        &::-webkit-scrollbar {
            display: none;
        }
    }

    .package-card {
        flex-shrink: 0;
        background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
        border-radius: 12px;
        padding: 14px;
        border: 1px solid rgba(124, 58, 237, 0.1);
        box-shadow: 0 2px 10px rgba(124, 58, 237, 0.08);
        
        .card-header {
            .card-header-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 6px;
                
                .card-title {
                    flex: 1;
                    font-size: 16px;
                    font-weight: 600;
                    color: #111827;
                    line-height: 1.4;
                }
                
                .tag-badge {
                    margin-left: 6px;
                    padding: 3px 8px;
                    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
                    border-radius: 999px;
                    box-shadow: 0 1px 4px rgba(249, 115, 22, 0.3);
                    
                    .tag-text {
                        font-size: 10px;
                        color: #ffffff;
                        font-weight: 600;
                    }
                }
            }
            
            .price-wrapper {
                display: flex;
                align-items: baseline;
                margin-bottom: 10px;
                
                .price-main {
                    display: flex;
                    align-items: baseline;
                    
                    .price-symbol {
                        font-size: 14px;
                        color: #7c3aed;
                        font-weight: 600;
                        margin-right: 2px;
                    }
                    
                    .price-value {
                        font-size: 24px;
                        color: #7c3aed;
                        font-weight: 700;
                        line-height: 1;
                    }
                }
                
                .price-original {
                    font-size: 12px;
                    color: #9ca3af;
                    text-decoration: line-through;
                    margin-left: 6px;
                }
            }
        }
        
        .services-list {
            padding: 10px 0;
            border-top: 1px solid rgba(124, 58, 237, 0.1);
            display: flex;
            flex-direction: column;
            gap: 6px;
            
            .service-item {
                display: flex;
                align-items: center;
                
                .service-dot {
                    width: 5px;
                    height: 5px;
                    background: #7c3aed;
                    border-radius: 50%;
                    margin-right: 6px;
                    flex-shrink: 0;
                }
                
                .service-text {
                    font-size: 13px;
                    color: #4b5563;
                    line-height: 1.5;
                }
            }
        }
        
        .card-desc {
            display: block;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(124, 58, 237, 0.1);
        }
    }

    // 纵向列表样式
    .package-card-vertical {
        background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
        border-radius: 12px;
        padding: 14px;
        border: 1px solid rgba(124, 58, 237, 0.1);
        box-shadow: 0 2px 10px rgba(124, 58, 237, 0.08);
        
        .card-header-vertical {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 10px;
            
            .card-header-left {
                flex: 1;
                
                .card-title-vertical {
                    display: block;
                    font-size: 16px;
                    font-weight: 600;
                    color: #111827;
                    line-height: 1.4;
                    margin-bottom: 4px;
                }
                
                .card-desc-vertical {
                    display: block;
                    font-size: 12px;
                    color: #6b7280;
                    line-height: 1.6;
                }
            }
            
            .tag-badge-vertical {
                margin-left: 8px;
                padding: 3px 8px;
                background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
                border-radius: 999px;
                box-shadow: 0 1px 4px rgba(249, 115, 22, 0.3);
                flex-shrink: 0;
                
                .tag-text-vertical {
                    font-size: 10px;
                    color: #ffffff;
                    font-weight: 600;
                }
            }
        }
        
        .services-list-vertical {
            padding: 10px 0;
            border-top: 1px solid rgba(124, 58, 237, 0.1);
            border-bottom: 1px solid rgba(124, 58, 237, 0.1);
            display: flex;
            flex-direction: column;
            gap: 6px;
            
            .service-item-vertical {
                display: flex;
                align-items: center;
                
                .service-dot-vertical {
                    width: 5px;
                    height: 5px;
                    background: #7c3aed;
                    border-radius: 50%;
                    margin-right: 6px;
                    flex-shrink: 0;
                }
                
                .service-text-vertical {
                    font-size: 13px;
                    color: #4b5563;
                    line-height: 1.5;
                }
            }
        }
        
        .price-wrapper-vertical {
            margin-top: 10px;
            
            .price-main-vertical {
                display: flex;
                align-items: baseline;
                
                .price-symbol-vertical {
                    font-size: 14px;
                    color: #7c3aed;
                    font-weight: 600;
                    margin-right: 2px;
                }
                
                .price-value-vertical {
                    font-size: 24px;
                    color: #7c3aed;
                    font-weight: 700;
                    line-height: 1;
                }
                
                .price-original-vertical {
                    font-size: 12px;
                    color: #9ca3af;
                    text-decoration: line-through;
                    margin-left: 6px;
                }
            }
        }
    }

    // 大卡片样式
    .package-card-large {
        background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
        border-radius: 12px;
        padding: 16px;
        border: 1px solid rgba(124, 58, 237, 0.1);
        box-shadow: 0 2px 10px rgba(124, 58, 237, 0.08);
        
        .card-header-large {
            margin-bottom: 12px;
            
            .card-header-large-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 6px;
                
                .card-title-large {
                    flex: 1;
                    font-size: 18px;
                    font-weight: 600;
                    color: #111827;
                    line-height: 1.4;
                }
                
                .tag-badge-large {
                    margin-left: 8px;
                    padding: 4px 10px;
                    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
                    border-radius: 999px;
                    box-shadow: 0 1px 4px rgba(249, 115, 22, 0.3);
                    
                    .tag-text-large {
                        font-size: 11px;
                        color: #ffffff;
                        font-weight: 600;
                    }
                }
            }
            
            .card-desc-large {
                display: block;
                font-size: 13px;
                color: #6b7280;
                line-height: 1.6;
            }
        }
        
        .services-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 12px 0;
            border-top: 1px solid rgba(124, 58, 237, 0.1);
            border-bottom: 1px solid rgba(124, 58, 237, 0.1);
            
            .service-badge {
                padding: 6px 10px;
                background: rgba(124, 58, 237, 0.08);
                border-radius: 999px;
                border: 1px solid rgba(124, 58, 237, 0.15);
                
                .service-badge-text {
                    font-size: 12px;
                    color: #7c3aed;
                    font-weight: 500;
                }
            }
        }
        
        .price-wrapper-large {
            margin-top: 12px;
            
            .price-main-large {
                display: flex;
                align-items: baseline;
                
                .price-symbol-large {
                    font-size: 16px;
                    color: #7c3aed;
                    font-weight: 600;
                    margin-right: 2px;
                }
                
                .price-value-large {
                    font-size: 28px;
                    color: #7c3aed;
                    font-weight: 700;
                    line-height: 1;
                }
                
                .price-original-large {
                    font-size: 14px;
                    color: #9ca3af;
                    text-decoration: line-through;
                    margin-left: 8px;
                }
            }
        }
    }
}
</style>
