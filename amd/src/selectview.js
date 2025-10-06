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

/* eslint-disable */
import $ from 'jquery';

/**
 * Inicializa el selector de vista sin recargar la página.
 */
export const init = () => {
    $('#viewmode-selector').on('change', function() {
        const mode = $(this).val();
        const block = $(this).closest('[data-region="recs-block"]');

        // Ocultar todas las listas
        block.find('.recs-list').addClass('d-none');

        // Mostrar según modo
        if (mode === 'cards') {
            block.find('.recs-cards').removeClass('d-none');
        } else {
            block.find('.recs-list-view').removeClass('d-none');
        }

        // Opcional: recordar preferencia en localStorage
        try {
            localStorage.setItem('datacurso_recs_viewmode', mode);
        } catch (e) {
            console.log(e)
        }
    });

  
    try {
        const savedMode = localStorage.getItem('datacurso_recs_viewmode');
        if (savedMode) {
            $('#viewmode-selector').val(savedMode).trigger('change');
        }
    } catch (e) {
        console.log(e)
    }
};
