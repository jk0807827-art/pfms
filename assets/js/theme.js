/*
 * PFMS theme (light / dark) toggle.
 *
 * The actual <html data-theme="..."> attribute is set as early
 * as possible by a tiny inline script in each page's <head> —
 * that's what stops a flash of the wrong theme on load. This
 * file only has to wire up the toggle button(s) and keep the
 * saved preference in sync.
 */

(function(){

  function getStoredTheme(){
    try{
      return localStorage.getItem('pfms-theme');
    }catch(e){
      return null;
    }
  }

  function setStoredTheme(theme){
    try{
      localStorage.setItem('pfms-theme', theme);
    }catch(e){
      /* private browsing / storage disabled — theme just won't persist */
    }
  }

  function applyTheme(theme){
    document.documentElement.setAttribute('data-theme', theme);
    document.querySelectorAll('[data-theme-icon]').forEach(function(el){
      el.textContent = theme === 'dark' ? '\u2600' : '\u263D'; // ☀ / ☽
    });
    document.querySelectorAll('[data-theme-label]').forEach(function(el){
      el.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
    });
  }

  function currentTheme(){
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }

  document.addEventListener('DOMContentLoaded', function(){

    // Sync the toggle button's icon/label with whatever the
    // inline head script already applied.
    applyTheme(currentTheme());

    document.querySelectorAll('.theme-toggle').forEach(function(btn){
      btn.addEventListener('click', function(){
        var next = currentTheme() === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        setStoredTheme(next);
      });
    });

  });

})();
