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
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['indexText' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Enrichment and Flow/settings.php';

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/settings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $partialFail = false;
    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'Enrichment and Flow' => [
            'indexText',
            'taskCategories',
        ],
    ];

    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name) {
            $value = $_POST[$name] ?? '';

            // Handle categories
            if ($name == 'taskCategories') {
                $value = array_combine(array_keys($_POST['order'] ?? []), array_values($value));
                $value = array_map(function ($item) {
                    $item['color'] = !empty($item['color']) ? preg_replace('/[^a-fA-F0-9\#]/', '', mb_substr($item['color'], 0, 7)) : '';
                    return $item;
                }, $value);
                ksort($value);
                $value = json_encode($value);
            }

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
            $partialFail &= !$updated;
        }
    }

    $URL .= $partialFail
        ? '&return=error2'
        : '&return=success0';
    header("Location: {$URL}");
}
