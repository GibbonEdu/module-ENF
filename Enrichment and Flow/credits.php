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

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Services\Format;
use Gibbon\Module\EnrichmentandFlow\Domain\CreditGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\DomainGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/credits.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Browse Credits'));

    //Filter
    $enfDomainID = $_GET['enfDomainID'] ?? '';
    $search = $_GET['search'] ?? '';

    $form = Form::create('search', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/credits.php');

    $domainGateway = $container->get(DomainGateway::class);
    $domains = $domainGateway->selectActiveDomains()->fetchKeyPair();

    $row = $form->addRow();
        $row->addLabel('enfDomainID', __('Domain'));
        $row->addSelect('enfDomainID')->fromArray($domains)->placeholder()->selected($enfDomainID);

    $row = $form->addRow();
        $row->addLabel('search', __('Search'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

    echo $form->getOutput();

    // Query categories
    $creditGateway = $container->get(CreditGateway::class);

    $criteria = $creditGateway->newQueryCriteria()
        ->searchBy($creditGateway->getSearchableColumns(), $search)
        ->filterBy('enfDomainID', $enfDomainID)
        ->sortBy(['sequenceNumber','enfDomain.name'])
        ->fromPOST();

    $credits = $creditGateway->queryCredits($criteria, false);

    // Render table
    $gridRenderer = new GridView($container->get('twig'));
    $table = $container->get(DataTable::class)->setRenderer($gridRenderer);

    $table->setTitle(__('Credits'));

    $table->addColumn('logo', __('Logo'))
    ->notSortable()
    ->addClass('h-full')
    ->format(function($values) use ($session, $gibbon, $search, $enfDomainID) {
        $return = null;
        $background = ($values['backgroundColour']) ? "; background-color: #".$values['backgroundColour'] : '';
        $font = ($values['accentColour']) ? "color: #".$values['accentColour'] : '';
        $return .= "<a class='h-full block text-black no-underline' href='".$session->get('absoluteURL')."/index.php?q=/modules/Enrichment and Flow/credits_detail.php&enfCreditID=".$values['enfCreditID']."&search=$search&enfDomainID=$enfDomainID'><div title='".str_replace("'", "&#39;", $values['description'])."' class='h-full text-center pb-8' style='".$background."'>";
        $return .= ($values['logo'] != '') ? "<img class='pt-10 pb-2' style='max-width: 65px' src='".$session->get('absoluteURL').'/'.$values['logo']."'/><br/>":"<img class='pt-10 pb-2' style='max-width: 65px' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/><br/>";
        $return .= "<span class='font-bold underline'>".$values['name']."</span><br/>";
        $return .= "<span class='text-sm italic' style='$font'>".$values['domain']."</span><br/>";
        $return .= "</div></a>";

        return $return;
    });

    echo $table->render($credits);
}
