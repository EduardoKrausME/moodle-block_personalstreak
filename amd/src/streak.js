// This file is part of Moodle - http://moodle.org/

/**
 * Small interaction helper for the personal activity calendar.
 *
 * @module block_personalstreak/streak
 */
define([], function() {
    return {
        init: function(selector) {
            document.querySelectorAll(selector).forEach((root) => {
                if (root.dataset.initialized === '1') {
                    return;
                }

                root.dataset.initialized = '1';
                const detail = root.querySelector('[data-region="day-detail"]');

                root.querySelectorAll('[data-detail]').forEach((cell) => {
                    cell.addEventListener('click', () => {
                        if (detail) {
                            detail.textContent = cell.dataset.detail || '';
                        }
                    });
                });
            });
        },
    };
});
