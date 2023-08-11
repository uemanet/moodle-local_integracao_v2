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
 * @package integracao_v2
 * @copyright 2020 Pedro Fellipe Melo
 * @author Uemanet
 */
class local_wsintegracao_v2_ping extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function ping_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @return array
     */
    public static function ping() {
        return array('response' => true);
    }

    /**
     * @return external_function_parameters
     */
    public static function ping_returns() {
        return new external_function_parameters(array(
            'response' => new external_value(PARAM_BOOL, 'Default response')
        ));
    }
}
