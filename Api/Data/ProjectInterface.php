<?php

namespace Piyush\ProjectManagement\Api\Data;

interface ProjectInterface
{
    public const ENTITY_ID = 'entity_id';
    public const CUSTOMER_ID = 'customer_id';
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const DUE_DATE = 'due_date';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId();
}