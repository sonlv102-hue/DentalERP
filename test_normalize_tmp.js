/** Lowercases and strips Vietnamese diacritics, so "thieu" matches "Thiều". */
function normalizeSearch(str) {
    return (str ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase();
}

module.exports = { normalizeSearch };