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
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityMentorGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityCreditGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/opportunities_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfOpportunityID = $_GET['enfOpportunityID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Opportunities'), 'opportunities_manage.php')
        ->add(__m('Edit Opportunity'));

    if (empty($enfOpportunityID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(OpportunityGateway::class)->getByID($enfOpportunityID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($search !='') {
        $params = [
            "search" => $search
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Enrichment and Flow', 'opportunities_manage.php')->withQueryParams($params));
    }

    $form = Form::create('category', $session->get('absoluteURL').'/modules/'.$session->get('module')."/opportunities_manage_editProcess.php?search=$search");
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfOpportunityID', $enfOpportunityID);

    $row = $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(50);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description');

    $enfCreditIDList = array();
    $credits = $container->get(OpportunityCreditGateway::class)->selectCreditsByOpportunity($enfOpportunityID);
    while ($credit = $credits->fetch()) {
        $enfCreditIDList[] = $credit['enfCreditID'];
    }
    $sql = "SELECT enfCreditID AS value, enfCredit.name, enfDomain.name AS groupBy FROM enfCredit INNER JOIN enfDomain ON (enfCredit.enfDomainID=enfDomain.enfDomainID) WHERE enfCredit.active='Y' ORDER BY enfDomain.sequenceNumber, enfDomain.name, enfCredit.name";
    $row = $form->addRow();
        $row->addLabel('enfCreditID', __m('Available Credits'))->description(__m('Which credits might a student be eligible for?'));
        $row->addSelect('enfCreditID')->selectMultiple()->fromQuery($pdo, $sql, array(), 'groupBy')->selected($enfCreditIDList);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow()->addHeading(__m('Enrolment, Mentorship & Completion'));

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Relevant student year groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFromCSV($values);;

    $gibbonPersonIDList = array();
    $people = $container->get(OpportunityMentorGateway::class)->selectMentorsByOpportunity($enfOpportunityID);
    while ($person = $people->fetch()) {
        $gibbonPersonIDList[] = $person['gibbonPersonID'];
    }
    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __m('Mentor'))->description(__m('Which staff can be selected as a mentor for this opportunity?'));
        $row->addSelectStaff('gibbonPersonID')->selectMultiple()->selected($gibbonPersonIDList);

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('outcomes', __m('Indicative Outcomes & Criteria'))->description('How can students and mentor judge progress towards completion?');
        $column->addEditor('outcomes', $guid)->setRows(15)->showMedia();

    $row = $form->addRow()->addHeading(__('Logo'));

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
