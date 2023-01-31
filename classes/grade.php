<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once('base.php');

/**
 * Class local_wsintegracao_v2_grade
 * @copyright 2020 Pedro Fellipe Melo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsintegracao_v2_grade extends wsintegracao_v2_base {

    /**
     * @param $grades
     * @return array
     * @throws Exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_grades_batch($grades) {
        // Validate parameters.
        self::validate_parameters(self::get_grades_batch_parameters(), array('grades' => $grades));

        $retorno = [];

        $pesid = $grades['pes_id'];
        $userid = self::get_user_by_pes_id($pesid);

        if (!$userid) {
            throw new Exception('Não existe um aluno cadastrado com o pes_id: '.$pesid);
        }

        $itens = json_decode($grades['itens'], true);

        if (empty($itens)) {
            throw new Exception('Parâmetro itens está vazio.');
        }

        $itensnotas = [];
        foreach ($itens as $item) {
            $nota = self::get_grade_by_itemid($item['id'], $userid);

            if ($nota) {
                $itensnotas[] = [
                    'id' => $item['id'],
                    'tipo' => $item['tipo'],
                    'nota' => $nota
                ];
            }
        }

        $retorno = [
            'pes_id' => $pesid,
            'grades' => json_encode($itensnotas),
            'status' => 'success',
            'message' => 'Notas mapeadas com sucesso.'
        ];

        return $retorno;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_grades_batch_parameters() {
        return new external_function_parameters(
            array(
                'grades' => new external_single_structure(
                    array(
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no acadêmico'),
                        'itens' => new external_value(PARAM_TEXT, "Array com os id's dos itens de nota")
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function get_grades_batch_returns() {
        return new external_single_structure(
            array(
                'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no acadêmico'),
                'grades' => new external_value(PARAM_TEXT, 'Array com as notas para cada item de nota'),
                'status' => new external_value(PARAM_TEXT, 'Status da operação'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem da operação')
            )
        );
    }

    public static function get_course_grades_batch_parameters() {
        return new external_function_parameters([
            'grades' => new external_single_structure([
                'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no harpia'),
                'pes_ids' => new external_value(PARAM_TEXT, "Array com os pes_id's dos alunos do harpia")
            ])
        ]);
    }

    public static function get_course_grades_batch($grades) {
        global $DB;

        // Validate parameters.
        self::validate_parameters(self::get_course_grades_batch_parameters(), ['grades' => $grades]);

        $discipline = $DB->get_record('int_v2_discipline_course', ['ofd_id' => $grades['ofd_id']], '*', MUST_EXIST);

        $pes_ids = json_decode($grades['pes_ids'], true);
        if (empty($pes_ids)) {
            throw new Exception('Parameter pes_ids can not be null.');
        }

        $studentsgrades = [];
        foreach ($pes_ids as $pes_id) {
            $userid = self::get_user_by_pes_id($pes_id);

            $grade = self::get_student_course_grade($userid, $discipline->course);

            $studentsgrades[] = [
                'pes_id' => $pes_id,
                'grade' => $grade
            ];
        }

        return [
            'grades' => $studentsgrades
        ];
    }

    public static function get_course_grades_batch_returns() {
        return new external_function_parameters([
            'grades' => new external_multiple_structure(
                new external_single_structure([
                    'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no acadêmico'),
                    'grade' => new external_value(PARAM_TEXT, 'Nota final do aluno no curso do Moodle')
                ])
            )
        ]);
    }

    /**
     * @param $itemid
     * @param $userid
     * @return float|int|mixed|string
     * @throws dml_exception
     */
    public static function get_grade_by_itemid($itemid, $userid) {
        global $DB;

        $finalgrade = 0;

        $sql = "SELECT gg.*, gi.scaleid
                FROM {grade_grades} gg
                INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE userid = :userid
                AND itemid = :itemid";

        $grade = $DB->get_record_sql($sql, array('userid' => $userid, 'itemid' => $itemid));

        // Retorna 0 caso não seja encontrados registros.
        if (!$grade) {
            return 0;
        }

        if ($grade->scaleid) {
            return self::get_grade_by_scale($grade->scaleid, $grade->finalgrade);
        }

        // Formata a nota final.
        if ($grade->finalgrade) {
            $finalgrade = number_format($grade->finalgrade, 2);
        }

        if ($grade->rawgrademax > 10 && $grade->finalgrade > 1) {
            $finalgrade = ($grade->finalgrade - 1) / $grade->rawgrademax;
            $finalgrade = number_format($finalgrade, 2);
        }

        return $finalgrade;
    }

    /**
     * @param $userid
     * @param $courseid
     * @return float|int|mixed|string
     * @throws dml_exception
     */
    public static function get_student_course_grade($userid, $courseid) {
        global $DB;

        $finalgrade = 0;

        $sql = "SELECT gg.*, gi.scaleid
                FROM {grade_grades} gg
                INNER JOIN {grade_items} gi ON gi.id = gg.itemid
                WHERE gg.userid = :userid AND gi.courseid = :courseid AND gi.itemtype = 'course'";

        $grade = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);

        // Retorna 0 caso não seja encontrados registros.
        if (!$grade) {
            return 0;
        }

        if ($grade->scaleid) {
            return self::get_grade_by_scale($grade->scaleid, $grade->finalgrade);
        }

        // Formata a nota final.
        if ($grade->finalgrade) {
            $finalgrade = number_format($grade->finalgrade, 2);
        }

        if ($grade->rawgrademax > 10 && $grade->finalgrade > 1) {
            $finalgrade = ($grade->finalgrade - 1) / $grade->rawgrademax;
            $finalgrade = number_format($finalgrade, 2);
        }

        return $finalgrade;
    }

    /**
     * @param $scaleid
     * @param $grade
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_grade_by_scale($scaleid, $grade) {
        global $DB;

        $scale = $DB->get_record('scale', array('id' => $scaleid), '*');
        $scale = $scale->scale;
        $scalearr = explode(', ', $scale);

        return $scalearr[(int) $grade - 1];
    }
}
