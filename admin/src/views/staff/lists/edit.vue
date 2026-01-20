<template>
    <div class="staff-edit">
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="$route.query.id ? '编辑人员' : '新增人员'" @back="$router.back()" />
        </el-card>
        <el-card class="mt-4 !border-none" shadow="never">
            <el-form
                ref="formRef"
                class="ls-form"
                :model="formData"
                label-width="100px"
                :rules="rules"
            >
                <el-tabs v-model="activeTab">
                    <el-tab-pane label="基本信息" name="basic">
                        <div class="grid grid-cols-2 gap-x-10">
                            <el-form-item label="姓名" prop="name">
                                <el-input v-model="formData.name" placeholder="请输入姓名" maxlength="50" />
                            </el-form-item>
                            <el-form-item label="手机号" prop="mobile">
                                <el-input v-model="formData.mobile" placeholder="请输入手机号" maxlength="11" />
                            </el-form-item>
                            <el-form-item label="头像" prop="avatar">
                                <material-picker v-model="formData.avatar" :limit="1" />
                            </el-form-item>
                            <el-form-item label="服务分类" prop="category_id">
                                <el-cascader
                                    v-model="formData.category_id"
                                    :options="optionsData.categories"
                                    :props="{ value: 'id', label: 'name', checkStrictly: true, emitPath: false }"
                                    placeholder="选择服务分类"
                                    class="w-full"
                                />
                            </el-form-item>
                            <el-form-item label="服务价格" prop="price">
                                <el-input-number v-model="formData.price" :min="0" :precision="2" class="w-full" />
                            </el-form-item>
                            <el-form-item label="从业年限" prop="experience_years">
                                <el-input-number v-model="formData.experience_years" :min="0" :max="50" class="w-full" />
                            </el-form-item>
                            <el-form-item label="排序" prop="sort">
                                <el-input-number v-model="formData.sort" :min="0" :max="9999" class="w-full" />
                            </el-form-item>
                            <el-form-item label="是否推荐" prop="is_recommend">
                                <el-radio-group v-model="formData.is_recommend">
                                    <el-radio :value="1">是</el-radio>
                                    <el-radio :value="0">否</el-radio>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="状态" prop="status">
                                <el-radio-group v-model="formData.status">
                                    <el-radio :value="1">启用</el-radio>
                                    <el-radio :value="0">禁用</el-radio>
                                </el-radio-group>
                            </el-form-item>
                        </div>
                        <el-form-item label="个人简介" prop="profile">
                            <el-input
                                v-model="formData.profile"
                                type="textarea"
                                :rows="3"
                                placeholder="请输入个人简介"
                                maxlength="500"
                                show-word-limit
                            />
                        </el-form-item>
                        <el-form-item label="服务说明" prop="service_desc">
                            <el-input
                                v-model="formData.service_desc"
                                type="textarea"
                                :rows="3"
                                placeholder="请输入服务说明"
                                maxlength="1000"
                                show-word-limit
                            />
                        </el-form-item>
                    </el-tab-pane>

                    <el-tab-pane label="标签设置" name="tags">
                        <el-form-item label="风格标签">
                            <el-checkbox-group v-model="formData.tag_ids">
                                <template v-for="(tags, type) in groupedTags" :key="type">
                                    <div class="mb-4">
                                        <div class="text-gray-500 mb-2">{{ type }}</div>
                                        <el-checkbox
                                            v-for="tag in tags"
                                            :key="tag.id"
                                            :value="tag.id"
                                            :label="tag.name"
                                        />
                                    </div>
                                </template>
                            </el-checkbox-group>
                        </el-form-item>
                    </el-tab-pane>

                    <el-tab-pane label="服务套餐" name="packages">
                        <el-form-item label="可提供套餐">
                            <el-table :data="formData.packages" border>
                                <el-table-column label="套餐名称" prop="package_name" />
                                <el-table-column label="默认价格" prop="default_price" width="120" />
                                <el-table-column label="自定义价格" width="150">
                                    <template #default="{ row }">
                                        <el-input-number
                                            v-model="row.price"
                                            :min="0"
                                            :precision="2"
                                            size="small"
                                            controls-position="right"
                                        />
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="80">
                                    <template #default="{ $index }">
                                        <el-button type="danger" link @click="removePackage($index)">移除</el-button>
                                    </template>
                                </el-table-column>
                            </el-table>
                            <el-button class="mt-4" @click="showPackageDialog = true">添加套餐</el-button>
                        </el-form-item>
                    </el-tab-pane>
                </el-tabs>
            </el-form>
        </el-card>
        <footer-btns>
            <el-button type="primary" @click="handleSave">保存</el-button>
        </footer-btns>

        <!-- 套餐选择弹窗 -->
        <el-dialog v-model="showPackageDialog" title="选择套餐" width="600px">
            <el-table
                :data="availablePackages"
                @selection-change="handlePackageSelect"
                ref="packageTableRef"
            >
                <el-table-column type="selection" width="55" />
                <el-table-column label="套餐名称" prop="name" />
                <el-table-column label="分类" prop="category_name" />
                <el-table-column label="价格" prop="price" width="100">
                    <template #default="{ row }">¥{{ row.price }}</template>
                </el-table-column>
            </el-table>
            <template #footer>
                <el-button @click="showPackageDialog = false">取消</el-button>
                <el-button type="primary" @click="confirmPackageSelect">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="staffListsEdit">
