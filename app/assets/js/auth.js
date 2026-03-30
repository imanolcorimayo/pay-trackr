// Firebase compat SDK loaded via CDN in header.php
const app = firebase.initializeApp(window.MANGOS_CONFIG);
const auth = firebase.auth();
const provider = new firebase.auth.GoogleAuthProvider();

function setCookie(name, value, days) {
  const d = new Date();
  d.setTime(d.getTime() + days * 86400000);
  document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/;SameSite=Lax`;
}

function deleteCookie(name) {
  document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
}

// Promise that resolves when auth state is known
let _authResolve;
const _authReady = new Promise(function(r) { _authResolve = r; });

window.mangosAuth = {
  user: null,
  ready: _authReady,

  signIn: async function() {
    var btn = document.getElementById('google-signin-btn');
    var error = document.getElementById('login-error');
    if (btn) btn.disabled = true;
    if (error) error.classList.add('hidden');

    try {
      await auth.signInWithPopup(provider);
    } catch (e) {
      if (e.code === 'auth/popup-blocked' || e.code === 'auth/popup-closed-by-user') {
        try {
          await auth.signInWithRedirect(provider);
          return;
        } catch (redirectErr) {
          e = redirectErr;
        }
      }
      console.error('Sign-in error:', e);
      if (error) {
        error.textContent = 'No se pudo iniciar sesion. Intenta de nuevo.';
        error.classList.remove('hidden');
      }
      if (btn) btn.disabled = false;
    }
  },

  signOut: async function() {
    await auth.signOut();
    deleteCookie('mangos_token');
    window.location.href = '/login';
  },

  getToken: async function() {
    var user = auth.currentUser;
    if (!user) return null;
    return user.getIdToken();
  },

  _updateUI: function(user) {
    var sidebarUser = document.getElementById('sidebar-user');
    var avatar = document.getElementById('sidebar-avatar');
    var name = document.getElementById('sidebar-name');

    if (sidebarUser && user) {
      sidebarUser.classList.remove('hidden');
      if (avatar) avatar.src = user.photoURL || '';
      if (name) name.textContent = user.displayName || user.email || '';
    }
  },
};

// Auto-init: listen for auth state
auth.onAuthStateChanged(async function(user) {
  mangosAuth.user = user;
  if (user) {
    var token = await user.getIdToken();
    setCookie('mangos_token', token, 7);
    mangosAuth._updateUI(user);
  }

  _authResolve(user);

  var isLoginPage = window.location.pathname === '/login';
  if (user && isLoginPage) {
    window.location.href = '/dashboard';
  } else if (!user && !isLoginPage) {
    window.location.href = '/login';
  }
});
