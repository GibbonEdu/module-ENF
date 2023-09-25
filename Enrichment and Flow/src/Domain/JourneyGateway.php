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

namespace Gibbon\Module\EnrichmentandFlow\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class JourneyGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'enfJourney';
    private static $primaryKey = 'enfJourneyID';
    private static $searchableColumns = ['name'];

    public function selectJourneyByStudent(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfJourney.*', '\'Credit\' AS type', 'enfCredit.name AS name', 'logo', 'mentor.gibbonPersonID', 'mentor.title', 'mentor.surname', 'mentor.preferredName'])
            ->from($this->getTableName())
            ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('enfCredit','enfJourney.enfCreditID=enfCredit.enfCreditID AND type=\'Credit\'')
            ->leftJoin('gibbonPerson as mentor', 'enfJourney.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->where('enfJourney.gibbonPersonIDStudent = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        $this->unionAllWithCriteria($query, $criteria)
            ->cols(['enfJourney.*', '\'Opportunity\' AS type', 'enfOpportunity.name AS name', 'logo', 'mentor.gibbonPersonID', 'mentor.title', 'mentor.surname', 'mentor.preferredName'])
            ->from($this->getTableName())
            ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->innerJoin('enfOpportunity','enfJourney.enfOpportunityID=enfOpportunity.enfOpportunityID AND type=\'Opportunity\'')
            ->leftJoin('gibbonPerson as mentor', 'enfJourney.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->where('enfJourney.gibbonPersonIDStudent = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    public function selectJourneyByStaff(QueryCriteria $criteria, $gibbonPersonID, $highestAction)
    {

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonIDStudent) {
                return $query
                    ->where('enfJourney.gibbonPersonIDStudent = :gibbonPersonIDStudent')
                    ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('enfJourney.status = :status')
                    ->bindValue('status', $status);
            }
        ]);

        if ($highestAction == 'Manage Journey_all') {
            $query = $this
                ->newQuery()
                ->cols(['enfJourney.*', '\'Credit\' AS type', 'enfCredit.name AS name', 'logo', 'student.surname', 'student.preferredName', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson AS student', 'enfJourney.gibbonPersonIDStudent=student.gibbonPersonID')
                ->innerJoin('enfCredit','enfJourney.enfCreditID=enfCredit.enfCreditID AND type=\'Credit\'')
                ->innerJoin('gibbonPerson AS mentor', 'enfJourney.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID');

            $this->unionAllWithCriteria($query, $criteria)
                ->cols(['enfJourney.*', '\'Opportunity\' AS type', 'enfOpportunity.name AS name', 'logo', 'student.surname', 'student.preferredName', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson AS student', 'enfJourney.gibbonPersonIDStudent=student.gibbonPersonID')
                ->innerJoin('enfOpportunity','enfJourney.enfOpportunityID=enfOpportunity.enfOpportunityID AND type=\'Opportunity\'')
                ->innerJoin('gibbonPerson AS mentor', 'enfJourney.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID');

        }
        else {
            $query = $this
                ->newQuery()
                ->cols(['enfJourney.*', '\'Credit\' AS type', 'enfCredit.name AS name', 'logo', 'surname', 'preferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->innerJoin('enfCredit','enfJourney.enfCreditID=enfCredit.enfCreditID AND type=\'Credit\'')
                ->where('enfJourney.gibbonPersonIDSchoolMentor = :gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);

            $this->unionAllWithCriteria($query, $criteria)
                ->cols(['enfJourney.*', '\'Opportunity\' AS type', 'enfOpportunity.name AS name', 'logo', 'surname', 'preferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->innerJoin('enfOpportunity','enfJourney.enfOpportunityID=enfOpportunity.enfOpportunityID AND type=\'Opportunity\'')
                ->where('enfJourney.gibbonPersonIDSchoolMentor = :gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectJourneyByID($enfJourneyID, $statusKey = null)
    {
        if (empty($statusKey)) {
            $query = $this
                ->newQuery()
                ->cols(['enfJourney.*', '\'Credit\' AS type', 'enfCredit.name AS name', 'logo', 'surname', 'preferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->innerJoin('enfCredit','enfJourney.enfCreditID=enfCredit.enfCreditID AND type=\'Credit\'')
                ->where('enfJourneyID = :enfJourneyID')
                ->bindValue('enfJourneyID', $enfJourneyID);

            $query->unionAll()
                ->cols(['enfJourney.*', '\'Opportunity\' AS type', 'enfOpportunity.name AS name', 'logo', 'surname', 'preferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->innerJoin('enfOpportunity','enfJourney.enfOpportunityID=enfOpportunity.enfOpportunityID AND type=\'Opportunity\'')
                ->where('enfJourneyID = :enfJourneyID')
                ->bindValue('enfJourneyID', $enfJourneyID);
        }
        else {
            $query = $this
                ->newQuery()
                ->cols(['enfJourney.*', '\'Credit\' AS type', 'enfCredit.name AS name', 'logo', 'surname', 'preferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->innerJoin('enfCredit','enfJourney.enfCreditID=enfCredit.enfCreditID AND type=\'Credit\'')
                ->where('enfJourneyID = :enfJourneyID AND statusKey = :statusKey')
                ->bindValue('enfJourneyID', $enfJourneyID)
                ->bindValue('statusKey', $statusKey);

            $query->unionAll()
                ->cols(['enfJourney.*', '\'Opportunity\' AS type', 'enfOpportunity.name AS name', 'logo', 'surname', 'preferredName'])
                ->from($this->getTableName())
                ->innerJoin('gibbonPerson', 'enfJourney.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
                ->innerJoin('enfOpportunity','enfJourney.enfOpportunityID=enfOpportunity.enfOpportunityID AND type=\'Opportunity\'')
                ->where('enfJourneyID = :enfJourneyID AND statusKey = :statusKey')
                ->bindValue('enfJourneyID', $enfJourneyID)
                ->bindValue('statusKey', $statusKey);
        }

        return $this->runSelect($query);
    }

    public function selectEvidencePending(QueryCriteria $criteria, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfJourneyID', 'student.surname AS studentsurname', 'student.preferredName AS studentpreferredName', 'mentor.title AS mentortitle', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'enfCredit.logo', 'enfCredit.name', 'timestampCompletePending', 'type'])
            ->from($this->getTableName())
            ->innerJoin('enfCredit', 'enfJourney.enfCreditID=enfCredit.enfCreditID')
            ->innerJoin('gibbonPerson AS student','enfJourney.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonPerson AS mentor','enfJourney.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->where('type=\'Credit\' AND enfJourney.status=\'Complete - Pending\' AND student.status=\'Full\' AND (student.dateStart IS NULL OR student.dateStart<=:date) AND (student.dateEnd IS NULL OR student.dateEnd>=:date)')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('date', date("Y-m-d"));

        if (!is_null($gibbonPersonID)) {
            $query->where('enfJourney.gibbonPersonIDSchoolMentor=:gibbonPersonIDSchoolMentor')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $this->unionAllWithCriteria($query, $criteria)
            ->cols(['enfJourneyID', 'student.surname AS studentsurname', 'student.preferredName AS studentpreferredName', 'mentor.title AS mentortitle', 'mentor.surname AS mentorsurname', 'mentor.preferredName AS mentorpreferredName', 'enfOpportunity.logo', 'enfOpportunity.name', 'timestampCompletePending', 'type'])
            ->from($this->getTableName())
            ->innerJoin('enfOpportunity', 'enfJourney.enfOpportunityID=enfOpportunity.enfOpportunityID')
            ->innerJoin('gibbonPerson AS student','enfJourney.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonPerson AS mentor','enfJourney.gibbonPersonIDSchoolMentor=mentor.gibbonPersonID')
            ->where('type=\'Opportunity\' AND enfJourney.status=\'Complete - Pending\' AND student.status=\'Full\' AND (student.dateStart IS NULL OR student.dateStart<=:date) AND (student.dateEnd IS NULL OR student.dateEnd>=:date)')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->bindValue('date', date("Y-m-d"))
            ->bindValue('gibbonPersonIDSchoolMentor', $gibbonPersonID);

        if (!is_null($gibbonPersonID)) {
            $query->where('gibbonPersonIDSchoolMentor=:gibbonPersonIDSchoolMentor')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectJourneyDiscussionsByStudent($gibbonPersonID, $limit = null)
    {
        $query = $this
            ->newQuery()
            ->cols(['enfJourney.*', 'enfJourney.type as journeyType', 'gibbonDiscussion.comment', 'gibbonDiscussion.type', 'gibbonDiscussion.tag', 'gibbonDiscussion.timestamp', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', '(CASE WHEN enfCredit.name IS NOT NULL THEN enfCredit.name ELSE enfOpportunity.name END) as journeyName'])
            ->from($this->getTableName())
            ->innerJoin('gibbonDiscussion', 'gibbonDiscussion.foreignTable="enfJourney" AND gibbonDiscussion.foreignTableID=enfJourney.enfJourneyID')

            ->leftJoin('gibbonPerson', 'gibbonDiscussion.gibbonPersonID=gibbonPerson.gibbonPersonID')

            ->leftJoin('enfCredit','enfJourney.enfCreditID=enfCredit.enfCreditID AND enfJourney.type=\'Credit\'')
            ->leftJoin('enfOpportunity','enfJourney.enfOpportunityID=enfOpportunity.enfOpportunityID AND enfJourney.type=\'Opportunity\'')

            ->where('enfJourney.gibbonPersonIDStudent = :gibbonPersonID')
            ->where('gibbonDiscussion.gibbonPersonID <> :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['gibbonDiscussion.timestamp DESC']);

        if (!empty($limit)) {
            $query->limit($limit);
        }

        return $this->runSelect($query);
    }
}
