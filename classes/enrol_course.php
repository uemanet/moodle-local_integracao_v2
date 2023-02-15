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
class local_wsintegracao_v2_enrol_course extends wsintegracao_v2_base
{

    /**
     * @param $course
     * @return null
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function enrol_student($student)
    {
        global $DB;

        self::validate_parameters(self::enrol_student_parameters(), array('student' => $student));

        $mapping = $DB->get_record('int_v2_student_class', array('mat_id' => $student->mat_id), '*');
        if ($mapping) {
            throw new \Exception("Esta matrícula já está mapeada com o ambiente virtual . mat_id: " . $student->mat_id);
        }

        $userid = self::get_user_by_pes_id($student->pes_id);
        if (!$userid) {
            throw new \Exception("Este estudante não está mapeado com o ambiente virtual . pes_id: " . $student->pes_id);
        }

        $student = (object)$student;

        $returndata = [];

        try {

            $transaction = $DB->start_delegated_transaction();


            if (!$userid) {

                $userid = self::save_user($student);

                $data['pes_id'] = $student->pes_id;
                $data['userid'] = $userid;

                $DB->insert_record('int_pessoa_user', $data);
            }

            $data['mat_id'] = $student->mat_id;
            $data['userid'] = $userid;
            $data['pes_id'] = $student->pes_id;
            $data['trm_id'] = $student->trm_id;
            $data['grp_id'] = $student->grp_id;

            $res = $DB->insert_record('int_v2_student_class', $data);

            if (isset($student->itt_id)) {
                $cohort = $DB->get_record('cohort', ['idnumber' => $student->itt_id]);

                if (!$cohort) {
                    throw new \Exception("Não existe um cohort com idnumber criado para esta instituicao de id: " . $student->itt_id);
                }

                cohort_add_member($cohort->id, $userid);
            }

            $returndata['id'] = $res;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Aluno mapeado ao curso com sucesso';

            $transaction->allow_commit();

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;

    }

    /**
     * @return external_function_parameters
     */
    public static function enrol_student_parameters()
    {

        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matricula do aluno no harpia'),
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma do aluno no harpia'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no harpia', VALUE_DEFAULT, null),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no harpia'),
                        'itt_id' => new external_value(PARAM_INT, 'Id da instituicao no harpia', VALUE_OPTIONAL, null),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do student'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do student'),
                        'email' => new external_value(PARAM_TEXT, 'Email do student'),
                        'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do student'),
                        'password' => new external_value(PARAM_TEXT, 'Senha do student'),
                        'city' => new external_value(PARAM_TEXT, 'Cidade do student')
                    )
                )
            )
        );
    }


    /**
     * @return external_single_structure
     */
    public static function enrol_student_returns()
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
     * @param $student
     * @return null
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function unenrol_student($student)
    {
        global $CFG, $DB;

        self::validate_parameters(self::unenrol_student_parameters(), array('student' => $student));

        $student = (object)$student;

        $mapping = $DB->get_record('int_v2_student_class', array('mat_id' => $student->mat_id), '*');
        if (!$mapping) {
            throw new \Exception("Esta matrícula não está vinculada com o ambiente virtual . mat_id: " . $student->mat_id);
        }

        $returndata = null;

        try {

            $transaction = $DB->start_delegated_transaction();

            $DB->delete_records('int_v2_student_class', ['mat_id' => $student->mat_id]);

            $returndata['id'] = $student->mat_id;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Aluno desmatriculado com sucesso';

            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_student_parameters()
    {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matricula do aluno no harpia')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function unenrol_student_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }


}
