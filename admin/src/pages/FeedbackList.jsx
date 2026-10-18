import { useEffect, useState } from 'react'
import { useStore } from '../store'

export default function FeedbackList({ onViewFeedback }) {
  const {
    feedback,
    pagination,
    filters,
    isLoading,
    error,
    capabilities,
    fetchFeedback,
    deleteFeedback,
    setFilters,
    setPagination,
    clearError,
  } = useStore()

  const [searchInput, setSearchInput] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [priorityFilter, setPriorityFilter] = useState('')
  const [selectedIds, setSelectedIds] = useState([])

  useEffect(() => {
    fetchFeedback()
  }, [filters, fetchFeedback])

  const handleSearch = (e) => {
    e.preventDefault()
    setFilters({ search: searchInput })
  }

  const handleFilterChange = (field, value) => {
    if (field === 'status') {
      setStatusFilter(value)
      setFilters({ status: value })
    } else if (field === 'priority') {
      setPriorityFilter(value)
      setFilters({ priority: value })
    }
  }

  const toggleSelectAll = () => {
    if (selectedIds.length === feedback.length) {
      setSelectedIds([])
    } else {
      setSelectedIds(feedback.map((item) => item.id))
    }
  }

  const toggleSelectOne = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((selectedId) => selectedId !== id) : [...prev, id]
    )
  }

  const handleBulkDelete = async () => {
    if (!selectedIds.length) return

    if (!window.confirm(`Delete ${selectedIds.length} selected feedback item${selectedIds.length > 1 ? 's' : ''}? This cannot be undone.`)) {
      return
    }

    try {
      await Promise.all(selectedIds.map((id) => deleteFeedback(id)))
      setSelectedIds([])
    } catch (err) {
      console.error(err)
    }
  }

  const handlePageChange = (newPage) => {
    setPagination(newPage)
  }

  return (
    <div>
      <h1 className="text-3xl font-bold mb-6">Feedback</h1>

      {error && (
        <div className="mb-4 p-4 bg-red-100 text-red-800 rounded-lg flex justify-between items-center">
          <p>{error}</p>
          <button
            onClick={clearError}
            className="text-sm underline hover:no-underline"
          >
            Dismiss
          </button>
        </div>
      )}

      {/* Filters */}
      <div className="lvf-card mb-6">
        <div className="space-y-4">
          <form onSubmit={handleSearch} className="flex gap-2">
            <input
              type="text"
              placeholder="Search feedback..."
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500"
            />
            <button
              type="submit"
              className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
            >
              Search
            </button>
          </form>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Status
              </label>
              <select
                value={statusFilter}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500"
              >
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="resolved">Resolved</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Priority
              </label>
              <select
                value={priorityFilter}
                onChange={(e) => handleFilterChange('priority', e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500"
              >
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Feedback Table */}
      <div className="lvf-card">
        {isLoading ? (
          <p className="text-center text-gray-500 py-8">Loading feedback...</p>
        ) : feedback.length === 0 ? (
          <p className="text-center text-gray-500 py-8">No feedback found</p>
        ) : (
          <>
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
              <div className="text-sm text-gray-600">
                {feedback.length} feedback item{feedback.length !== 1 ? 's' : ''}
                {capabilities.manage && (
                  <p className="mt-1 text-xs text-gray-500">
                    Select one or more rows to delete feedback in bulk.
                  </p>
                )}
              </div>
              {capabilities.manage && (
                <button
                  onClick={handleBulkDelete}
                  disabled={selectedIds.length === 0}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                    selectedIds.length === 0
                      ? 'bg-gray-200 text-gray-600 cursor-not-allowed'
                      : 'bg-red-600 text-white hover:bg-red-700'
                  }`}
                >
                  Delete selected ({selectedIds.length})
                </button>
              )}
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="py-3 px-4 text-sm font-semibold text-gray-700">
                      <input
                        type="checkbox"
                        checked={feedback.length > 0 && selectedIds.length === feedback.length}
                        onChange={toggleSelectAll}
                        className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      />
                    </th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">
                      Page
                    </th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">
                      Status
                    </th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">
                      Priority
                    </th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">
                      Device
                    </th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">
                      Created
                    </th>
                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">
                      Action
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {feedback.map((item) => (
                    <tr key={item.id} className="border-b border-gray-100 hover:bg-gray-50">
                      <td className="py-3 px-4">
                        <input
                          type="checkbox"
                          checked={selectedIds.includes(item.id)}
                          onChange={() => toggleSelectOne(item.id)}
                          className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                      </td>
                      <td className="py-3 px-4 text-sm text-gray-900 truncate max-w-xs">
                        {new URL(item.page_url).pathname}
                      </td>
                      <td className="py-3 px-4">
                        <span className={`lvf-badge lvf-badge-${item.status}`}>
                          {item.status}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        <span className={`lvf-badge lvf-badge-${item.priority}`}>
                          {item.priority}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-sm text-gray-600">
                        {item.device_type || 'desktop'}
                      </td>
                      <td className="py-3 px-4 text-sm text-gray-500">
                        {new Date(item.created_at).toLocaleDateString()}
                      </td>
                      <td className="py-3 px-4 space-x-2">
                        <button
                          onClick={() => onViewFeedback(item.id)}
                          className="text-primary-600 hover:text-primary-700 font-medium text-sm"
                        >
                          View →
                        </button>
                        {capabilities.manage && (
                          <button
                            onClick={() => {
                              if (window.confirm('Delete this feedback? This action cannot be undone.')) {
                                deleteFeedback(item.id)
                              }
                            }}
                            className="text-red-600 hover:text-red-700 font-medium text-sm"
                          >
                            Delete
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {pagination.pages > 1 && (
              <div className="mt-6 flex items-center justify-center gap-2">
                {Array.from({ length: pagination.pages }, (_, i) => i + 1).map((page) => (
                  <button
                    key={page}
                    onClick={() => handlePageChange(page)}
                    className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                      pagination.current_page === page
                        ? 'bg-primary-600 text-white'
                        : 'border border-gray-300 text-gray-700 hover:border-primary-600'
                    }`}
                  >
                    {page}
                  </button>
                ))}
              </div>
            )}
          </>
        )}
      </div>
    </div>
  )
}
