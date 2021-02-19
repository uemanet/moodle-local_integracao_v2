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
 * Class local_wsintegracao_v2_group
 * @copyright   2020 Uemanet
 * @author      Pedro Fellipe Melo
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsintegracao_v2_group extends wsintegracao_v2_base
{

    public static function create_group($group)
    {
        global $CFG, $DB;

        self::validate_parameters(self::create_group_parameters(), array('group' => $group));

        $group = (object)$group;

        $groupid = $DB->get_record('int_v2_groups', array('grp_id' => $group->grp_id), '*');
        if ($groupid) {
            throw new \Exception("Ja existe um grupo mapeado para o ambiente com grp_id: " . $group->grp_id);
        }

        $mapping_course = $DB->get_record('int_v2_class', array('trm_id' => $group->trm_id), '*');
        if (!$mapping_course) {
            throw new \Exception("Esta turma não está mapeada com o ambiente virtual . trm_id: " . $group->trm_id);
        }

        $courses = $DB->get_records('int_v2_discipline_course', array('trm_id' => $group->trm_id));

        $returndata = null;

        try {

            $transaction = $DB->start_delegated_transaction();

            foreach ($courses as $course) {

                $course_moodle = $DB->get_record('course', array('id' => $course->course), '*');

                $resultid = self::create_group_course($course_moodle, $group->name, $group->description);

                $data['course'] = (int)$course_moodle->id;
                $data['grp_id'] = $group->grp_id;
                $data['group_id'] = $resultid;
                $data['per_id'] = $group->per_id;

                $res = $DB->insert_record('int_v2_groups_course', $data);

            }

            $data['trm_id'] = $group->trm_id;
            $data['grp_id'] = $group->grp_id;
            $data['name'] = $group->name;
            $data['description'] = $group->description;

            $res = $DB->insert_record('int_v2_groups', $data);

            $returndata['id'] = $res;
            $returndata['status'] = 'success';
            $returndata['message'] = 'Grupo criado com sucesso';

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function create_group_parameters()
    {
        return new external_function_parameters(
            array(
                'group' => new external_single_structure(
                    array(
                        'trm_id' => new external_value(PARAM_INT, 'Id da turma do grupo no gestor'),
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                        'name' => new external_value(PARAM_TEXT, 'Nome do grupo'),
                        'description' => new external_value(PARAM_TEXT, 'Descrição do grupo'),
                        'per_id' => new external_value(PARAM_INT, 'Id do período letivo no harpia', VALUE_DEFAULT, null),
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function create_group_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do grupo criado'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $group
     * @return mixed
     * @throws Exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function remove_group($group)
    {
        global $CFG, $DB;

        self::validate_parameters(self::remove_group_parameters(), array('group' => $group));

        require_once("{$CFG->dirroot}/group/lib.php");

        $group = (object)$group;

        $group_mapping = $DB->get_record('int_v2_groups', array('grp_id' => $group->grp_id), '*');

        if (!$group_mapping) {
            throw new \Exception("Não existe um grupo mapeado para o ambiente com grp_id: " . $group->grp_id);
        }

        try {

            // Inicia a transacao, qualquer erro que aconteca o rollback sera executado.
            $transaction = $DB->start_delegated_transaction();

            $groupcourses = $DB->get_records('int_v2_groups_course', array('grp_id' => $group->grp_id, 'per_id' => $group->per_id));

            foreach ($groupcourses as $item) {

                // Deleta o curso usando a biblioteca do proprio moodle.
                groups_delete_group($item->group_id);

            }

            // Deleta os registros da tabela de controle.
            $DB->delete_records('int_v2_groups', array('grp_id' => $group->grp_id));
            $DB->delete_records('int_v2_groups_course', array('grp_id' => $group->grp_id));

            // Persiste as operacoes em caso de sucesso.
            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = 0;
        $returndata['status'] = 'success';
        $returndata['message'] = "Grupo excluído com sucesso";

        return $returndata;

    }

    /**
     * @return external_function_parameters
     */
    public static function remove_group_parameters()
    {
        return new external_function_parameters(
            array(
                'group' => new external_single_structure(
                    array(
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no gestor'),
                        'per_id' => new external_value(PARAM_INT, 'Id do período letivo no harpia', VALUE_DEFAULT, null),
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function remove_group_returns()
    {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Id do grupo removido'),
                'status' => new external_value(PARAM_TEXT, 'Status da operacao'),
                'message' => new external_value(PARAM_TEXT, 'Mensagem de retorno da operacao')
            )
        );
    }

    /**
     * @param $group
     * @return mixed
     * @throws Exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws invalid_parameter_exception
     */
    public static function update_group($group)
    {
        global $CFG, $DB;

        self::validate_parameters(self::update_group_parameters(), array('group' => $group));

        $group = (object)$group;

        $group_mapping = $DB->get_record('int_v2_groups', array('grp_id' => $group->grp_id), '*');
        if (!$group_mapping) {
            throw new \Exception("Não existe um grupo mapeado para o ambiente com grp_id: " . $group->grp_id);
        }

        require_once("{$CFG->dirroot}/group/lib.php");

        try {

            $transaction = $DB->start_delegated_transaction();

            $groupcourses = $DB->get_records('int_v2_groups_course', array('grp_id' => $group->grp_id, 'per_id' => $group->per_id));

            foreach ($groupcourses as $item) {

                $groupobject = $DB->get_record('groups', array('id' => $item->group_id), '*');
                $groupobject->name = $group->grp_nome;
                groups_update_group($groupobject);

            }

            $group_mapping_object = $DB->get_record('int_v2_groups', array('grp_id' => $group->grp_id), '*');
            $group_mapping_object->name = $group->grp_nome;
            $DB->update_record('int_v2_groups', $group_mapping_object, $bulk = false);

            $transaction->allow_commit();

        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        // Prepara o array de retorno.
        $returndata['id'] = $group->grp_id;
        $returndata['status'] = 'success';
        $returndata['message'] = "Grupo atualizado com sucesso";

        return $returndata;
    }

    /**
     * @return external_function_parameters
     */
    public static function update_group_parameters()
    {
        return new external_function_parameters(
            array(
                'group' => new external_single_structure(
                    array(
                        'grp_id' => new external_value(PARAM_INT, 'Id do grupo no harpia'),
                        'per_id' => new external_value(PARAM_INT, 'Id do período letivo no harpia', VALUE_DEFAULT, null),
                        'grp_nome' => new external_value(PARAM_TEXT, 'Nome do grupo')
                    )
                )
            )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function update_group_returns()
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