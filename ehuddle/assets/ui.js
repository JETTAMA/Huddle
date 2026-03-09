/**
 * @fileoverview E-Huddle UI interactions — likes, comments, follows, posts, search, profile.
 * All DOM event handlers and AJAX calls for the social media frontend.
 */

/**
 * @typedef {Object} PostData
 * @property {number} id
 * @property {string} username
 * @property {string} handle
 * @property {string} avatarColor
 * @property {string} avatarLetter
 * @property {string} content
 * @property {string|null} image
 * @property {number} likes
 * @property {number} comments
 * @property {number} shares
 * @property {string} time
 * @property {boolean} liked
 * @property {boolean} bookmarked
 */

/**
 * @typedef {Object} CommentData
 * @property {number} id
 * @property {string} content
 * @property {string} username
 * @property {string} avatar_color
 * @property {string} avatar_letter
 */

/**
 * @typedef {Object} SearchUser
 * @property {number} id
 * @property {string} username
 * @property {string} first_name
 * @property {string} last_name
 * @property {string} avatar_color
 * @property {string} avatar_letter
 */

/**
 * @typedef {Object} SearchPost
 * @property {number} id
 * @property {string} content
 * @property {string} username
 */

const EH_CONFIG = window.EH_CONFIG || {};
const EH_API_BASE_URL = typeof EH_CONFIG.apiBaseUrl === 'string' ? EH_CONFIG.apiBaseUrl.replace(/\/+$/, '') : '';

/**
 * Build a backend URL using an optional production API base.
 * Falls back to same-origin relative paths when no base URL is configured.
 * @param {string} path - API path (e.g. /api/posts).
 * @returns {string}
 */
function apiUrl(path) {
  if (!EH_API_BASE_URL) return path;
  return `${EH_API_BASE_URL}${path}`;
}

/**
 * Wrapper around fetch that supports configurable backend host.
 * @param {string} path - API path or URL.
 * @param {RequestInit} [options] - Fetch options.
 * @returns {Promise<Response>}
 */
function apiFetch(path, options = {}) {
  return fetch(apiUrl(path), { credentials: 'include', ...options });
}

