<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Forms\Form;
use Gibbon\Module\EnrichmentandFlow\View\StudentPlannerView;
use Gibbon\Module\EnrichmentandFlow\View\StaffPlannerView;
use Gibbon\Module\EnrichmentandFlow\Domain\AnnouncementGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\DailyPlannerGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/planner.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Enrichment and Flow/planner.php', $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $gibbonCourseClassID = $_REQUEST['gibbonCourseClassID'] ?? null;
    $date = !empty($_GET['date'])? Format::dateConvert($_GET['date']) : date('Y-m-d');
    $announcement = $container->get(AnnouncementGateway::class)->getAnnouncementByDate($date);

    // Date selector form
    $form = Form::create('dateSelect', $session->get('absoluteURL').'/index.php?q=/modules/Enrichment and Flow/planner.php', 'get');
    $form->setClass('blank w-full');
    $form->addHiddenValue('q', $session->get('address'));

    $row = $form->addRow()->addClass('flex flex-wrap');
    if (!empty($announcement)) {
        $row->addContent('<h3>'.__m('Announcements').'</h3>');
    }

    $col = $row->addColumn()->addClass('flex items-center justify-end');

    // Display class selector
    if ($highestAction == 'Planner Overview') {
        $classes = $container->get(DailyPlannerGateway::class)->selectAllENFClasses($session->get('gibbonSchoolYearID'))->fetchKeyPair();
        $col->addSelect('gibbonCourseClassID')->fromArray($classes)->setClass('shortWidth mr-1')->selected($gibbonCourseClassID)->placeholder('[ '.__m('Select a Class').' ]');
    }

    $col->addDate('date')->setValue(Format::date($date))->setClass('shortWidth');
    $col->addSubmit(__('Go'));

    $page->write($form->getOutput());

    // Announcements
    if (!empty($announcement)) {
        $announcement['content'] = trim(preg_replace('/^<p>|<\/p>$/i', '', $announcement['content']));
        $page->write('<div class="relative bg-gray-100 rounded text-gray-800 text-sm leading-snug border p-4 font-sans">'.$announcement['content']. '</div>');
    }

    // Display the ENF dashboards
    if ($highestAction == 'Planner Overview') {
        $page->breadcrumbs->add(__m('Planner Overview').' ('.Format::date($date).')');
        $planner = $container->get(StaffPlannerView::class)->setDate($date)->setClass($gibbonCourseClassID)->compose($page);

    } elseif ($highestAction == 'Plan & Log') {
        $page->breadcrumbs->add(__m('Plan & Log').' ('.Format::date($date).')');
        $planner = $container->get(StudentPlannerView::class)->setDate($date)->compose($page);
    }
}
