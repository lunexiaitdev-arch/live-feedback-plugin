import { useEffect, useState } from 'react'
import { useStore } from '../store'

export default function FeedbackDetail({ feedbackId, onBack }) {
  const {
    selectedFeedback,
    capabilities,
    fetchFeedbackDetail,
    updateFeedback,
    resolveFeedback,
    deleteFeedback,
    addComment,
    isLoading,
    error,
  } = useStore()

  const [newComment, setNewComment] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [selectedStatus, setSelectedStatus] = useState('')
  const [selectedPriority, setSelectedPriority] = useState('')

  useEffect(() => {
    fetchFeedbackDetail(feedbackId)
  }, [feedbackId, fetchFeedbackDetail])

  useEffect(() => {
    if (selectedFeedback?.feedback) {
      setSelectedStatus(selectedFeedback.feedback.status)
      setSelectedPriority(selectedFeedback.feedback.priority)
    }
  }, [selectedFeedback])

  const handleStatusChange = async (newStatus) => {
    setSelectedStatus(newStatus)
    try {
      await updateFeedback(feedbackId, { status: newStatus })
    } catch (err) {
      console.error(err)
    }
  }

  const handlePriorityChange = async (newPriority) => {
    setSelectedPriority(newPriority)
    try {
      await updateFeedback(feedbackId, { priority: newPriority })
    } catch (err) {
      console.error(err)
    }
  }

  const handleResolve = async () => {
    try {
      await resolveFeedback(feedbackId)
    } catch (err) {
      console.error(err)
    }
  }

  const handleDelete = async () => {
    if (!window.confirm('Delete this feedback? This action cannot be undone.')) {
      return
    }

    try {
      await deleteFeedback(feedbackId)
      onBack()
    } catch (err) {
      console.error(err)
    }
  }

  const handleAddComment = async (e) => {
    e.preventDefault()
    if (!newComment.trim()) return

    setIsSubmitting(true)
    try {
      await addComment(feedbackId, newComment)
      setNewComment('')
    } catch (err) {
      console.error(err)
    } finally {
      setIsSubmitting(false)
    }
  }

  if (isLoading || !selectedFeedback) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Loading feedback details...</p>
      </div>
    )
  }

  const feedback = selectedFeedback.feedback
  const comments = selectedFeedback.comments || []
  const activity = selectedFeedback.activity || []
  const assignments = selectedFeedback.assignments || []

  return (
    <div>
      <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <button
          onClick={onBack}
          className="text-primary-600 hover:text-primary-700 font-medium"
        >
          ← Back to Feedback List
        </button>
        {capabilities.manage && (
          <button
            onClick={handleDelete}
            className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium transition-colors"
          >
            Delete Feedback
          </button>
        )}
      </div>

      {error && (
        <div className="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
          <p>{error}</p>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Feedback Info */}
          <div className="lvf-card">
            <h1 className="text-2xl font-bold mb-4">Feedback #{feedback.id}</h1>

            <div className="space-y-3 text-sm">
              <div>
                <p className="text-gray-600">Page URL</p>
                <a href={feedback.page_url} target="_blank" rel="noreferrer" className="text-primary-600 hover:underline break-all">
                  {feedback.page_url}
                </a>
              </div>

              <div>
                <p className="text-gray-600">Position</p>
                <p className="text-gray-900">
                  X: {feedback.coord_x}, Y: {feedback.coord_y}
                </p>
              </div>

              <div>
                <p className="text-gray-600">Device</p>
                <p className="text-gray-900 capitalize">{feedback.device_type || 'desktop'}</p>
              </div>

              <div>
                <p className="text-gray-600">Viewport</p>
                <p className="text-gray-900">
                  {feedback.viewport_width}x{feedback.viewport_height}
                </p>
              </div>

              <div>
                <p className="text-gray-600">Browser</p>
                <p className="text-gray-900 text-xs break-all">{feedback.browser_info}</p>
              </div>

              <div>
                <p className="text-gray-600">Created</p>
                <p className="text-gray-900">
                  {new Date(feedback.created_at).toLocaleString()}
                </p>
              </div>
            </div>
          </div>

          {/* Screenshot */}
          {feedback.screenshot_url && (
            <div className="lvf-card">
              <h3 className="font-bold mb-4">Screenshot</h3>
              <img
                src={feedback.screenshot_url}
                alt="Screenshot"
                className="w-full border border-gray-200 rounded-lg"
              />
            </div>
          )}

          {/* Comments */}
          <div className="lvf-card">
            <h3 className="font-bold mb-4">Comments ({comments.length})</h3>

            <div className="space-y-4 mb-6">
              {comments.length === 0 ? (
                <p className="text-gray-500 text-sm">No comments yet</p>
              ) : (
                comments.map((comment) => (
                  <div key={comment.id} className="border-l-4 border-primary-500 pl-4">
                    <div className="flex justify-between items-start">
                      <div>
                        <p className="font-medium text-sm">{comment.author_name}</p>
                        <p className="text-xs text-gray-500">
                          {new Date(comment.created_at).toLocaleString()}
                        </p>
                      </div>
                    </div>
                    <p className="text-sm text-gray-700 mt-2 whitespace-pre-wrap">
                      {comment.content}
                    </p>
                  </div>
                ))
              )}
            </div>

            {capabilities.create && (
              <form onSubmit={handleAddComment} className="border-t pt-4">
                <textarea
                  value={newComment}
                  onChange={(e) => setNewComment(e.target.value)}
                  placeholder="Add a comment..."
                  rows="3"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500 text-sm"
                />
                <button
                  type="submit"
                  disabled={isSubmitting || !newComment.trim()}
                  className="mt-3 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:bg-gray-400 text-sm font-medium transition-colors"
                >
                  {isSubmitting ? 'Posting...' : 'Post Comment'}
                </button>
              </form>
            )}
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Status & Priority */}
          {capabilities.manage && (
            <div className="lvf-card">
              <h3 className="font-bold mb-4">Settings</h3>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Status
                  </label>
                  <select
                    value={selectedStatus}
                    onChange={(e) => handleStatusChange(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500 text-sm"
                  >
                    <option value="open">Open</option>
                    <option value="resolved">Resolved</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Priority
                  </label>
                  <select
                    value={selectedPriority}
                    onChange={(e) => handlePriorityChange(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary-500 text-sm"
                  >
                    <option value="low">Low</option>
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                  </select>
                </div>

                {capabilities.resolve && selectedStatus !== 'resolved' && (
                  <button
                    onClick={handleResolve}
                    className="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium transition-colors"
                  >
                    Mark as Resolved
                  </button>
                )}
              </div>
            </div>
          )}

          {/* Assignments */}
          {assignments.length > 0 && (
            <div className="lvf-card">
              <h3 className="font-bold mb-4">Assigned To</h3>
              <div className="space-y-2">
                {assignments.map((assignment) => (
                  <div
                    key={assignment.id}
                    className="p-2 bg-gray-100 rounded-lg text-sm"
                  >
                    {assignment.user_name}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Activity Timeline */}
          {activity.length > 0 && (
            <div className="lvf-card">
              <h3 className="font-bold mb-4">Activity</h3>
              <div className="space-y-3 text-sm">
                {activity.slice(0, 10).map((act) => (
                  <div key={act.id} className="flex gap-2 text-xs">
                    <span className="text-gray-500">
                      {new Date(act.created_at).toLocaleTimeString()}
                    </span>
                    <span className="text-gray-700">{act.action}</span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
