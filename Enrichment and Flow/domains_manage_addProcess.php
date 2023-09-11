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
use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Module\EnrichmentandFlow\Domain\DomainGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Enrichment and Flow/domains_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/domains_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $domainGateway = $container->get(domainGateway::class);

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
    if (!$domainGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    //Deal with file upload
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

    // Create the record
    $enfDomainID = $domainGateway->insert($data);

    if ($enfDomainID && !$partialFail) {
        $URL .= "&return=success0&editID=$enfDomainID";
    }
    else if ($enfDomainID && $partialFail) {
        $URL .= "&return=error6&editID=$enfDomainID";
    }
    else {
        $URL .= "&return=error2";
    }

    header("Location: {$URL}");
}
