<?php

class DataRetriever
{
    private array $user;
    private array $people;
    private array $organizations;
    private array $events;

    public function __construct()
    {
        // In a real app, this would be a database call.
        $this->user = json_decode(file_get_contents(__DIR__ . '/../data/user.json'), true);
        $this->people = json_decode(file_get_contents(__DIR__ . '/../data/people.json'), true);
        $this->organizations = json_decode(file_get_contents(__DIR__ . '/../data/organizations.json'), true);
        $this->events = json_decode(file_get_contents(__DIR__ . '/../data/events.json'), true);
    }

    /**
     * Builds a text-based context blob for a given person's name.
     */
    public function getContextForPerson(string $name): string
    {
        $person = null;
        foreach ($this->people as $p) {
            if (stripos($p['name']['givenName'] . ' ' . $p['name']['familyName'], $name) !== false) {
                $person = $p;
                break;
            }
        }

        if (!$person) {
            return "FACT: Person named '$name' not found in the database.";
        }

        $context = "CONTEXT FACTS FOR: {$person['name']['givenName']} {$person['name']['familyName']} (ID: {$person['personId']})\n";

        // Find relationship in user.json
        foreach ($this->user['relationships']['professionalNetwork'] ?? [] as $rel) {
            if ($rel['personId'] === $person['personId']) {
                $context .= "- User's relationship: {$rel['relation']}\n";
            }
        }

        // Find events they were involved in
        foreach ($this->events as $event) {
            if (in_array($person['personId'], $event['involvedPeopleIds'])) {
                $orgName = $this->findOrgNameById($event['relatedOrgId']);
                $context .= "- Involved in event '{$event['name']}' at {$orgName}.\n";
                $context .= "  - Event Outcome: {$event['outcome']}\n";
            }
        }

        return $context;
    }
    
    private function findOrgNameById(string $orgId): string
    {
        foreach ($this->organizations as $org) {
            if ($org['orgId'] === $orgId) {
                return $org['name'];
            }
        }
        return 'Unknown Organization';
    }
}