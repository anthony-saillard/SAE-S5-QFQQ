/* eslint-disable no-console */
import dayjs from 'dayjs'

class Logger {
  constructor() {
    this.isDev = process.env.NODE_ENV === 'development'
    this.logQueue = []
    this.isProcessing = false
  }

  async processLogQueue() {
    if (this.isProcessing || this.logQueue.length === 0) {
      return
    }

    this.isProcessing = true
    const log = this.logQueue.shift()

    if (this.isDev) {
      switch (log.type) {
        case 'error':
          console.error(dayjs(log.timestamp).format('DD/MM/YYYY h:mm'), log.message, log.details || '')
          break
        case 'warn':
          console.warn(dayjs(log.timestamp).format('DD/MM/YYYY h:mm'), log.message, log.details || '')
          break
        case 'info':
          console.info(dayjs(log.timestamp).format('DD/MM/YYYY h:mm'), log.message, log.details || '')
          break
        default:
          console.log(dayjs(log.timestamp).format('DD/MM/YYYY h:mm'), log.message, log.details || '')
      }
    } else {
      try {
        await this.saveLog(log)
      } catch (error) {
        console.error('Failed to save log:', error)
      }
    }

    this.isProcessing = false
    await this.processLogQueue()
  }

  async saveLog(log) {
    const logs = JSON.parse(localStorage.getItem('app_logs') || '[]')
    logs.push(log)

    if (logs.length > 1000) {
      logs.shift()
    }

    localStorage.setItem('app_logs', JSON.stringify(logs))
  }

  addToQueue(type, message, details = null) {
    const log = {
      timestamp: new Date().toISOString(),
      type,
      message: message instanceof Error ? message.message : message,
      details: details || (message instanceof Error ? message.stack : null)
    }

    this.logQueue.push(log)
    this.processLogQueue()
  }

  error(message, details = null) {
    this.addToQueue('error', message, details)
  }

  warn(message, details = null) {
    this.addToQueue('warn', message, details)
  }

  info(message, details = null) {
    this.addToQueue('info', message, details)
  }

  log(message, details = null) {
    this.addToQueue('log', message, details)
  }

  getLogs() {
    return JSON.parse(localStorage.getItem('app_logs') || '[]')
  }

  clearLogs() {
    localStorage.removeItem('app_logs')
  }
}

export const logger = new Logger()
