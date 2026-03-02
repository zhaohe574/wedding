<template>
    <div class="staff-center-dynamic">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[150px]" label="动态类型">
                    <el-select v-model="queryParams.dynamic_type" placeholder="选择类型" clearable>
                        <el-option label="图文" :value="1" />
                        <el-option label="视频" :value="2" />
                        <el-option label="活动" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="待审核" :value="0" />
                        <el-option label="已发布" :value="1" />
                        <el-option label="已下架" :value="2" />
                        <el-option label="已拒绝" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[220px]" label="内容">
                    <el-input v-model="queryParams.content" placeholder="搜索内容" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <el-button type="primary" @click="handleAdd">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        发布动态
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="发布者" min-width="160">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <el-avatar :src="row.publisher?.avatar" :size="32" />
                            <div class="ml-2">
                                <div>{{ row.publisher?.nickname || '-' }}</div>
                                <div class="text-gray-400 text-xs">{{ row.user_type_desc }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="90">
                    <template #default="{ row }">
                        <el-tag size="small">{{ row.type_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="内容" min-width="240" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div v-if="row.title" class="font-bold">{{ row.title }}</div>
                        <div class="text-gray-500">{{ row.content }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="图片" width="100">
                    <template #default="{ row }">
                        <div v-if="row.images && row.images.length > 0" class="flex gap-1">
                            <el-image :src="row.images[0]" style="width: 40px; height: 40px" fit="cover" :preview-src-list="row.images" />
                            <span v-if="row.images.length > 1" class="text-gray-400">+{{ row.images.length - 1 }}</span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="互动" width="160">
                    <template #default="{ row }">
                        <div class="text-xs">浏览 {{ row.view_count }} / 点赞 {{ row.like_count }}</div>
                        <div class="text-xs">评论 {{ row.comment_count }} / 收藏 {{ row.collect_count }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="发布时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <edit-popup ref="editRef" @success="handleSuccess" />

        <el-dialog v-model="detailVisible" title="动态详情" width="700px">
            <div v-if="currentDynamic">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="发布者">{{ currentDynamic.publisher?.nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="发布者类型">{{ currentDynamic.user_type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="动态类型">{{ currentDynamic.type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getStatusType(currentDynamic.status)">
                            {{ currentDynamic.status_desc }}
                        </el-tag>
                    </el-descriptions-item>
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
                    <video :src="currentDynamic.video_url" controls style="max-width: 420px" />
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="staffCenterDynamic">
import { onActivated, reactive, ref, shallowRef } from 'vue'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import EditPopup from './edit.vue'
import { myDynamicDelete, myDynamicDetail, myDynamics } from '@/api/staff-center'

const editRef = shallowRef<InstanceType<typeof EditPopup>>()

const queryParams = reactive({
    dynamic_type: '',
    status: '',
    content: ''
})

const detailVisible = ref(false)
const currentDynamic = ref<any>(null)

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: myDynamics,
    params: queryParams
})

const getStatusType = (status: number): 'warning' | 'success' | 'info' | 'danger' | 'primary' => {
    const types: Record<number, 'warning' | 'success' | 'info' | 'danger' | 'primary'> = {
        0: 'warning',
        1: 'success',
        2: 'info',
        3: 'danger'
    }
    return types[status] || 'info'
}

const handleDetail = async (row: any) => {
    currentDynamic.value = await myDynamicDetail({ id: row.id })
    detailVisible.value = true
}

const handleDelete = async (row: any) => {
    await feedback.confirm('确定要删除该动态吗？')
    await myDynamicDelete({ id: row.id })
    feedback.msgSuccess('删除成功')
    getLists()
}

const handleAdd = () => {
    editRef.value?.open('add')
}

const handleEdit = async (row: any) => {
    editRef.value?.open('edit')
    await editRef.value?.getDetail(row)
}

const handleSuccess = () => {
    getLists()
}

onActivated(() => {
    getLists()
})

getLists()
</script>
