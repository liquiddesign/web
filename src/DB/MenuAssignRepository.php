<?php

declare(strict_types=1);

namespace Web\DB;

use Common\DB\IGeneralRepository;
use Nette\Http\Request;
use StORM\Collection;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

/**
 * @extends \StORM\Repository<\Web\DB\MenuAssign>
 */
class MenuAssignRepository extends Repository
{
}
