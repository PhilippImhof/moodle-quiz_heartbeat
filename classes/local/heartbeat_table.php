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
 * This file defines the data table for the heartbeat quiz report report.
 *
 * @package   quiz_heartbeat
 * @copyright 2026 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_heartbeat\local;

use stdClass;
use core\dml\sql_join;

/**
 * Class to build the table for the {@see quiz_heartbeat_report}.
 *
 * @package   quiz_heartbeat
 * @copyright 2026 Philipp E. Imhof
 * @author    Philipp E. Imhof
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class heartbeat_table extends \table_sql {
    public $useridfield = 'userid';

    /** @var \moodle_url the URL of this report. */
    protected $reporturl;

    /** @var stdClass the quiz settings for the quiz we are reporting on. */
    protected $quiz;

    /** @var \context_module the quiz context. */
    protected $context;

    /** @var heartbeat_options the options affecting this report. */
    protected $options;

    /** @var \core\dml\sql_join Contains joins, wheres, params to find students
     * in the currently selected group, if applicable.
     */
    protected $groupstudentsjoins;

    protected $currenttime;

    /** @var \core\dml\sql_join Contains joins, wheres, params to find the students in the course. */
    protected $allstudentsjoins;

    // FIXME
    public function __construct($uniqueid, $quiz, $context,
            heartbeat_options $options, sql_join $groupstudentsjoins, sql_join $allstudentsjoins,
            $url) {

        parent::__construct($uniqueid);
        $this->quiz = $quiz;
        $this->context = $context;
        $this->options = $options;
        $this->groupstudentsjoins = $groupstudentsjoins;
        $this->allstudentsjoins = $allstudentsjoins;
        $this->baseurl = $url;

        $this->define_columns(['fullname', 'timecreated', 'type']);
        $this->define_headers([
            '',
            get_string('timeelapsed', 'quiz_heartbeat'),
            get_string('type', 'quiz_heartbeat')
        ]);
        $this->sortable(true, 'timecreated', SORT_ASC);
        $this->no_sorting('type');
    }

    public function col_fullname($row) {
        return fullname($row);
    }

    public function col_timecreated($row) {
        return format_time($this->currenttime - $row->timecreated);
    }

    public function col_type($row) {
        $type = get_string('manualsave', 'quiz_heartbeat');
        if ($row->sequencenumber === '0') {
            $type = get_string('started', 'quiz_heartbeat');
        } else if ($row->sequencenumber[0] === '-') {
            $type = get_string('automaticsave', 'quiz_heartbeat');
        }

        return $type;
    }

    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        $this->currenttime = time();
        parent::out($pagesize, $useinitialsbar);
    }

    public function get_sql_where() {
        return ['', []];
    }

    public function get_sql_sort() {
        // Construct the sorting criterion. By default, we sort by the last name and first name.
        // For the elapsed time, we need to inverse the sort order, because the actual value from
        // the DB is the timestamp and smaller timestamps mean more time has elapsed.
        if ($this->options->tsort === 'time') {
            $sortdir = ($this->options->tdir == SORT_DESC ? 'ASC' : 'DESC');
        } else {
            $sortdir = ($this->options->tdir == SORT_ASC ? 'ASC' : 'DESC');
        }
        switch ($this->options->tsort) {
            case 'lastname':
                return "u.lastname $sortdir, u.firstname $sortdir";
            case 'firstname':
                return "u.firstname $sortdir, u.lastname $sortdir";
            case 'time':
            default:
                return "timecreated $sortdir";
        }
    }

    /**
     * FIXME Set up the SQL queries (count rows, and get data).
     *
     * @param sql_join $joins (joins, wheres, params) defines allowed users for the report.
     */
    public function setup_sql_queries($joins) {
        global $DB;

        $dbrand = self::db_random();

        $fields = "
            $dbrand,
            MAX(st.sequencenumber) AS sequencenumber,
            MAX(st.timecreated) AS timecreated,
            u.id AS userid,
            u.firstname,
            u.lastname,
            u.firstnamephonetic,
            u.lastnamephonetic,
            u.middlename,
            u.alternatename";

        $from = "
            {question_attempt_steps} st
            JOIN {user} u on u.id = st.userid
            JOIN {question_attempts} qa on st.questionattemptid = qa.id
            JOIN {quiz_attempts} a on a.uniqueid = qa.questionusageid
            $joins->joins";

        // Parameters for the SQL query will include the quiz' ID for sure.
        $params = ['iquizid' => $this->quiz->id, 'quizid' => $this->quiz->id];

        // The user may choose to filter the table by the initial of the first and/or last name.
        // We use 'AND' at the start of the condition, because we place it after other conditions.
        $nameconditions = '';
        if (!empty($this->options->firstinitial) && preg_match('/[A-Z]/i', $this->options->firstinitial)) {
            $nameconditions .= 'AND ' . $DB->sql_like('u.firstname', ':initialfirstname', false);
            $params = $params + ['initialfirstname' => $this->options->firstinitial . '%'];
        }
        if (!empty($this->options->lastinitial) && preg_match('/[A-Z]/i', $this->options->lastinitial)) {
            $nameconditions .= 'AND ' . $DB->sql_like('u.lastname', ':initiallastname', false);
            $params = $params + ['initiallastname' => $this->options->lastinitial . '%'];
        }

        // FIXME: The initials filter is stored in $SESSION rather than passed as a parameter, to match
        // standard Moodle behaviour (e.g. the gradebook and participants list initials bars), so
        // the filter persists across requests (including this table being rebuilt for an AJAX
        // user-search request) without needing to be repeated in every URL.
        global $SESSION;
        $filterfirstnamekey = 'filterfirstname-' . $this->context->id;
        $filtersurnamekey = 'filtersurname-' . $this->context->id;
        if (!empty($SESSION->heartbeatreport[$filterfirstnamekey])) {
            $nameconditions .= 'AND ' . $DB->sql_like('u.firstname', ':sinitialfirstname', false);
            $params = $params + ['sinitialfirstname' => $SESSION->heartbeatreport[$filterfirstnamekey] . '%'];
        }
        if (!empty($SESSION->heartbeatreport[$filtersurnamekey])) {
            $nameconditions .= 'AND ' . $DB->sql_like('u.lastname', ':sinitiallastname', false);
            $params = $params + ['sinitiallastname' => $SESSION->heartbeatreport[$filtersurnamekey] . '%'];
        }

        // FIXME: filter for userid
        $useridcondition = '';
        if ($this->options->usersearch !== '' && $this->options->userid !== -1) {
            $useridcondition = 'AND ' . $DB->sql_equal('u.id', ':userid');
            $params = $params + ['userid' => $this->options->userid];
        }

        // If there are no WHERE clauses (i. e. because no group has been selected), we add a dummy
        // clause to simplify the syntax of the query.
        if (empty($joins->wheres)) {
            $joins->wheres = '1 = 1';
        }

        $where = "
            $joins->wheres
            $nameconditions
            $useridcondition
            AND a.quiz = :quizid AND a.state = 'inprogress'
            AND st.timecreated in (
                SELECT MAX(ist.timecreated)
                        FROM {question_attempt_steps} ist
                        JOIN {question_attempts} iqa on ist.questionattemptid = iqa.id
                        JOIN {quiz_attempts} ia on ia.uniqueid = iqa.questionusageid
                       WHERE ia.quiz = :iquizid AND ia.state = 'inprogress'
                    GROUP BY ist.userid
                )
            GROUP BY st.userid,
                     u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename";

        // FIXME: sorting!

        $params = $params + $joins->params;
        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM (SELECT $fields FROM $from WHERE $where) AS grouped", $params);
    }

    /**
     * Return a command that can be used in an SQL query to generate a column with unique values.
     * The command depends on the DB engine in use.
     *
     * @return string
     */
    private static function db_random(): string {
        global $DB;

        switch ($DB->get_dbfamily()) {
            case 'mssql':
                return 'NEWID()';
            case 'mysql':
                return 'UUID_SHORT()';
            case 'postgres':
                return 'RANDOM()';
            case 'oracle':
                return 'dbms_random.value';
            default:
                return 'RAND()';
        }
    }

}
