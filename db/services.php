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

/*
 * Web Service local plugin functions and services definition
 *
 * @package integracao_v2
 * @copyright 2020 Pedro Fellipe Melo
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$functions = array(
    'local_integracao_v2_create_course' => array(
        'classname' => 'local_wsintegracao_v2_course',
        'methodname' => 'create_course',
        'classpath' => 'local/integracao_v2/classes/course.php',
        'description' => 'Creates a new course',
        'type' => 'write'
    ),
    'local_integracao_v2_delete_course' => array(
        'classname' => 'local_wsintegracao_v2_course',
        'methodname' => 'remove_course',
        'classpath' => 'local/integracao_v2/classes/course.php',
        'description' => 'Creates a new course',
        'type' => 'write'
    ),
    'local_integracao_v2_create_discipline' => array(
        'classname' => 'local_wsintegracao_v2_discipline',
        'methodname' => 'create_discipline',
        'classpath' => 'local/integracao_v2/classes/discipline.php',
        'description' => 'Creates a new discipline',
        'type' => 'write'
    ),
    'local_integracao_v2_create_group' => array(
        'classname' => 'local_wsintegracao_v2_group',
        'methodname' => 'create_group',
        'classpath' => 'local/integracao_v2/classes/group.php',
        'description' => 'Creates a new group',
        'type' => 'write'
    ),
    'local_integracao_v2_enrol_tutor' => array(
        'classname' => 'local_wsintegracao_v2_tutor',
        'methodname' => 'enrol_tutor',
        'classpath' => 'local/integracao_v2/classes/tutor.php',
        'description' => 'Enrol a tutor to a group',
        'type' => 'write'
    ),
    'local_integracao_v2_enrol_student_discipline' => array(
        'classname' => 'local_wsintegracao_v2_enrol_discipline',
        'methodname' => 'enrol_student_discipline',
        'classpath' => 'local/integracao_v2/classes/enrol_discipline.php',
        'description' => 'Enrol a student to a discipline',
        'type' => 'write'
    ),
    'local_integracao_v2_batch_enrol_student_discipline' => array(
        'classname' => 'local_wsintegracao_v2_enrol_discipline',
        'methodname' => 'batch_enrol_student_discipline',
        'classpath' => 'local/integracao_v2/classes/enrol_discipline.php',
        'description' => 'Enrol multiple students in a discipline',
        'type' => 'write'
    ),
    'local_integracao_v2_batch_unenrol_student_discipline' => array(
        'classname' => 'local_wsintegracao_v2_enrol_discipline',
        'methodname' => 'batch_unenrol_student_discipline',
        'classpath' => 'local/integracao_v2/classes/enrol_discipline.php',
        'description' => 'Unenrol multiple students in a discipline',
        'type' => 'write'
    ),
    'local_integracao_v2_enrol_student' => array(
        'classname' => 'local_wsintegracao_v2_enrol_course',
        'methodname' => 'enrol_student',
        'classpath' => 'local/integracao_v2/classes/enrol_course.php',
        'description' => 'Enrol a student to a course',
        'type' => 'write'
    ),
    'local_integracao_v2_update_user' => array(
        'classname' => 'local_wsintegracao_v2_user',
        'methodname' => 'update_user',
        'classpath' => 'local/integracao_v2/classes/user.php',
        'description' => 'Update a user',
        'type' => 'write'
    ),
    'local_integracao_v2_update_group' => array(
        'classname' => 'local_wsintegracao_v2_group',
        'methodname' => 'update_group',
        'classpath' => 'local/integracao_v2/classes/group.php',
        'description' => 'Update a group',
        'type' => 'write'
    ),
    'local_integracao_v2_delete_group' => array(
        'classname' => 'local_wsintegracao_v2_group',
        'methodname' => 'remove_group',
        'classpath' => 'local/integracao_v2/classes/group.php',
        'description' => 'Delete a group',
        'type' => 'write'
    ),
    'local_integracao_v2_unenrol_tutor_group' => array(
        'classname' => 'local_wsintegracao_v2_tutor',
        'methodname' => 'unenrol_tutor_group',
        'classpath' => 'local/integracao_v2/classes/tutor.php',
        'description' => 'Unenrol a tutor from a group',
        'type' => 'write'
    ),
    'local_integracao_v2_unenrol_student' => array(
        'classname' => 'local_wsintegracao_v2_enrol_course',
        'methodname' => 'unenrol_student',
        'classpath' => 'local/integracao_v2/classes/enrol_course.php',
        'description' => 'Unenrol a student',
        'type' => 'write'
    ),
    'local_integracao_v2_change_student_group' => array(
        'classname' => 'local_wsintegracao_v2_student',
        'methodname' => 'change_student_group',
        'classpath' => 'local/integracao_v2/classes/student.php',
        'description' => 'Change a student from a group',
        'type' => 'write'
    ),
    'local_integracao_v2_unenrol_student_discipline' => array(
        'classname' => 'local_wsintegracao_v2_enrol_discipline',
        'methodname' => 'unenrol_student_discipline',
        'classpath' => 'local/integracao_v2/classes/enrol_discipline.php',
        'description' => 'Unenrol a student in a discipline',
        'type' => 'write'
    ),
    'local_integracao_v2_change_role_student_course' => array(
        'classname' => 'local_wsintegracao_v2_student',
        'methodname' => 'change_role_student_course',
        'classpath' => 'local/integracao_v2/classes/student.php',
        'description' => 'Change role for student in a course',
        'type' => 'write'
    ),
    'local_integracao_v2_unenrol_student_group' => array(
        'classname' => 'local_wsintegracao_v2_student',
        'methodname' => 'unenrol_student_group',
        'classpath' => 'local/integracao_v2/classes/student.php',
        'description' => 'Unenrol a student from a group',
        'type' => 'write'
    ),
    'local_integracao_v2_get_grades_batch' => array(
        'classname' => 'local_wsintegracao_v2_grade',
        'methodname' => 'get_grades_batch',
        'classpath' => 'local/integracao_v2/classes/grade.php',
        'description' => 'Return final grade of a list of students',
        'type' => 'read'
    ),
    'local_integracao_v2_get_course_grades_batch' => array(
        'classname' => 'local_wsintegracao_v2_grade',
        'methodname' => 'get_course_grades_batch',
        'classpath' => 'local/integracao_v2/classes/grade.php',
        'description' => 'Return course final grade of a list of students',
        'type' => 'read'
    ),
    'local_integracao_v2_delete_discipline' => array(
        'classname' => 'local_wsintegracao_v2_discipline',
        'methodname' => 'remove_discipline',
        'classpath' => 'local/integracao_v2/classes/discipline.php',
        'description' => 'Delete a discipline',
        'type' => 'write'
    ),
    'local_integracao_v2_change_teacher' => array(
        'classname' => 'local_wsintegracao_v2_teacher',
        'methodname' => 'change_teacher',
        'classpath' => 'local/integracao_v2/classes/teacher.php',
        'description' => 'Changes the teacher of a discipline',
        'type' => 'write'
    ),
    'local_integracao_v2_ping' => array(
        'classname' => 'local_wsintegracao_v2_ping',
        'methodname' => 'ping',
        'classpath' => 'local/integracao_v2/classes/ping.php',
        'description' => 'Ping function',
        'type' => 'read'
    ),
    'local_integracao_v2_get_user' => array(
        'classname' => 'local_wsintegracao_v2_user',
        'methodname' => 'get_user',
        'classpath' => 'local/integracao_v2/classes/user.php',
        'description' => 'Get a user',
        'type' => 'read'
    ),
    'local_integracao_v2_map_user' => array(
        'classname' => 'local_wsintegracao_v2_user',
        'methodname' => 'map_user',
        'classpath' => 'local/integracao_v2/classes/user.php',
        'description' => 'Map a user',
        'type' => 'write'
    ),
);

$services = array(
    'Integracao_v2' => array(
        'functions' => array(
            'local_integracao_v2_create_course',
            'local_integracao_v2_delete_course',
            'local_integracao_v2_create_group',
            'local_integracao_v2_update_group',
            'local_integracao_v2_delete_group',
            'local_integracao_v2_create_discipline',
            'local_integracao_v2_change_student_group',
            'local_integracao_v2_unenrol_student',
            'local_integracao_v2_enrol_student_discipline',
            'local_integracao_v2_unenrol_student_group',
            'local_integracao_v2_unenrol_student_discipline',
            'local_integracao_v2_batch_enrol_student_discipline',
            'local_integracao_v2_batch_unenrol_student_discipline',
            'local_integracao_v2_change_teacher',
            'local_integracao_v2_change_role_student_course',
            'local_integracao_v2_enrol_student',
            'local_integracao_v2_enrol_tutor',
            'local_integracao_v2_delete_discipline',
            'local_integracao_v2_unenrol_tutor_group',
            'local_integracao_v2_get_grades_batch',
            'local_integracao_v2_get_course_grades_batch',
            'local_integracao_v2_update_user',
            'local_integracao_v2_ping',
            'local_integracao_v2_map_user',
            'local_integracao_v2_get_user',
        ),
        'restrictedusers' => 0,
        'enabled' => 1
    )
);
