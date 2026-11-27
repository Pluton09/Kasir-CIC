export function rupiah(value) {
    const amount = Number(value) || 0;

    return 'Rp ' + amount.toLocaleString('id-ID');
}

export function initials(name) {
    const words = String(name).trim().split(/\s+/).filter(Boolean);

    if (words.length === 0) {
        return '?';
    }

    return words
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
}

export function colorFromName(name) {
    let hue = 0;

    for (const character of String(name)) {
        hue = (hue * 31 + character.charCodeAt(0)) % 360;
    }

    return `hsl(${hue} 45% 40%)`;
}
