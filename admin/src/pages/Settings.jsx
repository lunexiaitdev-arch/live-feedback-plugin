import { useEffect, useState } from 'react'
import { useStore } from '../store'

export default function Settings() {
  const { settings, fetchSettings, saveSettings, isLoading, error } = useStore()
  const [localSettings, setLocalSettings] = useState({
    frontend_enabled: true,
    allow_guest_feedback: false,
    notification_recipients: '',
    notification_recipients_enabled: true,
    guest_notifications_enabled: true,
  })
  const [saving, setSaving] = useState(false)
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (settings) {
      setLocalSettings({
        frontend_enabled: settings.frontend_enabled ?? true,
        allow_guest_feedback: settings.allow_guest_feedback ?? false,
        notification_recipients: Array.isArray( settings.notification_recipients )
          ? settings.notification_recipients.join(', ')
          : settings.notification_recipients ?? '',
        notification_recipients_enabled: settings.notification_recipients_enabled ?? true,
        guest_notifications_enabled: settings.guest_notifications_enabled ?? true,
      })
    }
  }, [settings])

  useEffect(() => {
    if (!settings || typeof settings.frontend_enabled === 'undefined') {
      fetchSettings().catch(() => {})
    }
  }, [fetchSettings, settings])

  const handleChange = (key, value) => {
    setLocalSettings((current) => ({ ...current, [key]: value }))
  }

  const handleSave = async (e) => {
    e.preventDefault()
    setSaving(true)
    setMessage('')

    try {
      await saveSettings(localSettings)
      setMessage('Settings saved successfully.')
      setTimeout(() => setMessage(''), 2500)
    } catch (err) {
      setMessage('Unable to save settings. Please try again.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div>
      <h1 className="text-3xl font-bold mb-2">Settings</h1>
      <p className="text-gray-600 mb-8">Configure feedback collection and permissions</p>

      <div className="max-w-2xl">
        <form onSubmit={handleSave} className="space-y-6">
          {/* Frontend Settings */}
          <div className="lvf-card">
            <h2 className="text-lg font-semibold mb-4">Frontend Feedback</h2>
            <p className="text-sm text-gray-600 mb-4">Control whether the feedback overlay is available on your website.</p>

            <label className="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-colors cursor-pointer">
              <input
                type="checkbox"
                checked={localSettings.frontend_enabled}
                onChange={(e) => handleChange('frontend_enabled', e.target.checked)}
                className="w-5 h-5 text-primary-600 rounded cursor-pointer"
              />
              <div className="flex-1">
                <span className="font-medium text-gray-900">Enable Frontend Feedback</span>
                <p className="text-sm text-gray-600 mt-1">Show the feedback button to your visitors</p>
              </div>
            </label>
          </div>

          {/* Guest Settings */}
          <div className="lvf-card">
            <h2 className="text-lg font-semibold mb-4">Guest Access</h2>
            <p className="text-sm text-gray-600 mb-4">Allow unauthenticated users to submit feedback.</p>

            <label className="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-colors cursor-pointer">
              <input
                type="checkbox"
                checked={localSettings.allow_guest_feedback}
                onChange={(e) => handleChange('allow_guest_feedback', e.target.checked)}
                className="w-5 h-5 text-primary-600 rounded cursor-pointer"
              />
              <div className="flex-1">
                <span className="font-medium text-gray-900">Allow Guest Feedback</span>
                <p className="text-sm text-gray-600 mt-1">Let anonymous users submit feedback</p>
              </div>
            </label>
          </div>

          {/* Notification Settings */}
          <div className="lvf-card">
            <h2 className="text-lg font-semibold mb-4">Notifications</h2>
            <p className="text-sm text-gray-600 mb-4">Control which notification emails are sent for feedback updates.</p>

            <label className="flex items-center gap-3 p-4 mb-4 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-colors cursor-pointer">
              <input
                type="checkbox"
                checked={localSettings.notification_recipients_enabled}
                onChange={(e) => handleChange('notification_recipients_enabled', e.target.checked)}
                className="w-5 h-5 text-primary-600 rounded cursor-pointer"
              />
              <div className="flex-1">
                <span className="font-medium text-gray-900">Admin email notifications</span>
                <p className="text-sm text-gray-600 mt-1">Send emails to configured recipient addresses when feedback receives new comments.</p>
              </div>
            </label>

            <label className="flex items-center gap-3 p-4 mb-4 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-colors cursor-pointer">
              <input
                type="checkbox"
                checked={localSettings.guest_notifications_enabled}
                onChange={(e) => handleChange('guest_notifications_enabled', e.target.checked)}
                className="w-5 h-5 text-primary-600 rounded cursor-pointer"
              />
              <div className="flex-1">
                <span className="font-medium text-gray-900">Guest email notifications</span>
                <p className="text-sm text-gray-600 mt-1">Send update emails to guests when their feedback receives a response or is resolved.</p>
              </div>
            </label>

            <label className="block text-sm font-medium text-gray-900 mb-2" htmlFor="notification_recipients">
              Notification email recipients
            </label>
            <textarea
              id="notification_recipients"
              value={localSettings.notification_recipients}
              onChange={(e) => handleChange('notification_recipients', e.target.value)}
              rows={4}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500 text-sm"
              placeholder="admin@example.com, support@example.com"
            />
            <p className="text-sm text-gray-500 mt-2">Enter one or more email addresses separated by commas, semicolons, or new lines.</p>
          </div>

          {/* Messages */}
          {error && (
            <div className="p-3 bg-red-50 text-red-800 border border-red-200 rounded-lg text-sm">
              {error}
            </div>
          )}
          {message && (
            <div className="p-3 bg-green-50 text-green-800 border border-green-200 rounded-lg text-sm">
              {message}
            </div>
          )}

          {/* Action */}
          <div className="flex gap-4">
            <button
              type="submit"
              disabled={saving || isLoading}
              className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium disabled:opacity-50"
            >
              {saving ? 'Saving...' : 'Save Settings'}
            </button>
            <p className="text-sm text-gray-600 self-center">Changes take effect immediately</p>
          </div>
        </form>
      </div>
    </div>
  )
}
