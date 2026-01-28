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
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Support\Facades\Access;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Forms\DateSelectForm;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;
use Gibbon\Module\EnrichmentandFlow\ENFFormat;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('All Sessions'));

    $blockGateway = $container->get(BlockGateway::class);
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);
  
    $canManage = Access::allows('Enrichment and Flow', 'sessions_view', 'All Sessions_manage');

    // Date selector
    $date = !empty($_GET['date'])? Format::dateConvert($_GET['date']) : date('Y-m-d');
    $url = Url::fromModuleRoute('Enrichment and Flow', 'sessions_my');
    $page->write($container->get(DateSelectForm::class)->createForm($url, $date)->getOutput());
    
    $blocks = $blockGateway->selectBlocks()->fetchAll();
    if (empty($blocks)) {
        $page->write($page->getBlankSlate(__m('There are no ENF sessions running at this time.')));
        return;
    }

    foreach ($blocks as $block) {
        $capacity = ['used' => 0, 'available' => 0];

        $sessions = $plannedSessionGateway->selectPlannedSessionsByDate($block['enfBlockID'], $date)->fetchAll();
        $sessions = array_map(function ($values) use (&$plannedSessionTeacherGateway, &$sessionStudentGateway, &$date, &$capacity) {
            $values['teachers'] = $plannedSessionTeacherGateway->selectTeachersByPlannedSession($values['enfPlannedSessionID'])->fetchAll();
            $values['students'] = $sessionStudentGateway->selectStudentsByPlannedSessionAndDate($values['enfPlannedSessionID'], $date)->fetchAll();
            $values['studentCount'] = count($values['students']);
            $capacity['available'] += $values['maxStudents'];
            $capacity['used'] += $values['studentCount'];
            return $values;
        }, $sessions);

        $table = DataTable::create('plannedSessions');
        $table->setTitle($block['name']);
        $table->setDescription(__m('Signed up').': '.Format::tag($capacity['used'], 'empty').' &nbsp;&nbsp; '.__m('Capacity').': '.Format::tag($capacity['available'], 'empty') );
        $table->addMetaData('blankSlate', __m('There are no sessions planned for this time'));

        if ($canManage) {
            $table->addHeaderAction('add', __('Add a Session'))
                ->setURL(Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_addEdit')->withQueryParams(['enfBlockID' => $block['enfBlockID'], 'mode' => 'manage']));
        }

        $table->addColumn('focus', __('Focus'))->width('20%');

        $table->addColumn('type', __('Type'))
            ->width('15%')
            ->format(function($values){
                return ENFFormat::sessionTag($values['type']);
            });

        $table->addColumn('facility', __('Facility'))->width('10%');

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

        if ($canManage) {
            // ACTIONS
            $table->addActionColumn()
            ->addParam('enfBlockID', $block['enfBlockID'])
            ->addParam('date', $date)
            ->addParam('enfPlannedSessionID')
            ->format(function ($values, $actions) {
                $actions->addAction('add', __('Add'))
                    ->setURL('/modules/Enrichment and Flow/sessions_view_addEditStudent.php')
                    ->setIcon('user-plus');
            });
        }

        $page->write($table->render($sessions));
        $page->write('<br>');

    }

    $studentsMissing = $sessionStudentGateway->selectENFStudentsNotSignedUp($session->get('gibbonSchoolYearID'), $date)->toDataSet();

    $table = DataTable::create('students');
    $table->setTitle(__m('Not Signed Up'));
    $table->setDescription(__m('Pending').': '.Format::tag(count($studentsMissing), 'empty'));

    $table->addColumn('student', __('Student'))
        ->format(function($values){
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true);
        });

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('class', __('Class'))
        ->format(Format::using('courseClassName', ['course', 'class']));

    $page->write($table->render($studentsMissing));
}