(() => {
  /**
   * Escape a string for safe HTML insertion.
   * @param {string} str - Raw string to escape.
   * @returns {string} HTML-escaped string.
   */
  function esc(str) {
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
  }

  // Toggle elements (comments sections)
  const toggles = document.querySelectorAll('[data-toggle]');
  toggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const targetId = toggle.getAttribute('data-toggle');
      if (!targetId) return;
      const target = document.getElementById(targetId);
      if (!target) return;
      target.classList.toggle('hidden');
    });
  });

  // Modal open
  const openModalButtons = document.querySelectorAll('[data-open-modal]');
  openModalButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const modalId = button.getAttribute('data-open-modal');
      if (!modalId) return;
      const modal = document.getElementById(modalId);
      if (!modal) return;
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';
    });
  });

  // Modal close
  const closeModalButtons = document.querySelectorAll('[data-close-modal]');
  closeModalButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const modalId = button.getAttribute('data-close-modal');
      if (!modalId) return;
      const modal = document.getElementById(modalId);
      if (!modal) return;
      modal.classList.remove('flex');
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    });
  });

  // Mobile menu
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  if (mobileMenuToggle && mobileMenu) {
    mobileMenuToggle.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });
  }

  // Notifications
  const notifToggle = document.getElementById('notifToggle');
  const notificationsMenu = document.getElementById('notificationsMenu');
  if (notifToggle && notificationsMenu) {
    notifToggle.addEventListener('click', (event) => {
      event.stopPropagation();
      notificationsMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', (event) => {
      if (!notificationsMenu.contains(event.target) && !notifToggle.contains(event.target)) {
        notificationsMenu.classList.add('hidden');
      }
    });
  }

  // Password toggles
  const passwordToggles = document.querySelectorAll('[data-password-target]');
  passwordToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const targetId = toggle.getAttribute('data-password-target');
      if (!targetId) return;
      const input = document.getElementById(targetId);
      if (!input) return;
      const icon = toggle.querySelector('i');
      const showing = input.getAttribute('type') === 'text';
      input.setAttribute('type', showing ? 'password' : 'text');
      if (icon) {
        icon.className = showing ? 'fa-regular fa-eye w-5 h-5' : 'fa-regular fa-eye-slash w-5 h-5';
      }
    });
  });

  // =================== LIKE TOGGLE ===================
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-like-post]');
    if (!btn) return;
    const postId = btn.getAttribute('data-like-post');
    try {
      const res = await apiFetch('/api/likes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: parseInt(postId) }),
      });
      const data = await res.json();
      if (data.success) {
        const icon = btn.querySelector('i');
        const countEl = btn.querySelector('.like-count');
        if (data.liked) {
          btn.classList.remove('text-muted-foreground');
          btn.classList.add('text-cartoon-red');
          icon.className = 'fa-solid fa-heart w-5 h-5';
        } else {
          btn.classList.remove('text-cartoon-red');
          btn.classList.add('text-muted-foreground');
          icon.className = 'fa-regular fa-heart w-5 h-5';
        }
        if (countEl) countEl.textContent = data.count;
      }
    } catch (err) {
      console.error('Like error:', err);
    }
  });

  // =================== BOOKMARK TOGGLE ===================
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-bookmark-post]');
    if (!btn) return;
    const postId = btn.getAttribute('data-bookmark-post');
    try {
      const res = await apiFetch('/api/bookmark', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: parseInt(postId) }),
      });
      const data = await res.json();
      if (data.success) {
        const icon = btn.querySelector('i');
        if (data.bookmarked) {
          btn.classList.remove('text-muted-foreground');
          btn.classList.add('text-cartoon-yellow');
          icon.className = 'fa-solid fa-bookmark w-5 h-5';
        } else {
          btn.classList.remove('text-cartoon-yellow');
          btn.classList.add('text-muted-foreground');
          icon.className = 'fa-regular fa-bookmark w-5 h-5';
        }
      }
    } catch (err) {
      console.error('Bookmark error:', err);
    }
  });

  // =================== COMMENT SUBMIT ===================
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-submit-comment]');
    if (!btn) return;
    const postId = btn.getAttribute('data-submit-comment');
    const input = document.querySelector(`.comment-input[data-post-id="${postId}"]`);
    if (!input || !input.value.trim()) return;

    const content = input.value.trim();
    input.value = '';

    try {
      const res = await apiFetch('/api/comments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: parseInt(postId), content }),
      });
      const data = await res.json();
      if (data.success) {
        const commentsList = document.querySelector(`.comments-list[data-post-id="${postId}"]`);
        if (commentsList) {
          const commentHtml = `
            <div class="flex items-start gap-2 mb-3">
              <div class="cartoon-avatar w-8 h-8 ${esc(data.comment.avatar_color)} flex items-center justify-center flex-shrink-0">
                <span class="font-display font-bold text-primary-foreground text-xs">${esc(data.comment.avatar_letter)}</span>
              </div>
              <div class="cartoon-bubble flex-1">
                <span class="font-display font-bold text-sm">${esc(data.comment.username)}</span>
                <p class="font-body text-sm mt-0.5">${esc(data.comment.content)}</p>
              </div>
            </div>`;
          commentsList.insertAdjacentHTML('beforeend', commentHtml);
        }
        // Update comment count
        const postCard = document.querySelector(`[data-post-id="${postId}"]`);
        if (postCard) {
          const countEl = postCard.querySelector('.comment-count');
          if (countEl) countEl.textContent = data.count;
        }
      }
    } catch (err) {
      console.error('Comment error:', err);
    }
  });

  // Allow Enter key to submit comments
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.classList.contains('comment-input')) {
      const postId = e.target.getAttribute('data-post-id');
      const submitBtn = document.querySelector(`[data-submit-comment="${postId}"]`);
      if (submitBtn) submitBtn.click();
    }
  });

  // =================== FOLLOW TOGGLE ===================
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-follow-user]');
    if (!btn) return;
    const userId = btn.getAttribute('data-follow-user');
    try {
      const res = await apiFetch('/api/follow', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: parseInt(userId) }),
      });
      const data = await res.json();
      if (data.success) {
        if (data.following) {
          btn.textContent = 'Following';
          btn.classList.remove('bg-primary', 'text-primary-foreground');
          btn.classList.add('follow-btn-following');
        } else {
          btn.textContent = 'Follow';
          btn.classList.remove('follow-btn-following');
          btn.classList.add('bg-primary', 'text-primary-foreground');
        }
      }
    } catch (err) {
      console.error('Follow error:', err);
    }
  });

  // =================== PRIVATE POST TOGGLE ===================
  const postPrivateToggle = document.getElementById('postPrivateToggle');
  const postPrivacyBtn = document.getElementById('postPrivacyBtn');
  const postPrivacyIcon = document.getElementById('postPrivacyIcon');
  const postPrivacyLabel = document.getElementById('postPrivacyLabel');

  if (postPrivateToggle && postPrivacyBtn) {
    postPrivacyBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const isPrivate = !postPrivateToggle.checked;
      postPrivateToggle.checked = isPrivate;
      if (isPrivate) {
        postPrivacyIcon.className = 'fa-solid fa-lock w-4 h-4';
        postPrivacyLabel.textContent = 'Private';
        postPrivacyBtn.classList.add('text-cartoon-purple', 'border-cartoon-purple/50', 'bg-cartoon-purple/10');
        postPrivacyBtn.classList.remove('text-muted-foreground');
      } else {
        postPrivacyIcon.className = 'fa-solid fa-globe w-4 h-4';
        postPrivacyLabel.textContent = 'Public';
        postPrivacyBtn.classList.remove('text-cartoon-purple', 'border-cartoon-purple/50', 'bg-cartoon-purple/10');
        postPrivacyBtn.classList.add('text-muted-foreground');
      }
    });
  }

  // =================== CREATE POST ===================
  const submitPostBtn = document.getElementById('submitPostBtn');
  if (submitPostBtn) {
    submitPostBtn.addEventListener('click', async () => {
      const contentEl = document.getElementById('createPostContent');
      const imageEl = document.getElementById('createPostImage');
      const content = contentEl ? contentEl.value.trim() : '';
      const imageUrl = imageEl ? imageEl.value.trim() : '';
      const isPrivate = document.getElementById('postPrivateToggle')?.checked ? '1' : '0';

      if (!content) {
        contentEl.focus();
        return;
      }

      const formData = new FormData();
      formData.append('content', content);
      if (imageUrl) formData.append('image_url', imageUrl);
      formData.append('is_private', isPrivate);

      try {
        const res = await apiFetch('/api/posts', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          // Close modal
          const modal = document.getElementById('createPostModal');
          if (modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
          }
          // Clear form
          if (contentEl) contentEl.value = '';
          if (imageEl) imageEl.value = '';
          // Reset privacy toggle
          const toggle = document.getElementById('postPrivateToggle');
          if (toggle) toggle.checked = false;
          if (postPrivacyIcon) postPrivacyIcon.className = 'fa-solid fa-globe w-4 h-4';
          if (postPrivacyLabel) postPrivacyLabel.textContent = 'Public';
          if (postPrivacyBtn) {
            postPrivacyBtn.classList.remove('text-cartoon-purple', 'border-cartoon-purple/50', 'bg-cartoon-purple/10');
            postPrivacyBtn.classList.add('text-muted-foreground');
          }

          // Prepend post to feed
          const feed = document.getElementById('postsFeed');
          if (feed) {
            const p = data.post;
            const imageHtml = p.image
              ? `<div class="mt-3 rounded-xl border-[3px] border-foreground overflow-hidden shadow-cartoon">
                   <img src="${esc(p.image)}" alt="Post" class="w-full h-48 sm:h-64 object-cover" loading="lazy" onerror="this.onerror=null;this.src='/assets/post-placeholder.svg';">
                 </div>`
              : '';

            const postHtml = `
              <div class="cartoon-card fade-in-up" data-post-id="${p.id}">
                <div class="flex items-start gap-3">
                  <div class="cartoon-avatar w-12 h-12 ${esc(p.avatarColor)} flex items-center justify-center flex-shrink-0 overflow-hidden">
                    ${p.avatarUrl
                      ? `<img src="${esc(p.avatarUrl)}" alt="${esc(p.username)}" class="w-full h-full object-cover">`
                      : `<span class="font-display font-extrabold text-primary-foreground text-lg">${esc(p.avatarLetter)}</span>`}
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <div>
                        <span class="font-display font-bold text-foreground">${esc(p.username)}</span>
                        <span class="text-muted-foreground text-sm ml-2 font-body">@${esc(p.handle)}</span>
                        <span class="text-muted-foreground text-sm ml-2">· ${esc(p.time)}</span>
                        ${p.isPrivate ? '<span class="ml-2 inline-flex items-center gap-1 text-xs font-bold text-cartoon-purple bg-cartoon-purple/10 border border-cartoon-purple/30 px-2 py-0.5 rounded-full"><i class="fa-solid fa-lock text-[10px]"></i> Private</span>' : ''}
                      </div>
                    </div>
                    <p class="mt-2 font-body text-foreground leading-relaxed">${esc(p.content)}</p>
                    ${imageHtml}
                    <div class="flex items-center gap-1 mt-3 -ml-2">
                      <button data-like-post="${p.id}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-body font-bold text-sm text-muted-foreground hover:text-cartoon-red hover:bg-cartoon-red/10 transition-colors">
                        <i class="fa-regular fa-heart w-5 h-5"></i>
                        <span class="like-count">0</span>
                      </button>
                      <button data-toggle="comments-${p.id}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-muted-foreground hover:text-cartoon-blue hover:bg-cartoon-blue/10 font-body font-bold text-sm transition-colors">
                        <i class="fa-regular fa-comment w-5 h-5"></i>
                        <span class="comment-count">0</span>
                      </button>
                      <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-muted-foreground hover:text-cartoon-green hover:bg-cartoon-green/10 font-body font-bold text-sm transition-colors">
                        <i class="fa-solid fa-share-nodes w-5 h-5"></i>
                        0
                      </button>
                      <div class="flex-1"></div>
                      <button data-bookmark-post="${p.id}" class="p-1.5 rounded-xl text-muted-foreground hover:text-cartoon-yellow transition-colors">
                        <i class="fa-regular fa-bookmark w-5 h-5"></i>
                      </button>
                    </div>
                    <div id="comments-${p.id}" class="hidden mt-3 overflow-hidden">
                      <div class="flex flex-col gap-3 pt-3 border-t-[2px] border-foreground/10">
                        <div class="comments-list" data-post-id="${p.id}"></div>
                        <div class="flex items-center gap-2 mt-1">
                          <input type="text" placeholder="Drop a comment..." class="cartoon-input flex-1 text-sm py-2 comment-input" data-post-id="${p.id}">
                          <button data-submit-comment="${p.id}" class="cartoon-btn bg-primary text-primary-foreground text-sm py-2 px-4">Send</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>`;
            feed.insertAdjacentHTML('afterbegin', postHtml);
          } else {
            // Fallback: reload the page
            window.location.reload();
          }
        }
      } catch (err) {
        console.error('Post creation error:', err);
      }
    });
  }

  // =================== CREATE POST IMAGE UPLOAD ===================
  const postImageUploadArea = document.getElementById('postImageUploadArea');
  const postImageFile = document.getElementById('postImageFile');
  const postImagePreview = document.getElementById('postImagePreview');
  const postImagePlaceholder = document.getElementById('postImagePlaceholder');
  const createPostImage = document.getElementById('createPostImage');

  if (postImageUploadArea && postImageFile) {
    postImageUploadArea.addEventListener('click', () => postImageFile.click());

    postImageFile.addEventListener('change', async (e) => {
      const file = e.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('type', 'post');
      formData.append('file', file);

      try {
        const res = await apiFetch('/api/upload', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          createPostImage.value = data.url;
          const img = postImagePreview.querySelector('img');
          img.src = data.url;
          postImagePreview.classList.remove('hidden');
          postImagePlaceholder.classList.add('hidden');
        } else {
          alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
      } catch (err) {
        console.error('Post image upload error:', err);
      }
    });
  }

  // =================== SEARCH ===================
  const searchInput = document.getElementById('searchInput');
  const searchResults = document.getElementById('searchResults');
  let searchTimeout = null;

  if (searchInput && searchResults) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      const q = searchInput.value.trim();
      if (q.length < 2) {
        searchResults.classList.add('hidden');
        return;
      }
      searchTimeout = setTimeout(async () => {
        try {
          const res = await apiFetch(`/api/search?q=${encodeURIComponent(q)}`);
          const data = await res.json();
          if (!data.success) return;

          let html = '';
          if (data.users.length > 0) {
            html += '<div class="px-4 py-2 bg-muted font-display font-bold text-sm text-muted-foreground border-b-[2px] border-foreground/10">Users</div>';
            data.users.forEach((u) => {
              const profileUrl = '/user?u=' + encodeURIComponent(u.username);
              const avatarImg = u.avatar_url
                ? `<img src="${esc(u.avatar_url)}" alt="${esc(u.username)}" class="w-full h-full object-cover">`
                : `<span class="font-display font-bold text-white text-xs">${esc(u.avatar_letter)}</span>`;
              html += `<a href="${esc(profileUrl)}" class="px-4 py-2 flex items-center gap-3 hover:bg-secondary cursor-pointer border-b-[2px] border-foreground/10 last:border-b-0">
                <div class="cartoon-avatar w-8 h-8 ${esc(u.avatar_color)} flex items-center justify-center flex-shrink-0 overflow-hidden">
                  ${avatarImg}
                </div>
                <div>
                  <p class="font-display font-bold text-sm text-foreground">${esc(u.username)}</p>
                  <p class="text-xs text-muted-foreground">${esc(u.first_name)} ${esc(u.last_name)}</p>
                </div>
              </a>`;
            });
          }
          if (data.posts.length > 0) {
            html += '<div class="px-4 py-2 bg-muted font-display font-bold text-sm text-muted-foreground border-t-[2px] border-b-[2px] border-foreground/10">Posts</div>';
            data.posts.forEach((p) => {
              const preview = p.content.length > 80 ? p.content.substring(0, 80) + '...' : p.content;
              html += `<div class="px-4 py-2 hover:bg-secondary cursor-pointer border-b-[2px] border-foreground/10 last:border-b-0">
                <p class="font-display font-bold text-sm text-foreground">${esc(p.username)}</p>
                <p class="text-xs text-muted-foreground font-body">${esc(preview)}</p>
              </div>`;
            });
          }
          if (!html) {
            html = '<div class="px-4 py-4 text-center text-muted-foreground font-body text-sm">No results found 🔍</div>';
          }
          html += `<a href="/search?q=${encodeURIComponent(q)}" class="block px-4 py-2 text-center text-sm font-display font-bold text-primary hover:bg-secondary border-t-[2px] border-foreground/20">See all results →</a>`;
          searchResults.innerHTML = html;
          searchResults.classList.remove('hidden');
        } catch (err) {
          console.error('Search error:', err);
        }
      }, 300);
    });

    // Close search results when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchResults.contains(e.target) && e.target !== searchInput) {
        searchResults.classList.add('hidden');
      }
    });
  }

  // =================== EDIT PROFILE ===================
  const editProfileBtn = document.getElementById('editProfileBtn');
  const editProfileForm = document.getElementById('editProfileForm');
  const profileInfo = document.getElementById('profileInfo');
  const saveProfileBtn = document.getElementById('saveProfileBtn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');

  if (editProfileBtn && editProfileForm && profileInfo) {
    editProfileBtn.addEventListener('click', () => {
      profileInfo.classList.add('hidden');
      editProfileForm.classList.remove('hidden');
      editProfileBtn.classList.add('hidden');
    });

    if (cancelEditBtn) {
      cancelEditBtn.addEventListener('click', () => {
        profileInfo.classList.remove('hidden');
        editProfileForm.classList.add('hidden');
        editProfileBtn.classList.remove('hidden');
      });
    }

    if (saveProfileBtn) {
      saveProfileBtn.addEventListener('click', async () => {
        const form = editProfileForm.querySelector('form');
        const formData = new FormData(form);
        const payload = {};
        formData.forEach((v, k) => (payload[k] = v));

        try {
          const res = await apiFetch('/api/profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });
          const data = await res.json();
          if (data.success) {
            // Update display
            const displayName = document.getElementById('profileDisplayName');
            const bio = document.getElementById('profileBio');
            if (displayName) displayName.textContent = `${data.user.first_name} ${data.user.last_name} ✨`;
            if (bio) bio.textContent = data.user.bio || 'No bio yet. Click Edit Profile to add one!';

            profileInfo.classList.remove('hidden');
            editProfileForm.classList.add('hidden');
            editProfileBtn.classList.remove('hidden');
          }
        } catch (err) {
          console.error('Profile update error:', err);
        }
      });
    }
  }

  // =================== PROFILE TABS ===================
  const profileTabs = document.querySelectorAll('.profile-tab');
  const profilePostsContainer = document.getElementById('profilePosts');

  profileTabs.forEach((tab) => {
    tab.addEventListener('click', async () => {
      const tabType = tab.getAttribute('data-tab');
      const userId = tab.getAttribute('data-user-id');
      
      // UI Update
      profileTabs.forEach((t) => {
        t.classList.remove('text-primary', 'border-b-[3px]', 'border-primary', 'active');
        t.classList.add('text-muted-foreground');
      });
      tab.classList.add('text-primary', 'border-b-[3px]', 'border-primary', 'active');
      tab.classList.remove('text-muted-foreground');

      if (!profilePostsContainer) return;

      // Loading state
      profilePostsContainer.innerHTML = `
        <div class="flex justify-center py-12">
          <div class="w-10 h-10 border-[4px] border-primary border-t-transparent rounded-full animate-spin"></div>
        </div>`;

      try {
        let url = `/api/profile?tab=${tabType}`;
        if (userId) url += `&user_id=${userId}`;
        const res = await apiFetch(url);
        const data = await res.json();
        if (data.success) {
          if (data.data.length === 0) {
            profilePostsContainer.innerHTML = `
              <div class="cartoon-card text-center py-12">
                <p class="text-4xl mb-3">👻</p>
                <p class="font-display font-bold text-lg">Nothing to show here!</p>
                <p class="font-body text-muted-foreground">It's a bit empty, isn't it?</p>
              </div>`;
            return;
          }

          let html = '';
          data.data.forEach(item => {
            if (tabType === 'replies') {
              html += `
                <div class="cartoon-card fade-in-up">
                  <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-cartoon-blue/10 text-cartoon-blue border border-cartoon-blue/20">REPLY</span>
                        <span class="text-muted-foreground text-xs font-body">${esc(item.time)}</span>
                      </div>
                      <p class="font-body text-foreground leading-relaxed mb-3">${esc(item.content)}</p>
                      <div class="bg-muted/30 rounded-xl p-3 border-[2px] border-foreground/5">
                        <p class="text-xs font-display font-bold text-muted-foreground mb-1">Replying to @${esc(item.post_author)}</p>
                        <p class="text-sm font-body text-muted-foreground line-clamp-2 italic">"${esc(item.post_content)}"</p>
                      </div>
                    </div>
                  </div>
                </div>`;
            } else {
              const imageHtml = item.image 
                ? `<div class="mt-3 rounded-xl border-[3px] border-foreground overflow-hidden shadow-cartoon">
                     <img src="${esc(item.image)}" alt="Post" class="w-full h-48 sm:h-64 object-cover" loading="lazy" onerror="this.onerror=null;this.src='/assets/post-placeholder.svg';">
                   </div>` 
                : '';
              
              const avatarHtml = item.avatarUrl 
                ? `<img src="${esc(item.avatarUrl)}" alt="${esc(item.username)}" class="w-full h-full object-cover">`
                : `<span class="font-display font-extrabold text-primary-foreground text-lg">${esc(item.avatarLetter)}</span>`;

              html += `
                <div class="cartoon-card fade-in-up" data-post-id="${item.id}">
                  <div class="flex items-start gap-3">
                    <div class="cartoon-avatar w-12 h-12 ${esc(item.avatarColor)} flex items-center justify-center flex-shrink-0 overflow-hidden">
                      ${avatarHtml}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <div>
                          <span class="font-display font-bold text-foreground">${esc(item.username)}</span>
                          <span class="text-muted-foreground text-sm ml-2 font-body">@${esc(item.handle)}</span>
                          <span class="text-muted-foreground text-sm ml-2">· ${esc(item.time)}</span>
                        </div>
                      </div>
                      <p class="mt-2 font-body text-foreground leading-relaxed">${esc(item.content)}</p>
                      ${imageHtml}
                      <div class="flex items-center gap-1 mt-3 -ml-2">
                        <button data-like-post="${item.id}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-body font-bold text-sm ${item.liked ? 'text-cartoon-red' : 'text-muted-foreground'} hover:text-cartoon-red hover:bg-cartoon-red/10 transition-colors">
                          <i class="${item.liked ? 'fa-solid' : 'fa-regular'} fa-heart w-5 h-5"></i>
                          <span class="like-count">${esc(item.likes)}</span>
                        </button>
                        <button data-toggle="comments-${item.id}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-muted-foreground hover:text-cartoon-blue hover:bg-cartoon-blue/10 font-body font-bold text-sm transition-colors">
                          <i class="fa-regular fa-comment w-5 h-5"></i>
                          <span class="comment-count">${esc(item.comments)}</span>
                        </button>
                        <div class="flex-1"></div>
                        <button data-bookmark-post="${item.id}" class="p-1.5 rounded-xl ${item.bookmarked ? 'text-cartoon-yellow' : 'text-muted-foreground'} hover:text-cartoon-yellow transition-colors">
                          <i class="${item.bookmarked ? 'fa-solid' : 'fa-regular'} fa-bookmark w-5 h-5"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>`;
            }
          });
          profilePostsContainer.innerHTML = html;
        }
      } catch (err) {
        console.error('Tab switch error:', err);
        profilePostsContainer.innerHTML = '<p class="text-center py-8 text-cartoon-red font-bold">Failed to load content. Try again later.</p>';
      }
    });
  });
})();

