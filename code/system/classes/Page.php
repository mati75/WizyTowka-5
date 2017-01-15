<?php

/**
* WizyTówka 5
* Page — database object model.
*/
namespace WizyTowka;

class Page extends DatabaseObject
{
	static protected $_tableName = 'Pages';
	static protected $_tableColumns = [
		'slug',
		'contentType',
		'title',
		'titleHead',
		'description',
		'isDraft',
		'contents',
		'settings',
		'userId',
		'languageId',
		'updatedTime',
		'createdTime',
	];
	static protected $_tableColumnsJSON = [
		'contents',
		'settings',
	];
	static protected $_tableColumnsTimeAtInsert = [
		'updatedTime',
		'createdTime',
	];
	static protected $_tableColumnsTimeAtUpdate = [
		'updatedTime',
	];

	static public function getAll()
	{
		return static::_getByWhereCondition('isDraft = 0');
	}
	// This method overwrites DatabaseObject::getAll().
	// Page::getAll() returns public pages, Page::getAllDrafts() returns pages with draft status.

	static public function getAllDrafts()
	{
		return static::_getByWhereCondition('isDraft = 1');
	}

	static public function getByLanguageId($languageId)
	{
		return static::_getByWhereCondition('isDraft = 0 AND languageId = :languageId', ['languageId' => $languageId]);
	}

	static public function getDraftsByLanguageId($languageId)
	{
		return static::_getByWhereCondition('isDraft = 1 AND languageId = :languageId', ['languageId' => $languageId]);
	}

	static public function getBySlug($slug)
	{
		return static::_getByWhereCondition('slug = :slug', ['slug' => $slug], true);
	}
}