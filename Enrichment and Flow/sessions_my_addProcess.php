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
use Gibbon\Data\Validator;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$enfSessionID = $_REQUEST['enfSessionID'] ?? '';
$URL = Url::fromModuleRoute('Enrichment and Flow', 'sessions_manage_addEdit')->withQueryParams(['enfSessionID' => $enfSessionID]);

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $sessionGateway = $container->get(SessionGateway::class);

    $data = [
        'type' => $_POST['type'] ?? '',
        'focus' => $_POST['focus'] ?? '',
        'maxStudents' => $_POST['maxStudents'] ?? null,
        'description' => $_POST['description'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['type']) || empty($data['focus'])) {
        header("Location: {$URL}&return=error1");
        exit;
    }

    // Validate that this record is unique
    if (!$sessionGateway->unique($data, ['focus'], $enfSessionID)) {
        header("Location: {$URL}&return=error7");
        exit;
    }

    
    if ($sessionGateway->exists($enfSessionID)) {
        // Update
        $sessionGateway->update($enfSessionID, $data + [
            'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
            'timestampModified'      => date('Y-m-d H:i:s'),
        ]);

    } else {
        // Insert
        $enfSessionID = $sessionGateway->insert($data + [
            'gibbonPersonIDCreated' => $session->get('gibbonPersonID'),
            'timestampCreated'      => date('Y-m-d H:i:s'),
        ]);

        $URL .= "&editID=$enfSessionID";
    }

    $URL .= !empty($enfSessionID)
        ? "&return=success0"
        : "&return=error2";

    header("Location: {$URL}");
}
