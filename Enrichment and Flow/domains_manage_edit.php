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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\EnrichmentandFlow\Domain\DomainGateway;
use Gibbon\FileUploader;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/domains_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfDomainID = $_GET['enfDomainID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Domains'), 'domains_manage.php')
        ->add(__m('Edit Domain'));

    if (empty($enfDomainID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(domainGateway::class)->getByID($enfDomainID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('category', $session->get('absoluteURL').'/modules/'.$session->get('module').'/domains_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfDomainID', $enfDomainID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(50);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description');

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addLabel('backgroundColour', __('Background Colour'))->description(__('RGB Hex value, without leading #.'));
        $row->addTextField('backgroundColour')->maxLength(6);

    $row = $form->addRow();
        $row->addLabel('accentColour', __('Accent Colour'))->description(__('RGB Hex value, without leading #.'));
        $row->addTextField('accentColour')->maxLength(6);

    $fileUploader = new FileUploader($pdo, $session);
    $row = $form->addRow();
        $row->addLabel('file', __('Logo'));
        $row->addFileUpload('file')
            ->setAttachment('logo', $session->get('absoluteURL'), $values['logo'])
            ->accepts($fileUploader->getFileExtensions('Graphics/Design'));

    $row = $form->addRow();
        $row->addLabel('creditLicensing', __m('Logo Credits & Licensing'));
        $row->addTextArea('creditLicensing');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
