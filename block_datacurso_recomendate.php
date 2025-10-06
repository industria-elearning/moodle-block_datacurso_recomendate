<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block datacurso_recomendate is defined here.
 *
 * @package     block_datacurso_recomendate
 * @copyright   Josue <josue@datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_datacurso_recomendate extends block_base {

    public function init() {
        $this->title = get_string('blocktitle', 'block_datacurso_recomendate');
    }

    public function applicable_formats() {
        return ['my' => true, 'site' => true];
    }

    /**
     * Obtiene la URL de la imagen del curso.
     * Intenta obtener la imagen personalizada o genera una por defecto.
     *
     * @param int $courseid ID del curso
     * @return string|null URL de la imagen o null
     */
    protected function get_course_image($courseid) {
        global $CFG;

        try {
            // Método 1: Usar la clase core_course_list_element (Moodle 3.0+)
            if (class_exists('core_course_list_element')) {
                require_once($CFG->dirroot . '/course/lib.php');
                $course = get_course($courseid);
                $courseobj = new core_course_list_element($course);
                
                // Obtener la primera imagen disponible
                foreach ($courseobj->get_course_overviewfiles() as $file) {
                    if ($file->is_valid_image()) {
                        return moodle_url::make_pluginfile_url(
                            $file->get_contextid(),
                            $file->get_component(),
                            $file->get_filearea(),
                            null,
                            $file->get_filepath(),
                            $file->get_filename()
                        )->out(false);
                    }
                }
            } else {
                // Método 2: Buscar directamente en el área de archivos (fallback para versiones antiguas)
                $context = context_course::instance($courseid);
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                    $context->id,
                    'course',
                    'overviewfiles',
                    false,
                    'filename',
                    false
                );

                if (!empty($files)) {
                    foreach ($files as $file) {
                        // Verificar que sea una imagen válida
                        $mimetype = $file->get_mimetype();
                        if (strpos($mimetype, 'image/') === 0) {
                            return moodle_url::make_pluginfile_url(
                                $file->get_contextid(),
                                $file->get_component(),
                                $file->get_filearea(),
                                $file->get_itemid(),
                                $file->get_filepath(),
                                $file->get_filename()
                            )->out(false);
                        }
                    }
                }
            }

            // Método 3: Imagen por defecto generada por Moodle
            return $this->get_default_course_image($courseid);

        } catch (Exception $e) {
            // En caso de error, devolver imagen por defecto
            debugging('Error obteniendo imagen del curso ' . $courseid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $this->get_default_course_image($courseid);
        }
    }

    /**
     * Genera una URL de imagen por defecto para el curso.
     * Usa el sistema de patrones de Moodle o un placeholder.
     *
     * @param int $courseid ID del curso
     * @return string URL de la imagen por defecto
     */
    protected function get_default_course_image($courseid) {
        global $OUTPUT;

        // Opción 1: Usar el sistema de imágenes por defecto de Moodle (Moodle 3.6+)
        if (method_exists($OUTPUT, 'get_generated_image_for_id')) {
            return $OUTPUT->get_generated_image_for_id($courseid);
        }

        // Opción 2: Usar imagen genérica de curso
        if (method_exists($OUTPUT, 'image_url')) {
            return $OUTPUT->image_url('course_defaultimage', 'moodle')->out(false);
        }

        // Opción 3: Generar patrón de color basado en el ID del curso
        return $this->generate_pattern_image_url($courseid);
    }

    /**
     * Genera una URL de imagen de patrón basada en el ID del curso.
     * Usa un servicio externo o crea un placeholder con color.
     *
     * @param int $courseid ID del curso
     * @return string URL de la imagen generada
     */
    protected function generate_pattern_image_url($courseid) {
        // Generar un color basado en el ID del curso
        $colors = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'
        ];
        $color = $colors[$courseid % count($colors)];
        $color = str_replace('#', '', $color);

        // Usar placeholder.com o similar para generar una imagen
        // Puedes cambiar esto por tu propio generador de imágenes
        return "https://via.placeholder.com/400x200/{$color}/ffffff?text=Curso+{$courseid}";
    }

    public function get_content() {
        global $USER, $CFG, $PAGE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        require_once($CFG->dirroot . '/local/datacurso_ratings/classes/recommendations/service.php');

        $viewmode = optional_param('viewmode', 'cards', PARAM_ALPHA);
        $page = optional_param('page', 0, PARAM_INT);
        $perpage = 6;

        // Obtener recomendaciones
        $recs = \local_datacurso_ratings\recommendations\service::get_recommendations_for_user($USER->id, 50);
        $total = count($recs);

        if (empty($recs)) {
            $this->content->text = $OUTPUT->notification(
                get_string('norecs', 'block_datacurso_recomendate'),
                'info'
            );
            return $this->content;
        }

        // Paginación manual
        $pagedrecs = array_slice($recs, $page * $perpage, $perpage);

        // Construir array de cursos con imagen mejorada
        $courses = [];
        foreach ($pagedrecs as $rec) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $rec['courseid']]);

            // Obtener imagen del curso usando el método mejorado
            $imgurl = $this->get_course_image($rec['courseid']);

            $courses[] = [
                'fullname' => format_string($rec['fullname']),
                'courseurl' => $courseurl->out(false),
                'imageurl' => $imgurl,
                'hasimage' => !empty($imgurl),
                'course_satisfaction' => $rec['course_satisfaction'],
                'category_preference_pct' => $rec['category_preference_pct'],
                'score' => round($rec['score'], 2)
            ];
        }

        // Selector de vista
        $baseurl = new moodle_url('/my/index.php');
        $selector = html_writer::select(
            [
                'cards' => get_string('view_cards', 'block_datacurso_recomendate'),
                'list' => get_string('view_list', 'block_datacurso_recomendate')
            ],
            'viewmode',
            $viewmode,
            false,
            [
                'id' => 'viewmode-selector',
                'class' => 'custom-select'
            ]
        );

        // Paginación
        $pagination = $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);

        $PAGE->requires->js_call_amd('block_datacurso_recomendate/selectview', 'init');
        
        $data = [
            'viewmode' => $viewmode,
            'viewmode_is_cards' => ($viewmode === 'cards'),
            'viewmode_is_list' => ($viewmode === 'list'),
            'selector' => $selector,
            'courses' => $courses,
            'pagination' => $pagination
        ];

        $this->content->text = $OUTPUT->render_from_template(
            'block_datacurso_recomendate/recommendations',
            $data
        );

        return $this->content;
    }
}
