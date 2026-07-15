chrome.runtime.onInstalled.addListener(() => {
  chrome.sidePanel.setPanelBehavior({ openPanelOnActionClick: true });

  chrome.contextMenus.create({ id: 'save-bookmark', title: 'Save to Knowledge Hub', contexts: ['page'] });
  chrome.contextMenus.create({ id: 'save-highlight', title: 'Save Highlight to Hub', contexts: ['selection'] });
  chrome.contextMenus.create({ id: 'read-later', title: 'Read Later', contexts: ['page', 'link'] });
  chrome.contextMenus.create({ id: 'save-link', title: 'Save Link to Hub', contexts: ['link'] });
});

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  const config = await chrome.storage.sync.get(['apiUrl', 'apiToken']);
  if (!config.apiUrl || !config.apiToken) return;
  const url = config.apiUrl.replace(/\/+$/, '') + '/items';
  const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + config.apiToken };

  switch (info.menuItemId) {
    case 'save-bookmark':
      await fetch(url, { method: 'POST', headers, body: JSON.stringify({ type: 'bookmark', title: tab.title, url: tab.url, tags: ['chrome-extension'] }) });
      break;
    case 'save-highlight':
      await fetch(url, { method: 'POST', headers, body: JSON.stringify({ type: 'note', title: 'Highlight: ' + tab.title, url: tab.url, content: info.selectionText, tags: ['highlight', 'chrome-extension'] }) });
      break;
    case 'read-later':
      await fetch(url, { method: 'POST', headers, body: JSON.stringify({ type: 'bookmark', title: tab.title, url: info.linkUrl || tab.url, tags: ['read-later'] }) });
      break;
    case 'save-link':
      await fetch(url, { method: 'POST', headers, body: JSON.stringify({ type: 'bookmark', title: info.selectionText || info.linkUrl, url: info.linkUrl, tags: ['chrome-extension'] }) });
      break;
  }
});

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'search') { handleSearch(request.query).then(sendResponse); return true; }
  if (request.action === 'saveItem') { handleSave(request.data).then(sendResponse); return true; }
});

async function handleSearch(query) {
  const config = await chrome.storage.sync.get(['apiUrl', 'apiToken']);
  if (!config.apiUrl || !config.apiToken) return { error: 'Not configured' };
  try {
    const resp = await fetch(config.apiUrl.replace(/\/+$/, '') + '/search?q=' + encodeURIComponent(query), {
      headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + config.apiToken }
    });
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    return await resp.json();
  } catch (err) { return { error: err.message }; }
}

async function handleSave(data) {
  const config = await chrome.storage.sync.get(['apiUrl', 'apiToken']);
  if (!config.apiUrl || !config.apiToken) return { error: 'Not configured' };
  try {
    const resp = await fetch(config.apiUrl.replace(/\/+$/, '') + '/items', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer ' + config.apiToken },
      body: JSON.stringify(data)
    });
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    return await resp.json();
  } catch (err) { return { error: err.message }; }
}
