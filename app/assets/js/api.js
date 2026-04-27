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
      return [];
    }

    if (response.status === 401) {
      window.mangosAuth.signOut();
      return null;
    }

    if (!response.ok) {
      console.error('API error:', response.status, await response.text());
      return [];
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
};
