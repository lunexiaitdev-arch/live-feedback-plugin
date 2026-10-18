import { create } from 'zustand'
import api from './api'

export const useStore = create((set, get) => ({
  // User state
  user: null,
  capabilities: {},

  // Feedback state
  feedback: [],
  selectedFeedback: null,
  isLoading: false,
  error: null,

  // Filters
  filters: {
    page: 1,
    per_page: 20,
    status: '',
    priority: '',
    search: '',
    author_id: null,
  },

  // Pagination
  pagination: {
    total: 0,
    pages: 0,
    current_page: 1,
    per_page: 20,
  },

  // Plugin settings
  settings: {
    frontend_enabled: true,
    allow_guest_feedback: false,
    notification_recipients: [],
    notification_recipients_enabled: true,
    guest_notifications_enabled: true,
  },

  // Initialize from window.lunexia_lf_admin or window.lvf_admin
  initialize: () => {
    const adminData = (typeof window !== 'undefined') ? (window.lunexia_lf_admin || window.lvf_admin) : null
    if (adminData) {
      set({
        user: adminData.current_user,
        capabilities: adminData.capabilities,
        settings: {
          frontend_enabled: adminData.settings?.frontend_enabled ?? true,
          allow_guest_feedback: adminData.settings?.allow_guest_feedback ?? false,
          notification_recipients: adminData.settings?.notification_recipients ?? [],
          notification_recipients_enabled: adminData.settings?.notification_recipients_enabled ?? true,
          guest_notifications_enabled: adminData.settings?.guest_notifications_enabled ?? true,
        },
      })
    }
  },

  // Fetch feedback list
  fetchFeedback: async () => {
    set({ isLoading: true, error: null })
    try {
      const state = get()
      const response = await api.get('/feedback', {
        params: state.filters,
      })
      set({
        feedback: response.data.data || [],
        pagination: response.data.pagination || {},
        isLoading: false,
      })
    } catch (error) {
      set({
        error: error.message || 'Failed to fetch feedback',
        isLoading: false,
      })
    }
  },

  // Fetch single feedback with details
  fetchFeedbackDetail: async (id) => {
    set({ isLoading: true, error: null })
    try {
      const response = await api.get(`/feedback/${id}`)
      set({
        selectedFeedback: response.data,
        isLoading: false,
      })
    } catch (error) {
      set({
        error: error.message || 'Failed to fetch feedback details',
        isLoading: false,
      })
    }
  },

  // Update feedback status or priority
  updateFeedback: async (id, updates) => {
    try {
      const response = await api.patch(`/feedback/${id}`, updates)
      set((state) => ({
        feedback: state.feedback.map((f) => (f.id === id ? response.data.feedback : f)),
        selectedFeedback:
          state.selectedFeedback?.feedback?.id === id
            ? { ...state.selectedFeedback, feedback: response.data.feedback }
            : state.selectedFeedback,
      }))
    } catch (error) {
      set({ error: error.message || 'Failed to update feedback' })
      throw error
    }
  },

  // Resolve feedback
  resolveFeedback: async (id) => {
    try {
      const response = await api.post(`/feedback/${id}/resolve`)
      set((state) => ({
        feedback: state.feedback.map((f) => (f.id === id ? response.data : f)),
        selectedFeedback: state.selectedFeedback?.feedback?.id === id ? { ...state.selectedFeedback, feedback: response.data } : state.selectedFeedback,
      }))
    } catch (error) {
      set({ error: error.message || 'Failed to resolve feedback' })
      throw error
    }
  },

  // Assign feedback
  assignFeedback: async (id, userId) => {
    try {
      await api.post(`/feedback/${id}/assign`, { user_id: userId })
      await get().fetchFeedbackDetail(id)
    } catch (error) {
      set({ error: error.message || 'Failed to assign feedback' })
      throw error
    }
  },

  // Add comment
  addComment: async (feedbackId, content, parentId = null) => {
    try {
      const response = await api.post('/comments', {
        feedback_id: feedbackId,
        content,
        parent_id: parentId,
      })
      await get().fetchFeedbackDetail(feedbackId)
      return response.data
    } catch (error) {
      set({ error: error.message || 'Failed to add comment' })
      throw error
    }
  },

  // Delete comment
  deleteComment: async (commentId, feedbackId) => {
    try {
      await api.delete(`/comments/${commentId}`)
      await get().fetchFeedbackDetail(feedbackId)
    } catch (error) {
      set({ error: error.message || 'Failed to delete comment' })
      throw error
    }
  },

  // Delete feedback
  deleteFeedback: async (id) => {
    try {
      await api.delete(`/feedback/${id}`)
      set((state) => ({
        feedback: state.feedback.filter((item) => item.id !== id),
        selectedFeedback: state.selectedFeedback?.feedback?.id === id ? null : state.selectedFeedback,
      }))
    } catch (error) {
      set({ error: error.message || 'Failed to delete feedback' })
      throw error
    }
  },

  // Set filters
  setFilters: (newFilters) => {
    set((state) => ({
      filters: { ...state.filters, ...newFilters, page: 1 },
    }))
  },

  // Set pagination
  setPagination: (page) => {
    set((state) => ({
      filters: { ...state.filters, page },
    }))
  },

  // Fetch plugin settings
  fetchSettings: async () => {
    set({ isLoading: true, error: null })
    try {
      const response = await api.get('/settings')
      set({ settings: response.data, isLoading: false })
    } catch (error) {
      set({ error: error.message || 'Failed to fetch settings', isLoading: false })
    }
  },

  // Save plugin settings
  saveSettings: async (settings) => {
    set({ isLoading: true, error: null })
    try {
      const response = await api.post('/settings', settings)
      set({ settings: response.data.settings, isLoading: false })
      return response.data.settings
    } catch (error) {
      set({ error: error.message || 'Failed to save settings', isLoading: false })
      throw error
    }
  },

  // Clear errors
  clearError: () => set({ error: null }),
}))
