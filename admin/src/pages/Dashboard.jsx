import { useEffect, useState } from 'react'
import { useStore } from '../store'

export default function Dashboard({ onViewFeedback, onOpenSettings }) {
  const { feedback, fetchFeedback, isLoading, setFilters, settings, saveSettings } = useStore()
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    setFilters({ per_page: 100 })
    fetchFeedback()
  }, [setFilters, fetchFeedback])

  const stats = [
    {
      label: 'Total Feedback',
      value: feedback.length,
      icon: '💬',
      color: 'bg-blue-100 text-blue-700',
    },
    {
      label: 'Open',
      value: feedback.filter((f) => f.status === 'open').length,
      icon: '🔴',
      color: 'bg-red-100 text-red-700',
    },
    {
      label: 'Resolved',
      value: feedback.filter((f) => f.status === 'resolved').length,
      icon: '✅',
      color: 'bg-green-100 text-green-700',
    },
    {
      label: 'High Priority',
      value: feedback.filter((f) => f.priority === 'high').length,
      icon: '⚠️',
      color: 'bg-yellow-100 text-yellow-700',
    },
  ]

  const recentFeedback = feedback.slice(0, 5)

  return (
    <div>
      <h1 className="text-3xl font-bold mb-2">Dashboard</h1>
      <p className="text-gray-600 mb-8">Monitor and manage all feedback</p>

      {/* Plugin Controls Card */}
      <div className="lvf-card mb-8">
        <div className="flex flex-col gap-6">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h2 className="text-lg font-semibold">Plugin Controls</h2>
              <p className="text-gray-500 text-sm">Enable or disable the frontend feedback overlay and guest comments.</p>
            </div>
            <div className="flex flex-wrap gap-3 items-center">
              <button
                onClick={onOpenSettings}
                className="px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-semibold"
              >
                Settings
              </button>
              <button
                onClick={async () => {
                  if (!settings) return
                  setIsSaving(true)
                  await saveSettings({
                    frontend_enabled: !settings.frontend_enabled,
                    allow_guest_feedback: settings.allow_guest_feedback,
                  }).catch(() => {})
                  setIsSaving(false)
                }}
                disabled={!settings || isSaving}
                className="px-5 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 transition-colors text-sm font-semibold disabled:opacity-50"
              >
                {settings?.frontend_enabled ? 'Disable' : 'Enable'}
              </button>
            </div>
          </div>

          <div className="grid gap-3 sm:grid-cols-2">
            <div className="rounded-2xl bg-slate-50 p-4">
              <p className="text-xs uppercase tracking-[0.12em] text-slate-500 mb-2">Frontend status</p>
              <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${settings?.frontend_enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'}`}>
                {settings?.frontend_enabled ? 'Enabled' : 'Disabled'}
              </span>
            </div>
            <div className="rounded-2xl bg-slate-50 p-4">
              <p className="text-xs uppercase tracking-[0.12em] text-slate-500 mb-2">Guest comments</p>
              <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${settings?.allow_guest_feedback ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700'}`}>
                {settings?.allow_guest_feedback ? 'Allowed' : 'Restricted'}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {stats.map((stat) => (
          <div key={stat.label} className="lvf-card">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-500 text-sm">{stat.label}</p>
                <p className="text-3xl font-bold mt-2">{stat.value}</p>
              </div>
              <span className={`text-3xl ${stat.color} p-3 rounded-lg`}>{stat.icon}</span>
            </div>
          </div>
        ))}
      </div>

      {/* Recent Feedback */}
      <div className="lvf-card">
        <h2 className="text-lg font-semibold mb-4">Recent Feedback</h2>

        {isLoading ? (
          <p className="text-gray-500">Loading...</p>
        ) : recentFeedback.length === 0 ? (
          <p className="text-gray-500">No feedback yet</p>
        ) : (
          <div className="space-y-2">
            {recentFeedback.map((item) => (
              <div
                key={item.id}
                onClick={() => onViewFeedback(item.id)}
                className="p-4 border border-gray-200 rounded-lg hover:border-primary-500 cursor-pointer transition-colors"
              >
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-sm truncate">{item.page_url}</p>
                    <p className="text-xs text-gray-500 mt-1 truncate">
                      {item.selector || item.browser_info || `${item.device_type || 'Desktop'}`}
                    </p>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <span className={`lvf-badge lvf-badge-${item.status}`}>
                      {item.status}
                    </span>
                    <span className={`lvf-badge lvf-badge-${item.priority}`}>
                      {item.priority}
                    </span>
                    <span className="text-xs text-gray-400">
                      {new Date(item.created_at).toLocaleString()}
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
