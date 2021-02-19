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
class local_wsintegracao_v2_enrol_discipline extends wsintegracao_v2_base
{

    /**
     * @param $enrol
     * @return array|null
     * @throws \Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function enrol_student_discipline($enrol) {
        global $CFG, $DB;

        self::validate_parameters(self::enrol_student_discipline_parameters(), array('enrol' => $enrol));

        $enrol = (object)$enrol;

        $course = self::get_course_by_ofd_id($enrol->ofd_id);

        if (!$course) {
            throw new \Exception("Nao existe um curso mapeado para essa disciplina oferecida. ofd_id: " . $enrol->ofd_id);
        }

        $userid = self::get_user_by_pes_id($enrol->pes_id);

        if (!$userid) {
            throw new \Exception("Nenhum usuario esta mapeado para o aluno com pes_id: " . $enrol->pes_id);
        }

        $returndata = null;

        try {

            $transaction = $DB->start_delegated_transaction();

            $studentrole = get_config('local_integracao_v2')->aluno;
            self::enrol_user_in_moodle_course($userid, $course->course, $studentrole);

            $group = $DB->get_record('int_v2_groups_course', array('course' => $course->course, 'grp_id' => $enrol->grp_id) , '*');
            if ($group) {

                require_once("{$CFG->dirroot}/group/lib.php");

                groups_add_member($group->group_id, $userid);
            }

            $student_discipline['mat_id'] = $enrol->mat_id;
            $student_discipline['userid'] = $userid;
            $student_discipline['pes_id'] = $enrol->pes_id;
            $student_discipline['trm_id'] = $enrol->trm_id;
            $student_discipline['grp_id'] = $enrol->grp_id;
            $student_discipline['mof_id'] = $enrol->mof_id;
            $student_discipline['course'] = $course->course;

            $result = $DB->insert_record('int_v2_student_discipline', $student_discipline);

            $transaction->allow_commit();

            $returndata = array(
                'id' => $result,
                'status' => 'success',
                'message' => 'Aluno matriculado na disciplina'
            );

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function enrol_student_discipline_parameters() {
        return new external_function_parameters(
            array(
                'enrol' => new external_single_structure(
                    array(
                        'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia'),
                        'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina'),
                        'pes_id' => new external_value(PARAM_INT, 'Id do aluno'),
                        'mat_id' => new external_value(PARAM_INT, 'Id de matrícula do aluno no lado do harpia'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no lado do harpia'),
                        'trm_id' => new external_value(PARAM_INT, 'Id do grupo no lado do harpia'),
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function enrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do aluno matriculado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }



    /**
     * @param $batch
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     * @return array
     */
    public static function batch_enrol_student_discipline($batch) {
        global $CFG, $DB;

        try {
            $transaction = $DB->start_delegated_transaction();
            require_once("{$CFG->dirroot}/group/lib.php");

            foreach ($batch as $enrol) {

                self::validate_parameters(self::enrol_student_discipline_parameters(), array('enrol' => $enrol));

                $enrol = (object)$enrol;

                $course = self::get_course_by_ofd_id($enrol->ofd_id);

                if (!$course) {
                    throw new \Exception("Nao existe um curso mapeado para essa disciplina oferecida. ofd_id: " . $enrol->ofd_id);
                }

                $userid = self::get_user_by_pes_id($enrol->pes_id);

                if (!$userid) {
                    throw new \Exception("Nenhum usuario esta mapeado para o aluno com pes_id: " . $enrol->pes_id);
                }

                $returndata = null;

                $studentrole = get_config('local_integracao_v2')->aluno;
                self::enrol_user_in_moodle_course($userid, $course->course, $studentrole);

                $group = $DB->get_record('int_v2_groups_course', array('course' => $course->course, 'grp_id' => $enrol->grp_id) , '*');

                if ($group) {

                    groups_add_member($group->group_id, $userid);
                }

                $student_discipline['mat_id'] = $enrol->mat_id;
                $student_discipline['userid'] = $userid;
                $student_discipline['pes_id'] = $enrol->pes_id;
                $student_discipline['trm_id'] = $enrol->trm_id;
                $student_discipline['grp_id'] = $enrol->grp_id;
                $student_discipline['mof_id'] = $enrol->mof_id;
                $student_discipline['course'] = $course->course;

                $result = $DB->insert_record('int_v2_student_discipline', $student_discipline);

            }

            $transaction->allow_commit();

            $returndata = array(
                'id' => $result,
                'status' => 'success',
                'message' => 'Matrícula em lote concluída com sucesso'
            );
        } catch (\Exception $exception) {
            $transaction->rollback($exception);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function batch_enrol_student_discipline_parameters() {
        $innerstructure = new external_single_structure(
            array(
                'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia'),
                'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina'),
                'pes_id' => new external_value(PARAM_INT, 'Id do aluno'),
                'mat_id' => new external_value(PARAM_INT, 'Id de matrícula do aluno no lado do harpia'),
                'grp_id' => new external_value(PARAM_INT, 'Id do grupo no lado do harpia'),
                'trm_id' => new external_value(PARAM_INT, 'Id do grupo no lado do harpia'),
            )
        );

        return new external_function_parameters(
            array(
                'enrol' => new external_multiple_structure($innerstructure)
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function batch_enrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do aluno matriculado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $enrol
     * @return array|null
     * @throws \Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function unenrol_student_discipline($enrol) {
        global $CFG, $DB;

        // Validação dos parametros.
        self::validate_parameters(self::unenrol_student_discipline_parameters(), array('enrol' => $enrol));

        $enrol = (object)$enrol;

        $enrol_discipline = $DB->get_record('int_v2_student_discipline', array('mof_id' => $enrol->mof_id), '*');
        if (!$enrol_discipline) {
            throw new \Exception("Matrícula em disciplina não mapeada com o moodle. mof_id: " . $enrol->mof_id);
        }

        $returndata = null;


        try {
            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            $userdiscipline = $DB->get_record('int_v2_student_discipline', array('mof_id' => $enrol->mof_id), '*');

            self::unenrol_user_in_moodle_course($userdiscipline->userid, $userdiscipline->course);

            $res = $DB->delete_records('int_v2_student_discipline',['mof_id' => $enrol->mof_id]);

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

            $returndata = array(
                'id' => $enrol->mof_id,
                'status' => 'success',
                'message' => 'Aluno desmatriculado da disciplina'
            );

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_single_structure
     */
    public static function unenrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da matrícula na disciplina'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_student_discipline_parameters() {
        return new external_function_parameters(
            array(
                'enrol' => new external_single_structure(
                    array(
                        'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia')
                    )
                )
            )
        );
    }

    /**
     * @param $batch
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     * @return array
     */
    public static function batch_unenrol_student_discipline($batch) {
        global $CFG, $DB;

        try {
            $transaction = $DB->start_delegated_transaction();
            foreach ($batch as $enrol) {

                // Validação dos parametros.
                self::validate_parameters(self::unenrol_student_discipline_parameters(), array('enrol' => $enrol));

                $enrol = (object)$enrol;

                // Verifica se o aluno ja esta matriculado para a disciplina.
                $params = array('mof_id' => $enrol->mof_id);

                $returndata = null;

                $userdiscipline = $DB->get_record('int_v2_student_discipline', $params, '*');

                if (!$userdiscipline) {
                    throw new \Exception("Matrícula em disciplina não mapeada com o moodle. mof_id: " . $enrol->mof_id);
                }

                self::unenrol_user_in_moodle_course($userdiscipline->userid, $userdiscipline->course);

                $res = $DB->delete_records('int_v2_student_discipline',['mof_id' => $enrol->mof_id]);

            }

            $transaction->allow_commit();

            $returndata = array(
                'id' => $enrol->mof_id,
                'status' => 'success',
                'message' => 'Alunos desmatriculados em lote com sucesso'
            );
        } catch (\Exception $exception) {
            $transaction->rollback($exception);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function batch_unenrol_student_discipline_parameters() {
        $innerstructure = new external_single_structure(
            array(
                'mof_id' => new external_value(PARAM_INT, 'Id da matricula na oferta de disciplina no Harpia')
            )
        );

        return new external_function_parameters(
            array(
                'enrol' => new external_multiple_structure($innerstructure)
            )
        );
    }
    
    /**
     * @return external_single_structure
     */
    public static function batch_unenrol_student_discipline_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da matrícula na disciplina'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

}
