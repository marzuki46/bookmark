(function() {
  if (document.getElementById('kh-overlay')) {
    document.getElementById('kh-overlay').remove();
    return;
  }

  let currentTabUrl = location.href;
  let currentTabTitle = document.title || location.hostname;
  let currentRenderSummary = '';

  const faviconUrl = (() => { try { return 'https://www.google.com/s2/favicons?domain=' + location.hostname + '&sz=32'; } catch { return ''; } })();

  const overlay = document.createElement('div');
  overlay.id = 'kh-overlay';
  overlay.innerHTML = `
    <div class="kh-layout">
      <div class="kh-sidebar">
        <button class="kh-close" id="kh-close">&times;</button>
        <div class="kh-brand">
          <div class="kh-logo">K</div>
          <div><div class="kh-brand-text">Knowledge Hub</div><div class="kh-brand-sub">${location.hostname}</div></div>
        </div>
        <div class="kh-nav">
          <button class="kh-nav-btn active" data-view="save"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>Save</button>
          <button class="kh-nav-btn" data-view="render"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>AI Render</button>
          <button class="kh-nav-btn" data-view="search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Search</button>
          <button class="kh-nav-btn" data-view="info"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>Info</button>
        </div>
      </div>
      <div class="kh-main">
        <div class="kh-status" id="kh-status"></div>
        <div id="kh-view-save">
          <div class="kh-header"><h1>Save Page</h1><p>Save the current page as bookmark or note</p></div>
          <div class="kh-card">
            <div class="kh-card-header">
              <div class="kh-favicon"><img src="${faviconUrl}" alt=""></div>
              <div class="kh-info"><div class="kh-title">${esc(currentTabTitle)}</div><div class="kh-url">${esc(currentTabUrl)}</div></div>
            </div>
            <textarea id="kh-note" rows="2" placeholder="Add a note... (optional)"></textarea>
            <div class="kh-form-row"><label class="kh-form-label">Tags</label><input type="text" class="kh-input" id="kh-tags" placeholder="comma, separated"></div>
            <div class="kh-actions">
              <button class="kh-btn kh-btn-ghost" id="kh-save-note"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Note</button>
              <button class="kh-btn kh-btn-ghost" id="kh-save-later"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Later</button>
              <button class="kh-btn kh-btn-primary" id="kh-save-bm"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>Bookmark</button>
            </div>
          </div>
        </div>
        <div id="kh-view-render" class="kh-hidden">
          <div class="kh-header"><h1>AI Render</h1><p>Generate AI notes from this page</p></div>
          <div class="kh-card">
            <div class="kh-card-header">
              <div class="kh-favicon"><img src="${faviconUrl}" alt=""></div>
              <div class="kh-info"><div class="kh-title">${esc(currentTabTitle)}</div><div class="kh-url">${esc(currentTabUrl)}</div></div>
            </div>
            <textarea id="kh-render-extra" rows="2" placeholder="Extra context for AI (optional)..."></textarea>
            <div class="kh-actions"><button class="kh-btn kh-btn-primary" id="kh-do-render" style="width:100%"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Render with AI</button></div>
          </div>
          <div id="kh-render-result" class="kh-hidden">
            <div class="kh-card">
              <div class="kh-card-header" style="border-bottom:none;padding-bottom:0"><div class="kh-info"><div class="kh-title" style="color:#6366f1">AI Notes</div></div><button class="kh-copy-btn" id="kh-copy-render">Copy</button></div>
              <div style="padding:0 12px 12px"><div id="kh-render-content" style="font-size:12px;color:#334155;line-height:1.6;white-space:pre-wrap"></div></div>
              <div class="kh-actions">
                <button class="kh-btn kh-btn-ghost" id="kh-render-save-note">Save as Note</button>
                <button class="kh-btn kh-btn-primary" id="kh-render-save-bm">Bookmark + AI</button>
              </div>
            </div>
          </div>
        </div>
        <div id="kh-view-search" class="kh-hidden">
          <div class="kh-header"><h1>Search</h1><p>Search your knowledge base</p></div>
          <div class="kh-search-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" id="kh-search-input" placeholder="Search everything..."></div>
          <div class="kh-results" id="kh-search-results"><div class="kh-empty"><div class="kh-empty-icon">&#128269;</div><div class="kh-empty-text">Type to search</div></div></div>
        </div>
        <div id="kh-view-info" class="kh-hidden">
          <div class="kh-header"><h1>Page Info</h1><p>Metadata from this page</p></div>
          <div class="kh-card">
            <div class="kh-card-header"><div class="kh-favicon"><img src="${faviconUrl}" alt=""></div><div class="kh-info"><div class="kh-title">${esc(currentTabTitle)}</div><div class="kh-url">${esc(currentTabUrl)}</div></div></div>
          </div>
          <div class="kh-meta-grid" id="kh-meta-grid"></div>
        </div>
        <div id="kh-view-config" class="kh-hidden">
          <div class="kh-config"><div class="kh-config-card">
            <div class="kh-config-logo">K</div><h2>Knowledge Hub</h2><p class="kh-sub">Enter API credentials</p>
            <div class="kh-form-group"><label class="kh-form-label">API URL</label><input type="text" class="kh-input" id="kh-cfg-url" placeholder="http://bookmark.test/api"></div>
            <div class="kh-form-group"><label class="kh-form-label">API Token</label><input type="password" class="kh-input" id="kh-cfg-token" placeholder="Paste token"></div>
            <button class="kh-btn kh-btn-primary" id="kh-cfg-save" style="width:100%">Connect</button>
          </div></div>
        </div>
      </div>
    </div>`;
  document.body.appendChild(overlay);

  const css = document.createElement('link');
  css.rel = 'stylesheet';
  css.href = chrome.runtime.getURL('overlay.css');
  document.head.appendChild(css);

  function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  document.getElementById('kh-close').addEventListener('click', () => overlay.remove());

  document.querySelectorAll('.kh-nav-btn[data-view]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.kh-nav-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      ['save','render','search','info','config'].forEach(v => {
        const el = document.getElementById('kh-view-' + v);
        if (el) el.classList.toggle('kh-hidden', v !== btn.dataset.view);
      });
    });
  });

  function showStatus(msg, type) {
    const s = document.getElementById('kh-status');
    s.className = 'kh-status ' + type;
    s.textContent = msg;
    setTimeout(() => { s.className = 'kh-status'; s.textContent = ''; }, 3000);
  }

  function switchView(name) {
    document.querySelectorAll('.kh-nav-btn').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector(`.kh-nav-btn[data-view="${name}"]`);
    if (btn) btn.classList.add('active');
    ['save','render','search','info','config'].forEach(v => {
      const el = document.getElementById('kh-view-' + v);
      if (el) el.classList.toggle('kh-hidden', v !== name);
    });
  }

  async function getConfig() {
    return new Promise(resolve => chrome.storage.sync.get(['apiUrl','apiToken'], resolve));
  }

  async function apiRequest(path, opts = {}) {
    const cfg = await getConfig();
    if (!cfg.apiUrl || !cfg.apiToken) { switchView('config'); throw new Error('Not configured'); }
    const url = cfg.apiUrl.replace(/\/+$/, '') + path;
    const resp = await fetch(url, {
      ...opts,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + cfg.apiToken, ...(opts.headers || {}) }
    });
    if (!resp.ok) { const e = await resp.json().catch(() => ({})); throw new Error(e.error || e.message || 'HTTP ' + resp.status); }
    return resp.json();
  }

  async function saveItem(type, extraTags = []) {
    const note = document.getElementById('kh-note').value;
    const tags = extraTags.concat(document.getElementById('kh-tags').value.split(',').map(t=>t.trim()).filter(Boolean), ['chrome-extension']);
    await apiRequest('/items', { method: 'POST', body: JSON.stringify({ type, title: currentTabTitle, url: currentTabUrl, content: note || undefined, tags }) });
    showStatus('Saved as ' + type + '!', 'success');
    document.getElementById('kh-note').value = '';
    document.getElementById('kh-tags').value = '';
  }

  document.getElementById('kh-save-bm').addEventListener('click', () => saveItem('bookmark'));
  document.getElementById('kh-save-note').addEventListener('click', () => saveItem('note'));
  document.getElementById('kh-save-later').addEventListener('click', () => saveItem('bookmark', ['read-later']));

  document.getElementById('kh-cfg-save').addEventListener('click', async () => {
    const url = document.getElementById('kh-cfg-url').value.trim();
    const token = document.getElementById('kh-cfg-token').value.trim();
    if (!url || !token) { showStatus('Fill in both fields', 'error'); return; }
    await chrome.storage.sync.set({ apiUrl: url, apiToken: token });
    switchView('save');
    showStatus('Connected!', 'success');
  });

  let searchTimeout;
  document.getElementById('kh-search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
      const q = e.target.value;
      const container = document.getElementById('kh-search-results');
      if (q.length < 2) { container.innerHTML = '<div class="kh-empty"><div class="kh-empty-icon">&#128269;</div><div class="kh-empty-text">Type at least 2 characters</div></div>'; return; }
      try {
        const resp = await apiRequest('/search?q=' + encodeURIComponent(q));
        const items = resp?.data || [];
        if (!items.length) { container.innerHTML = '<div class="kh-empty"><div class="kh-empty-icon">&#128528;</div><div class="kh-empty-text">No results</div></div>'; return; }
        container.innerHTML = items.map(i => `<div class="kh-result-item" data-url="${esc(i.url||'')}"><div class="kh-result-type">${esc(i.type)}</div><div class="kh-result-title">${esc(i.title||'Untitled')}</div><div class="kh-result-url">${esc(i.url||'')}</div></div>`).join('');
        container.querySelectorAll('.kh-result-item').forEach(el => el.addEventListener('click', () => { if (el.dataset.url) window.open(el.dataset.url, '_blank'); }));
      } catch(err) { container.innerHTML = '<div class="kh-empty"><div class="kh-empty-icon">&#9888;&#65039;</div><div class="kh-empty-text">' + esc(err.message) + '</div></div>'; }
    }, 300);
  });

  document.getElementById('kh-do-render').addEventListener('click', async () => {
    const btn = document.getElementById('kh-do-render');
    const result = document.getElementById('kh-render-result');
    btn.disabled = true;
    btn.innerHTML = '<div class="kh-render-loading"><div class="kh-spinner"></div> Rendering...</div>';
    result.classList.add('kh-hidden');
    try {
      const extra = document.getElementById('kh-render-extra').value;
      const resp = await apiRequest('/items/render', { method: 'POST', body: JSON.stringify({ title: currentTabTitle, url: currentTabUrl, content: extra }) });
      currentRenderSummary = resp.summary;
      document.getElementById('kh-render-content').textContent = resp.summary;
      result.classList.remove('kh-hidden');
      showStatus('AI notes generated!', 'success');
    } catch(err) { showStatus(err.message, 'error'); }
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Render with AI';
  });

  document.getElementById('kh-copy-render').addEventListener('click', () => {
    navigator.clipboard.writeText(currentRenderSummary).then(() => {
      document.getElementById('kh-copy-render').textContent = 'Copied!';
      setTimeout(() => document.getElementById('kh-copy-render').textContent = 'Copy', 1500);
    });
  });

  document.getElementById('kh-render-save-note').addEventListener('click', async () => {
    if (!currentRenderSummary) return;
    await apiRequest('/items', { method: 'POST', body: JSON.stringify({ type: 'note', title: currentTabTitle, url: currentTabUrl, content: currentRenderSummary, tags: ['ai-rendered','chrome-extension'] }) });
    showStatus('Saved as note!', 'success');
  });

  document.getElementById('kh-render-save-bm').addEventListener('click', async () => {
    if (!currentRenderSummary) return;
    await apiRequest('/items', { method: 'POST', body: JSON.stringify({ type: 'bookmark', title: currentTabTitle, url: currentTabUrl, content: currentRenderSummary, tags: ['ai-rendered','chrome-extension'] }) });
    showStatus('Saved as bookmark!', 'success');
  });

  const metaGrid = document.getElementById('kh-meta-grid');
  const metaLabels = { title:'Title', description:'Description', keywords:'Keywords', author:'Author', og_title:'OG Title', og_description:'OG Description', og_image:'OG Image', site_name:'Site Name' };
  document.querySelectorAll('meta').forEach(m => {
    const prop = m.getAttribute('property') || m.getAttribute('name');
    if (!prop) return;
    const key = prop.replace('og:', '').replace('twitter:', '');
    if (metaLabels[key]) {
      metaGrid.innerHTML += `<div class="kh-meta-card"><div class="kh-meta-label">${metaLabels[key]}</div><div class="kh-meta-value">${esc(m.content||'')}</div></div>`;
    }
  });

  (async () => {
    const cfg = await getConfig();
    if (!cfg.apiUrl || !cfg.apiToken) switchView('config');
  })();
})();
