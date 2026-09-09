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
        ->add(__m('Student Sessions'));

    $blockGateway = $container->get(BlockGateway::class);
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);
  
    // Date selector
    $date = !empty($_GET['date'])? Format::dateConvert($_GET['date']) : date('Y-m-d');
    $url = Url::fromModuleRoute('Enrichment and Flow', 'sessions_my');
    $page->write($container->get(DateSelectForm::class)->createForm($url, $date)->getOutput());

    $blocks = $blockGateway->selectBlocks()->fetchAll();
    $students = $sessionStudentGateway->selectENFStudentsByDate($session->get('gibbonSchoolYearID'), $date)->fetchAll();

    $studentsMissing = [];
    $students = array_reduce($students, function ($group, $item) use (&$studentsMissing) {
        $group[$item['gibbonPersonID']]['gibbonPersonID'] = $item['gibbonPersonID'];
        $group[$item['gibbonPersonID']]['preferredName'] = $item['preferredName'];
        $group[$item['gibbonPersonID']]['surname'] = $item['surname'];
        $group[$item['gibbonPersonID']]['formGroup'] = $item['formGroup'];


        $group[$item['gibbonPersonID']][$item['block']] = [
            'gibbonPersonID'      => $item['gibbonPersonID'] ?? '',
            'enfSessionStudentID' => $item['enfSessionStudentID'] ?? '',
            'focus'               => $item['focus'] ?? '',
            'type'                => $item['type'] ?? '',
            'status'              => $item['status'] ?? '',
            'facility'            => $item['facility'] ?? '',
        ];

        if ($item['status'] == 'Missing') {
            $studentsMissing[$item['gibbonPersonID']] = $item['gibbonPersonID'];
        }
        return $group;
    }, []);


    $table = DataTable::create('students');
    $table->setTitle(__m('Student Sessions'));
    $table->setDescription(__m('Signd Up').': '.Format::tag(count($students), 'empty').' &nbsp; '.__m('Missing').': '.Format::tag(count($studentsMissing), 'error'));

    $table->modifyRows(function ($values, $row) use (&$studentsMissing) {
        if (!empty($studentsMissing[$values['gibbonPersonID']])) $row->addClass('error');
        return $row;
    });

    $table->addColumn('student', __('Student'))
        ->width('25%')
        ->format(function($values){
            $url = Url::fromModuleRoute('Enrichment and Flow', 'planner_view')->withQueryParams(['gibbonPersonID' => $values['gibbonPersonID']]);
            $name = Format::name( '', $values['preferredName'], $values['surname'], 'Student', true, true);
            return Format::link($url, $name);
        });

    $table->addColumn('formGroup', __('Form Group'))->width('12%');

    foreach ($blocks as $block) {
        $table->addColumn('block'.$block['enfBlockID'], $block['name'])
            ->format(function($values) use ($block, &$page) {
                $studentSession = $values[$block['name']] ?? [];
                if (empty($studentSession)) return '';

                $tag = ENFFormat::sessionTag($studentSession['type'], $studentSession['facility'].' - '.$studentSession['focus']);
                $status = $studentSession['status'] != 'Present' 
                    ? Format::tag(__m($studentSession['status']), $studentSession['status'] == 'Missing' ? 'error ml-2' : 'message ml-2') 
                    : '';

                $menu = $page->fetchFromTemplate('plannerMenu.twig.html', [
                    'enfSessionStudentID' => $studentSession['enfSessionStudentID'],
                    'gibbonPersonID'      => $studentSession['gibbonPersonID'],
                    'status'              => $studentSession['status'],
                    'source'              => 'report',
                ]);

                return '<div class="flex items-center">'.$tag . $status. $menu.'</div>';
            });
    }

    $page->write($table->render($students));


    $studentsNotSignedUp = $sessionStudentGateway->selectENFStudentsNotSignedUp($session->get('gibbonSchoolYearID'), $date)->fetchAll();
    $studentsNotPresent = array_reduce($studentsNotSignedUp, function ($group, $item) {
        $group += !empty($item['attendanceStatus']) && $item['attendanceStatus'] != 'Present' && $item['attendanceStatus'] != 'Present - Late' ? 1 : 0;
        return $group;
    }, 0);

    $table = DataTable::create('studentsNotSignedUp');
    $table->setTitle(__m('Not Signed Up'));
    $table->setDescription(__m('Pending').': '.Format::tag(count($studentsNotSignedUp) - $studentsNotPresent, 'empty').' &nbsp; '.__m('Absent').': '.Format::tag($studentsNotPresent, 'error'));

    $table->modifyRows(function ($values, $row) {
        if (!empty($values['attendanceStatus']) && $values['attendanceStatus'] != 'Present' && $values['attendanceStatus'] != 'Present - Late') $row->addClass('bg-stripe');
        return $row;
    });

    $table->addColumn('student', __('Student'))
        ->width('25%')
        ->format(function($values){
            $url = Url::fromModuleRoute('Enrichment and Flow', 'planner_view')->withQueryParams(['gibbonPersonID' => $values['gibbonPersonID']]);
            $name = Format::name( '', $values['preferredName'], $values['surname'], 'Student', true, true);
            return Format::link($url, $name);
        });

    $table->addColumn('formGroup', __('Form Group'))->width('12%');

    $table->addColumn('class', __('Class'))
        ->format(Format::using('courseClassName', ['course', 'class']));

    $table->addColumn('attendanceStatus', __('Attendance'))->width('20%');

    $page->write($table->render($studentsNotSignedUp));
}
