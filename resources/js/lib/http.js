export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function apiFetch(url, options = {}) {
    const { headers = {}, ...rest } = options;

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...rest,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            ...(rest.body ? { 'Content-Type': 'application/json' } : {}),
            ...headers,
        },
    });

    if (response.status === 401 || response.status === 419) {
        window.location.assign('/login');
        throw new Error('Sesi berakhir.');
    }

    return response;
}
