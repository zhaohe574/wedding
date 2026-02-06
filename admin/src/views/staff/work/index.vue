<template>
    <div class="staff-work">
        <el-card class="!border-none" shadow="never">
            <el-form :model="queryParams" inline>
                <el-form-item label="工作人员">
                    <el-select v-model="queryParams.staff_id" placeholder="选择人员" clearable filterable>
                        <el-option
                            v-for="item in staffOptions"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="作品标题">
                    <el-input
                        v-model="queryParams.title"
                        placeholder="输入作品标题"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item label="作品类型">
                    <el-select v-model="queryParams.type" placeholder="选择类型" clearable>
                        <el-option label="图片" :value="1" />
                        <el-option label="视频" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item label="审核状态">
                    <el-select v-model="queryParams.audit_status" placeholder="选择状态" clearable>
                        <el-option label="待审核" :value="0" />
                        <el-option label="已通过" :value="1" />
                        <el-option label="已拒绝" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item label="显示状态">
                    <el-select v-model="queryParams.is_show" placeholder="选择状态" clearable>
                        <el-option label="显示" :value="1" />
                        <el-option label="隐藏" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="封面">
                    <el-select v-model="queryParams.is_cover" placeholder="选择状态" clearable>
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="封面" width="90">
                    <template #default="{ row }">
                        <el-image
                            :src="row.cover || row.images?.[0]"
                            fit="cover"
                            class="w-[64px] h-[64px] rounded"
                            :preview-src-list="row.cover ? [row.cover] : row.images || []"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="标题" prop="title" min-width="180" />
                <el-table-column label="工作人员" min-width="120">
                    <template #default="{ row }">
                        <div class="flex flex-col">
                            <span>{{ row.staff?.name || '-' }}</span>
                            <span class="text-xs text-gray-400">{{ row.staff?.sn || '' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="类型" prop="type_desc" width="90" />
                <el-table-column label="审核状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getAuditTagType(row.audit_status)">
                            {{ row.audit_status_desc || '-' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="显示" width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['staff.staffWork/changeStatus']"
                            v-model="row.is_show"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleChangeStatus($event, row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="封面" width="80">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_cover" type="success">封面</el-tag>
                        <el-tag v-else type="info">否</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="80" />
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="openDetail(row)">详情</el-button>
                        <el-button
                            v-if="row.audit_status === 0"
                            v-perms="['staff.staffWork/audit']"
                            link
                            type="success"
                            @click="handleAudit(row, 1)"
                        >
                            通过
                        </el-button>
                        <el-button
                            v-if="row.audit_status === 0"
                            v-perms="['staff.staffWork/audit']"
                            link
                            type="danger"
                            @click="handleAudit(row, 2)"
                        >
                            拒绝
                        </el-button>
                        <el-button
                            v-if="row.audit_status === 1 && !row.is_cover"
                            v-perms="['staff.staffWork/setCover']"
                            link
                            type="warning"
                            @click="handleSetCover(row)"
                        >
                            设为封面
                        </el-button>
                        <el-button
                            v-perms="['staff.staffWork/delete']"
                            link
                            type="danger"
                            @click="handleDelete(row)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="作品详情" width="900px" :close-on-click-modal="false">
            <div v-if="detailData" class="work-detail">
                <!-- 顶部作品信息 -->
                <div class="flex gap-6 mb-6">
                    <!-- 封面图 -->
                    <div class="flex-shrink-0">
                        <el-image
                            :src="detailData.cover || detailData.images?.[0]"
                            fit="cover"
                            class="w-[280px] h-[210px] rounded-lg shadow-md cursor-pointer"
                            :preview-src-list="detailData.cover ? [detailData.cover] : detailData.images || []"
                        />
                    </div>
                    
                    <!-- 基本信息 -->
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold mb-3">{{ detailData.title || '未命名作品' }}</h3>
                        
                        <div class="flex items-center gap-2 mb-3">
                            <el-tag :type="getAuditTagType(detailData.audit_status)" size="small">
                                {{ detailData.audit_status_desc || '-' }}
                            </el-tag>
                            <el-tag v-if="detailData.is_show" type="success" size="small">显示中</el-tag>
                            <el-tag v-else type="info" size="small">已隐藏</el-tag>
                            <el-tag v-if="detailData.is_cover" type="warning" size="small">封面作品</el-tag>
                        </div>

                        <!-- 统计数据 -->
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ detailData.view_count || 0 }}</div>
                                <div class="text-xs text-gray-500 mt-1">浏览量</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-red-600">{{ detailData.like_count || 0 }}</div>
                                <div class="text-xs text-gray-500 mt-1">点赞数</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold text-green-600">{{ detailData.sort || 0 }}</div>
                                <div class="text-xs text-gray-500 mt-1">排序权重</div>
                            </div>
                        </div>

                        <!-- 拍摄信息 -->
                        <div class="space-y-2 text-sm">
                            <div v-if="detailData.shoot_date" class="flex items-center text-gray-600">
                                <el-icon class="mr-2"><Calendar /></el-icon>
                                <span>拍摄日期：{{ detailData.shoot_date }}</span>
                            </div>
                            <div v-if="detailData.location" class="flex items-center text-gray-600">
                                <el-icon class="mr-2"><Location /></el-icon>
                                <span>拍摄地点：{{ detailData.location }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 工作人员信息卡片 -->
                <el-card v-if="detailData.staff" shadow="hover" class="mb-4">
                    <template #header>
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">工作人员信息</span>
                        </div>
                    </template>
                    <div class="flex items-start gap-4">
                        <el-avatar :size="64" :src="detailData.staff.avatar">
                            {{ detailData.staff.name?.charAt(0) }}
                        </el-avatar>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-lg font-medium">{{ detailData.staff.name || '-' }}</span>
                                <el-tag size="small">{{ detailData.staff.category_name || '-' }}</el-tag>
                                <el-tag :type="detailData.staff.status === 1 ? 'success' : 'info'" size="small">
                                    {{ detailData.staff.status_desc || '-' }}
                                </el-tag>
                            </div>
                            <div class="text-sm text-gray-500 mb-3">
                                工号：{{ detailData.staff.sn || '-' }} | 手机：{{ detailData.staff.mobile || '-' }}
                            </div>
                            <div class="grid grid-cols-4 gap-3 text-sm">
                                <div class="text-center">
                                    <div class="text-orange-500 font-semibold">{{ detailData.staff.rating || '5.0' }}</div>
                                    <div class="text-gray-400 text-xs">综合评分</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-blue-500 font-semibold">{{ detailData.staff.order_count || 0 }}</div>
                                    <div class="text-gray-400 text-xs">服务次数</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-green-500 font-semibold">{{ detailData.staff.review_count || 0 }}</div>
                                    <div class="text-gray-400 text-xs">评价数</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-purple-500 font-semibold">{{ detailData.staff.favorite_count || 0 }}</div>
                                    <div class="text-gray-400 text-xs">收藏数</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-card>

                <!-- 作品描述 -->
                <el-card v-if="detailData.description" shadow="hover" class="mb-4">
                    <template #header>
                        <span class="font-semibold">作品描述</span>
                    </template>
                    <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                        {{ detailData.description }}
                    </div>
                </el-card>

                <!-- 作品图片 -->
                <el-card v-if="detailData.images?.length" shadow="hover" class="mb-4">
                    <template #header>
                        <span class="font-semibold">作品图片（{{ detailData.images.length }}张）</span>
                    </template>
                    <div class="grid grid-cols-4 gap-3">
                        <el-image
                            v-for="(img, index) in detailData.images"
                            :key="index"
                            :src="img"
                            fit="cover"
                            class="w-full h-[140px] rounded-lg cursor-pointer"
                            :preview-src-list="detailData.images"
                            :initial-index="index"
                        />
                    </div>
                </el-card>

                <!-- 作品视频 -->
                <el-card v-if="detailData.video" shadow="hover" class="mb-4">
                    <template #header>
                        <span class="font-semibold">作品视频</span>
                    </template>
                    <div class="flex items-center gap-3">
                        <el-icon class="text-2xl text-blue-500"><VideoPlay /></el-icon>
                        <el-link :href="detailData.video" target="_blank" type="primary">
                            点击查看视频
                        </el-link>
                    </div>
                </el-card>

                <!-- 时间信息 -->
                <div class="flex justify-between text-xs text-gray-400 pt-4 border-t">
                    <span>创建时间：{{ detailData.create_time }}</span>
                    <span>更新时间：{{ detailData.update_time }}</span>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="staffWorkLists">
import { ref, reactive } from 'vue'
import { staffAll, staffWorkLists, staffWorkDetail, staffWorkDelete, staffWorkChangeStatus, staffWorkSetCover, staffWorkAudit } from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    staff_id: '',
    title: '',
    type: '',
    audit_status: '',
    is_show: '',
    is_cover: ''
})

const staffOptions = ref<any[]>([])
const detailVisible = ref(false)
const detailData = ref<any>(null)

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: staffWorkLists,
    params: queryParams
})

