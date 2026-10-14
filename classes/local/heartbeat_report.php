<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * This file defines the quiz_heartbeat report class.
 *
 * @package   quiz_heartbeat
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_heartbeat\local;

use core\dml\sql_join;
use stdClass;

/**
 * Quiz report subclass for the quiz_heartbeat report.
 *
 * This report allows you to download text responses and file attachments submitted
 * by students as a response to quiz essay questions.
 *
 * @package   quiz_heartbeat
 * @copyright 2024 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class heartbeat_report extends \mod_quiz\local\reports\attempts_report {
    /** @var object course object */
    protected object $course;

    /** @var object course module object */
    protected object $cm;

    /** @var object quiz object */
    protected object $quiz;

    /** @var heartbeat_options options for the report */
    protected heartbeat_options $options;

    /** @var array attempt and user data */
    protected array $attempts;

    /** @var int id of the currently selected group */
    protected int $currentgroup;

    /**
     * Override the parent function, because we have some custom stuff to initialise.
     *
     * @param string $mode
     * @param string $formclass
     * @param stdClass $quiz
     * @param stdClass $cm
     * @param stdClass $course
     * @return array with four elements:
     *      0 => integer the current group id (0 for none).
     *      1 => \core\dml\sql_join Contains joins, wheres, params for all the students in this course.
     *      2 => \core\dml\sql_join Contains joins, wheres, params for all the students in the current group.
     *      3 => \core\dml\sql_join Contains joins, wheres, params for all the students to show in the report.
     *              Will be the same as either element 1 or 2.
     */
    public function init($mode, $formclass, $quiz, $cm, $course): array {
        global $DB;

        // First, we call the parent init function...
        [$currentgroup, $allstudentsjoins, $groupstudentsjoins, $allowedjoins] =
            parent::init($mode, $formclass, $quiz, $cm, $course);

        $this->options = new heartbeat_options('heartbeat', $quiz, $cm, $course);
        $this->options->process_settings_from_params();
        if ($fromform = $this->form->get_data()) {
            $this->options->process_settings_from_form($fromform);
        }
        $this->form->set_data($this->options->get_initial_form_data());

        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->currentgroup = $currentgroup;

        $this->hasgroupstudents = false;
        if (!empty($groupstudentsjoins->joins)) {
            $sql = "SELECT DISTINCT u.id
                               FROM {user} u
                                    {$groupstudentsjoins->joins}
                              WHERE {$groupstudentsjoins->wheres}";
            $this->hasgroupstudents = $DB->record_exists_sql($sql, $groupstudentsjoins->params);
        }

        return [$currentgroup, $allstudentsjoins, $groupstudentsjoins, $allowedjoins];
    }

    /**
     * Display the form or, if the "Download" button has been pressed, invoke
     * preparation and shipping of the ZIP archive.
     *
     * @param stdClass $quiz this quiz.
     * @param stdClass $cm the course-module for this quiz.
     * @param stdClass $course the coures we are in.
     */
    public function display($quiz, $cm, $course) {
        $this->init('heartbeat', 'quiz_heartbeat\form\heartbeat_form', $quiz, $cm, $course);

        $this->display_form();
        $this->display_table();
    }

    /**
     * Display the settings form with the download button. May display an error notification, e. g.
     * if there are no attempts or if we already know that there are no essay questions.
     *
     * @return void
     */
    protected function display_form(): void {
        // Printing the standard header. We'll set $hasquestions and $hasstudents to true here,
        // because we do not need a specific notification when there are no questions or no students.
        // The default message "Nothing to display" will be enough.
        $this->print_standard_header_and_messages(
            $this->cm,
            $this->course,
            $this->quiz,
            $this->options,
            $this->currentgroup,
            true,
            true,
        );

        $this->form->display();
    }

    // FIXME
    public function setup_report_data(stdClass $quiz, \cm_info $cm, stdClass $course, ?\context $context = null): array {
        if (!is_null($context)) {
            $this->context = $context;
        }

        $options = new heartbeat_options('heartbeat', $quiz, $cm, $course);
        [$currentgroup, $allstudentsjoins, $groupstudentsjoins, $allowedjoins] = $this->get_students_joins(
            $cm,
            $course
        );

        if (!is_null($this->form)) {
            if ($fromform = $this->form->get_data()) {
                $options->process_settings_from_form($fromform);
            } else {
                $options->process_settings_from_params();
            }
        }

        $table = new heartbeat_table(
            'heartbeat',
            $quiz,
            $this->context,
            $options,
            $groupstudentsjoins,
            $allstudentsjoins,
            $options->get_url()
        );

        return [$options, $table, $allowedjoins];
    }

    /**
     * Build the report using a flexible_table.
     *
     * @return void
     */
    protected function display_table(): void {
        global $PAGE;

        [$currentgroup, $allstudentsjoins, $groupstudentsjoins, $allowedjoins] = $this->get_students_joins(
            $this->cm,
            $this->course,
        );
        $newtable = new heartbeat_table(
            'heartbeatoverview',
            $this->quiz,
            $this->context,
            $this->options,
            $allstudentsjoins,
            $groupstudentsjoins,
            $PAGE->url,
        );
        $newtable->set_attribute('id', 'heartbeatoverview');
        $newtable->setup();
        $newtable->setup_sql_queries($allowedjoins);

        $uselegacyinitialsbar = !method_exists($this, 'print_action_bar');
        $newtable->out(0, $uselegacyinitialsbar);
    }
}
