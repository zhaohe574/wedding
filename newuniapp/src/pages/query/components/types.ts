/**
 * 查档期页的本地类型。
 *
 * 只描述页面内的展示结构，不复制后端 DTO：
 *   - CalendarMeta 由 `/schedule/monthCalendar` 裁剪而来（吉日/农历/节假日）
 *   - DayAvailability 只表示「已知的」某日可约状态；未知不是 0，必须用 null/缺省表达
 */

export type DayStatus = 'available' | 'limited' | 'full'

export interface CalendarMeta {
    date: string
    isLucky: boolean
    isHoliday: boolean
    holidayName: string
    lunar: string
}

export interface DayAvailability {
    status: DayStatus
    count: number
}

export interface NeighborDay {
    date: string
    label: string
    count: number
}
