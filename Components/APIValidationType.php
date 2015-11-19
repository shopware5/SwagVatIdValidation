<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class APIValidationType
{
    /* The type for no api validation */
    const NONE = 1;

    /* Providing this type will use simple api validation */
    const SIMPLE = 2;

    /* Providing this type will use extended api validation */
    const EXTENDED = 3;
}
