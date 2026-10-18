import { useEffect, useState } from 'react'
import { useStore } from './store'
import Sidebar from './components/Sidebar'
import Dashboard from './pages/Dashboard'
import FeedbackList from './pages/FeedbackList'
import FeedbackDetail from './pages/FeedbackDetail'
import Settings from './pages/Settings'

export default function App() {
  const { initialize } = useStore()
  const [currentPage, setCurrentPage] = useState('dashboard')
  const [selectedFeedbackId, setSelectedFeedbackId] = useState(null)

  useEffect(() => {
    initialize()
  }, [initialize])

  return (
    <div className="lvf-admin-container flex">
      <Sidebar currentPage={currentPage} onNavigate={setCurrentPage} />
      <div className="lvf-main flex-1">
        <main className="p-6">
          {currentPage === 'dashboard' && <Dashboard onViewFeedback={(id) => {
            setSelectedFeedbackId(id)
            setCurrentPage('feedback-detail')
          }} onOpenSettings={() => setCurrentPage('settings')} />}
          {currentPage === 'feedback' && <FeedbackList onViewFeedback={(id) => {
            setSelectedFeedbackId(id)
            setCurrentPage('feedback-detail')
          }} />}
          {currentPage === 'settings' && <Settings />}
          {currentPage === 'feedback-detail' && selectedFeedbackId && (
            <FeedbackDetail
              feedbackId={selectedFeedbackId}
              onBack={() => {
                setCurrentPage('feedback')
                setSelectedFeedbackId(null)
              }}
            />
          )}
        </main>
      </div>
    </div>
  )
}
