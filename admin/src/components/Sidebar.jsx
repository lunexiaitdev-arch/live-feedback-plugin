import clsx from 'clsx'

export default function Sidebar({ currentPage, onNavigate }) {
  const navItems = [
    { id: 'dashboard', label: 'Dashboard', icon: '📊' },
    { id: 'feedback', label: 'Feedback', icon: '💬' },
    { id: 'settings', label: 'Settings', icon: '⚙️' },
  ]

  return (
    <div className="lvf-sidebar">
      <div className="p-6 border-b border-gray-700">
        <h1 className="text-xl font-bold text-white">Lunexia Live Feedback</h1>
        <p className="text-white text-sm">WordPress feedback dashboard</p>
      </div>

      <nav className="p-4">
        {navItems.map((item) => (
          <button
            key={item.id}
            onClick={() => onNavigate(item.id)}
            className={clsx(
              'w-full text-left px-4 py-3 rounded-lg mb-2 transition-colors text-lg font-medium',
              currentPage === item.id
                ? 'bg-primary-600 text-white'
                : 'text-gray-300 hover:bg-gray-800'
            )}
          >
            <span className="mr-3">{item.icon}</span>
            {item.label}
          </button>
        ))}
      </nav>
    </div>
  )
}
