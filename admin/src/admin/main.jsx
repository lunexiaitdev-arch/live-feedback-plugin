import './index.css'
import React from 'react'
import ReactDOM from 'react-dom/client'
import App from '../App'

const rootEl = document.getElementById('lunexia-lf-admin-app') || document.getElementById('lvf-admin-app')
if (rootEl) {
  ReactDOM.createRoot(rootEl).render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  )
}