const getAuditTagType = (status: number) => {
    const map: Record<number, string> = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }
    return map[status] || 'info'
}

const fetchStaffOptions = async () => {
    try {
        staffOptions.value = await staffAll()
    } catch (error) {
        staffOptions.value = []
    }
}

const handleChangeStatus = async (status: number, row: any) => {
    try {
        await staffWorkChangeStatus({ id: row.id, is_show: status })
        getLists()
    } catch (error) {
        getLists()
    }
}

const handleAudit = async (row: any, status: number) => {
    const text = status === 1 ? '通过' : '拒绝'
    await feedback.confirm(`确认${text}该作品？`)
    await staffWorkAudit({ id: row.id, audit_status: status })
    getLists()
}

const handleSetCover = async (row: any) => {
    await feedback.confirm('确认将该作品设为封面？')
    await staffWorkSetCover({ id: row.id })
    getLists()
}

const handleDelete = async (row: any) => {
    await feedback.confirm('确定要删除该作品？')
    await staffWorkDelete({ id: row.id })
    getLists()
}

const openDetail = async (row: any) => {
    detailVisible.value = true
    try {
        detailData.value = await staffWorkDetail({ id: row.id })
    } catch (error) {
        detailData.value = null
    }
}

fetchStaffOptions()
getLists()
</script>

<style lang="scss" scoped></style>
