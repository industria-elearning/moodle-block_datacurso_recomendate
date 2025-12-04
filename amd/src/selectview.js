// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Controlador de la vista (cards/list) para el block_datacurso_recomendate.
 *
 * @module block_datacurso_recomendate/selectview
 */

import $ from 'jquery';
import Notification from 'core/notification';

/**
 * Init the view mode selector.
 */
export const init = () => {
    $('#viewmode-selector').on('change', function() {
        const mode = $(this).val();
        const block = $(this).closest('[data-region="recs-block"]');

        // Hide both views
        block.find('.recs-list').addClass('d-none');

        // Show the selected view
        if (mode === 'cards') {
            block.find('.recs-cards').removeClass('d-none');
        } else {
            block.find('.recs-list-view').removeClass('d-none');
        }

        // Optional: Save preference to localStorage
        try {
            localStorage.setItem('datacurso_recs_viewmode', mode);
        } catch (e) {
            Notification.error(e.message);
        }
    });

    try {
        const savedMode = localStorage.getItem('datacurso_recs_viewmode');
        if (savedMode) {
            $('#viewmode-selector').val(savedMode).trigger('change');
        }
    } catch (e) {
        Notification.error(e.message);
    }
};
