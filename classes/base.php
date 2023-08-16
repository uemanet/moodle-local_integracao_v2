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

require_once($CFG->libdir . "/externallib.php");

/**
 * Class wsintregacao_v2_base
 *
 * @copyright 2020 Pedro Fellipe Melo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wsintegracao_v2_base extends external_api
{

    /**
     * @param $pesid
     * @return object
     * @throws moodle_exception
     */
    protected static function get_user_by_pes_id($pesid)
    {
        global $DB, $CFG;

        try {
            $userid = null;

            $result = $DB->get_record('int_pessoa_user', array('pes_id' => $pesid), '*');

            if ($result) {
                $userid = $result->userid;
            }

            return $userid;
        } catch (\Exception $e) {
            if ($CFG->debug == DEBUG_DEVELOPER) {
                throw new moodle_exception('databaseaccesserror', 'local_v2_integracao', null, null, '');
            }
        }
    }

    /**
     * @param $userid
     * @param $courseid
     * @param $roleid
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function enrol_user_in_moodle_course($userid, $courseid, $roleid)
    {
        global $CFG;

        $courseenrol = self::get_course_enrol($courseid);

        require_once($CFG->libdir . "/enrollib.php");
        if (!$enrolmanual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }

        $enrolmanual->enrol_user($courseenrol, $userid, $roleid, time());
    }

    /**
     * @param $courseid
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_course_enrol($courseid)
    {
        global $DB;

        $enrol = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'), '*', MUST_EXIST);
        return $enrol;
    }


    /**
     * @param $courseid
     * @param $name
     * @param $description
     * @return mixed
     * @throws dml_exception
     */
    protected static function create_group_course($course, $name, $description)
    {
        global $DB;

        $groupdata['courseid'] = $course->id;
        $groupdata['name'] = $name;
        $groupdata['description'] = $description;
        $groupdata['descriptionformat'] = 1;
        $groupdata['timecreated'] = time();
        $groupdata['timemodified'] = $groupdata['timecreated'];

        $resultid = $DB->insert_record('groups', $groupdata);

        $courseoptions = $DB->get_record('course', array('id' => $course->id), '*');

        $courseoptions->groupmode = 1;
        $courseoptions->groupmodeforce = 1;
        $DB->update_record('course', $courseoptions);

        cache_helper::invalidate_by_definition('core', 'groupdata', array(), array($course->id));

        return $resultid;
    }

    /**
     * @param $user
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected static function save_user($user)
    {
        global $CFG;

        require_once("{$CFG->dirroot}/user/lib.php");

        $user->confirmed = 1;
        $user->mnethostid = 1;
        $userid = user_create_user($user);

        self::send_instructions_email($userid, $user->password);

        return $userid;
    }

    /**
     * @param $tipo
     * @return int
     */
    protected static function get_config_role($tipo) {
        $config = get_config('local_integracao_v2');

        switch ($tipo) {
            case 'presencial':
                return $config->tutor_presencial;
            case 'distancia':
                return $config->tutor_distancia;
            case 'orientador':
                return $config->orientador;
            default:
                return null;
        }
    }

    /**
     * @param $userid
     * @param $senha
     * @throws dml_exception
     */
    protected static function send_instructions_email($userid, $senha)
    {
        global $CFG, $DB;

        require_once("{$CFG->dirroot}/lib/moodlelib.php");

        $user = $DB->get_record('user', array('id' => $userid));

        $subject = "Instruções de acesso";

        $messagehtml = '<p style="line-height:21px;font-size:20px;margin-top:20px;margin-bottom:0px">Prezado usuário,<br><br>
                        Para nós é um enorme prazer tê-lo(a) em um dos nossos cursos de Educação a Distância.<br>
                        Você já possui cadastro no AVA, no entanto, deverá seguir as instruções abaixo para acessá-lo:</p><br>
                        <p style="line-height:28px;font-size:20px;margin-top:20px;margin-bottom:0px;text-align:center">
                          <strong style="line-height:inherit">Instruções para acesso ao AVA</strong>
                        </p>
                        <br><br>
                        <blockquote style="line-height:inherit;margin:20px 0px 0px;padding-left:14px;border-left:4px solid rgb(189,189,189)">
                          <p style="line-height:21px;font-size:14px;margin-top:20px;margin-bottom:20px">
                          - Acesse o endereço: ' . $CFG->wwwroot . '<br>
                          - na caixa de texto "Usuário", digite: ' . $user->username . '<br>
                          - na caixa de texto "Senha", digite: <b>changeme</b> <br>
                          - clique no botão "Acessar"<br>
                          - uma nova página com três caixas de texto será exibida.<br>
                          - na caixa de texto "Senha Atual", digite: <b>changeme</b><br>
                          - na caixa de texto "Nova senha", digite uma nova senha para ser utilizada nos seus próximos acessos<br>
                          - na caixa de texto "Nova senha (novamente)", digite novamente a sua nova senha de acesso<br>
                          - clique no botão "Salvar mudanças"<br>
                          - uma nova página com o texto "A senha foi alterada" será exibida.<br>
                          - clique em "Continuar"
                          </p>
                        </blockquote>
                        <br><br>
                        Seja Bem Vindo(a)!<br><br>
                        <b>Obs: Esse é apenas um e-mail informativo. Não responda este e-mail.</b><br>';

        email_to_user($user, '', $subject, '', $messagehtml, '', '', false);
    }

    /**
     * @param $ofdid
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_course_by_ofd_id($ofdid)
    {
        global $DB;

        $course = $DB->get_record('int_v2_discipline_course', array('ofd_id' => $ofdid), '*');

        return $course;
    }

    /**
     * @param $userid
     * @param $courseid
     * @throws coding_exception
     * @throws dml_exception
     */
    protected static function unenrol_user_in_moodle_course($userid, $courseid)
    {
        global $CFG;
        require_once($CFG->libdir . "/enrollib.php");

        $courseenrol = self::get_course_enrol($courseid);

        if (!$enrolmanual = enrol_get_plugin('manual')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }
        $enrolmanual->unenrol_user($courseenrol, $userid);

    }

}
