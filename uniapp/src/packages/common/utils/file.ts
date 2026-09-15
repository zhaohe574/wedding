import { showError, showSuccess } from '@/utils/feedback'

export async function saveImageToPhotosAlbum(url: string) {
    if (!url) return showError('图片错误')




    try {
        const res: any = await uni.downloadFile({ url, timeout: 10000 })
        if (Number(res?.statusCode || 0) < 200 || Number(res?.statusCode || 0) >= 300 || !res?.tempFilePath) {
            throw new Error('图片下载失败，请重试')
        }
        await uni.saveImageToPhotosAlbum({
            filePath: res.tempFilePath
        })
        showSuccess('保存成功')
    } catch (error: any) {
        showError(error?.errMsg || error, '保存失败')
    }

}
