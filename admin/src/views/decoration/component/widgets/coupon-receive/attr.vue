<template>
    <div>
        <el-form label-width="80px">
            <!-- 基础设置 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">基础设置</div>
                </div>
                <el-form-item label="组件标题">
                    <el-input
                        v-model="contentData.title"
                        placeholder="请输入标题"
                        style="width: 300px"
                    />
                </el-form-item>
                <el-form-item label="显示数量">
                    <el-input-number
                        v-model="contentData.show_count"
                        :min="1"
                        :max="20"
                        style="width: 300px"
                    />
                </el-form-item>
                <el-form-item label="查看更多">
                    <el-switch
                        v-model="contentData.show_more"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
                <el-form-item v-if="contentData.show_more" label="更多链接">
                    <link-picker v-model="contentData.more_link" />
                </el-form-item>
            </el-card>

            <!-- 展示样式 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">展示样式</div>
                </div>
                <el-radio-group v-model="contentData.style">
                    <el-radio :value="1">横向滑动</el-radio>
                    <el-radio :value="2">纵向列表</el-radio>
                </el-radio-group>
            </el-card>

            <!-- 优惠券列表 -->
            <el-card shadow="never" class="!border-none flex mt-2">
                <div class="flex items-end mb-4">
                    <div class="text-base text-[#101010] font-medium">优惠券列表</div>
                    <div class="text-xs text-tx-secondary ml-2">从已存在的优惠券中选择，无需手动录入</div>
                </div>
                <div class="coupon-list">
                    <draggable
                        :model-value="Array.isArray(contentData.data) ? contentData.data : []"
                        @update:model-value="(val: any[]) => emits('update:content', { ...props.content, data: val })"
                        :item-key="(el: any, i: number) => (el && el.coupon_id) ? el.coupon_id : 'i' + i"
                        animation="300"
                        handle=".drag-handle"
                    >
                        <template #item="{ element, index }">
                            <div class="coupon-item mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    <!-- 拖拽手柄 -->
                                    <div class="drag-handle cursor-move mr-3 mt-2">
                                        <icon name="el-icon-Rank" :size="18" color="#999" />
                                    </div>

                                    <!-- 优惠券信息（只读）与更换 -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-2">
                                            <span class="font-medium text-gray-900">
                                                {{ getCouponName(element.coupon_id) || '未选择' }}
                                            </span>
                                            <span v-if="element.name" class="px-2 py-0.5 bg-orange-100 rounded text-xs text-orange-600">
                                                ¥{{ element.discount_amount }} 满{{ element.threshold_amount }}元可用
                                            </span>
                                        </div>
                                        <div v-if="element.name" class="text-xs text-gray-500 mb-2">
                                            有效期：{{ formatValidPeriod(element) }}
                                            <span v-if="element.total_count"> · 总量{{ element.total_count }}张</span>
                                        </div>
                                        <el-form-item label="更换优惠券" class="!mb-0" label-width="80px">
                                            <el-select
                                                :model-value="element.coupon_id || ''"
                                                placeholder="选择优惠券"
                                                filterable
                                                clearable
                                                style="width: 280px"
                                                @change="(id: number) => handleReplace(index, id)"
                                            >
                                                <el-option
                                                    v-for="coupon in couponOptions"
                                                    :key="coupon.id"
                                                    :label="coupon.name"
                                                    :value="coupon.id"
                                                />
                                            </el-select>
                                        </el-form-item>
                                    </div>

                                    <!-- 操作按钮 -->
                                    <div class="ml-4 flex flex-col gap-2 flex-shrink-0">
                                        <el-switch
                                            v-model="element.is_show"
                                            active-value="1"
                                            inactive-value="0"
                                            active-text="显示"
                                        />
                                        <el-button
                                            type="danger"
                                            text
                                            :icon="Delete"
                                            @click="handleDelete(index)"
                                        >
                                            删除
                                        </el-button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <!-- 添加按钮 -->
                    <div class="mt-2">
                        <span class="text-sm text-gray-500 mr-2">添加优惠券：</span>
                        <el-select
                            v-model="addCouponId"
                            placeholder="请选择要展示的优惠券"
                            filterable
                            clearable
                            style="width: 280px"
                            @change="handleAddByCoupon"
                        >
                            <el-option
                                v-for="coupon in couponOptions"
                                :key="coupon.id"
                                :label="coupon.name"
                                :value="coupon.id"
                            />
                        </el-select>
                    </div>
                </div>
            </el-card>
        </el-form>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'
