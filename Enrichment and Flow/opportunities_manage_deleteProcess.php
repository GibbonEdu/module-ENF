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

use Gibbon\Data\Validator;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityMentorGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\OpportunityCreditGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfOpportunityID = $_POST['enfOpportunityID'] ?? '';
$search = $_GET['search'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/opportunities_manage.php&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/opportunities_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($enfOpportunityID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $opportunityGateway = $container->get(OpportunityGateway::class);
    $values = $opportunityGateway->getByID($enfOpportunityID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $opportunityGateway->delete($enfOpportunityID);

    $opportunityMentorGateway = $container->get(OpportunityMentorGateway::class);
    $opportunityMentorGateway->deleteMentorsByOpportunity($enfOpportunityID);

    $opportunityCreditGateway = $container->get(OpportunityCreditGateway::class);
    $opportunityCreditGateway->deleteCreditsByOpportunity($enfOpportunityID);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
