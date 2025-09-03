import { Dialog } from 'quasar'

export const confirmDialog = (message, options = {}) => {
  return new Promise((resolve) => {
    const defaultOptions = {
      title: options.title || 'Confirmation',
      message: message,
      cancel: options.cancelLabel || 'Annuler',
      ok: options.okLabel || 'Confirmer',
      persistent: options.persistent ?? true,
      html: options.html ?? false,
      color: options.color || 'primary',
      cancelColor: options.cancelColor || 'grey',
      icon: options.icon || 'warning',
      position: options.position || 'standard'
    }

    Dialog.create({
      ...defaultOptions,
      class: 'confirm-dialog'
    })
      .onOk(() => resolve(true))
      .onCancel(() => resolve(false))
      .onDismiss(() => resolve(false))
  })
}
