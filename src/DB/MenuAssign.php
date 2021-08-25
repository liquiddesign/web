<?php

declare(strict_types=1);

namespace Web\DB;

/**
 * @table
 * @index{"name":"menu_assign","unique":true,"columns":["fk_menuitem","fk_menutype"]}
 */
class MenuAssign extends \StORM\Entity
{
	/**
	 * Cesta
	 * @column
	 */
	public string $path;

	/**
	 * Nadřazený
	 * @relation
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 */
	public ?MenuAssign $ancestor;

	/**
	 * Položka menu
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 * @relation
	 */
	public MenuItem $menuitem;
	
	/**
	 * Typ menu
	 * @constraint{"onUpdate":"CASCADE","onDelete":"CASCADE"}
	 * @relation
	 */
	public MenuType $menutype;
}