// =================== PROFILE PICTURE UPLOAD ===================
(function () {
  /**
   * Upload a file (avatar or banner) to the server.
   * @param {File} file - The file to upload.
   * @param {'avatar'|'banner'} type - Upload type.
   * @param {function(string): void} onSuccess - Callback receiving the URL on success.
   * @returns {Promise<void>}
   */
  async function handleUpload(file, type, onSuccess) {
    if (!file) return;
    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
      alert('Please select a JPEG, PNG, GIF, or WebP image.');
      return;
    }
    const formData = new FormData();
    formData.append('type', type);
    formData.append('file', file);
    try {
      const res = await apiFetch('/api/upload', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        onSuccess(data.url);
      } else {
        alert('Upload failed: ' + (data.error || 'Unknown error'));
      }
    } catch (err) {
      console.error('Upload error:', err);
      alert('Upload failed. Please try again.');
    }
  }

  // Avatar upload
  const avatarUpload = document.getElementById('avatarUpload');
  if (avatarUpload) {
    avatarUpload.addEventListener('change', (e) => {
      handleUpload(e.target.files[0], 'avatar', (url) => {
        const wrapper = document.querySelector('.avatar-upload-wrapper');
        if (!wrapper) return;
        let img = document.getElementById('avatarPreview');
        if (img) {
          img.src = url + '?t=' + Date.now();
        } else {
          // Replace letter span with img
          const letter = document.getElementById('avatarLetter');
          img = document.createElement('img');
          img.id = 'avatarPreview';
          img.alt = 'Avatar';
          img.className = 'w-full h-full object-cover';
          img.src = url;
          if (letter) letter.replaceWith(img);
          else wrapper.prepend(img);
        }
      });
      avatarUpload.value = '';
    });
  }

  // Banner upload
  const bannerUpload = document.getElementById('bannerUpload');
  if (bannerUpload) {
    bannerUpload.addEventListener('change', (e) => {
      handleUpload(e.target.files[0], 'banner', (url) => {
        const banner = document.getElementById('profileBanner');
        if (!banner) return;
        let img = document.getElementById('bannerPreview');
        if (img) {
          img.src = url + '?t=' + Date.now();
        } else {
          img = document.createElement('img');
          img.id = 'bannerPreview';
          img.alt = 'Banner';
          img.className = 'absolute inset-0 w-full h-full object-cover';
          img.src = url;
          banner.prepend(img);
        }
      });
      bannerUpload.value = '';
    });
  }
})();

// =================== LIKE / BOOKMARK MICRO-ANIMATIONS ===================
(function () {
  document.addEventListener('click', (e) => {
    const likeBtn = e.target.closest('[data-like-post]');
    if (likeBtn) {
      likeBtn.classList.add('heart-pop');
      likeBtn.addEventListener('animationend', () => likeBtn.classList.remove('heart-pop'), { once: true });
    }
    const bookmarkBtn = e.target.closest('[data-bookmark-post]');
    if (bookmarkBtn) {
      bookmarkBtn.classList.add('bookmark-pop');
      bookmarkBtn.addEventListener('animationend', () => bookmarkBtn.classList.remove('bookmark-pop'), { once: true });
    }
  });
})();
