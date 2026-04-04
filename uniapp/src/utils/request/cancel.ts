import { RequestOptions, RequestTask } from './type'

type PendingRequestEntry = {
    promise: Promise<any>
    task: RequestTask
}

const cancelerMap = new Map<string, PendingRequestEntry>()

const isPlainObject = (value: unknown): value is Record<string, unknown> => {
    return Object.prototype.toString.call(value) === '[object Object]'
}

const normalizeValue = (value: unknown): unknown => {
    if (Array.isArray(value)) {
        return value.map((item) => normalizeValue(item))
    }

    if (value instanceof Date) {
        return value.toISOString()
    }

    if (isPlainObject(value)) {
        return Object.keys(value)
            .sort()
            .reduce<Record<string, unknown>>((result, key) => {
                const currentValue = normalizeValue(value[key])
                if (typeof currentValue !== 'undefined') {
                    result[key] = currentValue
                }
                return result
            }, {})
    }

    return value
}

const serializePayload = (payload: unknown) => {
    if (typeof payload === 'undefined') {
        return ''
    }

    try {
        return JSON.stringify(normalizeValue(payload)) ?? ''
    } catch (error) {
        console.warn('序列化请求参数失败，将退化为基础请求键。', error)
        return ''
    }
}

export const createRequestKey = (options: RequestOptions) => {
    const method = String(options.method || 'GET').toUpperCase()
    const url = String(options.url || '')
    const payload = typeof options.data !== 'undefined' ? options.data : options.params
    return `${method}::${url}::${serializePayload(payload)}`
}

export class RequestCancel {
    private static instance?: RequestCancel

    static createInstance() {
        return this.instance ?? (this.instance = new RequestCancel())
    }

    get(key: string) {
        return cancelerMap.get(key)
    }

    add(key: string, requestTask: RequestTask, promise: Promise<any>) {
        cancelerMap.set(key, {
            task: requestTask,
            promise
        })
    }

    cancel(key: string) {
        const pendingRequest = cancelerMap.get(key)
        pendingRequest?.task?.abort()
        cancelerMap.delete(key)
    }

    remove(key: string, requestTask?: RequestTask) {
        const pendingRequest = cancelerMap.get(key)
        if (!pendingRequest) {
            return
        }

        if (requestTask && pendingRequest.task !== requestTask) {
            return
        }

        cancelerMap.delete(key)
    }
}

const requestCancel = RequestCancel.createInstance()

export default requestCancel
