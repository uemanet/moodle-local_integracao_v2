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
class local_wsintegracao_v2_discipline extends wsintegracao_v2_base
{

    /**
     * @param $discipline
     * @return null
     * @throws Exception
     */
    public static function create_discipline($discipline)
    {
        global $CFG, $DB;

        self::validate_parameters(self::create_discipline_parameters(), array('discipline' => $discipline));

        $discipline = (object)$discipline;

        $discipline_mapping = $DB->get_record('int_v2_discipline_course', array('ofd_id' => $discipline->ofd_id), '*');
        if ($discipline_mapping) {
            throw new \Exception('Essa disciplina já está mapeada no moodle. ofd_id: ' . $discipline->ofd_id);
        }

        $mapping_course = $DB->get_record('int_v2_class', array('trm_id' => $discipline->trm_id), '*');
        if (!$mapping_course) {
            $message = 'Não existe uma turma mapeada para essa disciplina. trm_id: ' . $discipline->trm_id;
            throw new \Exception($message);
        }

        $student_role = get_config('local_integracao_v2')->aluno;
        $teacherrole = get_config('local_integracao_v2')->professor;
        $tutorpresencialrole = get_config('local_integracao_v2')->tutor_presencial;
        $tutordistanciarole = get_config('local_integracao_v2')->tutor_distancia;

        $returndata = [];

        require_once("{$CFG->dirroot}/course/lib.php");

        try {

            $transaction = $DB->start_delegated_transaction();

            $category_parent = $DB->get_record('course_categories', array('idnumber' => $discipline->crs_id), '*');
            $category = $DB->get_record('course_categories', array('parent' => $category_parent->id), '*');
            $discipline->category = $category->id;

            $result = create_course($discipline);

            $teacher = (object)$discipline->teacher;
            $userid = self::get_user_by_pes_id($teacher->pes_id);

            if (!$userid) {
                $userid = self::create_teacher((object)$discipline->teacher);
            }

            self::enrol_user_in_moodle_course($userid, $result->id, $teacherrole);

            require_once("{$CFG->dirroot}/group/lib.php");

            $groups = $DB->get_records('int_v2_groups', array('trm_id' => $discipline->trm_id));
            foreach ($groups as $group) {

                $groupid = self::create_group_course($result, $group->name, $group->description);


                $groups_members = $DB->get_records('int_v2_tutor_group', array('grp_id' => $group->grp_id));
                foreach ($groups_members as $groups_member) {

                    $user = self::get_user_by_pes_id($groups_member->pes_id);

                    if ($groups_member->ttg_tipo_tutoria == "presencial") {
                        self::enrol_user_in_moodle_course($user, $result->id, $tutorpresencialrole);
                    } else {
                        self::enrol_user_in_moodle_course($user, $result->id, $tutordistanciarole);
                    }
                    $res = groups_add_member($groupid, $user);

                }

                $data_groups_course['course'] = (int)$result->id;
                $data_groups_course['grp_id'] = $group->grp_id;
                $data_groups_course['group_id'] = $groupid;
                $data_groups_course['per_id'] = $discipline->per_id;


                $DB->insert_record('int_v2_groups_course', $data_groups_course);

            }

            $data_discipline_course['trm_id'] = $discipline->trm_id;
            $data_discipline_course['ofd_id'] = $discipline->ofd_id;
            $data_discipline_course['course'] = $result->id;
            $data_discipline_course['pes_id'] = $discipline->trm_id;

            $res = $DB->insert_record('int_v2_discipline_course', $data_discipline_course);

            $returndata['id'] = $res;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Disciplina criada com sucesso';

            $transaction->allow_commit();

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;

    }

    /**
     * @return external_function_parameters
     */
    public static function create_discipline_parameters()
    {

        return new external_function_parameters(
            array(
                'discipline' => new external_single_structure(
                    array(
                        'crs_id' => new external_value(PARAM_INT, 'Id da do período letivo no harpia'),
                        'per_id' => new external_value(PARAM_INT, 'Id da do período letivo no harpia'),
                        'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no harpia'),
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma no harpia'),
                        'per_nome' => new external_value(PARAM_TEXT, 'Id do periodo letivo no harpia'),
                        'category' => new external_value(PARAM_TEXT, 'Categoria do curso'),
                        'shortname' => new external_value(PARAM_TEXT, 'Nome curto do curso'),
                        'name' => new external_value(PARAM_TEXT, 'Nome curto do curso'),
                        'fullname' => new external_value(PARAM_TEXT, 'Nome completo do curso'),
                        'summaryformat' => new external_value(PARAM_INT, 'Formato do sumario'),
                        'format' => new external_value(PARAM_TEXT, 'Formato do curso'),
                        'numsections' => new external_value(PARAM_INT, 'Quantidade de sections'),
                        'teacher' => new external_single_structure(
                            array(
                                'pes_id' => new external_value(PARAM_INT, 'Id de pessoa vinculado ao professor no gestor'),
                                'firstname' => new external_value(PARAM_TEXT, 'Primeiro nome do professor'),
                                'lastname' => new external_value(PARAM_TEXT, 'Ultimo nome do professor'),
                                'email' => new external_value(PARAM_TEXT, 'Email do professor'),
                                'username' => new external_value(PARAM_TEXT, 'Usuario de acesso do professor'),
                                'password' => new external_value(PARAM_TEXT, 'Senha do professor'),
                                'city' => new external_value(PARAM_TEXT, 'Cidade do tutor', VALUE_OPTIONAL, null)
                            )
                        ),
                    )
                )
            )
        );
    }


    /**
     * @return external_single_structure
     */
    public static function create_discipline_returns()
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
     * @param $teacher
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    private static function create_teacher($teacher)
    {
        global $DB;

        $userid = self::save_user($teacher);

        $data['pes_id'] = $teacher->pes_id;
        $data['userid'] = $userid;

        $DB->insert_record('int_pessoa_user', $data);

        return $userid;
    }

    /**
     * @param $discipline
     * @return mixed
     * @throws \Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function remove_discipline($discipline)
    {
        global $CFG, $DB;

        self::validate_parameters(self::remove_discipline_parameters(), array('discipline' => $discipline));

        $discipline = (object)$discipline;

        $discipline_mapping = $DB->get_record('int_v2_discipline_course', array('ofd_id' => $discipline->ofd_id), '*');
        if (!$discipline_mapping) {
            throw new \Exception('Essa disciplina não está mapeada com o moodle. ofd_id: ' . $discipline->ofd_id);
        }

        try {

            $transaction = $DB->start_delegated_transaction();

            require_once("{$CFG->dirroot}/course/lib.php");

            $discipline_course = $DB->get_record('int_v2_discipline_course', array('ofd_id' => $discipline->ofd_id));

            $userid = self::get_user_by_pes_id($discipline->pes_id);

            self::unenrol_user_in_moodle_course($userid, $discipline_course->course);

            delete_course($discipline_course->course, false);

            $DB->delete_records('int_v2_discipline_course', array('ofd_id' => $discipline->ofd_id));

            $transaction->allow_commit();

            $returndata['id'] = 1;
            $returndata['status'] = 'success';
            $returndata['message'] = "Curso excluído com sucesso";


        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = 'Disciplina deletada com sucesso';

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function remove_discipline_parameters()
    {
        return new external_function_parameters(
            array(
                'discipline' => new external_single_structure(
                    array(
                        'ofd_id' => new external_value(PARAM_INT, 'Id da oferta de disciplina no gestor')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function remove_discipline_returns()
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
