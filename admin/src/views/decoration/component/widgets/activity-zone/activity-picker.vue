<template>
    <el-dialog
        v-model="visible"
        title="选择活动"
        width="800px"
        :close-on-click-modal="false"
    >
        <div class="activity-picker">
            <!-- 搜索栏 -->
            <div class="mb-4">
                <el-input
                    v-model="searchTitle"
                    placeholder="搜索活动标题"
                    clearable
                    @clear="handleSearch"
                    @keyup.enter="handleSearch"
                >
                    <template #append>
                        <el-button :icon="Search" @click="handleSearch" />
                    </template>
                </el-input>
            </div>

            <!-- 活动列表 -->
            <div class="activity-list max-h-[500px] overflow-y-auto">
                <el-checkbox-group v-model="selectedIds">
                    <div
                        v-for="item in activityList"
                        :key="item.id"
                        class="activity-item flex items-start p-3 mb-2 border border-gray-200 rounded-lg hover:border-primary cursor-pointer"
                        @click="toggleSelect(item.id)"
                    >
                        <el-checkbox :value="item.id" class="mr-3" @click.stop />
                        <div class="flex-shrink-0 mr-3">
                            <el-image
                                :src="item.cover_image"
                                fit="cover"
                                class="w-20 h-20 rounded"
                            >
                                <template #error>
                                    <div class="w-20 h-20 flex items-center justify-center bg-gray-100 rounded">
                                        <icon name="el-icon-Picture" :size="30" color="#ccc" />
                                    </div>
                                </template>
                            </el-image>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center mb-1">
                                <span class="text-sm font-medium text-gray-900 truncate">{{ item.title }}</span>
                                <el-tag v-if="item.is_top" type="danger" size="small" class="ml-2">置顶</el-tag>
                                <el-tag v-if="item.is_hot" type="warning" size="small" class="ml-2">热门</el-tag>
                            </div>
                            <div class="text-xs text-gray-500 mb-1 line-clamp-2">{{ item.content_preview }}</div>
                            <div class="flex items-center text-xs text-gray-400">
                                <span class="mr-3">浏览 {{ item.view_count }}</span>
                                <span class="mr-3">点赞 {{ item.like_count }}</span>
                                <span>{{ item.create_time_text }}</span>
                            </div>
                            <div v-if="item.tags_arr.length" class="mt-1">
                                <el-tag
                                    v-for="tag in item.tags_arr.slice(0, 3)"
                                    :key="tag"
                                    size="small"
                                    class="mr-1"
                                >
                                    {{ tag }}
                                </el-tag>
                            </div>
                        </div>
                    </div>
                </el-checkbox-group>

                <!-- 空状态 -->
                <el-empty v-if="!activityList.length && !loading" description="暂无活动数据" />
            </div>

            <!-- 分页 -->
            <div class="mt-4 flex justify-center">
                <el-pagination
                    v-model:current-page="pager.page"
                    v-model:page-size="pager.limit"
                    :total="pager.total"
                    :page-sizes="[10, 20, 50]"
                    layout="total, sizes, prev, pager, next"
                    @current-change="getActivityList"
                    @size-change="getActivityList"
                />
            </div>
        </div>

        <template #footer>
            <span class="dialog-footer">
                <el-button @click="visible = false">取消</el-button>
                <el-button type="primary" @click="handleConfirm">
                    确定（已选 {{ selectedIds.length }} 个）
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { ref, reactive } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { getDecorateActivityList } from '@/api/decoration'
import { ElMessage } from 'element-plus'

const emit = defineEmits(['confirm'])

const visible = ref(false)
const loading = ref(false)
const searchTitle = ref('')
const activityList = ref<any[]>([])
const selectedIds = ref<number[]>([])

const pager = reactive({
    page: 1,
    limit: 10,
    total: 0
})

// 打开选择器
const open = (ids: number[] = []) => {
    visible.value = true
    selectedIds.value = [...ids]
    getActivityList()
}

// 获取活动列表
const getActivityList = async () => {
    try {
        loading.value = true
        const res = await getDecorateActivityList({
            page: pager.page,
            limit: pager.limit,
            title: searchTitle.value
        })
        activityList.value = res.data || []
        pager.total = res.total || 0
    } catch (error) {
        console.error('获取活动列表失败:', error)
        ElMessage.error('获取活动列表失败')
    } finally {
        loading.value = false
    }
}

// 搜索
const handleSearch = () => {
    pager.page = 1
    getActivityList()
}

// 切换选择
const toggleSelect = (id: number) => {
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
        selectedIds.value.splice(index, 1)
    } else {
        selectedIds.value.push(id)
    }
}

// 确认选择
const handleConfirm = () => {
    emit('confirm', selectedIds.value)
    visible.value = false
}

defineExpose({
    open
})
</script>

<style lang="scss" scoped>
.activity-picker {
    .activity-item {
        transition: all 0.2s;
        
        &:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
    }
}
</style>
