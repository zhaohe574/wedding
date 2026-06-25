import request from '@/utils/request'

export function quickToolTicket(params: { tool: string }) {
    return request.get<{ ticket: string; expire_in: number }>({
        url: '/ops.quickTool/ticket',
        params,
    })
}
