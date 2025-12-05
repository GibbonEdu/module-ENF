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
use Gibbon\Forms\Form;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Http\Url;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Sessions'));

    $sessionGateway = $container->get(SessionGateway::class);

    // QUERY
    $criteria = $sessionGateway->newQueryCriteria(true)
        ->sortBy('focus')
        ->fromPOST();

    $sessions = $sessionGateway->querySessions($criteria);

    // TABLE
    $table = DataTable::createPaginated('sessions', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL(Url::fromModuleRoute('Enrichment and Flow', 'sessions_manage_addEdit'));

    $table->addColumn('focus', __('Focus'));

    $table->addColumn('type', __('Type'));

    $table->addColumn('maxStudents', __('Max Students'));

    $table->addColumn('surname', __('Created By'))
        ->format(function($values) {
            return Format::name('', $values['preferredName'], $values['surname'], 'Staff', false, true);
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('enfSessionID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Enrichment and Flow/sessions_manage_addEdit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Enrichment and Flow/sessions_manage_delete.php');
        });

    echo $table->render($sessions);
}
