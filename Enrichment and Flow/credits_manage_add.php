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
use Gibbon\FileUploader;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\EnrichmentandFlow\Domain\DomainGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/credits_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfDomainID = $_GET['enfDomainID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Credits'), 'credits_manage.php')
        ->add(__m('Add Credit'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Enrichment and Flow/credits_manage_edit.php&enfCreditID='.$_GET['editID']."&enfDomainID=$enfDomainID&search=$search";
    }
    $page->return->setEditLink($editLink);

    if ($enfDomainID != '' || $search !='') {
        $params = [
            "search" => $search,
            "enfDomainID" => $enfDomainID
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Enrichment and Flow', 'credits_manage.php')->withQueryParams($params));
    }

    $form = Form::create('domain', $session->get('absoluteURL').'/modules/'.$session->get('module')."/credits_manage_addProcess.php?enfDomainID=$enfDomainID&search=$search");
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $domainGateway = $container->get(DomainGateway::class);
    $domains = $domainGateway->selectActiveDomains()->fetchKeyPair();

    $row = $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('enfDomainID', __('Domain'))->description(__('Must be unique.'));
        $row->addSelect('enfDomainID')->required()->fromArray($domains)->placeholder();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(50);

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('description', __('Description'));
        $column->addCommentEditor('description')
            ->maxLength(300);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow()->addHeading(__m('Mentorship & Completion'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Mentor'))->description(__m('Which staff can be selected as a mentor for this credit?'));
        $row->addSelectStaff('gibbonPersonID')->selectMultiple();

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('outcomes', __m('Indicative Outcomes & Criteria'))->description('How can students and mentor judge progress towards completion?');
        $column->addEditor('outcomes', $guid)->setRows(15)->showMedia();

    $row = $form->addRow()->addHeading(__('Logo'));

    $fileUploader = new FileUploader($pdo, $session);
    $row = $form->addRow();
        $row->addLabel('file', __('Logo'));
        $row->addFileUpload('file')->accepts($fileUploader->getFileExtensions('Graphics/Design'));

    $row = $form->addRow();
        $row->addLabel('creditLicensing', __m('Logo Credits & Licensing'));
        $row->addTextArea('creditLicensing');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
