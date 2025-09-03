import { ref } from 'vue'

const usedPeriodIndices = ref(new Set())

export function usePeriods() {
  const periodColors = [
    '#fb6060', // Rouge
    '#60b7fb', // Bleu
    '#68b56b', // Vert
    '#ffd555', // Jaune
    '#a54eb3', // Violet
    '#fdbc5d', // Orange
    '#6e7e85', // Bleu-gris
    '#7a655d', // Marron
    '#4bc5d5', // Cyan
    '#7753b8'  // Indigo
  ]

  const registerUsedPeriod = (periodIndex) => {
    if (periodIndex >= 0) {
      usedPeriodIndices.value.add(periodIndex)
    }
  }

  const resetUsedPeriods = () => {
    usedPeriodIndices.value.clear()
  }

  const getPeriodColor = (index) => {
    return periodColors[index % periodColors.length]
  }

  const getTransparentPeriodColor = (index) => {
    const color = periodColors[index % periodColors.length]
    const rgbValues = hexToRgb(color)
    return `rgba(${rgbValues.r}, ${rgbValues.g}, ${rgbValues.b}, 0.25)`
  }

  const hexToRgb = (hex) => {
    hex = hex.replace('#', '')

    const r = parseInt(hex.substring(0, 2), 16)
    const g = parseInt(hex.substring(2, 4), 16)
    const b = parseInt(hex.substring(4, 6), 16)

    return { r, g, b }
  }

  const getVisiblePeriods = (allPeriods) => {
    if (!allPeriods || allPeriods.length === 0) {
      return []
    }

    return allPeriods
      .filter((_, index) => usedPeriodIndices.value.has(index))
      .map(period => ({
        ...period,
        index: allPeriods.indexOf(period)
      }))
  }

  const formatDateRange = (startDate, endDate) => {
    if (!startDate || !endDate) {
      return 'Période pédagogique'
    }

    const formatDate = (dateStr) => {
      const date = new Date(dateStr)
      return date.getDate().toString().padStart(2, '0') + '/' +
        (date.getMonth() + 1).toString().padStart(2, '0')
    }

    return `${formatDate(startDate)} - ${formatDate(endDate)}`
  }

  return {
    getPeriodColor,
    getTransparentPeriodColor,
    getVisiblePeriods,
    formatDateRange,
    registerUsedPeriod,
    resetUsedPeriods,
    usedPeriodIndices
  }
}
