(function () {
  let currentTabId = null;
  let currentTabUrl = '';
  let currentTabTitle = '';
  let currentRenderSummary = '';

  const $ = (id) => document.getElementById(id);

  // --- Tab info ---
  async function loadTabInfo() {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (!tab) return;

    currentTabId = tab.id;
    currentTabUrl = tab.url || '';
    currentTabTitle = tab.title || new URL(currentTabUrl).hostname || 'Unknown';

    const hostname = (() => { try { return new URL(currentTabUrl).hostname; } catch { return ''; } })();
    const faviconUrl = hostname ? `https://www.google.com/s2/favicons?domain=${hostname}&sz=32` : '';

    $('sp-hostname').textContent = hostname;
    $('sp-title').textContent = currentTabTitle;
    $('sp-url').textContent = currentTabUrl;
    $('sp-favicon').src = faviconUrl;

    $('sp-render-title').textContent = currentTabTitle;
    $('sp-render-url').textContent = currentTabUrl;
    $('sp-render-favicon').src = faviconUrl;

    $('sp-info-title').textContent = currentTabTitle;
    $('sp-info-url').textContent = currentTabUrl;
    $('sp-info-favicon').src = faviconUrl;

    loadMetadata();
  }

  async function loadMetadata() {
    if (!currentTabId) return;
    try {
      const response = await chrome.tabs.sendMessage(currentTabId, { action: 'getMetadata' });
      if (response && response.meta) {
        renderMetaGrid(response.meta);
      }
    } catch {
      try {
        await chrome.scripting.executeScript({
          target: { tabId: currentTabId },
          files: ['content-script.js']
        });
        setTimeout(async () => {
          try {
            const response = await chrome.tabs.sendMessage(currentTabId, { action: 'getMetadata' });
            if (response && response.meta) renderMetaGrid(response.meta);
          } catch { renderMetaGrid(getBasicMeta()); }
        }, 200);
      } catch { renderMetaGrid(getBasicMeta()); }
    }
  }

  function getBasicMeta() {
    return { title: currentTabTitle, url: currentTabUrl };
  }

  function renderMetaGrid(meta) {
    const labels = {
      title: 'Title', description: 'Description', keywords: 'Keywords', author: 'Author',
      'og:title': 'OG Title', 'og:description': 'OG Description', 'og:image': 'OG Image', 'og:site_name': 'Site Name'
    };
    const grid = $('sp-meta-grid');
    grid.innerHTML = '';
    for (const [key, label] of Object.entries(labels)) {
      const val = meta[key] || '';
      if (val) {
        grid.innerHTML += `<div class="kh-sp-meta-card"><div class="kh-sp-meta-label">${esc(label)}</div><div class="kh-sp-meta-value">${esc(val)}</div></div>`;
      }
    }
    if (!grid.innerHTML) {
      grid.innerHTML = '<div class="kh-sp-empty"><div class="kh-sp-empty-text">No metadata found</div></div>';
    }
  }

  // --- Navigation ---
  document.querySelectorAll('.kh-sp-nav-btn[data-view]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.kh-sp-nav-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      ['save', 'render', 'search', 'info', 'config'].forEach(v => {
        const el = $('sp-view-' + v);
        if (el) el.classList.toggle('kh-sp-hidden', v !== btn.dataset.view);
      });
    });
  });

  function switchView(name) {
    document.querySelectorAll('.kh-sp-nav-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector(`.kh-sp-nav-btn[data-view="${name}"]`);
    if (btn) btn.classList.add('active');
    ['save', 'render', 'search', 'info', 'config'].forEach(v => {
      const el = $('sp-view-' + v);
      if (el) el.classList.toggle('kh-sp-hidden', v !== name);
    });
  }

  // --- Status ---
  function showStatus(msg, type) {
    const s = $('sp-status');
    s.className = 'kh-sp-status ' + type;
    s.textContent = msg;
    setTimeout(() => { s.className = 'kh-sp-status'; s.textContent = ''; }, 3000);
  }

  // --- API ---
  async function getConfig() {
    return new Promise(resolve => chrome.storage.sync.get(['apiUrl', 'apiToken'], resolve));
  }

  async function apiRequest(path, opts = {}) {
    const cfg = await getConfig();
    if (!cfg.apiUrl || !cfg.apiToken) { switchView('config'); throw new Error('Not configured'); }
    const url = cfg.apiUrl.replace(/\/+$/, '') + path;
    const resp = await fetch(url, {
      ...opts,
      headers: {
        'Content-Type': 'application/json', 'Accept': 'application/json',
        'Authorization': 'Bearer ' + cfg.apiToken, ...(opts.headers || {})
      }
    });
    if (!resp.ok) {
      const e = await resp.json().catch(() => ({}));
      throw new Error(e.error || e.message || 'HTTP ' + resp.status);
    }
    return resp.json();
  }

  // --- Save ---
  async function saveItem(type, extraTags = []) {
    const note = $('sp-note').value;
    const tags = extraTags.concat(
      $('sp-tags').value.split(',').map(t => t.trim()).filter(Boolean),
      ['chrome-extension']
    );
    await apiRequest('/items', {
      method: 'POST',
      body: JSON.stringify({ type, title: currentTabTitle, url: currentTabUrl, content: note || undefined, tags })
    });
    showStatus('Saved as ' + type + '!', 'success');
    $('sp-note').value = '';
    $('sp-tags').value = '';
  }

  $('sp-save-bm').addEventListener('click', () => saveItem('bookmark'));
  $('sp-save-note').addEventListener('click', () => saveItem('note'));
  $('sp-save-later').addEventListener('click', () => saveItem('bookmark', ['read-later']));

  // --- Config ---
  $('sp-cfg-save').addEventListener('click', async () => {
    const url = $('sp-cfg-url').value.trim();
    const token = $('sp-cfg-token').value.trim();
    if (!url || !token) { showStatus('Fill in both fields', 'error'); return; }
    await chrome.storage.sync.set({ apiUrl: url, apiToken: token });
    switchView('save');
    showStatus('Connected!', 'success');
  });

  // --- Search ---
  let searchTimeout;
  $('sp-search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
      const q = e.target.value;
      const container = $('sp-search-results');
      if (q.length < 2) {
        container.innerHTML = '<div class="kh-sp-empty"><div class="kh-sp-empty-icon">&#128269;</div><div class="kh-sp-empty-text">Type at least 2 characters</div></div>';
        return;
      }
      try {
        const resp = await apiRequest('/search?q=' + encodeURIComponent(q));
        const items = resp?.data || [];
        if (!items.length) {
          container.innerHTML = '<div class="kh-sp-empty"><div class="kh-sp-empty-icon">&#128528;</div><div class="kh-sp-empty-text">No results</div></div>';
          return;
        }
        container.innerHTML = items.map(i =>
          `<div class="kh-sp-result-item" data-url="${esc(i.url || '')}">
            <div class="kh-sp-result-type">${esc(i.type)}</div>
            <div class="kh-sp-result-title">${esc(i.title || 'Untitled')}</div>
            <div class="kh-sp-result-url">${esc(i.url || '')}</div>
          </div>`
        ).join('');
        container.querySelectorAll('.kh-sp-result-item').forEach(el =>
          el.addEventListener('click', () => { if (el.dataset.url) chrome.tabs.create({ url: el.dataset.url }); })
        );
      } catch (err) {
        container.innerHTML = `<div class="kh-sp-empty"><div class="kh-sp-empty-icon">&#9888;&#65039;</div><div class="kh-sp-empty-text">${esc(err.message)}</div></div>`;
      }
    }, 300);
  });

  // --- AI Render ---
  $('sp-do-render').addEventListener('click', async () => {
    const btn = $('sp-do-render');
    const result = $('sp-render-result');
    btn.disabled = true;
    btn.innerHTML = '<div class="kh-sp-render-loading"><div class="kh-sp-spinner"></div> Rendering...</div>';
    result.classList.add('kh-sp-hidden');
    try {
      const extra = $('sp-render-extra').value;
      const resp = await apiRequest('/items/render', {
        method: 'POST',
        body: JSON.stringify({ title: currentTabTitle, url: currentTabUrl, content: extra })
      });
      currentRenderSummary = resp.summary;
      $('sp-render-content').textContent = resp.summary;
      result.classList.remove('kh-sp-hidden');
      showStatus('AI notes generated!', 'success');
    } catch (err) {
      showStatus(err.message, 'error');
    }
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Render with AI';
  });

  $('sp-copy-render').addEventListener('click', () => {
    navigator.clipboard.writeText(currentRenderSummary).then(() => {
      $('sp-copy-render').textContent = 'Copied!';
      setTimeout(() => $('sp-copy-render').textContent = 'Copy', 1500);
    });
  });

  $('sp-render-save-note').addEventListener('click', async () => {
    if (!currentRenderSummary) return;
    await apiRequest('/items', {
      method: 'POST',
      body: JSON.stringify({ type: 'note', title: currentTabTitle, url: currentTabUrl, content: currentRenderSummary, tags: ['ai-rendered', 'chrome-extension'] })
    });
    showStatus('Saved as note!', 'success');
  });

  $('sp-render-save-bm').addEventListener('click', async () => {
    if (!currentRenderSummary) return;
    await apiRequest('/items', {
      method: 'POST',
      body: JSON.stringify({ type: 'bookmark', title: currentTabTitle, url: currentTabUrl, content: currentRenderSummary, tags: ['ai-rendered', 'chrome-extension'] })
    });
    showStatus('Saved as bookmark!', 'success');
  });

  // --- Helpers ---
  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  // --- Init ---
  (async () => {
    const cfg = await getConfig();
    if (!cfg.apiUrl || !cfg.apiToken) {
      switchView('config');
    }
    await loadTabInfo();
  })();

  // Reload tab info when user switches tabs
  chrome.tabs.onActivated.addListener(async () => {
    await loadTabInfo();
  });

  // Reload tab info when tab URL changes
  chrome.tabs.onUpdated.addListener(async (tabId, changeInfo) => {
    if (tabId === currentTabId && changeInfo.status === 'complete') {
      await loadTabInfo();
    }
  });
})();
