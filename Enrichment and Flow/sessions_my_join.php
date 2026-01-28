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
use Gibbon\Forms\Form;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockFacilityGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/sessions_my_join.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfBlockID = $_REQUEST['enfBlockID'] ?? '';

    $page->breadcrumbs
        ->add(__m('My Sessions'), 'sessions_my.php')
        ->add(__m('Join a Session'));

    $block = $container->get(BlockGateway::class)->getByID($enfBlockID);
    if (empty($block)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $plannedSessionGateway = $container->get(PlannedSessionGateway::class);

    $sessions = $plannedSessionGateway->selectPlannedSessionListByBlock($enfBlockID)->fetchAll();

    $form = Form::create('sessionAddEdit', Url::fromModuleRoute('Enrichment and Flow', 'sessions_my_joinProcess')->directLink());
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('enfBlockID', $enfBlockID);

    $form->addSelect('enfPlannedSessionID')
        ->label(__('Current Sessions'))
        ->fromArray($sessions, 'enfPlannedSessionID', 'focus', 'type')
        ->placeholder()
        ->required();

    $form->addRow('submit')->addSubmit();

    echo $form->getOutput();
}
