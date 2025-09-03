export const adminPerm = 'ROLE_ADMIN'

export function empty(value) {
  if (value === null || value === undefined) {
    return true
  }

  if (typeof value === 'string' && value.trim() === '') {
    return true
  }

  if (Array.isArray(value) && value.length === 0) {
    return true
  }

  if (typeof value === 'object' && Object.keys(value).length === 0) {
    return true
  }

  if (typeof value === 'number' && isNaN(value)) {
    return true
  }

  if (value === false) {
    return true
  }

  return false
}

export function formatUserName(user) {
  if (empty(user)) {
    return ''
  }

  const firstName = user?.first_name || user?.firstName || ''
  const lastName = user?.last_name || user?.lastName || ''

  return `${lastName} ${firstName}`.trim()
}

export function getInitials(input) {
  if (empty(input)) {
    return ''
  }

  let firstName = ''
  let lastName = ''

  if (typeof input === 'object') {
    firstName = input?.first_name || input?.firstName || ''
    lastName = input?.last_name || input?.lastName || ''

    if (empty(firstName) && empty(lastName)) {
      return ''
    }

    return [lastName, firstName]
      .filter(part => part)
      .map(part => part.charAt(0).toUpperCase())
      .join('')
      .substring(0, 2)
  }

  if (typeof input === 'string') {
    return input.split(' ')
      .filter(part => part)
      .map(part => part.charAt(0).toUpperCase())
      .join('')
      .substring(0, 2)
  }

  return ''
}
