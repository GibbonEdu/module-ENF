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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\EnrichmentandFlow\Domain\AnnouncementGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/announcements_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfAnnouncementID = $_GET['enfAnnouncementID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Announcements'), 'announcements_manage.php')
        ->add(__m('Edit Announcement'));

    if (empty($enfAnnouncementID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(AnnouncementGateway::class)->getByID($enfAnnouncementID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('category', $session->get('absoluteURL').'/modules/'.$session->get('module').'/announcements_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfAnnouncementID', $enfAnnouncementID);

    $row = $form->addRow();
    $row->addLabel('date', __('Date'));
    $row->addDate('date')->readonly();

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('contentBlock', __('Content'));
        $column->addEditor('content', $guid)->setRows(15)->showMedia()->required()->setID('contentBlock');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
