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

/**
 * User picker for the dashboard's quick-login shortcut (SPEC section 5).
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * A single autocomplete field plus submit button.
 *
 * The search itself is core's: the 'core_user/form_user_selector' AJAX
 * handler behind core's own core_user_search_identity web service, the same
 * one core's webservice token form and report builder audiences use. No
 * bespoke user-search endpoint is introduced by this plugin - which also
 * means the search inherits core's identity-field and capability handling
 * rather than reimplementing it.
 */
class loginas_form extends \moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement(
            'autocomplete',
            'loginasuser',
            get_string('loginas_label', 'local_admincockpit'),
            [],
            [
                'multiple' => false,
                'ajax' => 'core_user/form_user_selector',
                'noselectionstring' => get_string('loginas_noselection', 'local_admincockpit'),
                'valuehtmlcallback' => static function ($userid) {
                    global $OUTPUT;

                    $context = \core\context\system::instance();
                    $fields = \core_user\fields::for_name()->with_identity($context, false);
                    $record = \core_user::get_user($userid, 'id ' . $fields->get_sql()->selects, MUST_EXIST);

                    $user = (object) [
                        'id' => $record->id,
                        'fullname' => fullname($record, has_capability('moodle/site:viewfullnames', $context)),
                        'extrafields' => [],
                    ];
                    foreach ($fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]) as $extrafield) {
                        $user->extrafields[] = (object) [
                            'name' => $extrafield,
                            'value' => s($record->$extrafield),
                        ];
                    }

                    return $OUTPUT->render_from_template('core_user/form_user_selector_suggestion', $user);
                },
            ]
        );
        $mform->setType('loginasuser', PARAM_INT);

        // Marks the request as this form's, so index.php can tell it apart from the other
        // quick actions posting to the same page.
        $mform->addElement('hidden', 'loginassubmitted', 1);
        $mform->setType('loginassubmitted', PARAM_INT);

        $mform->addElement('submit', 'submitbutton', get_string('loginas_submit', 'local_admincockpit'));
    }

    /**
     * Server-side validation: a user must actually have been picked.
     *
     * The real authorisation decision is not made here but in
     * \local_admincockpit\loginas_guard, after the form returns - validation
     * only guards against an empty submission.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (empty($data['loginasuser'])) {
            $errors['loginasuser'] = get_string('loginas_nouserselected', 'local_admincockpit');
        }

        return $errors;
    }
}
