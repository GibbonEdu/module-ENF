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

namespace Gibbon\Module\EnrichmentandFlow\Forms;

use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;



/**
 * DateSelectForm
 */
class DateSelectForm
{
    protected $session;
    protected $db;
    protected $substituteGateway;
    protected $specialDayGateway;
    protected $staffCoverageDateGateway;
    protected $coverageMode;
    protected $internalCoverage;

    public function __construct(Session $session)
    {
        $this->session = $session;
    
    }

    public function createForm($url, $date)
    {
        $form = Form::create('dateSelect', $url, 'get');
        $form->setClass('blank w-full');

        $form->addHiddenValue('q', $this->session->get('address'));

        $row = $form->addRow()->addClass('flex flex-wrap mb-4');
        // if (!empty($announcement)) {
        //     $row->addContent('<h3>'.__m('Announcements').'</h3>');
        // }

        $col = $row->addColumn()->addClass('flex items-center justify-end gap-2');

        $col->addDate('date')->setValue(Format::date($date))->setClass('w-48');
        $col->addSubmit(__('Go'));

        return $form;
    }
}
