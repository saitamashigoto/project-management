<?php

namespace Piyush\ProjectManagement\Api\Data;

interface PomodoroInterface
{
    public const ENTITY_ID = 'entity_id';
    public const PROJECT_ID = 'project_id';
    public const DURATION = 'duration';
    public const VALUE = 'value';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId();
}