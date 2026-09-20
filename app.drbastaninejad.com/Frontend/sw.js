const CACHE='drb-smartteb-shell-v15';
const OFFLINE='/Frontend/offline.html';
const STATIC_SHELL=[
  OFFLINE,
  '/Frontend/manifest.webmanifest',
  '/Frontend/assets/css/tokens.css','/Frontend/assets/css/tokens-extended.css','/Frontend/assets/css/base.css','/Frontend/assets/css/components.css','/Frontend/assets/css/states.css','/Frontend/assets/css/ui-polish.css',
  '/Frontend/assets/js/app.js','/Frontend/assets/js/chrome.js','/Frontend/assets/js/dialog.js','/Frontend/assets/js/jalali.js','/Frontend/assets/js/calendar-day-appointments.js',
  '/Frontend/assets/img/smarteb-mark.svg','/Frontend/assets/img/favicon.svg','/Frontend/assets/img/site-icon.png',
  '/Frontend/assets/img/pwa/apple-touch-icon.png','/Frontend/assets/img/pwa/icon-192.png','/Frontend/assets/img/pwa/icon-512.png'
];
const STATIC_SET=new Set(STATIC_SHELL);
self.addEventListener('install',e=>e.waitUntil(
  caches.open(CACHE).then(c=>c.addAll(STATIC_SHELL).catch(()=>{})).then(()=>self.skipWaiting())
));
self.addEventListener('activate',e=>e.waitUntil(
  caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim())
));
self.addEventListener('fetch',e=>{
  if(e.request.method!=='GET') return;
  const u=new URL(e.request.url);
  if(u.origin!==self.location.origin) return;

  // Every HTML navigation, including the PWA start_url with ?source=pwa,
  // is network-only. Offline fallback contains no patient/clinical content.
  if(e.request.mode==='navigate'){
    e.respondWith(fetch(e.request,{cache:'no-store'}).catch(()=>caches.match(OFFLINE)));
    return;
  }

  // API, REST, signed/query URLs and any non-whitelisted app resource stay
  // network-only and are never written to Cache Storage.
  if(u.search||u.pathname.includes('/wp-json/')||u.pathname.includes('/api/')) return;
  if(!STATIC_SET.has(u.pathname)) return;

  // Mutable JS/CSS is network-first so an installed PWA cannot run a new HTML
  // shell against an old script. Cached copy is only an offline fallback.
  if (/\.(?:js|css)$/.test(u.pathname)) {
    e.respondWith(
      fetch(e.request,{cache:'no-store'}).then(r=>{
        if(r&&r.ok){const copy=r.clone();caches.open(CACHE).then(c=>c.put(u.pathname,copy));}
        return r;
      }).catch(()=>caches.match(u.pathname))
    );
    return;
  }

  e.respondWith(
    caches.match(u.pathname).then(cached=>cached||fetch(e.request).then(r=>{
      if(r&&r.ok){const copy=r.clone();caches.open(CACHE).then(c=>c.put(u.pathname,copy));}
      return r;
    }))
  );
});
self.addEventListener('push',e=>{
  let d={}; try{d=e.data?e.data.json():{}}catch(_){d={body:e.data?e.data.text():''}}
  const title=d.title||'CRM پزشکی دکتر باستانی‌نژاد';
  e.waitUntil(self.registration.showNotification(title,{body:d.body||'',icon:'/Frontend/assets/img/pwa/icon-192.png',badge:'/Frontend/assets/img/pwa/icon-192.png',tag:d.tag||'drb',data:{url:d.url||'/Frontend/pages/staff/dashboard.html'},dir:'rtl',lang:'fa-IR'}));
});
self.addEventListener('notificationclick',e=>{
  e.notification.close(); const url=e.notification.data?.url||'/Frontend/pages/staff/dashboard.html';
  e.waitUntil(clients.matchAll({type:'window',includeUncontrolled:true}).then(list=>{for(const c of list){if('focus' in c){c.navigate(url);return c.focus();}}return clients.openWindow(url);}));
});