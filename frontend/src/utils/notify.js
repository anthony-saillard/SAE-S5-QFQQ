import { Notify } from 'quasar'

/**
 * Displays a notification with customized options.
 * @param {String} message - The message to be displayed.
 * @param {String} color - Notification color.
 * @param {String} icon - The icon to be displayed in the notification.
 * @param {Object} options - Additional options for Quasar Notify.
 */
function showNotify(message, color, icon, options = {}) {
  Notify.create({
    message,
    color,
    icon,
    position: 'top-right',
    timeout: 3000,
    ...options
  })
}

/**
 * Displays an error notification.
 * @param {String} message - The error message.
 * @param {Object} options - Additional notification options.
 */
export function errorNotify(message, options = {}) {
  showNotify(message, 'negative', 'error', options)
}

/**
 * Displays an information notification.
 * @param {String} message - The information message.
 * @param {Object} options - Additional notification options.
 */
export function infoNotify(message, options = {}) {
  showNotify(message, 'info', 'info', options)
}

/**
 * Displays a notification of success.
 * @param {String} message - The message of success.
 * @param {Object} options - Additional notification options.
 */
export function successNotify(message, options = {}) {
  showNotify(message, 'positive', 'check_circle', options)
}

/**
 * Displays a warning notification.
 * @param {String} message - The warning message.
 * @param {Object} options - Additional notification options.
 */
export function warningNotify(message, options = {}) {
  showNotify(message, 'warning', 'warning', options)
}
