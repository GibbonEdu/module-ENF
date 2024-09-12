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
use Gibbon\Domain\Students\StudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/journey_record_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $search = $_GET['search'] ?? '';
    $enfCreditID = $_REQUEST['enfCreditID'] ?? '';
    $enfOpportunityID = $_REQUEST['enfOpportunityID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Record Journey'), 'journey_record.php')
        ->add(__m('Add'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Enrichment and Flow/journey_record_edit.php&enfJourneyID='.$_GET['editID']."&search=$search";
    }
    $page->return->setEditLink($editLink);

    if ($search !='') {
        $params = [
            "search" => $search
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Enrichment and Flow', 'journey_record.php')->withQueryParams($params));
    }

    $form = Form::create('domain', $session->get('absoluteURL').'/modules/'.$session->get('module')."/journey_record_addProcess.php?search=$search");
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $types = array(
        'Credit' => __m('Credit'),
        'Opportunity' => __m('Opportunity'),
    );
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->required()->placeholder()
            ->selected(!empty($enfCreditID)? 'Credit' : (!empty($enfOpportunityID) ? 'Opportunity' : ''));

    //Credit fields
    $form->toggleVisibilityByClass('credit')->onSelect('type')->when('Credit');

    $sql = "SELECT enfCreditID AS value, enfCredit.name, enfDomain.name AS groupBy FROM enfCredit INNER JOIN enfDomain ON (enfCredit.enfDomainID=enfDomain.enfDomainID) WHERE enfCredit.active='Y' ORDER BY enfDomain.sequenceNumber, enfDomain.name, enfCredit.name";
    $row = $form->addRow()->addClass('credit');
        $row->addLabel('enfCreditID', __m('Available Credits'))->description(__m('Which credit do you want to apply for?'));
        $row->addSelect('enfCreditID')->fromQuery($pdo, $sql, array(), 'groupBy')->required()->placeholder()->selected($enfCreditID);

    $data = array();
    $sql = 'SELECT enfCredit.enfCreditID as chainedTo, CONCAT(enfCredit.enfCreditID, \'-\', gibbonPerson.gibbonPersonID) AS value, CONCAT(surname, \', \', preferredName) AS name FROM enfCredit JOIN enfCreditMentor ON (enfCreditMentor.enfCreditID=enfCredit.enfCreditID) JOIN gibbonPerson ON (enfCreditMentor.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status=\'Full\' ORDER BY surname, preferredname';
    $row = $form->addRow()->addClass('credit');
        $row->addLabel('gibbonPersonIDSchoolMentor_credit', __('Mentor'));
        $row->addSelect('gibbonPersonIDSchoolMentor_credit')->setName('gibbonPersonIDSchoolMentor')->fromQueryChained($pdo, $sql, $data, 'enfCreditID')->required()->placeholder();

    //Opportunity fields
    $form->toggleVisibilityByClass('opportunity')->onSelect('type')->when('Opportunity');

    $studentGateway = $container->get(StudentGateway::class);
    $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
    $data = array('gibbonYearGroupID' => '%'.$student->fetch()['gibbonYearGroupID'].'%');
    $sql = "SELECT enfOpportunityID AS value, enfOpportunity.name FROM enfOpportunity WHERE enfOpportunity.active='Y' AND gibbonYearGroupIDList LIKE :gibbonYearGroupID ORDER BY enfOpportunity.name";
    $row = $form->addRow()->addClass('opportunity');
        $row->addLabel('enfOpportunityID', __m('Available Opportunities'))->description(__m('Which opportunity do you want to apply for?'));
        $row->addSelect('enfOpportunityID')->fromQuery($pdo, $sql, $data)->required()->placeholder()->selected($enfOpportunityID);

    $sql = 'SELECT enfOpportunity.enfOpportunityID as chainedTo, CONCAT(enfOpportunity.enfOpportunityID, \'-\', gibbonPerson.gibbonPersonID) AS value, CONCAT(surname, \', \', preferredName) AS name FROM enfOpportunity JOIN enfOpportunityMentor ON (enfOpportunityMentor.enfOpportunityID=enfOpportunity.enfOpportunityID) JOIN gibbonPerson ON (enfOpportunityMentor.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status=\'Full\' ORDER BY surname, preferredname';
    $row = $form->addRow()->addClass('opportunity');
        $row->addLabel('gibbonPersonIDSchoolMentor_opportunity', __('Mentor'));
        $row->addSelect('gibbonPersonIDSchoolMentor_opportunity')->setName('gibbonPersonIDSchoolMentor')->fromQueryChained($pdo, $sql, array(), 'enfOpportunityID')->required()->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
