window.api = {
  async request(endpoint, options = {}) {
    const token = await window.mangosAuth.getToken();
    if (!token) {
      window.location.href = '/login';
      return null;
    }

    const fetchOpts = {
      method: options.method || 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...options.headers,
      },
    };

    if (options.body) {
      fetchOpts.body = JSON.stringify(options.body);
    }

    // Build URL with query params
    let url = `${window.MANGOS_API_URL}${endpoint}`;
    if (options.params) {
      const qs = new URLSearchParams(options.params).toString();
      url += `?${qs}`;
    }

    let response;
    try {
      response = await fetch(url, fetchOpts);
    } catch (err) {
      console.error('API network error:', err);
      return { error: 'Sin conexion' };
    }

    if (response.status === 401) {
      window.mangosAuth.signOut();
      return null;
    }

    if (!response.ok) {
      // Try to surface the structured JSON error body if there is one;
      // otherwise synthesize one so callers can detect the failure.
      let body = null;
      try { body = await response.json(); } catch (_) {}
      console.error('API error:', response.status, body);
      if (body && typeof body === 'object' && body.error) return body;
      return { error: `HTTP ${response.status}` };
    }

    return response.json();
  },

  get(endpoint, params) {
    return this.request(endpoint, { params });
  },

  post(endpoint, body) {
    return this.request(endpoint, { method: 'POST', body });
  },

  put(endpoint, body, params) {
    return this.request(endpoint, { method: 'PUT', body, params });
  },

  del(endpoint, params) {
    return this.request(endpoint, { method: 'DELETE', params });
  },

  // Authenticated GET that returns an object URL for the response bytes.
  // Use for <img>/<audio> sources where the underlying endpoint sits behind
  // bearer-token auth and cannot be loaded via a plain `src` attribute.
  // Caller is responsible for URL.revokeObjectURL(...) when the element goes
  // away — for short-lived modals, leaking is fine.
  async getBlobUrl(endpoint, params) {
    const token = await window.mangosAuth.getToken();
    if (!token) {
      window.location.href = '/login';
      return null;
    }
    let url = `${window.MANGOS_API_URL}${endpoint}`;
    if (params) {
      const qs = new URLSearchParams(params).toString();
      url += `?${qs}`;
    }
    const response = await fetch(url, { headers: { Authorization: `Bearer ${token}` } });
    if (!response.ok) return null;
    const blob = await response.blob();
    return URL.createObjectURL(blob);
  },
};
