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
use Gibbon\Module\EnrichmentandFlow\Domain\DomainGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$enfDomainID = $_POST['enfDomainID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Enrichment and Flow/domains_manage_edit.php&enfDomainID='.$enfDomainID;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/domains_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {

    // Proceed!
    $domainGateway = $container->get(DomainGateway::class);

    $data = [
        'name'              => $_POST['name'] ?? '',
        'description'       => $_POST['description'] ?? '',
        'active'            => $_POST['active'] ?? '',
        'backgroundColour'  => $_POST['backgroundColour'] ?? '',
        'accentColour'      => $_POST['accentColour'] ?? '',
        'creditLicensing'   => $_POST['creditLicensing'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($data['active'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$domainGateway->unique($data, ['name'], $enfDomainID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }



    //Deal with file upload
    $data['logo'] = $_POST['logo'];
    if (!empty($_FILES['file']['tmp_name'])) {
        $fileUploader = new FileUploader($pdo, $session);
        $logo = $fileUploader->uploadFromPost($_FILES['file'], 'enf_domainLogo_'.$data['name']);

        if (empty($logo)) {
            $partialFail = true;
        }
        else {
            $data['logo'] = $logo;
        }
    }

    // Update the record
    $updated = $domainGateway->update($enfDomainID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
