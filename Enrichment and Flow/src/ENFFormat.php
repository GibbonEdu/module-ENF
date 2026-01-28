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

namespace Gibbon\Module\EnrichmentandFlow;

use Gibbon\Services\Format;

/**
 * Reusable formats for displaying ENF details
 */
class ENFFormat
{
    public static $sessionTypes = [
        'Consolidation' => 'bg-orange-100 border-orange-800/15 text-orange-800',
        'Extension'     => 'bg-sky-100 border-sky-800/15 text-sky-800',
        'Innovation'    => 'bg-purple-100 border-purple-800/15 text-purple-800',
        'Enrichment'    => 'bg-emerald-100 border-emerald-800/15 text-emerald-800',
        'Other'         => 'bg-pink-100 border-pink-800/15 text-pink-800',
    ];
    public static function sessionTag(string $type, ?string $tag = null)
    {
        return Format::tag($tag ?? $type, self::$sessionTypes[$type] ?? '');
    }
}
