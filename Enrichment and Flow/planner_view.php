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
use Gibbon\Module\EnrichmentandFlow\Domain\DailyPlannerGateway;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/planner_view.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Enrichment and Flow/planner.php', $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $page->breadcrumbs->add(__m('Planner Overview'), 'planner.php')->add(__m('View All'));

    $dailyPlannerGateway = $container->get(DailyPlannerGateway::class);
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    // Get current date and role category
    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');
    $date = !empty($_GET['date'])? Format::dateConvert($_GET['date']) : date('Y-m-d');

    if ($highestAction == 'Plan & Log' || $roleCategory == 'Student') {
        $gibbonPersonID = $session->get('gibbonPersonID');
    } else if ($highestAction == 'Planner Overview') {
        $gibbonPersonID = $_REQUEST['gibbonPersonID'] ?? '';
    }

    if (empty($gibbonPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $class = $dailyPlannerGateway->getENFClassByPerson($gibbonSchoolYearID, $gibbonPersonID);
    $teachers = $dailyPlannerGateway->selectENFTeachersByStudent($gibbonSchoolYearID, $gibbonPersonID)->fetchAll();

    $categories = $container->get(SettingGateway::class)->getSettingByScope('Enrichment and Flow', 'taskCategories');
    $categories = json_decode($categories, true);
    $categories = array_combine(array_column($categories, 'category'), array_column($categories, 'color'));

    $page->write('<h3>'.Format::name('', $class['preferredName'], $class['surname'], 'Student', false, true).'</h3>');

    $plannerEntries = $dailyPlannerGateway->selectPlannerEntriesByStudent($gibbonPersonID)->fetchGrouped();

    foreach ($plannerEntries as $date => $discussion) {
        $plannerEntry = current($discussion);
        $taskCode = '';

        if (!empty($plannerEntry['enfPlannerEntryID'])) {
            $tasks = $dailyPlannerGateway->selectPlannerTasksByEntry($plannerEntry['enfPlannerEntryID'])->fetchAll();

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

        $page->write('<div class="flex items-end justify-between w-full"><h4 class="mb-0 pb-0">'.Format::dateReadable($plannerEntry['date']).'</h4>'.$taskCode.'</div>');

        $discussion = array_map(function ($item) use ($taskCode) {
            $item['comment'] = Format::hyperlinkAll($item['comment'] ?? '');
            $item['type'] = '';
            return $item;
        }, $discussion);

        $page->writeFromTemplate('ui/discussion.twig.html', [
            'discussion' => $discussion,
        ]);
    }

    if (empty($plannerEntries)) {
        $page->writeFromTemplate('ui/discussion.twig.html', [
            'blankSlate' => __m('There is nothing here yet.'),
        ]);
    }
}
