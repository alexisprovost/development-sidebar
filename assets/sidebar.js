var STORAGE_KEY = 'devsidebar.taskversion';
var icon = document.querySelectorAll('.devsidebar-info-icon');
icon = icon[icon.length - 1];
if (icon) {
    icon.addEventListener('click', function (e) { e.preventDefault(); }, false);

    var stored = '';
    try { stored = window.localStorage.getItem(STORAGE_KEY) || ''; } catch (_) {}

    if (taskVersion && stored !== taskVersion) {
        icon.classList.add('devsidebar-pulse');
    }

    var acknowledge = function (e) {
        if (e) { e.preventDefault(); }
        try { window.localStorage.setItem(STORAGE_KEY, taskVersion); } catch (_) {}
        icon.classList.remove('devsidebar-pulse');
    };

    icon.addEventListener('contextmenu', acknowledge, false);
    icon.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { acknowledge(e); }
    }, false);
}
