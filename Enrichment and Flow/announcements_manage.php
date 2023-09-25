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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Module\EnrichmentandFlow\Domain\AnnouncementGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/announcements_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Announcements'));

    // Query categories
    $announcementGateway = $container->get(AnnouncementGateway::class);

    $criteria = $announcementGateway->newQueryCriteria(true)
        ->sortBy('date', 'DESC')
        ->fromPOST();

    $announcements = $announcementGateway->queryAnnouncements($criteria);

    // Render table
    $table = DataTable::createPaginated('announcements', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Enrichment and Flow/announcements_manage_add.php')
        ->displayLabel();

    $table->addExpandableColumn('content');

    $table->addColumn('date', __('Date'))->format(Format::using('dateReadable', 'date'));

    $table->addColumn('gibbonPersonIDCreated', __('Created By'))
        ->notSortable()
        ->format(function($values) {
            return Format::name('', $values['preferredName'], $values['surname'], 'Staff', false, true);
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('enfAnnouncementID')
        ->format(function ($category, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Enrichment and Flow/announcements_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Enrichment and Flow/announcements_manage_delete.php');
        });

    echo $table->render($announcements);
}
