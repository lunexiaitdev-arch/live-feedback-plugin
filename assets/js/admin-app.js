// Placeholder admin app entry. In production this is the React app built with Vite.
(function () {
    if ( typeof lunexia_lf_admin === 'undefined' && typeof lvf_admin === 'undefined' ) return;
    const root = document.getElementById('lunexia-lf-admin-app') || document.getElementById('lvf-admin-app');
    if (!root) return;
    root.innerHTML = '<h1>Lunexia Live Feedback</h1><p>Admin UI placeholder. Build the React app and replace this file.</p>';
})();
