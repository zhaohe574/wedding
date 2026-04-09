import { ref, shallowRef } from 'vue'

export const useAftersaleListPage = () => {
    const paging = shallowRef()
    const dataList = ref<any[]>([])
    const currentStatus = ref<number | string>('')

    const changeStatus = (status: number | string) => {
        currentStatus.value = status
        paging.value?.reload()
    }

    const applyQueryStatus = (params: Record<string, any>) => {
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }
        return params
    }

    const initStatus = (status: unknown) => {
        if (status === undefined || status === null || status === '') {
            return
        }
        currentStatus.value = Number(status)
    }

    return {
        paging,
        dataList,
        currentStatus,
        changeStatus,
        applyQueryStatus,
        initStatus
    }
}
