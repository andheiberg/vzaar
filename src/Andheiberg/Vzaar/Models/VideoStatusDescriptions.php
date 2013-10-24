<?php namespace Andheiberg\Vzaar\Models;

Class VideoStatusDescriptions
{
	const PROCESSING = "Processing not complete";
	const AVAILABLE = "Available (processing complete, video ready)";
	const EXPIRED = "Expired";
	const ON_HOLD = "On Hold (waiting for encoding to be available)";
	const FAILED = "Ecoding Failed";
	const ENCODING_UNAVAILABLE = "Encoding Unavailable";
	const NOT_AVAILABLE = "n/a";
	const REPLACED = "Replaced";
	const DELETED = "Deleted";
}