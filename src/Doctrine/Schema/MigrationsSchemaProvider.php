<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;

/**
 * The migrations schema provider is only used if the Doctrine migrations bundle is
 * installed, because it implements the necessary interface.
 */
class MigrationsSchemaProvider extends DcaSchemaProvider implements SchemaProviderInterface
{
}
