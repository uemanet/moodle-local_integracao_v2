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
 * Class local_wsintegracao_v2_student
 * @copyright 2020 Pedro Fellipe Melo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsintegracao_v2_student extends wsintegracao_v2_base
{
    /**
     * @param $student
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function change_student_group($student)
    {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::change_student_group_parameters(), array('student' => $student));

        // Transforma o array em objeto.
        $student = (object)$student;

        try {

            $transaction = $DB->start_delegated_transaction();

            require_once("{$CFG->dirroot}/group/lib.php");

            $studentclass = $DB->get_record('int_v2_student_class',
                array(
                    'trm_id' => $student->trm_id,
                    'pes_id' => $student->pes_id),
                '*'
                );

            if($studentclass->grp_id){
                $groups_courses = $DB->get_records('int_v2_groups_course', array('grp_id' => $studentclass->grp_id));
                foreach ($groups_courses as $group_course) {
                    groups_remove_member($group_course->group_id, $studentclass->userid);

                }
            }

            $groups_courses_new = $DB->get_records('int_v2_groups_course', array('grp_id' => $student->new_grp_id));

            foreach ($groups_courses_new as $group_course_new) {
                groups_add_member($group_course_new->group_id, $studentclass->userid);
            }

            $studentclass->grp_id = $student->new_grp_id;

            $DB->update_record('int_v2_student_class', $studentclass);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Aluno trocado de grupo com sucesso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function change_student_group_parameters()
    {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matrícula da pessoa do gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma do gestor'),
                        'new_grp_id' => new external_value(PARAM_INT, 'Id do novo grupo no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function change_student_group_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }


    /**
     * @param $student
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function unenrol_student_group($student)
    {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::unenrol_student_group_parameters(), array('student' => $student));

        // Transforma o array em objeto.
        $student = (object)$student;

        try {

            $transaction = $DB->start_delegated_transaction();

            require_once("{$CFG->dirroot}/group/lib.php");

            $studentclass = $DB->get_record('int_v2_student_class', array('grp_id' => $student->grp_id, 'pes_id' => $student->pes_id), '*');

            $groups_courses = $DB->get_records('int_v2_groups_course', array('grp_id' => $student->old_grp_id));

            foreach ($groups_courses as $group_course) {

                groups_remove_member($group_course->group_id, $studentclass->userid);

            }

            $studentclass->grp_id = null;

            $DB->update_record('int_v2_student_class', $studentclass);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Aluno desvinculado do grupo com sucesso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_student_group_parameters()
    {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'mat_id' => new external_value(PARAM_INT, 'Id da matrícula da pessoa do gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa do gestor'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function unenrol_student_group_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }


    /**
     * @param $student
     * @return array
     * @throws \Exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function change_role_student_course($student)
    {
        global $DB;

        // Validação dos parâmetros.
        self::validate_parameters(self::change_role_student_course_parameters(), array('student' => $student));

        $student = (object)$student;

        $mapping = $DB->get_record('int_v2_student_class', array('mat_id' => $student->mat_id), '*');
        if (!$mapping) {
            throw new \Exception("Esta matrícula não está vinculada com o ambiente virtual . mat_id: " . $student->mat_id);
        }

        $enrol_disciplines = $DB->get_record('int_v2_student_discipline', array('mat_id' => $student->mat_id));

        try {

            $transaction = $DB->start_delegated_transaction();

            foreach ($enrol_disciplines as $enrol_discipline) {

                $context = context_course::instance($enrol_discipline->course);

                $config = get_config('local_integracao_v2');

                $roleid = null;
                if ($student->new_status == 'concluido') {
                    $roleid = $config->aluno_concluido;
                } else if ($student->new_status == 'reprovado') {
                    $roleid = $config->aluno_reprovado;
                } else if ($student->new_status == 'cursando') {
                    $roleid = $config->aluno;
                } else if ($student->new_status == 'evadido') {
                    $roleid = $config->aluno_evadido;
                } else if ($student->new_status == 'desistente') {
                    $roleid = $config->aluno_desistente;
                } else if ($student->new_status == 'trancado') {
                    $roleid = $config->aluno_trancado;
                }

                $roleassignment = $DB->get_record('role_assignments', array('userid' => $enrol_discipline->userid, 'contextid' => $context->id));

                $roleassignment->roleid = $roleid;
                $DB->update_record('role_assignments', $roleassignment);
            }

            $reuturnArray = array(
                'id' => 0,
                'status' => 'success',
                'message' => 'Status da Matricula alterado com sucesso'
            );

            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $reuturnArray;

    }

    /**
     * @return external_function_parameters
     */
    public static function change_role_student_course_parameters()
    {
        return new external_function_parameters(
            array(
                'student' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma do aluno no gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no gestor'),
                        'mat_id' => new external_value(PARAM_INT, 'Id da matrícula no gestor'),
                        'new_status' => new external_value(PARAM_TEXT, 'Novo status da matricula do aluno no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function change_role_student_course_returns()
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