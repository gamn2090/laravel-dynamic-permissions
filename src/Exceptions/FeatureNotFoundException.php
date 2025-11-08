<?php

namespace gamn2090\DynamicPermissions\Exceptions;

use Exception;

class FeatureNotFoundException extends Exception
{
	public static function forSlug(string $slug): self
	{
		return new self("Feature with slug '{$slug}' not found.");
	}

	public static function forId(int $id): self
	{
		return new self("Feature with ID '{$id}' not found.");
	}
}