import type { FormInstance } from 'element-plus'
import { staffAdd, staffEdit, staffDetail } from '@/api/staff'
import { categoryTree, styleTagAll, packageAll } from '@/api/service'
import { useDictOptions } from '@/hooks/useDictOptions'
import useMultipleTabs from '@/hooks/useMultipleTabs'

const route = useRoute()
const router = useRouter()
const { removeTab } = useMultipleTabs()
const formRef = shallowRef<FormInstance>()
const activeTab = ref('basic')
const showPackageDialog = ref(false)
const selectedPackages = ref<any[]>([])
const groupedTags = ref<Record<string, any[]>>({})

const formData = reactive({
    id: '',
    name: '',
    avatar: '',
    mobile: '',
    category_id: '',
    price: 0,
    experience_years: 0,
    profile: '',
    service_desc: '',
    sort: 0,
    is_recommend: 0,
    status: 1,
    tag_ids: [] as number[],
    packages: [] as any[]
})

const rules = reactive({
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    category_id: [{ required: true, message: '请选择服务分类', trigger: 'change' }],
    price: [{ required: true, message: '请输入服务价格', trigger: 'blur' }]
})

const { optionsData } = useDictOptions<{
    categories: any[]
    packages: any[]
}>({
    categories: {
        api: categoryTree
    },
    packages: {
        api: packageAll
    }
})

// 可用套餐（未添加的）
const availablePackages = computed(() => {
    const addedIds = formData.packages.map(p => p.package_id)
    return optionsData.packages?.filter(p => !addedIds.includes(p.id)) || []
})

// 获取标签列表（分组）
const getTags = async () => {
    const res = await styleTagAll({ group_by_type: 1 })
    groupedTags.value = res
}

// 获取详情
const getDetails = async () => {
    const data = await staffDetail({ id: route.query.id })
    Object.keys(formData).forEach((key) => {
        if (data[key] !== undefined) {
            (formData as any)[key] = data[key]
        }
    })
}

const handlePackageSelect = (selection: any[]) => {
    selectedPackages.value = selection
}

const confirmPackageSelect = () => {
    selectedPackages.value.forEach(pkg => {
        formData.packages.push({
            package_id: pkg.id,
            package_name: pkg.name,
            default_price: pkg.price,
            price: pkg.price
        })
    })
    showPackageDialog.value = false
    selectedPackages.value = []
}

const removePackage = (index: number) => {
    formData.packages.splice(index, 1)
}

const handleSave = async () => {
    await formRef.value?.validate()
    const params = {
        ...formData,
        packages: formData.packages.map(p => ({
            package_id: p.package_id,
            price: p.price
        }))
    }
    if (route.query.id) {
        await staffEdit(params)
    } else {
        await staffAdd(params)
    }
    removeTab()
    router.back()
}

getTags()
if (route.query.id) {
    getDetails()
}
</script>
