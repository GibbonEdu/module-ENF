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

use Gibbon\Data\Validator;
use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityMentorGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityCreditGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML', 'outcomes' => 'HTML']);

$search = $_GET['search'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/opportunities_manage_add.php&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/opportunities_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $opportunityGateway = $container->get(OpportunityGateway::class);

    $data = [
        'name'                      => $_POST['name'] ?? '',
        'description'               => $_POST['description'] ?? '',
        'outcomes'                  => $_POST['outcomes'] ?? '',
        'active'                    => $_POST['active'] ?? '',
        'gibbonYearGroupIDList'     => (isset($_POST['gibbonYearGroupIDList']) && is_array($_POST['gibbonYearGroupIDList'])) ? implode(',', $_POST['gibbonYearGroupIDList']) : '',
        'creditLicensing'           => $_POST['creditLicensing'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$opportunityGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    //Deal with file upload
    if (!empty($_FILES['file']['tmp_name'])) {
        $fileUploader = new FileUploader($pdo, $session);
        $logo = $fileUploader->uploadFromPost($_FILES['file'], 'enf_opportunityLogo_'.$data['name']);

        if (empty($logo)) {
            $partialFail = true;
        }
        else {
            $data['logo'] = $logo;
        }
    }

    // Create the record
    $enfOpportunityID = $opportunityGateway->insert($data);

    //Deal with mentors
    $opportunityMentorGateway = $container->get(OpportunityMentorGateway::class);
    $gibbonPersonIDs = (isset($_POST['gibbonPersonID']) && is_array($_POST['gibbonPersonID'])) ? $_POST['gibbonPersonID'] : array();
    if (count($gibbonPersonIDs) > 0) {
        foreach ($gibbonPersonIDs as $gibbonPersonID) {
            $data = [
                'enfOpportunityID' => $enfOpportunityID,
                'gibbonPersonID'            => $gibbonPersonID
            ];
            if (!$opportunityMentorGateway->insert($data)) {
                $partialFail = true;
            }
        }
    }

    //Deal with credits
    $opportunityCreditGateway = $container->get(OpportunityCreditGateway::class);
    $enfCreditIDs = (isset($_POST['enfCreditID']) && is_array($_POST['enfCreditID'])) ? $_POST['enfCreditID'] : array();
    if (count($enfCreditIDs) > 0) {
        foreach ($enfCreditIDs as $enfCreditID) {
            $data = [
                'enfOpportunityID' => $enfOpportunityID,
                'enfCreditID'      => $enfCreditID
            ];
            if (!$opportunityCreditGateway->insert($data)) {
                $partialFail = true;
            }
        }
    }

    if ($enfOpportunityID && !$partialFail) {
        $URL .= "&return=success0&editID=$enfOpportunityID";
    }
    else if ($enfOpportunityID && $partialFail) {
        $URL .= "&return=warning1&editID=$enfOpportunityID";
    }
    else {
        $URL .= "&return=error2";
    }

    header("Location: {$URL}");
}
