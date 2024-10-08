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
use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\FileUploader;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\JourneyGateway;
use Gibbon\Data\Validator;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/journey_record_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $validator = $container->get(Validator::class);
    $enfJourneyID = $_GET['enfJourneyID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__m('Record Journey'), 'journey_record.php');

    if (empty($enfJourneyID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $result = $container->get(JourneyGateway::class)->selectJourneyByID($enfJourneyID);

    if ($result->rowCount() != 1) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $values = $result->fetch();

    $page->breadcrumbs
        ->add($values['name']." (".$values['status'].")");

    if ($search !='') {
        $params = [
            "search" => $search
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Enrichment and Flow', 'journey_record.php')->withQueryParams($params));
    }

    if ($values['status'] == 'Current - Pending') {
        $page->addWarning(__m('This journey is pending mentor agreement, and so cannot be edited at this time.'));
        return;
    }

    if ($values['type'] == 'Opportunity') {
        $page->navigator->addHeaderAction('view', __m('View Opportunity Details'))
            ->setURL('/modules/Enrichment and Flow/opportunities_detail.php')
            ->addParams(["enfOpportunityID" => $values['enfOpportunityID']])
            ->displayLabel();
    } else if ($values['type'] == 'Credit') {
        $page->navigator->addHeaderAction('view', __m('View Credit Details'))
            ->setURL('/modules/Enrichment and Flow/credits_detail.php')
            ->addParams(["enfCreditID" => $values['enfCreditID']])
            ->displayLabel();
    }

    //Render log
    $discussionGateway = $container->get(DiscussionGateway::class);
    $logs = $discussionGateway->selectDiscussionByContext('enfJourney', $enfJourneyID);
    if ($logs->rowCount() < 1) {
        $page->addMessage(__m('The conversation has not yet begun.'), 'warning');
    }
    else {
        echo "<h2>".__m('Conversation Log')."</h2>";

        //Legend
        $templateView = new View($container->get('twig'));
        echo $templateView->fetchFromTemplate('legend.twig.html');

        while ($log = $logs->fetch()) {
            $log['comment'] = $validator->sanitizeRichText($log['comment']);
            echo $page->fetchFromTemplate('logEntry.twig.html', [
                'log' => $log
            ]);
        }
    }

    //New log form
    if ($values['status'] != 'Current - Pending') {
        echo "<h2>".__m('New Entry')."</h2>";
        $form = Form::create('log', $session->get('absoluteURL').'/modules/'.$session->get('module')."/journey_record_editProcess.php?enfJourneyID=$enfJourneyID&search=$search");
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));

        $types = array(
            'Comment' => __m('Comment'),
        );
        if ($values['status'] != 'Complete - Approved') {
            $types['Evidence'] = __m('Evidence');
        }
        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->required()->fromArray($types)->placeholder()->required();

        $row = $form->addRow();
            $column = $row->addColumn();
            $column->addLabel('comment', __m('Comment'));
            $column->addEditor('comment', $guid)->setRows(15)->showMedia()->required();

        $form->toggleVisibilityByClass('evidence')->onSelect('type')->when('Evidence');
        $form->toggleVisibilityByClass('evidenceLink')->onSelect('evidenceType')->when('Link');
        $form->toggleVisibilityByClass('evidenceFile')->onSelect('evidenceType')->when('File');

        $evidenceTypes = array(
            'Link' => __m('Link'),
            'File' => __m('File'),
        );
        $row = $form->addRow()->addClass('evidence');
            $row->addLabel('evidenceType', __('Evidence Type'));
            $row->addSelect('evidenceType')->fromArray($evidenceTypes)->placeholder()->required();

        $row = $form->addRow()->addClass('evidenceLink');
            $row->addLabel('evidenceLink', __('Link'));
            $row->addURL('evidenceLink')->required()->required();

        $fileUploader = new FileUploader($pdo, $session);
        $row = $form->addRow()->addClass('evidenceFile');
            $row->addLabel('evidenceFile', __('File'));
            $row->addFileUpload('evidenceFile')->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
