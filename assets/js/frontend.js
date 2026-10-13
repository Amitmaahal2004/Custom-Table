/**
 * DEFE Custom Tables - Frontend JavaScript
 * Ensures smooth responsive horizontal touch scrolling and mobile optimization.
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var scrollWrappers = document.querySelectorAll('.defe-custom-table-scroll');

        scrollWrappers.forEach(function(wrapper) {
            checkScrollOverflow(wrapper);

            wrapper.addEventListener('scroll', function() {
                checkScrollOverflow(wrapper);
            }, { passive: true });
        });

        window.addEventListener('resize', function() {
            scrollWrappers.forEach(function(wrapper) {
                checkScrollOverflow(wrapper);
            });
        });

        function checkScrollOverflow(el) {
            if (el.scrollWidth > el.clientWidth) {
                el.classList.add('has-horizontal-overflow');
                if (el.scrollLeft > 10) {
                    el.classList.add('is-scrolled-left');
                } else {
                    el.classList.remove('is-scrolled-left');
                }
                if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 10) {
                    el.classList.add('is-scrolled-right');
                } else {
                    el.classList.remove('is-scrolled-right');
                }
            } else {
                el.classList.remove('has-horizontal-overflow', 'is-scrolled-left', 'is-scrolled-right');
            }
        }
    });
})();


document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.defe-custom-table td, .defe-custom-table th').forEach(function (cell) {

        // Remove extra whitespace and count words
        const text = cell.textContent.trim();
        const words = text.split(/\s+/).filter(Boolean);

        if (words.length > 4) {
            cell.classList.add('defe-more-than-4-words');
        }
    });
});
