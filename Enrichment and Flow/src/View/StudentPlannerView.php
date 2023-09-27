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

namespace Gibbon\Module\EnrichmentandFlow\View;

use Gibbon\View\Page;
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Module\EnrichmentandFlow\Domain\DailyPlannerGateway;
use Gibbon\Forms\Form;
use Gibbon\Http\Url;
use Gibbon\Module\EnrichmentandFlow\Domain\JourneyGateway;
use Gibbon\Domain\System\SettingGateway;

/**
 * StudentPlannerView
 *
 * A view composer class
 *
 * @version v1.1.00
 * @since   v1.1.00
 */
class StudentPlannerView
{

    protected $session;
    protected $settingGateway;
    protected $dailyPlannerGateway;
    protected $journeyGateway;
    protected $date;

    public function __construct(Session $session, SettingGateway $settingGateway, DailyPlannerGateway $dailyPlannerGateway, JourneyGateway $journeyGateway)
    {
        $this->session = $session;
        $this->settingGateway = $settingGateway;
        $this->dailyPlannerGateway = $dailyPlannerGateway;
        $this->journeyGateway = $journeyGateway;
    }

    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function compose(Page $page)
    {
        $gibbonSchoolYearID = $this->session->get('gibbonSchoolYearID');
        $gibbonPersonID = $this->session->get('gibbonPersonID');
        $guid = $this->session->get('guid');

        $class = $this->dailyPlannerGateway->getENFClassByStudent($gibbonSchoolYearID, $gibbonPersonID);
        $teachers = $this->dailyPlannerGateway->selectENFTeachersByStudent($gibbonSchoolYearID, $gibbonPersonID)->fetchAll();

        $categoryList = $this->settingGateway->getSettingByScope('Enrichment and Flow', 'taskCategories');
        $categoryList = json_decode($categoryList ?? '', true) ?? [];
        $categories = array_combine(array_column($categoryList, 'category'), array_column($categoryList, 'color'));

        $plannerEntry = $this->dailyPlannerGateway->getPlannerEntryByDate($gibbonPersonID, $this->date);
        $url = Url::fromModuleRoute('Enrichment and Flow', 'planner_view.php');

        if (empty($class)) return;

        // Display task view
        if (!empty($plannerEntry['enfPlannerEntryID'])) {
            $tasks = $this->dailyPlannerGateway->selectPlannerTasksByEntry($plannerEntry['enfPlannerEntryID'])->fetchAll();

            if (!empty($tasks)) {
                $minutes = array_sum(array_column($tasks, 'minutes'));
                $taskCode = $page->fetchFromTemplate('tasks.twig.html', [
                    'tasks' => $tasks,
                    'count' => count($tasks),
                    'minutes' => max($minutes, 140),
                    'totalMinutes' => $minutes,
                    'width' => 'w-full',
                    'categories' => $categories,
                ]);
            }
        }

        // New entry
        $form = Form::create('plannerEntry', $this->session->get('absoluteURL').'/modules/Enrichment and Flow/plannerProcess.php');
        $form->setTitle(__m('Plan & Log'));
        $form->setClass('blank');

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('enfPlannerEntryID', $plannerEntry['enfPlannerEntryID'] ?? '');
        $form->addHiddenValue('date', $this->date);

        if (!empty($taskCode)) {
            $form->addRow()->addContent('<a href="'.$url.'" class="block mb-4">'.$taskCode.'</a>');
        }

        // TASKS
        if ($this->date >= date('Y-m-d')) {
            $categories = array_column($categoryList ?? [], 'category');

            // Custom Block Template
            $addBlockButton = $form->getFactory()->createButton(__('Add Task'))->addClass('addBlock float-right');

            $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
            $row = $blockTemplate->addRow();
                $row->addSelect('category')->fromArray($categories)->setClass('w-48 mr-2')->required()->placeholder();
                $row->addNumber('minutes')->setClass('w-24 mr-2')->onlyInteger(true)->required()->placeholder(__m('Mins'));
                $row->addTextField('description')->maxLength(120)->setClass('w-full')->required()->placeholder(__('Description'))
                    ->append('<input type="hidden" id="enfPlannerTaskID" name="enfPlannerTaskID" value="">');

            // Custom Blocks
            $row = $form->addRow();
            $customBlocks = $row->addCustomBlocks('tasks', $this->session)
                ->fromTemplate($blockTemplate, true)
                ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click', 'sortable' => true))
                ->placeholder(__('Add some tasks to your plan...'))
                ->addToolInput($addBlockButton);

            // Add existing tasks, or create some blank ones
            if (!empty($tasks)) {
                foreach ($tasks ?? [] as $index => $task) {
                    $customBlocks->addBlock($index, $task);
                }
            } else {
                for ($n = 0; $n < 3; $n++) {
                    $customBlocks->addBlock($n);
                }
            }
        }

        // Existing Planner Entry
        if (!empty($plannerEntry)) {
            $discussion = $this->dailyPlannerGateway->selectPlannerEntryDiscussionByDate($plannerEntry['enfPlannerEntryID'])->fetchAll();
            $discussion = array_map(function ($item) use ($url) {
                $item['comment'] = Format::hyperlinkAll($item['comment']);
                $item['type'] = '';
                $item['url'] = $url;
                return $item;
            }, $discussion);

            $form->addRow()->addClass('')->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'discussion' => $discussion,
            ]));
        } 

        // New planner entry
        $commentBox = $form->getFactory()->createColumn()->addClass('flex flex-col');
        $commentBox->addTextArea('comment')
            ->placeholder(__m('Write about your plan for the day'))
            ->setClass('flex w-full')
            ->setRows(5)
            ->required();

        $form->addRow()->addClass(!empty($discussion) ? '-mt-4' : '')
             ->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'discussion' => [[
                    'surname'       => $this->session->get('surname'),
                    'preferredName' => $this->session->get('preferredName'),
                    'image_240'     => $this->session->get('image_240'),
                    'comment'       => $commentBox->getOutput(),
                ]]
            ]));

        $form->addRow()->addSubmit(!empty($plannerEntry) ? __m('Update My Plan') : __m('Share My Plan'));

        $page->write($form->getOutput());

        // Journey

        $journey = $this->journeyGateway->selectJourneyDiscussionsByStudent($gibbonPersonID, 3)->fetchAll();
        if (!empty($journey)) {

            $page->write('<h3>'.__m('My Recent Feedback').'</h3>');

            $journey = array_map(function ($item) {
                $item['comment'] = Format::hyperlinkAll($item['comment']);
                $item['attachmentText'] = __m($item['journeyType']).': '. $item['journeyName'];
                $item['attachmentType'] = 'Link';
                $item['attachmentLocation'] = Url::fromModuleRoute('Enrichment and Flow', 'journey_record_edit')->withQueryParams(['enfJourneyID' => $item['enfJourneyID']]);
                return $item;
            }, $journey);

            $page->writeFromTemplate('ui/discussion.twig.html', [
                'discussion' => $journey,
            ]);
        }
       
    }
}
