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

require_once("base.php");

/**
 * Class local_wsintegracao_v2_course
 * @copyright 2020 Pedro Fellipe Melo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsintegracao_v2_course extends wsintegracao_v2_base
{

    /**
     * @param $course
     * @return null
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function create_course($course)
    {
        global $CFG, $DB;

        self::validate_parameters(self::create_course_parameters(), array('course' => $course));

        $course = (object)$course;

        $returndata = [];

        $mapping = $DB->get_record('int_v2_class', array('trm_id' => $course->trm_id), '*');

        if ($mapping) {
            throw new Exception("Esta turma já está mapeada com o ambiente virtual. trm_id: " . $course->trm_id);
        }

        try {

            $transaction = $DB->start_delegated_transaction();


            $categorie_course = $DB->get_record('course_categories', array('idnumber' => $course->crs_id), '*');

            if (!$categorie_course) {
                $categorie_course = $DB->insert_record('course_categories', ['name' => $course->crs_nome, 'parent' => 0, 'idnumber' => $course->crs_id]);
            }

            $categorie_period = $DB->get_record('course_categories', array('name' => $course->per_nome, 'parent' => $categorie_course->id), '*');
            if (!$categorie_period) {
                $DB->insert_record('course_categories', ['name' => $course->per_nome, 'parent' => $categorie_course]);
            }

            $data['trm_id'] = $course->trm_id;

            $res = $DB->insert_record('int_v2_class', $data);

            $returndata['id'] = $res;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Curso criado com sucesso';

            $transaction->allow_commit();

            return $returndata;

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        $returndata['id'] = 0;
        $returndata['status'] = 'error';
        $returndata['message'] = 'Erro ao tentar criar o curso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function create_course_parameters()
    {
        return new external_function_parameters(
            array(
                'course' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma no gestor'),
                        'per_id' => new external_value(PARAM_TEXT, 'Id do período letivo no harpia'),
                        'crs_id' => new external_value(PARAM_TEXT, 'Id do curso no harpia'),
                        'crs_nome' => new external_value(PARAM_TEXT, 'Nome curto do curso'),
                        'per_nome' => new external_value(PARAM_TEXT, 'Nome completo do curso')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function create_course_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do curso criado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $course
     * @return null
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function remove_course($course)
    {
        global $CFG, $DB;

        self::validate_parameters(self::remove_course_parameters(), array('course' => $course));

        $course = (object)$course;

        $mapping = $DB->get_record('int_v2_class', array('trm_id' => $course->trm_id), '*');

        if (!$mapping) {
            throw new \Exception("Esta turma não está mapeada com o ambiente virtual . trm_id: " . $course->trm_id);
        }

        try {

            $transaction = $DB->start_delegated_transaction();

            $DB->delete_records('int_v2_class', array('trm_id' => $course->trm_id));

            $transaction->allow_commit();

            $returndata['id'] = 1;
            $returndata['status'] = 'success';
            $returndata['message'] = "Curso excluído com sucesso";

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function remove_course_parameters()
    {
        return new external_function_parameters(
            array(
                'course' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function remove_course_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do curso atualizado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }
}
