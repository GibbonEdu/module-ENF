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
use Gibbon\Forms\Form;
use Gibbon\Http\Url;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Module\EnrichmentandFlow\Domain\JourneyGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\BlockGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\DailyPlannerGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionGateway;
use Gibbon\Module\EnrichmentandFlow\Domain\SessionStudentGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Module\EnrichmentandFlow\ENFFormat;
use Gibbon\Module\EnrichmentandFlow\Domain\PlannedSessionTeacherGateway;

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
    protected $connection;
    protected $dailyPlannerGateway;
    protected $blockGateway;
    protected $plannedSessionGateway;
    protected $plannedSessionTeacherGateway;
    protected $sessionStudentGateway;
    protected $journeyGateway;
    protected $date;

    public function __construct(Session $session, Connection $connection, SettingGateway $settingGateway, DailyPlannerGateway $dailyPlannerGateway, BlockGateway $blockGateway, PlannedSessionGateway $plannedSessionGateway, PlannedSessionTeacherGateway $plannedSessionTeacherGateway,  SessionStudentGateway $sessionStudentGateway, JourneyGateway $journeyGateway)
    {
        $this->session = $session;
        $this->connection = $connection;
        $this->settingGateway = $settingGateway;
        $this->dailyPlannerGateway = $dailyPlannerGateway;
        $this->plannedSessionGateway = $plannedSessionGateway;
        $this->plannedSessionTeacherGateway = $plannedSessionTeacherGateway;
        $this->sessionStudentGateway = $sessionStudentGateway;
        $this->blockGateway = $blockGateway;
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
        $connection2 = $this->connection->getConnection();

        $class = $this->dailyPlannerGateway->getENFClassByPerson($gibbonSchoolYearID, $gibbonPersonID);
        $teachers = $this->dailyPlannerGateway->selectENFTeachersByStudent($gibbonSchoolYearID, $gibbonPersonID)->fetchAll();

        $categoryList = $this->settingGateway->getSettingByScope('Enrichment and Flow', 'taskCategories');
        $categoryList = json_decode($categoryList ?? '', true) ?? [];
        $categories = array_combine(array_column($categoryList, 'category'), array_column($categoryList, 'color'));

        $plannerEntry = $this->dailyPlannerGateway->getPlannerEntryByDate($gibbonPersonID, $this->date);
        $url = Url::fromModuleRoute('Enrichment and Flow', 'planner_view.php');

        if (empty($class)) return;

        if (empty($this->date) || $this->date < date('Y-m-d')) {
            $page->write(Format::alert(__m('The selected date is in the past. Use My Planner to view past planner logs.'), 'empty'));
            return;
        }

        if ($this->date > date('Y-m-d')) {
            $page->write(Format::alert(__m('The selected date is in the future.'), 'empty'));
            return;
        }

        $blocks = $this->blockGateway->selectBlocksByWeekday(Format::dayOfWeekName($this->date))->fetchAll();
        if (empty($blocks) || !isSchoolOpen($guid, $this->date, $connection2)) {
            $page->write(Format::alert(__m('There are no ENF sessions running on this date.'), 'empty'));
            return;
        }
        
        $currentBlock = current($blocks);
        $beforeStartTime = date('H:i:s') < $currentBlock['signUpStart'];
        if ($currentBlock['signUpSameDay'] == 'Y' && $beforeStartTime) {
            $page->write(Format::alert(__m('ENF sessions will open for sign-up at {time} ({relative})', ['time' => Format::time($currentBlock['signUpStart']), 'relative' => Format::relativeTime($this->date.' '.$currentBlock['signUpStart'])]), 'message'));

            $this->listSessions($page, $blocks);
            return;
        }

        $studentSessions = $this->sessionStudentGateway->selectSessionsByStudentsAndDate($gibbonPersonID, $this->date)->fetchGroupedUnique();
        $canModify = true;

        if (!empty($plannerEntry)) {
            $timeRemaining = time() - Format::timestamp($plannerEntry['timestampCreated']);
            $canModify = $timeRemaining < 300;
        }

        $viewSessions = $_GET['viewSessions'] ?? false;
        if ($viewSessions) {
            $this->listSessions($page, $blocks);
        }

        // Display task view
        // if (!empty($plannerEntry['enfPlannerEntryID'])) {
        //     $tasks = $this->dailyPlannerGateway->selectPlannerTasksByEntry($plannerEntry['enfPlannerEntryID'])->fetchAll();

        //     if (!empty($tasks)) {
        //         $minutes = array_sum(array_column($tasks, 'minutes'));
        //         $taskCode = $page->fetchFromTemplate('tasks.twig.html', [
        //             'tasks' => $tasks,
        //             'count' => count($tasks),
        //             'minutes' => max($minutes, 140),
        //             'totalMinutes' => $minutes,
        //             'width' => 'w-full',
        //             'categories' => $categories,
        //         ]);
        //     }
        // }

        // New entry
        $form = Form::createBlank('plannerEntry', $this->session->get('absoluteURL').'/modules/Enrichment and Flow/plannerProcess.php');
        $form->setTitle(__m('Plan & Log'));
        $form->setClass('blank');

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('enfPlannerEntryID', $plannerEntry['enfPlannerEntryID'] ?? '');
        $form->addHiddenValue('date', $this->date);

        $form->addHeaderAction('view', $viewSessions ? __m('Hide All Sessions') : __m('View All Sessions'))
            ->setURL('/modules/Enrichment and Flow/planner.php')
            ->addParams(['viewSessions' => !$viewSessions])
            ->setIcon($viewSessions ? 'eye-slash' : 'eye', '', 'basic')
            ->displayLabel();

        $form->addHeaderAction('planner', __m('My Planner'))
            ->setURL('/modules/Enrichment and Flow/planner_view.php')
            ->displayLabel();

        if (!empty($plannerEntry)) {
            $relativeTime = Format::relativeTime(date('Y-m-d H:i:s', strtotime($plannerEntry['timestampCreated']) + 300), true, false);
            $modfificationAlert = $canModify
                ? Format::alert(__m('You have created a plan for today. You have {relative} left to make any changes.', ['relative' => strtolower($relativeTime)]), 'success')
                : Format::alert(__m('Your plan has been created and shared with your teachers. You cannot change locations at this time.'), 'empty');
            $form->addRow()->addContent($modfificationAlert);
        } else {
            $form->addRow()->addContent(Format::alert(__m('Choose your location for each period and submit the form to create your plan.'), 'message'));
        }

        foreach ($blocks as $block) {
            $currentSession = $studentSessions[$block['enfBlockID']] ?? null;
            $locked = ($currentSession['locked'] ?? 'N') == 'Y';
            
            $sessions = $this->plannedSessionGateway->selectAvailablePlannedSessionsByBlock($block['enfBlockID'], $this->date)->fetchAll();
            $sessions = array_reduce($sessions, function ($group, $values) {
                $group[$values['enfPlannedSessionID']] = $values['facility'].' - '.$values['focus'];
                return $group;
            }, []);

            $section = $form->addRow()->setClass('flex flex-col mb-4 border rounded-md bg-blue-50 p-6');

            $ajaxURL = Url::fromHandlerModuleRoute('fullscreen.php', 'Enrichment and Flow', 'planner_blockAjax.php');

            $row = $section->addColumn()->setClass('flex items-center');
            $row->addLabel($block['name'], $block['name'])->addClass('w-40 font-semibold');

            if ($canModify && !$locked) {
                $row->addSelect("enfPlannedSessionID[{$block['enfBlockID']}]")
                    ->fromArray($sessions)
                    ->placeholder(__('Select your location...'))
                    ->required()
                    ->addClass('flex-1 relative')
                    ->setAttribute('hx-get', $ajaxURL)
                    ->setAttribute('hx-trigger', $currentSession ? 'load, input delay:300ms' : 'input delay:300ms')
                    ->setAttribute('hx-target', 'next .blockContent')
                    ->setAttribute('hx-vals', json_encode(['date' => $this->date]))
                    ->selected($currentSession['enfPlannedSessionID'] ?? '');
            } else {
                $sessionName = $currentSession ? $currentSession['facility'].' - '.$currentSession['focus'] : __('Unknown');
                if ($locked) {
                    $sessionName .= icon('solid', 'lock-closed', 'ml-6 mr-2 size-4 text-gray-600 align-text-bottom').Format::tag(__('Pre-set Session'), 'empty');
                }
                $row->addContent($sessionName)->addClass('text-sm');
                $row->addTextField('sessionLabel[]')
                    ->addClass('hidden')
                    ->readOnly()
                    ->setAttribute('hx-get', $ajaxURL)
                    ->setAttribute('hx-trigger', 'load')
                    ->setAttribute('hx-target', 'next .blockContent')
                    ->setAttribute('hx-vals', json_encode(['date' => $this->date, 'enfPlannedSessionID' => $currentSession['enfPlannedSessionID'] ?? '']));
            }

            $row = $section->addColumn()->setClass('flex items-center');
            $row->addContent('')->setClass('blockContent w-full');
        }
        

        // if (!empty($taskCode)) {
        //     $form->addRow()->addContent('<a href="'.$url.'" class="block mb-4">'.$taskCode.'</a>');
        // }

        // TASKS
        // if ($this->date >= date('Y-m-d')) {
        //     $categories = array_column($categoryList ?? [], 'category');

        //     // Custom Block Template
        //     $addBlockButton = $form->getFactory()->createButton(__('Add Task'))->addClass('addBlock float-right');

        //     $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
        //     $row = $blockTemplate->addRow();
        //         $row->addSelect('category')->fromArray($categories)->setClass('w-48 mr-2')->required()->placeholder();
        //         $row->addTextField('description')->maxLength(120)->setClass('flex-1')->required()->placeholder(__('Description'));
        //         $row->addNumber('minutes')->setClass('w-48 mr-2')->onlyInteger(true)->required()->placeholder(__m('Mins'));

        //     // Custom Blocks
        //     $row = $form->addRow();
        //     $customBlocks = $row->addCustomBlocks('tasks', $this->session)
        //         ->fromTemplate($blockTemplate, true)
        //         ->settings([
        //             'inputNameStrategy' => 'object',
        //             'addOnEvent'        => 'click',
        //             'sortable'          => true,
        //             'expanded'          => empty($tasks),
        //             'uniqueID'          => 'enfPlannerTaskID',
        //         ])
        //         ->placeholder(__('Add some tasks to your plan...'))
        //         ->addToolInput($addBlockButton);

        //     // Add existing tasks, or create some blank ones
        //     if (!empty($tasks)) {
        //         foreach ($tasks ?? [] as $index => $task) {
        //             $customBlocks->addBlock($index, $task);
        //         }
        //     } else {
        //         for ($n = 0; $n < 3; $n++) {
        //             $customBlocks->addBlock($n);
        //         }
        //     }
        // }

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
            ->required(empty($plannerEntry));

        $form->addRow()->addClass(!empty($discussion) ? '' : '')
             ->addContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                'discussion' => [[
                    'surname'       => $this->session->get('surname'),
                    'preferredName' => $this->session->get('preferredName'),
                    'image_240'     => $this->session->get('image_240'),
                    'comment'       => $commentBox->getOutput(),
                ]]
            ]));

        $form->addRow()->addSubmit(!empty($plannerEntry) && !empty($studentSessions) ? __m('Update My Plan') : __m('Share My Plan'));

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

    protected function listSessions(Page $page, array $blocks)
    {
        foreach ($blocks as $block) {
            $sessions = $this->plannedSessionGateway->selectPlannedSessionsByDate($block['enfBlockID'], $this->date)->fetchAll();
            $sessions = array_map(function ($values) {
                $values['teachers'] = $this->plannedSessionTeacherGateway->selectTeachersByPlannedSession($values['enfPlannedSessionID'], $this->date)->fetchAll();
                $values['students'] = $this->sessionStudentGateway->selectStudentsByPlannedSessionAndDate($values['enfPlannedSessionID'], $this->date)->fetchAll();
                $values['studentCount'] = count($values['students']);
                return $values;
            }, $sessions);

            $table = DataTable::create('plannedSessions');
            $table->setTitle($block['name']);
            $table->setDescription($block['weekday'] .' ('.Format::timeRange($block['timeStart'], $block['timeEnd']).')');

            $table->addColumn('facility', __('Locaiton'))->width('10%');

            $table->addColumn('focus', __('Focus'))->width('20%');

            $table->addColumn('teachers', __('Teachers'))
                ->width('20%')
                ->format(function($values){
                    return Format::nameList($values['teachers'] ?? [], 'Staff', false, false);
                });

            $beforeStartTime = date('H:i:s') < $block['signUpStart'];
            if ($block['signUpSameDay'] == 'Y' && $beforeStartTime) {
                $table->addColumn('maxStudents', __('Capacity'))->width('10%');
            } else {
                $table->addColumn('studentCount', __('Students'))
                    ->width('15%')
                    ->format(function($values) use (&$page) {
                        return $page->fetchFromTemplate('ui/progress.twig.html', [
                            'progressCount'  => $values['studentCount'],
                            'totalCount'     => $values['maxStudents'],
                            'leftCount'      => $values['maxStudents'] - $values['studentCount'],
                            'progressLabel'  => __('Full'),
                            'width'          => 'w-32',
                            'progressColour' => 'blue',
                            'title'          => __('Students'),
                        ]);
                    });
            }

            $table->addColumn('type', __('Type'))
                ->width('15%')
                ->format(function($values){
                    return ENFFormat::sessionTag($values['type']);
                });
            

            $page->write($table->render($sessions));
        }
    }
}
