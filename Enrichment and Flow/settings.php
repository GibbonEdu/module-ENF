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

use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Enrichment and Flow/settings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Settings'));

    $settingGateway = $container->get(SettingGateway::class);

    // FORM
    $form = Form::create('settings', $session->get('absoluteURL').'/modules/Enrichment and Flow/settingsProcess.php');
    $form->setTitle(__('Settings'));

    $form->addHiddenValue('address', $session->get('address'));

    $setting = $settingGateway->getSettingByScope('Enrichment and Flow', 'indexText', true);
    
    $column = $form->addRow()->addColumn();
        $column->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $column->addEditor($setting['name'], $guid)->required()->setValue($setting['value']);

    // CATEGORIES
    $setting = $settingGateway->getSettingByScope('Enrichment and Flow', 'taskCategories', true);

    $addBlockButton = $form->getFactory()->createButton(__('Add Category'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow();
        $row->addTextField('category')->setClass('w-full mr-2')->required()->placeholder(__m('Category Name'));
        $row->addColor('color')->setClass('w-48 mr-2 colorField')->required()->setValue('#ffffff');

    // Custom Blocks
    $column = $form->addRow()->addColumn();
    $column->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
    $customBlocks = $column->addCustomBlocks('taskCategories', $session)
        ->fromTemplate($blockTemplate, true)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click', 'sortable' => true))
        ->placeholder(__('Add some categories...'))
        ->addToolInput($addBlockButton);

    // Add existing tasks, or create some blank ones
    $tasks = json_decode($setting['value'] ?? '', true);
    if (!empty($tasks)) {
        foreach ($tasks ?? [] as $index => $task) {
            $customBlocks->addBlock($index, $task);
        }
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
<script>
    $(document).on('change', '.colorPicker', function () {
        var target = $(this).next('input.colorField');
        $(target).val($(this).val());
    });

    $(document).on('change', '.colorField', function () {
        var target = $(this).prev('input.colorPicker');
        $(target).val($(this).val());
    });

    $('#taskCategories').on('addedBlock', function (event, block) {
        $('.colorPicker', block).each(function () {
            var target = $(this).next('input.colorField');
            $(this).val($(target).val());
        });
    });
</script>
