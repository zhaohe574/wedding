<template>
    <div class="dynamic-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[150px]" label="动态类型">
                    <el-select v-model="queryParams.dynamic_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="图文" :value="1" />
                        <el-option label="视频" :value="2" />
                        <el-option label="案例" :value="3" />
                        <el-option label="活动" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待审核" :value="0" />
                        <el-option label="已发布" :value="1" />
                        <el-option label="已下架" :value="2" />
                        <el-option label="已拒绝" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="发布者类型">
                    <el-select v-model="queryParams.user_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="用户" :value="1" />
                        <el-option label="工作人员" :value="2" />
                        <el-option label="官方" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[200px]" label="内容">
                    <el-input
                        v-model="queryParams.content"
                        placeholder="搜索内容"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-4 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待审核</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已发布</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已下架</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已拒绝</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="发布者" min-width="150">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.publisher">
                            <el-avatar :src="row.publisher.avatar" :size="32" />
                            <div class="ml-2">
                                <div>{{ row.publisher.nickname }}</div>
                                <div class="text-gray-400 text-xs">{{ row.user_type_desc }}</div>
                            </div>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="80">
                    <template #default="{ row }">
                        <el-tag size="small">{{ row.type_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="内容" min-width="200" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div v-if="row.title" class="font-bold">{{ row.title }}</div>
                        <div class="text-gray-500">{{ row.content }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="图片" width="100">
                    <template #default="{ row }">
                        <div v-if="row.images && row.images.length > 0" class="flex gap-1">
                            <el-image 
                                :src="row.images[0]" 
                                style="width: 40px; height: 40px"
                                fit="cover"
                                :preview-src-list="row.images"
                            />
                            <span v-if="row.images.length > 1" class="text-gray-400">
                                +{{ row.images.length - 1 }}
                            </span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="互动数据" width="150">
                    <template #default="{ row }">
                        <div class="text-xs">
                            <span class="mr-2">浏览: {{ row.view_count }}</span>
                            <span class="mr-2">点赞: {{ row.like_count }}</span>
                        </div>
                        <div class="text-xs">
                            <span class="mr-2">评论: {{ row.comment_count }}</span>
                            <span>收藏: {{ row.collect_count }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="置顶/热门" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_top" type="warning" size="small">置顶</el-tag>
                        <el-tag v-if="row.is_hot" type="danger" size="small" class="ml-1">热门</el-tag>
                        <span v-if="!row.is_top && !row.is_hot">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="发布时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.status === 0" 
                            type="success" 
                            link 
                            @click="handleAudit(row, true)"
                        >通过</el-button>
                        <el-button 
                            v-if="row.status === 0" 
                            type="danger" 
                            link 
                            @click="handleAudit(row, false)"
                        >拒绝</el-button>
                        <el-button 
                            v-if="row.status === 1" 
                            type="warning" 
                            link 
                            @click="handleOffline(row)"
                        >下架</el-button>
                        <el-dropdown v-if="row.status === 1" trigger="click" class="ml-2">
                            <el-button type="primary" link>更多</el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item @click="handleSetTop(row)">
                                        {{ row.is_top ? '取消置顶' : '设为置顶' }}
                                    </el-dropdown-item>
                                    <el-dropdown-item @click="handleSetHot(row)">
                                        {{ row.is_hot ? '取消热门' : '设为热门' }}
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                        <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="动态详情" width="700px">
            <div v-if="currentDynamic">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="发布者">
                        {{ currentDynamic.publisher?.nickname || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="发布者类型">{{ currentDynamic.user_type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="动态类型">{{ currentDynamic.type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getStatusType(currentDynamic.status)">
                            {{ currentDynamic.status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="标题" :span="2">{{ currentDynamic.title || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="内容" :span="2">{{ currentDynamic.content }}</el-descriptions-item>
                    <el-descriptions-item label="位置" :span="2">{{ currentDynamic.location || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="标签" :span="2">{{ currentDynamic.tags || '-' }}</el-descriptions-item>
                </el-descriptions>

                <div class="mt-4" v-if="currentDynamic.images && currentDynamic.images.length > 0">
                    <h4 class="font-bold mb-2">图片</h4>
                    <div class="flex flex-wrap gap-2">
                        <el-image
                            v-for="(img, idx) in currentDynamic.images"
                            :key="idx"
                            :src="img"
                            style="width: 100px; height: 100px"
                            fit="cover"
                            :preview-src-list="currentDynamic.images"
                            :initial-index="idx"
                        />
                    </div>
                </div>

                <div class="mt-4" v-if="currentDynamic.video_url">
                    <h4 class="font-bold mb-2">视频</h4>
                    <video :src="currentDynamic.video_url" controls style="max-width: 400px" />
                </div>

                <div class="mt-4">
                    <h4 class="font-bold mb-2">互动数据</h4>
                    <el-descriptions :column="5">
                        <el-descriptions-item label="浏览">{{ currentDynamic.view_count }}</el-descriptions-item>
                        <el-descriptions-item label="点赞">{{ currentDynamic.like_count }}</el-descriptions-item>
                        <el-descriptions-item label="评论">{{ currentDynamic.comment_count }}</el-descriptions-item>
                        <el-descriptions-item label="收藏">{{ currentDynamic.collect_count }}</el-descriptions-item>
                        <el-descriptions-item label="分享">{{ currentDynamic.share_count }}</el-descriptions-item>
                    </el-descriptions>
                </div>
            </div>
        </el-dialog>

        <!-- 审核弹窗 -->
        <el-dialog v-model="auditVisible" :title="auditForm.approved ? '审核通过' : '审核拒绝'" width="500px">
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="审核备注">
                    <el-input 
                        v-model="auditForm.remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入审核备注（可选）"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button :type="auditForm.approved ? 'success' : 'danger'" @click="submitAudit">
                    {{ auditForm.approved ? '确认通过' : '确认拒绝' }}
                </el-button>
            </template>
        </el-dialog>

        <!-- 下架弹窗 -->
        <el-dialog v-model="offlineVisible" title="下架动态" width="500px">
            <el-form :model="offlineForm" label-width="100px">
                <el-form-item label="下架原因">
                    <el-input 
                        v-model="offlineForm.reason" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入下架原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="offlineVisible = false">取消</el-button>
                <el-button type="warning" @click="submitOffline">确认下架</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="dynamicLists">
import { 
    dynamicLists, 
    dynamicDetail, 
    dynamicStatistics, 
    dynamicAudit, 
    dynamicOffline,
    dynamicSetTop,
    dynamicSetHot,
    dynamicDelete
} from '@/api/dynamic'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    dynamic_type: '',
    status: '',
    user_type: '',
    content: ''
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentDynamic = ref<any>(null)
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    approved: true,
    remark: ''
})
const offlineVisible = ref(false)
const offlineForm = reactive({
    id: 0,
    reason: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: dynamicLists,
    params: queryParams
})

const getStatistics = async () => {
    const res = await dynamicStatistics()
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}

const getStatusType = (status: number) => {
    const types: Record<number, string> = {
        0: 'warning',
        1: 'success',
        2: 'info',
        3: 'danger'
    }
    return types[status] || 'info'
}

const handleDetail = async (row: any) => {
    const res = await dynamicDetail({ id: row.id })
    currentDynamic.value = res
    detailVisible.value = true
}

const handleAudit = (row: any, approved: boolean) => {
    auditForm.id = row.id
    auditForm.approved = approved
    auditForm.remark = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    await dynamicAudit(auditForm)
    feedback.msgSuccess('审核成功')
    auditVisible.value = false
    getLists()
    getStatistics()
}

const handleOffline = (row: any) => {
    offlineForm.id = row.id
    offlineForm.reason = ''
    offlineVisible.value = true
}

const submitOffline = async () => {
    await dynamicOffline(offlineForm)
    feedback.msgSuccess('下架成功')
    offlineVisible.value = false
    getLists()
    getStatistics()
}

const handleSetTop = async (row: any) => {
    await dynamicSetTop({ id: row.id, is_top: row.is_top ? 0 : 1 })
    feedback.msgSuccess('设置成功')
    getLists()
}

const handleSetHot = async (row: any) => {
    await dynamicSetHot({ id: row.id, is_hot: row.is_hot ? 0 : 1 })
    feedback.msgSuccess('设置成功')
    getLists()
}

const handleDelete = async (row: any) => {
    await feedback.confirm('确定要删除该动态吗？')
    await dynamicDelete({ id: row.id })
    feedback.msgSuccess('删除成功')
    getLists()
    getStatistics()
}

onActivated(() => {
    getLists()
    getStatistics()
})

getLists()
getStatistics()
</script>
