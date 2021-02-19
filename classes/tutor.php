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
 *
 * @copyright   2020 Pedro Fellipe Melo
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsintegracao_v2_tutor extends wsintegracao_v2_base
{

    /**
     * @param $tutor
     * @return null
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function enrol_tutor($tutor)
    {
        global $CFG, $DB;

        // Validação dos paramêtros.
        self::validate_parameters(self::enrol_tutor_parameters(), array('tutor' => $tutor));

        $tutor = (object)$tutor;

        $group = $DB->get_record('int_v2_groups', array('grp_id' => $tutor->grp_id), '*');
        if (!$group) {
            throw new \Exception("Não existe um grupo mapeado no moodle com grp_id:" . $tutor->grp_id);
        }

        $tutorgroup = $DB->get_record('int_v2_tutor_group', array('pes_id' => $tutor->pes_id, 'grp_id' => $tutor->grp_id), '*');
        if ($tutorgroup) {
            throw new \Exception("O tutor de pes_id " . $tutor->pes_id . " já está vinculado ao grupo de grp_id " . $tutor->grp_id);
        }

        $returndata = null;

        try {
            $transaction = $DB->start_delegated_transaction();

            $tutorpresencialrole = get_config('local_integracao_v2')->tutor_presencial;
            $tutordistanciarole = get_config('local_integracao_v2')->tutor_distancia;

            $userid = self::get_user_by_pes_id($tutor->pes_id);

            if (!$userid) {
                $userid = self::save_user($tutor);

                $data['pes_id'] = $tutor->pes_id;
                $data['userid'] = $userid;

                $DB->insert_record('int_pessoa_user', $data);
            }

            $user = self::get_user_by_pes_id($tutor->pes_id);

            $groups_courses = $DB->get_records('int_v2_groups_course', array('grp_id' => $tutor->grp_id));

            require_once("{$CFG->dirroot}/group/lib.php");

            foreach ($groups_courses as $group) {

                if ($tutor->ttg_tipo_tutoria == "presencial") {
                    self::enrol_user_in_moodle_course($user, $group->course, $tutorpresencialrole);
                } else {
                    self::enrol_user_in_moodle_course($user, $group->course, $tutordistanciarole);
                }

                $res = groups_add_member($group->group_id, $user);
            }

            $tutorgroup['pes_id'] = $tutor->pes_id;
            $tutorgroup['userid'] = $user;
            $tutorgroup['grp_id'] = $tutor->grp_id;
            $tutorgroup['ttg_tipo_tutoria'] = $tutor->ttg_tipo_tutoria;

            $result = $DB->insert_record('int_v2_tutor_group', $tutorgroup);

            $returndata['id'] = $result->id;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Tutor vinculado com sucesso';

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function enrol_tutor_parameters()
    {
        return new external_function_parameters(
            array(
                'tutor' => new external_single_structure(
                    array(
                        'ttg_tipo_tutoria' => new external_value(PARAM_TEXT, 'Tipo de tutoria do tutor'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                        'pes_id' => new external_value(PARAM_INT, 'Id da pessoa no gestor'),
                        'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do tutor'),
                        'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do tutor'),
                        'email' => new external_value(PARAM_TEXT, 'Email do tutor'),
                        'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do tutor'),
                        'password' => new external_value(PARAM_TEXT, 'Senha do tutor'),
                        'city' => new external_value(PARAM_TEXT, 'Cidade do tutor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function enrol_tutor_returns()
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
     * @param $tutor
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function unenrol_tutor_group($tutor)
    {
        global $CFG, $DB;

        self::validate_parameters(self::unenrol_tutor_group_parameters(), array('tutor' => $tutor));

        $tutor = (object)$tutor;


        $group = $DB->get_record('int_v2_groups', array('grp_id' => $tutor->grp_id), '*');
        if (!$group) {
            throw new \Exception("Não existe um grupo mapeado no moodle com grp_id:" . $tutor->grp_id);
        }

        $tutorgroup = $DB->get_record('int_v2_tutor_group', array('pes_id' => $tutor->pes_id, 'grp_id' => $tutor->grp_id), '*');
        if (!$tutorgroup) {
            throw new \Exception("O tutor de pes_id " . $tutor->pes_id . " não está vinculado ao grupo de grp_id " . $tutor->grp_id);
        }


        try {

            $transaction = $DB->start_delegated_transaction();

            require_once("{$CFG->dirroot}/group/lib.php");

            $tutorgroup = $DB->get_record('int_v2_tutor_group', array('grp_id' => $tutor->grp_id, 'pes_id' => $tutor->pes_id), '*');

            $groups_courses = $DB->get_records('int_v2_groups_course', array('grp_id' => $tutor->grp_id));

            foreach ($groups_courses as $group_course) {

                groups_remove_member($group_course->group_id, $tutorgroup->userid);

                self::unenrol_user_in_moodle_course($group_course->userid, $group_course->course);

            }

            $DB->delete_records('int_v2_tutor_group', array('id' => $tutorgroup->id));

            $transaction->allow_commit();

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Tutor desvinculado do grupo com sucesso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function unenrol_tutor_group_parameters()
    {
        return new external_function_parameters(
            array(
                'tutor' => new external_single_structure(
                    array(
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
    public static function unenrol_tutor_group_returns()
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
