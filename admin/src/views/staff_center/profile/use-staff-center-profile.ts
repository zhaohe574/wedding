import { computed, reactive, ref } from 'vue'
import type { FormItemRule, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import feedback from '@/utils/feedback'
import { categoryTree, styleTagAll } from '@/api/service'
import {
    myProfile,
    myProfileBannerAdd,
    myProfileBannerConfig,
    myProfileBannerDelete,
    myProfileBannerEdit,
    myProfileBannerList,
    myProfileBannerSort,
    myProfileCreatePackage,
    myProfileDeletePackage,
    myProfilePackageConfig,
    myProfileUpdate,
    myProfileUpdateStaffPackage,
} from '@/api/staff-center'

type RegionPrice = Record<string, unknown>

interface CategoryOption {
    id: number | string
    name: string
    children?: CategoryOption[]
}

interface StyleTagOption {
    id: number | string
    name: string
}

type GroupedTags = Record<string, StyleTagOption[]>

interface StaffCenterProfileResponse {
    id?: number | string
    avatar?: string
    name?: string
    mobile?: string
    category_id?: number | string
    category_name?: string
    price_text?: string
    experience_years?: number | string
    profile?: string
    service_desc?: string
    long_detail?: string
    status?: number | string
    is_recommend?: number | string
    tag_ids?: Array<number | string>
    pending_tag_ids?: Array<number | string>
    pending_tag_names?: string[]
    tag_apply_status?: number | string | null
    tag_apply_status_desc?: string
    tag_apply_reject_reason?: string
    staff_tag_review_enabled?: number | string
    admin_account?: string
    admin_disable?: number | string
    banner_mode?: number | string
    banner_small_height?: number | string
    banner_large_height?: number | string
    banner_indicator_style?: number | string
    banner_autoplay?: number | string
    banner_interval?: number | string
}

interface StaffPackageResponse {
    id?: number | string
    name?: string
    price?: number | string
    original_price?: number | string
    image?: string
    description?: string
    region_prices?: RegionPrice[]
    sort?: number | string
    is_show?: number | string
    is_recommend?: number | string
}

interface BannerResponse {
    id?: number | string
    type?: number | string
    file_url?: string
    cover_url?: string
    is_autoplay?: number | string
}

interface AdminInfo {
    account: string
    disable: number
}

interface TagAuditInfo {
    pending_tag_ids: number[]
    pending_tag_names: string[]
    tag_apply_status: number | null
    tag_apply_status_desc: string
    tag_apply_reject_reason: string
    staff_tag_review_enabled: number
}

export interface StaffProfileFormData {
    id: number
    avatar: string
    name: string
    mobile: string
    category_id: string
    category_name: string
    price_text: string
    experience_years: number
    profile: string
    service_desc: string
    long_detail: string
    status: number
    is_recommend: number
    tag_ids: number[]
}

export interface StaffPackageFormData {
    id: number
    name: string
    price: number
    original_price: number
    image: string
    description: string
    region_prices: RegionPrice[]
    sort: number
    is_show: number
    is_recommend: number
}

export interface BannerConfigFormData {
    banner_mode: number
    banner_small_height: number
    banner_large_height: number
    banner_indicator_style: number
    banner_autoplay: number
    banner_interval: number
}

export interface BannerFormData {
    id: number
    type: number
    file_url: string
    cover_url: string
    is_autoplay: number
}

export interface StaffPackageItem extends StaffPackageFormData {}

export interface BannerItem extends BannerFormData {}

interface SaveProfileResponse {
    tag_action?: string
}

type ProfileUpdateField = keyof Pick<
    StaffProfileFormData,
    'avatar' | 'name' | 'mobile' | 'experience_years' | 'profile' | 'service_desc' | 'long_detail' | 'tag_ids'
>

const toNumber = (value: number | string | null | undefined, fallback = 0) => {
    const current = Number(value)
    return Number.isFinite(current) ? current : fallback
}

const toStringValue = (value: unknown) => (typeof value === 'string' ? value : value == null ? '' : String(value))

const toNumberArray = (value: Array<number | string> | undefined) =>
    Array.isArray(value) ? value.map((item) => toNumber(item)).filter((item) => item > 0) : []

const replaceLastPathSegment = (path: string, target: string) => {
    const segments = path.split('/').filter(Boolean)
    if (!segments.length) {
        return `/${target}`
    }
    segments[segments.length - 1] = target
    return `/${segments.join('/')}`
}

export const staffProfileFormRules: FormRules<StaffProfileFormData> = {
    name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
}

export const staffPackageRules: FormRules<StaffPackageFormData> = {
    name: [{ required: true, message: '请输入套餐名称', trigger: 'blur' }],
    price: [{ required: true, message: '请输入套餐价格', trigger: 'blur' }],
}

const bannerCoverRule: FormItemRule = {
    trigger: 'change',
    validator: (_rule, value, callback) => {
        if (typeof value !== 'string') {
            callback()
            return
        }
        callback()
    },
}

export const bannerFormRules: FormRules<BannerFormData> = {
    type: [{ required: true, message: '请选择类型', trigger: 'change' }],
    file_url: [{ required: true, message: '请上传文件', trigger: 'change' }],
    cover_url: [bannerCoverRule],
}

export function useStaffCenterProfile() {
    const categoryOptions = ref<CategoryOption[]>([])
    const groupedTags = ref<GroupedTags>({})
    const staffPackages = ref<StaffPackageItem[]>([])
    const bannerList = ref<BannerItem[]>([])

    const showStaffPackageDialog = ref(false)
    const showBannerDialog = ref(false)
    const isEditingStaffPackage = ref(false)
    const isEditingBanner = ref(false)
    const saveLoading = ref(false)

    const adminInfo = reactive<AdminInfo>({
        account: '',
        disable: 0,
    })

    const tagAuditInfo = reactive<TagAuditInfo>({
        pending_tag_ids: [],
        pending_tag_names: [],
        tag_apply_status: null,
        tag_apply_status_desc: '',
        tag_apply_reject_reason: '',
        staff_tag_review_enabled: 0,
    })

    const formData = reactive<StaffProfileFormData>({
        id: 0,
        avatar: '',
        name: '',
        mobile: '',
        category_id: '',
        category_name: '',
        price_text: '',
        experience_years: 0,
        profile: '',
        service_desc: '',
        long_detail: '',
        status: 1,
        is_recommend: 0,
        tag_ids: [],
    })

    const staffPackageForm = reactive<StaffPackageFormData>({
        id: 0,
        name: '',
        price: 0,
        original_price: 0,
        image: '',
        description: '',
        region_prices: [],
        sort: 0,
        is_show: 1,
        is_recommend: 0,
    })

    const bannerConfig = reactive<BannerConfigFormData>({
        banner_mode: 1,
        banner_small_height: 400,
        banner_large_height: 600,
        banner_indicator_style: 1,
        banner_autoplay: 1,
        banner_interval: 3000,
    })

    const bannerForm = reactive<BannerFormData>({
        id: 0,
        type: 1,
        file_url: '',
        cover_url: '',
        is_autoplay: 0,
    })

    const tagTypeLabelMap: Record<number, string> = {
        1: '风格',
        2: '特长',
        3: '其他',
    }

    const tagReviewAlert = computed(() => {
        if (tagAuditInfo.staff_tag_review_enabled !== 1) {
            return {
                type: 'info' as const,
                title: '当前标签保存后立即生效。',
            }
        }
        if (tagAuditInfo.tag_apply_status === 0) {
            return {
                type: 'warning' as const,
                title: '当前存在待审核标签申请，新的保存会覆盖原待审内容。',
            }
        }
        if (tagAuditInfo.tag_apply_status === 2) {
            const reason = tagAuditInfo.tag_apply_reject_reason
                ? `拒绝原因：${tagAuditInfo.tag_apply_reject_reason}`
                : '请调整后重新提交。'
            return {
                type: 'error' as const,
                title: `上次标签申请未通过审核。${reason}`,
            }
        }

        return {
            type: 'info' as const,
            title: '当前标签保存后需管理员审核通过才会生效。',
        }
    })

    const syncSelectedTags = (tagsByType: GroupedTags) => {
        const availableIds = new Set<number>()
        Object.values(tagsByType).forEach((list) => {
            list.forEach((tag) => {
                availableIds.add(toNumber(tag.id))
            })
        })
        if (formData.tag_ids.length) {
            formData.tag_ids = formData.tag_ids.filter((id) => availableIds.has(id))
        }
    }

    const findCategoryName = (options: CategoryOption[], targetId: number): string => {
        for (const option of options) {
            if (toNumber(option.id) === targetId) {
                return option.name || ''
            }
            if (Array.isArray(option.children) && option.children.length) {
                const childName = findCategoryName(option.children, targetId)
                if (childName) {
                    return childName
                }
            }
        }
        return ''
    }

    const resolveCategoryName = (categoryId: string | number) => {
        const currentId = toNumber(categoryId)
        if (!currentId) {
            return ''
        }
        return findCategoryName(categoryOptions.value, currentId)
    }

    const resetStaffPackageForm = () => {
        Object.assign(staffPackageForm, {
            id: 0,
            name: '',
            price: 0,
            original_price: 0,
            image: '',
            description: '',
            region_prices: [],
            sort: 0,
            is_show: 1,
            is_recommend: 0,
        })
        isEditingStaffPackage.value = false
    }

    const resetBannerForm = () => {
        Object.assign(bannerForm, {
            id: 0,
            type: 1,
            file_url: '',
            cover_url: '',
            is_autoplay: 0,
        })
        isEditingBanner.value = false
    }

    const loadCategoryOptions = async () => {
        const result = await categoryTree()
        categoryOptions.value = Array.isArray(result) ? (result as CategoryOption[]) : []
        if (!formData.category_name && formData.category_id) {
            formData.category_name = resolveCategoryName(formData.category_id)
        }
    }

    const loadTags = async () => {
        const categoryId = toNumber(formData.category_id)
        if (!categoryId) {
            groupedTags.value = {}
            formData.tag_ids = []
            return
        }
        const result = await styleTagAll({ group_by_type: 1, category_id: categoryId })
        groupedTags.value = result && typeof result === 'object' ? (result as GroupedTags) : {}
        syncSelectedTags(groupedTags.value)
    }

    const loadProfile = async () => {
        const data = (await myProfile()) as StaffCenterProfileResponse
        formData.id = toNumber(data.id)
        formData.avatar = toStringValue(data.avatar)
        formData.name = toStringValue(data.name)
        formData.mobile = toStringValue(data.mobile)
        formData.category_id = data.category_id ? String(data.category_id) : ''
        formData.category_name = toStringValue(data.category_name) || resolveCategoryName(formData.category_id)
        formData.price_text = toStringValue(data.price_text)
        formData.experience_years = toNumber(data.experience_years)
        formData.profile = toStringValue(data.profile)
        formData.service_desc = toStringValue(data.service_desc)
        formData.long_detail = toStringValue(data.long_detail)
        formData.status = toNumber(data.status, 1)
        formData.is_recommend = toNumber(data.is_recommend)

        tagAuditInfo.pending_tag_ids = toNumberArray(data.pending_tag_ids)
        tagAuditInfo.pending_tag_names = Array.isArray(data.pending_tag_names) ? data.pending_tag_names : []
        tagAuditInfo.tag_apply_status = data.tag_apply_status == null ? null : toNumber(data.tag_apply_status)
        tagAuditInfo.tag_apply_status_desc = toStringValue(data.tag_apply_status_desc)
        tagAuditInfo.tag_apply_reject_reason = toStringValue(data.tag_apply_reject_reason)
        tagAuditInfo.staff_tag_review_enabled = toNumber(data.staff_tag_review_enabled)

        const effectiveTagIds = toNumberArray(data.tag_ids)
        formData.tag_ids = tagAuditInfo.pending_tag_ids.length ? [...tagAuditInfo.pending_tag_ids] : effectiveTagIds

        adminInfo.account = toStringValue(data.admin_account)
        adminInfo.disable = toNumber(data.admin_disable)

        bannerConfig.banner_mode = toNumber(data.banner_mode, 1)
        bannerConfig.banner_small_height = toNumber(data.banner_small_height, 400)
        bannerConfig.banner_large_height = toNumber(data.banner_large_height, 600)
        bannerConfig.banner_indicator_style = toNumber(data.banner_indicator_style, 1)
        bannerConfig.banner_autoplay = toNumber(data.banner_autoplay, 1)
        bannerConfig.banner_interval = toNumber(data.banner_interval, 3000)
    }

    const loadPackageConfig = async () => {
        const result = await myProfilePackageConfig()
        const packageList = Array.isArray(result) ? (result as StaffPackageResponse[]) : []
        staffPackages.value = packageList.map((item) => ({
            id: toNumber(item.id),
            name: toStringValue(item.name),
            price: toNumber(item.price),
            original_price: toNumber(item.original_price),
            image: toStringValue(item.image),
            description: toStringValue(item.description),
            region_prices: Array.isArray(item.region_prices) ? item.region_prices : [],
            sort: toNumber(item.sort),
            is_show: toNumber(item.is_show, 1),
            is_recommend: toNumber(item.is_recommend),
        }))
    }

    const loadBannerList = async () => {
        const result = await myProfileBannerList()
        const list = Array.isArray(result) ? (result as BannerResponse[]) : []
        bannerList.value = list.map((item) => ({
            id: toNumber(item.id),
            type: toNumber(item.type, 1),
            file_url: toStringValue(item.file_url),
            cover_url: toStringValue(item.cover_url),
            is_autoplay: toNumber(item.is_autoplay),
        }))
    }

    const saveProfile = async (fields: ProfileUpdateField[]) => {
        const payload = fields.reduce<Record<string, unknown>>((current, field) => {
            current[field] = field === 'tag_ids' ? [...formData.tag_ids] : formData[field]
            return current
        }, {})

        saveLoading.value = true
        try {
            const response = (await myProfileUpdate(payload)) as SaveProfileResponse
            ElMessage.success(response?.tag_action === 'pending' ? '资料已保存，标签修改已提交审核' : '保存成功')
            return response
        } finally {
            saveLoading.value = false
        }
    }

    const openCreateStaffPackage = () => {
        resetStaffPackageForm()
        showStaffPackageDialog.value = true
    }

    const openEditStaffPackage = (row: StaffPackageItem) => {
        resetStaffPackageForm()
        isEditingStaffPackage.value = true
        Object.assign(staffPackageForm, {
            id: toNumber(row.id),
            name: row.name,
            price: toNumber(row.price),
            original_price: toNumber(row.original_price),
            image: row.image,
            description: row.description,
            region_prices: Array.isArray(row.region_prices) ? row.region_prices : [],
            sort: toNumber(row.sort),
            is_show: toNumber(row.is_show, 1),
            is_recommend: toNumber(row.is_recommend),
        })
        showStaffPackageDialog.value = true
    }

    const submitStaffPackage = async () => {
        const payload = {
            name: staffPackageForm.name,
            price: Number(staffPackageForm.price ?? 0),
            original_price: staffPackageForm.original_price ?? null,
            image: staffPackageForm.image,
            description: staffPackageForm.description,
            region_prices: [...staffPackageForm.region_prices],
            sort: Number(staffPackageForm.sort ?? 0),
            is_show: Number(staffPackageForm.is_show ?? 1),
            is_recommend: Number(staffPackageForm.is_recommend ?? 0),
        }

        if (isEditingStaffPackage.value) {
            await myProfileUpdateStaffPackage({
                package_id: staffPackageForm.id,
                ...payload,
            })
            ElMessage.success('更新成功')
        } else {
            await myProfileCreatePackage(payload)
            ElMessage.success('创建成功')
        }

        showStaffPackageDialog.value = false
        resetStaffPackageForm()
        await loadPackageConfig()
    }

    const deleteStaffPackage = async (row: StaffPackageItem) => {
        await feedback.confirm(`确定要删除专属套餐「${row.name || ''}」吗？`)
        await myProfileDeletePackage({ package_id: row.id })
        ElMessage.success('删除成功')
        await loadPackageConfig()
    }

    const openAddBanner = () => {
        resetBannerForm()
        showBannerDialog.value = true
    }

    const openEditBanner = (row: BannerItem) => {
        resetBannerForm()
        isEditingBanner.value = true
        Object.assign(bannerForm, {
            id: toNumber(row.id),
            type: toNumber(row.type, 1),
            file_url: row.file_url,
            cover_url: row.cover_url,
            is_autoplay: toNumber(row.is_autoplay),
        })
        showBannerDialog.value = true
    }

    const submitBanner = async () => {
        const payload = {
            ...bannerForm,
        }
        if (bannerForm.type === 2 && !bannerForm.cover_url) {
            ElMessage.warning('视频需要上传封面图')
            return
        }

        if (isEditingBanner.value) {
            await myProfileBannerEdit(payload)
            ElMessage.success('编辑成功')
        } else {
            await myProfileBannerAdd(payload)
            ElMessage.success('添加成功')
        }

        showBannerDialog.value = false
        await loadBannerList()
    }

    const deleteBanner = async (row: BannerItem) => {
        await feedback.confirm('确定要删除该轮播图吗？')
        await myProfileBannerDelete({ id: row.id })
        ElMessage.success('删除成功')
        await loadBannerList()
    }

    const moveBanner = async (index: number, direction: 'up' | 'down') => {
        const list = [...bannerList.value]
        const targetIndex = direction === 'up' ? index - 1 : index + 1
        if (targetIndex < 0 || targetIndex >= list.length) {
            return
        }

        ;[list[index], list[targetIndex]] = [list[targetIndex], list[index]]
        const sortData = list.map((item, currentIndex) => ({
            id: item.id,
            sort: currentIndex,
        }))

        await myProfileBannerSort({ sort_data: sortData })
        ElMessage.success('排序成功')
        await loadBannerList()
    }

    const saveBannerConfig = async () => {
        await myProfileBannerConfig({ ...bannerConfig })
        ElMessage.success('配置保存成功')
    }

    const initializeBasicPage = async () => {
        await loadCategoryOptions()
        await loadProfile()
    }

    const initializeShowcasePage = async () => {
        await loadCategoryOptions()
        await loadProfile()
        await loadTags()
        await loadBannerList()
    }

    const initializePackagePage = async () => {
        await loadProfile()
        await loadPackageConfig()
    }

    return {
        adminInfo,
        bannerConfig,
        bannerForm,
        bannerList,
        categoryOptions,
        deleteBanner,
        deleteStaffPackage,
        formData,
        groupedTags,
        initializeBasicPage,
        initializePackagePage,
        initializeShowcasePage,
        isEditingBanner,
        isEditingStaffPackage,
        loadBannerList,
        loadPackageConfig,
        loadProfile,
        moveBanner,
        openAddBanner,
        openCreateStaffPackage,
        openEditBanner,
        openEditStaffPackage,
        replaceLastPathSegment,
        resetBannerForm,
        resetStaffPackageForm,
        saveBannerConfig,
        saveLoading,
        saveProfile,
        showBannerDialog,
        showStaffPackageDialog,
        staffPackageForm,
        staffPackages,
        submitBanner,
        submitStaffPackage,
        tagAuditInfo,
        tagReviewAlert,
        tagTypeLabelMap,
    }
}
