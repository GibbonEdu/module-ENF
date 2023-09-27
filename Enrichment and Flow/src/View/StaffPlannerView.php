<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\EnrichmentAndFlow\View;

use Gibbon\View\Page;
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Module\EnrichmentandFlow\Domain\DailyPlannerGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\JourneyGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Http\Url;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\System\SettingGateway;

/**
 * StaffPlannerView
 *
 * A view composer class
 *
 * @version v1.1.00
 * @since   v1.1.00
 */
class StaffPlannerView
{

    protected $session;
    protected $settingGateway;
    protected $dailyPlannerGateway;
    protected $journeyGateway;
    protected $attendanceGateway;

    protected $date;
    protected $gibbonCourseClassID;

    public function __construct(Session $session, SettingGateway $settingGateway, DailyPlannerGateway $dailyPlannerGateway, JourneyGateway $journeyGateway, AttendanceLogPersonGateway $attendanceGateway)
    {
        $this->session = $session;
        $this->settingGateway = $settingGateway;
        $this->dailyPlannerGateway = $dailyPlannerGateway;
        $this->journeyGateway = $journeyGateway;
        $this->attendanceGateway = $attendanceGateway;
    }

    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function setClass($gibbonCourseClassID)
    {
        $this->gibbonCourseClassID = $gibbonCourseClassID;

        return $this;
    }

    public function compose(Page $page)
    {
        $gibbonSchoolYearID = $this->session->get('gibbonSchoolYearID');
        $gibbonPersonID = $this->session->get('gibbonPersonID');
        $guid = $this->session->get('guid');

        $categories = $this->settingGateway->getSettingByScope('Enrichment and Flow', 'taskCategories');
        $categories = json_decode($categories ?? '', true) ?? [];
        $categories = array_combine(array_column($categories, 'category'), array_column($categories, 'color'));

        if (!empty($this->gibbonCourseClassID)) {
            $students = $this->dailyPlannerGateway->selectENFStudentsByClass($gibbonSchoolYearID, $this->gibbonCourseClassID)->fetchAll();
        } else {
            $students = $this->dailyPlannerGateway->selectENFStudentsByTeacher($gibbonSchoolYearID, $gibbonPersonID)->fetchAll();
        }
        
        $discussion = [];

        foreach ($students as $student) {
            $plannerEntry = $this->dailyPlannerGateway->getPlannerEntryByDate($student['gibbonPersonID'], $this->date);
            $url = Url::fromModuleRoute('Enrichment and Flow', 'planner_view.php')->withQueryParams(['gibbonPersonID' => $student['gibbonPersonID']]);

            $attendance = $this->attendanceGateway->selectAttendanceLogsByPersonAndDate($student['gibbonPersonID'], $this->date, 'N');
            $log = ($attendance->rowCount() > 0) ? $attendance->fetch() : [];
            $isAbsent = !empty($log) && ($log['direction'] == 'Out' || $log['scope'] == 'Offsite');

            if (!empty($plannerEntry['enfPlannerEntryID'])) {
                $tasks = $this->dailyPlannerGateway->selectPlannerTasksByEntry($plannerEntry['enfPlannerEntryID'])->fetchAll();
    
                if (!empty($tasks)) {
                    $minutes = array_sum(array_column($tasks, 'minutes'));
                    $taskCode = $page->fetchFromTemplate('tasks.twig.html', [
                        'tasks' => $tasks,
                        'count' => count($tasks),
                        'minutes' => max($minutes, 140),
                        'totalMinutes' => $minutes,
                        'width' => 'w-64',
                        'categories' => $categories,
                    ]);
                }
            }

            if (!empty($plannerEntry)) {
                // Existing entry
                $discussion[] = [
                    'surname'       => $student['surname'],
                    'preferredName' => $student['preferredName'],
                    'image_240'     => $student['image_240'],
                    'comment'       => $plannerEntry['comment'],
                    'timestamp'     => $plannerEntry['timestamp'],
                    'type'          => __('Complete'),
                    'tag'           => 'success',
                    'url'           => $url,
                    'extra'         => $taskCode ?? '',
                ];
            } else {
                // Missing entry
                $discussion[] = [
                    'surname'       => $student['surname'],
                    'preferredName' => $student['preferredName'],
                    'image_240'     => $student['image_240'],
                    'type'          => !$isAbsent ? __('Incomplete') : __($log['type']),
                    'tag'           => !$isAbsent ? 'error' : 'dull',
                    'url'           => $url,
                ];
            }
        }

        $page->writeFromTemplate('ui/discussion.twig.html', [
            'title' => __m('Student Plans'),
            'compact' => true,
            'discussion' => $discussion,
        ]);

        $page->write('<br/>');

        // JOURNEY
        $criteria = $this->journeyGateway->newQueryCriteria()
            ->sortBy('timestampJoined', 'DESC')
            ->filterBy('active', true)
            ->fromPOST();

        $journey = $this->journeyGateway->selectJourneyByStaff($criteria, $this->session->get('gibbonPersonID'), 'Manage Journey_my');
        
        // Render table
        if ($journey->count() > 0) {
            $table = DataTable::createPaginated('opportunities', $criteria);

            $table->setTitle(__('My Mentorship'));

            $table->modifyRows(function ($journey, $row) {
                $row->addClass('pending');
                return $row;
            });

            $table->addMetaData('hidePagination', true);

            $table->addColumn('type', __('Type'));

            $table->addColumn('logo', __('Logo'))
                ->notSortable()
                ->format(function($values) {
                    $return = null;
                    $return .= ($values['logo'] != '') ? "<img class='user' style='max-width: 75px' src='".$this->session->get('absoluteURL').'/'.$values['logo']."'/>":"<img class='user' style='max-width: 75px' src='".$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/>";
                    return $return;
                });

            $table->addColumn('name', __('Name'));

            $table->addColumn('student', __('Student'))
                ->notSortable()
                ->format(function($values) use ($guid) {
                    return Format::name('', $values['preferredName'], $values['surname'], 'Student', false, true);
                });

            if (!empty($allMentors)) {
                $table->addColumn('mentor', __('Mentor'))
                    ->notSortable()
                    ->format(function($values) use ($guid) {
                        return Format::name($values['mentortitle'], $values['mentorpreferredName'], $values['mentorsurname'], 'Staff', false, true);
                    });
            }

            $table->addColumn('timestampCompletePending', __('Submitted'))
                ->format(function($values) use ($guid) {
                    return Format::date($values['timestampCompletePending']);
                });

            // ACTIONS
            $table->addActionColumn()
                ->addParam('enfJourneyID')
                ->format(function ($category, $actions) {
                    $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Enrichment and Flow/journey_manage_edit.php');
                });

            $page->write($table->render($journey));
        }
    
    }
}
