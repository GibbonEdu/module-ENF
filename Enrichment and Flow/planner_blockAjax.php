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

use Gibbon\Services\Format;
use Gibbon\Forms\FormFactory;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;
use Gibbon\Module\EnrichmentandFlow\ENFFormat;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/planner_view.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $enfPlannedSessionIDs = $_GET['enfPlannedSessionID'] ?? [];
    $enfPlannedSessionIDs = is_array($enfPlannedSessionIDs) ? array_filter($enfPlannedSessionIDs) : [$enfPlannedSessionIDs];
    $date = $_GET['date'] ?? '';
    
    if (empty($enfPlannedSessionIDs) || empty($date)) die();

    $formFactory = $container->get(FormFactory::class);
    $sessionStudentGateway = $container->get(SessionStudentGateway::class);
    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);
    $plannedSessionTeacherGateway = $container->get(PlannedSessionTeacherGateway::class);

    $output = '';

    foreach ($enfPlannedSessionIDs as $enfPlannedSessionID) {
        $sessionDetails = $plannedSessionGateway->getSessionDetailsByID($enfPlannedSessionID);
        if (empty($sessionDetails)) die();
        
        $students = $sessionStudentGateway->selectStudentsByPlannedSessionAndDate($enfPlannedSessionID, $date)->fetchAll();
        $teachers = $plannedSessionTeacherGateway->selectTeachersByPlannedSession($enfPlannedSessionID)->fetchAll();
        $teachers = Format::nameList($teachers, 'Staff', false, false, ', ');

        $section = $formFactory->createRow()->setClass('w-full mt-4 text-sm');

        $row = $section->addRow()->setClass('flex items-center');
        $row->addLabel('teachers', __('Teacher(s)'))->setClass('w-40 font-medium my-0 text-base/6 sm:text-sm/6 text-gray-800');
        $row->addContent($teachers)->wrap('<div class="flex-1">', '</div>');

        $progress = $page->fetchFromTemplate('ui/progress.twig.html', [
            'progressCount'  => count($students),
            'totalCount'     => $sessionDetails['maxStudents'],
            'leftCount'      => $sessionDetails['maxStudents'] - count($students),
            'progressLabel'  => __('Full'),
            'width'          => 'w-32',
            'progressColour' => 'blue',
            'title'          => __('Students'),
        ]);
        $row->addLabel('students', __('Students'))->setClass('w-24 font-medium my-0 text-base/6 sm:text-sm/6 text-gray-800');
        $row->addContent($progress)->wrap('<div class="w-64 mb-1">', '</div>');

        $row->addLabel('type', __('Type'))->setClass('w-24 font-medium my-0 text-base/6 sm:text-sm/6 text-gray-800');
        $row->addContent(ENFFormat::sessionTag($sessionDetails['type']))->wrap('<div class="w-24 text-right">', '</div>');

        if (!empty($sessionDetails['notes'])) {
            $row = $section->addRow();
            $row->addContent($sessionDetails['notes'])->wrap('<div class="w-full mt-4">', '</div>');
        }

        $output .= $section->getOutput();
    }

    echo $output;
}
