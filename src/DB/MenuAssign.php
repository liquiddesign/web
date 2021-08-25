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
	 * Priorita
	 * @column
	 */
	public int $priority = 10;

	/**
	 * Skryto
	 * @column
	 */
	public bool $hidden = false;

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