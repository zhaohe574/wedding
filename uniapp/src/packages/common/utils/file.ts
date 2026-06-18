import { showError, showSuccess } from '@/utils/feedback'

export async function saveImageToPhotosAlbum(url: string) {
    if (!url) return showError('图片错误')
    //#ifdef H5
    showError('长按图片保存')
    //#endif
    //#ifndef H5
    try {
        const res: any = await uni.downloadFile({ url, timeout: 10000 })
        await uni.saveImageToPhotosAlbum({
            filePath: res.tempFilePath
        })
        showSuccess('保存成功')
    } catch (error: any) {
        showError(error?.errMsg || error, '保存失败')
    }
    //#endif
}
