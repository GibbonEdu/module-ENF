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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/blocks_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Blocks'));

    $blockGateway = $container->get(BlockGateway::class);
  
    // QUERY
    $criteria = $blockGateway->newQueryCriteria(true)
        ->sortBy('timeStart')
        ->fromPOST();

    $blocks = $blockGateway->queryBlocks($criteria);

    // TABLE
    $table = DataTable::createPaginated('blocks', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Enrichment and Flow/blocks_manage_addEdit.php')
        ->displayLabel();

    $table->addColumn('courseNameShort', __('Course'));

    $table->addColumn('name', __('Name'));

    $table->addColumn('weekday', __('Weekday'));

    $table->addColumn('timeStart', __('Time'))
        ->format(Format::using('timeRange', ['timeStart', 'timeEnd']));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('enfBlockID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Enrichment and Flow/blocks_manage_addEdit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Enrichment and Flow/blocks_manage_delete.php');
        });

    echo $table->render($blocks);
}
