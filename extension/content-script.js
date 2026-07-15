(function () {
  if (window.__khContentScriptLoaded) return;
  window.__khContentScriptLoaded = true;

  chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'getMetadata') {
      const meta = {};
      meta.title = document.title || '';
      meta.url = location.href;

      document.querySelectorAll('meta').forEach(m => {
        const prop = m.getAttribute('property') || m.getAttribute('name');
        if (prop && m.content) {
          meta[prop] = m.content;
        }
      });

      sendResponse({ meta });
      return true;
    }
  });
})();
