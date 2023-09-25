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
use Gibbon\Module\EnrichmentandFlow\Domain\AnnouncementGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['content' => 'HTML']);

$enfAnnouncementID = $_POST['enfAnnouncementID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Enrichment and Flow/announcements_manage_edit.php&enfAnnouncementID='.$enfAnnouncementID;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/announcements_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {

    // Proceed!
    $announcementGateway = $container->get(AnnouncementGateway::class);

    $data = [
        'date'    => !empty($_POST['date']) ? Format::dateConvert($_POST['date']) : '',
        'content' => $_POST['content'] ?? '',
        'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
    ];

    // Validate the required values are present
    if (empty($data['date']) || empty($data['content'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$announcementGateway->unique($data, ['date'], $enfAnnouncementID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $announcementGateway->update($enfAnnouncementID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
