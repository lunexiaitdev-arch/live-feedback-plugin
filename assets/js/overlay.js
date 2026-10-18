/* LVF Frontend - Visual Feedback Collection System */
(function () {
    if (typeof lvf === 'undefined') return;

    const restBase = lvf.rest_url;
    const nonce = lvf.nonce;
    const allowGuestFeedback = lvf.allow_guest_feedback;
    const canCreate = lvf.current_user_can_create || allowGuestFeedback;
    const canManage = lvf.current_user_can_manage;
    const canReview = canManage;

    let feedbackMode = false;
    let solvingMode = false;
    let currentPos = null;
    let feedbackPins = {};
    let adminPanel = null;
    let selectedFeedbackId = null;
    let adminFeedbackList = [];
    let feedbackHighlight = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        createToggle();
    });

    function createToggle() {
        const root = document.getElementById('lvf-toggle-root');
        if (!root) return;

        const group = document.createElement('div');
        group.className = 'lvf-toggle-group';

        const feedbackItem = document.createElement('div');
        feedbackItem.className = 'lvf-toggle-item';
        const btn = document.createElement('button');
        btn.id = 'lvf-enable-feedback';
        btn.className = 'lvf-toggle-btn';
        btn.title = 'Click to enable visual feedback mode';
        btn.innerHTML = `<span class="lvf-toggle-icon">💬</span><span class="lvf-toggle-label">Enable Feedback</span>`;
        feedbackItem.appendChild(btn);
        group.appendChild(feedbackItem);

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleFeedbackMode();
        });

        if (canReview) {
            const solvingItem = document.createElement('div');
            solvingItem.className = 'lvf-toggle-item';
            const solveBtn = document.createElement('button');
            solveBtn.id = 'lvf-solving-mode-toggle';
            solveBtn.className = 'lvf-toggle-btn lvf-toggle-secondary';
            solveBtn.title = 'Toggle developing / solving mode';
            solveBtn.innerHTML = `<span class="lvf-toggle-icon">🛠</span><span class="lvf-toggle-label">Developing Mode</span>`;
            solvingItem.appendChild(solveBtn);
            group.appendChild(solvingItem);

            solveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleSolvingMode();
            });
        }

        root.appendChild(group);
    }

    function toggleFeedbackMode() {
        feedbackMode = !feedbackMode;
        const btn = document.getElementById('lvf-enable-feedback');

        if (btn) {
            const label = btn.querySelector('.lvf-toggle-label');
            const icon = btn.querySelector('.lvf-toggle-icon');

            if (feedbackMode) {
                icon.textContent = '⏹';
                label.textContent = 'Disable Feedback';
                btn.classList.add('active');
            } else {
                icon.textContent = '💬';
                label.textContent = 'Enable Feedback';
                btn.classList.remove('active');
            }
        }

        if (feedbackMode) {
            document.body.classList.add('lvf-feedback-mode');
            enablePageClicks();
            if (canReview && solvingMode) {
                createAdminPanel();
            }
            loadExistingFeedback();
        } else {
            document.body.classList.remove('lvf-feedback-mode');
            disablePageClicks();
            clearFeedbackPins();
            clearFeedbackHighlight();
            if (adminPanel) {
                removeAdminPanel();
            }
        }
    }

    function toggleSolvingMode() {
        solvingMode = !solvingMode;
        const solveBtn = document.getElementById('lvf-solving-mode-toggle');
        if (!solveBtn) return;

        const label = solveBtn.querySelector('.lvf-toggle-label');
        const icon = solveBtn.querySelector('.lvf-toggle-icon');

        if (solvingMode) {
            icon.textContent = '🛠';
            label.textContent = 'Solving Mode On';
            solveBtn.classList.add('active');
            document.body.classList.add('lvf-solving-mode');

            if (canReview && !adminPanel) {
                createAdminPanel();
            }

            loadExistingFeedback();
        } else {
            icon.textContent = '🛠';
            label.textContent = 'Developing Mode';
            solveBtn.classList.remove('active');
            document.body.classList.remove('lvf-solving-mode');
            if (adminPanel) {
                removeAdminPanel();
            }
        }

        if (adminPanel) {
            const status = adminPanel.querySelector('.lvf-admin-panel-status');
            if (status) {
                status.innerText = solvingMode ? 'Mode: Solving' : 'Mode: Live';
            }
        }
    }

    function enablePageClicks() {
        document.addEventListener('click', handlePageClick, true);
    }

    function disablePageClicks() {
        document.removeEventListener('click', handlePageClick, true);
    }

    function handlePageClick(e) {
        if (!feedbackMode || solvingMode) return;

        // Don't intercept clicks on overlay UI or existing feedback elements
        if (e.target.closest('.lvf-modal') || e.target.closest('.lvf-preview-modal') || e.target.closest('.lvf-admin-panel') || e.target.closest('.lvf-toggle-group') || e.target.closest('#lvf-enable-feedback') || e.target.closest('.lvf-pin')) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        currentPos = {
            x: Math.round(window.pageXOffset + e.clientX),
            y: Math.round(window.pageYOffset + e.clientY),
            selector: getElementSelector(e.target)
        };

        openFeedbackModal();
    }

    function getElementSelector(el) {
        if (!el || el === document.body || el === document.documentElement) return '';

        const parts = [];
        while (el && el.nodeType === Node.ELEMENT_NODE && el !== document.body) {
            let part = el.tagName.toLowerCase();
            if (el.id) {
                part += '#' + el.id;
                parts.unshift(part);
                break;
            }
            if (el.classList && el.classList.length) {
                part += '.' + Array.from(el.classList).join('.');
            }

            const parent = el.parentNode;
            if (parent && parent.children) {
                const siblings = Array.from(parent.children).filter((child) => child.tagName === el.tagName);
                if (siblings.length > 1) {
                    const index = siblings.indexOf(el) + 1;
                    part += `:nth-of-type(${index})`;
                }
            }

            parts.unshift(part);
            el = parent;
        }

        return parts.join(' > ');
    }

    function getDeviceType() {
        const width = window.innerWidth;
        if (width <= 768) return 'mobile';
        if (width <= 1024) return 'tablet';
        return 'desktop';
    }

    const guestCookieName = 'lvf_guest_session';

    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${encodeURIComponent(value)};path=/;expires=${expires.toUTCString()}`;
    }

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }

    function parseGuestCookie() {
        const raw = getCookie(guestCookieName);
        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(decodeURIComponent(raw));
        } catch (err) {
            return null;
        }
    }

    function saveGuestCookie(data) {
        if (!data || typeof data !== 'object') {
            return;
        }
        setCookie(guestCookieName, JSON.stringify(data), 30);
    }

    function generateSessionId() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'lvf-' + Math.random().toString(36).substring(2, 15) + '-' + Math.random().toString(36).substring(2, 15);
    }

    function getGuestInfo() {
        const guest = parseGuestCookie();
        if (guest && guest.session_id) {
            return guest;
        }
        return null;
    }

    function escapeHtml(value) {
        if (!value) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getGuestSessionQuery() {
        const guest = getGuestInfo();
        if (!guest || !guest.session_id) {
            return '';
        }
        return `guest_session=${encodeURIComponent(guest.session_id)}`;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function openFeedbackModal() {
        // Remove existing modal if any
        const existing = document.querySelector('.lvf-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.className = 'lvf-modal';
        modal.innerHTML = `
            <div class="lvf-modal-inner">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600;">Report Issue</h3>
                    <button class="lvf-modal-close" type="button" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #666; display:flex; align-items:center; gap:8px;">
                        <span style="font-size:20px;line-height:1;">×</span>
                        <span>Close</span>
                    </button>
                </div>

                <div style="margin-bottom: 12px; font-size: 12px; color: #666;">
                    Position: <strong>${currentPos.x}, ${currentPos.y}</strong>
                </div>

                ${!lvf.current_user_can_create ? `
                    <div style="margin-bottom: 12px; display: grid; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #333;">Your name</label>
                            <input id="lvf-guest-name" type="text" value="${escapeHtml(getGuestInfo()?.name || '')}" placeholder="Jane Doe" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit; font-size: 14px;" />
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #333;">Your email</label>
                            <input id="lvf-guest-email" type="email" value="${escapeHtml(getGuestInfo()?.email || '')}" placeholder="you@example.com" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit; font-size: 14px;" />
                        </div>
                    </div>
                ` : ''}

                <textarea id="lvf-feedback-text" 
                    placeholder="Describe the issue or feedback..."
                    style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit; font-size: 14px; resize: vertical; margin-bottom: 12px; min-height: 100px;"
                ></textarea>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #333;">
                        Attach Screenshot (optional)
                    </label>
                    <input type="file" id="lvf-image-upload" accept="image/*" 
                        style="display: block; font-size: 12px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; width: 100%;">
                    <div id="lvf-image-preview" style="margin-top: 8px; display: none;">
                        <img id="lvf-preview-img" style="max-width: 100%; max-height: 150px; border-radius: 6px; border: 1px solid #e5e7eb;">
                    </div>
                </div>

                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button id="lvf-modal-cancel" type="button" 
                        style="padding: 8px 16px; border: 1px solid #e5e7eb; background: white; color: #333; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Cancel
                    </button>
                    <button id="lvf-modal-submit" type="button" 
                        style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Submit Feedback
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Handle image upload
        const imageInput = document.getElementById('lvf-image-upload');
        imageInput.addEventListener('change', (e) => handleImageSelect(e));

        // Handle close
        document.querySelector('.lvf-modal-close').addEventListener('click', () => modal.remove());
        document.getElementById('lvf-modal-cancel').addEventListener('click', () => modal.remove());

        // Handle submit
        document.getElementById('lvf-modal-submit').addEventListener('click', submitFeedback);

        // Close on escape
        const closeOnEscape = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeOnEscape);
            }
        };
        document.addEventListener('keydown', closeOnEscape);

        // Focus text area
        document.getElementById('lvf-feedback-text').focus();
    }

    function handleImageSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            return;
        }

        if (file.size > 5242880) { // 5MB
            alert('Image must be smaller than 5MB');
            return;
        }

        // Preview
        const reader = new FileReader();
        reader.onload = (event) => {
            document.getElementById('lvf-preview-img').src = event.target.result;
            document.getElementById('lvf-image-preview').style.display = 'block';
            // Store for later
            e.target.dataset.base64 = event.target.result;
        };
        reader.readAsDataURL(file);
    }

    function submitFeedback() {
        const text = document.getElementById('lvf-feedback-text').value.trim();
        const imageInput = document.getElementById('lvf-image-upload');
        const screenshotBase64 = imageInput.dataset.base64 || null;

        if (!text && !screenshotBase64) {
            alert('Please enter feedback or select an image');
            return;
        }

        const guest = getGuestInfo() || {};
        let guestName = guest.name || '';
        let guestEmail = guest.email || '';
        let guestSession = guest.session_id || '';

        if (!lvf.current_user_can_create) {
            const nameInput = document.getElementById('lvf-guest-name');
            const emailInput = document.getElementById('lvf-guest-email');
            if (nameInput) {
                guestName = nameInput.value.trim();
            }
            if (emailInput) {
                guestEmail = emailInput.value.trim();
            }

            if (!guestName) {
                alert('Please enter your name');
                return;
            }
            if (!guestEmail || !isValidEmail(guestEmail)) {
                alert('Please enter a valid email address');
                return;
            }
        }

        if (!guestSession) {
            guestSession = generateSessionId();
        }

        saveGuestCookie({
            session_id: guestSession,
            name: guestName,
            email: guestEmail,
        });

        const btn = document.getElementById('lvf-modal-submit');
        btn.disabled = true;
        btn.innerText = 'Submitting...';

        // Step 1: Create feedback
        const feedbackPayload = {
            page_url: window.location.href,
            x: currentPos.x,
            y: currentPos.y,
            selector: currentPos.selector,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            browser: navigator.userAgent,
            device_type: getDeviceType(),
            initial_comment: text,
            screenshot_base64: screenshotBase64,
            guest_session: guestSession,
            guest_name: guestName,
            guest_email: guestEmail,
        };

        const headers = {
            'Content-Type': 'application/json'
        };

        if (nonce) {
            headers['X-WP-Nonce'] = nonce;
        }

        const fallbackSubmit = (url) => {
            return fetch(url, {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify(feedbackPayload),
            }).then(fallbackResponse => {
                if (!fallbackResponse.ok) {
                    return fallbackResponse.text().then(text => {
                        console.error('Fallback submission failed:', url, fallbackResponse.status, text);
                        throw new Error('Fallback request returned ' + fallbackResponse.status);
                    });
                }
                return fallbackResponse.json();
            });
        };

        fetch(restBase + 'feedback', {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify(feedbackPayload)
        })
            .then(r => {
                if (!r.ok) {
                    if (r.status === 403 || r.status === 404 || r.status === 500) {
                        return r.text().then(text => {
                            console.warn('REST submit failed, falling back to admin-post or ajax:', r.status, text);
                            return fallbackSubmit(lvf.fallback_submit_url)
                                .catch(() => fallbackSubmit(lvf.fallback_ajax_url));
                        });
                    }
                    return r.text().then(text => {
                        console.error('Server error:', text);
                        throw new Error('Server returned ' + r.status);
                    });
                }
                return r.json();
            })
            .then(data => {
                if (data.id) {
                    // Success
                    createPinAt(currentPos, data.id);
                    document.querySelector('.lvf-modal').remove();
                    alert('✅ Feedback submitted successfully!');

                    // Re-enable feedback mode for next item
                    feedbackMode = true;
                } else {
                    throw new Error(data.message || 'Failed to submit');
                }
            })
            .catch(err => {
                console.error('Submit error:', err);
                alert('❌ Error submitting feedback: ' + err.message);
                btn.disabled = false;
                btn.innerText = 'Submit Feedback';
            });
    }

    function createPinAt(pos, id, status) {
        if (feedbackPins[id]) {
            return;
        }

        const pin = document.createElement('div');
        pin.className = 'lvf-pin';
        if (status) {
            pin.classList.add(`lvf-pin-${status}`);
            pin.dataset.status = status;
        }
        pin.style.left = pos.x + 'px';
        pin.style.top = pos.y + 'px';
        pin.dataset.id = id;
        pin.innerHTML = `<span class="lvf-pin-label">${id}</span>`;
        pin.title = canReview ? 'Feedback #' + id + ' — click to view comments' : 'Feedback #' + id;
        document.body.appendChild(pin);

        feedbackPins[id] = pin;
        if (selectedFeedbackId === id) {
            pin.classList.add('active');
        }

        pin.addEventListener('click', (e) => {
            e.stopPropagation();
            if (adminPanel) {
                selectAdminFeedback(id);
                return;
            }

            if (solvingMode && canReview) {
                selectedFeedbackId = id;
                createAdminPanel();
                if (adminFeedbackList.length) {
                    renderAdminPanelList(adminFeedbackList);
                    selectAdminFeedback(id);
                } else {
                    loadExistingFeedback();
                }
                return;
            }

            showFeedbackPreview(id);
        });
    }

    function createAdminPanel() {
        if (adminPanel) return;

        adminPanel = document.createElement('div');
        adminPanel.className = 'lvf-admin-panel';
        adminPanel.innerHTML = `
            <div class="lvf-admin-panel-header">
                <div class="lvf-admin-panel-title-group">
                    <div class="lvf-admin-panel-title">Feedback Review</div>
                    <div class="lvf-admin-panel-subtitle">Manage pinned comments and resolve issues.</div>
                </div>
                <div class="lvf-admin-panel-header-actions">
                    <span class="lvf-admin-panel-status">${solvingMode ? 'Solving Mode' : 'Live Mode'}</span>
                    <button class="lvf-admin-panel-close" type="button" style="display:flex;align-items:center;gap:8px;font-size:14px;padding:6px 8px;">
                        <span style="font-size:18px;line-height:1;">×</span>
                        <span>Close</span>
                    </button>
                </div>
            </div>
            <div class="lvf-admin-panel-body">
                <aside class="lvf-admin-panel-list-area">
                    <div class="lvf-admin-panel-list-header">
                        <span>Live Feedback</span>
                        <span class="lvf-admin-panel-count">${adminFeedbackList.filter((fb) => fb.status === 'open').length}</span>
                    </div>
                    <div class="lvf-admin-panel-list"></div>
                </aside>
                <section class="lvf-admin-panel-details">
                    <div class="lvf-admin-panel-empty">Select a feedback item to review details, comments, and resolution actions.</div>
                </section>
            </div>
        `;

        document.body.appendChild(adminPanel);

        if (adminFeedbackList.length) {
            renderAdminPanelList(adminFeedbackList);
        }

        adminPanel.querySelector('.lvf-admin-panel-close').addEventListener('click', () => {
            removeAdminPanel();
        });
    }

    function removeAdminPanel() {
        if (!adminPanel) return;
        adminPanel.remove();
        adminPanel = null;
        selectedFeedbackId = null;
    }

    function setActivePin(id) {
        Object.entries(feedbackPins).forEach(([pinId, pin]) => {
            if (pinId === String(id)) {
                pin.classList.add('active');
                pin.scrollIntoView({ block: 'center', inline: 'center', behavior: 'smooth' });
            } else {
                pin.classList.remove('active');
            }
        });
    }

    function highlightFeedbackPoint(pos) {
        clearFeedbackHighlight();
        if (!pos || pos.x === undefined || pos.y === undefined) return;

        feedbackHighlight = document.createElement('div');
        feedbackHighlight.className = 'lvf-feedback-highlight';
        feedbackHighlight.style.left = pos.x + 'px';
        feedbackHighlight.style.top = pos.y + 'px';
        document.body.appendChild(feedbackHighlight);
    }

    function clearFeedbackHighlight() {
        if (feedbackHighlight) {
            feedbackHighlight.remove();
            feedbackHighlight = null;
        }
    }

    function renderAdminPanelList(feedbacks) {
        if (!adminPanel) return;
        const listContainer = adminPanel.querySelector('.lvf-admin-panel-list');
        const countBadge = adminPanel.querySelector('.lvf-admin-panel-count');
        if (countBadge) {
            const openFeedbackCount = feedbacks.filter((fb) => fb.status === 'open').length;
            countBadge.textContent = openFeedbackCount;
        }
        if (!listContainer) return;

        listContainer.innerHTML = '';

        if (!feedbacks.length) {
            listContainer.innerHTML = '<div class="lvf-admin-panel-empty">No feedback on this page yet.</div>';
            return;
        }

        feedbacks.forEach((fb) => {
            const item = document.createElement('button');
            item.type = 'button';
            const statusClass = fb.status ? `lvf-admin-panel-item-${fb.status}` : '';
            item.className = `lvf-admin-panel-item ${statusClass}`.trim();
            item.dataset.feedbackId = fb.id;
            item.dataset.status = fb.status || '';
            item.innerHTML = `
                <div class="lvf-admin-panel-item-row">
                    <span class="lvf-admin-panel-item-number">${fb.id}</span>
                    <div class="lvf-admin-panel-item-content">
                        <div class="lvf-admin-panel-item-top">
                            <span class="lvf-admin-panel-item-title">Feedback</span>
                            <span class="lvf-admin-panel-item-meta">${fb.status.toUpperCase()} • ${fb.priority.toUpperCase()}</span>
                        </div>
                        <div class="lvf-admin-panel-item-subtitle">${fb.selector || 'Page comment point'}</div>
                    </div>
                </div>
            `;
            item.addEventListener('click', () => selectAdminFeedback(fb.id));
            listContainer.appendChild(item);
        });

        if (feedbacks.length) {
            if (selectedFeedbackId) {
                const matched = feedbacks.some((fb) => String(fb.id) === String(selectedFeedbackId));
                if (matched) {
                    selectAdminFeedback(selectedFeedbackId);
                } else {
                    selectAdminFeedback(feedbacks[0].id);
                }
            } else {
                selectAdminFeedback(feedbacks[0].id);
            }
        }
    }

    function selectAdminFeedback(id) {
        selectedFeedbackId = id;

        if (!adminPanel) return;

        const items = adminPanel.querySelectorAll('.lvf-admin-panel-item');
        items.forEach((item) => {
            item.classList.toggle('active', item.dataset.feedbackId === String(id));
        });

        setActivePin(id);

        fetch(restBase + 'feedback/' + id, {
            headers: { 'X-WP-Nonce': nonce },
            credentials: 'same-origin'
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.feedback) {
                    renderAdminFeedbackDetails(data);
                    if (data.feedback.coord_x !== undefined && data.feedback.coord_y !== undefined) {
                        highlightFeedbackPoint({ x: data.feedback.coord_x, y: data.feedback.coord_y });
                    }
                }
            })
            .catch((err) => console.error('Error loading feedback details:', err));
    }

    function renderAdminFeedbackDetails(data) {
        if (!adminPanel) return;
        const details = adminPanel.querySelector('.lvf-admin-panel-details');
        if (!details) return;

        const feedback = data.feedback;
        const comments = data.comments || [];

        let html = `
            <div class="lvf-admin-feedback-header">
                <div>
                    <div class="lvf-admin-feedback-title">Feedback #${feedback.id}</div>
                    <div class="lvf-admin-feedback-meta">
                        <span class="lvf-admin-feedback-status-label lvf-admin-feedback-status-label-${feedback.status}">${feedback.status.toUpperCase()}</span>
                        <span>${feedback.priority.toUpperCase()} • ${feedback.device_type || 'desktop'}</span>
                    </div>
                </div>
            </div>
        `;

        if (feedback.screenshot_url) {
            html += `
                <div class="lvf-admin-feedback-screenshot">
                    <img src="${feedback.screenshot_url}" alt="Screenshot">
                </div>
            `;
        }

        html += `
            <div class="lvf-admin-feedback-comments-header">Comments (${comments.length})</div>
        `;

        if (!comments.length) {
            html += '<div class="lvf-admin-panel-empty">No comments yet.</div>';
        } else {
            comments.forEach((c) => {
                html += `
                    <div class="lvf-admin-feedback-comment">
                        <div class="lvf-admin-feedback-comment-author">${c.author_name || 'Anonymous'}</div>
                        <div class="lvf-admin-feedback-comment-time">${new Date(c.created_at).toLocaleString()}</div>
                        <div class="lvf-admin-feedback-comment-content">${c.content}</div>
                    </div>
                `;
            });
        }

        if (canCreate) {
            html += `
                <div class="lvf-admin-feedback-add-comment">
                    <textarea id="lvf-admin-new-comment" placeholder="Reply or add an internal note..."></textarea>
                    <button id="lvf-admin-add-comment-button" type="button">Post Comment</button>
                </div>
            `;
        }

        if (feedback.status !== 'resolved' && canManage) {
            html += `
                <button id="lvf-admin-resolve-feedback-button" type="button" class="lvf-admin-panel-action">Mark as Completed</button>
            `;
        }

        details.innerHTML = html;

        const addCommentButton = details.querySelector('#lvf-admin-add-comment-button');
        if (addCommentButton) {
            addCommentButton.addEventListener('click', () => {
                const textarea = details.querySelector('#lvf-admin-new-comment');
                if (!textarea || !textarea.value.trim()) {
                    alert('Please enter a comment.');
                    return;
                }
                addCommentButton.disabled = true;
                addCommentButton.innerText = 'Posting...';

                const guest = getGuestInfo();
                const commentPayload = {
                    feedback_id: feedback.id,
                    content: textarea.value.trim()
                };

                if (guest && guest.session_id) {
                    commentPayload.guest_session = guest.session_id;
                    commentPayload.guest_name = guest.name || '';
                    commentPayload.guest_email = guest.email || '';
                }

                fetch(restBase + 'comments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nonce
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(commentPayload)
                })
                    .then(r => {
                        if (!r.ok) {
                            return r.text().then(text => {
                                console.error('Server error:', text);
                                throw new Error('Server returned ' + r.status);
                            });
                        }
                        return r.json();
                    })
                    .then(data => {
                        textarea.value = '';
                        addCommentButton.disabled = false;
                        addCommentButton.innerText = 'Post Comment';
                        selectAdminFeedback(feedback.id);
                    })
                    .catch(err => {
                        console.error('Comment error:', err);
                        alert('Unable to post comment.');
                        addCommentButton.disabled = false;
                        addCommentButton.innerText = 'Post Comment';
                    });
            });
        }

        const resolveButton = details.querySelector('#lvf-admin-resolve-feedback-button');
        if (resolveButton) {
            resolveButton.addEventListener('click', () => {
                resolveFeedback(feedback.id, null, null);
                selectAdminFeedback(feedback.id);
            });
        }
    }

    function showFeedbackPreview(id) {
        // Fetch feedback details
        const guest = getGuestInfo();
        const previewUrl = restBase + 'feedback/' + id + (guest && guest.session_id ? '?guest_session=' + encodeURIComponent(guest.session_id) : '');

        fetch(previewUrl, {
            headers: { 'X-WP-Nonce': nonce },
            credentials: 'same-origin'
        })
            .then(r => r.json())
            .then(data => {
                if (data.feedback) {
                    displayPreview(data);
                }
            })
            .catch(err => console.error('Error loading preview:', err));
    }

    function resolveFeedback(id, preview, backdrop) {
        fetch(restBase + 'feedback/' + id + '/resolve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            },
            credentials: 'same-origin'
        })
            .then((r) => {
                if (!r.ok) {
                    return r.text().then(text => {
                        console.error('Resolve server error:', text);
                        throw new Error('Server returned ' + r.status);
                    });
                }
                return r.json();
            })
            .then((data) => {
                if (data && data.status === 'resolved') {
                    alert('Feedback marked completed.');
                    if (preview) preview.remove();
                    if (backdrop) backdrop.remove();
                } else if (data && data.feedback && data.feedback.status === 'resolved') {
                    alert('Feedback marked completed.');
                    if (preview) preview.remove();
                    if (backdrop) backdrop.remove();
                } else {
                    throw new Error(data.message || 'Unable to resolve feedback');
                }
            })
            .catch((err) => {
                console.error('Resolve error:', err);
                alert('Unable to mark feedback completed.');
            });
    }

    function getDeviceLabel(type) {
        switch (type) {
            case 'mobile':
                return 'Mobile';
            case 'tablet':
                return 'Tablet';
            case 'desktop':
                return 'Laptop/Desktop';
            default:
                return type ? type.charAt(0).toUpperCase() + type.slice(1) : 'Laptop/Desktop';
        }
    }

    function displayPreview(data) {
        const feedback = data.feedback;
        const comments = data.comments || [];
        const deviceLabel = getDeviceLabel(feedback.device_type);

        // Remove existing preview
        const existing = document.querySelector('.lvf-preview-modal');
        if (existing) existing.remove();

        const preview = document.createElement('div');
        preview.className = 'lvf-preview-modal';
        preview.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            z-index: 1000001;
            max-width: 600px;
            width: 90%;
            max-height: 85vh;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        `;

        let html = `
            <div class="lvf-preview-header">
                <div>
                    <h3>Feedback #${feedback.id}</h3>
                    <div class="lvf-preview-meta">Created: ${new Date(feedback.created_at).toLocaleString()}</div>
                </div>
                <button id="lvf-preview-close" class="lvf-preview-button secondary" type="button" style="display:flex;align-items:center;gap:8px;padding:6px 8px;font-size:14px;">
                    <span style="font-size:18px;line-height:1;">×</span>
                    <span>Close</span>
                </button>
            </div>

            <div class="lvf-preview-content">
                <div class="lvf-preview-summary">
                    <div class="lvf-preview-summary-item">
                        <span>Status</span>
                        <span class="lvf-preview-chip ${feedback.status}">${feedback.status}</span>
                    </div>
                    <div class="lvf-preview-summary-item">
                        <span>Priority</span>
                        <span class="lvf-preview-chip priority-${feedback.priority}">${feedback.priority}</span>
                    </div>
                    <div class="lvf-preview-summary-item">
                        <span>Device</span>
                        <span>${deviceLabel}</span>
                    </div>
                    <div class="lvf-preview-summary-item">
                        <span>Viewport</span>
                        <span>${feedback.viewport_width}×${feedback.viewport_height}</span>
                    </div>
                </div>

                <div class="lvf-preview-section">
                    <div class="lvf-preview-section-title">Technical details</div>
                    <div class="lvf-preview-technical-box">
                        <div>
                            <span class="lvf-preview-technical-label">URL</span>
                            <a href="${feedback.page_url}" target="_blank" rel="noreferrer">${feedback.page_url}</a>
                        </div>
                        ${feedback.selector ? `
                            <div>
                                <span class="lvf-preview-technical-label">Selector</span>
                                <code>${feedback.selector}</code>
                            </div>
                        ` : ''}
                        <div>
                            <span class="lvf-preview-technical-label">Screen</span>
                            <span>${feedback.screen_width}×${feedback.screen_height}</span>
                        </div>
                        <div>
                            <span class="lvf-preview-technical-label">Viewport</span>
                            <span>${feedback.viewport_width}×${feedback.viewport_height}</span>
                        </div>
                        ${feedback.browser_info ? `
                            <div>
                                <span class="lvf-preview-technical-label">Browser</span>
                                <span>${feedback.browser_info}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
        `;

        if (feedback.screenshot_url) {
            html += `
                <div class="lvf-preview-section">
                    <div class="lvf-preview-section-title">Screenshot</div>
                    <img src="${feedback.screenshot_url}" alt="Screenshot">
                </div>
            `;
        }

        html += `
            <div class="lvf-preview-section">
                <h4 class="lvf-preview-section-title">Comments (${comments.length})</h4>
            </div>
        `;

        if (comments.length === 0) {
            html += '<p class="lvf-preview-meta">No comments yet</p>';
        } else {
            html += '<div class="lvf-preview-comments">';
            comments.forEach((c) => {
                html += `
                    <div class="lvf-preview-comment">
                        <div class="lvf-preview-comment-author">
                            <span>${c.author_name || 'Unknown'}</span>
                            <span class="lvf-preview-comment-time">${new Date(c.created_at).toLocaleString()}</span>
                        </div>
                        <div class="lvf-preview-comment-body">${c.content}</div>
                    </div>
                `;
            });
            html += '</div>';
        }

        if (canCreate) {
            html += `
                <div class="lvf-preview-section">
                    <textarea id="lvf-new-comment" placeholder="Add a comment..." class="lvf-preview-comment-textarea"></textarea>
                    <button id="lvf-add-comment-button" class="lvf-preview-button primary">Post Comment</button>
                </div>
            `;
        }

        html += '</div>';

        if (canManage && feedback.status !== 'resolved') {
            html += `
                <div class="lvf-preview-actions">
                    <button id="lvf-resolve-feedback-button" class="lvf-preview-button primary">✓ Mark as Completed</button>
                </div>
            `;
        }

        preview.innerHTML = html;
        document.body.appendChild(preview);

        // Close on background click
        const backdrop = document.createElement('div');
        backdrop.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000000;
            background: rgba(15, 23, 42, 0.35);
        `;
        backdrop.addEventListener('click', () => {
            preview.remove();
            backdrop.remove();
        });
        document.body.insertBefore(backdrop, preview);

        if (canManage && feedback.status !== 'resolved') {
            const resolveButton = document.getElementById('lvf-resolve-feedback-button');
            if (resolveButton) {
                resolveButton.addEventListener('click', () => {
                    resolveFeedback(feedback.id, preview, backdrop);
                });
            }
        }

        const closeButton = document.getElementById('lvf-preview-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                preview.remove();
                backdrop.remove();
            });
        }

        // Add comment button listener
        const addCommentButton = document.getElementById('lvf-add-comment-button');
        if (addCommentButton) {
            addCommentButton.addEventListener('click', () => {
                const textarea = document.getElementById('lvf-new-comment');
                if (!textarea || !textarea.value.trim()) {
                    alert('Please enter a comment');
                    return;
                }

                addCommentButton.disabled = true;
                addCommentButton.innerText = 'Posting...';

                const guest = getGuestInfo();
                const commentPayload = {
                    feedback_id: feedback.id,
                    content: textarea.value.trim()
                };

                if (guest && guest.session_id) {
                    commentPayload.guest_session = guest.session_id;
                    commentPayload.guest_name = guest.name || '';
                    commentPayload.guest_email = guest.email || '';
                }

                fetch(restBase + 'comments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nonce
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(commentPayload)
                })
                    .then(r => {
                        if (!r.ok) {
                            return r.text().then(text => {
                                console.error('Server error:', text);
                                throw new Error('Server returned ' + r.status);
                            });
                        }
                        return r.json();
                    })
                    .then(data => {
                        if (data.id) {
                            // Refresh preview with new comment
                            showFeedbackPreview(feedback.id);
                        } else {
                            throw new Error(data.message || 'Failed to add comment');
                        }
                    })
                    .catch(err => {
                        console.error('Comment error:', err);
                        alert('❌ Error posting comment: ' + err.message);
                        addCommentButton.disabled = false;
                        addCommentButton.innerText = 'Post Comment';
                    });
            });
        }
    }

    function loadExistingFeedback() {
        if (!feedbackMode && !solvingMode) {
            return;
        }

        const params = new URLSearchParams();
        params.append('page_url', window.location.href);
        if (!canManage) {
            params.append('device_type', getDeviceType());
        }

        const guest = getGuestInfo();
        if (!canManage && guest && guest.session_id) {
            params.append('guest_session', guest.session_id);
        }

        fetch(restBase + 'feedback?' + params.toString(), {
            headers: { 'X-WP-Nonce': nonce },
            credentials: 'same-origin'
        })
            .then(r => r.json())
            .then(data => {
                const feedbacks = Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : [];
                feedbacks.forEach(fb => {
                    createPinAt({ x: fb.coord_x, y: fb.coord_y }, fb.id, fb.status);
                });
                if (canManage) {
                    adminFeedbackList = feedbacks;
                    renderAdminPanelList(feedbacks);
                }
            })
            .catch(err => console.error('Error loading feedback:', err));
    }

    function clearFeedbackPins() {
        Object.values(feedbackPins).forEach((pin) => pin.remove());
        feedbackPins = {};
    }
})();
