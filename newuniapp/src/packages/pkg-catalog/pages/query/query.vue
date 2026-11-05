<template>
    <view class="page-wrap query">
        <view class="hero">
            <text class="hero__title">查档期</text>
            <text class="hero__sub">选好日子，看看谁还有空</text>
        </view>

        <view class="page-pad form">
            <!-- 日期 -->
            <picker mode="date" :value="date" :start="today" @change="onDateChange">
                <view class="field">
                    <text class="field__label">服务日期</text>
                    <view class="field__value">
                        <text :class="date ? 'field__text' : 'field__ph'">{{ date || '请选择日期' }}</text>
                        <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                    </view>
                </view>
            </picker>

            <!-- 分类 -->
            <view class="field">
                <text class="field__label">服务类型</text>
                <scroll-view class="chips" scroll-x :show-scrollbar="false">
                    <view class="chips__row">
                        <view hover-class="is-pressed"
                            class="chip"
                            :class="{ 'is-active': categoryId === 0 }"
                            @tap="categoryId = 0"
                        >
                            <text class="chip__text">全部</text>
                        </view>
                        <view hover-class="is-pressed"
                            v-for="c in categories"
                            :key="c.id"
                            class="chip"
                            :class="{ 'is-active': categoryId === c.id }"
                            @tap="categoryId = c.id"
                        >
                            <text class="chip__text">{{ c.name }}</text>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <!-- 关键词 -->
            <view class="field field--stack">
                <text class="field__label">关键词</text>
                <input
                    v-model="keyword"
                    class="field__input"
                    placeholder="人员姓名 / 风格，可留空"
                    placeholder-class="field__ph"
                    confirm-type="search"
                    @confirm="handleSearch"
                />
            </view>

            <view class="tips">
                <u-icon name="info-circle" :size="24" color="var(--c-ink-3)" />
                <text class="tips__text">档期以服务人员当天是否已被预约为准，最终以下单确认为准</text>
            </view>

            <view class="submit">
                <base-button text="查看可预约人员" variant="brand" size="lg" :loading="loading" @click="handleSearch" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 档期查询。
 *
 * 只收集三个必要条件（日期/分类/关键词），其余筛选交给列表页 ——
 * 老端把 8 个筛选项堆在这一页，用户填完已经流失一半。
 */
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getCategoryTree, type ServiceCategory } from '@/api/catalog'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'

const date = ref('')
const categoryId = ref(0)
const keyword = ref('')
const categories = ref<ServiceCategory[]>([])
const loading = ref(false)

/** 今天，作为 picker 的最小可选日期 */
const today = (() => {
    const d = new Date()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${d.getFullYear()}-${m}-${day}`
})()

function onDateChange(e: any) {
    date.value = String(e?.detail?.value || '')
}

async function loadCategories() {
    try {
        const list = await getCategoryTree()
        categories.value = Array.isArray(list) ? list : []
    } catch {
        report('api_error', { endpoint: 'service.categoryTree', message: '服务分类加载失败' })
    }
}

function handleSearch() {
    loading.value = true
    navigate({
        path: '/packages/pkg-catalog/pages/staff-list/staff-list',
        query: {
            date: date.value,
            category_id: categoryId.value || '',
            keyword: keyword.value
        }
    })
    loading.value = false
}

onLoad((options: any) => {
    if (options?.date) date.value = String(options.date)
    if (options?.category_id) categoryId.value = Number(options.category_id) || 0
    if (options?.keyword) keyword.value = String(options.keyword)
    loadCategories()
})
</script>

<style lang="scss" scoped>
.query {
    background: var(--c-bg);
}

.hero {
    padding: 120rpx var(--sp-4) var(--sp-5);
    background: linear-gradient(180deg, var(--c-hero-bg) 0%, rgba(248, 246, 240, 0) 100%);

    &__title {
        display: block;
        font-size: var(--fs-2xl);
        font-weight: 500;
        line-height: 1.25;
        color: var(--c-ink);
    }

    &__sub {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.5;
    }
}

.form {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    margin-top: -20rpx;
}

.field {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 96rpx;
    box-sizing: border-box;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    padding: var(--sp-2) var(--sp-3);
    box-shadow: var(--shadow-card);

    &--stack {
        align-items: stretch;
        flex-direction: column;
        gap: var(--sp-1);
        padding-top: var(--sp-3);
        padding-bottom: var(--sp-2);
    }

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.5;
    }

    &__value {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-1);
        min-width: 0;
        min-height: 88rpx;
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__ph {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }

    &__input {
        width: 100%;
        height: 64rpx;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

.chips {
    flex: 1;
    width: 0;
    min-width: 0;
    white-space: nowrap;

    &__row {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-2);
        padding-right: var(--sp-1);
    }
}

.chip {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 56rpx;
    box-sizing: border-box;
    padding: var(--sp-1) var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-pill);
    background: var(--c-surface-2);

    &.is-active {
        background: var(--c-ink);
        border-color: var(--c-ink);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.4;
    }

    &.is-active &__text {
        color: var(--c-on-dark);
    }
}

.tips {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-1);
    padding: var(--sp-1);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: 1.6;
    }
}

.submit {
    margin-top: var(--sp-4);
}
</style>
