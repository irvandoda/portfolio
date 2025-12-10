export async function fetchAPI(endpoint, options = {}) {
  const apiUrl = window.sasecData?.apiUrl || '/wp-json/sasec/v1/';
  const nonce = window.sasecData?.nonce || '';

  const defaultOptions = {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
    },
  };

  const response = await fetch(`${apiUrl}${endpoint}`, {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...(options.headers || {}),
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Unknown error' }));
    throw new Error(error.message || `HTTP error! status: ${response.status}`);
  }

  return response.json();
}