import { Delete } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import draggable from 'vuedraggable'
import { getEnabledCouponList, getCouponDetail } from '@/api/coupon'
import type options from './options'
import type { CouponReceiveItem } from './options'

type OptionsType = ReturnType<typeof options>

const emits = defineEmits<(event: 'update:content', data: OptionsType['content']) => void>()

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

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

function ensureDataArray() {
    if (!Array.isArray(props.content?.data)) {
        emits('update:content', { ...props.content, data: [] })
    }
}

const couponOptions = ref<{ id: number; name: string }[]>([])
const addCouponId = ref<number | ''>('')
const loading = ref(false)

onMounted(() => {
    ensureDataArray()
    loadCouponOptions()
})

// 加载优惠券列表
async function loadCouponOptions() {
    try {
        const res = await getEnabledCouponList()
        couponOptions.value = Array.isArray(res) ? res : (res?.lists ?? res?.data ?? [])
    } catch (e) {
        console.error('加载优惠券列表失败', e)
        couponOptions.value = []
    }
}

// 获取优惠券名称
function getCouponName(couponId: number): string {
    const coupon = couponOptions.value.find((c) => c.id === couponId)
    return coupon?.name || ''
}

// 格式化日期
function formatDate(timestamp: number): string {
    if (!timestamp) return ''
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

// 构建优惠券数据项（只保存引用ID和控制信息）
function buildItemFromDetail(d: any, keepIsShow?: string): CouponReceiveItem {
    return {
        coupon_id: d.id,
        is_show: keepIsShow ?? '1',
        sort: 0,
        // 以下字段用于预览显示，不会保存到配置中
        name: d.name,
        coupon_type: d.coupon_type,
        discount_amount: d.discount_amount,
        threshold_amount: d.threshold_amount,
        valid_type: d.valid_type,
        valid_days: d.valid_days,
        valid_start_time: d.valid_start_time,
        valid_end_time: d.valid_end_time,
        total_count: d.total_count,
        receive_count: d.receive_count
    }
}

// 更换优惠券
async function handleReplace(index: number, couponId: number | '') {
    if (!couponId) return
    loading.value = true
    try {
        const d = await getCouponDetail({ id: couponId })
        if (!d || !d.id) return
        const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
        const old = list[index] as CouponReceiveItem
        list[index] = buildItemFromDetail(d, old?.is_show ?? '1')
        contentData.value = { ...contentData.value, data: list }
    } catch (e) {
        console.error('获取优惠券详情失败', e)
        ElMessage.error('获取优惠券详情失败')
    } finally {
        loading.value = false
    }
}

// 添加优惠券
async function handleAddByCoupon(couponId: number | '') {
    if (!couponId) return
    loading.value = true
    addCouponId.value = ''
    try {
        const d = await getCouponDetail({ id: couponId })
        if (!d || !d.id) return
        const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
        list.push(buildItemFromDetail(d))
        contentData.value = { ...contentData.value, data: list }
        ElMessage.success('添加成功')
    } catch (e) {
        console.error('获取优惠券详情失败', e)
        ElMessage.error('获取优惠券详情失败')
    } finally {
        loading.value = false
    }
}

// 删除优惠券
function handleDelete(index: number) {
    const list = Array.isArray(contentData.value.data) ? [...contentData.value.data] : []
    list.splice(index, 1)
    contentData.value = { ...contentData.value, data: list }
}
</script>

<style lang="scss" scoped>
.coupon-item {
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;

    &:hover {
        border-color: var(--el-color-primary-light-5);
    }
}

.drag-handle {
    &:hover {
        color: var(--el-color-primary);
    }
}
</style>
