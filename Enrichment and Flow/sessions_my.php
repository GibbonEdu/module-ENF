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

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;
use Gibbon\Module\EnrichmentandFlow\Forms\DateSelectForm;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Module\EnrichmentandFlow\ENFFormat;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('My Sessions'));

    // Date selector
    $date = !empty($_GET['date'])? Format::dateConvert($_GET['date']) : date('Y-m-d');
    $url = Url::fromModuleRoute('Enrichment and Flow', 'sessions_my');
    $page->write($container->get(DateSelectForm::class)->createForm($url, $date)->getOutput());

    $blockGateway = $container->get(BlockGateway::class);
    $attendanceGateway = $container->get(AttendanceLogPersonGateway::class);
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);

    $blocks = $blockGateway->selectBlocks()->fetchAll();
    if (empty($blocks)) {
        $page->write($page->getBlankSlate(__m('There are no ENF sessions running at this time.')));
        return;
    }

    foreach ($blocks as $block) {

        $sessions = $plannedSessionGateway->selectPlannedSessionsByTeacher($block['enfBlockID'], $session->get('gibbonPersonID'))->fetchAll();
        $sessions = array_map(function ($values) use ($plannedSessionTeacherGateway, $sessionStudentGateway, $date) {
            $values['teachers'] = $plannedSessionTeacherGateway->selectTeachersByPlannedSession($values['enfPlannedSessionID'], $date)->fetchAll();
            $values['students'] = $sessionStudentGateway->selectStudentsByPlannedSessionAndDate($values['enfPlannedSessionID'], $date)->fetchAll();
            $values['studentCount'] = count($values['students']);
            return $values;
        }, $sessions);

        $table = DataTable::create('plannedSessions');
        $table->setTitle($block['name']);
        $table->setDescription($block['weekday'] .' ('.Format::timeRange($block['timeStart'], $block['timeEnd']).')');
        $table->addMetaData('blankSlate', __m('You do not have a session yet, click to add one'));
        
        if (empty($sessions)) {
            $table->addHeaderAction('add', __('Run a Session'))
                ->setURL(Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_addEdit')->withQueryParams(['enfBlockID' => $block['enfBlockID']]));

            $table->addHeaderAction('join', __('Join a Session'))
                ->setURL(Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_join')->withQueryParams(['enfBlockID' => $block['enfBlockID']]))
                ->setIcon('users');
        }

        $table->addColumn('facility', __('Location'))->width('10%');

        $table->addColumn('focus', __('Focus'))->width('25%');

        $table->addColumn('type', __('Type'))
            ->width('15%')
            ->format(function($values){
                return ENFFormat::sessionTag($values['type']);
            });

        $table->addColumn('studentCount', __('Students'))
            ->width('10%')
            ->format(function($values) use (&$page) {
                return $page->fetchFromTemplate('ui/progress.twig.html', [
                    'progressCount'  => $values['studentCount'],
                    'totalCount'     => $values['maxStudents'],
                    'leftCount'      => $values['maxStudents'] - $values['studentCount'],
                    'progressLabel'  => __('Full'),
                    'width'          => 'w-32',
                    'progressColour' => 'blue',
                    'title'          => __('Students'),
                ]);
            });

        $table->addColumn('teachers', __('Teachers'))
            ->format(function($values){
                return !empty($values['teachers'])
                    ? Format::nameList($values['teachers'], 'Staff', false, true)
                    : __('None');
            });

        // ACTIONS
        $table->addActionColumn()
        ->addParam('enfBlockID', $block['enfBlockID'])
        ->addParam('enfPlannedSessionID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Enrichment and Flow/sessions_my_addEdit.php');

            if (!empty($values['teachers']) && count($values['teachers']) > 1) {
                $actions->addAction('delete', __('Leave Session'))
                    ->setURL('/modules/Enrichment and Flow/sessions_my_delete.php')
                    ->setIcon('user-minus');
            } elseif ($values['studentCount'] == 0) {
                $actions->addAction('delete', __('Cancel Session'))
                    ->setURL('/modules/Enrichment and Flow/sessions_my_delete.php')
                    ->setIcon('cross');
            }
            
        });

        $page->write($table->render($sessions));

        if (!empty($sessions[0]['students'])) {
            $discussion = [];

            foreach ($sessions[0]['students'] as $student) {
                $url = Url::fromModuleRoute('Enrichment and Flow', 'planner_view.php')->withQueryParams(['gibbonPersonID' => $student['gibbonPersonID']]);
                $attendance = $attendanceGateway->selectAttendanceLogsByPersonAndDate($student['gibbonPersonID'], $this->date, 'N');
                $log = ($attendance->rowCount() > 0) ? $attendance->fetch() : [];
                $isAbsent = !empty($log) && ($log['direction'] == 'Out' || $log['scope'] == 'Offsite');

                $menu = $page->fetchFromTemplate('plannerMenu.twig.html', [
                    'gibbonPersonID'      => $student['gibbonPersonID'],
                    'enfSessionStudentID' => $student['enfSessionStudentID'],
                    'enfPlannedSessionID' => $student['enfPlannedSessionID'],
                    'enfPlannerEntryID'   => $student['enfPlannerEntryID'],
                    'status'              => $student['status'],
                ]);

                $discussion[] = [
                    'surname'       => $student['surname'],
                    'preferredName' => $student['preferredName'],
                    'image_240'     => $student['image_240'],
                    'type'          => $student['status'] != 'Present' ? __($student['status']) : '',
                    'tag'           => $student['status'] != 'Present' ? ($student['status'] == 'Missing' ? 'error' : 'message') : '',
                    'url'           => $url,
                    'label'         => $student['formGroup'],
                    'comment'       => $student['comment'],
                    'timestamp'     => $student['timestampModified'],
                    'extra'         => $menu,
                ];

            }

            $page->writeFromTemplate('ui/discussion.twig.html', [
                'compact'    => true,
                'discussion' => $discussion,
            ]);

        } else {
            $page->write($page->getBlankSlate(__m('There are no students signed up for the selected date.')));
        }

        $page->write('<br>');
    }
}
