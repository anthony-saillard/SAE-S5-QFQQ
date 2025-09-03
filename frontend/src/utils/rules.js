export const rules = {
  required: (value) => (value !== null && value !== undefined && value !== '') || 'Ce champ est requis.',

  maxLength: (max) => {
    return (value) =>
      !value || value.length <= max || `La longueur maximale est de ${max} caractères.`
  },

  minLength: (min) => {
    return (value) =>
      !value || value.length >= min || `La longueur minimale est de ${min} caractères.`
  },

  email: (value) =>
    !value ||
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ||
    'Veuillez entrer une adresse e-mail valide.',

  numeric: (value) =>
    !value || /^[0-9]+$/.test(value) || 'Ce champ doit contenir uniquement des chiffres.',

  minValue: (min) => {
    return (value) =>
      !value || parseFloat(value) >= min || `La valeur doit être supérieure ou égale à ${min}.`
  },

  maxValue: (max) => {
    return (value) =>
      !value || parseFloat(value) <= max || `La valeur doit être inférieure ou égale à ${max}.`
  },

  positive: (val) => {
    return parseFloat(val) >= 0 || 'La valeur doit être positive ou égale à zéro.'
  },

  dateOrder: (startDate, endDate) => () =>
    !startDate || !endDate || new Date(startDate) <= new Date(endDate) ||
    'Vous ne pouvez pas mettre une date de fin avant une date de début'

}
