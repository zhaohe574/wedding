import { computed, reactive, ref, watch } from 'vue'
import {
    buildQuestionnaireAnswers,
    createQuestionAnswerMap,
    normalizeQuestionnaireQuestions
} from '../shared'

export const useAftersaleQuestionnaire = (detailRef: { value: any }) => {
    const answerMap = reactive<Record<string, string | string[]>>({})
    const scoreOverall = ref(5)
    const feedback = ref('')

    const questions = computed(() =>
        normalizeQuestionnaireQuestions(detailRef.value?.questionnaire?.questions || [])
    )

    const hydrate = () => {
        Object.keys(answerMap).forEach((key) => delete answerMap[key])
        const nextMap = createQuestionAnswerMap(questions.value)
        Object.keys(nextMap).forEach((key) => {
            answerMap[key] = nextMap[key]
        })
        scoreOverall.value = Number(detailRef.value?.score_overall || detailRef.value?.score || 5)
        feedback.value = String(detailRef.value?.content || '')
    }

    const isSelected = (key: string, option: string) => {
        const value = answerMap[key]
        return Array.isArray(value) ? value.includes(option) : false
    }

    const toggleOption = (key: string, option: string) => {
        const current = Array.isArray(answerMap[key]) ? [...(answerMap[key] as string[])] : []
        const exists = current.includes(option)
        answerMap[key] = exists ? current.filter((item) => item !== option) : [...current, option]
    }

    const buildAnswers = () => buildQuestionnaireAnswers(questions.value, answerMap)

    watch(
        () => detailRef.value,
        () => {
            hydrate()
        }
    )

    return {
        questions,
        answerMap,
        scoreOverall,
        feedback,
        hydrate,
        isSelected,
        toggleOption,
        buildAnswers
    }
}
