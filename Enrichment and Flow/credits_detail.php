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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Module\EnrichmentandFlow\Domain\CreditGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\DomainGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\CreditMentorGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/credits_detail.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $enfCreditID = $_GET['enfCreditID'] ?? '';
    $enfDomainID = $_GET['enfDomainID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__m('Browse Credits'), 'credits.php')
        ->add(__m('Credit Details'));

    if (empty($enfCreditID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $result = $container->get(CreditGateway::class)->selectCreditByID($enfCreditID);

    if ($result->rowCount() != 1) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $values = $result->fetch();

    if ($enfDomainID != '' || $search !='') {
        $params = [
            "search" => $search,
            "enfDomainID" => $enfDomainID
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Enrichment and Flow', 'credits.php')->withQueryParams($params));
    }

    // CREDIT DETAILS TABLE
    $table = DataTable::createDetails('unitDetails');

    $table->addMetaData('allowHTML', ['outcomes']);

    $table->addColumn('name', '')
        ->addClass('col-span-2 text-lg font-bold')
        ->format(function ($values) {
            $return = $values['name']."<br/>";
            $return .= Format::small($values['domain']);
            return $return;
        });

    $table->addColumn('logo', '')
        ->addClass('row-span-4 text-right')
        ->format(function ($values) use ($session) {
            if ($values['logo'] == null) {
                return "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_125.jpg'/><br/>";
            } else {
                return "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$values['logo']."'/><br/>";
            }
        });

    $table->addColumn('description', __m('Description'))->addClass('col-span-2');
    $table->addColumn('outcomes', __m('Indicative Outcomes & Criteria'))->addClass('col-span-2');

    $table->addColumn('mentors', __m('Mentors'))
        ->addClass('col-span-2')
        ->format(function ($values) use ($container, $enfCreditID) {
            $return = '';

            $mentors = $container->get(CreditMentorGateway::class)->selectMentorsByCredit($enfCreditID);
            if ($mentors->rowCount() < 1) {
                $return .= __('N/A');
            }
            else {
                $return .= "<ul>";
                while ($mentor = $mentors->fetch()) {
                    $return .= "<li>".Format::name($mentor['title'], $mentor['preferredName'], $mentor['surname'], 'Staff', true, true)."</li>";
                }
                $return .= "</ul>";
            }

            return $return;
        });

    echo $table->render([$values]);
}
