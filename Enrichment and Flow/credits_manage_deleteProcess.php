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
use Gibbon\Module\EnrichmentandFlow\Domain\CreditGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\CreditMentorGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfCreditID = $_POST['enfCreditID'] ?? '';
$enfDomainID = $_GET['enfDomainID'] ?? '';
$search = $_GET['search'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/credits_manage.php&enfDomainID=$enfDomainID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/credits_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($enfCreditID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $creditGateway = $container->get(CreditGateway::class);
    $values = $creditGateway->getByID($enfCreditID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $creditGateway->delete($enfCreditID);

    $creditMentorGateway = $container->get(CreditMentorGateway::class);
    $creditMentorGateway->deleteMentorsByCredit($enfCreditID);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
