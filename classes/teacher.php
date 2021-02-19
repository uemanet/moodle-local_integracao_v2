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
 * Class local_wsintegracao_v2_teacher
 * @copyright 2020 Pedro Fellipe Melo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsintegracao_v2_teacher extends wsintegracao_v2_base
{

    /**
     * @param $discipline
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function change_teacher($discipline)
    {
        global $DB;

        // Validação dos parametros.
        self::validate_parameters(self::change_teacher_parameters(), array('discipline' => $discipline));

        // Transforma o array em objeto.
        $discipline['pes_id'] = $discipline['teacher']['pes_id'];
        $discipline = (object)$discipline;

        $discipline_mapping = $DB->get_record('int_v2_discipline_course', array('ofd_id' => $discipline->ofd_id), '*');
        if (!$discipline_mapping) {
            throw new \Exception('Essa disciplina não está mapeada no moodle. ofd_id: ' . $discipline->ofd_id);
        }

        $userid = self::get_user_by_pes_id($discipline->pes_id);
        if (!$userid) {
            throw new \Exception("Este professor não está mapeado com o ambiente virtual . pes_id: " . $discipline->pes_id);
        }

        try {
            $transaction = $DB->start_delegated_transaction();

            $userid = self::get_user_by_pes_id($discipline->pes_id);

            if (!$userid) {
                $userid = self::save_user((object)$discipline->teacher);
                $data['pes_id'] = $discipline->pes_id;
                $data['userid'] = $userid;
                $res = $DB->insert_record('int_pessoa_user', $data);
            }

            $discipline_course = $DB->get_record('int_v2_discipline_course', array('ofd_id' => $discipline->ofd_id), '*');

            $oldteacher = $discipline_course->pes_id;
            $newteacher = $discipline->pes_id;

            $teacherrole = get_config('local_integracao_v2')->professor;
            self::enrol_user_in_moodle_course($userid, $discipline_course->course, $teacherrole);

            $discipline_course->pes_id = $newteacher;
            $DB->update_record('int_v2_discipline_course', $discipline_course);

            $olduserid = self::get_user_by_pes_id($oldteacher);
            self::unenrol_user_in_moodle_course($olduserid, $discipline_course->course);

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }


        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Disciplina atualizada com sucesso';

        return $returndata;

    }

    /**
     * @return external_function_parameters
     */
    public static function change_teacher_parameters()
    {
        return new external_function_parameters(
            array(
                'discipline' => new external_single_structure(
                    array(
                        'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no gestor'),
                        'teacher' => new external_single_structure(
                            array(
                                'pes_id' => new external_value(PARAM_INT, 'Id de pessoa vinculado ao professor no gestor'),
                                'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do professor'),
                                'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do professor'),
                                'email' => new external_value(PARAM_TEXT, 'Email do professor'),
                                'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do professor'),
                                'password' => new external_value(PARAM_TEXT, 'Senha do professor'),
                                'city' => new external_value(PARAM_TEXT, 'Cidade do tutor')
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function change_teacher_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id da disciplina criada'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }
}